<?php

namespace App\Http\Controllers\Admin\COE;

use App\DataTables\ExaminationDriveDataTable;
use App\DataTables\ExaminationDriveFacultyDataTable;
use App\Http\Controllers\Controller;
use App\Models\ComponentMaster;
use App\Models\CourseMaster;
use App\Models\ExaminationDrive;
use App\Models\ExaminationDriveComponentMap;
use App\Models\ExaminationDriveFacultyMap;
use App\Models\ExaminationTypeMaster;
use App\Models\FacultyMaster;
use App\Models\SubjectMaster;
use App\Models\TermMaster;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * COE → Examination Management → Examination Drive Configuration.
 *
 * Design-first screen. Sub-pages:
 *   index          — drive list (Active / Archived tabs)
 *   show/{id}      — Map Faculty to Examination Drive (editable component table)
 *   coordinates/{id} — Faculty Coordinates & Components (view with Assign Faculty modal)
 */
class ExaminationDriveController extends Controller
{
    public const SCOPES       = ['active', 'archived'];
    public const STATUSES     = ['Draft', 'Pending at COE', 'Active', 'Closed'];
    public const EXAM_TYPES   = ['Written Test', 'Practical', 'Viva', 'Assignment'];
    public const TERMS        = ['Annual Term', 'Semester 1', 'Semester 2', 'Term I', 'Term II'];
    public const PHASES       = ['Phase-IV 2025', 'Phase-III 2025', 'Phase-II 2024'];
    public const COURSES      = ['Phase IV 2025', 'Phase III 2025', 'Foundation Course 2026'];
    public const SOURCES      = ['Faculty', 'Sargam', 'Sargam 2.0'];
    public const EXAM_MODES   = ['Online', 'Offline'];


    public function index(Request $request, ExaminationDriveDataTable $dataTable)
    {
        $scope = in_array($request->query('scope'), self::SCOPES, true)
            ? $request->query('scope') : 'active';

        return $dataTable->render('admin.coe.examination_drive.index', [
            'scope'     => $scope,
            'courses'   => $this->courseNames(),
            'terms'     => $this->termNames(),
            'phases'    => $this->phaseValues(),
            'examTypes' => $this->examTypeNames(),
            'statuses'  => ExaminationDrive::STATUSES,
            'subjects'  => $this->subjectNames(),
            'faculty'   => $this->facultyNames(),
        ]);
    }

    /** Persist a new drive + its Subject/Faculty mapping rows. */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'drive_name'       => ['required', 'string', 'max:150'],
            'course'           => ['required', 'string', Rule::exists('course_master', 'course_name')],
            'exam_type'        => ['required', 'string', Rule::exists('examination_type_master', 'exam_type_name')],
            'term'             => ['required', 'string', Rule::exists('term_master', 'term_name')],
            'academic_session' => ['required', 'string', 'max:10'],
            'status'           => ['required', Rule::in(ExaminationDrive::STATUSES)],
            'start_date'       => ['required', 'date'],
            'end_date'         => ['required', 'date', 'after_or_equal:start_date'],
            'mappings'              => ['array'],
            'mappings.*.subject'    => ['required_with:mappings.*.faculty', 'nullable', 'string', 'max:150'],
            'mappings.*.faculty'    => ['required_with:mappings.*.subject', 'nullable', 'string', 'max:150'],
        ]);

        $examTypePk = ExaminationTypeMaster::where('exam_type_name', $data['exam_type'])->value('pk');
        $termPk     = TermMaster::where('term_name', $data['term'])->value('pk');
        $coursePk   = CourseMaster::where('course_name', $data['course'])->value('pk');
        $statusCode = array_search($data['status'], ExaminationDrive::STATUSES, true);

        $drive = DB::transaction(function () use ($data, $examTypePk, $termPk, $coursePk, $statusCode) {
            $drive = ExaminationDrive::create([
                'drive_name'                 => $data['drive_name'],
                'examination_type_master_pk' => $examTypePk,
                'term_master_pk'             => $termPk,
                'course_master_pk'           => $coursePk,
                'academic_session'           => $data['academic_session'],
                // `phase` is NOT NULL in the schema but the Create form has no
                // separate Phase field; the academic session is the closest
                // real value until the UI grows one.
                'phase'                      => $data['academic_session'],
                'status'                     => $statusCode,
                'start_date'                 => $data['start_date'],
                'end_date'                   => $data['end_date'],
                'created_by'                 => auth()->id(),
                'created_date'               => now(),
            ]);

            foreach ($data['mappings'] ?? [] as $row) {
                if (empty($row['subject']) || empty($row['faculty'])) {
                    continue;
                }
                // faculty_master.full_name often carries leading/trailing whitespace; the
                // dropdown options are trimmed, so match the same way here.
                $subjectPk = SubjectMaster::whereRaw('TRIM(subject_name) = ?', [trim($row['subject'])])->value('pk');
                $facultyPk = FacultyMaster::whereRaw('TRIM(full_name) = ?', [trim($row['faculty'])])->value('pk');
                if (!$subjectPk || !$facultyPk) {
                    continue;
                }
                ExaminationDriveFacultyMap::create([
                    'examination_drive_id' => $drive->id,
                    'subject_master_pk'    => $subjectPk,
                    'faculty_master_pk'    => $facultyPk,
                    'created_date'         => now(),
                ]);
            }

            return $drive;
        });

        return response()->json(['id' => $drive->id, 'message' => 'Examination drive created.']);
    }

    /** Update an existing drive + replace its Subject/Faculty mapping rows. */
    public function update(Request $request, int $id): JsonResponse
    {
        $drive = ExaminationDrive::findOrFail($id);
        abort_if($drive->status === ExaminationDrive::STATUS_CLOSED, 422, 'A closed drive cannot be edited.');

        $data = $request->validate([
            'course'           => ['required', 'string', Rule::exists('course_master', 'course_name')],
            'exam_type'        => ['required', 'string', Rule::exists('examination_type_master', 'exam_type_name')],
            'term'             => ['required', 'string', Rule::exists('term_master', 'term_name')],
            'academic_session' => ['required', 'string', 'max:10'],
            'status'           => ['required', Rule::in(ExaminationDrive::STATUSES)],
            'start_date'       => ['required', 'date'],
            'end_date'         => ['required', 'date', 'after_or_equal:start_date'],
            'mappings'              => ['array'],
            'mappings.*.subject'    => ['required_with:mappings.*.faculty', 'nullable', 'string', 'max:150'],
            'mappings.*.faculty'    => ['required_with:mappings.*.subject', 'nullable', 'string', 'max:150'],
        ]);

        $examTypePk = ExaminationTypeMaster::where('exam_type_name', $data['exam_type'])->value('pk');
        $termPk     = TermMaster::where('term_name', $data['term'])->value('pk');
        $coursePk   = CourseMaster::where('course_name', $data['course'])->value('pk');
        $statusCode = array_search($data['status'], ExaminationDrive::STATUSES, true);

        DB::transaction(function () use ($drive, $data, $examTypePk, $termPk, $coursePk, $statusCode) {
            $drive->update([
                'examination_type_master_pk' => $examTypePk,
                'term_master_pk'             => $termPk,
                'course_master_pk'           => $coursePk,
                'academic_session'           => $data['academic_session'],
                'phase'                      => $data['academic_session'],
                'status'                     => $statusCode,
                'start_date'                 => $data['start_date'],
                'end_date'                   => $data['end_date'],
                'modified_date'              => now(),
            ]);

            ExaminationDriveFacultyMap::where('examination_drive_id', $drive->id)->delete();
            foreach ($data['mappings'] ?? [] as $row) {
                if (empty($row['subject']) || empty($row['faculty'])) {
                    continue;
                }
                $subjectPk = SubjectMaster::whereRaw('TRIM(subject_name) = ?', [trim($row['subject'])])->value('pk');
                $facultyPk = FacultyMaster::whereRaw('TRIM(full_name) = ?', [trim($row['faculty'])])->value('pk');
                if (!$subjectPk || !$facultyPk) {
                    continue;
                }
                ExaminationDriveFacultyMap::create([
                    'examination_drive_id' => $drive->id,
                    'subject_master_pk'    => $subjectPk,
                    'faculty_master_pk'    => $facultyPk,
                    'created_date'         => now(),
                ]);
            }
        });

        return response()->json(['id' => $drive->id, 'message' => 'Examination drive updated.']);
    }

    /** Delete a drive (its component/faculty mapping rows cascade at the DB level). */
    public function destroy(int $id): JsonResponse
    {
        $drive = ExaminationDrive::findOrFail($id);
        $drive->delete();

        return response()->json(['message' => 'Examination drive deleted.']);
    }

    /** Subject/Faculty mapping rows for the Edit modal. */
    public function mappings(int $id): JsonResponse
    {
        $rows = ExaminationDriveFacultyMap::with(['subject', 'faculty'])
            ->where('examination_drive_id', $id)
            ->get()
            ->map(fn ($row) => [
                'subject' => $row->subject->subject_name ?? '',
                'faculty' => trim($row->faculty->full_name ?? ''),
            ])
            ->values();

        return response()->json($rows);
    }

    /** Map Faculty to Drive — editable component table. */
    public function show(int $id)
    {
        $drive = $this->findDriveOrFail($id);

        return view('admin.coe.examination_drive.show', [
            'drive'      => $this->driveViewModel($drive),
            'components' => $this->componentNames(),
            'sources'    => self::SOURCES,
            'faculty'    => $this->facultyNames(),
            'examModes'  => self::EXAM_MODES,
        ]);
    }

    /** Replace a drive's component mapping rows (Map Faculty to Examination Drive → Save). */
    public function saveComponents(Request $request, int $id): JsonResponse
    {
        $drive = ExaminationDrive::findOrFail($id);

        $data = $request->validate([
            'rows'                  => ['array'],
            'rows.*.component'      => ['required', 'string', Rule::exists('component_master', 'component_name')],
            'rows.*.max_marks'      => ['required', 'numeric', 'min:0'],
            'rows.*.passing_marks'  => ['required', 'numeric', 'min:0'],
            'rows.*.source'         => ['required', 'string', Rule::in(self::SOURCES)],
            'rows.*.faculty'        => ['nullable', 'string', 'max:150'],
            'rows.*.weightage'      => ['required', 'numeric', 'min:0', 'max:100'],
            'rows.*.exam_date'      => ['nullable', 'date'],
            'rows.*.exam_type'      => ['nullable', 'string', Rule::in(self::EXAM_MODES)],
            // JSON-encoded string, not an array: jQuery drops an empty array from the
            // request, so "[]" (cleared) would be indistinguishable from "not sent".
            'rows.*.sub_faculty'    => ['nullable', 'string', 'json'],
            // {"Faculty Name": "24"} - same JSON-string reason as sub_faculty.
            'rows.*.codes'          => ['nullable', 'string', 'json'],
        ]);

        $rows = collect($data['rows'] ?? [])->map(function (array $row) {
            if (array_key_exists('sub_faculty', $row) && $row['sub_faculty'] !== null) {
                $row['sub_faculty'] = $this->cleanNames((array) json_decode($row['sub_faculty'], true));
            } else {
                unset($row['sub_faculty']);
            }

            if (array_key_exists('codes', $row) && $row['codes'] !== null) {
                $row['codes'] = (array) json_decode($row['codes'], true);
            } else {
                unset($row['codes']);
            }

            return $row;
        });

        $unknown = $this->unknownFacultyNames(
            $rows->flatMap(fn ($row) => array_merge([$row['faculty'] ?? ''], $row['sub_faculty'] ?? []))
                ->reject(fn ($name) => $name === 'Sargam 2.0')
                ->all()
        );
        if ($unknown) {
            return response()->json(['message' => 'Unknown faculty: ' . implode(', ', $unknown)], 422);
        }

        $otCodes = $this->courseOtCodes($drive);

        DB::transaction(function () use ($drive, $rows, $otCodes) {
            // Sub-coordinators by component, so a page that does not send them
            // (admin Map Faculty) does not wipe what faculty set on Complete Setup.
            $previous = ExaminationDriveComponentMap::where('examination_drive_id', $drive->id)
                ->get(['component_master_pk', 'sub_faculty_names', 'faculty_codes'])
                ->keyBy('component_master_pk');

            ExaminationDriveComponentMap::where('examination_drive_id', $drive->id)->delete();

            foreach ($rows as $row) {
                $componentPk = ComponentMaster::where('component_name', $row['component'])->value('pk');
                if (! $componentPk) {
                    continue;
                }
                $facultyPk = filled($row['faculty'] ?? null)
                    ? FacultyMaster::whereRaw('TRIM(full_name) = ?', [trim($row['faculty'])])->value('pk')
                    : null;

                ExaminationDriveComponentMap::create([
                    'examination_drive_id' => $drive->id,
                    'component_master_pk'  => $componentPk,
                    'faculty_master_pk'    => $facultyPk,
                    'max_marks'            => $row['max_marks'],
                    'passing_marks'        => $row['passing_marks'],
                    'source'               => $row['source'],
                    'weightage'            => $row['weightage'],
                    'exam_date'            => $row['exam_date'] ?? null,
                    'exam_type'            => $row['exam_type'] ?? null,
                    'sub_faculty_names'    => $subs = array_key_exists('sub_faculty', $row)
                        ? ($row['sub_faculty'] ?: null)
                        : ($previous[$componentPk]->sub_faculty_names ?? null),
                    'faculty_codes'        => $this->cleanCodes(
                        array_key_exists('codes', $row) ? $row['codes'] : ($previous[$componentPk]->faculty_codes ?? []),
                        array_merge([trim((string) ($row['faculty'] ?? ''))], $subs ?? []),
                        $otCodes
                    ),
                    'created_date'         => now(),
                ]);
            }
        });

        return response()->json(['message' => 'Component mapping saved.']);
    }

    /** Assign a single component's primary faculty + sub-coordinators (Assign Faculty modal). */
    public function assignFaculty(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'component'   => ['required', 'string', Rule::exists('component_master', 'component_name')],
            'faculty'     => ['nullable', 'string', 'max:150'],
            'sub_faculty' => ['array'],
            'sub_faculty.*' => ['string', 'max:150'],
            // Per faculty: a Multi-tab code (string) or a list of OT codes; cleanCodes() sanitises.
            'codes'       => ['nullable', 'array'],
        ]);

        $subNames = $this->cleanNames($data['sub_faculty'] ?? []);
        $unknown = $this->unknownFacultyNames(array_merge([$data['faculty'] ?? ''], $subNames));
        if ($unknown) {
            return response()->json(['message' => 'Unknown faculty: ' . implode(', ', $unknown)], 422);
        }

        $componentPk = ComponentMaster::where('component_name', $data['component'])->value('pk');
        $map = ExaminationDriveComponentMap::where('examination_drive_id', $id)
            ->where('component_master_pk', $componentPk)
            ->first();

        if (! $map) {
            return response()->json([
                'message' => 'This component is not saved on the drive yet. Save the component rows first, then assign faculty.',
            ], 422);
        }

        $facultyPk = filled($data['faculty'] ?? null)
            ? FacultyMaster::whereRaw('TRIM(full_name) = ?', [trim($data['faculty'])])->value('pk')
            : null;

        $map->update([
            'faculty_master_pk'  => $facultyPk,
            'sub_faculty_names'  => $subNames ?: null,
            // Only sent by the setup page's Multi tab; otherwise keep what is stored.
            'faculty_codes'      => $this->cleanCodes(
                array_key_exists('codes', $data) ? ($data['codes'] ?? []) : ($map->faculty_codes ?? []),
                array_merge([trim((string) ($data['faculty'] ?? ''))], $subNames),
                $this->courseOtCodes(ExaminationDrive::findOrFail($id))
            ),
            'modified_date'      => now(),
        ]);

        return response()->json(['message' => 'Faculty assigned.']);
    }

    /** Faculty-end: list of drives assigned to this faculty member. */
    /**
     * Faculty-end: list of drives. NOTE: there is no established convention in
     * this codebase yet for mapping the logged-in user to a faculty_master
     * row, so — matching the previous (mock) behaviour exactly — this still
     * shows all drives rather than a per-faculty subset.
     */
    public function facultyIndex(Request $request, ExaminationDriveFacultyDataTable $dataTable)
    {
        $scope = in_array($request->query('scope'), self::SCOPES, true)
            ? $request->query('scope') : 'active';

        return $dataTable->render('admin.coe.examination_drive.faculty_index', [
            'scope'     => $scope,
            'courses'   => $this->courseNames(),
            'terms'     => $this->termNames(),
            'phases'    => $this->phaseValues(),
            'examTypes' => $this->examTypeNames(),
        ]);
    }

    /** Faculty-end: Map Faculty to Examination Drive (editable component table + Assign Faculty modal). */
    public function facultySetup(int $id)
    {
        $drive = $this->findDriveOrFail($id);

        // A Draft drive is not released to faculty yet - not in their list, not by URL.
        abort_if($drive->status === ExaminationDrive::STATUS_DRAFT, 404);

        return view('admin.coe.examination_drive.faculty_setup', [
            'drive'       => $this->driveViewModel($drive),
            'components'  => $this->componentNames(),
            'sources'     => self::SOURCES,
            'faculty'     => $this->facultyNames(),
            'examModes'   => self::EXAM_MODES,
            // Select Roll Number lists the real OTs enrolled in the drive's course.
            'otList'      => $this->courseOts((int) $drive->course_master_pk),
        ]);
    }

    /**
     * Active OTs of a course as [code, name], ordered naturally by OT code
     * (A2 before A10). Only OTs with a generated code are listed.
     *
     * @return array<int, array{code: string, name: string}>
     */
    private function courseOts(int $coursePk): array
    {
        return DB::table('student_master_course__map as m')
            ->join('student_master as s', 's.pk', '=', 'm.student_master_pk')
            ->where('m.course_master_pk', $coursePk)
            ->where('m.active_inactive', 1)
            ->whereNotNull('s.generated_OT_code')
            ->where('s.generated_OT_code', '!=', '')
            ->get(['s.generated_OT_code as code', 's.display_name as name'])
            ->map(fn ($ot) => ['code' => trim($ot->code), 'name' => trim((string) $ot->name)])
            ->unique('code')
            ->sort(fn ($a, $b) => $this->compareOtCodes($a['code'], $b['code']))
            ->values()
            ->all();
    }

    /** Faculty Coordinates & Components — view with Assign Faculty modal. */
    public function coordinates(int $id)
    {
        $drive = $this->findDriveOrFail($id);

        return view('admin.coe.examination_drive.coordinates', [
            'drive'    => $this->driveViewModel($drive),
            'faculty'  => $this->facultyNames(),
            'examModes'=> self::EXAM_MODES,
        ]);
    }

    /**
     * @param  array<mixed>  $names
     * @return string[] trimmed, non-empty, de-duplicated
     */
    private function cleanNames(array $names): array
    {
        return collect($names)
            ->filter(fn ($name) => is_string($name))
            ->map(fn ($name) => trim($name))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * OT code per faculty, kept only for names still on the row, values trimmed.
     *
     * @param  array<mixed>  $codes
     * @param  string[]  $names
     * @return array<string, string>|null
     */
    private function cleanCodes(array $codes, array $names, ?array $allowedOtCodes = null): ?array
    {
        $clean = [];
        foreach ($codes as $name => $code) {
            if (! is_string($name) || ! in_array(trim($name), $names, true)) {
                continue;
            }
            if (is_array($code)) {
                // OT codes picked in Select Roll Number: only the course's real OTs.
                $list = collect($code)
                    ->filter(fn ($c) => is_scalar($c))
                    ->map(fn ($c) => trim((string) $c))
                    ->filter()
                    ->when($allowedOtCodes !== null, fn ($l) => $l->filter(fn ($c) => in_array($c, $allowedOtCodes, true)))
                    ->unique()
                    ->sort(fn ($a, $b) => $this->compareOtCodes($a, $b))
                    ->values()
                    ->all();
                if ($list) {
                    $clean[trim($name)] = $list;
                }
            } elseif (is_scalar($code) && trim((string) $code) !== '') {
                // Multi Faculty tab "Unique Codes of OT's" value.
                $clean[trim($name)] = mb_substr(trim((string) $code), 0, 20);
            }
        }

        return $clean ?: null;
    }

    /**
     * Orders OT codes by prefix, then number value: A2 < A5 < A10 < A044 (=44).
     * strnatcmp() reads a leading zero as a fraction and puts A044 before A05.
     */
    private function compareOtCodes(string $a, string $b): int
    {
        $split = fn (string $c) => preg_match('/^(\D*)(\d+)(.*)$/', $c, $m) ? [strtoupper($m[1]), (int) $m[2], $m[3]] : [strtoupper($c), 0, ''];

        return $split($a) <=> $split($b);
    }

    /** @return string[] */
    private function courseOtCodes(ExaminationDrive $drive): array
    {
        return array_column($this->courseOts((int) $drive->course_master_pk), 'code');
    }

    /**
     * Names not found in faculty_master. Rejecting these, instead of storing
     * them, keeps free text out of sub_faculty_names.
     *
     * @param  string[]  $names
     * @return string[]
     */
    private function unknownFacultyNames(array $names): array
    {
        $names = $this->cleanNames($names);
        if ($names === []) {
            return [];
        }

        $known = FacultyMaster::query()
            ->whereIn(DB::raw('TRIM(full_name)'), $names)
            ->pluck('full_name')
            ->map(fn ($name) => trim($name))
            ->all();

        return array_values(array_diff($names, $known));
    }

    private function findDriveOrFail(int $id): ExaminationDrive
    {
        return ExaminationDrive::with([
            'examinationType', 'term', 'course',
            'facultyMaps.faculty', 'facultyMaps.subject',
            'componentMaps.component', 'componentMaps.faculty',
        ])->findOrFail($id);
    }

    /**
     * Shape a real ExaminationDrive the same way the old mock rows were
     * shaped, so show/coordinates blades don't need to change. Per-component
     * faculty/exam-date/exam-type aren't columns on
     * examination_drive_component_maps yet, so those placeholders stay '-'
     * until that's added.
     */
    private function driveViewModel(ExaminationDrive $drive): array
    {
        return [
            'id'         => $drive->id,
            'name'       => filled($drive->drive_name)
                ? $drive->drive_name
                : ($drive->examinationType->exam_type_name ?? 'Examination') . ' Drive — ' . $drive->academic_session,
            'exam_type'  => $drive->examinationType->exam_type_name ?? '-',
            'term'       => $drive->term->term_name ?? '-',
            'course'     => $drive->course->course_name ?? '-',
            'session'    => $drive->academic_session,
            // Faculty mapped when the drive was created (Subject -> Faculty rows).
            'drive_faculty' => $drive->driveFacultyNames(),
            // Subjects from the same creation mapping (Subject -> Faculty rows).
            'subjects'   => $drive->facultyMaps
                ->map(fn ($map) => trim((string) ($map->subject->subject_name ?? '')))
                ->filter()->unique()->values()->all(),
            'faculty'    => implode(', ', $drive->driveFacultyNames()) ?: '-',
            'sub_coords' => count($drive->subCoordinatorNames()),
            'components' => $drive->componentMaps->count(),
            'start_date' => optional($drive->start_date)->format('d-m-Y'),
            'end_date'   => optional($drive->end_date)->format('d-m-Y'),
            'status'     => $drive->statusLabel(),
            'archived'   => $drive->status === ExaminationDrive::STATUS_CLOSED,
            'component_rows' => $drive->componentMaps->map(fn ($map) => [
                'component'      => $map->component->component_name ?? '-',
                'max_marks'      => $map->max_marks,
                'passing_marks'  => $map->passing_marks,
                'source'         => $map->source ?: self::SOURCES[0],
                'faculty'        => trim($map->faculty->full_name ?? '') ?: '-',
                'weightage'      => $map->weightage,
                'exam_date'      => optional($map->exam_date)->format('d/m/Y')
                    ?: (optional($drive->start_date)->format('d/m/Y') ?: now()->format('d/m/Y')),
                'exam_type'      => $map->exam_type ?: self::EXAM_MODES[0],
                'sub_faculty'    => $map->sub_faculty_names ?? [],
                // Setup faculty + further names, as shown in SUB-COORDINATES.
                'sub_coordinators' => $map->subCoordinatorNames(),
                'faculty_codes'    => $map->faculty_codes ?? [],
            ])->values()->all(),
        ];
    }

    /** @return Collection<int, array> */
    private function courseNames(): array
    {
        return CourseMaster::orderBy('course_name')->pluck('course_name')->filter()->unique()->values()->all();
    }

    /** Real subject_master names for the Subject/Faculty mapping selects. */
    private function subjectNames(): array
    {
        return SubjectMaster::where('active_inactive', 1)
            ->orderBy('subject_name')
            ->pluck('subject_name')
            ->map(fn ($name) => trim($name))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /** Real component_master names for the component mapping selects. */
    private function componentNames(): array
    {
        return ComponentMaster::where('active_inactive', 1)
            ->orderBy('component_name')
            ->pluck('component_name')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /** Real faculty_master names for the Subject/Faculty mapping selects. */
    private function facultyNames(): array
    {
        return FacultyMaster::where('active_inactive', 1)
            ->orderBy('full_name')
            ->pluck('full_name')
            ->map(fn ($name) => trim($name))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function termNames(): array
    {
        return TermMaster::orderBy('term_name')->pluck('term_name')->filter()->unique()->values()->all();
    }

    private function examTypeNames(): array
    {
        return ExaminationTypeMaster::orderBy('exam_type_name')->pluck('exam_type_name')->filter()->unique()->values()->all();
    }

    private function phaseValues(): array
    {
        return ExaminationDrive::whereNotNull('phase')->distinct()->orderBy('phase')->pluck('phase')->filter()->values()->all();
    }

    private function sampleDrives(): Collection
    {
        $rows = [
            // name, examType, term, course, session, faculty, sub, components, start, end, status, archived
            ['Law Drive',                    'Law Exam',    'Annual Term', 'Phase IV 2025', '2024', 'Lee Brown',   3, 3, '05-01-2027', '08-02-2027', 'Pending at COE', false],
            ['Computer Proficiency Test Drive','-',         'Annual Term', 'Phase IV 2025', '2024', 'Casey Sims',  0, 0, '05-01-2027', '08-02-2027', 'Active',         false],
            ['Presentation Assessment Drive',  '-',         'Annual Term', 'Phase IV 2025', '2024', 'Gene Brewer', 0, 0, '05-01-2027', '08-02-2027', 'Closed',         false],
            ['Director Assessment Drive',      '-',         'Annual Term', 'Phase IV 2025', '2024', 'Homer Green', 0, 0, '05-01-2027', '08-02-2027', 'Closed',         false],
            ['Leadership Drive',             'Written Test','Term I',      'Phase IV 2025', '2025', 'John Doe',    2, 2, '10-03-2027', '15-03-2027', 'Draft',          false],
            ['Old Exam Drive',               'Practical',   'Annual Term', 'Phase III 2025','2024', 'Lee Brown',   1, 1, '01-01-2026', '10-01-2026', 'Closed',         true],
        ];

        return collect($rows)->values()->map(fn (array $r, int $i) => [
            'id'          => $i + 1,
            'name'        => $r[0],
            'exam_type'   => $r[1],
            'term'        => $r[2],
            'course'      => $r[3],
            'session'     => $r[4],
            'faculty'     => $r[5],
            'sub_coords'  => $r[6],
            'components'  => $r[7],
            'start_date'  => $r[8],
            'end_date'    => $r[9],
            'status'      => $r[10],
            'archived'    => $r[11],
            // Sample component rows for the show/coordinates sub-pages
            'component_rows' => [
                ['component' => 'Ethics & Integrity', 'max_marks' => 100, 'passing_marks' => 40.00,
                 'source' => 'Faculty', 'faculty' => 'John Doe', 'weightage' => 40.00,
                 'exam_date' => '01/11/2026', 'exam_type' => 'Online',
                 'sub_faculty' => ['Eileen Lane', 'Helen Riley', 'Johnathan Juarez', 'Sharon Ross', 'Jan Harmon']],
                ['component' => 'Discipline',         'max_marks' => 100, 'passing_marks' => 40.00,
                 'source' => 'Sargam 2.0', 'faculty' => 'Sargam 2.0', 'weightage' => 60.00,
                 'exam_date' => '01/11/2026', 'exam_type' => 'Offline',
                 'sub_faculty' => []],
            ],
        ]);
    }
}
