<?php

namespace App\Http\Controllers\Admin\IssueManagement;

use App\Exports\IssueSubCategoryExport;
use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\{
    IssueCategoryMaster,
    IssueSubCategoryMaster,
    IssueLogSubCategoryMap
};
use App\Support\DataTableRedisCache;
use App\Support\DataTableSearchHelper;
use App\Support\ExportCsvHeader;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class IssueSubCategoryController extends Controller
{
    private const LISTING_CACHE_EPOCH_KEY = 'admin_issue_sub_categories_index_list_epoch';

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
                $query->where(function (Builder $inner) use ($like, $search) {
                    $inner->where('issue_sub_category_master.issue_sub_category', 'like', $like)
                        ->orWhere('issue_category_master.issue_category', 'like', $like);

                    // The grid searches in the browser, so it also matches the
                    // rendered Status pill. The export runs this query instead —
                    // match the pill here too, or a searched export comes back
                    // empty while the screen shows rows.
                    $statuses = DataTableSearchHelper::statusPillMatches($search);
                    if ($statuses !== []) {
                        $inner->orWhereIn('issue_sub_category_master.status', $statuses);
                    }
                });
            })
            ->orderBy($sortColumn, $sortDir)
            // pk tiebreaker — names are not unique, and without it rows can
            // duplicate/vanish between pages of the snapshot pagination.
            ->orderBy('issue_sub_category_master.pk');
    }

    /**
     * Display a listing of issue sub-categories.
     *
     * Rows come from data() over ajax (server-side paging); this action only supplies
     * the category filter dropdown and the modals.
     */
    public function index(Request $request)
    {
        $categories = IssueCategoryMaster::active()->orderBy('issue_category')->get();

        return view('admin.issue_management.sub_categories.index', compact('categories'));
    }

    /**
     * DataTables server-side feed for the Manage Sub-Categories grid.
     *
     * The grid shows and sorts by the PARENT category name, so the join is not
     * optional — ordering through the relation alone cannot be expressed in SQL.
     */
    public function data(Request $request)
    {
        $query = IssueSubCategoryMaster::query()
            ->leftJoin(
                'issue_category_master',
                'issue_category_master.pk',
                '=',
                'issue_sub_category_master.issue_category_master_pk'
            )
            // Only the columns the grid renders (G1).
            ->select([
                'issue_sub_category_master.pk',
                'issue_sub_category_master.issue_category_master_pk',
                'issue_sub_category_master.issue_sub_category',
                'issue_sub_category_master.status',
            ])
            ->selectRaw('issue_category_master.issue_category as category_name')
            ->when(
                $request->filled('category_id'),
                fn ($q) => $q->where(
                    'issue_sub_category_master.issue_category_master_pk',
                    (int) $request->category_id
                )
            )
            ;

        /* Only order here when DataTables sent none. An ORDER BY baked into the
           query is applied FIRST and silently outranks the one Yajra appends, so
           the user's column sort would never take effect. */
        if (! $request->filled('order')) {
            $query->orderBy('issue_sub_category_master.pk', 'desc');
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('category', fn ($row) => (string) ($row->category_name ?: '-'))
            ->addColumn(
                'sub_category',
                fn ($row) => '<span class="fw-medium">' . e((string) $row->issue_sub_category) . '</span>'
            )
            ->addColumn('status', fn ($row) => view(
                'admin.issue_management.sub_categories._row_status',
                ['subCategory' => $row]
            )->render())
            ->addColumn('action', fn ($row) => view(
                'admin.issue_management.sub_categories._row_actions',
                ['subCategory' => $row]
            )->render())
            /* The status toggle reads these off the <tr> to rebuild its PUT payload,
               so they have to survive the move to ajax rows. */
            ->setRowAttr([
                'data-category-id' => fn ($row) => $row->issue_category_master_pk ?? '',
                'data-subcategory-name' => fn ($row) => $row->issue_sub_category,
            ])
            ->filterColumn(
                'category',
                fn ($q, $keyword) => $q->where('issue_category_master.issue_category', 'like', "%{$keyword}%")
            )
            ->filterColumn(
                'sub_category',
                fn ($q, $keyword) => $q->where('issue_sub_category_master.issue_sub_category', 'like', "%{$keyword}%")
            )
            ->orderColumn('category', 'issue_category_master.issue_category $1')
            ->orderColumn('sub_category', 'issue_sub_category_master.issue_sub_category $1')
            ->orderColumn('status', 'issue_sub_category_master.status $1')
            ->rawColumns(['sub_category', 'status', 'action'])
            ->make(true);
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
