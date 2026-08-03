<?php

namespace App\DataTables;

use App\Http\Controllers\Admin\FamilyIDCardRequestController;
use App\Support\DataTableRedisCache;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\CollectionDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

/**
 * Active (id_status = 1) tab of /admin/family-idcard.
 *
 * Underlying dataset is a computed PHP collection (grouped, one row per multi-member
 * application) built by {@see FamilyIDCardRequestController::fetchFamilyIdcardApplicantRows()}
 * + {@see FamilyIDCardRequestController::buildFamilyIdcardGroupedListFromRows()}, so this is
 * served via Yajra's Collection engine rather than a single Eloquent query (mirrors
 * {@see EmployeeIdcardApproval1DataTable}).
 */
class FamilyIdCardActiveDataTable extends DataTable
{
    /**
     * Same epoch key value as FamilyIDCardRequestController::LISTING_CACHE_EPOCH_KEY
     * ('admin_family_idcard_index_list_epoch'), so every existing bumpIndexListCacheEpoch()
     * call site keeps invalidating this table's cache too, with no changes to those call sites.
     */
    public const LISTING_CACHE_EPOCH_KEY = 'admin_family_idcard_index_list_epoch';

    /**
     * Server-side JSON for the Active list. .env: FAMILY_IDCARD_ACTIVE_DATATABLE_CACHE_*.
     */
    public function ajax(): JsonResponse
    {
        $request = $this->request();
        $createdBy = Auth::user()->user_id ?? null;
        $search = trim((string) $request->input('search_filter', $request->input('search', '')));
        $cardType = trim((string) $request->input('card_type', ''));

        return DataTableRedisCache::serveCachedAjax(
            $request,
            'admin_family_idcard_active_dt:v1:',
            self::LISTING_CACHE_EPOCH_KEY,
            [
                'enabled' => 'FAMILY_IDCARD_ACTIVE_DATATABLE_CACHE_ENABLED',
                'seconds' => 'FAMILY_IDCARD_ACTIVE_DATATABLE_CACHE_SECONDS',
            ],
            'FamilyIdCardActiveDataTable',
            fn () => parent::ajax(),
            [
                'created_by' => $createdBy,
                'search' => $search,
                'card_type' => $cardType,
            ]
        );
    }

    public function query(Request $request): Collection
    {
        $createdBy = Auth::user()->user_id ?? null;
        $search = trim((string) $request->input('search_filter', $request->input('search', '')));
        $cardType = trim((string) $request->input('card_type', ''));

        $rows = FamilyIDCardRequestController::fetchFamilyIdcardApplicantRows($createdBy, $search);
        $activeRows = $rows->filter(fn ($r) => (int) ($r->id_status ?? 1) === 1)->values();

        return FamilyIDCardRequestController::buildFamilyIdcardGroupedListFromRows($activeRows, $cardType);
    }

    public function dataTable(Collection $query): CollectionDataTable
    {
        return (new CollectionDataTable($query))
            ->addIndexColumn()
            ->addColumn('request_date_fmt', function ($group) {
                return $group->created_at ? Carbon::parse($group->created_at)->format('d-m-Y') : '--';
            })
            ->addColumn('member_count_link', function ($group) {
                return $this->renderMemberCountLink($group);
            })
            ->addColumn('card_type_fmt', function ($group) {
                return e($group->card_type ?? 'Family Card');
            })
            ->addColumn('actions', function ($group) {
                return $this->renderActionsColumn($group);
            })
            ->rawColumns(['member_count_link', 'actions']);
    }

    private function renderMemberCountLink(object $group): string
    {
        $url = route('admin.family_idcard.members', $group->first_id);

        return '<a href="' . e($url) . '" class="text-primary fw-medium">' . e((string) $group->member_count) . '</a>';
    }

    /**
     * Mirrors the Active tab @forelse block in resources/views/admin/family_idcard/index.blade.php.
     */
    private function renderActionsColumn(object $group): string
    {
        $viewUrl = route('admin.family_idcard.members', $group->first_id);

        $html = '<div class="d-flex gap-2">';
        $html .= '<a href="' . e($viewUrl) . '" class="btn  btn-outline-primary bg-transparent border-0 text-primary p-0" title="View members">'
            . '<i class="material-icons material-symbols-rounded" style="font-size:18px;">visibility</i></a>';

        if ($group->can_delete ?? true) {
            $editUrl = route('admin.family_idcard.edit', $group->first_id);
            $deleteUrl = route('admin.family_idcard.destroy', $group->first_id);
            $token = csrf_token();

            $html .= '<a href="' . e($editUrl) . '" class="btn  btn-outline-primary bg-transparent border-0 text-primary p-0" title="Edit">'
                . '<i class="material-icons material-symbols-rounded" style="font-size:18px;">edit</i></a>';
            $html .= '<form action="' . e($deleteUrl) . '" method="POST" class="d-inline" onsubmit="return confirm(\'Are you sure you want to archive this request?\');">'
                . '<input type="hidden" name="_token" value="' . e($token) . '">'
                . '<input type="hidden" name="_method" value="DELETE">'
                . '<button type="submit" class="btn btn-outline-danger bg-transparent border-0 text-primary p-0" title="Archive">'
                . '<i class="material-icons material-symbols-rounded" style="font-size:18px;">delete</i></button>'
                . '</form>';
        }

        $html .= '</div>';

        return $html;
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('familyIdcardActiveTable')
            ->addTableClass('table text-nowrap align-middle mb-0 family-idcard-table')
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
                    'info' => 'Showing _START_ to _END_ of _TOTAL_ requests',
                    'infoEmpty' => 'No requests found',
                    'infoFiltered' => '',
                    'zeroRecords' => 'No family ID card requests found.',
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
            Column::computed('request_date_fmt')->title('Request Date')->orderable(false)->searchable(false),
            Column::make('employee_id')->title('Employee ID')->orderable(false)->searchable(false),
            Column::make('employee_name')->title('Employee Name')->orderable(false)->searchable(false),
            Column::make('designation')->title('Designation')->orderable(false)->searchable(false),
            Column::make('section')->title('Department')->orderable(false)->searchable(false),
            Column::computed('member_count_link')->title('No of Members')->orderable(false)->searchable(false),
            Column::computed('card_type_fmt')->title('ID Type')->orderable(false)->searchable(false),
            Column::computed('actions')->title('Actions')->addClass('family-idcard-actions-col')->orderable(false)->searchable(false),
        ];
    }

    protected function filename(): string
    {
        return 'FamilyIdCardActive_' . date('YmdHis');
    }
}
