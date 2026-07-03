<?php

namespace App\Http\Controllers\FC;

use App\Http\Controllers\Controller;
use App\Models\FC\FcJoiningSampleDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Admin CRUD for the joining "Sample Document" master.
 *
 * This only manages the downloadable sample/blank form shown against each joining
 * document. It does NOT touch candidate uploads or the dynamic-form save logic.
 */
class FcJoiningSampleDocumentController extends Controller
{
    private const STORAGE_DIR = 'joining_sample_documents';

    public function index(): View
    {
        $samples = FcJoiningSampleDocument::orderBy('display_order')->orderBy('document_title')->get();

        // Distinct joining-document fields available to attach a sample to.
        $docFields = DB::table('fc_form_fields')
            ->where('field_name', 'like', 'doc_%')
            ->where('field_type', 'file')
            ->whereNotNull('label')
            ->get(['field_name', 'label', 'section_heading'])
            ->groupBy('field_name')
            ->map(fn ($rows) => $rows->first())
            ->values()
            ->sortBy('field_name')
            ->values();

        return view('admin.sample-documents.index', compact('samples', 'docFields'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'field_name'     => 'required|string|max:100|unique:fc_joining_sample_documents,field_name',
            'document_title' => 'nullable|string|max:300',
            'section'        => 'nullable|string|max:200',
            'display_order'  => 'nullable|integer|min:0',
            'sample_file'    => 'required|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
        ]);

        [$path, $original] = $this->storeFile($request);

        FcJoiningSampleDocument::create([
            'field_name'           => $data['field_name'],
            'document_title'       => $data['document_title'] ?? $data['field_name'],
            'section'              => $data['section'] ?? null,
            'display_order'        => $data['display_order'] ?? ((FcJoiningSampleDocument::max('display_order') ?? 0) + 1),
            'sample_file_path'     => $path,
            'sample_original_name' => $original,
            'is_active'            => true,
        ]);

        return back()->with('success', 'Sample document added.');
    }

    public function update(Request $request, FcJoiningSampleDocument $sample): RedirectResponse
    {
        $data = $request->validate([
            'field_name'     => 'required|string|max:100|unique:fc_joining_sample_documents,field_name,' . $sample->id,
            'document_title' => 'nullable|string|max:300',
            'section'        => 'nullable|string|max:200',
            'display_order'  => 'nullable|integer|min:0',
            'is_active'      => 'nullable|boolean',
            'sample_file'    => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
        ]);

        $sample->field_name     = $data['field_name'];
        $sample->document_title = $data['document_title'] ?? $sample->document_title;
        $sample->section        = $data['section'] ?? $sample->section;
        $sample->display_order  = $data['display_order'] ?? $sample->display_order;
        $sample->is_active      = (bool) ($request->input('is_active', $sample->is_active));

        if ($request->hasFile('sample_file')) {
            $this->deleteStoredFile($sample->sample_file_path);
            [$path, $original] = $this->storeFile($request);
            $sample->sample_file_path     = $path;
            $sample->sample_original_name = $original;
        }

        $sample->save();

        return back()->with('success', 'Sample document updated.');
    }

    public function destroy(FcJoiningSampleDocument $sample): RedirectResponse
    {
        $this->deleteStoredFile($sample->sample_file_path);
        $sample->delete();

        return back()->with('success', 'Sample document removed.');
    }

    /**
     * Store the uploaded sample on the public disk.
     *
     * @return array{0:string,1:string} [web path usable by asset(), original name]
     */
    private function storeFile(Request $request): array
    {
        $file     = $request->file('sample_file');
        $original = $file->getClientOriginalName();
        $stored   = $file->store(self::STORAGE_DIR, 'public'); // joining_sample_documents/xxxx.pdf

        return ['storage/' . $stored, $original];
    }

    /**
     * Delete a previously-uploaded sample file (only ones we manage under storage/).
     * Seeded static files (admin_assets/...) are left untouched.
     */
    private function deleteStoredFile(?string $path): void
    {
        if ($path && str_starts_with($path, 'storage/')) {
            Storage::disk('public')->delete(substr($path, strlen('storage/')));
        }
    }
}
