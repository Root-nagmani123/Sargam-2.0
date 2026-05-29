<?php

namespace App\DataTables\FC;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class FcImportedRosterDataTable extends DataTable
{
    public function dataTable($query)
    {
        return datatables()
            ->query($query)
            ->addColumn('student_name', function ($row) {
                $parts = array_filter([
                    $row->first_name ?? '',
                    $row->middle_name ?? '',
                    $row->last_name ?? '',
                ]);

                return e($parts ? strtoupper(implode(' ', $parts)) : ($row->display_name ?? '—'));
            })
            ->addColumn('roster_status', function ($row) {
                if ($row->uc_pk) {
                    return '<span class="badge bg-success">Migrated</span>';
                }
                if ((int) ($row->is_registered ?? 0) === 1
                    && trim((string) ($row->user_id ?? '')) !== ''
                    && trim((string) ($row->password ?? '')) !== '') {
                    return '<span class="badge bg-primary">Ready to migrate</span>';
                }
                if ((int) ($row->is_registered ?? 0) === 1) {
                    return '<span class="badge bg-info text-dark">Forms complete</span>';
                }
                if (trim((string) ($row->user_id ?? '')) !== '') {
                    return '<span class="badge bg-warning text-dark">Credentials staged</span>';
                }

                return '<span class="badge bg-secondary">Imported</span>';
            })
            ->filterColumn('student_name', function ($query, $keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('r.first_name', 'like', "%{$keyword}%")
                        ->orWhere('r.middle_name', 'like', "%{$keyword}%")
                        ->orWhere('r.last_name', 'like', "%{$keyword}%")
                        ->orWhere('r.display_name', 'like', "%{$keyword}%");
                });
            })
            ->editColumn('course_name', function ($row) {
                return $this->formatNameWithShort($row->course_name ?? null, $row->course_short_name ?? null);
            })
            ->editColumn('service_name', function ($row) {
                return $this->formatNameWithShort($row->service_name ?? null, $row->service_short_name ?? null);
            })
            ->filterColumn('course_name', function ($query, $keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('c.course_name', 'like', "%{$keyword}%")
                        ->orWhere('c.couse_short_name', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('service_name', function ($query, $keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('s.service_name', 'like', "%{$keyword}%")
                        ->orWhere('s.service_short_name', 'like', "%{$keyword}%");
                });
            })
            ->rawColumns(['roster_status']);
    }

    public function query(): Builder
    {
        $query = DB::table('fc_registration_master as r')
            ->leftJoin('service_master as s', 'r.service_master_pk', '=', 's.pk')
            ->leftJoin('course_master as c', 'r.course_master_pk', '=', 'c.pk')
            ->leftJoin('user_credentials as uc', 'uc.user_name', '=', 'r.user_id')
            ->select([
                'r.pk',
                'r.user_id',
                'r.email',
                'r.contact_no',
                'r.first_name',
                'r.middle_name',
                'r.last_name',
                'r.display_name',
                'r.rank',
                'r.exam_year',
                'r.is_registered',
                'r.generated_OT_code',
                'r.course_master_pk',
                'r.service_master_pk',
                'r.password',
                's.service_name',
                's.service_short_name',
                'c.course_name',
                'c.couse_short_name as course_short_name',
                'uc.pk as uc_pk',
            ]);

        if ($course = request('filter_course')) {
            $query->where('r.course_master_pk', (int) $course);
        }

        if ($services = request('filter_services')) {
            $ids = is_array($services) ? $services : explode(',', (string) $services);
            $ids = array_filter(array_map('intval', $ids));
            if ($ids !== []) {
                $query->whereIn('r.service_master_pk', $ids);
            }
        }

        if ($search = trim((string) request('filter_search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('r.user_id', 'like', "%{$search}%")
                    ->orWhere('r.email', 'like', "%{$search}%")
                    ->orWhere('r.contact_no', 'like', "%{$search}%")
                    ->orWhere('r.first_name', 'like', "%{$search}%")
                    ->orWhere('r.last_name', 'like', "%{$search}%")
                    ->orWhere('r.generated_OT_code', 'like', "%{$search}%")
                    ->orWhere('c.course_name', 'like', "%{$search}%")
                    ->orWhere('c.couse_short_name', 'like', "%{$search}%")
                    ->orWhere('s.service_name', 'like', "%{$search}%")
                    ->orWhere('s.service_short_name', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    public function html(): HtmlBuilder
    {
        $ajaxData = <<<'JS'
function (d) {
    d.filter_course = document.getElementById('filter_course')?.value || '';
    d.filter_services = $('#filter_services').val() || [];
    d.filter_search = document.getElementById('filter_search')?.value || '';
}
JS;

        return $this->builder()
            ->setTableId('fcImportedRosterTable')
            ->addTableClass('table table-striped table-hover table-sm align-middle w-100')
            ->columns($this->getColumns())
            ->ajax([
                'url' => route('students.imported.index'),
                'type' => 'GET',
                'data' => $ajaxData,
            ])
            ->parameters([
                'responsive' => false,
                'autoWidth' => false,
                'scrollX' => true,
                'processing' => true,
                'serverSide' => true,
                'pageLength' => 25,
                'lengthMenu' => [[10, 25, 50, 100], [10, 25, 50, 100]],
                'order' => [[0, 'desc']],
                'searching' => true,
                'dom' => "<'row mb-2'<'col-sm-6'l><'col-sm-6'f>>".
                    "<'row'<'col-sm-12'tr>>".
                    "<'row mt-2'<'col-sm-5'i><'col-sm-7'p>>",
                'language' => [
                    'search' => 'Table search:',
                    'searchPlaceholder' => 'Name, email, mobile, username…',
                    'info' => 'Showing _START_ to _END_ of _TOTAL_ record(s)',
                    'infoEmpty' => 'No records',
                    'zeroRecords' => 'No matching records',
                ],
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::make('pk')->title('Roster PK')->addClass('text-center')->width('90px'),
            Column::computed('student_name')->title('Student Name')->orderable(false)->searchable(true),
            Column::make('user_id')->title('Username')->defaultContent('—'),
            Column::make('generated_OT_code')->title('OT Code')->defaultContent('—'),
            Column::make('course_name')->title('Course')->defaultContent('—')->searchable(true),
            Column::make('service_name')->title('Service')->defaultContent('—')->searchable(true),
            Column::make('email')->title('Email')->defaultContent('—'),
            Column::make('contact_no')->title('Mobile')->defaultContent('—'),
            Column::make('exam_year')->title('Exam Year')->defaultContent('—'),
            Column::computed('roster_status')->title('Status')->orderable(false)->searchable(false),
        ];
    }

    protected function filename(): string
    {
        return 'FcImportedRoster_'.date('YmdHis');
    }

    private function formatNameWithShort(?string $name, ?string $short): string
    {
        $name = trim((string) ($name ?? ''));
        $short = trim((string) ($short ?? ''));
        if ($name === '') {
            return '—';
        }

        return $short !== '' ? e($name).' ('.e($short).')' : e($name);
    }
}
