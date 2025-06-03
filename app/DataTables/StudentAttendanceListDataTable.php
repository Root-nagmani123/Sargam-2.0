<?php

namespace App\DataTables;

use App\Models\{StudentCourseGroupMap, GroupTypeMasterCourseMasterMap, MDOEscotDutyMap, CourseStudentAttendance};
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class StudentAttendanceListDataTable extends DataTable
{
    public function __construct(
        protected int $group_pk,
        protected int $course_pk,
        protected int $timetable_pk
    ) {}

    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->addColumn('student_name', fn($row) => '<label class="text-dark">' . $row->studentsMaster->display_name . '</label>')
            ->addColumn('student_code', fn($row) => '<label class="text-dark">' . $row->studentsMaster->generated_OT_code . '</label>')
            ->addColumn('attendance_status', fn($row) => $this->renderRadioGroup($row, 'attendance_status', [1 => 'Present', 2 => 'Late', 3 => 'Absent']))
            ->addColumn('mdo_duty', fn($row) => $this->renderRadio($row, 4, 'MDO'))
            ->addColumn('escort_duty', fn($row) => $this->renderRadio($row, 5, 'Escort'))
            ->addColumn('medical_exempt', fn($row) => $this->renderRadio($row, 6, 'Medical Exempted'))
            ->addColumn('other_exempt', fn($row) => $this->renderRadio($row, 7, 'Other Exempted'))
            ->filterColumn('student_name', fn($query, $keyword) => $query->whereHas('studentsMaster', fn($q) => $q->where('display_name', 'like', "%{$keyword}%")))
            ->filterColumn('student_code', fn($query, $keyword) => $query->whereHas('studentsMaster', fn($q) => $q->where('generated_OT_code', 'like', "%{$keyword}%")))
            ->rawColumns(['student_name', 'student_code', 'attendance_status', 'mdo_duty', 'escort_duty', 'medical_exempt', 'other_exempt']);
    }

    public function query(): QueryBuilder
    {
        $groupTypeMaster = GroupTypeMasterCourseMasterMap::where('pk', $this->group_pk)
            ->where('course_name', $this->course_pk)

            ->firstOrFail();

        return StudentCourseGroupMap::with([
                'studentsMaster:display_name,generated_OT_code,pk',
                'attendance' => fn($q) => $q->where('course_master_pk', $this->course_pk)
                                          ->where('student_course_group_map_pk', $this->group_pk)
            ])
            ->where('group_type_master_course_master_map_pk', $groupTypeMaster->pk);
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('studentAttendanceTable')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(1)
            ->parameters([
                'paging' => false,           
                'searching' => true,         
                'info' => false,             
                'scrollY' => '100vh',        
                'scrollCollapse' => true,
                'responsive' => true,
                'scrollX' => true,
                'autoWidth' => false,
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('#')->addClass('text-center')->orderable(false)->searchable(false),
            Column::make('student_name')->title('OT Name')->addClass('text-center')->orderable(false),
            Column::make('student_code')->title('OT Code')->addClass('text-center')->orderable(false),
            Column::make('attendance_status')->title('Attendance')->addClass('text-center')->orderable(false)->searchable(false),
            Column::make('mdo_duty')->title('MDO Duty')->addClass('text-center')->orderable(false)->searchable(false),
            Column::make('escort_duty')->title('Escort Duty')->addClass('text-center')->orderable(false)->searchable(false),
            Column::make('medical_exempt')->title('Medical Exemption')->addClass('text-center')->orderable(false)->searchable(false),
            Column::make('other_exempt')->title('Other Exemption')->addClass('text-center')->orderable(false)->searchable(false),
        ];
    }

    protected function renderRadio($row, int $value, string $label, string $labelClass = 'text-dark'): string
    {
        $studentId = $row->studentsMaster->pk;
        $courseStudent = CourseStudentAttendance::where([
            ['Student_master_pk', '=', $studentId],
            ['Course_master_pk', '=', $this->course_pk],
            ['student_course_group_map_pk', '=', $this->group_pk],
            ['timetable_pk', '=', $this->timetable_pk]
        ])->first();

        $checked = ($courseStudent && $courseStudent->status == $value) ? 'checked' : '';

        if (!$checked && $value === 4) {
            $mdoEscot = MDOEscotDutyMap::where([
                ['course_master_pk', '=', $this->course_pk],
                ['mdo_duty_type_master_pk', '=', MDOEscotDutyMap::getMdoDutyTypes()['mdo']],
                ['selected_student_list', '=', $studentId],
            ])->first();

            if ($mdoEscot) {
                $courseStudentMDO = CourseStudentAttendance::where([
                    ['Student_master_pk', '=', $studentId],
                    ['Course_master_pk', '=', $this->course_pk],
                    ['student_course_group_map_pk', '=', $this->group_pk],
                    ['timetable_pk', '=', $this->timetable_pk]
                ])->first();
                if($courseStudentMDO) {
                    $checked = 'checked';
                }
            }
        }

        return "<div class='form-check form-check-inline'>
                    <input class='form-check-input' type='radio' name='student[{$studentId}]' value='{$value}' {$checked} id='student[{$studentId}][{$value}]'>
                    <label class='form-check-label {$labelClass}' for='student[{$studentId}][{$value}]'>{$label}</label>
                </div>";
    }

    protected function renderRadioGroup($row, string $field, array $options): string
    {
        $studentId = $row->studentsMaster->pk;
        $courseStudent = CourseStudentAttendance::where([
            ['Student_master_pk', '=', $studentId],
            ['Course_master_pk', '=', $this->course_pk],
            ['student_course_group_map_pk', '=', $this->group_pk],
            ['timetable_pk', '=', $this->timetable_pk]
        ])->first();

        $html = '';

        foreach ($options as $value => $label) {
            $labelClass = match ($value) {
                1 => 'text-success',
                2 => 'text-warning',
                3 => 'text-danger',
                default => 'text-dark',
            };

            $checked = ($courseStudent && $courseStudent->status == $value) ? 'checked' : '';

            $html .= "<div class='form-check form-check-inline'>
                        <input class='form-check-input' type='radio' name='student[{$studentId}]' value='{$value}' {$checked} id='student[{$studentId}][{$value}]'>
                        <label class='form-check-label {$labelClass}' for='student[{$studentId}][{$value}]'>{$label}</label>
                    </div>";
        }

        return $html;
    }

    protected function filename(): string
    {
        return 'StudentAttendanceList_' . date('YmdHis');
    }
}
