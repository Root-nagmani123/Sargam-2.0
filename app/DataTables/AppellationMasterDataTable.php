<?php

namespace App\DataTables;

use App\Models\AppellationMaster;
use Illuminate\Database\Eloquent\Builder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Column;

class AppellationMasterDataTable extends DataTable
{
    public function dataTable(Builder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()

            ->editColumn('appettation_name', fn($row) => $row->appettation_name ?? 'N/A')

            // Status: display-only soft badge. The control lives in the action stack.
            ->addColumn('status', function ($row) {
                $isActive = (int) $row->active_inactive === 1;

                return '<span class="status-pill badge rounded-1 ' . ($isActive ? 'bg-success-subtle' : 'bg-danger-subtle') . '">'
                    . ($isActive ? 'Active' : 'Inactive')
                    . '</span>';
            })

            // Action: Edit · status switch · Delete, as equal-width icon-over-label stacks.
            ->addColumn('actions', function ($row) {
                $isActive = (int) $row->active_inactive === 1;
                $name     = e($row->appettation_name ?? '');
                $delete   = route('master.appellation.delete', encrypt($row->pk));

                // Inactive is stored as 0 (status switch) or 2 (form) — the edit
                // modal only knows 1/2, so normalise before handing it over.
                $formStatus = $isActive ? 1 : 2;

                $html = '<div class="mst-act-group" role="group" aria-label="Row actions">';

                $html .= '<button type="button" class="mst-act mst-act--edit appl-edit-btn"'
                    . ' data-id="' . e(encrypt($row->pk)) . '"'
                    . ' data-name="' . $name . '"'
                    . ' data-status="' . $formStatus . '"'
                    . ' title="Edit ' . $name . '">'
                    . '<span class="mst-act__icon"><i class="bi bi-pencil" aria-hidden="true"></i></span>'
                    . '<span class="mst-act__label">Edit</span>'
                    . '</button>';

                // No .form-check/.form-switch wrapper here — custom.css pulls the
                // input -2.375rem left inside one, which breaks the stacked layout.
                $html .= '<label class="mst-act mst-act--toggle">'
                    . '<span class="mst-act__icon">'
                    . '<input class="form-check-input status-toggle" type="checkbox" role="switch"'
                    . ' data-table="appellation_master" data-column="active_inactive"'
                    . ' data-id="' . $row->pk . '"' . ($isActive ? ' checked' : '')
                    . ' aria-label="' . ($isActive ? 'Deactivate' : 'Activate') . ' ' . $name . '">'
                    . '</span>'
                    . '<span class="mst-act__label">' . ($isActive ? 'Deactivate' : 'Activate') . '</span>'
                    . '</label>';

                // destroy() refuses active records — show that instead of a red
                // icon that always fails.
                if ($isActive) {
                    $html .= '<span class="mst-act mst-act--del is-disabled" aria-disabled="true"'
                        . ' title="Active records cannot be deleted. Deactivate it first.">'
                        . '<span class="mst-act__icon"><i class="bi bi-trash" aria-hidden="true"></i></span>'
                        . '<span class="mst-act__label">Delete</span>'
                        . '</span>';
                } else {
                    $html .= '<form action="' . $delete . '" method="POST" class="mst-del-form" data-name="' . $name . '">'
                        . csrf_field() . method_field('DELETE')
                        . '<button type="submit" class="mst-act mst-act--del" title="Delete ' . $name . '">'
                        . '<span class="mst-act__icon"><i class="bi bi-trash" aria-hidden="true"></i></span>'
                        . '<span class="mst-act__label">Delete</span>'
                        . '</button>'
                        . '</form>';
                }

                return $html . '</div>';
            })

            ->rawColumns(['status', 'actions']);
    }

    public function query(AppellationMaster $model): Builder
    {
        $query = $model->newQuery();

        // Status pills (view) → data.status_filter (minifiedAjax script below).
        $status = request()->query('status_filter');

        if ($status === '1') {
            $query->where('active_inactive', 1);
        } elseif ($status === '0') {
            // Inactive is 0 or 2 depending on which control wrote it.
            $query->where(function ($q) {
                $q->where('active_inactive', '!=', 1)->orWhereNull('active_inactive');
            });
        }

        return $query->orderBy('pk', 'desc');
    }

    /**
     * Fixed widths on everything except the name.
     *
     * Without them DataTables hands the leftover width to the last column, so
     * the action stack floats in the middle of a very wide cell instead of
     * sitting against the grid's right edge (master-admin.css right-aligns it).
     */
    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S. No.')->addClass('text-nowrap')->width('5.5rem'),
            Column::make('appettation_name')->title('Appellation Name'),
            Column::computed('status')->title('Status')->addClass('text-nowrap')->width('8rem'),
            Column::computed('actions')->title('Action')->addClass('text-nowrap')->width('13rem'),
        ];
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('appellation-master-table')
            ->columns($this->getColumns())
            ->minifiedAjax('', 'data.status_filter = (window.applStatusFilter || "");')
            ->pageLength(10);
    }

    protected function filename(): string
    {
        return 'AppellationMaster_' . date('YmdHis');
    }
}
