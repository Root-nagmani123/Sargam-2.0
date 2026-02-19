<?php

namespace App\DataTables\Mess;

use App\Models\Mess\VendorItemMapping;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class VendorItemMappingDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     * @return \Yajra\DataTables\EloquentDataTable
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->addColumn('vendor_name', function ($row) {
                $vendor = $row->vendor;
                return $vendor ? ($vendor->name ?? 'N/A') : 'N/A';
            })
            ->addColumn('item_name', function ($row) {
                if ($row->itemSubcategory) {
                    return $row->itemSubcategory->item_name ?? 'N/A';
                }
                if ($row->itemCategory) {
                    return $row->itemCategory->category_name ?? 'N/A';
                }
                return '—';
            })
            ->addColumn('action', function ($row) {
                $editUrl = route('admin.mess.vendor-item-mappings.edit', $row->id);
                $deleteUrl = route('admin.mess.vendor-item-mappings.destroy', $row->id);
                $csrf = csrf_token();

                return '<a href="' . $editUrl . '" class="btn btn-sm btn-warning openEditVendorMapping">Edit</a>
                    <form action="' . $deleteUrl . '" method="POST" class="d-inline" onsubmit="return confirm(\'Are you sure you want to delete this mapping?\');">
                        <input type="hidden" name="_token" value="' . $csrf . '">
                        <input type="hidden" name="_method" value="DELETE">
                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                    </form>';
            })
            ->filterColumn('vendor_name', function ($query, $keyword) {
                $query->whereHas('vendor', function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('item_name', function ($query, $keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->whereHas('itemSubcategory', function ($sub) use ($keyword) {
                        $sub->where('item_name', 'like', "%{$keyword}%")
                            ->orWhere('subcategory_name', 'like', "%{$keyword}%")
                            ->orWhere('name', 'like', "%{$keyword}%");
                    })->orWhereHas('itemCategory', function ($sub) use ($keyword) {
                        $sub->where('category_name', 'like', "%{$keyword}%");
                    });
                });
            })
            ->orderColumn('vendor_name', function ($query, $order) {
                $query->orderBy(
                    \App\Models\Mess\Vendor::select('name')->whereColumn('mess_vendors.id', 'mess_vendor_item_mappings.vendor_id'),
                    $order
                );
            })
            ->filter(function ($query) {
                $searchValue = request()->input('search.value');
                if (!empty($searchValue)) {
                    $query->where(function ($sub) use ($searchValue) {
                        $sub->whereHas('vendor', fn ($q) => $q->where('name', 'like', "%{$searchValue}%"))
                            ->orWhereHas('itemSubcategory', function ($q) use ($searchValue) {
                                $q->where('item_name', 'like', "%{$searchValue}%")
                                    ->orWhere('subcategory_name', 'like', "%{$searchValue}%")
                                    ->orWhere('name', 'like', "%{$searchValue}%");
                            })
                            ->orWhereHas('itemCategory', fn ($q) => $q->where('category_name', 'like', "%{$searchValue}%"));
                    });
                }
            }, true)
            ->rawColumns(['action'])
            ->setRowId('id');
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\Mess\VendorItemMapping $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(VendorItemMapping $model): QueryBuilder
    {
        return $model->newQuery()
            ->with(['vendor', 'itemCategory', 'itemSubcategory'])
            ->orderBy('vendor_id')
            ->orderBy('id');
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('vendorMappingsTable')
            ->addTableClass('table table-bordered table-hover align-middle w-100')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->parameters([
                'responsive' => false,
                'autoWidth' => false,
                'ordering' => true,
                'searching' => true,
                'lengthChange' => true,
                'pageLength' => 25,
                'lengthMenu' => [[10, 25, 50, 100], [10, 25, 50, 100]],
                'order' => [[1, 'asc']],
                'language' => [
                    'search' => '',
                    'searchPlaceholder' => 'Search vendor mappings...',
                    'lengthMenu' => 'Show _MENU_ entries',
                    'info' => 'Showing _START_ to _END_ of _TOTAL_ vendor mappings',
                    'infoEmpty' => 'No vendor mappings',
                    'infoFiltered' => '(filtered from _MAX_ total)',
                    'paginate' => [
                        'first' => 'First',
                        'last' => 'Last',
                        'previous' => '&laquo;',
                        'next' => '&raquo;',
                    ],
                ],
            ]);
    }

    /**
     * Get the dataTable columns definition.
     *
     * @return array
     */
    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')
                ->title('S.No.')
                ->searchable(false)
                ->orderable(false)
                ->addClass('text-center')
                ->width('80px'),
            Column::computed('vendor_name')
                ->title('Vendor Name')
                ->searchable(true)
                ->orderable(false)
                ->addClass('text-center'),
            Column::computed('item_name')
                ->title('Item')
                ->searchable(true)
                ->orderable(false)
                ->addClass('text-center'),
            Column::computed('action')
                ->title('Actions')
                ->searchable(false)
                ->orderable(false)
                ->addClass('text-center')
                ->width('160px')
                ->exportable(false)
                ->printable(false),
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename(): string
    {
        return 'VendorItemMapping_' . date('YmdHis');
    }
}
