<?php

namespace App\DataTables\FC;

use App\Services\FC\FcMigrateStudentsExportService;
use Illuminate\Database\Query\Builder;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class FcMigrateStudentsDataTable extends DataTable
{
    public function dataTable($query)
    {
        return datatables()
            ->query($query)
            ->addColumn('select', function ($row) {
                return '<div class="migrate-select-cell">'
                    .'<input type="checkbox" class="form-check-input migrate-row-checkbox" '
                    .'value="'.(int) $row->pk.'" checked aria-label="Select record">'
                    .'</div>';
            })
            ->addColumn('student_name', function ($row) {
                $parts = array_filter([
                    $row->first_name ?? '',
                    $row->middle_name ?? '',
                    $row->last_name ?? '',
                ]);

                return e($parts ? strtoupper(implode(' ', $parts)) : ($row->display_name ?? '—'));
            })
            ->addColumn('migration_status', function ($row) {
                return '<span class="badge bg-success">Ready to migrate</span>';
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
            ->rawColumns(['select', 'migration_status']);
    }

    public function query(): Builder
    {
        return app(FcMigrateStudentsExportService::class)->eligibleQuery(request());
    }

    public function html(): HtmlBuilder
    {
        $ajaxData = <<<'JS'
function (d) {
    var courseEl = document.getElementById('filter_course');
    var courseVal = '';
    if (courseEl) {
        if (courseEl._choicesBs && typeof courseEl._choicesBs.getValue === 'function') {
            var picked = courseEl._choicesBs.getValue(true);
            if (picked !== null && picked !== undefined && picked !== '') {
                courseVal = String(picked);
            }
        } else {
            courseVal = courseEl.value || '';
        }
    }
    d.course_filter = courseVal;
    d.filter_services = $('#filter_services').val() || [];
    d.filter_search = document.getElementById('filter_search')?.value || '';
}
JS;

        return $this->builder()
            ->setTableId('fcMigrateStudentsTable')
            ->addTableClass('table table-striped table-hover table-sm align-middle w-100')
            ->columns($this->getColumns())
            ->ajax([
                'url' => route('students.index'),
                'type' => 'GET',
                'data' => $ajaxData,
            ])
            ->parameters([
                'responsive' => false,
                'autoWidth' => false,
                'scrollX' => true,
                'processing' => true,
                'serverSide' => true,
                'deferLoading' => 1,
                'pageLength' => 25,
                'lengthMenu' => [[10, 25, 50, 100], [10, 25, 50, 100]],
                'order' => [[2, 'asc']],
                'searching' => true,
                'columnDefs' => [
                    ['orderable' => false, 'searchable' => false, 'targets' => 0],
                ],
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
            Column::computed('select')
                ->title(
                    '<div class="migrate-select-cell">'
                    .'<input type="checkbox" id="migrateSelectAll" class="form-check-input js-migrate-select-all migrate-header-checkbox" '
                    .'title="Select all on this page" aria-label="Select all on this page">'
                    .'</div>'
                )
                ->exportable(false)
                ->printable(false)
                ->orderable(false)
                ->searchable(false)
                ->addClass('text-center migrate-select-th migrate-select-td')
                ->width('48px'),
            Column::make('pk')->title('Student PK')->addClass('text-center')->width('90px'),
            Column::computed('student_name')->title('Student Name')->orderable(false)->searchable(true),
            Column::make('user_id')->title('Username'),
            Column::make('generated_OT_code')->title('OT Code')->defaultContent('—'),
            Column::make('course_name')->title('Course')->defaultContent('—')->searchable(true),
            Column::make('service_name')->title('Service')->defaultContent('—')->searchable(true),
            Column::make('email')->title('Email'),
            Column::make('contact_no')->title('Mobile'),
            Column::make('exam_year')->title('Exam Year')->defaultContent('—'),
            Column::computed('migration_status')->title('Status')->orderable(false)->searchable(false),
        ];
    }

    protected function filename(): string
    {
        return 'FcMigrateStudents_'.date('YmdHis');
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
