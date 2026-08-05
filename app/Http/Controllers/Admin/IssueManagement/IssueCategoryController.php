<?php

namespace App\Http\Controllers\Admin\IssueManagement;

use App\Http\Controllers\Controller;
use App\Models\{
    IssueCategoryMaster,
    IssueSubCategoryMaster
};
use App\Support\DataTableRedisCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;

class IssueCategoryController extends Controller
{
    private const LISTING_CACHE_EPOCH_KEY = 'admin_issue_categories_index_list_epoch';

    private const INDEX_PER_PAGE = 10;

    /** Page-size choices offered by the "Showing N of M items" footer select. */
    private const PER_PAGE_OPTIONS = [10, 20, 50, 100, 200];

    /** Sortable grid headers → orderable column / withCount alias. */
    private const SORTABLE_COLUMNS = [
        'category' => 'issue_category',
        'description' => 'description',
        'sub_categories' => 'sub_categories_count',
        'status' => 'status',
    ];

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
                $query->where(function (Builder $inner) use ($like) {
                    $inner->where('issue_category', 'like', $like)
                        ->orWhere('description', 'like', $like);
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
     * @return array{total: int, ids: array<int, int>}
     */
    private function indexPageSnapshot(int $page, int $perPage, string $search, string $sortKey, string $sortDir): array
    {
        $base = $this->indexFilteredQuery($search, $sortKey, $sortDir);
        $total = (int) (clone $base)->toBase()->getCountForPagination();
        $ids = [];
        if ($total > 0) {
            $ids = (clone $base)->forPage($page, $perPage)->pluck('pk')->values()->all();
            $ids = array_map('intval', $ids);
        }

        return ['total' => $total, 'ids' => $ids];
    }

    /**
     * @param  array<int, int>  $ids
     * @return \Illuminate\Support\Collection<int, IssueCategoryMaster>
     */
    private function hydrateCategoriesByOrderedPks(array $ids): \Illuminate\Support\Collection
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if ($ids === []) {
            return collect();
        }
        $byPk = IssueCategoryMaster::with('subCategories')
            ->whereIn('pk', $ids)
            ->get()
            ->keyBy(fn (IssueCategoryMaster $m) => (int) $m->pk);

        return collect($ids)
            ->map(fn (int $id) => $byPk->get($id))
            ->filter()
            ->values();
    }

    /**
     * Display a listing of issue categories.
     */
    public function index(Request $request)
    {
        $page = Paginator::resolveCurrentPage('page');
        $perPage = $this->resolvePerPage($request);
        $search = $this->resolveSearch($request);
        $sort = $this->resolveSort($request);

        $epoch = DataTableRedisCache::readListEpoch(self::LISTING_CACHE_EPOCH_KEY);
        $cacheKey = 'admin_issue_categories_index:v1:' . md5(json_encode([
            'epoch' => $epoch,
            'page' => $page,
            'per_page' => $perPage,
            'q' => $search,
            'sort' => $sort,
        ]));

        $snapshot = DataTableRedisCache::remember(
            $cacheKey,
            [
                'enabled' => 'ISSUE_CATEGORY_INDEX_CACHE_ENABLED',
                'seconds' => 'ISSUE_CATEGORY_INDEX_CACHE_SECONDS',
            ],
            'IssueCategoryController@index',
            fn () => $this->indexPageSnapshot($page, $perPage, $search, $sort['key'], $sort['dir'])
        );

        if (! is_array($snapshot) || ! array_key_exists('total', $snapshot) || ! array_key_exists('ids', $snapshot) || ! is_array($snapshot['ids'])) {
            $snapshot = $this->indexPageSnapshot($page, $perPage, $search, $sort['key'], $sort['dir']);
        }

        $total = (int) $snapshot['total'];
        $ids = array_map('intval', $snapshot['ids']);
        $items = $this->hydrateCategoriesByOrderedPks($ids);

        $categories = new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]
        );
        $categories->withQueryString();

        $perPageOptions = self::PER_PAGE_OPTIONS;
        $sortKey = $sort['key'];
        $sortDir = $sort['dir'];

        return view('admin.issue_management.categories.index', compact(
            'categories',
            'perPage',
            'perPageOptions',
            'search',
            'sortKey',
            'sortDir'
        ));
    }

    /**
     * Download / print the full (filtered) category list.
     *
     * Both formats share the same header + columns as the index grid.
     */
    public function export(Request $request, string $format = 'csv')
    {
        $format = strtolower($format);
        abort_unless(in_array($format, ['csv', 'print'], true), 404);

        $search = $this->resolveSearch($request);
        $sort = $this->resolveSort($request);
        $rows = $this->indexFilteredQuery($search, $sort['key'], $sort['dir'], true)->get();

        $header = ['S. No.', 'Category', 'Description', 'Sub-Categories', 'Status'];

        if ($format === 'print') {
            return view('admin.issue_management.categories.export_print', [
                'header' => $header,
                'rows' => $rows,
                'search' => $search,
                'exportDate' => now()->format('d-m-Y h:i A'),
            ]);
        }

        $filename = 'ManageCategories_' . now()->format('YmdHis') . '.csv';

        return response()->streamDownload(function () use ($header, $rows) {
            $handle = fopen('php://output', 'w');
            // BOM so Excel opens the UTF-8 file with the right encoding.
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $header);

            foreach ($rows as $index => $row) {
                fputcsv($handle, [
                    $index + 1,
                    $row->issue_category,
                    $row->description,
                    (int) $row->sub_categories_count,
                    ((int) $row->status === 1) ? 'Active' : 'Inactive',
                ]);
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
