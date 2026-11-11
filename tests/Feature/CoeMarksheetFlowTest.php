<?php

namespace Tests\Feature;

use App\Models\COE\Marksheet;
use App\Models\COE\SignatureMaster;
use App\Models\User;
use App\Services\COE\MarksheetService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * COE → Document Management: marksheets of the OTs ranked in Merit & Ranking are
 * Generated, Verify & Publish needs the UDC, AAO and COE signatures, and Verified
 * Marksheet lists what was published. Development database, in a transaction.
 */
class CoeMarksheetFlowTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::role('Super Admin')->first() ?? $this->markTestSkipped('Needs a Super Admin.');
    }

    /** A Generated marksheet, as the listing has it; with $unsigned, one nobody has signed and that can be signed. */
    private function generated(bool $unsigned = false): array
    {
        if (! MarksheetService::enabled()) {
            $this->markTestSkipped('Needs the coe_marksheets table.');
        }
        if ($unsigned && ! MarksheetService::signingEnabled()) {
            $this->markTestSkipped('Needs the coe_marksheets signed column.');
        }
        foreach (['active', 'archived'] as $scope) {
            foreach (app(MarksheetService::class)->rows($scope) as $row) {
                if (! $row['published'] && (! $unsigned || empty($row['signatures']))) {
                    return $row;
                }
            }
        }
        $this->markTestSkipped('Needs an OT ranked in Merit & Ranking with a Pass / Fail'.($unsigned ? ', not signed yet.' : '.'));
    }

    public function test_the_screens_open(): void
    {
        $admin = $this->actingAs($this->admin());

        foreach (['admin.coe.marksheet-generation.index', 'admin.coe.verified-marksheet.index'] as $route) {
            $admin->get(route($route))->assertOk();
            $admin->get(route($route, ['scope' => 'archived']))->assertOk();
        }
        $admin->get(route('admin.coe.marksheet-generation.verify', ['course' => '1', 'ots' => ['1']]))->assertNotFound();
    }

    public function test_every_listed_marksheet_is_a_ranked_ot_with_a_result_and_a_number(): void
    {
        $row = $this->generated();

        $this->assertContains($row['result'], ['Pass', 'Fail']);
        $this->assertMatchesRegularExpression('#^MS/[A-Z0-9]+/\d{4}/\d{4}$#', $row['number']);
        $this->assertTrue(Marksheet::where('course_master_pk', (int) $row['course_key'])
            ->where('student_master_pk', (int) $row['code'])->where('marksheet_number', $row['number'])->exists());

        $this->actingAs($this->admin())->get(route('admin.coe.marksheet-generation.index'))->assertOk();
        $page = $this->actingAs($this->admin())->get(route('admin.coe.marksheet.show', ['course' => $row['course_key'], 'ot' => $row['code']]))
            ->assertOk()->assertSee($row['number'])->assertSee($row['name']);
        // Nobody has signed it yet: no signatures on it.
        if (empty($row['signatures'])) {
            $page->assertDontSee('signature-master/', false);
        }
    }

    public function test_view_and_verify_lists_every_pending_marksheet_of_the_course_clicked_one_first(): void
    {
        $row = $this->generated();
        $pending = collect(app(MarksheetService::class)->courseRows($row['course_key']))->where('published', false);

        $rows = $this->actingAs($this->admin())
            ->get(route('admin.coe.marksheet-generation.verify', ['course' => $row['course_key'], 'first' => $row['code']]))
            ->assertOk()->viewData('rows');

        $this->assertSame($row['code'], $rows[0]['code']);
        $this->assertEqualsCanonicalizing($pending->pluck('code')->all(), array_column($rows, 'code'));
    }

    public function test_publish_is_validated(): void
    {
        $this->actingAs($this->admin())->postJson(route('admin.coe.marksheet-generation.publish'), ['course' => 'x', 'ots' => []])
            ->assertUnprocessable()->assertJsonValidationErrors(['course', 'ots']);
    }

    public function test_publish_is_refused_until_all_three_signatures_are_active(): void
    {
        $row = $this->generated(true);
        SignatureMaster::where('designation_name', 'AAO')->update(['active_inactive' => 0]);

        $this->actingAs($this->admin())->postJson(route('admin.coe.marksheet-generation.publish'), ['course' => $row['course_key'], 'ots' => [$row['code']]])
            ->assertUnprocessable()->assertJsonPath('message', fn ($m) => str_contains($m, 'AAO'));

        $this->assertFalse(Marksheet::where('course_master_pk', (int) $row['course_key'])
            ->where('student_master_pk', (int) $row['code'])->where('status', Marksheet::STATUS_PUBLISHED)->exists());
    }

    public function test_publish_saves_the_marksheet_and_lists_it_as_verified(): void
    {
        $row = $this->generated(true);
        if (app(MarksheetService::class)->signatureProblem() !== null) {
            $this->markTestSkipped('Needs one Active UDC, AAO and COE signature with its image.');
        }

        $this->actingAs($this->admin())->postJson(route('admin.coe.marksheet-generation.publish'), ['course' => $row['course_key'], 'ots' => [$row['code']]])
            ->assertOk()->assertJsonPath('published', 1);

        $record = Marksheet::where('course_master_pk', (int) $row['course_key'])->where('student_master_pk', (int) $row['code'])->first();
        $this->assertTrue($record->isPublished());
        $this->assertEquals($row['grand'], $record->snapshot['grand']);
        $this->assertEqualsCanonicalizing(['UDC', 'AAO', 'COE'], array_keys($record->snapshot['signatures']));

        // Published: signed.
        $this->actingAs($this->admin())->get(route('admin.coe.marksheet.show', ['course' => $row['course_key'], 'ot' => $row['code']]))
            ->assertOk()->assertSee('signature-master/', false);

        // Publishing again changes nothing.
        $this->actingAs($this->admin())->postJson(route('admin.coe.marksheet-generation.publish'), ['course' => $row['course_key'], 'ots' => [$row['code']]])
            ->assertOk()->assertJsonPath('published', 0);

        $scope = collect(app(MarksheetService::class)->rows('active'))->contains('number', $row['number']) ? [] : ['scope' => 'archived'];
        $this->actingAs($this->admin())->get(route('admin.coe.verified-marksheet.index', $scope))->assertOk()->assertSee($row['number']);
    }
}
