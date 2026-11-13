<?php

namespace Tests\Feature;

use App\Models\LeaveApplication;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

class LeaveOnBehalfExportSearchTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    public static function searches(): array
    {
        return [
            'reason' => ['F082match', 2],
            'OT code' => ['F082code', 3],
            'display name' => ['F082display', 3],
            'first name' => ['F082first', 3],
            'last name' => ['F082last', 3],
            'course' => ['F082course', 3],
            'nature' => ['F082nature', 3],
            'empty search' => ['', 3],
            'no matches' => ['F082absent', 0],
            'array search' => [['nested' => ['F082match']], 3],
        ];
    }

    /** @dataProvider searches */
    public function test_grid_excel_and_pdf_use_the_same_search($search, int $count): void
    {
        $operator = $this->staffWithRole('Super Admin');
        $map = DB::table('student_master_course__map')->first();
        $nature = DB::table('leave_nature_master')->value('pk');
        $otherCourse = DB::table('course_master')->where('pk', '!=', $map?->course_master_pk)->value('pk');
        if (! $map || ! $nature || ! $otherCourse) {
            $this->markTestSkipped('needs an enrolled trainee, leave nature and another course');
        }

        DB::table('student_master')->where('pk', $map->student_master_pk)->update([
            'generated_OT_code' => 'F082code',
            'display_name' => 'F082display',
            'first_name' => 'F082first',
            'last_name' => 'F082last',
        ]);
        DB::table('course_master')->where('pk', $map->course_master_pk)->update(['course_name' => 'F082course']);
        DB::table('leave_nature_master')->where('pk', $nature)->update(['nature_name' => 'F082nature']);

        $date = '2096-10-14';
        $common = [
            'course_master_pk' => $map->course_master_pk,
            'student_master_pk' => $map->student_master_pk,
            'leave_nature_master_pk' => $nature,
            'applied_by_user_pk' => $operator->pk,
            'leave_type' => LeaveApplication::TYPE_STATIONED_LEAVE,
            'from_date' => $date,
            'to_date' => $date,
            'total_days' => 1,
            'status' => LeaveApplication::STATUS_APPROVED,
        ];
        foreach (['F082match one', 'F082match two', 'F082other'] as $reason) {
            DB::table('leave_application')->insert($common + ['reason' => $reason]);
        }
        foreach ([
            ['from_date' => '2096-10-15', 'to_date' => '2096-10-15'],
            ['course_master_pk' => $otherCourse],
            ['applied_by_user_pk' => null],
        ] as $excluded) {
            DB::table('leave_application')->insert(array_replace($common, $excluded, ['reason' => 'F082match excluded']));
        }

        $query = [
            'course_filter' => $map->course_master_pk,
            'from_date' => $date,
            'to_date' => $date,
            'search' => ['value' => $search],
        ];
        $this->as($operator, ['Super Admin']);
        $grid = $this->getJson(route('admin.leave-on-behalf.index', $query + [
            'draw' => 1, 'start' => 0, 'length' => 100,
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $grid->assertOk()->assertJsonPath('recordsFiltered', $count);
        $expected = array_column($grid->json('data'), 'reason');
        $this->assertCount($count, $expected);

        $excel = $this->get(route('admin.leave-on-behalf.export', $query + ['format' => 'excel']));
        $excel->assertOk();
        $path = $excel->baseResponse->getFile()->getPathname();
        $spreadsheet = IOFactory::load($path);
        try {
            $rows = array_slice($spreadsheet->getActiveSheet()->toArray(null, false, false, false), 6);
            $reasons = array_values(array_filter(array_column($rows, 10), fn ($value) => $value !== null && $value !== ''));
            $this->assertSame($expected, $reasons);
        } finally {
            $spreadsheet->disconnectWorksheets();
            unlink($path);
        }

        $pdf = app('dompdf.wrapper');
        Pdf::shouldReceive('loadView')->once()->andReturnUsing(function ($view, $data) use ($pdf, $expected) {
            $this->assertSame('admin.exports.table_pdf', $view);
            $this->assertSame($expected, $data['rows']->pluck(10)->all());

            return $pdf->loadView($view, $data);
        });
        $response = $this->get(route('admin.leave-on-behalf.export', $query + ['format' => 'pdf']));
        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }
}
