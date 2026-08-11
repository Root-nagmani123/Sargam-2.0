<?php

namespace App\Http\Controllers\Admin\Setup;

use App\Http\Controllers\Controller;
use App\Models\UsefulLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class UsefulLinksSetupController extends Controller
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

        return view('admin.setup.useful_links.index');
    }

    /**
     * Server-side feed for the listing grid (search/sort/paginate happen in SQL).
     * Rows carry id + position so drag-and-drop ordering keeps working per page.
     */
    protected function datatable()
    {
        $query = UsefulLink::query()
            ->select(['id', 'label', 'url', 'file_path', 'position', 'target_blank'])
            ->orderBy('position');

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('url', fn ($row) => '<span class="d-inline-block text-truncate" style="max-width:260px;" title="'.e($row->url).'">'.e($row->url ?: '-').'</span>')
            ->addColumn('file', fn ($row) => $row->file_path
                ? '<a href="'.e(asset('storage/'.$row->file_path)).'" target="_blank">View File</a>'
                : '-')
            ->addColumn('order', fn () => '<span class="usefullink-drag-handle text-muted" draggable="true" title="Drag to reorder" style="cursor: grab;">'
                .'<i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">drag_handle</i></span>')
            ->addColumn('open', fn ($row) => $row->target_blank ? 'New Tab' : 'Same Tab')
            ->addColumn('action', function ($row) {
                return '<div class="d-flex gap-2">'
                    .'<a href="'.route('admin.setup.useful_links.edit', encrypt($row->id)).'" class="text-primary openEditUsefulLink" title="Edit">'
                    .'<i class="material-icons material-symbols-rounded" style="font-size:22px;">edit</i></a>'
                    .'<form action="'.route('admin.setup.useful_links.delete', encrypt($row->id)).'" method="POST" onsubmit="return confirm(\'Delete this useful link?\');">'
                    .csrf_field().method_field('DELETE')
                    .'<button type="submit" class="btn btn-link p-0 text-primary" title="Delete">'
                    .'<i class="material-icons material-symbols-rounded" style="font-size:22px;">delete</i></button>'
                    .'</form></div>';
            })
            ->setRowAttr([
                'data-usefullink-id' => fn ($row) => $row->id,
                'data-position' => fn ($row) => $row->position,
            ])
            ->rawColumns(['url', 'file', 'order', 'action'])
            ->make(true);
    }

    public function create(Request $request)
    {
        $this->authorizeAdmin();

        if ($request->ajax()) {
            return view('admin.setup.useful_links._form');
        }

        return view('admin.setup.useful_links.create');
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'url' => ['nullable', 'string', 'max:2048', 'url'],
            'file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx,ppt,pptx', 'max:10240'],
            'target_blank' => ['required', 'boolean'],
        ]);

        $storeUrl = isset($validated['url']) ? trim($validated['url']) : null;
        if ($storeUrl === '') {
            $storeUrl = null;
        }

        if (!$storeUrl && !$request->hasFile('file')) {
            return back()
                ->withErrors(['url_or_file' => 'Please provide either URL or file.'])
                ->withInput();
        }

        $position = (int) (UsefulLink::query()->max('position') ?? 0) + 1;
        $filePath = null;

        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('useful-links', 'public');
        }

        UsefulLink::create([
            'label' => $validated['label'],
            'url' => $storeUrl,
            'file_path' => $filePath,
            'target_blank' => (bool) $validated['target_blank'],
            'position' => $position,
            'active_inactive' => 1,
        ]);

        return redirect()
            ->route('admin.setup.useful_links.index')
            ->with('success', 'Useful link created successfully.');
    }

    public function edit(Request $request, $id)
    {
        $this->authorizeAdmin();

        try {
            $pk = decrypt($id);
        } catch (\Throwable $e) {
            abort(404);
        }

        $usefulLink = UsefulLink::query()->findOrFail($pk);

        if ($request->ajax()) {
            return view('admin.setup.useful_links._form', compact('usefulLink'));
        }

        return view('admin.setup.useful_links.edit', compact('usefulLink'));
    }

    public function update(Request $request, $id)
    {
        $this->authorizeAdmin();

        try {
            $pk = decrypt($id);
        } catch (\Throwable $e) {
            abort(404);
        }

        $usefulLink = UsefulLink::query()->findOrFail($pk);

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'url' => ['nullable', 'string', 'max:2048', 'url'],
            'file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx,ppt,pptx', 'max:10240'],
            'target_blank' => ['required', 'boolean'],
            'remove_file' => ['nullable', 'boolean'],
        ]);

        $removeFile = (bool) ($validated['remove_file'] ?? false);
        $currentPath = $usefulLink->file_path;
        $newFilePath = $currentPath;

        if ($removeFile && $currentPath) {
            if (Storage::disk('public')->exists($currentPath)) {
                Storage::disk('public')->delete($currentPath);
            }
            $newFilePath = null;
        }

        if ($request->hasFile('file')) {
            if ($currentPath && Storage::disk('public')->exists($currentPath)) {
                Storage::disk('public')->delete($currentPath);
            }
            $newFilePath = $request->file('file')->store('useful-links', 'public');
        }

        $finalUrl = isset($validated['url']) ? trim($validated['url']) : null;
        if ($finalUrl === '') {
            $finalUrl = null;
        }
        if (!$finalUrl && !$newFilePath) {
            return back()
                ->withErrors(['url_or_file' => 'Please provide either URL or file.'])
                ->withInput();
        }

        $usefulLink->label = $validated['label'];
        $usefulLink->url = $finalUrl;
        $usefulLink->file_path = $newFilePath;
        $usefulLink->target_blank = (bool) $validated['target_blank'];
        $usefulLink->active_inactive = 1;
        $usefulLink->save();

        return redirect()
            ->route('admin.setup.useful_links.index')
            ->with('success', 'Useful link updated successfully.');
    }

    public function delete($id)
    {
        $this->authorizeAdmin();

        try {
            $pk = decrypt($id);
        } catch (\Throwable $e) {
            abort(404);
        }

        $usefulLink = UsefulLink::query()->findOrFail($pk);

        if ($usefulLink->file_path && Storage::disk('public')->exists($usefulLink->file_path)) {
            Storage::disk('public')->delete($usefulLink->file_path);
        }

        $usefulLink->delete();

        return redirect()
            ->route('admin.setup.useful_links.index')
            ->with('success', 'Useful link deleted successfully.');
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
        $existingIds = UsefulLink::query()->whereIn('id', $ids)->pluck('id')->all();

        if (count($existingIds) !== count($ids)) {
            abort(422, 'Invalid useful link order payload.');
        }

        // With server-side paging only the visible rows are reordered, so they are shuffled
        // within the position slots they already occupy. Without `positions` (full-list
        // reorder) the legacy 1..n renumbering applies.
        $positions = $validated['positions'] ?? null;
        $usePositions = is_array($positions) && count($positions) === count($ids);

        foreach (array_values($ids) as $i => $id) {
            UsefulLink::query()
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
            ->route('admin.setup.useful_links.index')
            ->with('success', 'Useful links order updated successfully.');
    }
}

