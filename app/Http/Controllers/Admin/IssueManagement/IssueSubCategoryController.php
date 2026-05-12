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

    private const INDEX_PER_PAGE = 20;

    public static function bumpIndexListCacheEpoch(): void
    {
        DataTableRedisCache::bumpListEpoch(self::LISTING_CACHE_EPOCH_KEY, 'IssueSubCategoryController@index');
    }

    private function indexFilteredQuery(Request $request): Builder
    {
        $query = IssueSubCategoryMaster::query();
        if ($request->filled('category_id')) {
            $query->where('issue_category_master_pk', $request->category_id);
        }
        $query->orderBy('pk', 'desc');

        return $query;
    }

    /**
     * @return array{total: int, ids: array<int, int>}
     */
    private function indexPageSnapshot(Request $request, int $page): array
    {
        $base = $this->indexFilteredQuery($request);
        $perPage = self::INDEX_PER_PAGE;
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
        $epoch = DataTableRedisCache::readListEpoch(self::LISTING_CACHE_EPOCH_KEY);
        $cacheKey = 'admin_issue_sub_categories_index:v1:' . md5(json_encode([
            'epoch' => $epoch,
            'category_id' => $request->filled('category_id') ? (string) $request->category_id : null,
            'page' => $page,
        ]));

        $snapshot = DataTableRedisCache::remember(
            $cacheKey,
            [
                'enabled' => 'ISSUE_SUB_CATEGORY_INDEX_CACHE_ENABLED',
                'seconds' => 'ISSUE_SUB_CATEGORY_INDEX_CACHE_SECONDS',
            ],
            'IssueSubCategoryController@index',
            fn () => $this->indexPageSnapshot($request, $page)
        );

        if (! is_array($snapshot) || ! array_key_exists('total', $snapshot) || ! array_key_exists('ids', $snapshot) || ! is_array($snapshot['ids'])) {
            $snapshot = $this->indexPageSnapshot($request, $page);
        }

        $total = (int) $snapshot['total'];
        $ids = array_map('intval', $snapshot['ids']);
        $items = $this->hydrateSubCategoriesByOrderedPks($ids);

        $subCategories = new LengthAwarePaginator(
            $items,
            $total,
            self::INDEX_PER_PAGE,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]
        );
        $subCategories->withQueryString();

        $categories = IssueCategoryMaster::active()->orderBy('issue_category')->get();

        return view('admin.issue_management.sub_categories.index', compact('subCategories', 'categories'));
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
