<?php

namespace App\DataTables;

use App\Models\{StudentCourseGroupMap, GroupTypeMasterCourseMasterMap};
use App\Models\CourseStudentAttendance;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Illuminate\Http\Request;
use Yajra\DataTables\Services\DataTable;

class StudentAttendanceListDataTable extends DataTable
{
    public $group_pk;
    public $course_pk;

    public function __construct($group_pk, $course_pk)
    {
        $this->group_pk = $group_pk;
        $this->course_pk = $course_pk;
    }
    

    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->addColumn('student_name', function($row) {
                return '<label class="text-dark">' . $row->studentsMaster->display_name . '</label>';
            })
            ->addColumn('student_code', function($row) {
                return '<label class="text-dark">' . $row->studentsMaster->generated_OT_code . '</label>';
            })
            ->addColumn('attendance_status', function ($row) {
                return $this->renderRadioGroup($row, 'attendance_status', [
                    1 => 'Present', 2 => 'Late', 3 => 'Absent'
                ]);
            })
            ->addColumn('mdo_duty', function ($row) {
                return $this->renderRadio($row, 4, 'MDO', 'text-dark');
            })
            ->addColumn('escort_duty', function ($row) {
                return $this->renderRadio($row, 5, 'Escort', 'text-dark');
            })
            ->addColumn('medical_exempt', function ($row) {
                return $this->renderRadio($row, 6, 'Medical Exempted', 'text-dark');
            })
            ->addColumn('other_exempt', function ($row) {
                return $this->renderRadio($row, 7, 'Other Exempted', 'text-dark');
            })
            ->filterColumn('student_name', function ($query, $keyword) {
                $query->whereHas('studentsMaster', function ($q) use ($keyword) {
                    $q->where('display_name', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('student_code', function ($query, $keyword) {
                $query->whereHas('studentsMaster', function ($q) use ($keyword) {
                    $q->where('generated_OT_code', 'like', "%{$keyword}%");
                });
            })
            ->rawColumns(['student_name', 'student_code', 'attendance_status', 'mdo_duty', 'escort_duty', 'medical_exempt', 'other_exempt']);
    }

    public function query(): QueryBuilder
    {
        
        $groupTypeMaster = GroupTypeMasterCourseMasterMap::where('pk', $this->group_pk)
                    ->where('course_name', $this->course_pk)
                    ->first();
        return StudentCourseGroupMap::with(['studentsMaster:display_name,generated_OT_code,pk', 'attendance' => fn($q) => $q->where('course_master_pk', $this->course_pk)->where('student_course_group_map_pk', $this->group_pk)])
                    ->where('group_type_master_course_master_map_pk', $groupTypeMaster->pk);
                    // dd($student->toSql());

    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('studentAttendanceTable')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(1);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('#')->orderable(false)->searchable(false),
            Column::make('student_name')->title('OT Name')->orderable(false),
            Column::make('student_code')->title('OT Code')->orderable(false),
            Column::make('attendance_status')->title('Attendance')->orderable(false)->searchable(false),
            Column::make('mdo_duty')->title('MDO Duty')->orderable(false)->searchable(false),
            Column::make('escort_duty')->title('Escort Duty')->orderable(false)->searchable(false),
            Column::make('medical_exempt')->title('Medical Exemption')->orderable(false)->searchable(false),
            Column::make('other_exempt')->title('Other Exemption')->orderable(false)->searchable(false),
        ];
    }

    protected function renderRadio($row, $value, $label, $labelClass = 'text-dark'): string
    {
        $studentId = $row->studentsMaster->pk;
        $courseStudent = CourseStudentAttendance::where('Student_master_pk', $studentId)
            ->where('Course_master_pk', $this->coursePk)
            ->where('student_course_group_map_pk', $this->groupPk)
            ->first();

        $checked = ($courseStudent && $courseStudent->status == $value) ? 'checked' : '';

        return '
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="student[' . $studentId . ']" value="' . $value . '" ' . $checked . '>
            <label class="form-check-label ' . $labelClass . '">' . $label . '</label>
        </div>';
    }

    protected function renderRadioGroup($row, $field, $options): string
    {
        $studentId = $row->studentsMaster->pk;
        $courseStudent = CourseStudentAttendance::where('Student_master_pk', $studentId)
            ->where('Course_master_pk', $this->coursePk)
            ->where('student_course_group_map_pk', $this->groupPk)
            ->first();

        $html = '';
        foreach ($options as $value => $label) {
            if( $value == 1 ) $labelClass = 'text-success';
            elseif( $value == 2 ) $labelClass = 'text-warning';
            elseif( $value == 3 ) $labelClass = 'text-danger';
            else $labelClass = 'text-dark';
            $checked = ($courseStudent && $courseStudent->status == $value) ? 'checked' : '';
            $html .= '
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="student[' . $studentId . ']" value="' . $value . '" ' . $checked . '>
                <label class="form-check-label ' . $labelClass . '">' . $label . '</label>
            </div>';
        }

        return $html;
    }

    protected function filename(): string
    {
        return 'StudentAttendanceList_' . date('YmdHis');
    }
}
