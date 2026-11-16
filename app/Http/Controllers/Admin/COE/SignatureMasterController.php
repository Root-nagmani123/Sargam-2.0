<?php

namespace App\Http\Controllers\Admin\COE;

use App\Http\Controllers\Controller;
use App\Models\COE\SignatureMaster;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * COE → Document Management → Signature Master.
 *
 * The signatures printed at the foot of a marksheet. Client-side grid (CoeGrid)
 * like Scriber's Master: index() renders every row, the browser filters on Status.
 * Add / Edit / Deactivate / Delete are AJAX calls answered in JSON.
 *
 * Signature images sit on the private disk and are only served by file(), to a
 * signed-in user who holds this screen's permission — a signature is not public.
 *
 * Designation Name is picked from the Active HR Designation Master and Person Name
 * from the Active employees holding it (people()).
 */
class SignatureMasterController extends Controller
{
    public function index()
    {
        $rows = SignatureMaster::query()
            ->orderBy('designation_name')
            ->get()
            ->map(fn (SignatureMaster $s) => [
                'id' => (int) $s->pk,
                'designation' => $s->designation_name,
                'person' => $s->person_name,
                'active' => (bool) $s->active_inactive,
                'file_url' => route('admin.coe.signature-master.file', ['signature' => $s->pk]),
            ]);

        return view('admin.coe.signature_master.index', ['rows' => $rows, 'designations' => $this->designations()]);
    }

    /** Person Name options: the Active employees of ?designation=, by name. JSON. */
    public function people(Request $request): JsonResponse
    {
        return response()->json(['people' => $this->peopleOf((string) $request->query('designation', ''))]);
    }

    /** Add / Edit — one endpoint; a `pk` in the body makes it an edit. The image is optional on edit. */
    public function store(Request $request): JsonResponse
    {
        $id = $request->filled('pk') ? (int) $request->input('pk') : null;
        $existing = $id ? SignatureMaster::find($id) : null;

        if ($id && $existing === null) {
            return response()->json(['message' => 'That signature no longer exists.'], 404);
        }

        // An edit may keep the designation / person it already has, even when that is
        // no longer on offer (rows saved before the dropdowns, a deactivated designation).
        $designations = $this->designations();
        $people = $this->peopleOf((string) $request->input('designation_name'));
        if ($existing && trim((string) $request->input('designation_name')) === $existing->designation_name) {
            $designations[] = $existing->designation_name;
            $people[] = $existing->person_name;
        }

        // No SVG: it can carry script, and the image is shown inline.
        $validated = $request->validate([
            'designation_name' => ['required', 'string', 'max:100', Rule::in($designations)],
            'person_name' => ['required', 'string', 'max:150', Rule::in($people)],
            'signature' => [$existing ? 'nullable' : 'required', 'file', 'mimes:jpg,jpeg,png', 'max:2048'],
            'active_inactive' => ['required', 'in:0,1'],
        ], [
            'designation_name.required' => 'Please select the designation name.',
            'designation_name.in' => 'Select a designation from the list.',
            'person_name.required' => 'Please select the person name.',
            'person_name.in' => 'Select a person of this designation from the list.',
            'signature.required' => 'Please upload the signature.',
            'signature.mimes' => 'The signature must be a JPG or PNG image.',
            'signature.max' => 'The signature must not be larger than 2 MB.',
        ]);

        $record = $existing ?? new SignatureMaster;
        $oldPath = $record->signature_path;
        $now = now();

        $record->fill([
            'designation_name' => trim($validated['designation_name']),
            'person_name' => trim($validated['person_name']),
            'active_inactive' => (int) $validated['active_inactive'],
        ]);
        if ($request->hasFile('signature')) {
            $record->signature_path = $request->file('signature')->store(SignatureMaster::DIR, SignatureMaster::DISK);
        }
        if (! $record->exists) {
            $record->created_by = Auth::id();
            $record->created_date = $now;
        }
        $record->modified_by = Auth::id();
        $record->modified_date = $now;
        $record->save();

        // The old image goes only once the row points at the new one.
        if ($oldPath && $oldPath !== $record->signature_path) {
            Storage::disk(SignatureMaster::DISK)->delete($oldPath);
        }

        return response()->json(['message' => $existing ? 'Signature updated.' : 'Signature added.']);
    }

    public function toggleStatus(Request $request, int $signature): JsonResponse
    {
        $record = SignatureMaster::find($signature);
        if ($record === null) {
            return response()->json(['message' => 'That signature no longer exists.'], 404);
        }

        $record->active_inactive = $request->boolean('active') ? 1 : 0;
        $record->modified_by = Auth::id();
        $record->modified_date = now();
        $record->save();

        return response()->json(['message' => 'Signature '.($record->active_inactive ? 'activated.' : 'deactivated.')]);
    }

    public function destroy(int $signature): JsonResponse
    {
        $record = SignatureMaster::find($signature);
        if ($record === null) {
            return response()->json(['message' => 'That signature no longer exists.'], 404);
        }

        $path = $record->signature_path;
        $record->delete();
        Storage::disk(SignatureMaster::DISK)->delete($path);

        return response()->json(['message' => 'Signature deleted.']);
    }

    /** The signature image, inline, never cached. */
    public function file(int $signature): StreamedResponse
    {
        $record = SignatureMaster::findOrFail($signature);
        $disk = Storage::disk(SignatureMaster::DISK);
        abort_unless($disk->exists($record->signature_path), 404);

        return $disk->response($record->signature_path, null, [
            'Cache-Control' => 'no-store, private, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /** Names of the Active HR designations, A–Z (a name held twice is listed once). */
    private function designations(): array
    {
        return DB::table('designation_master')
            ->where('active_inactive', 1)
            ->orderBy('designation_name')
            ->pluck('designation_name')
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /** Full names of the Active employees (status 1) whose designation is $designation, A–Z. */
    private function peopleOf(string $designation): array
    {
        if (trim($designation) === '') {
            return [];
        }

        return DB::table('employee_master as e')
            ->join('designation_master as d', 'd.pk', '=', 'e.designation_master_pk')
            ->where('d.designation_name', trim($designation))
            ->where('d.active_inactive', 1)
            ->where('e.status', 1)
            ->get(['e.first_name', 'e.middle_name', 'e.last_name'])
            ->map(fn ($e) => implode(' ', array_filter(array_map(
                fn ($part) => trim((string) $part), [$e->first_name, $e->middle_name, $e->last_name]
            ), 'strlen')))
            ->filter()
            ->unique()
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }
}
