<?php

namespace App\Http\Controllers\Admin\IssueManagement;

use App\Http\Controllers\Controller;
use App\Models\{
    IssueCategoryMaster,
    IssueSubCategoryMaster,
    IssueLogSubCategoryMap
};
use App\Support\DataTableRedisCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;

class IssueSubCategoryController extends Controller
{
    private const LISTING_CACHE_EPOCH_KEY = 'admin_issue_sub_categories_index_list_epoch';

    private const INDEX_PER_PAGE = 10;

    /** Page-size choices offered by the "Showing N of M items" footer select. */
    private const PER_PAGE_OPTIONS = [10, 20, 50, 100, 200];

    /** Sortable grid headers → the column ordered by. */
    private const SORTABLE_COLUMNS = [
        'category' => 'category_name',
        'sub_category' => 'issue_sub_category_master.issue_sub_category',
        'status' => 'issue_sub_category_master.status',
    ];

    public static function bumpIndexListCacheEpoch(): void
    {
        DataTableRedisCache::bumpListEpoch(self::LISTING_CACHE_EPOCH_KEY, 'IssueSubCategoryController@index');
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

    private function resolveCategoryFilter(Request $request): ?int
    {
        $categoryId = $request->query('category_id');

        return ($categoryId !== null && $categoryId !== '') ? (int) $categoryId : null;
    }

    /**
     * The grid sorts and searches by the PARENT category name, so the join is
     * not optional — an orderBy on the relation alone would not work here.
     */
    private function indexFilteredQuery(
        string $search = '',
        string $sortKey = 'category',
        string $sortDir = 'asc',
        ?int $categoryId = null
    ): Builder {
        $sortColumn = self::SORTABLE_COLUMNS[$sortKey] ?? self::SORTABLE_COLUMNS['category'];
        $sortDir = $sortDir === 'desc' ? 'desc' : 'asc';

        return IssueSubCategoryMaster::query()
            ->leftJoin(
                'issue_category_master',
                'issue_category_master.pk',
                '=',
                'issue_sub_category_master.issue_category_master_pk'
            )
            ->select('issue_sub_category_master.*')
            ->selectRaw('issue_category_master.issue_category as category_name')
            ->when($categoryId !== null, fn (Builder $query) => $query
                ->where('issue_sub_category_master.issue_category_master_pk', $categoryId))
            ->when($search !== '', function (Builder $query) use ($search) {
                $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $search) . '%';
                $query->where(function (Builder $inner) use ($like) {
                    $inner->where('issue_sub_category_master.issue_sub_category', 'like', $like)
                        ->orWhere('issue_category_master.issue_category', 'like', $like);
                });
            })
            ->orderBy($sortColumn, $sortDir)
            // pk tiebreaker — names are not unique, and without it rows can
            // duplicate/vanish between pages of the snapshot pagination.
            ->orderBy('issue_sub_category_master.pk');
    }

    /**
     * @return array{total: int, ids: array<int, int>}
     */
    private function indexPageSnapshot(int $page, int $perPage, string $search, string $sortKey, string $sortDir, ?int $categoryId): array
    {
        $base = $this->indexFilteredQuery($search, $sortKey, $sortDir, $categoryId);
        $total = (int) (clone $base)->toBase()->getCountForPagination();
        $ids = [];
        if ($total > 0) {
            $ids = (clone $base)->forPage($page, $perPage)
                ->pluck('issue_sub_category_master.pk')
                ->values()
                ->all();
            $ids = array_map('intval', $ids);
        }

        return ['total' => $total, 'ids' => $ids];
    }

    /**
     * @param  array<int, int>  $ids
     * @return \Illuminate\Support\Collection<int, IssueSubCategoryMaster>
     */
    private function hydrateSubCategoriesByOrderedPks(array $ids): \Illuminate\Support\Collection
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if ($ids === []) {
            return collect();
        }
        $byPk = IssueSubCategoryMaster::with('category')
            ->whereIn('pk', $ids)
            ->get()
            ->keyBy(fn (IssueSubCategoryMaster $m) => (int) $m->pk);

        return collect($ids)
            ->map(fn (int $id) => $byPk->get($id))
            ->filter()
            ->values();
    }

    /**
     * Display a listing of issue sub-categories.
     */
    public function index(Request $request)
    {
        $page = Paginator::resolveCurrentPage('page');
        $perPage = $this->resolvePerPage($request);
        $search = $this->resolveSearch($request);
        $sort = $this->resolveSort($request);
        $categoryId = $this->resolveCategoryFilter($request);

        $epoch = DataTableRedisCache::readListEpoch(self::LISTING_CACHE_EPOCH_KEY);
        $cacheKey = 'admin_issue_sub_categories_index:v1:' . md5(json_encode([
            'epoch' => $epoch,
            'category_id' => $categoryId,
            'page' => $page,
            'per_page' => $perPage,
            'q' => $search,
            'sort' => $sort,
        ]));

        $snapshot = DataTableRedisCache::remember(
            $cacheKey,
            [
                'enabled' => 'ISSUE_SUB_CATEGORY_INDEX_CACHE_ENABLED',
                'seconds' => 'ISSUE_SUB_CATEGORY_INDEX_CACHE_SECONDS',
            ],
            'IssueSubCategoryController@index',
            fn () => $this->indexPageSnapshot($page, $perPage, $search, $sort['key'], $sort['dir'], $categoryId)
        );

        if (! is_array($snapshot) || ! array_key_exists('total', $snapshot) || ! array_key_exists('ids', $snapshot) || ! is_array($snapshot['ids'])) {
            $snapshot = $this->indexPageSnapshot($page, $perPage, $search, $sort['key'], $sort['dir'], $categoryId);
        }

        $total = (int) $snapshot['total'];
        $ids = array_map('intval', $snapshot['ids']);
        $items = $this->hydrateSubCategoriesByOrderedPks($ids);

        $subCategories = new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]
        );
        $subCategories->withQueryString();

        $categories = IssueCategoryMaster::active()->orderBy('issue_category')->get();
        $perPageOptions = self::PER_PAGE_OPTIONS;
        $sortKey = $sort['key'];
        $sortDir = $sort['dir'];

        return view('admin.issue_management.sub_categories.index', compact(
            'subCategories',
            'categories',
            'perPage',
            'perPageOptions',
            'search',
            'sortKey',
            'sortDir',
            'categoryId'
        ));
    }

    /**
     * Download / print the full (filtered) sub-category list.
     *
     * Both formats share the same header + columns as the index grid.
     */
    public function export(Request $request, string $format = 'csv')
    {
        $format = strtolower($format);
        abort_unless(in_array($format, ['csv', 'print'], true), 404);

        $search = $this->resolveSearch($request);
        $sort = $this->resolveSort($request);
        $categoryId = $this->resolveCategoryFilter($request);

        $rows = $this->indexFilteredQuery($search, $sort['key'], $sort['dir'], $categoryId)->get();

        $header = ['S. No.', 'Category', 'Sub-Categories Name', 'Status'];

        if ($format === 'print') {
            return view('admin.issue_management.sub_categories.export_print', [
                'header' => $header,
                'rows' => $rows,
                'search' => $search,
                'exportDate' => now()->format('d-m-Y h:i A'),
            ]);
        }

        $filename = 'ManageSubCategories_' . now()->format('YmdHis') . '.csv';

        return response()->streamDownload(function () use ($header, $rows) {
            $handle = fopen('php://output', 'w');
            // BOM so Excel opens the UTF-8 file with the right encoding.
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $header);

            foreach ($rows as $index => $row) {
                fputcsv($handle, [
                    $index + 1,
                    $row->category_name,
                    $row->issue_sub_category,
                    ((int) $row->status === 1) ? 'Active' : 'Inactive',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Store a newly created sub-category in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'issue_category_master_pk' => 'required|exists:issue_category_master,pk',
            'issue_sub_category' => 'required|string|max:255',

        ]);

        $userId = Auth::user()->user_id ?? Auth::id();
        IssueSubCategoryMaster::create([
            'issue_category_master_pk' => $request->issue_category_master_pk,
            'issue_sub_category' => $request->issue_sub_category,
            'created_date' => now()->setTimezone('Asia/Kolkata')->format('Y-m-d'),
            'created_by' => $userId,
            'status' => 1,
        ]);

        static::bumpIndexListCacheEpoch();

        return redirect()->route('admin.issue-sub-categories.index')
            ->with('success', 'Sub-category created successfully.');
    }

    /**
     * Update the specified sub-category in storage.
     */
    public function update(Request $request, $id)
    {
        $subCategory = IssueSubCategoryMaster::findOrFail($id);

        $request->validate([
            'issue_category_master_pk' => 'required|exists:issue_category_master,pk',
            'issue_sub_category' => 'required|string|max:255',
            'status' => 'required|in:0,1',
        ]);

        $userId = Auth::user()->user_id ?? Auth::id();
        $subCategory->update([
            'issue_category_master_pk' => $request->issue_category_master_pk,
            'issue_sub_category' => $request->issue_sub_category,
            'status' => $request->status,
            'modified_by' => $userId,
            'modified_date' => now(),
        ]);

        static::bumpIndexListCacheEpoch();

        return redirect()->route('admin.issue-sub-categories.index')
            ->with('success', 'Sub-category updated successfully.');
    }

    /**
     * Remove the specified sub-category from storage.
     */
    public function destroy($id)
    {
        $subCategory = IssueSubCategoryMaster::findOrFail($id);

        if ($subCategory->status == 1) {
            return back()->with('error', 'Cannot delete an active sub-category. Please set it to Inactive first.');
        }

        if (IssueLogSubCategoryMap::where('issue_sub_category_master_pk', $id)->exists()) {
            return back()->with('error', 'Cannot delete sub-category with associated issues.');
        }

        $subCategory->delete();

        static::bumpIndexListCacheEpoch();

        return redirect()->route('admin.issue-sub-categories.index')
            ->with('success', 'Sub-category deleted successfully.');
    }
}
