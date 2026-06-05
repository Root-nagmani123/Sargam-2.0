<?php

namespace App\DataTables;

use App\Models\StudentMasterCourseMap;
use Illuminate\Database\Eloquent\Builder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Button;

class OtDirectoryDataTable extends DataTable
{
    private $status = 'active';
    private $courseId = 0;

    public function setStatus($status)
    {
        $this->status = in_array($status, ['active', 'archived'], true) ? $status : 'active';
        return $this;
    }

    public function setCourseId($courseId)
    {
        $this->courseId = (int) $courseId;
        return $this;
    }

    public function dataTable(Builder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->editColumn('display_name', function ($row) {
                $photo = !empty($row->photo_path) 
                    ? '<img src="' . asset('storage/' . $row->photo_path) . '" alt="photo" class="ot-photo" loading="lazy" onerror="this.style.display=\'none\';this.nextElementSibling.style.display=\'\';">'
                    : '';
                $avatar = '<span class="ot-avatar" ' . (!empty($row->photo_path) ? 'style="display:none"' : '') . '>' . strtoupper(substr($row->display_name ?? '-', 0, 1)) . '</span>';
                return '<div class="d-flex align-items-center gap-2">' . $photo . $avatar . '<span class="ot-name">' . ($row->display_name ?: '-') . '</span></div>';
            })
            ->editColumn('generated_OT_code', fn($row) => $row->generated_OT_code ?: '-')
            ->editColumn('email', fn($row) => $row->email ?: '-')
            ->editColumn('course_name', fn($row) => $row->course_name ?: '-')
            ->editColumn('cadre_name', fn($row) => $row->cadre_name ?: '-')
            ->rawColumns(['display_name'])
            ->setRowClass('ot-row');
    }

    public function query(): Builder
    {
        $today = now()->toDateString();
        
        $studentsQuery = StudentMasterCourseMap::query()
            ->join('student_master as sm', 'student_master_course__map.student_master_pk', '=', 'sm.pk')
            ->join('course_master as cm', 'student_master_course__map.course_master_pk', '=', 'cm.pk')
            ->leftJoin('cadre_master as cad', 'sm.cadre_master_pk', '=', 'cad.pk')
            ->where('student_master_course__map.active_inactive', 1)
            ->where('sm.status', 1)
            ->where('cm.active_inactive', 1)
            ->select([
                'sm.pk',
                'sm.display_name',
                'sm.generated_OT_code',
                'sm.email',
                'sm.photo_path',
                'cm.course_name',
                'cad.cadre_name',
            ]);

        // Filter by course
        if ($this->courseId > 0) {
            $studentsQuery->where('cm.pk', $this->courseId);
        }

        // Filter by status (active = ongoing, archived = ended)
        if ($this->status === 'archived') {
            $studentsQuery->where('cm.end_date', '<', $today);
        } else {
            $studentsQuery->where('cm.end_date', '>=', $today);
        }

        return $studentsQuery;
    }

    public function html(): Column
    {
        return $this->builder()
            ->setTableId('otDirectoryTable')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom('lBfrtip')
            ->orderBy(1, 'asc')
            ->lengthMenu([10, 25, 50, 100])
            ->pageLength(10)
            ->buttons([
                Button::make('csv')
                    ->text('CSV')
                    ->className('btn btn-sm btn-success'),
                Button::make('excel')
                    ->text('Excel')
                    ->className('btn btn-sm btn-success'),
            ]);
    }

    protected function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')
                ->title('S. No.')
                ->searchable(false)
                ->orderable(false)
                ->width('50px'),
            Column::make('display_name')
                ->title('Name'),
            Column::make('generated_OT_code')
                ->title('OT Code'),
            Column::make('email')
                ->title('Email ID'),
            Column::make('course_name')
                ->title('Course Name'),
            Column::make('cadre_name')
                ->title('Cadre Name'),
        ];
    }

    protected function filename(): string
    {
        return 'OT_Directory_' . date('YmdHis');
    }
}
