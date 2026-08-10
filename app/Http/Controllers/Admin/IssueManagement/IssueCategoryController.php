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
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class IssueCategoryController extends Controller
{
    private const LISTING_CACHE_EPOCH_KEY = 'admin_issue_categories_index_list_epoch';

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
