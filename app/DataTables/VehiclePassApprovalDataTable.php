<?php

namespace App\DataTables;

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
use App\Models\VehiclePassTWApply;
use App\Models\VehiclePassTWApplyApproval;
use App\Models\VehiclePassFWApply;

/**
 * Requested Vehicle Pass approval list (4 tabs: new / for_approval / issued / rejected)
 * on one page. Reproduces the merge/role/split logic previously computed eagerly in
 * VehiclePassApprovalController::index() for every tab on every request, but now scoped
 * to a single `tab_key` per ajax call so each tab is a genuinely server-side-paginated
 * DataTable. Served via Yajra's Collection engine because the underlying data is a merge
 * of two Eloquent sources (VehiclePassTWApply + VehiclePassFWApply) plus a bulk-resolved
 * approval-stats aggregate, not a single query.
 */
class VehiclePassApprovalDataTable extends DataTable
{
    public const LISTING_CACHE_EPOCH_KEY = 'security_vehicle_pass_approval_list_epoch';

    public static function bumpListingCacheEpoch(): void
    {
        DataTableRedisCache::bumpListEpoch(self::LISTING_CACHE_EPOCH_KEY, 'VehiclePassApprovalDataTable');
    }

    /**
     * Server-side JSON for one tab of the Requested Vehicle Pass approval list.
     * .env: SECURITY_VEHICLE_PASS_APPROVAL_CACHE_*.
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
            'security_vehicle_pass_approval_dt:v1:',
            self::LISTING_CACHE_EPOCH_KEY,
            [
                'enabled' => 'SECURITY_VEHICLE_PASS_APPROVAL_CACHE_ENABLED',
                'seconds' => 'SECURITY_VEHICLE_PASS_APPROVAL_CACHE_SECONDS',
            ],
            'VehiclePassApprovalDataTable',
            fn () => parent::ajax(),
            [
                'user_pk' => $currentUserPk,
                'has_security_card_role' => $hasSecurityCard,
                'has_admin_security_role' => $hasAdminSecurity,
                'tab_key' => (string) $request->input('tab_key', 'new'),
                'wheeler' => (string) $request->input('wheeler', 'tw'),
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
     * Same merge/filter logic as the pre-DataTable controller implementation
     * (VehiclePassApprovalController::index()), scoped down to the sub-list needed
     * for the requested tab_key.
     */
    private function buildTabCollection(Request $request): Collection
    {
        $hasSecurityCard = hasRole('Security Card');
        $hasAdminSecurity = hasRole('Admin Security');
        $isLevel1Only = $hasSecurityCard && ! $hasAdminSecurity;
        $isLevel2Only = $hasAdminSecurity && ! $hasSecurityCard;
        $hasBothApprovalRoles = $hasSecurityCard && $hasAdminSecurity;

        $search = trim((string) $request->input('search', ''));
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $wheeler = $request->input('wheeler', 'tw');
        $tabKey = $request->input('tab_key', 'new');
        if (! in_array($tabKey, ['new', 'for_approval', 'issued', 'rejected'], true)) {
            $tabKey = 'new';
        }

        $twHasApplicantName = Schema::hasColumn('vehicle_pass_tw_apply', 'applicant_name');
        $fwHasApplicantName = Schema::hasColumn('vehicle_pass_fw_apply', 'applicant_name');

        // Two Wheeler applications (regular only)
        $twQuery = VehiclePassTWApply::with([
            'vehicleType',
            'employee' => function ($q) {
                $q->select(['pk', 'emp_id', 'first_name', 'last_name']);
            },
        ])
            ->orderBy('created_date', 'desc');

        if ($search !== '') {
            $twQuery->where(function ($q) use ($search, $twHasApplicantName) {
                $like = '%' . $search . '%';
                $q->where('employee_id_card', 'like', $like)
                    ->orWhere('vehicle_no', 'like', $like);
                if ($twHasApplicantName) {
                    $q->orWhere('applicant_name', 'like', $like);
                }
            });
        }
        if (!empty($dateFrom)) {
            try {
                $from = Carbon::parse($dateFrom)->startOfDay()->toDateTimeString();
                $twQuery->where('created_date', '>=', $from);
            } catch (\Exception $e) {
            }
        }
        if (!empty($dateTo)) {
            try {
                $to = Carbon::parse($dateTo)->endOfDay()->toDateTimeString();
                $twQuery->where('created_date', '<=', $to);
            } catch (\Exception $e) {
            }
        }

        // Four Wheeler applications (regular only)
        $fwQuery = VehiclePassFWApply::with([
            'vehicleType',
            'employee' => function ($q) {
                $q->select(['pk', 'emp_id', 'first_name', 'last_name']);
            },
        ])
            ->orderBy('created_date', 'desc');

        if ($search !== '') {
            $fwQuery->where(function ($q) use ($search, $fwHasApplicantName) {
                $like = '%' . $search . '%';
                $q->where('employee_id_card', 'like', $like)
                    ->orWhere('vehicle_no', 'like', $like);
                if ($fwHasApplicantName) {
                    $q->orWhere('applicant_name', 'like', $like);
                }
            });
        }
        if (!empty($dateFrom)) {
            try {
                $from = Carbon::parse($dateFrom)->startOfDay()->toDateTimeString();
                $fwQuery->where('created_date', '>=', $from);
            } catch (\Exception $e) {
            }
        }
        if (!empty($dateTo)) {
            try {
                $to = Carbon::parse($dateTo)->endOfDay()->toDateTimeString();
                $fwQuery->where('created_date', '<=', $to);
            } catch (\Exception $e) {
            }
        }

        $twRows = collect();
        $fwRows = collect();

        if ($wheeler === 'tw') {
            $twRows = $twQuery->get();
        } elseif ($wheeler === 'fw') {
            $fwRows = $fwQuery->get();
        } else {
            $twRows = $twQuery->get();
            $fwRows = $fwQuery->get();
        }

        // NOTE: preserved exactly as in the original controller — the approval table is
        // keyed by vehicle_TW_pk regardless of whether the source row is TW or FW.
        $vehicleKeys = $twRows->pluck('vehicle_tw_pk')
            ->merge($fwRows->pluck('vehicle_fw_pk'))
            ->filter()
            ->unique()
            ->values();

        $approvalStats = collect();
        if ($vehicleKeys->isNotEmpty()) {
            $approvalStats = VehiclePassTWApplyApproval::select(
                'vehicle_TW_pk',
                DB::raw('MAX(CASE WHEN status = 1 OR veh_recommend_status = 1 THEN 1 ELSE 0 END) as has_level1'),
                DB::raw('MAX(CASE WHEN status = 2 THEN 1 ELSE 0 END) as has_level2')
            )
                ->whereIn('vehicle_TW_pk', $vehicleKeys)
                ->groupBy('vehicle_TW_pk')
                ->get()
                ->keyBy('vehicle_TW_pk');
        }

        $mapFn = function ($r, string $kind) use ($isLevel1Only, $isLevel2Only, $hasBothApprovalRoles, $approvalStats, $twHasApplicantName, $fwHasApplicantName) {
            $statusInt = (int) ($r->vech_card_status ?? 1);
            $vehicleKey = $kind === 'tw' ? $r->vehicle_tw_pk : $r->vehicle_fw_pk;
            $stat = $approvalStats->get($vehicleKey);
            $hasLevel1 = $stat ? (bool) ($stat->has_level1 ?? false) : false;
            $hasLevel2 = $stat ? (bool) ($stat->has_level2 ?? false) : false;

            $phaseLabel = 'Pending (Level 1)';
            $phaseClass = 'warning';
            if ($statusInt === 2 && $hasLevel2) {
                $phaseLabel = 'Approved';
                $phaseClass = 'success';
            } elseif ($statusInt === 3) {
                $phaseLabel = 'Rejected';
                $phaseClass = 'danger';
            } elseif ($statusInt === 1 && $hasLevel1 && !$hasLevel2) {
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

            $employeeName = $r->employee_id_card ?? '--';
            if (isset($r->employee) && $r->employee) {
                $resolved = trim((string) (($r->employee->first_name ?? '') . ' ' . ($r->employee->last_name ?? '')));
                if ($resolved !== '') {
                    $employeeName = $resolved . ($r->employee_id_card ? ' (' . $r->employee_id_card . ')' : '');
                }
            } elseif (($kind === 'tw' && $twHasApplicantName) || ($kind === 'fw' && $fwHasApplicantName)) {
                $fallbackName = trim((string) ($r->applicant_name ?? ''));
                if ($fallbackName !== '') {
                    $employeeName = $fallbackName . ($r->employee_id_card ? ' (' . $r->employee_id_card . ')' : '');
                }
            }

            $vehicleTypeLabel = $kind === 'tw' ? 'Two Wheeler' : 'Four Wheeler';

            return (object) [
                'id' => $kind . '-' . ($kind === 'tw' ? $r->vehicle_tw_pk : $r->vehicle_fw_pk),
                'vehicle_number' => $r->vehicle_no ?? '--',
                'employee_id' => $r->employee_id_card ?? '--',
                'employee_name' => $employeeName,
                'vehicle_type' => $vehicleTypeLabel,
                'status' => $phaseLabel,
                'status_class' => $phaseClass,
                'created_date' => $r->created_date,
                'request_type' => 'fresh',
                'vehicle_pass_no' => $r->vehicle_req_id ?? '--',
                'can_approve' => $canApprove,
                'status_int' => $statusInt,
                'has_level1' => $hasLevel1,
                'has_level2' => $hasLevel2,
            ];
        };

        $twDtos = $twRows->map(fn ($r) => $mapFn($r, 'tw'));
        $fwDtos = $fwRows->map(fn ($r) => $mapFn($r, 'fw'));

        $merged = $twDtos->concat($fwDtos)->sortByDesc(function ($d) {
            return $d->created_date ? (Carbon::parse($d->created_date)->timestamp ?? 0) : 0;
        })->values();

        if ($isLevel2Only) {
            $merged = $merged->filter(function ($d) {
                return (bool) ($d->has_level1 ?? false);
            })->values();
        }

        switch ($tabKey) {
            case 'for_approval':
                // "Other stage" only after Level 1 is done; excludes plain Level-1 pending rows.
                $list = $merged->filter(function ($d) {
                    $isApproved = (($d->status_int ?? 1) === 2) && (($d->has_level2 ?? false) === true);
                    $isRejected = (($d->status_int ?? 1) === 3);
                    if ($isApproved || $isRejected || (int) ($d->status_int ?? 1) !== 1) {
                        return false;
                    }
                    if (!((bool) ($d->has_level1 ?? false))) {
                        return false;
                    }

                    return (($d->can_approve ?? false) !== true);
                })->values();
                break;
            case 'issued':
                // Issued tab should show only finally approved records.
                $list = $merged->filter(fn ($d) => (($d->status_int ?? 1) === 2) && (($d->has_level2 ?? false) === true))->values();
                break;
            case 'rejected':
                $list = $merged->filter(fn ($d) => ($d->status_int ?? 1) === 3)->values();
                break;
            case 'new':
            default:
                $list = $merged->filter(function ($d) {
                    $isApproved = (($d->status_int ?? 1) === 2) && (($d->has_level2 ?? false) === true);
                    $isRejected = (($d->status_int ?? 1) === 3);
                    return !$isApproved && !$isRejected && (($d->status_int ?? 1) === 1) && (($d->can_approve ?? false) === true);
                })->values();
                break;
        }

        return $list;
    }

    public function dataTable(Collection $query): CollectionDataTable
    {
        return (new CollectionDataTable($query))
            ->addColumn('employee_name', function ($row) {
                return e($row->employee_name ?? '--');
            })
            ->addColumn('vehicle_number', function ($row) {
                return '<strong>' . e($row->vehicle_number ?? '--') . '</strong>';
            })
            ->addColumn('vehicle_type', function ($row) {
                return e($row->vehicle_type ?? '--');
            })
            ->addColumn('status', function ($row) {
                $class = e($row->status_class ?? 'secondary');
                $label = e($row->status ?? '--');

                return '<span class="badge bg-' . $class . '">' . $label . '</span>';
            })
            ->addColumn('vehicle_pass_no', function ($row) {
                return e($row->vehicle_pass_no ?? '--');
            })
            ->addColumn('employee_id', function ($row) {
                return e($row->employee_id ?? '--');
            })
            ->addColumn('created_date', function ($row) {
                return $row->created_date ? Carbon::parse($row->created_date)->format('d-m-Y H:i') : '--';
            })
            ->addColumn('actions', function ($row) {
                return $this->renderActionsColumn($row);
            })
            ->rawColumns(['vehicle_number', 'status', 'actions']);
    }

    /**
     * Mirrors resources/views/admin/security/vehicle_pass_approval/_vehicle_pass_table.blade.php
     * exactly: View link + (when can_approve) Approve/Reject buttons carrying the same
     * btn-veh-approve / btn-veh-reject classes and data-encrypted-id attribute the
     * page-level modal-trigger JS in index.blade.php depends on.
     */
    private function renderActionsColumn(object $row): string
    {
        $encryptId = encrypt($row->id);
        $showUrl = route('admin.security.vehicle_pass_approval.show', $encryptId);

        $html = '<div class="d-flex gap-2 flex-wrap">';
        $html .= '<a href="' . e($showUrl) . '" class="btn  btn-info bg-transparent border-0 text-primary p-0" title="View Details">'
            . '<i class="material-icons material-symbols-rounded">visibility</i></a>';

        if ($row->can_approve ?? false) {
            $html .= '<button type="button" class="btn  btn-success btn-veh-approve bg-transparent border-0 text-primary p-0" '
                . 'data-encrypted-id="' . e($encryptId) . '" title="Approve">'
                . '<i class="material-icons material-symbols-rounded">check_circle</i></button>';
            $html .= '<button type="button" class="btn  btn-danger btn-veh-reject bg-transparent border-0 text-primary p-0" '
                . 'data-encrypted-id="' . e($encryptId) . '" title="Reject">'
                . '<i class="material-icons material-symbols-rounded">cancel</i></button>';
        }

        $html .= '</div>';

        return $html;
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->addTableClass('table text-nowrap align-middle mb-0 vehicle-pass-approval-table')
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
            Column::computed('employee_name')->title('Employee Name')->orderable(false)->searchable(false),
            Column::computed('vehicle_number')->title('Vehicle Number')->orderable(false)->searchable(false),
            Column::computed('vehicle_type')->title('Vehicle Type')->orderable(false)->searchable(false),
            Column::computed('status')->title('Status')->orderable(false)->searchable(false),
            Column::computed('vehicle_pass_no')->title('Vehicle Pass No')->orderable(false)->searchable(false),
            Column::computed('employee_id')->title('Employee ID')->orderable(false)->searchable(false),
            Column::computed('created_date')->title('Applied On')->orderable(false)->searchable(false),
            Column::computed('actions')->title('Actions')->orderable(false)->searchable(false),
        ];
    }

    protected function filename(): string
    {
        return 'VehiclePassApproval_' . date('YmdHis');
    }
}
