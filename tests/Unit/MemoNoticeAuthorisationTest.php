<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Guards the Memo / Notice module's authorisation wiring.
 *
 * The rule this locks down was previously applied method by method inside the
 * controller, and four of nine write methods were missing it — creating a Show Cause
 * Memo, closing a case with a marks deduction, issuing a notice, and deleting messages
 * from a disciplinary conversation all ran for any authenticated user. Moving the check
 * to route middleware fixed that; this test is what stops it drifting back.
 *
 * The second half matters as much as the first: the participant-facing routes must stay
 * OUT of the gate, or an Officer Trainee cannot read or reply to their own notice.
 *
 * Route-level rather than request-level because the application's tables are legacy with
 * no factories and the full suite cannot execute in this environment — see
 * CourseRepositoryDocumentAccessTest for the detail.
 *
 * Run with:  php vendor/bin/phpunit --filter=MemoNoticeAuthorisation
 */
class MemoNoticeAuthorisationTest extends TestCase
{
    private const GUARD = 'memo.notice.manager';

    /** Every action that writes to a disciplinary record. */
    private const WRITE_ROUTES = [
        'memo.notice.management.store_memo_notice',
        'memo.notice.management.store_memo_status',
        'memo.notice.management.update_memo_status',
        'memo.notice.management.editNotice',
        'memo.notice.management.update_notice_template',
        'memo.notice.management.endChat',
        'memo.notice.management.noticedeleteMessage',
        'send.notice.direct.save',
    ];

    /** Routes an Officer Trainee uses to read and reply to their own notice. */
    private const PARTICIPANT_ROUTES = [
        'memo.notice.management.user',
        'memo.notice.management.conversation_student',
        'memo.notice.management.memo_notice_conversation_student',
    ];

    public function test_every_disciplinary_write_route_is_gated(): void
    {
        foreach (self::WRITE_ROUTES as $name) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route, "Route {$name} is not registered.");

            $middleware = $route->gatherMiddleware();

            $this->assertContains('auth', $middleware, "Route {$name} is not behind auth.");
            $this->assertContains(
                self::GUARD,
                $middleware,
                "Route {$name} writes to a disciplinary record but is not behind the "
                . self::GUARD . " gate — any authenticated user could call it."
            );
        }
    }

    /** Gating these would break the reply path the module is built around. */
    public function test_participant_routes_are_not_gated(): void
    {
        foreach (self::PARTICIPANT_ROUTES as $name) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route, "Route {$name} is not registered.");

            $this->assertNotContains(
                self::GUARD,
                $route->gatherMiddleware(),
                "Route {$name} is participant-facing and must not require manager roles."
            );
        }
    }

    /** The middleware has to be resolvable, or the routes above fail at request time. */
    public function test_the_guard_alias_is_registered(): void
    {
        $kernel = app(\App\Http\Kernel::class);

        $property = new \ReflectionProperty($kernel, 'routeMiddleware');
        $property->setAccessible(true);
        $aliases = $property->getValue($kernel);

        $this->assertArrayHasKey(self::GUARD, $aliases, 'Guard alias is not registered in the HTTP kernel.');
        $this->assertTrue(
            class_exists($aliases[self::GUARD]),
            'Guard alias points at a class that does not exist: ' . $aliases[self::GUARD]
        );
    }

    /**
     * The middleware duplicates the controller's role list. If one is edited without the
     * other, the module gets two different answers to the same question — so pin them
     * together here.
     */
    public function test_middleware_and_controller_agree_on_the_permitted_roles(): void
    {
        $roles = function (string $class, string $method): array {
            $reflection = new \ReflectionMethod($class, $method);
            $source = file_get_contents($reflection->getDeclaringClass()->getFileName());
            $body = implode("\n", array_slice(
                explode("\n", $source),
                $reflection->getStartLine() - 1,
                $reflection->getEndLine() - $reflection->getStartLine() + 1
            ));
            preg_match_all("/hasRole\('([^']+)'\)/", $body, $m);
            $found = $m[1];
            sort($found);

            return $found;
        };

        $this->assertSame(
            $roles(\App\Http\Controllers\Admin\CourseAttendanceNoticeMapController::class, 'userCanManageMemoNotice'),
            $roles(\App\Http\Middleware\EnsureMemoNoticeManager::class, 'canManage'),
            'The middleware and the controller permit different role sets.'
        );
    }
}
