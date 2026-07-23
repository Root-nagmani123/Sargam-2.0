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

    private const INDEX_PER_PAGE = 20;

    public static function bumpIndexListCacheEpoch(): void
    {
        DataTableRedisCache::bumpListEpoch(self::LISTING_CACHE_EPOCH_KEY, 'IssueCategoryController@index');
    }

    private function indexFilteredQuery(): Builder
    {
        // pk tiebreaker — issue_category unique nahi hai, warna snapshot pagination me
        // rows pages ke beech duplicate/miss ho sakte hain.
        return IssueCategoryMaster::query()
            ->orderBy('issue_category')
            ->orderBy('pk');
    }

    /**
     * @return array{total: int, ids: array<int, int>}
     */
    private function indexPageSnapshot(int $page): array
    {
        $base = $this->indexFilteredQuery();
        $total = (int) (clone $base)->toBase()->getCountForPagination();
        $ids = [];
        if ($total > 0) {
            $ids = (clone $base)->forPage($page, self::INDEX_PER_PAGE)->pluck('pk')->values()->all();
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
    public function index()
    {
        $page = Paginator::resolveCurrentPage('page');
        $epoch = DataTableRedisCache::readListEpoch(self::LISTING_CACHE_EPOCH_KEY);
        $cacheKey = 'admin_issue_categories_index:v1:' . md5(json_encode([
            'epoch' => $epoch,
            'page' => $page,
        ]));

        $snapshot = DataTableRedisCache::remember(
            $cacheKey,
            [
                'enabled' => 'ISSUE_CATEGORY_INDEX_CACHE_ENABLED',
                'seconds' => 'ISSUE_CATEGORY_INDEX_CACHE_SECONDS',
            ],
            'IssueCategoryController@index',
            fn () => $this->indexPageSnapshot($page)
        );

        if (! is_array($snapshot) || ! array_key_exists('total', $snapshot) || ! array_key_exists('ids', $snapshot) || ! is_array($snapshot['ids'])) {
            $snapshot = $this->indexPageSnapshot($page);
        }

        $total = (int) $snapshot['total'];
        $ids = array_map('intval', $snapshot['ids']);
        $items = $this->hydrateCategoriesByOrderedPks($ids);

        $categories = new LengthAwarePaginator(
            $items,
            $total,
            self::INDEX_PER_PAGE,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]
        );
        $categories->withQueryString();

        return view('admin.issue_management.categories.index', compact('categories'));
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
