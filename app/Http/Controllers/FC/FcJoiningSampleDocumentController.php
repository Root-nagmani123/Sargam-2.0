<?php

namespace App\Http\Controllers\FC;

use App\Http\Controllers\Controller;
use App\Models\FC\FcJoiningSampleDocument;
use App\Rules\SafeUploadedDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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

    /** Ceiling we want for a blank sample form; trimmed to php.ini's real limit. */
    private const DESIRED_MAX_KB = 10240;

    /** Document types an admin may attach as a downloadable sample. */
    private const ALLOWED_TYPES = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];

    /**
     * Upload rules shared by store() and update() (CWE-434).
     *
     * `mimes` is the cheap first pass; SafeUploadedDocument is the one that
     * actually decides, by verifying the file's magic bytes and detected MIME
     * against each other and rejecting script extensions anywhere in the
     * submitted name. `max` is clamped to what PHP will really accept so an
     * oversized file produces a validation message instead of a dropped request.
     *
     * @return array<int, mixed>
     */
    private function fileRules(bool $required): array
    {
        return [
            $required ? 'required' : 'nullable',
            'file',
            'mimes:' . implode(',', self::ALLOWED_TYPES),
            'max:' . SafeUploadedDocument::maxKilobytes(self::DESIRED_MAX_KB),
            new SafeUploadedDocument(self::ALLOWED_TYPES),
        ];
    }

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
            'sample_file'    => $this->fileRules(true),
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
            'sample_file'    => $this->fileRules(false),
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
     * Both halves of the name are decided by the server: a random basename, and
     * an extension taken from the signature SafeUploadedDocument verified — not
     * from the client filename and not from the MIME guesser (which can widen a
     * .docx to .zip). The client's name is kept only as a sanitised display
     * label. Validation has already run, so the signature always matches here;
     * the fallback is a defensive no-op.
     *
     * @return array{0:string,1:string} [web path usable by asset(), display name]
     */
    private function storeFile(Request $request): array
    {
        $file      = $request->file('sample_file');
        $extension = SafeUploadedDocument::canonicalExtension($file, self::ALLOWED_TYPES) ?? 'dat';
        $display   = SafeUploadedDocument::safeDisplayName($file->getClientOriginalName(), 'sample.' . $extension);

        $stored = $file->storeAs(
            self::STORAGE_DIR,
            Str::random(40) . '.' . $extension,
            'public'
        ); // joining_sample_documents/<random>.pdf

        return ['storage/' . $stored, $display];
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
