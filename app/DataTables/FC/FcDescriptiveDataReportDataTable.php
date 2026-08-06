<?php

namespace App\DataTables\FC;

use App\Models\FC\FcForm;
use App\Services\FC\FcDescriptiveDataFieldResolver;
use App\Services\FC\FcDescriptiveDataQuery;
use App\Support\FC\FcUploadUrl;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

/**
 * Server-side (AJAX) Descriptive Data report — the fields the Descriptive Roll PDF prints,
 * as a filterable table with Excel/PDF export.
 *
 * Server-side by construction (G2): only the requested page is ever read, so a 1000-trainee
 * course costs the same as a 10-trainee one. The columns are resolved per course, because
 * the FC schema is form-driven — see FcDescriptiveDataFieldResolver.
 */
class FcDescriptiveDataReportDataTable extends DataTable
{
    protected ?FcForm $form;

    /** @var array<string,array<string,mixed>> */
    protected array $fields = [];

    public function __construct(?FcForm $form = null)
    {
        $this->form = $form;
        $this->fields = $form
            ? app(FcDescriptiveDataFieldResolver::class)->forForm($form)
            : [];
    }

    /** @return array<string,array<string,mixed>> */
    public function fields(): array
    {
        return $this->fields;
    }

    public function query()
    {
        // No course selected → an empty, correctly-shaped result set rather than a scan of
        // every trainee in the academy.
        if (! $this->form) {
            return DB::table('student_master_firsts')
                ->whereRaw('1 = 0')
                ->selectRaw('NULL as login_username, NULL as route_user_id');
        }

        $service = app(FcDescriptiveDataQuery::class);
        $query = $service->build($this->form, $this->fields);
        $service->applyFilters($query, $this->fields, request());

        return $query;
    }

    public function dataTable($query)
    {
        $dt = datatables()->query($query)->addIndexColumn();

        $dt->editColumn('login_username', fn ($row) => '<code style="font-size:11px">'.e($row->login_username ?? '—').'</code>');

        foreach ($this->fields as $key => $field) {
            if ($field['type'] === 'file') {
                // Uploads render as a link to the stored file (see the report's own notes on
                // why this is a public URL).
                $dt->editColumn($key, fn ($row) => $this->fileLink($row->{$key} ?? null, $field['label']));
                continue;
            }

            if ($field['type'] === 'date') {
                $dt->editColumn($key, fn ($row) => $this->formatDate($row->{$key} ?? null));
                continue;
            }

            $dt->editColumn($key, fn ($row) => e(trim((string) ($row->{$key} ?? '')) !== '' ? $row->{$key} : '—'));
        }

        // Yajra's own global search would emit invalid SQL against these aliases; the report's
        // search closure in FcDescriptiveDataQuery is already applied in query().
        $dt->filter(function ($q) {
            // no-op: search is applied in query() via applyFilters()
        }, false);

        // Order hints: the aliases are expressions, so ORDER BY must name the real column.
        $service = app(FcDescriptiveDataQuery::class);
        foreach ($this->fields as $key => $field) {
            if (in_array($field['type'], ['concat', 'address', 'file'], true)) {
                continue;
            }

            if ($field['type'] === 'derived') {
                // Service sorts by the resolved NAME, not the id — sorting by id would put
                // the services in an order that means nothing to a reader.
                $column = $field['derived'] === 'service'
                    ? $service->serviceNameSql($field)
                    : $field['alias'].'.'.$field['columns'][0];
                $dt->orderColumn($key, $column.' $1');
                continue;
            }

            $column = isset($field['lookup'])
                ? 'lk_'.$key.'.'.$field['lookup']['label']
                : $field['alias'].'.'.$field['columns'][0];
            $dt->orderColumn($key, $column.' $1');
        }

        $raw = ['login_username'];
        foreach ($this->fields as $key => $field) {
            if ($field['type'] === 'file') {
                $raw[] = $key;
            }
        }
        $dt->rawColumns($raw);

        return $dt;
    }

    /**
     * Public URL to the stored upload, opened in a new tab. FcUploadUrl absolutises against
     * the host actually serving the page, so the link does not point at APP_URL's host when
     * that differs from the one the admin is on.
     */
    private function fileLink(?string $path, string $label): string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return '<span class="text-muted">—</span>';
        }

        return '<a href="'.e(FcUploadUrl::for($path)).'" target="_blank" rel="noopener" '
            .'class="btn btn-outline-primary py-0 px-2" style="font-size:11px;" '
            .'title="'.e($label).'"><i class="bi bi-box-arrow-up-right me-1"></i>View</a>';
    }

    /** d-m-Y for display; the underlying column stays a DATE so filters can use a range. */
    private function formatDate($value): string
    {
        $value = trim((string) $value);
        if ($value === '' || str_starts_with($value, '0000')) {
            return '—';
        }

        try {
            return \Carbon\Carbon::parse($value)->format('d-m-Y');
        } catch (\Throwable $e) {
            return e($value);
        }
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('descriptiveDataTable')
            ->addTableClass('table table-bordered table-striped table-hover text-nowrap align-middle mb-0')
            ->columns($this->getColumns());
    }

    public function getColumns(): array
    {
        $columns = [
            Column::computed('DT_RowIndex')->title('S.No.')->addClass('text-center')
                ->orderable(false)->searchable(false)->width('50px'),
            Column::make('login_username')->title('Username')->orderable(false),
        ];

        foreach ($this->fields as $key => $field) {
            $col = Column::make($key)->title($field['label']);
            if (in_array($field['type'], ['concat', 'address', 'file'], true)) {
                $col->orderable(false);
            }
            $col->searchable(false);
            $columns[] = $col;
        }

        return $columns;
    }

    protected function filename(): string
    {
        return 'DescriptiveDataReport_'.date('YmdHis');
    }
}
