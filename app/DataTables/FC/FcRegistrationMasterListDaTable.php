<?php

namespace App\DataTables\FC;

use App\Models\FcRegistrationMaster;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class FcRegistrationMasterListDaTable extends DataTable
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
            ->addColumn('service_master_pk', function ($row) {
                return $row->service_master_pk ?? '';
            })
            ->addColumn('schema_id', function ($row) {
                return $row->schema_id ?? '';
            })
            ->addColumn('contact_no', function ($row) {
                return $row->contact_no ?? '';
            })
            ->addColumn('dob', function ($row) {
                return $row->dob ??  '';
            })
            ->addColumn('display_name', function ($row) {
                return $row->display_name ?? '';
            })
            ->addColumn('first_name', function ($row) {
                return $row->first_name ?? '';
            })
            ->addColumn('middle_name', function ($row) {
                return $row->middle_name ?? '';
            })
            ->addColumn('last_name', function ($row) {
                return $row->last_name ?? '';
            })
            ->addColumn('email', function ($row) {
                return $row->email ?? '';
            })
            ->addColumn('rank', function ($row) {
                return $row->rank ?? '';
            })
            ->addColumn('web_auth', function ($row) {
                return $row->web_auth ?? '';
            })
            ->addColumn('exam_year', function ($row) {
                return $row->exam_year ?? '';
            })
            ->filterColumn('rank', function ($query, $keyword) {
                $query->whereRaw("BINARY `rank` = ?", [$keyword]);
            })
            ->filterColumn('display_name', function ($query, $keyword) {
                $query->where("display_name", 'like', "%{$keyword}%");
            })
            ->filterColumn('first_name', function ($query, $keyword) {
                $query->where("first_name", 'like', "%{$keyword}%");
            })
            ->filterColumn('middle_name', function ($query, $keyword) {
                $query->where("middle_name", 'like', "%{$keyword}%");
            })
            ->filterColumn('last_name', function ($query, $keyword) {
                $query->where("last_name", 'like', "%{$keyword}%");
            })
            ->filterColumn('email', function ($query, $keyword) {
                $query->where("email", 'like', "%{$keyword}%");
            })
            ->filterColumn('web_auth', function ($query, $keyword) {
                $query->where("web_auth", 'like', "%{$keyword}%");
            })
            ->filterColumn('exam_year', function ($query, $keyword) {
                // $query->whereRaw("exam_year", '=', $keyword);
                $query->whereRaw("BINARY `exam_year` = ?", [$keyword]);
            })->addColumn('action', function ($row) {
                return '<a href="' . route('admin.registration.edit', $row->pk) . '" class="btn btn-sm btn-primary">Edit</a>';
            })
            ->rawColumns(['service_master_pk', 'schema_id', 'contact_no', 'dob', 'display_name', 'first_name', 'middle_name', 'last_name', 'email', 'rank', 'web_auth', 'exam_year', 'action']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\FcRegistrationMasterListDaTable $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(FcRegistrationMaster $model): QueryBuilder
    {
        return $model->select('pk', 'email', 'contact_no', 'display_name', 'schema_id', 'first_name', 'middle_name', 'last_name', 'rank', 'exam_year', 'service_master_pk', 'web_auth', 'dob')->newQuery();
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('fcregistrationmasterlistdatable-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->selectStyleSingle()
            ->parameters([
                'responsive' => true,
                'scrollX' => true,
                'autoWidth' => false,
                'order' => [],
            ])
            ->buttons([
                Button::make('excel'),
                Button::make('csv'),
                Button::make('pdf'),
                Button::make('print'),
                Button::make('reset'),
                Button::make('reload')
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
            Column::computed('DT_RowIndex')->title('S.No.')->searchable(false)->orderable(false),
            Column::make('service_master_pk')->title('Service Master PK')->orderable(false)->searchable(false),
            Column::make('schema_id')->title('Schema ID')->searchable(false)->orderable(false),
            Column::make('contact_no')->title('Contact No')->searchable(false)->orderable(false),
            Column::make('display_name')->title('Display Name')->searchable(true)->orderable(false),
            Column::make('first_name')->title('First Name')->searchable(true)->orderable(false),
            Column::make('middle_name')->title('Middle Name')->searchable(true)->orderable(false),
            Column::make('last_name')->title('Last Name')->searchable(true)->orderable(false),
            Column::make('email')->title('Email')->searchable(true)->orderable(false),
            Column::make('rank')->title('Rank')->searchable(true)->orderable(false),
            Column::make('dob')->title('Date of Birth')->searchable(false)->orderable(false),
            Column::make('web_auth')->title('Web Auth')->searchable(true)->orderable(false),
            Column::make('exam_year')->title('Exam Year')->searchable(true)->orderable(false),
            Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->width(60)
                ->addClass('text-center')->title('Action'),
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename(): string
    {
        return 'FcRegistrationMasterListDaTable_' . date('YmdHis');
    }
}
