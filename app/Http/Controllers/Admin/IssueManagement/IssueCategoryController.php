<?php

namespace App\Http\Controllers\Admin\IssueManagement;

use App\Http\Controllers\Controller;
use App\Models\{
    IssueCategoryMaster,
    IssueSubCategoryMaster
};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IssueCategoryController extends Controller
{ 
    /**
     * Display a listing of issue categories.
     */
    public function index()
    {
        $categories = IssueCategoryMaster::with('subCategories')
            ->orderBy('issue_category')
            ->paginate(20);

        return view('admin.issue_management.categories.index', compact('categories'));
    }

    /**
     * Store a newly created category in storage.
     */
    public function store(Request $request)
    {
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
            'modified_date' => now(),
        ]);

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

        return redirect()->route('admin.issue-categories.index')
            ->with('success', 'Category deleted successfully.');
    }
}
