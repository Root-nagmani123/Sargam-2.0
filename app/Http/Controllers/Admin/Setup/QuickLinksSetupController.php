<?php

namespace App\Http\Controllers\Admin\Setup;

use App\Http\Controllers\Controller;
use App\Models\QuickLink;
use Illuminate\Http\Request;

class QuickLinksSetupController extends Controller
{
    private function authorizeAdmin()
    {
        abort_unless(hasRole('Admin') || hasRole('Super Admin'), 403);
    }

    public function index()
    {
        // Quick links are typically small in number, so we load all records.
        // This allows drag-and-drop ordering across the full list.
        $quickLinks = QuickLink::query()
            ->orderBy('position')
            ->get();

        return view('admin.setup.quick_links.index', compact('quickLinks'));
    }

    public function create(Request $request)
    {
        $this->authorizeAdmin();

        // Load only the form markup inside the modal (keeps master layout out of the modal).
        if ($request->ajax()) {
            return view('admin.setup.quick_links._form');
        }

        return view('admin.setup.quick_links.create');
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'url' => ['required', 'string', 'max:2048', 'url'],
            'target_blank' => ['required', 'boolean'],
        ]);

        $position = (int) (QuickLink::query()->max('position') ?? 0) + 1;

        QuickLink::create([
            'label' => $validated['label'],
            'url' => trim($validated['url']),
            'target_blank' => (bool) $validated['target_blank'],
            'position' => $position,
            'active_inactive' => 1,
        ]);

        return redirect()
            ->route('admin.setup.quick_links.index')
            ->with('success', 'Quick link created successfully.');
    }

    public function edit(Request $request, $id)
    {
        $this->authorizeAdmin();

        try {
            $pk = decrypt($id);
        } catch (\Throwable $e) {
            abort(404);
        }

        $quickLink = QuickLink::query()->findOrFail($pk);

        if ($request->ajax()) {
            return view('admin.setup.quick_links._form', compact('quickLink'));
        }

        return view('admin.setup.quick_links.edit', compact('quickLink'));
    }

    public function update(Request $request, $id)
    {
        $this->authorizeAdmin();

        try {
            $pk = decrypt($id);
        } catch (\Throwable $e) {
            abort(404);
        }

        $quickLink = QuickLink::query()->findOrFail($pk);

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'url' => ['required', 'string', 'max:2048', 'url'],
            'target_blank' => ['required', 'boolean'],
        ]);

        $quickLink->label = $validated['label'];
        $quickLink->url = trim($validated['url']);
        $quickLink->target_blank = (bool) $validated['target_blank'];
        $quickLink->active_inactive = 1;
        $quickLink->save();

        return redirect()
            ->route('admin.setup.quick_links.index')
            ->with('success', 'Quick link updated successfully.');
    }

    public function delete($id)
    {
        $this->authorizeAdmin();

        try {
            $pk = decrypt($id);
        } catch (\Throwable $e) {
            abort(404);
        }

        QuickLink::query()->where('id', $pk)->delete();

        return redirect()
            ->route('admin.setup.quick_links.index')
            ->with('success', 'Quick link deleted successfully.');
    }

    public function reorder(Request $request, $id)
    {
        $this->authorizeAdmin();

        try {
            $pk = decrypt($id);
        } catch (\Throwable $e) {
            abort(404);
        }

        $validated = $request->validate([
            'position' => ['required', 'integer', 'min:1'],
        ]);

        $target = QuickLink::query()->findOrFail($pk);
        $target->position = (int) $validated['position'];
        $target->active_inactive = 1;
        $target->save();

        // Normalize positions to keep them unique and continuous.
        $links = QuickLink::query()
            ->orderBy('position')
            ->orderBy('id')
            ->get(['id', 'position']);

        $i = 1;
        foreach ($links as $link) {
            if ((int) $link->position !== $i) {
                QuickLink::query()->where('id', $link->id)->update(['position' => $i]);
            }
            $i++;
        }

        return redirect()
            ->route('admin.setup.quick_links.index')
            ->with('success', 'Quick link order updated successfully.');
    }

    public function bulkReorder(Request $request)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'order' => ['required', 'array', 'min:1'],
            'order.*' => ['required', 'integer', 'min:1', 'distinct'],
        ]);

        $ids = $validated['order'];
        $existingIds = QuickLink::query()->whereIn('id', $ids)->pluck('id')->all();

        // Safety check: when index() is used, the order array should contain all ids.
        if (count($existingIds) !== count($ids)) {
            abort(422, 'Invalid quick link order payload.');
        }

        foreach (array_values($ids) as $i => $id) {
            QuickLink::query()
                ->where('id', $id)
                ->update([
                    'position' => $i + 1,
                    'active_inactive' => 1,
                ]);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()
            ->route('admin.setup.quick_links.index')
            ->with('success', 'Quick links order updated successfully.');
    }
}

