<?php

namespace App\Http\Controllers\Admin\IssueManagement;

use App\Http\Controllers\Controller;
use App\Models\{
    IssueCategoryMaster,
    IssueSubCategoryMaster,
    IssueLogSubCategoryMap
};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IssueSubCategoryController extends Controller
{
    /**
     * Display a listing of issue sub-categories.
     */
    public function index(Request $request)
    {
       
        $query = IssueSubCategoryMaster::with('category');
        if ($request->filled('category_id')) {
            $query->where('issue_category_master_pk', $request->category_id);
        }
        $subCategories = $query->orderBy('pk','desc')->paginate(20)->withQueryString();
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

        return redirect()->route('admin.issue-sub-categories.index')
            ->with('success', 'Sub-category deleted successfully.');
    }
}
