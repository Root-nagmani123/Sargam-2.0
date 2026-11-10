<?php

namespace App\DataTables;

use App\Models\ExaminationDrive;
use Illuminate\Database\Eloquent\Builder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Column;

/**
 * COE → Examination Management → Examination Drive (Faculty) grid.
 *
 * Same underlying `examination_drives` query as ExaminationDriveDataTable
 * (reused via inheritance, not duplicated); only the rendered action set and
 * table id differ — faculty gets "Setup", never Edit/Delete.
 */
class ExaminationDriveFacultyDataTable extends ExaminationDriveDataTable
{
    /**
     * Faculty never see a Draft drive: Active tab = Active, Archived tab = Closed.
     * The parent query already splits the tabs on Closed / not Closed.
     */
    /** Status filter options for the faculty list (no Draft: faculty never see one). Applied by the parent query. */
    public const STATUS_FILTERS = [
        'faculty' => 'Pending at Faculty',
        'cc' => 'Pending at CC',
        'approved' => 'Approved by CC',
        'rejected' => 'Rejected by CC',
    ];

    /**
     * A faculty user sees only the drives they are mapped on (Subject -> Faculty);
     * Super Admin and other roles see every drive. The CC and COE grids, which
     * extend this one, turn it off: a CC sees their courses' drives either way.
     */
    protected bool $onlyOwnDrives = true;

    public function query(ExaminationDrive $model): Builder
    {
        $query = parent::query($model)->where('status', '!=', ExaminationDrive::STATUS_DRAFT);

        $user = auth()->user();
        if ($this->onlyOwnDrives && $user && $user->hasRole('Faculty') && ! $user->hasRole('Super Admin')) {
            $facultyPks = \App\Models\FacultyMaster::where('employee_master_pk', $user->user_id)->pluck('pk')->all();
            $query->whereHas('facultyMaps', fn ($q) => $q->whereIn('faculty_master_pk', $facultyPks ?: [0]));
        }

        return $query;
    }

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
            ->addColumn('sub_coords', fn ($row) => $this->plainCountCell($this->subCoordCount($row)))
            ->addColumn('components', fn ($row) => $this->plainCountCell($row->component_maps_count))
            ->editColumn('start_date', fn ($row) => optional($row->start_date)->format('d-m-Y'))
            ->editColumn('end_date', fn ($row) => optional($row->end_date)->format('d-m-Y'))
            ->addColumn('status', fn ($row) => $this->statusCell($row))
            ->addColumn('actions', fn ($row) => $this->actionsCell($row))
            ->setRowAttr(['data-coe-id' => fn ($row) => $row->id])
            ->filterColumn('course', fn ($q, $kw) => $q->whereHas('course', fn ($qq) => $qq->where('course_name', 'REGEXP', $kw)))
            ->filterColumn('term', fn ($q, $kw) => $q->whereHas('term', fn ($qq) => $qq->where('term_name', 'REGEXP', $kw)))
            ->filterColumn('exam_type', fn ($q, $kw) => $q->whereHas('examinationType', fn ($qq) => $qq->where('exam_type_name', 'REGEXP', $kw)))
            ->rawColumns(['exam_type', 'sub_coords', 'components', 'status', 'actions']);
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
        return $this->builder()->setTableId('fedcTable')->columns($this->getColumns());
    }

    protected function filename(): string
    {
        return 'ExaminationDriveFaculty_' . date('YmdHis');
    }

    /** "Complete Setup", or "Setup Completed" (still opens the page to edit) once done. */
    protected function actionsCell(ExaminationDrive $row): string
    {
        $url = route('admin.coe.faculty-examination-drive.setup', $row->id);

        // Approved by the CC: faculty can only look at the setup now.
        if ($row->cc_status === ExaminationDrive::CC_APPROVED) {
            return '<a href="' . $url . '" class="edc-setup-link is-done" title="Approved by the Course Coordinator - view only">'
                . '<i class="bi bi-eye" aria-hidden="true"></i><span>View<br>Setup</span></a>';
        }

        if ($this->setupCompleted($row)) {
            return '<a href="' . $url . '" class="edc-setup-link is-done" title="Setup completed - open to view or edit">'
                . '<i class="bi bi-check2-circle" aria-hidden="true"></i><span>Setup<br>Completed</span></a>';
        }

        return '<a href="' . $url . '" class="edc-setup-link" title="Complete Setup">'
            . '<i class="bi bi-pencil-square" aria-hidden="true"></i><span>Complete<br>Setup</span></a>';
    }

    /**
     * Setup is complete when every subject the viewer sets up on the drive has
     * component rows saved: a mapped faculty's own subjects, or (an admin) all of
     * the drive's subjects. Uses the facultyMaps / componentMaps the query loads.
     */
    protected function setupCompleted(ExaminationDrive $row): bool
    {
        $user = auth()->user();
        $facultyPks = $this->viewerFacultyPks ??= $user
            ? \App\Models\FacultyMaster::where('employee_master_pk', $user->user_id)->pluck('pk')->all()
            : [];

        $own = $row->facultyMaps->whereIn('faculty_master_pk', $facultyPks);
        $subjects = ($own->isNotEmpty() ? $own : $row->facultyMaps)->pluck('subject_master_pk')->unique();
        $saved = $row->componentMaps->pluck('subject_master_pk')->unique();

        return $subjects->isNotEmpty() && $subjects->diff($saved)->isEmpty();
    }

    /** @var int[]|null faculty_master.pk of the logged-in user, looked up once per page */
    private ?array $viewerFacultyPks = null;

    protected function plainCountCell(int $count): string
    {
        return $count > 0 ? (string) $count : '—';
    }
}
