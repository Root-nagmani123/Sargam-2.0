<?php

namespace App\Http\Controllers\Admin\IssueManagement;

use App\Exports\IssueEscalationMatrixExport;
use App\Http\Controllers\Concerns\NormalisesDataTablesRequest;
use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\{
    IssueCategoryMaster,
    IssueCategoryEmployeeMap,
    EmployeeMaster,
};
use App\Support\DataTableRedisCache;
use App\Support\ExportCsvHeader;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\{DB, Auth};

class IssueEscalationMatrixController extends Controller
{
    use NormalisesDataTablesRequest;

    private const LISTING_CACHE_EPOCH_KEY = 'admin_issue_escalation_matrix_index_list_epoch';

    public static function bumpEscalationMatrixListCacheEpoch(): void
    {
        DataTableRedisCache::bumpListEpoch(self::LISTING_CACHE_EPOCH_KEY, 'IssueEscalationMatrixController@index');
    }

    /**
     * Build matrix + categories (one categories query + one maps query — no per-category N+1).
     *
     * @return array{matrix: array<int, array{category: IssueCategoryMaster, level1: IssueCategoryEmployeeMap|null, level2: IssueCategoryEmployeeMap|null, level3: IssueCategoryEmployeeMap|null}>, categories: Collection<int, IssueCategoryMaster>}
     */
    private function buildMatrixData(): array
    {
        $categories = IssueCategoryMaster::active()->orderBy('issue_category')->get();
        $ids = $categories->pluck('pk')->all();
        if ($ids === []) {
            return ['matrix' => [], 'categories' => $categories];
        }

        $allLevels = IssueCategoryEmployeeMap::query()
            ->whereIn('issue_category_master_pk', $ids)
            ->orderBy('priority')
            ->with('employee')
            ->get()
            ->groupBy('issue_category_master_pk');

        $matrix = [];
        foreach ($categories as $category) {
            $levels = $allLevels->get($category->pk, collect());
            $matrix[] = [
                'category' => $category,
                'level1' => $levels->firstWhere('priority', 1),
                'level2' => $levels->firstWhere('priority', 2),
                'level3' => $levels->firstWhere('priority', 3),
            ];
        }

        return ['matrix' => $matrix, 'categories' => $categories];
    }

    /**
     * @param  array<int, array{category: IssueCategoryMaster, level1: IssueCategoryEmployeeMap|null, level2: IssueCategoryEmployeeMap|null, level3: IssueCategoryEmployeeMap|null}>  $matrix
     */
    private function matrixToCacheArray(array $matrix, Collection $categories): array
    {
        $serializeLevel = function (?IssueCategoryEmployeeMap $level): ?array {
            if ($level === null) {
                return null;
            }
            $emp = $level->relationLoaded('employee') ? $level->getRelation('employee') : $level->employee;
            $empAttrs = $emp instanceof EmployeeMaster ? $emp->getAttributes() : null;

            return [
                'map' => $level->getAttributes(),
                'employee' => $empAttrs,
            ];
        };

        $rows = [];
        foreach ($matrix as $row) {
            $rows[] = [
                'category' => $row['category']->getAttributes(),
                'level1' => $serializeLevel($row['level1'] ?? null),
                'level2' => $serializeLevel($row['level2'] ?? null),
                'level3' => $serializeLevel($row['level3'] ?? null),
            ];
        }

        return [
            'matrix_rows' => $rows,
            'categories' => $categories->map(fn (IssueCategoryMaster $c) => $c->getAttributes())->values()->all(),
        ];
    }

    /**
     * @return array{matrix: array<int, array<string, mixed>>, categories: Collection<int, IssueCategoryMaster>}
     */
    private function matrixFromCacheArray(array $cached): array
    {
        if (! isset($cached['matrix_rows'], $cached['categories']) || ! is_array($cached['matrix_rows']) || ! is_array($cached['categories'])) {
            $built = $this->buildMatrixData();

            return ['matrix' => $built['matrix'], 'categories' => $built['categories']];
        }

        $categories = IssueCategoryMaster::hydrate($cached['categories']);
        $byCategoryPk = $categories->keyBy('pk');

        $hydrateLevel = function (?array $data): ?IssueCategoryEmployeeMap {
            if ($data === null || ! isset($data['map']) || ! is_array($data['map'])) {
                return null;
            }
            $map = new IssueCategoryEmployeeMap;
            $map->setRawAttributes($data['map']);
            $map->syncOriginal();
            if (! empty($data['employee']) && is_array($data['employee'])) {
                $emp = new EmployeeMaster;
                $emp->setRawAttributes($data['employee']);
                $emp->syncOriginal();
                $map->setRelation('employee', $emp);
            }

            return $map;
        };

        $matrix = [];
        foreach ($cached['matrix_rows'] as $row) {
            if (! isset($row['category']) || ! is_array($row['category'])) {
                continue;
            }
            $pk = $row['category']['pk'] ?? null;
            $category = $pk !== null ? $byCategoryPk->get($pk) : null;
            if ($category === null) {
                $category = IssueCategoryMaster::hydrate([$row['category']])->first();
            }
            if ($category === null) {
                continue;
            }
            $matrix[] = [
                'category' => $category,
                'level1' => $hydrateLevel($row['level1'] ?? null),
                'level2' => $hydrateLevel($row['level2'] ?? null),
                'level3' => $hydrateLevel($row['level3'] ?? null),
            ];
        }

        return ['matrix' => $matrix, 'categories' => $categories];
    }

    /**
     * Display escalation matrix - categories with 3-level hierarchy (employees + days).
     */
    /**
     * Page sizes the grid feed accepts — this list MUST mirror the lengthMenu
     * datatable-global-ui.js installs, or picking one it offers falls back to
     * INDEX_PER_PAGE and the footer says 25 while 10 rows are shown.
     */
    private const DATA_PER_PAGE_OPTIONS = [10, 25, 50, 100, 200];

    private const INDEX_PER_PAGE = 10;

    /** Sortable grid headers — anything else falls back to Complaint Category. */
    private const SORTABLE_COLUMNS = ['category', 'level1', 'level2', 'level3'];

    /**
     * A level cell as the grid and the reports render it: "Trevor Swanson - 1 Day".
     *
     * Empty string when the level is unset — callers add their own placeholder
     * ('-' in the reports, an em dash on screen).
     */
    private function levelCellText($level): string
    {
        if (! $level) {
            return '';
        }

        $days = (int) $level->days_notify;

        return trim(($level->employee->name ?? 'N/A') . ' - ' . $days . ' ' . ($days === 1 ? 'Day' : 'Days'));
    }

    /**
     * The matrix is assembled in memory (one row per category), so search and
     * sort run over the built collection rather than as SQL.
     *
     * @param  array<int, array<string, mixed>>  $matrix
     * @return array<int, array<string, mixed>>
     */
    private function filterAndSortMatrix(array $matrix, string $search, string $sortKey, string $sortDir): array
    {
        $name = fn ($level) => $level?->employee?->name ?? '';

        if ($search !== '') {
            $needle = mb_strtolower($search);
            // Search the level cells as the grid renders them ("Name - 3 Days"),
            // not just the employee name: the grid searches in the browser while
            // the export runs through here, and a term like "Days" must not
            // return rows on screen and nothing in the download.
            $matrix = array_values(array_filter($matrix, function ($row) use ($needle) {
                $haystack = mb_strtolower(implode(' ', [
                    $row['category']->issue_category ?? '',
                    $this->levelCellText($row['level1']),
                    $this->levelCellText($row['level2']),
                    $this->levelCellText($row['level3']),
                ]));

                return str_contains($haystack, $needle);
            }));
        }

        $value = function (array $row) use ($sortKey, $name) {
            return match ($sortKey) {
                'level1' => mb_strtolower($name($row['level1'])),
                'level2' => mb_strtolower($name($row['level2'])),
                'level3' => mb_strtolower($name($row['level3'])),
                default => mb_strtolower($row['category']->issue_category ?? ''),
            };
        };

        usort($matrix, fn ($a, $b) => $sortDir === 'desc'
            ? $value($b) <=> $value($a)
            : $value($a) <=> $value($b));

        return $matrix;
    }

    /**
     * The whole matrix (cached), hydrated back into models.
     *
     * @return array{matrix: array<int, array<string, mixed>>, categories: Collection<int, IssueCategoryMaster>}
     */
    private function cachedMatrix(): array
    {
        $epoch = DataTableRedisCache::readListEpoch(self::LISTING_CACHE_EPOCH_KEY);
        $cacheKey = 'admin_issue_escalation_matrix:v1:' . md5(json_encode(['epoch' => $epoch]));

        $cached = DataTableRedisCache::remember(
            $cacheKey,
            [
                'enabled' => 'ISSUE_ESCALATION_MATRIX_CACHE_ENABLED',
                'seconds' => 'ISSUE_ESCALATION_MATRIX_CACHE_SECONDS',
            ],
            'IssueEscalationMatrixController@index',
            function () {
                $built = $this->buildMatrixData();

                return $this->matrixToCacheArray($built['matrix'], $built['categories']);
            }
        );

        if (! is_array($cached) || ! isset($cached['matrix_rows'], $cached['categories'])) {
            $built = $this->buildMatrixData();
            $cached = $this->matrixToCacheArray($built['matrix'], $built['categories']);
        }

        return $this->matrixFromCacheArray($cached);
    }

    /**
     * Display the escalation matrix.
     *
     * Rows come from data() over ajax; this action renders the shell plus the
     * category / employee lists the Add and Edit modals need.
     */
    public function index(Request $request)
    {
        $hydrated = $this->cachedMatrix();
        $categories = $hydrated['categories'];
        $employees = $this->getEmployeesForDropdown();

        return view('admin.issue_management.escalation_matrix.index', compact('categories', 'employees'));
    }

    /**
     * DataTables server-side feed for the Escalation Matrix grid.
     *
     * Unlike the other Centcom grids this one has no single query behind it —
     * a row is a category joined to its three levels, assembled in memory — so
     * search, sort and the page slice run over the built (cached) matrix. Only
     * the page on screen is serialised and sent.
     */
    public function data(Request $request)
    {
        $paging = $this->normaliseDataTablesRequest($request, self::INDEX_PER_PAGE, self::DATA_PER_PAGE_OPTIONS);

        $search = trim((string) $request->query('q', ''));
        $sortKey = (string) $request->query('sort', 'category');
        if (! in_array($sortKey, self::SORTABLE_COLUMNS, true)) {
            $sortKey = 'category';
        }
        $sortDir = strtolower((string) $request->query('dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $hydrated = $this->cachedMatrix();
        $total = count($hydrated['matrix']);
        $filtered = $this->filterAndSortMatrix($hydrated['matrix'], $search, $sortKey, $sortDir);

        $page = array_slice($filtered, $paging['start'], $paging['perPage']);

        $rows = [];
        foreach ($page as $i => $row) {
            $rows[] = [
                'sno' => $paging['start'] + $i + 1,
                'category' => e((string) ($row['category']->issue_category ?? '')),
                'level1' => $this->levelCellHtml($row['level1'], 1),
                'level2' => $this->levelCellHtml($row['level2'], 2),
                'level3' => $this->levelCellHtml($row['level3'], 3),
                'action' => view('admin.issue_management.escalation_matrix._row_actions', compact('row'))->render(),
            ];
        }

        return response()->json([
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $total,
            'recordsFiltered' => count($filtered),
            'data' => $rows,
        ]);
    }

    /**
     * A level cell as the grid paints it: "Trevor Swanson - 1 Day", days tinted
     * by level. The reports use levelCellText() for the same content in plain text.
     */
    private function levelCellHtml($level, int $n): string
    {
        if (! $level) {
            return '<span class="text-muted">—</span>';
        }

        $days = (int) $level->days_notify;

        return '<span class="ic-level ic-level--' . $n . '">'
            . e($level->employee->name ?? 'N/A')
            . ' - <span class="ic-level__days">' . $days . ' ' . ($days === 1 ? 'Day' : 'Days') . '</span>'
            . '</span>';
    }

    /**
     * Download / print the escalation matrix (same filter + order as the grid).
     */
    /**
     * Canonical export columns, in order. Keys must match EM_EXPORT_COLUMN_KEYS
     * in escalation_matrix/index.blade.php.
     *
     * Rows reaching these resolvers are the flat arrays built in export(), keyed
     * by the same slugs — this grid has no single model per row.
     */
    private function exportColumnDefs(): array
    {
        return [
            'sno' => [
                'heading' => 'S. No.',
                'class' => 'col-sno',
                'value' => fn (array $row) => $row['sno'],
            ],
            'category' => [
                'heading' => 'Complaint Category',
                'class' => 'col-category',
                'value' => fn (array $row) => $row['category'],
            ],
            'level1' => [
                'heading' => 'Level 1 (Escalation Days)',
                'class' => 'col-level',
                'value' => fn (array $row) => $row['level1'],
            ],
            'level2' => [
                'heading' => 'Level 2 (Escalation Days)',
                'class' => 'col-level',
                'value' => fn (array $row) => $row['level2'],
            ],
            'level3' => [
                'heading' => 'Level 3 (Escalation Days)',
                'class' => 'col-level',
                'value' => fn (array $row) => $row['level3'],
            ],
        ];
    }

    /**
     * Intersect ?cols= against the canonical list so a hand-edited value cannot
     * reorder the report or inject a column. Empty => every column.
     */
    private function resolveExportColumns(Request $request): array
    {
        $defs = $this->exportColumnDefs();
        $wanted = array_filter(array_map('trim', explode(',', (string) $request->query('cols', ''))));

        if ($wanted === []) {
            return $defs;
        }

        $keys = array_values(array_intersect(array_keys($defs), $wanted));

        return $keys === [] ? $defs : array_intersect_key($defs, array_flip($keys));
    }

    public function export(Request $request, string $format = 'csv')
    {
        $format = strtolower($format);
        abort_unless(in_array($format, ['csv', 'excel', 'pdf', 'print'], true), 404);

        $built = $this->buildMatrixData();
        $search = trim((string) $request->query('q', ''));
        $sortKey = (string) $request->query('sort', 'category');
        if (! in_array($sortKey, ['category', 'level1', 'level2', 'level3'], true)) {
            $sortKey = 'category';
        }
        $sortDir = strtolower((string) $request->query('dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $rows = $this->filterAndSortMatrix($built['matrix'], $search, $sortKey, $sortDir);

        $columns = $this->resolveExportColumns($request);
        $header = array_values(array_map(fn ($col) => $col['heading'], $columns));
        $exportDate = now()->format('d-m-Y h:i A');
        $stamp = now()->format('YmdHis');

        // Same text the grid shows and the search matches on.
        $cell = fn ($level) => $this->levelCellText($level) ?: '-';

        // Keyed by column slug so a hidden column drops cleanly from every format.
        $lines = collect();
        foreach ($rows as $i => $row) {
            $lines->push([
                'sno' => $i + 1,
                'category' => $row['category']->issue_category ?? '',
                'level1' => $cell($row['level1']),
                'level2' => $cell($row['level2']),
                'level3' => $cell($row['level3']),
            ]);
        }

        if ($format === 'print') {
            return view('admin.issue_management.escalation_matrix.export_print', [
                'columns' => $columns,
                'header' => $header,
                'rows' => $lines,
                'search' => $search,
                'exportDate' => $exportDate,
            ]);
        }

        if ($format === 'excel') {
            return Excel::download(
                new IssueEscalationMatrixExport($lines, $columns, $exportDate, $search),
                'EscalationMatrix_' . $stamp . '.xlsx'
            );
        }

        if ($format === 'pdf') {
            return Pdf::loadView('admin.issue_management.escalation_matrix.export_pdf', [
                'columns' => $columns,
                'rows' => $lines,
                'search' => $search,
                'exportDate' => $exportDate,
            ])
                ->setPaper('a4', 'landscape')
                ->setOptions([
                    'defaultFont' => 'DejaVu Sans',
                    'isHtml5ParserEnabled' => true,
                    'isPhpEnabled' => true,
                ])
                ->download('EscalationMatrix_' . $stamp . '.pdf');
        }

        $filename = 'EscalationMatrix_' . $stamp . '.csv';

        // Same band the .xlsx and the print/PDF headers carry, so the CSV names
        // the applied filters too.
        $csvBand = ExportCsvHeader::rows(
            'Escalation Matrix',
            $search !== '' ? 'Search: ' . $search : null,
            $exportDate,
            $lines->count()
        );

        return response()->streamDownload(function () use ($columns, $header, $lines, $csvBand) {
            $handle = fopen('php://output', 'w');
            // BOM so Excel opens the UTF-8 file with the right encoding.
            fwrite($handle, "\xEF\xBB\xBF");
            foreach ($csvBand as $bandRow) {
                fputcsv($handle, $bandRow);
            }
            fputcsv($handle, $header);
            foreach ($lines as $line) {
                fputcsv($handle, array_values(array_map(fn ($col) => $col['value']($line), $columns)));
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Store escalation matrix for a category (3 levels).
     * Use only for categories that do not have hierarchy yet. For existing hierarchy, use update().
     */
    public function store(Request $request)
    {
        $empRule = ['required', 'integer', function ($attr, $v, $fail) {
            if (! EmployeeMaster::findByIdOrPkOld($v)) {
                $fail('The selected employee is invalid.');
            }
        }];
        $request->validate([
            'issue_category_master_pk' => 'required|exists:issue_category_master,pk',
            'level1_employee_pk' => $empRule,
            'level1_days' => 'required|integer|min:0',
            'level2_employee_pk' => $empRule,
            'level2_days' => 'required|integer|min:0',
            'level3_employee_pk' => $empRule,
            'level3_days' => 'required|integer|min:0',
        ]);

        $categoryId = $request->issue_category_master_pk;

        // POST (Add): do not allow insert if category already has hierarchy — ask to use Edit
        // PUT (Update): allow; we will delete existing and re-insert below
        if ($request->isMethod('post') && IssueCategoryEmployeeMap::where('issue_category_master_pk', $categoryId)->exists()) {
            $category = IssueCategoryMaster::find($categoryId);
            $categoryName = $category ? $category->issue_category : $categoryId;

            return redirect()->route('admin.issue-escalation-matrix.index')
                ->with('error', 'This category ("' . $categoryName . '") already has escalation hierarchy configured. Please use Edit to update.');
        }

        DB::beginTransaction();
        try {
            // Update path or fresh insert: remove existing mappings so we can insert 3 levels
            IssueCategoryEmployeeMap::where('issue_category_master_pk', $categoryId)->delete();

            // Insert 3 levels
            $levels = [
                ['employee_pk' => $request->level1_employee_pk, 'days' => $request->level1_days, 'priority' => 1],
                ['employee_pk' => $request->level2_employee_pk, 'days' => $request->level2_days, 'priority' => 2],
                ['employee_pk' => $request->level3_employee_pk, 'days' => $request->level3_days, 'priority' => 3],
            ];

            foreach ($levels as $level) {
                IssueCategoryEmployeeMap::create([
                    'issue_category_master_pk' => $categoryId,
                    'employee_master_pk' => $level['employee_pk'],
                    'days_notify' => $level['days'],
                    'priority' => $level['priority'],
                    'created_by' => Auth::id(),
                    'created_date' => now(),
                ]);
            }

            DB::commit();
            static::bumpEscalationMatrixListCacheEpoch();

            return redirect()->route('admin.issue-escalation-matrix.index')
                ->with('success', 'Escalation matrix saved successfully.');
        // \Throwable, not \Exception: a PHP Error would otherwise skip this
        // handler and leave the transaction open holding locks.
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withInput()->with('error', 'Failed to save: ' . $e->getMessage());
        }
    }

    /**
     * Update escalation matrix for a category.
     */
    public function update(Request $request, $categoryId)
    {
        $request->merge(['issue_category_master_pk' => $categoryId]);

        return $this->store($request);
    }

    private function getEmployeesForDropdown()
    {
        return DB::table('employee_master as e')
            ->select(
                'e.pk as employee_pk',
                DB::raw("TRIM(CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.middle_name, ''), ' ', COALESCE(e.last_name, ''))) as employee_name")
            )
            ->orderBy('e.first_name')
            ->get();
    }
}
