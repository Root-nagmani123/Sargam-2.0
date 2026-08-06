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

    private const INDEX_PER_PAGE = 10;

    /** Page-size choices offered by the "Showing N of M items" footer select. */
    private const PER_PAGE_OPTIONS = [10, 20, 50, 100, 200];

    /** Sortable grid headers → the column ordered by. */
    private const SORTABLE_COLUMNS = [
        'priority' => 'priority',
        'description' => 'description',
        'status' => 'status',
    ];

    public static function bumpIndexListCacheEpoch(): void
    {
        DataTableRedisCache::bumpListEpoch(self::LISTING_CACHE_EPOCH_KEY, 'IssuePriorityController@index');
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
        $key = (string) $request->query('sort', 'priority');
        if (! array_key_exists($key, self::SORTABLE_COLUMNS)) {
            $key = 'priority';
        }

        $dir = strtolower((string) $request->query('dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        return ['key' => $key, 'dir' => $dir];
    }

    private function indexFilteredQuery(string $search = '', string $sortKey = 'priority', string $sortDir = 'asc'): Builder
    {
        $sortColumn = self::SORTABLE_COLUMNS[$sortKey] ?? self::SORTABLE_COLUMNS['priority'];
        $sortDir = $sortDir === 'desc' ? 'desc' : 'asc';

        return IssuePriorityMaster::query()
            ->when($search !== '', function (Builder $query) use ($search) {
                $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $search) . '%';
                $query->where(function (Builder $inner) use ($like) {
                    $inner->where('priority', 'like', $like)
                        ->orWhere('description', 'like', $like);
                });
            })
            ->orderBy($sortColumn, $sortDir)
            // pk tiebreaker — priority is not unique, and without it rows can
            // duplicate/vanish between pages of the snapshot pagination.
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
     * @return \Illuminate\Support\Collection<int, IssuePriorityMaster>
     */
    private function hydratePrioritiesByOrderedPks(array $ids): \Illuminate\Support\Collection
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if ($ids === []) {
            return collect();
        }
        // issue_logs_count drives the Delete guard — destroy() refuses a priority
        // that is referenced by any issue log.
        $byPk = IssuePriorityMaster::query()
            ->withCount('issueLogs')
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
    public function index(Request $request)
    {
        $page = Paginator::resolveCurrentPage('page');
        $perPage = $this->resolvePerPage($request);
        $search = $this->resolveSearch($request);
        $sort = $this->resolveSort($request);

        $epoch = DataTableRedisCache::readListEpoch(self::LISTING_CACHE_EPOCH_KEY);
        $cacheKey = 'admin_issue_priorities_index:v1:' . md5(json_encode([
            'epoch' => $epoch,
            'page' => $page,
            'per_page' => $perPage,
            'q' => $search,
            'sort' => $sort,
        ]));

        $snapshot = DataTableRedisCache::remember(
            $cacheKey,
            [
                'enabled' => 'ISSUE_PRIORITY_INDEX_CACHE_ENABLED',
                'seconds' => 'ISSUE_PRIORITY_INDEX_CACHE_SECONDS',
            ],
            'IssuePriorityController@index',
            fn () => $this->indexPageSnapshot($page, $perPage, $search, $sort['key'], $sort['dir'])
        );

        if (! is_array($snapshot) || ! array_key_exists('total', $snapshot) || ! array_key_exists('ids', $snapshot) || ! is_array($snapshot['ids'])) {
            $snapshot = $this->indexPageSnapshot($page, $perPage, $search, $sort['key'], $sort['dir']);
        }

        $total = (int) $snapshot['total'];
        $ids = array_map('intval', $snapshot['ids']);
        $items = $this->hydratePrioritiesByOrderedPks($ids);

        $priorities = new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]
        );
        $priorities->withQueryString();

        $perPageOptions = self::PER_PAGE_OPTIONS;
        $sortKey = $sort['key'];
        $sortDir = $sort['dir'];

        return view('admin.issue_management.priorities.index', compact(
            'priorities',
            'perPage',
            'perPageOptions',
            'search',
            'sortKey',
            'sortDir'
        ));
    }

    /**
     * Download / print the full (filtered) priority list.
     *
     * Both formats share the same header + columns as the index grid.
     */
    public function export(Request $request, string $format = 'csv')
    {
        $format = strtolower($format);
        abort_unless(in_array($format, ['csv', 'print'], true), 404);

        $search = $this->resolveSearch($request);
        $sort = $this->resolveSort($request);
        $rows = $this->indexFilteredQuery($search, $sort['key'], $sort['dir'])->get();

        $header = ['S. No.', 'Priority', 'Description', 'Status'];

        if ($format === 'print') {
            return view('admin.issue_management.priorities.export_print', [
                'header' => $header,
                'rows' => $rows,
                'search' => $search,
                'exportDate' => now()->format('d-m-Y h:i A'),
            ]);
        }

        $filename = 'ManagePriorities_' . now()->format('YmdHis') . '.csv';

        return response()->streamDownload(function () use ($header, $rows) {
            $handle = fopen('php://output', 'w');
            // BOM so Excel opens the UTF-8 file with the right encoding.
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $header);

            foreach ($rows as $index => $row) {
                fputcsv($handle, [
                    $index + 1,
                    $row->priority,
                    $row->description,
                    ((int) $row->status === 1) ? 'Active' : 'Inactive',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
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
        $priority = IssuePriorityMaster::findOrFail($id);

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
        $priority = IssuePriorityMaster::findOrFail($id);

        if ($priority->issueLogs()->count() > 0) {
            return back()->with('error', 'Cannot delete priority with associated issues.');
        }

        $priority->delete();

        static::bumpIndexListCacheEpoch();

        return redirect()->route('admin.issue-priorities.index')
            ->with('success', 'Priority deleted successfully.');
    }
}
