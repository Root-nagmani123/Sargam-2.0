<?php

namespace App\Http\Controllers\Admin\IssueManagement;

use App\Http\Controllers\Controller;
use App\Models\{
    IssueCategoryMaster,
    IssueSubCategoryMaster
};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IssueSubCategoryController extends Controller
{
    /**
     * Display a listing of issue sub-categories.
     */
    public function index()
    {
        $subCategories = IssueSubCategoryMaster::with('category')
            ->orderBy('issue_sub_category')
            ->paginate(20);

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
            'description' => 'nullable|string',
        ]);

        IssueSubCategoryMaster::create([
            'issue_category_master_pk' => $request->issue_category_master_pk,
            'issue_sub_category' => $request->issue_sub_category,
            'description' => $request->description,
            'created_by' => Auth::id(),
            'created_date' => now(),
            'status' => 1,
        ]);

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
            'description' => 'nullable|string',
            'status' => 'required|in:0,1',
        ]);

        $subCategory->update([
            'issue_category_master_pk' => $request->issue_category_master_pk,
            'issue_sub_category' => $request->issue_sub_category,
            'description' => $request->description,
            'status' => $request->status,
            'modified_by' => Auth::id(),
            'modified_date' => now(),
        ]);

        return redirect()->route('admin.issue-sub-categories.index')
            ->with('success', 'Sub-category updated successfully.');
    }

    /**
     * Remove the specified sub-category from storage.
     */
    public function destroy($id)
    {
        $subCategory = IssueSubCategoryMaster::findOrFail($id);
        $subCategory->delete();

        return redirect()->route('admin.issue-sub-categories.index')
            ->with('success', 'Sub-category deleted successfully.');
    }
}
