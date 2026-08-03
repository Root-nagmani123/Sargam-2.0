<?php

namespace App\DataTables;

use App\Models\MemoDiscipline;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

/**
 * Send Discipline Memo listing (/memo/discipline). Single Eloquent model with
 * relation-based filters, mirroring the pre-DataTable MemoDisciplineController::index().
 */
class MemoDisciplineDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        $canManage = $this->canManageMemoNotice();
        $hideAction = hasRole('Officer Trainee');

        $dt = (new EloquentDataTable($query))
            ->addIndexColumn()
            ->addColumn('course_name', fn ($row) => $row->course->course_name ?? 'N/A')
            ->addColumn('student_name', fn ($row) => $row->student->display_name ?? 'N/A')
            ->addColumn('ot_code', fn ($row) => $row->student->generated_OT_code ?? 'N/A')
            ->addColumn('cadre_name', fn ($row) => $row->student->cadre->cadre_name ?? 'N/A')
            ->addColumn('infraction_date', function ($row) {
                return $row->date ? Carbon::parse($row->date)->format('d M Y') : 'N/A';
            })
            ->addColumn('discipline_name', function ($row) {
                return '<span class="badge bg-info-subtle text-info">' . e($row->discipline->discipline_name ?? 'N/A') . '</span>';
            })
            ->editColumn('remarks', fn ($row) => $row->remarks ?? '—')
            ->addColumn('created_date_fmt', function ($row) {
                return !empty($row->created_date) ? Carbon::parse($row->created_date)->format('d M Y') : 'N/A';
            })
            ->addColumn('status', function ($row) use ($canManage) {
                return $this->renderStatusColumn($row, $canManage);
            });

        $rawColumns = ['discipline_name', 'status'];

        if (!$hideAction) {
            $dt->addColumn('action', function ($row) use ($canManage) {
                return $this->renderActionsColumn($row, $canManage);
            });
            $rawColumns[] = 'action';
        }

        return $dt->rawColumns($rawColumns);
    }

    private function canManageMemoNotice(): bool
    {
        return hasRole('Internal Faculty') || hasRole('Guest Faculty')
            || hasRole('Super Admin') || hasRole('Training Induction Admin') || hasRole('Training-Induction');
    }

    /**
     * Mirrors resources/views/admin/memo_discipline/index.blade.php's previous Status cell.
     */
    private function renderStatusColumn(object $row, bool $canManage): string
    {
        $viewUrl = route('memo.discipline.memo.show', encrypt($row->pk));
        $chatType = $canManage ? 'admin' : 'OT';

        if ((int) $row->status === 1) {
            return '<span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle me-1"></i> Recorded</span>'
                . '<div class="mt-1 d-flex gap-2">'
                . '<a href="' . e($viewUrl) . '" class="link-primary small fw-medium">View Memo</a>'
                . '</div>';
        }

        $chatLink = '<a class="text-success view-conversation" data-bs-toggle="offcanvas" data-bs-target="#chatOffcanvas"'
            . ' data-id="' . $row->pk . '" data-type="' . $chatType . '">'
            . '<i class="material-icons material-symbols-rounded fs-5">chat</i></a>';

        if ((int) $row->status === 2) {
            return '<span class="badge bg-warning-subtle text-warning"><i class="bi bi-envelope me-1"></i> Memo Sent</span>'
                . '<div class="mt-1 d-flex gap-2">'
                . '<a href="' . e($viewUrl) . '" class="link-primary small fw-medium">View Memo</a>'
                . $chatLink
                . '</div>';
        }

        return '<span class="badge bg-secondary-subtle text-secondary"><i class="bi bi-lock me-1"></i> Closed</span>'
            . '<div class="mt-1 d-flex gap-2">'
            . '<a href="' . e($viewUrl) . '" class="link-primary small fw-medium">View Memo</a>'
            . $chatLink
            . '</div>';
    }

    /**
     * Mirrors resources/views/admin/memo_discipline/index.blade.php's previous Action cell.
     */
    private function renderActionsColumn(object $row, bool $canManage): string
    {
        if (!$canManage) {
            return '<span class="text-muted small">—</span>';
        }

        $html = '';
        $status = (int) $row->status;

        if ($status === 1) {
            $html .= '<button class="btn btn-sm btn-outline-secondary btn-edit-memo me-1" data-id="' . $row->pk . '" title="Edit">'
                . '<i class="bi bi-pencil"></i></button>';
            $html .= '<button class="btn btn-sm btn-outline-primary border-0 bg-transparent text-primary" data-discipline="' . $row->pk . '" id="sendMemoBtn">'
                . '<i class="material-icons material-symbols-rounded fs-5">send</i></button>';
        } elseif ($status === 2) {
            $viewUrl = route('memo.discipline.memo.show', encrypt($row->pk));
            $html .= '<a href="' . e($viewUrl) . '" class="btn btn-sm btn-outline-danger border-0 bg-transparent text-primary">'
                . '<i class="material-icons material-symbols-rounded fs-5">close</i> Close</a>';
        } else {
            $html .= '<span class="text-muted small">—</span>';
        }

        // Delete: admins/faculty only, hard-deletes the discipline memo + its chat.
        // Active only while the memo is open (Recorded/Memo Sent); disabled once closed.
        $isMemoClosed = !in_array($status, [1, 2], true);
        $disabledAttrs = $isMemoClosed ? ' aria-disabled="true" tabindex="-1" style="pointer-events:none;opacity:.45;"' : '';
        $title = $isMemoClosed ? 'Cannot delete a closed memo' : 'Delete';
        $html .= '<a href="javascript:void(0)"'
            . ' class="btn btn-sm btn-outline-danger discipline-delete-record ms-1 border-0 bg-transparent text-primary' . ($isMemoClosed ? ' disabled' : '') . '"'
            . ' data-id="' . $row->pk . '" title="' . e($title) . '"' . $disabledAttrs . '>'
            . '<i class="material-icons material-symbols-rounded fs-5">delete</i></a>';

        return $html;
    }

    public function query(MemoDiscipline $model): QueryBuilder
    {
        $request = $this->request();

        $programNameFilter = $request->input('program_name');
        $statusFilter = $request->input('status');
        $disciplineFilter = $request->input('discipline_master_pk');
        $searchFilter = $request->input('search_filter');
        $fromDateFilter = $request->input('from_date') ?: null;
        $toDateFilter = $request->input('to_date') ?: null;

        $data_course_id = get_Role_by_course();

        return $model->with([
                'course:pk,course_name',
                'discipline:pk,discipline_name,active_inactive',
                'student:pk,display_name,generated_OT_code,cadre_master_pk',
                'student.cadre:pk,cadre_name',
            ])
            ->when(hasRole('Student-OT'), function ($q) {
                $studentPk = Auth::user()->user_id;
                $courseIds = DB::table('student_master_course__map')
                    ->where('student_master_pk', $studentPk)
                    ->pluck('course_master_pk');
                $q->where('student_master_pk', $studentPk);
                $q->whereIn('course_master_pk', $courseIds);
            })
            ->when(!hasRole('Student-OT') && !empty($data_course_id), function ($q) use ($data_course_id) {
                $q->whereIn('course_master_pk', $data_course_id);
            })
            ->when($programNameFilter, function ($q) use ($programNameFilter) {
                $q->where('course_master_pk', $programNameFilter);
            })
            ->when($statusFilter !== null && $statusFilter !== '', function ($q) use ($statusFilter) {
                $q->where('status', $statusFilter);
            })
            ->when($disciplineFilter, function ($q) use ($disciplineFilter) {
                $q->whereHas('discipline', fn ($d) => $d->where('discipline_name', $disciplineFilter));
            })
            ->when($searchFilter, function ($q) use ($searchFilter) {
                $q->where(function ($sub) use ($searchFilter) {
                    $sub->whereHas('student', function ($s) use ($searchFilter) {
                            $s->where('display_name', 'like', "%{$searchFilter}%")
                              ->orWhere('generated_OT_code', 'like', "%{$searchFilter}%")
                              ->orWhereHas('cadre', function ($c) use ($searchFilter) {
                                  $c->where('cadre_name', 'like', "%{$searchFilter}%");
                              });
                        })
                        ->orWhereHas('course', function ($c) use ($searchFilter) {
                            $c->where('course_name', 'like', "%{$searchFilter}%");
                        })
                        ->orWhereHas('discipline', function ($d) use ($searchFilter) {
                            $d->where('discipline_name', 'like', "%{$searchFilter}%");
                        })
                        ->orWhere('remarks', 'like', "%{$searchFilter}%")
                        ->orWhere('mark_deduction_submit', 'like', "%{$searchFilter}%")
                        ->orWhere('final_mark_deduction', 'like', "%{$searchFilter}%")
                        ->orWhere('date', 'like', "%{$searchFilter}%");
                });
            })
            ->when($fromDateFilter && $toDateFilter, function ($q) use ($fromDateFilter, $toDateFilter) {
                $q->whereBetween('date', [$fromDateFilter, $toDateFilter]);
            })
            ->whereHas('discipline', function ($q) {
                $q->where('active_inactive', 1);
            })
            ->orderBy('pk', 'desc');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('discTable')
            ->addTableClass('table align-middle mb-0 text-nowrap')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->parameters([
                'searching' => false,
                'ordering' => false,
                'language' => [
                    'zeroRecords' => $this->emptyStateHtml(),
                    'emptyTable' => $this->emptyStateHtml(),
                ],
            ]);
    }

    private function emptyStateHtml(): string
    {
        return '<div class="text-center py-5 text-muted"><i class="bi bi-inbox fs-1 d-block mb-2"></i>'
            . '<span class="fw-medium">No memo records available</span></div>';
    }

    public function getColumns(): array
    {
        $columns = [
            Column::computed('DT_RowIndex')->title('S. No.')->orderable(false)->searchable(false)->addClass('fw-semibold text-muted'),
            Column::computed('course_name')->title('Program Name')->orderable(false)->searchable(false)->addClass('fw-semibold'),
            Column::computed('student_name')->title('Name')->orderable(false)->searchable(false)->addClass('fw-semibold'),
            Column::computed('ot_code')->title('OT/Participant Code')->orderable(false)->searchable(false)->addClass('text-muted'),
            Column::computed('cadre_name')->title('Cadre')->orderable(false)->searchable(false)->addClass('text-muted'),
            Column::computed('infraction_date')->title('Date of Infraction')->orderable(false)->searchable(false)->addClass('text-muted'),
            Column::computed('discipline_name')->title('Infraction')->orderable(false)->searchable(false),
            Column::make('mark_deduction_submit')->title('Submitted')->orderable(false)->searchable(false)->addClass('text-center fw-semibold text-warning'),
            Column::make('final_mark_deduction')->title('Final')->orderable(false)->searchable(false)->addClass('text-center fw-semibold text-danger'),
            Column::make('remarks')->title('Remarks')->orderable(false)->searchable(false)->addClass('text-muted'),
            Column::computed('created_date_fmt')->title('Created Date')->orderable(false)->searchable(false)->addClass('text-muted'),
            Column::computed('status')->title('Status')->orderable(false)->searchable(false),
        ];

        if (!hasRole('Officer Trainee')) {
            $columns[] = Column::computed('action')->title('Action')->orderable(false)->searchable(false)->addClass('text-end');
        }

        return $columns;
    }

    protected function filename(): string
    {
        return 'MemoDiscipline_' . date('YmdHis');
    }
}
