<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NoticeCategoryMaster;
use App\Models\NoticeNotification;
use App\Models\NoticeSubcategoryMaster;
use Illuminate\Http\Request;

class NoticeNotificationCategoryMasterController extends Controller
{
    public function index(Request $request)
    {
        $query = NoticeCategoryMaster::withCount('subCategories')
            ->withExists('notices')
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($request->filled('status') && $request->status !== '') {
            $query->where('active_inactive', (int) $request->status);
        }

        $categories = $query->paginate(20)->withQueryString();

        return view('admin.NoticeNotification.categories.category_master', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'sort_order' => 'nullable|integer|min:0|max:65535',
        ]);

        NoticeCategoryMaster::create([
            'name' => $request->name,
            'sort_order' => $request->input('sort_order', 0),
            'active_inactive' => 1,
        ]);

        return redirect()->back()->with('success', 'Category created successfully.');
    }

    public function update(Request $request, int $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'sort_order' => 'nullable|integer|min:0|max:65535',
            'active_inactive' => 'nullable|in:0,1',
        ]);

        $cat = NoticeCategoryMaster::where('pk', $id)->firstOrFail();
        $cat->update([
            'name' => $request->name,
            'sort_order' => $request->input('sort_order', $cat->sort_order),
            'active_inactive' => $request->input('active_inactive', $cat->active_inactive),
        ]);

        return redirect()->back()->with('success', 'Category updated successfully.');
    }

    public function destroy(int $id)
    {
        $cat = NoticeCategoryMaster::where('pk', $id)->firstOrFail();

        if ((int) $cat->active_inactive === 1) {
            return redirect()->back()->with('error', 'Deactivate the category before deleting.');
        }

        if (NoticeNotification::where('notice_category_master_pk', $id)->exists()) {
            return redirect()->back()->with('error', 'This category is used by notices and cannot be deleted.');
        }

        if (NoticeSubcategoryMaster::where('notice_category_master_pk', $id)->exists()) {
            return redirect()->back()->with('error', 'Delete all subcategories first (Notice subcategory master).');
        }

        $cat->delete();

        return redirect()->back()->with('success', 'Category deleted successfully.');
    }
}
