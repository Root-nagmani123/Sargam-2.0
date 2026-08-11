<?php

namespace App\Http\Controllers\Admin\Setup;

use App\Http\Controllers\Controller;
use App\Models\QuickLink;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class QuickLinksSetupController extends Controller
{
    private function authorizeAdmin()
    {
        abort_unless(hasRole('Admin') || hasRole('Super Admin'), 403);
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->datatable();
        }

        return view('admin.setup.quick_links.index');
    }

    /**
     * Server-side feed for the listing grid (search/sort/paginate happen in SQL).
     * Rows carry id + position so drag-and-drop ordering keeps working per page.
     */
    protected function datatable()
    {
        $query = QuickLink::query()
            ->select(['id', 'label', 'url', 'position', 'target_blank'])
            ->orderBy('position');

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('url', fn ($row) => '<span class="d-inline-block text-truncate" style="max-width:360px;" title="'.e($row->url).'">'.e($row->url).'</span>')
            ->addColumn('order', fn () => '<span class="quicklink-drag-handle text-muted" draggable="true" title="Drag to reorder" style="cursor: grab;">'
                .'<i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">drag_handle</i></span>')
            ->addColumn('open', fn ($row) => $row->target_blank ? 'New Tab' : 'Same Tab')
            ->addColumn('action', function ($row) {
                return '<div class="d-flex gap-2">'
                    .'<a href="'.route('admin.setup.quick_links.edit', encrypt($row->id)).'" class="text-primary openEditQuickLink" title="Edit">'
                    .'<i class="material-icons material-symbols-rounded" style="font-size:22px;">edit</i></a>'
                    .'<form action="'.route('admin.setup.quick_links.delete', encrypt($row->id)).'" method="POST" onsubmit="return confirm(\'Delete this quick link?\');">'
                    .csrf_field().method_field('DELETE')
                    .'<button type="submit" class="btn btn-link p-0 text-primary" title="Delete">'
                    .'<i class="material-icons material-symbols-rounded" style="font-size:22px;">delete</i></button>'
                    .'</form></div>';
            })
            ->setRowAttr([
                'data-quicklink-id' => fn ($row) => $row->id,
                'data-position' => fn ($row) => $row->position,
            ])
            ->rawColumns(['url', 'order', 'action'])
            ->make(true);
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
            // Sent by the paginated grid: the position slots the reordered rows occupy.
            'positions' => ['sometimes', 'array'],
            'positions.*' => ['required', 'integer', 'min:1'],
        ]);

        $ids = $validated['order'];
        $existingIds = QuickLink::query()->whereIn('id', $ids)->pluck('id')->all();

        // Safety check: when index() is used, the order array should contain all ids.
        if (count($existingIds) !== count($ids)) {
            abort(422, 'Invalid quick link order payload.');
        }

        // With server-side paging only the visible rows are reordered, so they are shuffled
        // within the position slots they already occupy. Without `positions` (full-list
        // reorder) the legacy 1..n renumbering applies.
        $positions = $validated['positions'] ?? null;
        $usePositions = is_array($positions) && count($positions) === count($ids);

        foreach (array_values($ids) as $i => $id) {
            QuickLink::query()
                ->where('id', $id)
                ->update([
                    'position' => $usePositions ? (int) $positions[$i] : $i + 1,
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

