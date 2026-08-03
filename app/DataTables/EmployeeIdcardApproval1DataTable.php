<?php

namespace App\DataTables;

use App\Support\DataTableRedisCache;
use App\Support\IdCardSecurityMapper;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\CollectionDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

/**
 * Approval I list (Contractual regular + Contractual Duplicate ID Card requests) for the
 * current Approval Authority. Merged from two raw-query sources, so it is served via
 * Yajra's Collection engine rather than a single Eloquent query.
 */
class EmployeeIdcardApproval1DataTable extends DataTable
{
    public const LISTING_CACHE_EPOCH_KEY = 'employee_idcard_approval1_list_epoch';

    public static function bumpListingCacheEpoch(): void
    {
        DataTableRedisCache::bumpListEpoch(self::LISTING_CACHE_EPOCH_KEY, 'EmployeeIdcardApproval1DataTable');
    }

    /**
     * Server-side JSON for the Approval I list. .env: EMPLOYEE_IDCARD_APPROVAL1_CACHE_*.
     */
    public function ajax(): JsonResponse
    {
        $request = $this->request();
        $user = Auth::user();
        $currentEmployeePk = $user->user_id ?? $user->pk ?? null;

        return DataTableRedisCache::serveCachedAjax(
            $request,
            'employee_idcard_approval1_dt:v1:',
            self::LISTING_CACHE_EPOCH_KEY,
            [
                'enabled' => 'EMPLOYEE_IDCARD_APPROVAL1_CACHE_ENABLED',
                'seconds' => 'EMPLOYEE_IDCARD_APPROVAL1_CACHE_SECONDS',
            ],
            'EmployeeIdcardApproval1DataTable',
            fn () => parent::ajax(),
            [
                'emp_pk' => $currentEmployeePk,
                'search_filter' => (string) $request->input('search_filter', ''),
                'date_from_filter' => (string) $request->input('date_from_filter', ''),
                'date_to_filter' => (string) $request->input('date_to_filter', ''),
                'card_type' => (string) $request->input('card_type', ''),
            ]
        );
    }

    public function query(Request $request): Collection
    {
        $user = Auth::user();
        $currentEmployeePk = $user->user_id ?? $user->pk ?? null;

        return $this->buildMergedCollection($request, $currentEmployeePk);
    }

    /**
     * Same merge/filter logic as the pre-DataTable controller implementation
     * (contractual regular + contractual duplicate requests for the current approval authority).
     */
    private function buildMergedCollection(Request $request, $currentEmployeePk): Collection
    {
        $search = $request->input('search_filter');
        $dateFrom = $request->input('date_from_filter');
        $dateTo = $request->input('date_to_filter');

        // Contractual regular ID Card requests - Approval 1
        $contA1Done = DB::table('security_con_oth_id_apply_approval')
            ->where('status', 1)
            ->pluck('security_parm_id_apply_pk');
        $contQuery = DB::table('security_con_oth_id_apply')
            // Show history too: Pending/Approved/Rejected
            ->whereIn('id_status', [1, 2, 3])
            // Approval-I scope: requests assigned to current authority
            ->where('department_approval_emp_pk', $currentEmployeePk);
        // (No whereNotIn here: we want already-approved rows to still appear as view-only.)
        $contQuery->orderByDesc('created_date');

        if (filled($search)) {
            $searchLike = '%' . trim($search) . '%';
            $contQuery->where(function ($q) use ($searchLike) {
                $q->where('employee_name', 'like', $searchLike)
                    ->orWhere('id_card_no', 'like', $searchLike);
            });
        }

        // Date filters (by created_date)
        if (filled($dateFrom)) {
            $from = Carbon::parse($dateFrom)->startOfDay()->toDateTimeString();
            $contQuery->where('created_date', '>=', $from);
        }
        if (filled($dateTo)) {
            $to = Carbon::parse($dateTo)->endOfDay()->toDateTimeString();
            $contQuery->where('created_date', '<=', $to);
        }
        if ($request->filled('card_type')) {
            $contQuery->where('permanent_type', $request->input('card_type'));
        }

        $contRows = $contQuery->get();
        $contA1DoneArr = $contA1Done->toArray();
        $contDtos = $contRows->map(function ($r) use ($contA1DoneArr) {
            $dto = IdCardSecurityMapper::toContractualRequestDto($r);

            // Normalize status fields so the shared approval table can hide action buttons
            // once a request is Approved / Rejected.
            $dto->id_status = (int) ($dto->id_status ?? $r->id_status ?? 0);
            $dto->status = match ((int) ($dto->id_status ?? 0)) {
                1 => 'Pending',
                2 => 'Approved',
                3 => 'Rejected',
                default => 'Unknown',
            };

            // Section head (Approval I) for contractual regular: approve1() sets
            // depart_approval_status = 2 and inserts security row with approval status 0 (not 1).
            // So we must key "A1 done" off the main table, not approval.status = 1.
            $sectionHeadDone = (int) ($r->depart_approval_status ?? 0) === 2;
            $legacyA1InApprovalTable = in_array($r->emp_id_apply ?? null, $contA1DoneArr, false);

            if ((int) ($dto->id_status ?? 0) === 1 && ($sectionHeadDone || $legacyA1InApprovalTable)) {
                $dto->is_view_only = true;
            }

            return $dto;
        });

        // Contractual Duplicate ID Card requests only (not Permanent/Family) - same approving authority
        $dupContA1Done = DB::table('security_dup_other_id_apply_approval')
            ->where('status', 1)
            ->pluck('security_con_id_apply_pk');
        $dupContQuery = DB::table('security_dup_other_id_apply')
            // Show history too: Pending/Approved/Rejected
            ->whereIn('id_status', [1, 2, 3])
            ->where('card_type', 'Contractual')
            // Approval-I scope: requests assigned to current authority
            ->where('department_approval_emp_pk', $currentEmployeePk);

        // (No whereNotIn here: we want already-approved rows to still appear as view-only.)
        $dupContQuery->orderByDesc('created_date');

        if (filled($search)) {
            $searchLike = '%' . trim($search) . '%';
            $dupContQuery->where(function ($q) use ($searchLike) {
                $q->where('employee_name', 'like', $searchLike)
                    ->orWhere('id_card_no', 'like', $searchLike);
            });
        }
        if (filled($dateFrom)) {
            $from = Carbon::parse($dateFrom)->startOfDay()->toDateTimeString();
            $dupContQuery->where('created_date', '>=', $from);
        }
        if (filled($dateTo)) {
            $to = Carbon::parse($dateTo)->endOfDay()->toDateTimeString();
            $dupContQuery->where('created_date', '<=', $to);
        }

        $dupContRows = $dupContQuery->get();
        $deptMap = DB::table('department_master')->pluck('department_name', 'pk')->toArray();
        $dupContA1DoneArr = $dupContA1Done->toArray();
        $dupContDtos = $dupContRows->map(function ($r) use ($deptMap, $dupContA1DoneArr) {
            $requestedSection = null;
            if (!empty($r->section) && isset($deptMap[$r->section])) {
                $requestedSection = $deptMap[$r->section];
            }
            $dto = new \stdClass();
            $dto->id = 'c-' . $r->emp_id_apply;
            $dto->pk = 0;
            $dto->emp_id_apply = $r->emp_id_apply ?? '';
            $dto->name = $r->employee_name ?? '--';
            $dto->designation = $r->designation_name ?? '--';
            $dto->photo = $r->id_photo_path ?? null;
            $dto->joining_letter = null;
            $dto->created_at = isset($r->created_date) ? Carbon::parse($r->created_date) : null;
            $dto->card_type = $r->card_type ?? 'Contractual';
            $dto->request_for = 'Duplication';
            $dto->duplication_reason = $r->card_reason ?? null;
            $dto->id_card_valid_upto = isset($r->card_valid_to) ? Carbon::parse($r->card_valid_to)->format('d/m/Y') : null;
            $dto->id_card_valid_from = isset($r->card_valid_from) ? Carbon::parse($r->card_valid_from)->format('d/m/Y') : null;
            $dto->id_card_number = $r->id_card_no ?? null;
            $dto->date_of_birth = $r->employee_dob ?? null;
            $dto->mobile_number = $r->mobile_no ?? null;
            $dto->telephone_number = null;
            $dto->blood_group = $r->blood_group ?? null;
            $dto->remarks = $r->remarks ?? null;
            $dto->created_by = $r->created_by ?? null;
            $dto->id_status = (int) ($r->id_status ?? 0);
            $dto->status = match ((int) ($r->id_status ?? 0)) {
                1 => 'Pending',
                2 => 'Approved',
                3 => 'Rejected',
                default => 'Unknown',
            };
            $dto->request_type = 'duplicate';
            $dto->father_name = null;
            $dto->requested_section = $requestedSection;
            if ((int) ($r->id_status ?? 0) === 1 && in_array(($r->emp_id_apply ?? ''), $dupContA1DoneArr, true)) {
                $dto->is_view_only = true;
            }
            return $dto;
        });

        return $contDtos->concat($dupContDtos)->sortByDesc('created_at')->values();
    }

    public function dataTable(Collection $query): CollectionDataTable
    {
        return (new CollectionDataTable($query))
            ->addIndexColumn()
            ->editColumn('photo', function ($row) {
                return $this->renderPhotoColumn($row);
            })
            ->addColumn('request_type_badge', function ($row) {
                return $this->renderRequestTypeBadge($row);
            })
            ->addColumn('contact_no', function ($row) {
                return $row->mobile_number ?? $row->telephone_number ?? '--';
            })
            ->addColumn('request_date_fmt', function ($row) {
                return $row->created_at ? $row->created_at->format('d-m-Y') : '--';
            })
            ->addColumn('actions', function ($row) {
                return $this->renderActionsColumn($row);
            })
            ->rawColumns(['photo', 'request_type_badge', 'actions']);
    }

    private function renderPhotoColumn(object $row): string
    {
        $dummyUrl = asset('images/dummypic.jpeg');
        $photoPath = null;
        if (!empty($row->photo)) {
            $photoPath = str_starts_with($row->photo, 'idcard/') ? $row->photo : 'idcard/photos/' . $row->photo;
        }

        if (!$photoPath) {
            return '<img src="' . e($dummyUrl) . '" alt="No Photo" '
                . 'style="width:50px; height:50px; object-fit:cover; border-radius:4px; border:1px solid #dee2e6;" '
                . 'title="No photo available">';
        }

        $photoUrl = asset('storage/' . $photoPath);

        return '<a href="' . e($photoUrl) . '" target="_blank" class="d-inline-block">'
            . '<img src="' . e($photoUrl) . '" alt="Photo" onerror="this.onerror=null;this.src=\'' . e($dummyUrl) . '\';" '
            . 'style="width:50px; height:50px; object-fit:cover; border-radius:4px; border:1px solid #dee2e6; cursor:pointer;" '
            . 'title="Click to view full photo"></a>';
    }

    private function renderRequestTypeBadge(object $row): string
    {
        $empTypeShort = match ($row->employee_type ?? null) {
            'Permanent Employee' => 'Permanent',
            'Contractual Employee' => 'Contractual',
            default => null,
        };

        if (isset($row->request_type) && $row->request_type === 'duplicate') {
            return $empTypeShort
                ? '<span class="badge bg-info">Duplicate (' . e($empTypeShort) . ')</span>'
                : '<span class="badge bg-info">Duplicate</span>';
        }

        return $empTypeShort
            ? '<span class="badge bg-secondary">Fresh (' . e($empTypeShort) . ')</span>'
            : '<span class="badge bg-secondary">Fresh</span>';
    }

    /**
     * Mirrors resources/views/admin/security/employee_idcard_approval/_approval_table.blade.php
     * for approvalStage = 1 (Approval I never reaches the stage-2-only branches).
     */
    private function renderActionsColumn(object $row): string
    {
        $encryptKey = $row->id;
        if (isset($row->request_type) && $row->request_type === 'duplicate') {
            if (is_string($encryptKey) && str_starts_with($encryptKey, 'c-')) {
                $encryptKey = 'c-dup-' . substr($encryptKey, 2);
            } else {
                $encryptKey = 'p-dup-' . $encryptKey;
            }
        }
        $encryptedId = encrypt($encryptKey);

        $viewUrl = route('admin.security.employee_idcard_approval.show', ['id' => $encryptedId, 'stage' => 1]);

        $html = '<div class="d-flex flex-column gap-1 align-items-center">';
        $html .= '<a href="' . e($viewUrl) . '" class="btn btn-link p-0 text-primary text-decoration-none" '
            . 'title="View full request details">View Request</a>';

        $status = (string) ($row->status ?? 'Pending');
        if ($status !== 'Pending') {
            $badgeClass = match ($status) {
                'Approved' => 'bg-success',
                'Rejected' => 'bg-danger',
                default => 'bg-secondary',
            };
            $titleAttr = $status === 'Approved' ? ' title="Please collect your ID card from security section"' : '';
            $html .= '<span class="badge ' . $badgeClass . '"' . $titleAttr . '>' . e($status) . '</span>';
            $html .= '<small class="text-muted">No further actions available</small>';
        } elseif (!empty($row->is_view_only)) {
            $html .= '<span class="badge bg-info">View Only</span>';
            $html .= '<small class="text-muted">Approved at Level 1</small>';
        } else {
            $approveRoute = route('admin.security.employee_idcard_approval.approve1', $encryptedId);
            $rejectRoute = route('admin.security.employee_idcard_approval.reject1', $encryptedId);
            $token = csrf_token();

            $html .= '<div class="d-flex gap-1 flex-wrap justify-content-center align-items-center">';
            $html .= '<form action="' . e($approveRoute) . '" method="POST" class="d-inline">'
                . '<input type="hidden" name="_token" value="' . e($token) . '">'
                . '<button type="submit" class="btn btn-link p-0 text-success text-decoration-none" title="Approve">Approve</button>'
                . '</form>';
            $html .= '<span class="text-muted">|</span>';
            $html .= '<button type="button" class="btn btn-link p-0 text-danger text-decoration-none reject-btn" title="Reject" '
                . 'data-name="' . e($row->name ?? '') . '" data-url="' . e($rejectRoute) . '">Reject</button>';
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('approval1Table')
            ->addTableClass('table text-nowrap align-middle mb-0')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->parameters([
                'responsive' => true,
                'autoWidth' => false,
                'ordering' => false,
                'searching' => false,
                'pageLength' => 10,
                'lengthMenu' => [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
                'language' => [
                    'lengthMenu' => 'Show _MENU_ entries',
                    'info' => 'Showing _START_ to _END_ of _TOTAL_ entries',
                    'infoEmpty' => 'No requests found',
                    'infoFiltered' => '',
                    'zeroRecords' => 'No requests found for Approval I.',
                    'paginate' => [
                        'first' => 'First',
                        'last' => 'Last',
                        'next' => 'Next',
                        'previous' => 'Previous',
                    ],
                ],
                'dom' => '<"row align-items-center mb-2"<"col-12 col-md-4"l>>rt<"row align-items-center mt-2"<"col-12 col-md-5"i><"col-12 col-md-7"p>>',
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S.No.')->addClass('text-center')->orderable(false)->searchable(false)->width('50px'),
            Column::make('name')->title('EMPLOYEE NAME')->orderable(false)->searchable(false),
            Column::make('designation')->title('DESIGNATION')->orderable(false)->searchable(false),
            Column::make('id_card_number')->title('ID CARD NO')->orderable(false)->searchable(false),
            Column::make('card_type')->title('ID TYPE')->orderable(false)->searchable(false),
            Column::computed('request_type_badge')->title('REQUEST TYPE')->addClass('text-center')->orderable(false)->searchable(false),
            Column::make('photo')->title('PHOTO')->addClass('text-center')->orderable(false)->searchable(false)->width('70px'),
            Column::computed('contact_no')->title('CONTACT NO')->orderable(false)->searchable(false),
            Column::computed('actions')->title('APPROVED/REJECT')->addClass('text-center')->orderable(false)->searchable(false),
            Column::computed('request_date_fmt')->title('REQUEST DATE')->orderable(false)->searchable(false),
            Column::make('requested_section')->title('REQUESTED SECTION')->orderable(false)->searchable(false),
        ];
    }

    protected function filename(): string
    {
        return 'ApprovalI_' . date('YmdHis');
    }
}
