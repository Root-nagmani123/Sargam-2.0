<?php

namespace App\Http\Controllers\Admin\IssueManagement;

use App\Http\Controllers\Controller;
use App\Models\IssuePriorityMaster;
use App\Support\DataTableRedisCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;

class IssuePriorityController extends Controller
{
    private const LISTING_CACHE_EPOCH_KEY = 'admin_issue_priorities_index_list_epoch';

    private const INDEX_PER_PAGE = 20;

    public static function bumpIndexListCacheEpoch(): void
    {
        DataTableRedisCache::bumpListEpoch(self::LISTING_CACHE_EPOCH_KEY, 'IssuePriorityController@index');
    }

    private function indexFilteredQuery(): Builder
    {
        return IssuePriorityMaster::query()->orderBy('priority');
    }

    /**
     * @return array{total: int, ids: array<int, int>}
     */
    private function indexPageSnapshot(int $page): array
    {
        $base = $this->indexFilteredQuery();
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
     * @return \Illuminate\Support\Collection<int, IssuePriorityMaster>
     */
    private function hydratePrioritiesByOrderedPks(array $ids): \Illuminate\Support\Collection
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if ($ids === []) {
            return collect();
        }
        $byPk = IssuePriorityMaster::query()
            ->whereIn('pk', $ids)
            ->get()
            ->keyBy(fn (IssuePriorityMaster $m) => (int) $m->pk);

        return collect($ids)
            ->map(fn (int $id) => $byPk->get($id))
            ->filter()
            ->values();
    }

    /**
     * Display a listing of issue priorities.
     */
    public function index()
    {
        $page = Paginator::resolveCurrentPage('page');
        $epoch = DataTableRedisCache::readListEpoch(self::LISTING_CACHE_EPOCH_KEY);
        $cacheKey = 'admin_issue_priorities_index:v1:' . md5(json_encode([
            'epoch' => $epoch,
            'page' => $page,
        ]));

        $snapshot = DataTableRedisCache::remember(
            $cacheKey,
            [
                'enabled' => 'ISSUE_PRIORITY_INDEX_CACHE_ENABLED',
                'seconds' => 'ISSUE_PRIORITY_INDEX_CACHE_SECONDS',
            ],
            'IssuePriorityController@index',
            fn () => $this->indexPageSnapshot($page)
        );

        if (! is_array($snapshot) || ! array_key_exists('total', $snapshot) || ! array_key_exists('ids', $snapshot) || ! is_array($snapshot['ids'])) {
            $snapshot = $this->indexPageSnapshot($page);
        }

        $total = (int) $snapshot['total'];
        $ids = array_map('intval', $snapshot['ids']);
        $items = $this->hydratePrioritiesByOrderedPks($ids);

        $priorities = new LengthAwarePaginator(
            $items,
            $total,
            self::INDEX_PER_PAGE,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]
        );

        return view('admin.issue_management.priorities.index', compact('priorities'));
    }

    /**
     * Store a newly created priority in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'priority' => 'required|string|max:100',
            'description' => 'nullable|string',
        ]);

        IssuePriorityMaster::create([
            'priority' => $request->priority,
            'description' => $request->description,
            'created_by' => Auth::id(),
            'created_date' => now(),
            'status' => 1,
        ]);

        static::bumpIndexListCacheEpoch();

        return redirect()->route('admin.issue-priorities.index')
            ->with('success', 'Priority added successfully.');
    }

    /**
     * Update the specified priority in storage.
     */
    public function update(Request $request, $id)
    {
        $priority = IssuePriorityMaster::findOrFail(decrypt($id));

        $request->validate([
            'priority' => 'required|string|max:100',
            'description' => 'nullable|string',
            'status' => 'required|in:0,1',
        ]);

        $priority->update([
            'priority' => $request->priority,
            'description' => $request->description,
            'status' => $request->status,
            'modified_by' => Auth::id(),
            'modified_date' => now(),
        ]);

        static::bumpIndexListCacheEpoch();

        return redirect()->route('admin.issue-priorities.index')
            ->with('success', 'Priority updated successfully.');
    }

    /**
     * Remove the specified priority from storage.
     */
    public function destroy($id)
    {
        $priority = IssuePriorityMaster::findOrFail(decrypt($id));

        if ($priority->issueLogs()->count() > 0) {
            return back()->with('error', 'Cannot delete priority with associated issues.');
        }

        $priority->delete();

        static::bumpIndexListCacheEpoch();

        return redirect()->route('admin.issue-priorities.index')
            ->with('success', 'Priority deleted successfully.');
    }
}
