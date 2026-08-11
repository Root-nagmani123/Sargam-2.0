<?php

namespace App\DataTables;

use App\Http\Controllers\Admin\DuplicateIDCardRequestController;
use App\Support\DataTableRedisCache;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\CollectionDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

/**
 * Duplicate ID Card request list for the current logged-in applicant
 * (admin/duplicate-idcard). Data is a merge of Permanent + Other/Contractual
 * duplicate requests (see {@see DuplicateIDCardRequestController::buildDuplicateIdcardIndexItems()}),
 * so it is served via Yajra's Collection engine rather than a single Eloquent query.
 */
class DuplicateIdCardDataTable extends DataTable
{
    public const LISTING_CACHE_EPOCH_KEY = 'admin_duplicate_idcard_index_list_epoch';

    public static function bumpListingCacheEpoch(): void
    {
        DataTableRedisCache::bumpListEpoch(self::LISTING_CACHE_EPOCH_KEY, 'DuplicateIdCardDataTable');
    }

    /**
     * Server-side JSON for the Duplicate ID Card request list. .env: DUPLICATE_IDCARD_DATATABLE_CACHE_*.
     */
    public function ajax(): JsonResponse
    {
        $request = $this->request();
        $user = Auth::user();
        $employeePk = $user->user_id ?? $user->pk ?? null;

        return DataTableRedisCache::serveCachedAjax(
            $request,
            'admin_duplicate_idcard_dt:v1:',
            self::LISTING_CACHE_EPOCH_KEY,
            [
                'enabled' => 'DUPLICATE_IDCARD_DATATABLE_CACHE_ENABLED',
                'seconds' => 'DUPLICATE_IDCARD_DATATABLE_CACHE_SECONDS',
            ],
            'DuplicateIdCardDataTable',
            fn () => parent::ajax(),
            [
                'employee_pk' => $employeePk,
                'search' => trim((string) $request->input('search.value', '')),
            ]
        );
    }

    public function query(Request $request): Collection
    {
        $user = Auth::user();
        $employeePk = $user->user_id ?? $user->pk ?? null;
        $search = trim((string) $request->input('search.value', ''));

        return DuplicateIDCardRequestController::buildDuplicateIdcardIndexItemsStatic($employeePk, $search);
    }

    public function dataTable(Collection $query): CollectionDataTable
    {
        return (new CollectionDataTable($query))
            ->addIndexColumn()
            ->editColumn('employee_dob', function ($row) {
                return $row->employee_dob ? Carbon::parse($row->employee_dob)->format('d-m-Y') : '--';
            })
            ->addColumn('employee_photo', function ($row) {
                return $this->renderPhotoColumn($row);
            })
            ->addColumn('document', function ($row) {
                return $this->renderDocumentColumn($row);
            })
            ->editColumn('valid_from', function ($row) {
                return $row->valid_from ? Carbon::parse($row->valid_from)->format('d-m-Y') : '--';
            })
            ->editColumn('valid_to', function ($row) {
                return $row->valid_to ? Carbon::parse($row->valid_to)->format('d-m-Y') : '--';
            })
            ->editColumn('request_date', function ($row) {
                return $row->request_date ? Carbon::parse($row->request_date)->format('d-m-Y') : '--';
            })
            ->addColumn('actions', function ($row) {
                return $this->renderActionsColumn($row);
            })
            ->rawColumns(['employee_photo', 'document', 'actions']);
    }

    /**
     * Mirrors resources/views/admin/duplicate_idcard/index.blade.php's photo <td> block exactly.
     */
    private function renderPhotoColumn(object $row): string
    {
        $p = $row->photo_path;
        if ($p && strpos((string) $p, '/') === false) {
            $p = 'idcard/photos/' . $p;
        }
        $photoExists = $p && Storage::disk('public')->exists($p);

        if ($photoExists) {
            return '<a href="' . e(asset('storage/' . $p)) . '" target="_blank">Download</a>';
        }

        return '--';
    }

    /**
     * Mirrors resources/views/admin/duplicate_idcard/index.blade.php's document <td> block exactly.
     */
    private function renderDocumentColumn(object $row): string
    {
        $d = $row->doc_path;
        if ($d && strpos((string) $d, '/') === false) {
            $d = 'idcard/dup_docs/' . $d;
        }
        $docExists = $d && Storage::disk('public')->exists($d);

        if ($docExists) {
            return '<a href="' . e(asset('storage/' . $d)) . '" target="_blank">Download</a>';
        }

        return '--';
    }

    /**
     * Mirrors resources/views/admin/duplicate_idcard/index.blade.php's actions <td> block exactly.
     */
    private function renderActionsColumn(object $row): string
    {
        if (empty($row->user_may_edit)) {
            return '<span class="text-muted small">—</span>';
        }

        $editUrl = route('admin.duplicate_idcard.edit', $row->id);
        $deleteUrl = route('admin.duplicate_idcard.destroy', $row->id);
        $token = csrf_token();

        $html = '<div class="d-flex align-items-center gap-2">';
        $html .= '<a href="' . e($editUrl) . '" class="btn btn-outline-primary bg-transparent border-0 text-primary p-0" title="Edit">'
            . '<i class="material-icons material-symbols-rounded" style="font-size:16px;">edit</i>'
            . '</a>';
        $html .= '<form action="' . e($deleteUrl) . '" method="POST" class="d-inline" '
            . 'onsubmit="return confirm(\'Delete this duplicate ID card request? This cannot be undone.\');">'
            . '<input type="hidden" name="_token" value="' . e($token) . '">'
            . '<input type="hidden" name="_method" value="DELETE">'
            . '<button type="submit" class="btn btn-outline-danger bg-transparent border-0 text-danger p-0" title="Delete">'
            . '<i class="material-icons material-symbols-rounded" style="font-size:16px;">delete</i>'
            . '</button>'
            . '</form>';
        $html .= '</div>';

        return $html;
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('duplicateIdcardTable')
            ->addTableClass('table text-nowrap align-middle')
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
                    'zeroRecords' => 'No requests found.',
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
            Column::computed('DT_RowIndex')->title('S. No.')->addClass('text-center')->orderable(false)->searchable(false)->width('50px'),
            Column::make('employee_name')->title('Employee Name')->orderable(false)->searchable(false),
            Column::make('designation')->title('Designation')->orderable(false)->searchable(false),
            Column::make('department')->title('Department')->orderable(false)->searchable(false),
            Column::make('id_card_no')->title('ID Card No')->orderable(false)->searchable(false),
            Column::make('employee_dob')->title('Date Of Birth')->orderable(false)->searchable(false),
            Column::make('blood_group')->title('Blood Group')->orderable(false)->searchable(false),
            Column::make('mobile_no')->title('Contact No.')->orderable(false)->searchable(false),
            Column::make('card_reason')->title('Reason')->orderable(false)->searchable(false),
            Column::make('employee_type')->title('Employee Type')->orderable(false)->searchable(false),
            Column::computed('employee_photo')->title('Employee Photo')->orderable(false)->searchable(false),
            Column::computed('document')->title('Document (If Any)')->orderable(false)->searchable(false),
            Column::make('valid_from')->title('Valid From')->orderable(false)->searchable(false),
            Column::make('valid_to')->title('Valid To')->orderable(false)->searchable(false),
            Column::make('status_label')->title('Status')->orderable(false)->searchable(false),
            Column::make('request_date')->title('Request Date')->orderable(false)->searchable(false),
            Column::computed('actions')->title('Actions')->addClass('text-center')->orderable(false)->searchable(false),
        ];
    }

    protected function filename(): string
    {
        return 'DuplicateIdCard_' . date('YmdHis');
    }
}
