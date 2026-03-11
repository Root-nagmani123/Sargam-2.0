<?php

namespace App\Http\Controllers\Admin\IssueManagement;

use App\Http\Controllers\Controller;
use App\Models\IssuePriorityMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IssuePriorityController extends Controller
{
    /**
     * Display a listing of issue priorities.
     */
    public function index()
    {
        $priorities = IssuePriorityMaster::orderBy('priority')->paginate(20);

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

        return redirect()->route('admin.issue-priorities.index')
            ->with('success', 'Priority deleted successfully.');
    }
}
