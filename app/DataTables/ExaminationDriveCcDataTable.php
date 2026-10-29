<?php

namespace App\DataTables;

use App\Models\ExaminationDrive;
use App\Services\COE\CourseCoordinatorService;
use Illuminate\Database\Eloquent\Builder;
use Yajra\DataTables\EloquentDataTable;

/**
 * COE → Examination Management → Examination Drive (CC) grid.
 *
 * Same columns as the faculty grid, limited to the drives of the courses the
 * logged-in faculty is Course Coordinator of (Super Admin: all). A drive
 * pending at CC gets Approve / Reject.
 */
class ExaminationDriveCcDataTable extends ExaminationDriveFacultyDataTable
{
    public function query(ExaminationDrive $model): Builder
    {
        $query = parent::query($model);
        $user = auth()->user();

        if (! $user || ! $user->hasRole('Super Admin')) {
            $courses = $user ? app(CourseCoordinatorService::class)->coursePksFor($user) : [];
            $query->whereIn('course_master_pk', $courses ?: [0]);
        }

        return $query;
    }

    public function dataTable(Builder $query): EloquentDataTable
    {
        // The Approve dialog shows the drive's dates and pre-fills the proposed ones.
        return parent::dataTable($query)->setRowAttr([
            'data-coe-id' => fn ($row) => $row->id,
            'data-cc-start' => fn ($row) => optional($row->start_date)->format('Y-m-d'),
            'data-cc-end' => fn ($row) => optional($row->end_date)->format('Y-m-d'),
            'data-cc-proposed-start' => fn ($row) => optional($row->proposed_start_date ?? $row->start_date)->format('Y-m-d'),
            'data-cc-proposed-end' => fn ($row) => optional($row->proposed_end_date ?? $row->end_date)->format('Y-m-d'),
        ]);
    }

    public function html()
    {
        return $this->builder()->setTableId('ccdcTable')->columns($this->getColumns());
    }

    protected function filename(): string
    {
        return 'ExaminationDriveCc_' . date('YmdHis');
    }

    protected function actionsCell(ExaminationDrive $row): string
    {
        if ($row->cc_status !== ExaminationDrive::CC_PENDING) {
            return '<span class="coe-muted">-</span>';
        }

        return '<div class="coe-act-group" role="group" aria-label="Row actions">'
            . '<button type="button" class="coe-act coe-act--approve">'
            . '<span class="coe-act__icon"><i class="bi bi-check-lg" aria-hidden="true"></i></span>'
            . '<span class="coe-act__label">Approve</span></button>'
            . '<button type="button" class="coe-act coe-act--reject">'
            . '<span class="coe-act__icon"><i class="bi bi-x-lg" aria-hidden="true"></i></span>'
            . '<span class="coe-act__label">Reject</span></button>'
            . '</div>';
    }
}
