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

    /** @var array<string,array<string,mixed>>|null */
    protected ?array $selected = null;

    /** @return array<string,array<string,mixed>> */
    public function fields(): array
    {
        return $this->fields;
    }

    /**
     * The columns the browser is actually showing.
     *
     * The report offers ~100 columns and the Columns menu hides most of them in practice.
     * Selecting a column the admin cannot see costs a lookup JOIN on the page query and, for
     * a repeating section, a whole extra query — so the browser sends its visible set and the
     * SELECT is built from that, exactly as the Excel/CSV/PDF exports already do.
     *
     * FILTERS still run against the full set: a column the admin filtered on and then hid must
     * keep constraining the rows. Filter predicates name s1/s2/s3 columns, which scopedBase()
     * joins regardless of what is selected.
     *
     * Absent parameter (first draw, or a client that does not send it) means "everything".
     *
     * The column being SORTED is always included, even when hidden. dataTable() registers an
     * ORDER BY for every field naming its `lk_<key>` lookup alias, and that alias only exists
     * if the column is selected — so sorting a column and then hiding it (the sort survives the
     * redraw) produced "Unknown column 'lk_<key>.<label>' in 'order clause'". Selecting one
     * extra column is cheaper than reproducing Yajra's order resolution here.
     *
     * @return array<string,array<string,mixed>>
     */
    protected function selectedFields(): array
    {
        if ($this->selected !== null) {
            return $this->selected;
        }

        // input(), NOT query(): the table POSTs its draw request (see the route comment on
        // why — ~99 columns is a 25 KB query string and a 414), so `cols` arrives in the
        // BODY. query() reads the query string alone, so it always came back empty and this
        // whole narrowing silently fell through to "select everything" on every draw.
        $cols = trim((string) request()->input('cols', ''));
        if ($cols === '') {
            return $this->selected = $this->fields;
        }

        $keys = array_filter(array_map('trim', explode(',', $cols)));

        foreach ($this->orderedColumnKeys() as $ordered) {
            $keys[] = $ordered;
        }

        $visible = array_intersect_key($this->fields, array_flip($keys));

        // Nothing recognised at all — a stale `cols` from a course that has since been
        // switched, or a hand-made request. Fall back to the whole report rather than draw a
        // table of nothing but S.No. and Username, matching what the export path already does
        // (DescriptiveDataReportController::selection()). Only reachable now that `cols` is
        // actually read: while it was being looked for in the query string it was always
        // empty, so this branch could never be hit.
        return $this->selected = $visible !== [] ? $visible : $this->fields;
    }

    /**
     * Field keys named by the request's ORDER BY, resolved through the DataTables
     * `order[i][column]` → `columns[n][data|name]` indirection the client actually sends.
     *
     * @return list<string>
     */
    protected function orderedColumnKeys(): array
    {
        $request = request();
        $columns = (array) $request->input('columns', []);
        $out = [];

        foreach ((array) $request->input('order', []) as $order) {
            $index = $order['column'] ?? null;
            if ($index === null || ! isset($columns[$index])) {
                continue;
            }

            $key = (string) ($columns[$index]['data'] ?? $columns[$index]['name'] ?? '');
            if ($key !== '' && isset($this->fields[$key])) {
                $out[] = $key;
            }
        }

        return $out;
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
        $query = $service->build($this->form, $this->selectedFields());
        $service->applyFilters($query, $this->fields, request());

        return $query;
    }

    public function dataTable($query)
    {
        // Own subclass rather than datatables()->query(): it hooks results() so the repeating
        // sections are batch-loaded once per page (see FcChildHydratingQueryDataTable).
        $dt = (new FcChildHydratingQueryDataTable($query))
            ->fcHydrateChildren($this->selectedFields())
            ->addIndexColumn();

        if ($this->form) {
            // Count without the lookup joins — they are LEFT JOINs on unique keys, so they
            // cannot change the count, and Yajra would otherwise carry all ~30 of them into
            // a query whose only output is a number.
            $form = $this->form;
            $fields = $this->fields;
            $dt->fcCountQueryUsing(function () use ($form, $fields) {
                $service = app(FcDescriptiveDataQuery::class);
                $count = $service->scopedBase($form)->selectRaw('1 as dt_row_count');
                $service->applyFilters($count, $fields, request());

                return $count;
            });
        }

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
        //
        // DO NOT put predicates in this closure. FcChildHydratingQueryDataTable::prepareCountQuery()
        // counts off a freshly built scopedBase() + applyFilters(), NOT off $this->query — so any
        // filtering added here would be invisible to the count and recordsFiltered would silently
        // report the unfiltered total. Filters belong in query(), where both paths see them.
        $dt->filter(function ($q) {
            // no-op: search is applied in query() via applyFilters()
        }, false);

        // Order hints: the aliases are expressions, so ORDER BY must name the real column.
        $service = app(FcDescriptiveDataQuery::class);
        foreach ($this->fields as $key => $field) {
            // 'child' columns are not in the SQL at all — there is nothing to ORDER BY.
            if (in_array($field['type'], ['concat', 'address', 'file', 'child'], true)) {
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
            if (in_array($field['type'], ['concat', 'address', 'file', 'child'], true)) {
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
