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
    /** Not limited to the viewer's own drives (see ExaminationDriveFacultyDataTable). */
    protected bool $onlyOwnDrives = false;

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
            // Date + time for the dialog's pickers; without a proposal, the drive's dates as whole days.
            'data-cc-proposed-start' => fn ($row) => $row->proposed_start_date
                ? $row->proposed_start_date->format('Y-m-d\TH:i')
                : (optional($row->start_date)->format('Y-m-d') ? $row->start_date->format('Y-m-d') . 'T00:00' : ''),
            'data-cc-proposed-end' => fn ($row) => $row->proposed_end_date
                ? $row->proposed_end_date->format('Y-m-d\TH:i')
                : (optional($row->end_date)->format('Y-m-d') ? $row->end_date->format('Y-m-d') . 'T23:59' : ''),
            // Course dates bound the dates the CC can confirm (ExaminationDriveController::ccApprove);
            // a re-exam's own dates instead (it may run after the course ends).
            'data-cc-course-start' => fn ($row) => $this->isReexamRow($row)
                ? optional($row->start_date)->format('Y-m-d')
                : ($row->course?->start_year ? \Carbon\Carbon::parse($row->course->start_year)->format('Y-m-d') : ''),
            'data-cc-course-end' => fn ($row) => $this->isReexamRow($row)
                ? optional($row->end_date)->format('Y-m-d')
                : ($row->course?->end_date ? \Carbon\Carbon::parse($row->course->end_date)->format('Y-m-d') : ''),
            'data-cc-range-what' => fn ($row) => $this->isReexamRow($row) ? 're-exam dates' : 'course dates',
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
