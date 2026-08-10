<?php

namespace App\Http\Controllers\Admin\IssueManagement;

use App\Exports\IssueCategoryExport;
use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\{
    IssueCategoryMaster,
    IssueSubCategoryMaster
};
use App\Support\DataTableRedisCache;
use App\Support\DataTableSearchHelper;
use App\Support\ExportCsvHeader;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class IssueCategoryController extends Controller
{
    private const LISTING_CACHE_EPOCH_KEY = 'admin_issue_categories_index_list_epoch';

    public static function bumpIndexListCacheEpoch(): void
    {
        DataTableRedisCache::bumpListEpoch(self::LISTING_CACHE_EPOCH_KEY, 'IssueCategoryController@index');
    }

    /**
     * Normalise the ?per_page= value against the whitelist (falls back to the default).
     */
    private function resolvePerPage(Request $request): int
    {
        $perPage = (int) $request->query('per_page', self::INDEX_PER_PAGE);

        return in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : self::INDEX_PER_PAGE;
    }

    private function resolveSearch(Request $request): string
    {
        return trim((string) $request->query('q', ''));
    }

    /**
     * Whitelisted sort keys → the expression the query orders by.
     *
     * @return array{key: string, dir: string}
     */
    private function resolveSort(Request $request): array
    {
        $key = (string) $request->query('sort', 'category');
        if (! array_key_exists($key, self::SORTABLE_COLUMNS)) {
            $key = 'category';
        }

        $dir = strtolower((string) $request->query('dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        return ['key' => $key, 'dir' => $dir];
    }

    private function indexFilteredQuery(string $search = '', string $sortKey = 'category', string $sortDir = 'asc', bool $withSubCategoryCount = false): Builder
    {
        $sortColumn = self::SORTABLE_COLUMNS[$sortKey] ?? self::SORTABLE_COLUMNS['category'];
        $sortDir = $sortDir === 'desc' ? 'desc' : 'asc';

        // pk tiebreaker — issue_category unique nahi hai, warna snapshot pagination me
        // rows pages ke beech duplicate/miss ho sakte hain.
        return IssueCategoryMaster::query()
            ->when($search !== '', function (Builder $query) use ($search) {
                $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $search) . '%';
                $query->where(function (Builder $inner) use ($like, $search) {
                    $inner->where('issue_category', 'like', $like)
                        ->orWhere('description', 'like', $like);

                    // The grid searches in the browser, so it also matches the
                    // rendered Status pill. The export runs this query instead —
                    // match the pill here too, or a searched export comes back
                    // empty while the screen shows rows.
                    $statuses = DataTableSearchHelper::statusPillMatches($search);
                    if ($statuses !== []) {
                        $inner->orWhereIn('status', $statuses);
                    }
                });
            })
            // The sub-category count lives in a subselect, so it needs withCount() to be orderable.
            // Guarded by a single when() so the alias can never be added twice.
            ->when(
                $withSubCategoryCount || $sortColumn === 'sub_categories_count',
                fn (Builder $query) => $query->withCount('subCategories')
            )
            ->orderBy($sortColumn, $sortDir)
            ->orderBy('pk');
    }

    /**
     * Display a listing of issue categories.
     *
     * Rows come from data() over ajax (server-side paging), so this action only
     * renders the shell.
     */
    public function index()
    {
        return view('admin.issue_management.categories.index');
    }

    /**
     * DataTables server-side feed for the Manage Categories grid.
     *
     * withCount() rather than loading each category's children: the grid only shows
     * the number, and the subselect is what makes that column sortable in SQL.
     */
    public function data(Request $request)
    {
        // Only the columns the grid renders (G1) — this payload goes to the browser.
        $query = IssueCategoryMaster::query()
            ->select(['pk', 'issue_category', 'description', 'status'])
            ->withCount('subCategories');

        /* Only order here when DataTables sent none. An ORDER BY baked into the
           query is applied FIRST and silently outranks the one Yajra appends, so
           the user's column sort would never take effect. */
        if (! $request->filled('order')) {
            $query->orderBy('issue_category')->orderBy('pk');   // pk: name is not unique
        }

        return DataTables::of($query)
            // Serial follows the page the server returned; no client renumbering.
            ->addIndexColumn()
            ->addColumn('category_name', fn (IssueCategoryMaster $row) => (string) $row->issue_category)
            ->addColumn('description', fn (IssueCategoryMaster $row) => \Illuminate\Support\Str::limit(
                (string) ($row->description ?: 'No description'), 50
            ))
            ->addColumn('sub_categories', fn (IssueCategoryMaster $row) => (string) (int) $row->sub_categories_count)
            ->addColumn('status', fn (IssueCategoryMaster $row) => view(
                'admin.issue_management.categories._row_status',
                ['category' => $row]
            )->render())
            ->addColumn('action', fn (IssueCategoryMaster $row) => view(
                'admin.issue_management.categories._row_actions',
                ['category' => $row]
            )->render())
            ->filterColumn('category_name', fn ($q, $keyword) => $q->where('issue_category', 'like', "%{$keyword}%"))
            ->filterColumn('description', fn ($q, $keyword) => $q->where('description', 'like', "%{$keyword}%"))
            ->orderColumn('category_name', 'issue_category $1')
            ->orderColumn('description', 'description $1')
            ->orderColumn('sub_categories', 'sub_categories_count $1')
            ->orderColumn('status', 'status $1')
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    /**
     * Every category pk in display order — the full set backing the client-side grid.
     */
    private function indexAllPks(): array
    {
        return $this->indexFilteredQuery()
            ->pluck('pk')
            ->map(fn ($pk) => (int) $pk)
            ->values()
            ->all();
    }

    /**
     * Download / print the full (filtered) category list.
     *
     * Both formats share the same header + columns as the index grid.
     */
    /**
     * Canonical export columns, in display order.
     *
     * Keyed so the grid can ask for a subset with ?cols= — the keys must match
     * IC_EXPORT_COLUMN_KEYS in categories/index.blade.php. 'sno' is not a data
     * column; it only drives the running serial.
     */
    private function exportColumnDefs(): array
    {
        return [
            'sno' => [
                'heading' => 'S. No.',
                'class' => 'col-sno',
                'value' => fn ($row, int $index) => $index + 1,
            ],
            'category' => [
                'heading' => 'Category',
                'class' => 'col-category',
                'value' => fn ($row) => $row->issue_category,
            ],
            'description' => [
                'heading' => 'Description',
                'class' => 'col-desc',
                'value' => fn ($row) => $row->description ?: '-',
            ],
            'sub_categories' => [
                'heading' => 'Sub-Categories',
                'class' => 'col-sub',
                'value' => fn ($row) => (int) $row->sub_categories_count,
            ],
            'status' => [
                'heading' => 'Status',
                'class' => 'col-status',
                'value' => fn ($row) => ((int) $row->status === 1) ? 'Active' : 'Inactive',
            ],
        ];
    }

    /**
     * Which columns the export should carry.
     *
     * Intersects the request against the canonical list rather than trusting it,
     * so a hand-edited ?cols= can't reorder the report or inject a column. Empty
     * or absent => every column.
     */
    private function resolveExportColumns(Request $request): array
    {
        $defs = $this->exportColumnDefs();
        $wanted = array_filter(array_map('trim', explode(',', (string) $request->query('cols', ''))));

        if ($wanted === []) {
            return $defs;
        }

        $keys = array_values(array_intersect(array_keys($defs), $wanted));

        // Every column hidden would produce an empty file — fall back to all.
        return $keys === [] ? $defs : array_intersect_key($defs, array_flip($keys));
    }

    public function export(Request $request, string $format = 'csv')
    {
        $format = strtolower($format);
        abort_unless(in_array($format, ['csv', 'excel', 'pdf', 'print'], true), 404);

        $search = $this->resolveSearch($request);
        $sort = $this->resolveSort($request);
        $rows = $this->indexFilteredQuery($search, $sort['key'], $sort['dir'], true)->get();

        $columns = $this->resolveExportColumns($request);
        $header = array_values(array_map(fn ($col) => $col['heading'], $columns));
        $exportDate = now()->format('d-m-Y h:i A');
        $stamp = now()->format('YmdHis');

        if ($format === 'print') {
            return view('admin.issue_management.categories.export_print', [
                'columns' => $columns,
                'header' => $header,
                'rows' => $rows,
                'search' => $search,
                'exportDate' => $exportDate,
            ]);
        }

        if ($format === 'excel') {
            return Excel::download(
                new IssueCategoryExport($rows, $columns, $exportDate, $search),
                'ManageCategories_' . $stamp . '.xlsx'
            );
        }

        if ($format === 'pdf') {
            return Pdf::loadView('admin.issue_management.categories.export_pdf', [
                'columns' => $columns,
                'rows' => $rows,
                'search' => $search,
                'exportDate' => $exportDate,
            ])
                ->setPaper('a4', 'portrait')
                ->setOptions([
                    'defaultFont' => 'DejaVu Sans',
                    'isHtml5ParserEnabled' => true,
                    // The page-number script in the view needs this.
                    'isPhpEnabled' => true,
                ])
                ->download('ManageCategories_' . $stamp . '.pdf');
        }

        $filename = 'ManageCategories_' . $stamp . '.csv';

        // Same band the .xlsx and the print/PDF headers carry, so the CSV names
        // the applied filters too.
        $csvBand = ExportCsvHeader::rows(
            'Manage Categories',
            $search !== '' ? 'Search: ' . $search : null,
            $exportDate,
            $rows->count()
        );

        return response()->streamDownload(function () use ($columns, $header, $rows, $csvBand) {
            $handle = fopen('php://output', 'w');
            // BOM so Excel opens the UTF-8 file with the right encoding.
            fwrite($handle, "\xEF\xBB\xBF");
            foreach ($csvBand as $bandRow) {
                fputcsv($handle, $bandRow);
            }
            fputcsv($handle, $header);

            foreach ($rows as $index => $row) {
                fputcsv($handle, array_values(array_map(
                    fn ($col) => $col['value']($row, $index),
                    $columns
                )));
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Store a newly created category in storage.
     */
    public function store(Request $request)
    {
        // Handle multiple categories if submitted
        if ($request->has('categories') && is_array($request->categories)) {
            $request->validate([
                'categories.*.issue_category' => 'required|string|max:255',
                'categories.*.description' => 'nullable|string',
            ]);

            $userId = Auth::user()->user_id ?? Auth::id();
            $createdCount = 0;

            foreach ($request->categories as $categoryData) {
                if (!empty($categoryData['issue_category'])) {
                    IssueCategoryMaster::create([
                        'issue_category' => $categoryData['issue_category'],
                        'description' => $categoryData['description'] ?? null,
                        'created_by' => $userId,
                        'status' => 1,
                    ]);
                    $createdCount++;
                }
            }

            $message = $createdCount > 1
                ? "$createdCount categories created successfully."
                : 'Category created successfully.';

            static::bumpIndexListCacheEpoch();

            return redirect()->route('admin.issue-categories.index')
                ->with('success', $message);
        }

        // Handle single category (backward compatibility)
        $request->validate([
            'issue_category' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $userId = Auth::user()->user_id ?? Auth::id();
        IssueCategoryMaster::create([
            'issue_category' => $request->issue_category,
            'description' => $request->description,
            'created_by' => $userId,
            'status' => 1,
        ]);

        static::bumpIndexListCacheEpoch();

        return redirect()->route('admin.issue-categories.index')
            ->with('success', 'Category created successfully.');
    }

    /**
     * Update the specified category in storage.
     */
    public function update(Request $request, $id)
    {
        $category = IssueCategoryMaster::findOrFail($id);

        $request->validate([
            'issue_category' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:0,1',
        ]);

        $userId = Auth::user()->user_id ?? Auth::id();
        $category->update([
            'issue_category' => $request->issue_category,
            'description' => $request->description,
            'status' => $request->status,
            'modified_by' => $userId,
        ]);

        static::bumpIndexListCacheEpoch();

        return redirect()->route('admin.issue-categories.index')
            ->with('success', 'Category updated successfully.');
    }

    /**
     * Remove the specified category from storage.
     */
    public function destroy($id)
    {
        $category = IssueCategoryMaster::findOrFail($id);

        if ($category->status == 1) {
            return back()->with('error', 'Cannot delete an active category. Please set it to Inactive first.');
        }

        if ($category->issueLogs()->count() > 0) {
            return back()->with('error', 'Cannot delete category with associated issues.');
        }

        $category->delete();

        static::bumpIndexListCacheEpoch();

        return redirect()->route('admin.issue-categories.index')
            ->with('success', 'Category deleted successfully.');
    }
}
