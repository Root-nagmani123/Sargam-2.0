<?php

namespace App\DataTables\FC;

use App\Http\Controllers\FC\StepReportController;
use App\Models\FC\FcForm;
use App\Services\FC\FcStepReport;
use App\Support\FC\FcUploadUrl;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

/**
 * Server-side table for any {@see FcStepReport} — Vision Statement, Special Assistant, and
 * whatever step-shaped report comes next.
 *
 * Server-side by construction: only the requested page is read, so a 600-trainee course costs
 * the same as a 10-trainee one — which matters more here than on most reports, because these
 * steps carry free-text fields of up to 1,500 characters per row.
 */
class FcStepReportDataTable extends DataTable
{
    public function __construct(
        protected FcStepReport $report,
        protected ?FcForm $form = null
    ) {
    }

    public function query()
    {
        // No course selected → an empty, correctly-shaped result set rather than a scan of
        // every trainee in the academy.
        if (! $this->form) {
            return DB::table('student_master_firsts')
                ->whereRaw('1 = 0')
                ->selectRaw('NULL as login_username');
        }

        // SELECT only what the browser is showing, but FILTER on everything — a hidden column
        // must still constrain the rows. Filter predicates name real columns rather than the
        // SELECT aliases, so narrowing cannot break them.
        $query = $this->report->build($this->form, $this->report->visibleColumns(request()));
        $this->report->applyFilters($query, request());

        return $query;
    }

    public function dataTable($query)
    {
        $dt = datatables()->query($query)->addIndexColumn();

        // The report's own search closure already ran in query(); Yajra's global search would
        // emit invalid SQL against these aliased expressions.
        //
        // DO NOT put predicates in this closure — the filtered count is taken from the same
        // builder, so anything added here would be invisible to it and recordsFiltered would
        // report the wrong total. Filters belong in query().
        $dt->filter(function ($q) {
            // no-op: search is applied in query() via applyFilters()
        }, false);

        // ORDER BY has to name the real column/expression, not the SELECT alias.
        if ($this->form) {
            foreach ($this->report->columns() as $key => $column) {
                $sql = $this->report->orderSql($key, $this->form);
                if ($sql !== null) {
                    $dt->orderColumn($key, $sql.' $1');
                }
            }
        }

        // ?? on every renderer, not just ?: — a column hidden in the Columns menu is not in the
        // SELECT at all, so the property is absent rather than null and ?: alone would fatal.
        $dt->editColumn('login_username', fn ($row) => '<code style="font-size:11px">'.e(($row->login_username ?? '') ?: '—').'</code>');

        $raw = ['login_username'];

        foreach ($this->report->columns() as $key => $column) {
            if ($key === 'login_username') {
                continue;
            }

            if (! empty($column['file'])) {
                $dt->editColumn($key, fn ($row) => $this->fileLink($row->{$key} ?? null, $column['label']));
                $raw[] = $key;
                continue;
            }

            if (! empty($column['long'])) {
                $dt->editColumn($key, fn ($row) => $this->longCell($row->{$key} ?? null));
                $raw[] = $key;
                continue;
            }

            $dt->editColumn($key, fn ($row) => e(trim((string) ($row->{$key} ?? '')) !== '' ? $row->{$key} : '—'));
        }

        $dt->rawColumns($raw);

        return $dt;
    }

    /**
     * Prose, truncated on screen only — the full text is one click away, and every export
     * carries it whole. A 1,500-character cell makes the grid unreadable.
     */
    private function longCell($value): string
    {
        $text = trim((string) ($value ?? ''));
        if ($text === '') {
            return '<span class="badge bg-light text-secondary border">Not submitted</span>';
        }

        $short = Str::limit($text, FcStepReport::TABLE_PREVIEW_CHARS);

        return '<div class="vs-cell">'
            .'<span class="vs-short">'.e($short).'</span>'
            .($short !== $text
                ? '<span class="vs-full d-none">'.e($text).'</span>'
                   .' <button type="button" class="btn btn-link btn-sm p-0 align-baseline vs-more">more</button>'
                : '')
            .'</div>';
    }

    /**
     * Uploads open through the shared opaque-token route: nothing about the file is in the URL,
     * so there is no user id to read and no directory to walk.
     */
    private function fileLink(?string $path, string $label): string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return '<span class="text-muted">—</span>';
        }

        return '<a href="'.e(FcUploadUrl::for($path, StepReportController::FILE_PATH)).'" target="_blank" rel="noopener" '
            .'class="btn btn-outline-primary py-0 px-2" style="font-size:11px;" '
            .'title="'.e($label).'"><i class="bi bi-box-arrow-up-right me-1"></i>View</a>';
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('stepReportTable')
            ->addTableClass('table table-bordered table-striped table-hover align-middle mb-0')
            ->columns($this->getColumns());
    }

    public function getColumns(): array
    {
        $columns = [
            Column::computed('DT_RowIndex')->title('S.No.')->addClass('text-center')
                ->orderable(false)->searchable(false)->width('50px'),
        ];

        foreach ($this->report->columns() as $key => $column) {
            $columns[] = Column::make($key)
                ->title($column['label'])
                ->orderable((bool) ($column['orderable'] ?? false))
                ->searchable(false);
        }

        return $columns;
    }

    protected function filename(): string
    {
        return Str::studly($this->report->key()).'Report_'.date('YmdHis');
    }
}
