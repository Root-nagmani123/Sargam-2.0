<?php

namespace App\DataTables\Master;

use App\Http\Controllers\Admin\Master\OfficeBearerController;
use App\Services\ClubSociety\OfficeBearerService;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\QueryDataTable;
use Yajra\DataTables\Services\DataTable;

/**
 * Officer Bearers — read-only, so there is no Action column. Ranking happens in
 * SQL (a window function inside OfficeBearerService), which keeps server-side
 * pagination honest instead of materialising every elected row in PHP.
 */
class OfficeBearerDataTable extends DataTable
{
    public function dataTable($query): QueryDataTable
    {
        return (new QueryDataTable($query))
            ->addIndexColumn()
            ->editColumn('role_name', fn ($row) => e($row->role_name ?? '-'))
            ->editColumn('ot_code', fn ($row) => e($row->ot_code ?: '-'))
            ->addColumn('officer_bearer', function ($row) {
                $name = trim((string) $row->officer_bearer_name);

                $initials = collect(preg_split('/\s+/', $name))
                    ->filter()->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('');
                $initials = $initials !== '' ? e($initials) : '?';

                // photoUrl() returns null when the file is not on disk, so a
                // missing photo renders initials instead of a guaranteed 404.
                $photoUrl = OfficeBearerService::photoUrl($row->photo_path ?? null);

                $avatar = '<span class="ob-avatar ob-avatar--initials' . ($photoUrl ? ' d-none' : '') . '" aria-hidden="true">'
                    . $initials . '</span>';

                if ($photoUrl) {
                    // Belt and braces: the file could vanish between the check
                    // and the browser's request.
                    $onError = "this.classList.add('d-none'); var f=this.previousElementSibling; if(f){f.classList.remove('d-none');}";
                    $avatar .= '<img src="' . e($photoUrl) . '" alt="" class="ob-avatar" loading="lazy"'
                        . ' onerror="' . e($onError) . '">';
                }

                return '<div class="d-flex align-items-center gap-2">' . $avatar
                    . '<span>' . ($name !== '' ? e($name) : '-') . '</span></div>';
            })
            // Every visible column is a derived-table column, so filter on the
            // outer alias rather than the underlying tables.
            ->filterColumn('role_name', function ($query, $keyword) {
                $query->where('ob.role_name', 'like', "%{$keyword}%");
            })
            ->filterColumn('officer_bearer', function ($query, $keyword) {
                $query->where('ob.officer_bearer_name', 'like', "%{$keyword}%");
            })
            ->filterColumn('ot_code', function ($query, $keyword) {
                $query->where('ob.ot_code', 'like', "%{$keyword}%");
            })
            ->rawColumns(['officer_bearer']);
    }

    /**
     * @return QueryBuilder
     */
    public function query()
    {
        return OfficeBearerController::baseQuery(
            request('status_filter', 'active'),
            request('course_master_pk'),
            request('club_society_master_pk')
        );
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('officebearer-table')
            ->columns($this->getColumns())
            // Raw JS MUST go through minifiedAjax()'s $script argument — the
            // $data array quotes its values, which would send the literal source
            // text as the filter and silently match nothing.
            ->minifiedAjax('', <<<'JS'
            data.status_filter = window.obStatusFilter ? window.obStatusFilter() : 'active';
            data.course_master_pk = window.obCourseFilter ? window.obCourseFilter() : '';
            data.club_society_master_pk = window.obClubFilter ? window.obClubFilter() : '';
JS)
            ->selectStyleSingle()
            ->responsive(true)
            ->parameters([
                'responsive'   => true,
                'scrollX'      => false,
                'autoWidth'    => false,
                'ordering'     => false,
                'searching'    => true,
                'lengthChange' => true,
                'pageLength'   => 10,
                'lengthMenu'   => [[10, 25, 50, 100, 200], [10, 25, 50, 100, 200]],
                'order'        => [],
                'language'     => [
                    'search'            => '',
                    'searchPlaceholder' => 'Search',
                    'paginate'          => ['previous' => '‹', 'next' => '›'],
                    'lengthMenu'        => 'Showing _MENU_',
                    'info'              => 'of _TOTAL_ items',
                    'infoEmpty'         => 'of 0 items',
                    'infoFiltered'      => 'of _MAX_ items',
                ],
            ]);
        // No ->buttons([...]) — Button::make('reset')/('reload') are not real
        // button types and break the global enhancer's init.dt hook.
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S No.')->searchable(false)->orderable(false),
            Column::make('role_name')->title('Role')->orderable(false),
            Column::computed('officer_bearer')->title('Officer Bearer Name')->orderable(false),
            Column::make('ot_code')->title('OT Code')->orderable(false),
        ];
    }

    protected function filename(): string
    {
        return 'OfficerBearers_' . date('YmdHis');
    }
}
