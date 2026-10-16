<?php

namespace App\DataTables;

use App\Models\ExaminationDrive;
use Illuminate\Database\Eloquent\Builder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

/**
 * COE → Examination Management → Examination Drive Configuration (admin grid).
 *
 * Backed by `examination_drives` + its master relations/maps (see
 * App\Models\ExaminationDrive). Everything the grid needs per row — type,
 * term, course names, a representative faculty, sub-coordinate and component
 * counts — is eager-loaded once per page in query(), never per row.
 */
class ExaminationDriveDataTable extends DataTable
{
    public function dataTable(Builder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->editColumn('name', fn ($row) => e($this->driveName($row)))
            ->editColumn('exam_type', fn ($row) => $row->examinationType ? e($row->examinationType->exam_type_name) : '<span class="coe-muted">-</span>')
            ->editColumn('term', fn ($row) => e($row->term->term_name ?? '-'))
            ->editColumn('course', fn ($row) => e($row->course->course_name ?? '-'))
            ->editColumn('academic_session', fn ($row) => e($row->academic_session))
            ->addColumn('faculty', fn ($row) => e(implode(', ', $row->driveFacultyNames()) ?: '-'))
            ->addColumn('sub_coords', fn ($row) => $this->countCell($this->subCoordCount($row), $row))
            ->addColumn('components', fn ($row) => $this->countCell($row->component_maps_count, $row, 'show'))
            ->editColumn('start_date', fn ($row) => optional($row->start_date)->format('d-m-Y'))
            ->editColumn('end_date', fn ($row) => optional($row->end_date)->format('d-m-Y'))
            ->addColumn('status', fn ($row) => $this->statusCell($row))
            ->addColumn('actions', fn ($row) => $this->actionsCell($row))
            ->setRowAttr([
                'data-coe-id' => fn ($row) => $row->id,
                'data-edc-status' => fn ($row) => $row->statusLabel(),
                'data-edc-course' => fn ($row) => $row->course->course_name ?? '',
                'data-edc-type' => fn ($row) => $row->examinationType->exam_type_name ?? '',
                'data-edc-term' => fn ($row) => $row->term->term_name ?? '',
                'data-edc-session' => fn ($row) => $row->academic_session,
                'data-edc-start' => fn ($row) => optional($row->start_date)->format('d-m-Y'),
                'data-edc-end' => fn ($row) => optional($row->end_date)->format('d-m-Y'),
            ])
            ->filterColumn('course', fn ($q, $kw) => $q->whereHas('course', fn ($qq) => $qq->where('course_name', 'REGEXP', $kw)))
            ->filterColumn('term', fn ($q, $kw) => $q->whereHas('term', fn ($qq) => $qq->where('term_name', 'REGEXP', $kw)))
            ->filterColumn('exam_type', fn ($q, $kw) => $q->whereHas('examinationType', fn ($qq) => $qq->where('exam_type_name', 'REGEXP', $kw)))
            ->rawColumns(['exam_type', 'sub_coords', 'components', 'status', 'actions']);
    }

    public function query(ExaminationDrive $model): Builder
    {
        $query = $model->newQuery()
            ->with(['examinationType', 'term', 'course'])
            ->withCount('componentMaps')
            // Drive faculty (creation mapping) and sub-coordinators (faculty setup),
            // loaded up front so the two columns do not query per row.
            ->with(['facultyMaps' => fn ($q) => $q->with('faculty')->orderBy('id')])
            ->with('componentMaps.faculty');

        $scope = request()->query('scope') === 'archived' ? 'archived' : 'active';
        if ($scope === 'archived') {
            $query->where('status', ExaminationDrive::STATUS_CLOSED);
        } else {
            $query->where('status', '!=', ExaminationDrive::STATUS_CLOSED);
        }

        if ($course = request()->query('course_filter')) {
            $query->whereHas('course', fn ($q) => $q->where('course_name', $course));
        }
        if ($term = request()->query('term_filter')) {
            $query->whereHas('term', fn ($q) => $q->where('term_name', $term));
        }
        if ($type = request()->query('type_filter')) {
            $query->whereHas('examinationType', fn ($q) => $q->where('exam_type_name', $type));
        }
        if ($phase = request()->query('phase_filter')) {
            $query->where('phase', $phase);
        }

        return $query->orderByDesc('id');
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S. No.')->addClass('coe-col-sno'),
            Column::make('name')->title('Drive Name'),
            Column::computed('exam_type')->title('Examination Type'),
            Column::computed('term')->title('Term'),
            Column::computed('course')->title('Course'),
            Column::make('academic_session')->title('Academic Session'),
            Column::computed('faculty')->title('Faculty'),
            Column::computed('sub_coords')->title('Sub-Coordinates')->addClass('coe-col-center'),
            Column::computed('components')->title('Components')->addClass('coe-col-center'),
            Column::make('start_date')->title('Start Date'),
            Column::make('end_date')->title('End Date'),
            Column::computed('status')->title('Status')->addClass('coe-col-center'),
            Column::computed('actions')->title('Action')->addClass('edc-cell-actions'),
        ];
    }

    public function html()
    {
        return $this->builder()->setTableId('edcTable')->columns($this->getColumns());
    }

    protected function filename(): string
    {
        return 'ExaminationDrive_' . date('YmdHis');
    }

    /**
     * Older rows (seeded before `drive_name` existed) fall back to a derived
     * label so the grid isn't blank.
     */
    protected function driveName(ExaminationDrive $row): string
    {
        if (filled($row->drive_name)) {
            return $row->drive_name;
        }

        $type = $row->examinationType->exam_type_name ?? 'Examination';

        return $type . ' Drive — ' . $row->academic_session;
    }

    /** First-mapped faculty stands in for "Faculty" (coordinator); the rest count as sub-coordinates. */
    /** Distinct sub-coordinators assigned on faculty Complete Setup, across all components. */
    protected function subCoordCount(ExaminationDrive $row): int
    {
        return count($row->subCoordinatorNames());
    }

    protected function countCell(int $count, ExaminationDrive $row, ?string $linkRoute = null): string
    {
        if ($count <= 0) {
            return '<span class="coe-muted">-</span>';
        }

        if ($linkRoute === 'show') {
            return '<a href="' . route('admin.coe.examination-drive.show', $row->id) . '" class="text-primary fw-semibold">' . $count . '</a>';
        }

        return '<a href="' . route('admin.coe.examination-drive.coordinates', $row->id) . '" class="text-primary fw-semibold">' . $count . '</a>';
    }

    protected function statusCell(ExaminationDrive $row): string
    {
        $label = $row->statusLabel();
        $class = match ($row->status) {
            ExaminationDrive::STATUS_ACTIVE => 'edc-status-active',
            ExaminationDrive::STATUS_CLOSED => 'edc-status-closed',
            default => 'edc-status-draft',
        };

        return '<span class="coe-state ' . $class . '">' . $label . '</span>';
    }

    protected function actionsCell(ExaminationDrive $row): string
    {
        $coordUrl = route('admin.coe.examination-drive.coordinates', $row->id);
        $canEdit = $row->status !== ExaminationDrive::STATUS_CLOSED;

        $html = '<div class="coe-act-group" role="group" aria-label="Row actions">'
            . '<a href="' . $coordUrl . '" class="coe-act"><span class="coe-act__icon"><i class="bi bi-eye" aria-hidden="true"></i></span><span class="coe-act__label">View</span></a>';

        if ($canEdit) {
            $html .= '<button type="button" class="coe-act coe-act--edit"><span class="coe-act__icon"><i class="bi bi-pencil" aria-hidden="true"></i></span><span class="coe-act__label">Edit</span></button>';
        }

        $html .= '<button type="button" class="coe-act coe-act--del"><span class="coe-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span><span class="coe-act__label">Delete</span></button>'
            . '</div>';

        return $html;
    }
}
