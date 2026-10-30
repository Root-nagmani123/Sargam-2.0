<?php

namespace App\DataTables;

use App\DataTables\Concerns\RendersEstateRowActions;
use App\Models\EstateOtherRequest;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class EstateOtherRequestDataTable extends DataTable
{
    use RendersEstateRowActions;

    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->editColumn('request_no_oth', function ($row) {
                $val = trim((string) ($row->request_no_oth ?? ''));

                return $val !== '' ? '<span title="' . e($val) . '">' . e($val) . '</span>' : '—';
            })
            // Name & ID: name, with the linked employee id (employee_master_emp_id) beneath.
            ->editColumn('emp_name', fn ($row) => self::nameWithId($row->emp_name, $row->employee_master_emp_id ?? null))
            ->editColumn('section', fn ($row) => self::plainOrDash($row->section))
            ->editColumn('doj_acad', fn ($row) => self::dateOrDash($row->doj_acad))
            ->addColumn('actions', function ($row) {
                $deleteUrl = route('admin.estate.other-estate-request.destroy', ['id' => $row->pk]);
                $doj = $row->doj_acad ? $row->doj_acad->format('Y-m-d') : '';

                $attrs = [
                    'data-id' => (int) $row->pk,
                    'data-employee_name' => e($row->emp_name ?? ''),
                    'data-father_name' => e($row->f_name ?? ''),
                    'data-section' => e($row->section ?? ''),
                    'data-doj_academy' => $doj,
                    'data-employee_master_emp_id' => e($row->employee_master_emp_id ?? ''),
                ];
                $dataAttrs = implode(' ', array_map(fn ($k, $v) => $k . '="' . $v . '"', array_keys($attrs), $attrs));

                return '<div class="rfe-actions" role="group" aria-label="Row actions">'
                    . self::actionLink('edit', 'Edit', 'edit', [
                        'class' => 'btn-edit-other-request',
                        'title' => 'Edit',
                        'attrs' => $dataAttrs,
                    ])
                    . self::actionLink('delete', 'Delete', 'delete', [
                        'class' => 'btn-delete-other-request',
                        'title' => 'Delete',
                        'attrs' => 'data-url="' . e($deleteUrl) . '"',
                    ])
                    . '</div>';
            })
            ->rawColumns(['request_no_oth', 'emp_name', 'actions'])
            ->filter(function ($query) {
                static::applyFilters(
                    $query,
                    (string) request()->input('search.value', ''),
                    (string) request()->input('bill_year_filter', ''),
                    (string) request()->input('bill_date_filter', '')
                );
            }, false)
            ->orderColumn('DT_RowIndex', 'estate_other_req.pk $1')
            ->orderColumn('request_no_oth', fn ($query, $order) => $query->reorder()->orderByRaw('LOWER(COALESCE(estate_other_req.request_no_oth, "")) ' . $order))
            ->orderColumn('emp_name', fn ($query, $order) => $query->reorder()->orderByRaw('LOWER(COALESCE(estate_other_req.emp_name, "")) ' . $order))
            ->orderColumn('section', fn ($query, $order) => $query->reorder()->orderByRaw('LOWER(COALESCE(estate_other_req.section, "")) ' . $order))
            ->orderColumn('doj_acad', fn ($query, $order) => $query->reorder()
                ->orderBy('estate_other_req.doj_acad', $order)
                ->orderBy('estate_other_req.pk', $order))
            ->setRowId('pk');
    }

    public function query(EstateOtherRequest $model): QueryBuilder
    {
        return static::listingQuery($model);
    }

    /**
     * The listing query, shared with the Download / Print exports so what a user
     * downloads is exactly the list they were looking at.
     */
    public static function listingQuery(?EstateOtherRequest $model = null): QueryBuilder
    {
        $model = $model ?: new EstateOtherRequest();

        return $model->newQuery()
            ->select([
                'estate_other_req.pk',
                'estate_other_req.request_no_oth',
                'estate_other_req.emp_name',
                'estate_other_req.f_name',
                // Links an Other request to employee_master.emp_id (My Estate Bill, scope=self).
                // Not every database has the column yet, so fall back to a NULL of the same name.
                static::hasEmpIdColumn()
                    ? 'estate_other_req.employee_master_emp_id'
                    : DB::raw('NULL as employee_master_emp_id'),
                'estate_other_req.section',
                'estate_other_req.doj_acad',
            ])
            ->orderByDesc('estate_other_req.pk');
    }

    private static ?bool $hasEmpIdColumn = null;

    public static function hasEmpIdColumn(): bool
    {
        return static::$hasEmpIdColumn ??= Schema::hasColumn('estate_other_req', 'employee_master_emp_id');
    }

    /**
     * Toolbar filters + free-text search, in one place so the grid and the
     * exports can never disagree about which rows are in scope.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     */
    public static function applyFilters($query, string $searchValue, string $billYear = '', string $billDate = ''): void
    {
        // Bill filters keep a request when it has a bill (via its possession) that matches:
        // Bill Year = bill_year, Bill Date = a bill period from_date..to_date covering that day.
        $billYear = trim($billYear);
        $billDate = trim($billDate);
        $hasYear = preg_match('/^\d{4}$/', $billYear) === 1;
        $hasDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $billDate) === 1;
        if ($hasYear || $hasDate) {
            $query->whereExists(function ($q) use ($hasYear, $billYear, $hasDate, $billDate) {
                $q->selectRaw('1')
                    ->from('estate_possession_other as epo')
                    ->join('estate_month_reading_details_other as emr', 'emr.estate_possession_other_pk', '=', 'epo.pk')
                    ->whereColumn('epo.estate_other_req_pk', 'estate_other_req.pk')
                    ->when($hasYear, fn ($q) => $q->where('emr.bill_year', $billYear))
                    ->when($hasDate, fn ($q) => $q->whereDate('emr.from_date', '<=', $billDate)
                        ->whereDate('emr.to_date', '>=', $billDate));
            });
        }

        $searchValue = trim($searchValue);
        if ($searchValue === '') {
            return;
        }

        $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $searchValue) . '%';
        $query->where(function ($q) use ($like) {
            $q->where('estate_other_req.request_no_oth', 'like', $like)
                ->orWhere('estate_other_req.emp_name', 'like', $like)
                ->orWhere('estate_other_req.f_name', 'like', $like)
                ->orWhere('estate_other_req.section', 'like', $like);
            if (static::hasEmpIdColumn()) {
                $q->orWhere('estate_other_req.employee_master_emp_id', 'like', $like);
            }
        });
    }

    /**
     * Bill years on record for Others' bills, newest first — the toolbar's Bill Year options.
     *
     * @return string[]
     */
    public static function billYearOptions(): array
    {
        return DB::table('estate_month_reading_details_other')
            ->whereNotNull('bill_year')
            ->distinct()
            ->orderByDesc('bill_year')
            ->pluck('bill_year')
            ->map(fn ($year) => trim((string) $year))
            ->filter(fn ($year) => preg_match('/^\d{4}$/', $year) === 1)
            ->unique()
            ->values()
            ->all();
    }

    /** Cell text, or the shared muted dash when the column is empty. */
    public static function plainOrDash(?string $value): string
    {
        $value = trim((string) $value);

        return $value === '' || $value === '-' ? '—' : $value;
    }

    /** d-m-Y, or the shared muted dash. */
    public static function dateOrDash($value): string
    {
        if (empty($value)) {
            return '—';
        }

        return \Carbon\Carbon::parse($value)->format('d-m-Y');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('estateRequestTable')
            // programme-dt chrome (docs/new-design-index-page.md) — no `dom` and no
            // `language` here on purpose: datatable-global-ui.js owns both, and a
            // page-level override would win and break the "Showing N of M items" footer.
            ->addTableClass('table table-hover align-middle mb-0 w-100 programme-dt-table')
            ->columns($this->getColumns())
            ->minifiedAjax('', null, [
                'bill_year_filter' => '$("#eorBillYearFilter").val()',
                'bill_date_filter' => '$("#eorBillDateFilter").val()',
            ])
            ->parameters([
                'responsive' => false,
                'autoWidth' => false,
                'ordering' => true,
                // Keep DataTables' native (server-side) ordering so a header click
                // re-sorts the WHOLE list instead of just the loaded page.
                'sargamServerOrder' => true,
                'searching' => true,
                'lengthChange' => true,
                'pageLength' => 10,
                'order' => [[0, 'desc']],
                'lengthMenu' => [[10, 25, 50, 100, 200], [10, 25, 50, 100, 200]],
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S. No.')->orderable(true)->searchable(false)->width('64px'),
            Column::make('request_no_oth')->title('Request ID')->addClass('eor-col-req')->orderable(true)->searchable(true),
            Column::make('emp_name')->title('Name & ID')->addClass('rfe-col-name')->orderable(true)->searchable(true),
            // Father Name is captured on the form but filled on ~10% of the rows, so it
            // stays in the Add / Edit modal and out of the grid — it is still searchable.
            Column::make('section')->title('Section')->addClass('eor-col-section')->orderable(true)->searchable(true),
            Column::make('doj_acad')->title('DOJ in Academy')->addClass('eor-col-doj')->orderable(true)->searchable(false),
            // Only Edit + Delete here — see .eor-page .rfe-col-action.
            Column::computed('actions')->title('Action')->addClass('rfe-col-action')->orderable(false)->searchable(false)->width('120px'),
        ];
    }

    protected function filename(): string
    {
        return 'EstateOtherRequest_' . date('YmdHis');
    }
}
