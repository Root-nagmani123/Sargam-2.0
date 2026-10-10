<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\UserController;
use Illuminate\Http\Request;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * Merge 7082e5204 replaced otParticipantsRowsFor()'s body (PR #334 round 4):
 *  - F-030: it discarded the caller's coordinated $students and rebuilt a wider,
 *    uncoordinated payload — taught-only courses and memo-only students included;
 *  - F-031: it let the shared filter's narrower search drop rows the page search
 *    would have matched (mobile number, counsellor, duty type);
 *  - F-032: the grid's Cadre/House read different fields from the filter and the
 *    export.
 *
 * Rows here are synthetic, with student pks far above any real one, so no DB
 * row is needed and the comment-count queries return nothing.
 */
class OtParticipantsRosterTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    private function invokePrivate(string $method, ...$args)
    {
        $m = new \ReflectionMethod(UserController::class, $method);
        $m->setAccessible(true);

        return $m->invoke(app(UserController::class), ...$args);
    }

    private function row(int $pk, array $student = [], array $row = []): object
    {
        $sm = (object) array_merge([
            'pk' => $pk,
            'display_name' => "Test Trainee {$pk}",
            'first_name' => null,
            'last_name' => null,
            'generated_OT_code' => "OT{$pk}",
            'email' => "t{$pk}@example.test",
            'contact_no' => '9000000000',
            'user_id' => "ot{$pk}",
            'cadre' => (object) ['cadre_name' => 'StudentCadre'],
        ], $student);

        return (object) array_merge([
            'student_master_pk' => $pk,
            'studentMaster' => $sm,
            'course' => null,
            'course_master_pk' => null,
            'groupMapping' => null,
            'house_name' => 'GANG-116',
            'cadre_name' => null,
            'counsellor_name' => null,
            'house_group' => null,
            'house_groups' => [],
            'house_faculty_name' => null,
        ], $row);
    }

    public function test_the_list_holds_exactly_the_students_the_caller_passed(): void
    {
        $students = collect([$this->row(990000001), $this->row(990000002)]);

        $rows = $this->invokePrivate('otParticipantsRowsFor', Request::create('/', 'GET'), $students);

        $this->assertSame(
            [990000001, 990000002],
            $rows->pluck('student_master_pk')->sort()->values()->all()
        );
    }

    public function test_a_mobile_number_search_finds_the_trainee(): void
    {
        $students = collect([
            $this->row(990000001, ['contact_no' => '9876501234']),
            $this->row(990000002, ['contact_no' => '9000000000']),
        ]);
        $request = Request::create('/', 'GET', ['search' => ['value' => '9876501234']]);

        $rows = $this->invokePrivate('otParticipantsRowsFor', $request, $students);
        $found = $this->invokePrivate('otParticipantsApplySearch', $request, $rows, []);

        $this->assertSame([990000001], $found->pluck('student_master_pk')->all());
    }

    public function test_grid_and_export_show_the_same_cadre_and_house_on_the_normal_view(): void
    {
        $students = collect([$this->row(990000001, [], [
            'cadre_name' => 'AGMUT',
            'house_group' => 'Nanda Devi',
            'house_groups' => ['Nanda Devi'],
        ])]);
        $request = Request::create('/', 'GET', ['draw' => 1, 'start' => 0, 'length' => 10]);

        $rows = $this->invokePrivate('otParticipantsRowsFor', $request, $students);
        $rowMeta = [990000001 => [
            'duty_count' => 0, 'duty_type' => '-', 'medical' => 0, 'pt' => 0,
            'stationed' => 0, 'notice_memo' => 0, 'discipline_memo' => 0,
        ]];

        $grid = $this->invokePrivate('otParticipantsDataTableResponse', $request, $rows, $rowMeta, $rows->count(), [])
            ->getData(true)['data'][0];
        $export = $this->invokePrivate('otParticipantsExportData', $rows, $rowMeta, false)['rows'][0];

        $this->assertSame('AGMUT', $grid['cadre']);
        $this->assertSame('Nanda Devi', $grid['house']);
        $this->assertSame($export[6], $grid['cadre']);
        $this->assertSame($export[9], $grid['house']);
    }

    public function test_the_cadre_filter_and_the_cadre_column_agree(): void
    {
        $students = collect([
            $this->row(990000001, [], ['cadre_name' => 'AGMUT']),
            $this->row(990000002, [], ['cadre_name' => 'Kerala']),
        ]);
        $request = Request::create('/', 'GET', ['cadre' => 'AGMUT']);

        $rows = $this->invokePrivate('otParticipantsRowsFor', $request, $students);

        $this->assertSame([990000001], $rows->pluck('student_master_pk')->all());
        $this->assertSame('AGMUT', $this->invokePrivate('otParticipantCadreLabel', $rows->first()));
    }
}
