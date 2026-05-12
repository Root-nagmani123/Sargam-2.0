<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NoticeCategoryMaster;
use App\Models\NoticeNotification;
use App\Models\NoticeSubcategoryMaster;
use Illuminate\Http\Request;

class NoticeNotificationSubcategoryMasterController extends Controller
{
    public function index(Request $request)
    {
        $categoryFilter = $request->input('notice_category_master_pk');

        $categories = NoticeCategoryMaster::orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $query = NoticeSubcategoryMaster::with('category')
            ->orderBy('notice_category_master_pk')
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($categoryFilter !== null && $categoryFilter !== '') {
            $query->where('notice_category_master_pk', $categoryFilter);
        }

        if ($request->filled('status') && $request->status !== '') {
            $query->where('active_inactive', (int) $request->status);
        }

        $subcategories = $query->paginate(25)->withQueryString();

        $usedSubcategoryIds = NoticeNotification::query()
            ->whereNotNull('notice_subcategory_master_pk')
            ->pluck('notice_subcategory_master_pk')
            ->unique();

        return view('admin.NoticeNotification.subcategories.index', compact(
            'subcategories',
            'categories',
            'categoryFilter',
            'usedSubcategoryIds'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'notice_category_master_pk' => 'required|exists:notice_category_master,pk',
            'name' => 'required|string|max:255',
            'sort_order' => 'nullable|integer|min:0|max:65535',
        ]);

        NoticeSubcategoryMaster::create([
            'notice_category_master_pk' => (int) $request->notice_category_master_pk,
            'name' => $request->name,
            'sort_order' => $request->input('sort_order', 0),
            'active_inactive' => 1,
        ]);

        return redirect()->back()->with('success', 'Subcategory created successfully.');
    }

    public function update(Request $request, int $id)
    {
        $request->validate([
            'notice_category_master_pk' => 'required|exists:notice_category_master,pk',
            'name' => 'required|string|max:255',
            'sort_order' => 'nullable|integer|min:0|max:65535',
            'active_inactive' => 'nullable|in:0,1',
        ]);

        $sub = NoticeSubcategoryMaster::where('pk', $id)->firstOrFail();
        $sub->update([
            'notice_category_master_pk' => (int) $request->notice_category_master_pk,
            'name' => $request->name,
            'sort_order' => $request->input('sort_order', $sub->sort_order),
            'active_inactive' => $request->input('active_inactive', $sub->active_inactive),
        ]);

        return redirect()->back()->with('success', 'Subcategory updated successfully.');
    }

    public function destroy(int $id)
    {
        $sub = NoticeSubcategoryMaster::where('pk', $id)->firstOrFail();
        $catPk = $sub->notice_category_master_pk;

        if ((int) $sub->active_inactive === 1) {
            return redirect()->back()->with('error', 'Deactivate the subcategory before deleting.');
        }

        if (NoticeNotification::where('notice_subcategory_master_pk', $id)->exists()) {
            return redirect()->back()->with('error', 'This subcategory is used by notices and cannot be deleted.');
        }

        $sub->delete();

        return redirect()->back()->with('success', 'Subcategory deleted successfully.');
    }
}
