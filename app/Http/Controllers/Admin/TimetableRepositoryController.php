<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseMaster;
use App\Models\TimetableRepositoryDocument;
use App\Rules\SafeUploadedDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Timetable Repository (Setup → Time Table): course + week wise PDF library.
 *
 * Active / Archived follow the course lifecycle, exactly like the other Time Table
 * screens — Active lists documents of running courses, Archived those of courses
 * that have finished (CourseMaster::scopeActiveRunning / scopeArchived).
 */
class TimetableRepositoryController extends Controller
{
    /** PDFs only, 5 MB ceiling — the requirement's hard limit. */
    public const MAX_FILE_KB = 5120;

    /** Safety cap on the generated week list so a mis-keyed course date can't build 10k options. */
    private const MAX_WEEKS = 104;

    private const STORAGE_FOLDER = 'timetable-repository';

    /**
     * Server-side gate. Registered as constructor middleware (rather than a call in
     * each action) so a newly added action is covered automatically — the sidebar
     * hiding the link does nothing for a direct request.
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless(
                isSidebarPrivilegedUser() || (bool) (auth()->user()?->can('timetable_repository')),
                403,
                'You do not have permission to access the Timetable Repository.'
            );

            return $next($request);
        });
    }

    /**
     * Listing — Active / Archived tab decided by the course each document belongs to.
     */
    public function index(Request $request)
    {
        $status = $request->query('status') === 'archive' ? 'archive' : 'active';

        $courseIds = $status === 'archive'
            ? CourseMaster::archived()->pluck('pk')
            : CourseMaster::activeRunning()->pluck('pk');

        // Naya record hamesha sabse upar — isliye pk desc.
        $items = TimetableRepositoryDocument::with('course')
            ->whereIn('course_master_pk', $courseIds)
            ->orderBy('pk', 'desc')
            ->get();

        $counts = [
            'active'  => TimetableRepositoryDocument::whereIn('course_master_pk', CourseMaster::activeRunning()->select('pk'))->count(),
            'archive' => TimetableRepositoryDocument::whereIn('course_master_pk', CourseMaster::archived()->select('pk'))->count(),
        ];

        return view('admin.timetable-repository.index', compact('items', 'status', 'counts'));
    }

    public function create()
    {
        return view('admin.timetable-repository.form', [
            'item'           => null,
            'activeCourses'  => $this->courseOptions('active'),
            'archiveCourses' => $this->courseOptions('archive'),
            'weeks'          => [],
            'maxKb'          => SafeUploadedDocument::maxKilobytes(self::MAX_FILE_KB),
            'maxLabel'       => SafeUploadedDocument::maxLabel(self::MAX_FILE_KB),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validatePayload($request, true);

        $file = $request->file('document_file');
        $path = $file->storeAs(
            self::STORAGE_FOLDER . '/' . $validated['course_master_pk'],
            $this->buildFileName($file),
            'public'
        );

        TimetableRepositoryDocument::create([
            'document_name'    => $validated['document_name'],
            'course_master_pk' => $validated['course_master_pk'],
            'week_start'       => $validated['week_start'],
            'file_name'        => SafeUploadedDocument::safeDisplayName($file->getClientOriginalName(), 'document.pdf'),
            'file_path'        => $path,
            'file_size'        => $file->getSize(),
            'created_by'       => auth()->id(),
            'modified_by'      => auth()->id(),
        ]);

        return redirect()
            ->route('timetable-repository.index', ['status' => $this->statusOfCourse($validated['course_master_pk'])])
            ->with('success', 'Document uploaded successfully.');
    }

    public function edit(string $pk)
    {
        $item = TimetableRepositoryDocument::with('course')->findOrFail($pk);

        return view('admin.timetable-repository.form', [
            'item'           => $item,
            'activeCourses'  => $this->courseOptions('active'),
            'archiveCourses' => $this->courseOptions('archive'),
            'weeks'          => $this->courseWeeks($item->course),
            'maxKb'          => SafeUploadedDocument::maxKilobytes(self::MAX_FILE_KB),
            'maxLabel'       => SafeUploadedDocument::maxLabel(self::MAX_FILE_KB),
        ]);
    }

    public function update(Request $request, string $pk)
    {
        $item = TimetableRepositoryDocument::findOrFail($pk);

        // File stays optional on edit — only the details change unless a new PDF is picked.
        $validated = $this->validatePayload($request, false);

        $payload = [
            'document_name'    => $validated['document_name'],
            'course_master_pk' => $validated['course_master_pk'],
            'week_start'       => $validated['week_start'],
            'modified_by'      => auth()->id(),
        ];

        if ($request->hasFile('document_file')) {
            $file = $request->file('document_file');
            $oldPath = $item->file_path;

            $payload['file_path'] = $file->storeAs(
                self::STORAGE_FOLDER . '/' . $validated['course_master_pk'],
                $this->buildFileName($file),
                'public'
            );
            $payload['file_name'] = SafeUploadedDocument::safeDisplayName($file->getClientOriginalName(), 'document.pdf');
            $payload['file_size'] = $file->getSize();

            if (filled($oldPath) && Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        $item->update($payload);

        return redirect()
            ->route('timetable-repository.index', ['status' => $this->statusOfCourse($validated['course_master_pk'])])
            ->with('success', 'Document updated successfully.');
    }

    public function destroy(Request $request, string $pk)
    {
        $item = TimetableRepositoryDocument::findOrFail($pk);
        $status = $this->statusOfCourse((int) $item->course_master_pk);

        if (filled($item->file_path) && Storage::disk('public')->exists($item->file_path)) {
            Storage::disk('public')->delete($item->file_path);
        }

        $item->delete();

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Document deleted successfully.']);
        }

        return redirect()
            ->route('timetable-repository.index', ['status' => $status])
            ->with('success', 'Document deleted successfully.');
    }

    /**
     * Stream the PDF back under its original name (files live on the public disk,
     * but going through the route keeps the name and the access check).
     */
    public function download(string $pk)
    {
        $item = TimetableRepositoryDocument::findOrFail($pk);

        abort_unless($item->fileExists(), 404, 'The uploaded file is no longer available.');

        return Storage::disk('public')->download($item->file_path, $item->file_name);
    }

    /**
     * Week options for a course — used by the Course dropdown's change handler.
     */
    public function weeks(Request $request)
    {
        $course = CourseMaster::find($request->query('course_master_pk'));

        if (! $course) {
            return response()->json(['weeks' => [], 'message' => 'Select a course first.'], 422);
        }

        return response()->json(['weeks' => $this->courseWeeks($course)]);
    }

    // ── internals ────────────────────────────────────────────────────────────

    /**
     * Shared validation for store/update, including the check that the submitted
     * week actually belongs to the submitted course (the dropdown is rebuilt over
     * AJAX, so the pair has to be re-verified server side).
     */
    private function validatePayload(Request $request, bool $fileRequired): array
    {
        $rules = [
            'document_name'    => 'required|string|max:255',
            'course_master_pk' => 'required|integer|exists:course_master,pk',
            'week_start'       => 'required|date',
            // Same composition the other document uploads use (CWE-434): `mimes` is
            // the cheap first pass, SafeUploadedDocument verifies magic bytes vs the
            // detected MIME, and `max` is clamped to what PHP will really accept so an
            // oversized file gives a validation message instead of a dropped request.
            'document_file'    => [
                $fileRequired ? 'required' : 'nullable',
                'file',
                'mimes:pdf',
                'max:' . SafeUploadedDocument::maxKilobytes(self::MAX_FILE_KB),
                new SafeUploadedDocument(['pdf']),
            ],
        ];

        $validated = $request->validate($rules, [
            'document_file.max'   => 'The PDF may not be larger than ' . SafeUploadedDocument::maxLabel(self::MAX_FILE_KB) . '.',
            'document_file.mimes' => 'Only PDF files can be uploaded.',
            'week_start.required' => 'Please select a week.',
        ]);

        $course = CourseMaster::findOrFail($validated['course_master_pk']);
        $weekStart = Carbon::parse($validated['week_start'])->startOfWeek(Carbon::MONDAY)->toDateString();

        $allowed = collect($this->courseWeeks($course))->pluck('value')->all();
        if (! in_array($weekStart, $allowed, true)) {
            throw ValidationException::withMessages([
                'week_start' => 'The selected week does not belong to this course.',
            ]);
        }

        $validated['week_start'] = $weekStart;

        return $validated;
    }

    /**
     * Weeks of a course as dropdown options: Monday date + "Week N (dd Mon – dd Mon yyyy)".
     */
    private function courseWeeks(?CourseMaster $course): array
    {
        $anchor = TimetableRepositoryDocument::courseWeekAnchor($course);

        if (! $anchor) {
            return [];
        }

        $end = $course->end_date
            ? Carbon::parse($course->end_date)
            : $anchor->copy()->endOfYear();

        $last = $end->lt($anchor) ? $anchor->copy() : $end->copy()->startOfWeek(Carbon::MONDAY);

        $weeks = [];
        $cursor = $anchor->copy();

        for ($n = 1; $cursor->lte($last) && $n <= self::MAX_WEEKS; $n++) {
            $weekEnd = $cursor->copy()->endOfWeek(Carbon::SUNDAY);

            $weeks[] = [
                'value'  => $cursor->toDateString(),
                'number' => $n,
                'label'  => 'Week ' . $n . ' (' . $cursor->format('d M') . ' – ' . $weekEnd->format('d M Y') . ')',
            ];

            $cursor->addWeek();
        }

        return $weeks;
    }

    /** Course dropdown options for one bucket. */
    private function courseOptions(string $bucket)
    {
        $query = $bucket === 'archive' ? CourseMaster::archived() : CourseMaster::activeRunning();

        return $query->select('pk', 'course_name', 'couse_short_name', 'course_year')
            ->orderBy('course_name')
            ->get();
    }

    /** Which tab a course's documents land on, so redirects return to the right list. */
    private function statusOfCourse(int $coursePk): string
    {
        return CourseMaster::activeRunning()->where('pk', $coursePk)->exists() ? 'active' : 'archive';
    }

    /** Unique, safe storage name that still hints at the uploaded file. */
    private function buildFileName($file): string
    {
        $base = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $base = Str::limit($base ?: 'document', 60, '');

        return $base . '-' . now()->format('YmdHis') . '-' . Str::random(6) . '.pdf';
    }
}
