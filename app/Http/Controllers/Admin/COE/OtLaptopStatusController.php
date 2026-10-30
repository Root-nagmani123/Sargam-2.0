<?php

namespace App\Http\Controllers\Admin\COE;

use App\Exports\COE\LaptopStatusTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\COE\LaptopStatusImport;
use App\Models\COE\LaptopResponse;
use App\Models\COE\ScriberMaster;
use App\Services\COE\LaptopDeclarationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

/**
 * COE → Laptop Management → OT Laptop Status.
 *
 * Records, per Officer Trainee, whether they need a laptop for the exam and —
 * for an OT with a disability — whether they need a scriber.
 *
 * ── The grid lists answers, not participants ──
 * A row here IS a coe_laptop_responses row. An OT who has not declared yet has no row
 * and does not appear; they appear in the Add Single Record dialog's OT picker instead,
 * which is where an administrator records the missing answer. Course, batch, service and
 * roll number are joined from student_master / coe_roll_numbers, never copied.
 *
 * The grid is client-side (CoeGrid): index() renders every row for the scope and the
 * browser filters, sorts and pages them. Every write is an AJAX call that answers in
 * JSON, and the page reloads or removes rows only after the server says it worked.
 *
 * Two scopes, each its own URL (docs/new-design-index-page.md §1 "Scope tabs"), split
 * on the COURSE's lifecycle — the same rule as CourseMaster::scopeActiveRunning() /
 * scopeArchived():
 *   ?scope=active    running courses (default)
 *   ?scope=archived  finished or disabled courses
 */
class OtLaptopStatusController extends Controller
{
    public const SCOPES = ['active', 'archived'];

    /** Bulk import limits — the dropzone, the hint and the upload rule all read these. */
    public const IMPORT_EXTENSIONS = ['xlsx', 'xls', 'csv'];

    public const IMPORT_MAX_MB = 5;

    public function index(Request $request)
    {
        $scope = $this->resolveScope($request);

        $rows = $this->listQuery($scope)->get()->map(fn (LaptopResponse $row) => [
            'id' => (int) $row->pk,
            'roll' => (string) ($row->roll_number ?: '—'),
            'name' => $this->studentName($row),
            'code' => (string) ($row->generated_OT_code ?: '—'),
            'course' => $this->courseLabel($row),
            'service' => (string) ($row->service_short_name ?: $row->service_name ?: ''),
            'batch' => (string) ($row->batch ?: '—'),
            'laptop_required' => (bool) $row->laptop_required,
            'reason' => $row->reason,
            'disability' => (bool) $row->has_disability,
            'disability_type' => LaptopResponse::disabilityTypeLabel($row->disability_type),
            // NULL = the scriber question was never put (no disability), shown as "—".
            'scriber' => $row->scriber_required,
            'scriber_remarks' => $row->scriber_remarks,
            'primary_scriber' => $row->primary_scriber_name,
            'secondary_scriber' => $row->secondary_scriber_name,
            'additional_remarks' => $row->additional_remarks,
            'updated_by' => LaptopResponse::sourceLabel((int) $row->source),
        ]);

        // The toolbar filters match the text the grid shows, so their options are taken
        // from the rows themselves — an option that can never match a row is not offered.
        return view('admin.coe.ot_laptop_status.index', [
            'scope' => $scope,
            'rows' => $rows,
            'courses' => $rows->pluck('course')->unique()->sort()->values(),
            'services' => $rows->pluck('service')->filter()->unique()->sort()->values(),
            'batches' => $rows->pluck('batch')->unique()->sortDesc()->values(),
            'singleCourses' => $this->singleCourseOptions(),
            'disabilityTypes' => LaptopResponse::disabilityTypes(),
            'scribers' => $this->scriberOptions(),
            'importExtensions' => self::IMPORT_EXTENSIONS,
            'importMaxMb' => self::IMPORT_MAX_MB,
        ]);
    }

    /**
     * The OTs of one course, for the Add Single Record pickers.
     *
     * Fetched per course rather than rendered up front: every trainee in the academy would
     * bloat the page for a dialog that is usually not opened. Each OT carries their
     * existing answer, if any, so the dialog can pre-fill it rather than overwrite blind,
     * and so the Scribers Details list can offer only the OTs with a disability type.
     */
    public function participants(Request $request): JsonResponse
    {
        $courseId = (int) $request->query('course', 0);

        if ($courseId <= 0) {
            return response()->json(['data' => []]);
        }

        $rows = DB::table('student_master as sm')
            ->leftJoin('coe_roll_numbers as rn', 'rn.student_master_pk', '=', 'sm.pk')
            ->leftJoin('coe_laptop_responses as lr', 'lr.student_master_pk', '=', 'sm.pk')
            ->where('sm.course_master_pk', $courseId)
            // Roll-number order, the not-yet-numbered at the end — the grid's own order.
            ->orderByRaw('rn.serial_number is null, rn.serial_number')
            ->orderBy('sm.display_name')
            ->get([
                'sm.pk',
                'sm.display_name',
                'sm.first_name',
                'sm.middle_name',
                'sm.last_name',
                'sm.generated_OT_code',
                'rn.roll_number',
                'lr.pk as response_pk',
                'lr.laptop_required',
                'lr.reason',
                'lr.has_disability',
                'lr.disability_type',
                'lr.scriber_required',
                'lr.scriber_remarks',
                'lr.primary_scriber_pk',
                'lr.secondary_scriber_pk',
                'lr.additional_remarks',
            ]);

        return response()->json([
            'data' => $rows->map(fn ($row) => [
                'pk' => (int) $row->pk,
                'name' => $this->studentName($row),
                'code' => (string) ($row->generated_OT_code ?? ''),
                'roll_number' => $row->roll_number,
                'answered' => $row->response_pk !== null,
                'laptop_required' => $row->response_pk === null ? null : (int) $row->laptop_required,
                'reason' => (string) ($row->reason ?? ''),
                'has_disability' => $row->response_pk === null ? null : (int) $row->has_disability,
                'disability_type' => $row->disability_type === null ? null : (int) $row->disability_type,
                // Stays NULL where the scriber question was never asked.
                'scriber_required' => $row->scriber_required === null ? null : (int) $row->scriber_required,
                'scriber_remarks' => (string) ($row->scriber_remarks ?? ''),
                'primary_scriber_pk' => $row->primary_scriber_pk === null ? null : (int) $row->primary_scriber_pk,
                'secondary_scriber_pk' => $row->secondary_scriber_pk === null ? null : (int) $row->secondary_scriber_pk,
                'additional_remarks' => (string) ($row->additional_remarks ?? ''),
            ])->all(),
        ]);
    }

    /**
     * "Add Single Record" — one answer recorded for one or more OTs of a course, each
     * created or corrected.
     *
     * Validation failures come back as Laravel's 422 JSON, keyed by the field names the
     * dialog posts, so the browser can put each message under its own control.
     */
    public function store(Request $request, LaptopDeclarationService $declarations): JsonResponse
    {
        $validated = $request->validate(array_merge([
            'course' => ['required', 'integer', 'exists:course_master,pk'],
            'student_master_pks' => ['required', 'array', 'min:1'],
            'student_master_pks.*' => ['integer', 'distinct'],
        ], $declarations->rules(), $declarations->scriberRules()), array_merge([
            'course.required' => 'Please select a course.',
            'student_master_pks.required' => 'Please select at least one OT.',
            'student_master_pks.min' => 'Please select at least one OT.',
        ], $declarations->messages(), $declarations->scriberMessages()));

        $studentPks = array_map('intval', $validated['student_master_pks']);

        // Re-checked server-side rather than trusted from the dialog: the fields arrive
        // separately, and a hand-edited OT id would otherwise attach an answer to someone
        // on a different course entirely. All or nothing — one stray id saves none.
        if (! $this->participantsBelongTo($studentPks, (int) $validated['course'])) {
            $message = count($studentPks) === 1
                ? 'That OT is not on the selected course.'
                : 'One or more of those OTs are not on the selected course.';

            return response()->json(['message' => $message, 'errors' => ['student_master_pks' => [$message]]], 422);
        }

        $hasDisability = (int) $validated['has_disability'] === LaptopResponse::HAS_DISABILITY_YES;

        // With a disability, each OT's Disability Type comes from its own Scribers
        // Details row — so every OT being recorded has to be in the table.
        $typeFor = [];
        if ($hasDisability) {
            foreach ($validated['scribers'] ?? [] as $row) {
                $typeFor[(int) $row['student_master_pk']] = (int) $row['disability_type'];
            }
            if (array_diff($studentPks, array_keys($typeFor)) !== []) {
                $message = 'Add every selected OT to Scribers Details with its disability type.';

                return response()->json(['message' => $message, 'errors' => ['scribers' => [$message]]], 422);
            }
        }

        $existing = LaptopResponse::whereIn('student_master_pk', $studentPks)->count();

        // Scribers Details is only on the dialog while "OT with Disabilities" is Yes, and
        // is only written then. One transaction, so a rejected row also undoes every answer.
        $writesScribers = $hasDisability && ($validated['scribers_present'] ?? null) === '1';

        DB::transaction(function () use ($declarations, $validated, $studentPks, $typeFor, $writesScribers) {
            foreach ($studentPks as $studentPk) {
                $declarations->record(
                    $studentPk,
                    $validated + ['disability_type' => $typeFor[$studentPk] ?? null],
                    LaptopResponse::SOURCE_ADMIN
                );
            }

            if ($writesScribers) {
                $declarations->assignScribers((int) $validated['course'], $validated['scribers'] ?? []);
            }
        });

        $added = count($studentPks) - $existing;
        $parts = array_filter([
            $added > 0 ? $added . ' saved' : null,
            $existing > 0 ? $existing . ' updated' : null,
        ]);

        return response()->json([
            'message' => count($studentPks) === 1
                ? ($existing ? 'Laptop status updated.' : 'Laptop status saved.')
                : 'Laptop status for ' . count($studentPks) . ' OTs: ' . implode(', ', $parts) . '.',
        ]);
    }

    /**
     * "Import In Bulk" — the filled-in template.
     *
     * Answers 200 whenever the file was read, with the counts and every rejected row's
     * reason; the browser decides how to present a partial import. A file that could not
     * be read at all is a 422, never a success.
     */
    public function import(Request $request, LaptopDeclarationService $declarations): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:' . (self::IMPORT_MAX_MB * 1024)],
        ], [
            'file.required' => 'Choose an .xlsx, .xls or .csv file first.',
            'file.mimes' => 'The file must be .xlsx, .xls or .csv.',
            'file.max' => 'The file must be ' . self::IMPORT_MAX_MB . ' MB or smaller.',
        ]);

        $import = new LaptopStatusImport($declarations);

        try {
            Excel::import($import, $request->file('file'));
        } catch (\Throwable $e) {
            // The library's own message names internal classes and sheet offsets, which
            // tells an administrator nothing they can act on.
            Log::error('COE laptop status import failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'That file could not be read. Download the template and check the column headings.',
            ], 422);
        }

        return response()->json([
            'message' => $this->importSummary($import),
            'added' => $import->importedCount,
            'updated' => $import->updatedCount,
            'skipped' => $import->skippedCount,
            'failures' => $import->failures,
        ]);
    }

    /**
     * The .xlsx an administrator fills in before importing.
     *
     * Pre-filled with the OTs of the courses in the current scope, because
     * generated_OT_code is NOT unique across the academy — the import matches on the
     * Participant ID the template carries, so a blank template would be unusable.
     */
    public function importTemplate(Request $request)
    {
        $scope = $this->resolveScope($request);
        $today = now()->toDateString();

        $participants = DB::table('student_master')
            ->join('course_master', 'course_master.pk', '=', 'student_master.course_master_pk')
            ->leftJoin('coe_roll_numbers', 'coe_roll_numbers.student_master_pk', '=', 'student_master.pk')
            ->leftJoin('coe_laptop_responses', 'coe_laptop_responses.student_master_pk', '=', 'student_master.pk')
            ->where(fn ($q) => $this->applyScope($q, $scope, $today))
            ->orderByRaw('coe_roll_numbers.serial_number is null, coe_roll_numbers.serial_number')
            ->orderBy('student_master.display_name')
            ->get([
                'student_master.pk',
                'student_master.display_name',
                'student_master.generated_OT_code',
                'course_master.course_name',
                'student_master.batch',
                'coe_roll_numbers.roll_number',
                'coe_laptop_responses.laptop_required',
                'coe_laptop_responses.reason',
            ]);

        return Excel::download(
            new LaptopStatusTemplateExport($participants),
            'OT_Laptop_Status_Template_' . now()->format('YmdHis') . '.xlsx'
        );
    }

    /** Remove one OT's laptop status. The participant is untouched. */
    public function destroy(int $response): JsonResponse
    {
        $record = LaptopResponse::find($response);
        if ($record === null) {
            return response()->json(['message' => 'That laptop status no longer exists.'], 404);
        }

        $record->delete();

        return response()->json(['message' => 'Laptop status deleted.']);
    }

    /**
     * Delete Selected — every ticked row, in one statement.
     *
     * Returns the ids that were actually deleted so the browser removes exactly those
     * rows, not the ones it asked for.
     */
    public function bulkDestroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ], [
            'ids.required' => 'Select at least one row to delete.',
        ]);

        $ids = LaptopResponse::whereIn('pk', $validated['ids'])->pluck('pk')->map(fn ($pk) => (int) $pk)->all();

        if ($ids === []) {
            return response()->json(['message' => 'Nothing was deleted — those rows no longer exist.'], 404);
        }

        $deleted = LaptopResponse::whereIn('pk', $ids)->delete();

        return response()->json([
            'message' => $deleted . ' laptop ' . ($deleted === 1 ? 'status' : 'statuses') . ' deleted.',
            'deleted' => $ids,
        ]);
    }

    // ── Queries ─────────────────────────────────────────────────────────────

    /**
     * The grid's query: one row per recorded laptop status.
     *
     * Every join is one-to-at-most-one — student_master.pk is the key,
     * coe_roll_numbers.student_master_pk is UNIQUE and service_master is a lookup — so
     * none can fan the result out and the row count stays the answer count.
     */
    private function listQuery(string $scope): Builder
    {
        $today = now()->toDateString();

        return LaptopResponse::query()
            ->select([
                'coe_laptop_responses.*',
                'student_master.display_name',
                'student_master.first_name',
                'student_master.middle_name',
                'student_master.last_name',
                'student_master.generated_OT_code',
                'student_master.batch',
                'course_master.course_name',
                'course_master.couse_short_name',
                'service_master.service_name',
                'service_master.service_short_name',
                'coe_roll_numbers.roll_number',
                'primary_scriber.scriber_name as primary_scriber_name',
                'secondary_scriber.scriber_name as secondary_scriber_name',
            ])
            ->join('student_master', 'student_master.pk', '=', 'coe_laptop_responses.student_master_pk')
            ->leftJoin('coe_scriber_master as primary_scriber', 'primary_scriber.pk', '=', 'coe_laptop_responses.primary_scriber_pk')
            ->leftJoin('coe_scriber_master as secondary_scriber', 'secondary_scriber.pk', '=', 'coe_laptop_responses.secondary_scriber_pk')
            ->join('course_master', 'course_master.pk', '=', 'student_master.course_master_pk')
            ->leftJoin('service_master', 'service_master.pk', '=', 'student_master.service_master_pk')
            ->leftJoin('coe_roll_numbers', 'coe_roll_numbers.student_master_pk', '=', 'student_master.pk')
            ->where(fn ($q) => $this->applyScope($q, $scope, $today))
            ->orderByRaw('coe_roll_numbers.roll_number is null, coe_roll_numbers.roll_number')
            ->orderBy('student_master.display_name')
            ->orderBy('coe_laptop_responses.pk');
    }

    /**
     * Active / Archived on course_master — CourseMaster::scopeActiveRunning() and its
     * exact complement scopeArchived(), restated on the joined table name because those
     * scopes use unqualified columns, which are ambiguous once student_master is joined
     * (both tables have active_inactive).
     *
     * @param  \Illuminate\Database\Query\Builder|Builder  $q
     */
    private function applyScope($q, string $scope, string $today): void
    {
        if ($scope === 'archived') {
            $q->where('course_master.active_inactive', '!=', 1)
                ->orWhereNull('course_master.active_inactive')
                ->orWhere('course_master.end_date', '<', $today)
                ->orWhereNull('course_master.end_date');

            return;
        }

        $q->where('course_master.active_inactive', 1)
            ->where('course_master.end_date', '>=', $today);
    }

    /**
     * @param  array<int, int>  $studentPks  distinct (validated)
     */
    private function participantsBelongTo(array $studentPks, int $courseId): bool
    {
        return DB::table('student_master')
            ->whereIn('pk', $studentPks)
            ->where('course_master_pk', $courseId)
            ->count() === count($studentPks);
    }

    /**
     * Courses the Add Single Record dialog offers: every course with at least one OT.
     *
     * Not restricted to courses already in the grid — the dialog exists to record an
     * answer that is missing, so that would make the first entry for a course impossible.
     */
    private function singleCourseOptions(): Collection
    {
        return DB::table('student_master')
            ->join('course_master', 'course_master.pk', '=', 'student_master.course_master_pk')
            ->select('course_master.pk', 'course_master.course_name', 'course_master.couse_short_name')
            ->distinct()
            ->orderBy('course_master.course_name')
            ->get();
    }

    /**
     * Every scriber, for the Scribers Details dropdowns. Inactive ones are included so an
     * existing assignment still shows its name; the dialog offers them only in a row
     * that already holds them.
     *
     * @return array<int, array<string, mixed>>
     */
    private function scriberOptions(): array
    {
        return ScriberMaster::query()
            ->orderBy('scriber_name')
            ->get(['pk', 'scriber_name', 'scriber_role', 'active_inactive'])
            ->map(fn (ScriberMaster $s) => [
                'pk' => (int) $s->pk,
                'name' => $s->scriber_name,
                'role' => (int) $s->scriber_role,
                'active' => (bool) $s->active_inactive,
            ])
            ->all();
    }

    private function courseLabel(object $row): string
    {
        return (string) ($row->couse_short_name ?: $row->course_name ?: '—');
    }

    private function studentName(object $student): string
    {
        $display = trim(preg_replace('/\s+/', ' ', (string) $student->display_name));
        if ($display !== '') {
            return $display;
        }

        $parts = array_filter([$student->first_name, $student->middle_name, $student->last_name]);

        return trim(preg_replace('/\s+/', ' ', implode(' ', $parts))) ?: '—';
    }

    private function importSummary(LaptopStatusImport $import): string
    {
        $parts = [];

        if ($import->importedCount > 0) {
            $parts[] = $import->importedCount . ' added';
        }
        if ($import->updatedCount > 0) {
            $parts[] = $import->updatedCount . ' updated';
        }
        if ($import->skippedCount > 0) {
            $parts[] = $import->skippedCount . ' skipped (no answer in the file)';
        }

        $message = $parts === [] ? 'Nothing was imported.' : ucfirst(implode(', ', $parts)) . '.';

        return $import->failures === []
            ? $message
            : $message . ' ' . count($import->failures) . ' row(s) rejected.';
    }

    private function resolveScope(Request $request): string
    {
        $scope = $request->query('scope');

        return in_array($scope, self::SCOPES, true) ? $scope : 'active';
    }
}
