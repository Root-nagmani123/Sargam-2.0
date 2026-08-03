<?php

namespace App\DataTables;

use App\Http\Controllers\Admin\Security\EmployeeIDCardApprovalController;
use App\Support\DataTableRedisCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Yajra\DataTables\CollectionDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

/**
 * "Approval III - Employee ID Card" list — 4 tabs (new / for_approval / issued / rejected) on
 * one page. Reuses EmployeeIDCardApprovalController::buildApproval3Lists() (the exact same
 * pool-building/merge/search logic previously computed eagerly for every tab on every request),
 * scoped to a single `tab_key` per ajax call so each tab is a genuinely server-side-paginated
 * DataTable. Served via Yajra's Collection engine since the underlying data is a multi-source
 * merge, not a single query.
 */
class EmployeeIdcardApproval3DataTable extends DataTable
{
    public const LISTING_CACHE_EPOCH_KEY = 'employee_idcard_approval3_list_epoch';

    public static function bumpListingCacheEpoch(): void
    {
        DataTableRedisCache::bumpListEpoch(self::LISTING_CACHE_EPOCH_KEY, 'EmployeeIdcardApproval3DataTable');
    }

    /**
     * Server-side JSON for one tab of the Approval III list. .env: EMPLOYEE_IDCARD_APPROVAL3_CACHE_*.
     */
    public function ajax(): JsonResponse
    {
        $request = $this->request();

        return DataTableRedisCache::serveCachedAjax(
            $request,
            'employee_idcard_approval3_dt:v1:',
            self::LISTING_CACHE_EPOCH_KEY,
            [
                'enabled' => 'EMPLOYEE_IDCARD_APPROVAL3_CACHE_ENABLED',
                'seconds' => 'EMPLOYEE_IDCARD_APPROVAL3_CACHE_SECONDS',
            ],
            'EmployeeIdcardApproval3DataTable',
            fn () => parent::ajax(),
            [
                'tab_key' => (string) $request->input('tab_key', 'new'),
                'search' => (string) $request->input('search', ''),
                'date_from' => (string) $request->input('date_from', ''),
                'date_to' => (string) $request->input('date_to', ''),
            ]
        );
    }

    public function query(Request $request): Collection
    {
        $tabKey = $request->input('tab_key', 'new');
        if (! in_array($tabKey, ['new', 'for_approval', 'issued', 'rejected'], true)) {
            $tabKey = 'new';
        }

        $controller = new EmployeeIDCardApprovalController();
        $lists = $controller->buildApproval3Lists($request);

        return $lists[$tabKey] ?? collect();
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
        if (! empty($row->photo)) {
            $photoPath = str_starts_with($row->photo, 'idcard/') ? $row->photo : 'idcard/photos/' . $row->photo;
        }

        if (! $photoPath) {
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
     * for approvalStage = 3 (no card-print checkbox — that only appears at approvalStage 2).
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

        $viewUrl = route('admin.security.employee_idcard_approval.show', ['id' => $encryptedId, 'stage' => 3]);

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
        } elseif (! empty($row->is_view_only)) {
            if (in_array(($row->employee_type ?? ''), ['Permanent Employee', 'Contractual Employee'], true)) {
                $html .= '<span class="badge bg-warning">Pending from Final Approval</span>';
                $html .= '<small class="text-muted">' . e($row->final_status_hint ?? 'Recommended at Level 2') . '</small>';
            } else {
                $html .= '<span class="badge bg-info">View Only</span>';
                $html .= '<small class="text-muted">Approved at Level 1</small>';
            }
        } else {
            $approveRoute = route('admin.security.employee_idcard_approval.approve3', $encryptedId);
            $rejectRoute = route('admin.security.employee_idcard_approval.reject3', $encryptedId);
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
            ->addTableClass('table text-nowrap align-middle mb-0')
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
        return 'EmployeeIdcardApproval3_' . date('YmdHis');
    }
}
