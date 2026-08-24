<?php

namespace App\Http\Controllers\Admin\Setup;

use App\Http\Controllers\Controller;
use App\Models\UsefulLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UsefulLinksSetupController extends Controller
{
    private function authorizeAdmin()
    {
        abort_unless(hasRole('Admin') || hasRole('Super Admin'), 403);
    }

    public function index()
    {
        $usefulLinks = UsefulLink::query()
            ->orderBy('position')
            ->get();

        return view('admin.setup.useful_links.index', compact('usefulLinks'));
    }

    /**
     * Download / Print — one action, two formats, off the same query and the
     * same column definitions, so the CSV and the printout can't drift apart
     * (docs/new-design-index-page.md §1). ?q and ?cols are stamped on by the
     * grid so the export carries what the user is looking at.
     */
    public function export(Request $request)
    {
        $this->authorizeAdmin();

        $query = UsefulLink::query()->orderBy('position');

        $search = trim((string) $request->input('q', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $like = '%'.$search.'%';
                $q->where('label', 'like', $like)->orWhere('url', 'like', $like);
            });
        }

        $rows = $query->get();
        $columns = $this->exportColumns($request->input('cols'));

        if ($request->input('format') === 'print') {
            return view('admin.setup.useful_links.export_print', [
                'rows' => $rows,
                'columns' => $columns,
                'search' => $search,
                'exportDate' => now()->format('d-m-Y H:i'),
            ]);
        }

        $filename = 'useful-links-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($rows, $columns) {
            $handle = fopen('php://output', 'w');
            // BOM: without it Excel reads the file as ANSI and mangles any
            // non-ASCII label.
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, array_column($columns, 'heading'));

            foreach ($rows as $index => $row) {
                fputcsv($handle, array_map(
                    fn (array $col) => (string) $col['value']($row, $index),
                    $columns
                ));
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * One definition feeds BOTH the CSV and the print view. `key` is what the
     * grid's Columns modal sends back as ?cols=; keep the first column
     * identical to the grid's.
     */
    private function exportColumnDefs(): array
    {
        return [
            ['key' => 'sno', 'heading' => 'S.No.', 'class' => 'ul-print-sno',
                'value' => fn ($row, $index) => $index + 1],
            ['key' => 'label', 'heading' => 'Label', 'class' => 'ul-print-label',
                'value' => fn ($row) => (string) $row->label],
            ['key' => 'url', 'heading' => 'URL', 'class' => 'ul-print-url',
                'value' => fn ($row) => filled($row->url) ? (string) $row->url : '-'],
            ['key' => 'file', 'heading' => 'File', 'class' => 'ul-print-file',
                'value' => fn ($row) => filled($row->file_path) ? basename($row->file_path) : '-'],
            ['key' => 'position', 'heading' => 'Order', 'class' => 'ul-print-order',
                'value' => fn ($row) => (string) $row->position],
            ['key' => 'target', 'heading' => 'Opens In', 'class' => 'ul-print-target',
                'value' => fn ($row) => $row->target_blank ? 'New Tab' : 'Same Tab'],
        ];
    }

    /** Columns still ticked in the grid's Columns modal, in table order. */
    private function exportColumns(?string $cols): array
    {
        $defs = $this->exportColumnDefs();

        if (! filled($cols)) {
            return $defs;
        }

        $wanted = array_filter(array_map('trim', explode(',', $cols)));
        if (empty($wanted)) {
            return $defs;
        }

        return array_values(array_filter(
            $defs,
            fn (array $def) => in_array($def['key'], $wanted, true)
        ));
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
        ]);

        $ids = $validated['order'];
        $existingIds = UsefulLink::query()->whereIn('id', $ids)->pluck('id')->all();

        if (count($existingIds) !== count($ids)) {
            abort(422, 'Invalid useful link order payload.');
        }

        foreach (array_values($ids) as $i => $id) {
            UsefulLink::query()
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
            ->route('admin.setup.useful_links.index')
            ->with('success', 'Useful links order updated successfully.');
    }
}

