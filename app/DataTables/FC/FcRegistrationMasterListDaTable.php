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
use Illuminate\Support\Facades\DB; // Add this for joins


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

            // 🔹 Course Name column
            ->addColumn('course_name', function ($row) {
                return $row->course_name ?? 'N/A';
            })
            // 🔹 Exemption Category column
            ->addColumn('exemption_name', function ($row) {
                return $row->exemption_name ?? 'N/A';
            })
            // 🔹 Type column (Registration/Exemption)
            ->addColumn('application_type', function ($row) {
                if ($row->application_type == 1) {
                    return 'Registration';
                } elseif ($row->application_type == 2) {
                    return 'Exemption';
                }
                return 'N/A';
            })
            ->addColumn('service_master_pk', function ($row) {
                return $row->service_short_name ?? 'N/A';
            })
            ->addColumn('group_service_name', function ($row) {
                return $row->group_service_name ?? 'N/A';
            })
            ->addColumn('generated_OT_code', function ($row) {
                return $row->generated_OT_code ?? 'N/A';
            })
            ->addColumn('cadre_master_pk', function ($row) {
                return $row->cadre_name ?? 'N/A';
            })
            ->addColumn('schema_id', function ($row) {
                return $row->schema_id ?? 'N/A';
            })
            ->addColumn('contact_no', function ($row) {
                return $row->contact_no ?? 'N/A';
            })
            ->addColumn('dob', function ($row) {
                return $row->dob ?? 'N/A';
            })
            ->addColumn('display_name', function ($row) {
                return $row->display_name ?? 'N/A';
            })
            ->addColumn('first_name', function ($row) {
                return $row->first_name ?? 'N/A';
            })
            ->addColumn('middle_name', function ($row) {
                return $row->middle_name ?? 'N/A';
            })
            ->addColumn('last_name', function ($row) {
                return $row->last_name ?? 'N/A';
            })
            ->addColumn('email', function ($row) {
                return $row->email ?? 'N/A';
            })
            ->addColumn('rank', function ($row) {
                return $row->rank ?? 'N/A';
            })
            ->addColumn('web_auth', function ($row) {
                return $row->web_auth ?? 'N/A';
            })
            ->addColumn('exam_year', function ($row) {
                return $row->exam_year ?? 'N/A';
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
            ->addColumn('email_count', function ($row) {
                return (int) $row->email_count; // make sure JS can read it as number
            })

            ->addColumn('status', function ($row) {
                $checked = $row->active_inactive == 1 ? 'checked' : '';
                return '<div class="form-check form-switch d-inline-block ms-2">
                <input class="form-check-input status-toggle" 
                    type="checkbox" role="switch"
                    data-table="fc_registration_master" 
                    data-column="active_inactive" 
                    data-id="' . $row->pk . '" 
                    ' . $checked . '>
            </div>';
            })

            ->rawColumns(['service_master_pk', 'schema_id', 'contact_no', 'dob', 'display_name', 'first_name', 'middle_name', 'last_name', 'email', 'rank', 'web_auth', 'exam_year', 'status', 'action', 'email_count']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\FcRegistrationMasterListDaTable $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    // public function query(FcRegistrationMaster $model): QueryBuilder
    // {
    //     return $model->select('pk', 'email', 'contact_no', 'display_name', 'schema_id', 'first_name', 'middle_name', 'last_name', 'rank', 'exam_year', 'service_master_pk', 'web_auth', 'dob')->newQuery();
    // }


    public function query(FcRegistrationMaster $model): \Illuminate\Database\Eloquent\Builder
    {
        $query = $model->newQuery()
            ->leftJoin('service_master as s', 'fc_registration_master.service_master_pk', '=', 's.pk')
            ->leftJoin('fc_exemption_master as e', 'fc_registration_master.fc_exemption_master_pk', '=', 'e.Pk')
            ->leftJoin('cadre_master as c', 'fc_registration_master.cadre_master_pk', '=', 'c.pk')
            ->select(
                'fc_registration_master.*',
                's.service_short_name',
                'e.Exemption_name as exemption_name',
                'c.cadre_name as cadre_name',
                's.group_service_name as group_type', // <-- alias here
                DB::raw('(SELECT COUNT(*) FROM fc_registration_master f2 WHERE f2.email = fc_registration_master.email) as email_count')

            );

        // Apply DataTable filters
        if ($course = request('course_name')) {
            $query->where('fc_registration_master.formid', $course);
        }

        if ($exemption = request('exemption_category')) {
            $query->where('e.Exemption_name', $exemption);
        }

        if ($type = request('application_type')) {
            $query->where('fc_registration_master.application_type', $type);
        }

        if ($service = request('service_master')) {
            $query->where('fc_registration_master.service_master_pk', $service);
        }
        if ($year = request('year')) {
            $query->where('fc_registration_master.exam_year', $year);
        }
        if ($group = request('group_type')) {
            if ($group === 'NULL') {
                $query->whereNull('s.group_service_name');
            } else {
                $query->where('s.group_service_name', $group);
            }
        }

        //  Show only duplicates (if button clicked)
        if (request('show_duplicates') == '1') {
            $query->havingRaw('email_count > 1');
        }
        return $query;
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
            ->minifiedAjax() // keep empty
            ->selectStyleSingle()
            ->parameters([
                'responsive' => true,
                'scrollX' => true,
                'autoWidth' => false,
                'order' => [],
                'rowCallback' => "function(row, data) {
                if (parseInt(data.email_count) > 1) {
                    $(row).css('background-color', '#ffe6e6');
                } else {
                    $(row).css('background-color', '');
                }
            }",
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
            Column::make('course_name')->title('Course Name')->searchable(true)->orderable(false),
            Column::make('exemption_name')->title('Exemption Category')->searchable(true)->orderable(false),
            Column::make('application_type')->title('Application Type')->searchable(true)->orderable(false),
            Column::make('service_master_pk')->title('Service')->orderable(false)->searchable(false),
            Column::make('group_type')->title('Group Type')->orderable(false)->searchable(false),
            Column::make('cadre_master_pk')->title('Cadre')->orderable(false)->searchable(false),
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
            Column::make('generated_OT_code')->title('Generated OT Code')->searchable(false)->orderable(false),
            Column::make('exam_year')->title('Exam Year')->searchable(true)->orderable(false),
            Column::computed('status')->title('Status')->searchable(false)->orderable(false)->addClass('text-center'),
            Column::make('email_count')->visible(false),
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
