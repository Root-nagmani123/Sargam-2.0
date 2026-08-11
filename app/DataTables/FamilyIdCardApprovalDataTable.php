<?php

namespace App\DataTables;

use App\Models\SecurityFamilyIdApply;
use App\Support\DataTableRedisCache;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Yajra\DataTables\CollectionDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

/**
 * Requested Family ID (Family ID Card Approval) list — 4 tabs (new / for_approval / issued /
 * rejected) on one page. Reproduces the group/role/split logic previously computed eagerly in
 * FamilyIDCardApprovalController::index() for every tab on every request, but scoped to a single
 * `tab_key` per ajax call so each tab is a genuinely server-side-paginated DataTable. Served via
 * Yajra's Collection engine because the underlying data is grouped in PHP from raw rows (one row
 * per multi-member application), not a single query (mirrors EmployeeIdcardApproval1DataTable).
 */
class FamilyIdCardApprovalDataTable extends DataTable
{
    public const LISTING_CACHE_EPOCH_KEY = 'security_family_idcard_approval_list_epoch';

    public static function bumpListingCacheEpoch(): void
    {
        DataTableRedisCache::bumpListEpoch(self::LISTING_CACHE_EPOCH_KEY, 'FamilyIdCardApprovalDataTable');
    }

    /**
     * Server-side JSON for one tab of the Requested Family ID approval list.
     * .env: SECURITY_FAMILY_IDCARD_APPROVAL_CACHE_*.
     */
    public function ajax(): JsonResponse
    {
        $request = $this->request();
        $user = Auth::user();
        $currentUserPk = $user->user_id ?? $user->pk ?? null;

        $hasSecurityCard = hasRole('Security Card');
        $hasAdminSecurity = hasRole('Admin Security');

        return DataTableRedisCache::serveCachedAjax(
            $request,
            'security_family_idcard_approval_dt:v1:',
            self::LISTING_CACHE_EPOCH_KEY,
            [
                'enabled' => 'SECURITY_FAMILY_IDCARD_APPROVAL_CACHE_ENABLED',
                'seconds' => 'SECURITY_FAMILY_IDCARD_APPROVAL_CACHE_SECONDS',
            ],
            'FamilyIdCardApprovalDataTable',
            fn () => parent::ajax(),
            [
                'user_pk' => $currentUserPk,
                'has_security_card_role' => $hasSecurityCard,
                'has_admin_security_role' => $hasAdminSecurity,
                'tab_key' => (string) $request->input('tab_key', 'new'),
                'search' => (string) $request->input('search', ''),
                'date_from' => (string) $request->input('date_from', ''),
                'date_to' => (string) $request->input('date_to', ''),
            ]
        );
    }

    public function query(Request $request): Collection
    {
        return $this->buildTabCollection($request);
    }

    /**
     * Same group/filter/role logic as the pre-DataTable controller implementation
     * (FamilyIDCardApprovalController::index()), scoped down to the sub-list needed
     * for the requested tab_key.
     */
    private function buildTabCollection(Request $request): Collection
    {
        $search = trim((string) $request->input('search', ''));
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $tabKey = $request->input('tab_key', 'new');
        if (! in_array($tabKey, ['new', 'for_approval', 'issued', 'rejected'], true)) {
            $tabKey = 'new';
        }

        $hasSecurityCard = hasRole('Security Card');
        $hasAdminSecurity = hasRole('Admin Security');
        $isLevel1Only = $hasSecurityCard && ! $hasAdminSecurity;
        $isLevel2Only = $hasAdminSecurity && ! $hasSecurityCard;
        $hasBothApprovalRoles = $hasSecurityCard && $hasAdminSecurity;

        $baseQuery = SecurityFamilyIdApply::query()
            ->select([
                'fml_id_apply',
                'emp_id_apply',
                'created_by',
                'created_date',
                'id_status',
                'id_card_generate_date',
            ])
            ->orderBy('created_date', 'desc');

        if (! empty($dateFrom)) {
            try {
                $from = Carbon::parse($dateFrom)->startOfDay()->toDateTimeString();
                $baseQuery->where('created_date', '>=', $from);
            } catch (\Exception $e) {
            }
        }
        if (! empty($dateTo)) {
            try {
                $to = Carbon::parse($dateTo)->endOfDay()->toDateTimeString();
                $baseQuery->where('created_date', '<=', $to);
            } catch (\Exception $e) {
            }
        }

        $pendingRows = $baseQuery->get();

        $groupKey = function ($r) {
            $date = $r->created_date ? Carbon::parse($r->created_date)->format('Y-m-d H:i:s') : '';

            return $r->emp_id_apply . '|' . ($r->created_by ?? '') . '|' . $date;
        };
        $groups = $pendingRows->groupBy($groupKey);
        $creatorPks = $pendingRows->pluck('created_by')->filter()->unique();
        $creators = collect();
        if ($creatorPks->isNotEmpty()) {
            $emps = DB::table('employee_master')
                ->whereIn('pk', $creatorPks)
                ->orWhereIn('pk_old', $creatorPks)
                ->get(['pk', 'pk_old', 'first_name', 'last_name']);

            foreach ($emps as $e) {
                $fullName = trim(($e->first_name ?? '') . ' ' . ($e->last_name ?? ''));
                $label = $fullName ?: ('Employee #' . ($e->pk ?? $e->pk_old));

                if (! is_null($e->pk)) {
                    $creators[(string) $e->pk] = $label;
                }
                if (! is_null($e->pk_old)) {
                    $creators[(string) $e->pk_old] = $label;
                }
            }
        }

        $allFmlIds = $pendingRows->pluck('fml_id_apply')->filter()->unique()->values();
        $approvalFlagMap = collect();
        if ($allFmlIds->isNotEmpty()) {
            $approvalFlagMap = DB::table('security_family_id_apply_approval')
                ->select(
                    'security_fm_id_apply_pk',
                    DB::raw('MAX(CASE WHEN status = 1 THEN 1 ELSE 0 END) as has_level1'),
                    DB::raw('MAX(CASE WHEN status = 2 THEN 1 ELSE 0 END) as has_level2')
                )
                ->whereIn('security_fm_id_apply_pk', $allFmlIds->all())
                ->groupBy('security_fm_id_apply_pk')
                ->get()
                ->keyBy('security_fm_id_apply_pk');
        }

        $groupList = $groups->map(function ($rows) use ($creators, $isLevel1Only, $isLevel2Only, $hasBothApprovalRoles, $approvalFlagMap) {
            $first = $rows->sortBy('fml_id_apply')->first();
            $creatorName = $creators[(string) ($first->created_by ?? '')] ?? ('User #' . ($first->created_by ?? '--'));

            $statusInt = (int) ($first->id_status ?? 1);
            $approvalFlags = $approvalFlagMap->get((string) ($first->fml_id_apply ?? ''));
            $hasLevel1 = $approvalFlags ? (bool) ($approvalFlags->has_level1 ?? false) : false;
            $hasLevel2 = $approvalFlags ? (bool) ($approvalFlags->has_level2 ?? false) : false;

            $phaseLabel = 'Pending (Level 1)';
            $phaseClass = 'warning';
            if ($statusInt === 2 && $hasLevel2) {
                $phaseLabel = 'Approved';
                $phaseClass = 'success';
            } elseif ($statusInt === 3) {
                $phaseLabel = 'Rejected';
                $phaseClass = 'danger';
            } elseif ($statusInt === 1 && $hasLevel1 && ! $hasLevel2) {
                $phaseLabel = 'Pending Final Approval';
                $phaseClass = 'primary';
            }

            $canApprove = false;
            if ($statusInt === 1) {
                if (! $hasLevel1 && ($isLevel1Only || $hasBothApprovalRoles)) {
                    $canApprove = true;
                } elseif ($hasLevel1 && ! $hasLevel2 && ($isLevel2Only || $hasBothApprovalRoles)) {
                    $canApprove = true;
                }
            }

            return (object) [
                'first_id' => $first->fml_id_apply,
                'emp_id_apply' => $first->emp_id_apply,
                'created_by' => $first->created_by,
                'created_date' => $first->created_date,
                'submitted_by' => $creatorName,
                'member_count' => $rows->count(),
                'members' => $rows,
                'employee_type' => $first->employee_type ?? null,
                'phase_label' => $phaseLabel,
                'phase_class' => $phaseClass,
                'can_approve' => $canApprove,
                'status_int' => $statusInt,
                'has_level1' => $hasLevel1,
                'has_level2' => $hasLevel2,
            ];
        })->values();

        if ($search !== '') {
            $searchLower = mb_strtolower($search);
            $groupList = $groupList->filter(function ($g) use ($searchLower) {
                $submitted = mb_strtolower($g->submitted_by ?? '');
                $empId = mb_strtolower($g->emp_id_apply ?? '');

                return str_contains($submitted, $searchLower) || str_contains($empId, $searchLower);
            })->values();
        }

        if ($isLevel2Only) {
            $groupList = $groupList->filter(function ($g) {
                return (bool) ($g->has_level1 ?? false);
            })->values();
        }

        switch ($tabKey) {
            case 'for_approval':
                $list = $groupList->filter(function ($g) {
                    $isApproved = ((int) ($g->status_int ?? 1) === 2) && ((bool) ($g->has_level2 ?? false));
                    $isRejected = ((int) ($g->status_int ?? 1) === 3);
                    if ($isApproved || $isRejected || (int) ($g->status_int ?? 1) !== 1) {
                        return false;
                    }
                    if (! ((bool) ($g->has_level1 ?? false))) {
                        return false;
                    }

                    return (($g->can_approve ?? false) !== true);
                })->values();
                break;
            case 'issued':
                $list = $groupList->filter(function ($g) {
                    return ((int) ($g->status_int ?? 1) === 2) && ((bool) ($g->has_level2 ?? false));
                })->values()->map(function ($g) {
                    $memberFirst = isset($g->members) ? $g->members->sortBy('fml_id_apply')->first() : null;
                    $g->id_card_physical_print_done = Schema::hasColumn('security_family_id_apply', 'id_card_generate_date')
                        && $memberFirst
                        && ! empty($memberFirst->id_card_generate_date);

                    return $g;
                });
                break;
            case 'rejected':
                $list = $groupList->filter(function ($g) {
                    return (int) ($g->status_int ?? 1) === 3;
                })->values();
                break;
            case 'new':
            default:
                $list = $groupList->filter(function ($g) {
                    $isApproved = ((int) ($g->status_int ?? 1) === 2) && ((bool) ($g->has_level2 ?? false));
                    $isRejected = ((int) ($g->status_int ?? 1) === 3);

                    return ! $isApproved && ! $isRejected && ((int) ($g->status_int ?? 1) === 1) && (($g->can_approve ?? false) === true);
                })->values();
                break;
        }

        return $list;
    }

    public function dataTable(Collection $query): CollectionDataTable
    {
        return (new CollectionDataTable($query))
            ->addColumn('submitted_by', function ($group) {
                return '<strong>' . e($group->submitted_by ?? '--') . '</strong>';
            })
            ->addColumn('employee_type_badge', function ($group) {
                if (isset($group->employee_type) && $group->employee_type === 'Contractual Employee') {
                    return '<span class="badge bg-warning">Contractual</span>';
                }

                return '<span class="badge bg-info">Permanent</span>';
            })
            ->addColumn('emp_id_apply', function ($group) {
                return '<code>' . e($group->emp_id_apply ?? '--') . '</code>';
            })
            ->addColumn('member_count_badge', function ($group) {
                return '<span class="badge bg-primary">' . e((string) $group->member_count) . '</span>';
            })
            ->addColumn('status', function ($group) {
                return $this->renderStatusColumn($group);
            })
            ->addColumn('applied_on', function ($group) {
                return $group->created_date ? Carbon::parse($group->created_date)->format('d-m-Y H:i') : '--';
            })
            ->addColumn('actions', function ($group) {
                return $this->renderActionsColumn($group);
            })
            ->rawColumns(['submitted_by', 'employee_type_badge', 'emp_id_apply', 'member_count_badge', 'status', 'actions']);
    }

    private function renderStatusColumn(object $group): string
    {
        $class = e($group->phase_class ?? 'secondary');
        $label = e($group->phase_label ?? 'Unknown');
        $html = '<span class="badge bg-' . $class . '" title="' . $label . '">' . $label . '</span>';

        if (((int) ($group->status_int ?? 1)) === 2 && ! empty($group->id_card_physical_print_done)) {
            $html .= '<div class="small text-muted mt-1">Card printed</div>';
        }

        return $html;
    }

    /**
     * Mirrors resources/views/admin/security/family_idcard_approval/_family_approval_table.blade.php
     * exactly, including the `familyMembersQueryString` return-tab query string logic from the
     * top of index.blade.php.
     */
    private function renderActionsColumn(object $group): string
    {
        $request = $this->request();
        $familyMembersQs = ['from' => 'family_approval'];
        $returnParam = $request->input('return');
        if (in_array($returnParam, ['approval2', 'approval3'], true)) {
            $familyMembersQs['return'] = $returnParam;
        }
        $familyMembersQueryString = '?' . http_build_query($familyMembersQs);

        $viewUrl = route('admin.family_idcard.members', $group->first_id) . $familyMembersQueryString;

        $html = '<div class="d-flex gap-2 flex-wrap">';
        $html .= '<a href="' . e($viewUrl) . '" class="btn  btn-outline-info bg-transparent border-0 text-primary p-0" title="View Members">'
            . '<i class="material-icons material-symbols-rounded" style="font-size:18px;">visibility</i></a>';

        if ($group->can_approve ?? false) {
            $encryptedId = encrypt($group->first_id);
            $approveUrl = route('admin.security.family_idcard_approval.approve_group', $encryptedId);
            $token = csrf_token();

            $html .= '<form action="' . e($approveUrl) . '" method="POST" class="d-inline">'
                . '<input type="hidden" name="_token" value="' . e($token) . '">'
                . '<button type="submit" class="btn  btn-outline-success bg-transparent border-0 text-primary p-0" title="Approve" '
                . 'onclick="return confirm(\'Are you sure you want to approve?\')">'
                . '<i class="material-icons material-symbols-rounded" style="font-size:18px;">check_circle</i></button>'
                . '</form>';
            $html .= '<button type="button" class="btn  btn-outline-danger bg-transparent border-0 text-primary p-0" title="Reject" '
                . 'data-encrypted-id="' . e($encryptedId) . '" '
                . 'data-member-count="' . e((string) $group->member_count) . '" '
                . 'onclick="openRejectModal(this)">'
                . '<i class="material-icons material-symbols-rounded" style="font-size:18px;">cancel</i></button>';
        }

        $html .= '</div>';

        return $html;
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->addTableClass('table text-nowrap mb-0')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->parameters([
                'responsive' => true,
                'autoWidth' => false,
                'ordering' => false,
                'searching' => false,
                'pageLength' => 10,
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('submitted_by')->title('Submitted By')->orderable(false)->searchable(false),
            Column::computed('employee_type_badge')->title('Employee Type')->orderable(false)->searchable(false),
            Column::computed('emp_id_apply')->title('Employee ID')->orderable(false)->searchable(false),
            Column::computed('member_count_badge')->title('Member Count')->orderable(false)->searchable(false),
            Column::computed('status')->title('Status')->orderable(false)->searchable(false),
            Column::computed('applied_on')->title('Applied On')->orderable(false)->searchable(false),
            Column::computed('actions')->title('Actions')->orderable(false)->searchable(false),
        ];
    }

    protected function filename(): string
    {
        return 'FamilyIdCardApproval_' . date('YmdHis');
    }
}
