<?php

namespace App\Http\Controllers\Admin\IssueManagement;

use App\Http\Controllers\Controller;
use App\Models\IssuePriorityMaster;
use App\Support\DataTableRedisCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class IssuePriorityController extends Controller
{
    private const LISTING_CACHE_EPOCH_KEY = 'admin_issue_priorities_index_list_epoch';

    /**
     * Kept because sibling controllers call it on mutation. The listing itself no
     * longer reads this epoch — the grid is server-side and queries per draw.
     */
    public static function bumpIndexListCacheEpoch(): void
    {
        DataTableRedisCache::bumpListEpoch(self::LISTING_CACHE_EPOCH_KEY, 'IssuePriorityController@index');
    }

    private function indexFilteredQuery(): Builder
    {
        return IssuePriorityMaster::query()->orderBy('priority');
    }

    /**
     * Display a listing of issue priorities.
     *
     * Rows come from data() over ajax (server-side paging), so this action only
     * renders the shell — no rows, no pk snapshot, no cache read.
     */
    public function index()
    {
        return view('admin.issue_management.priorities.index');
    }

    /**
     * DataTables server-side feed for the Manage Priorities grid.
     *
     * Search and ordering run in SQL over the whole table; the browser only ever
     * receives the page it is showing.
     */
    public function data(Request $request)
    {
        // Only the columns the grid renders (G1) — this payload goes to the browser.
        $query = $this->indexFilteredQuery()
            ->select(['pk', 'priority', 'description', 'status'])
            ->reorder();   // drop the default sort; see below

        /* Only order here when DataTables sent none. An ORDER BY left on the query
           is applied FIRST and silently outranks the one Yajra appends, so the
           user's column sort would never take effect. */
        if (! $request->filled('order')) {
            $query->orderBy('priority');
        }

        return DataTables::of($query)
            ->addColumn('id', fn (IssuePriorityMaster $row) => (string) $row->pk)
            ->addColumn('priority_name', fn (IssuePriorityMaster $row) => (string) $row->priority)
            ->addColumn('description', fn (IssuePriorityMaster $row) => (string) ($row->description ?: '-'))
            ->addColumn('status', fn (IssuePriorityMaster $row) => (int) $row->status === 1
                ? '<span class="badge bg-success">Active</span>'
                : '<span class="badge bg-danger">Inactive</span>')
            ->addColumn('action', fn (IssuePriorityMaster $row) => view(
                'admin.issue_management.priorities._row_actions',
                ['priority' => $row]
            )->render())
            // Searching/ordering happen on the real columns, not the rendered ones.
            ->filterColumn('id', fn ($q, $keyword) => $q->where('pk', 'like', "%{$keyword}%"))
            ->filterColumn('priority_name', fn ($q, $keyword) => $q->where('priority', 'like', "%{$keyword}%"))
            ->filterColumn('description', fn ($q, $keyword) => $q->where('description', 'like', "%{$keyword}%"))
            ->orderColumn('id', 'pk $1')
            ->orderColumn('priority_name', 'priority $1')
            ->orderColumn('description', 'description $1')
            ->orderColumn('status', 'status $1')
            ->rawColumns(['status', 'action'])
            ->make(true);
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
