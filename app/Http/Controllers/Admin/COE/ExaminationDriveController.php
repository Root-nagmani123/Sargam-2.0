<?php

namespace App\Http\Controllers\Admin\COE;

use App\DataTables\ExaminationDriveCcDataTable;
use App\DataTables\ExaminationDriveCoeDataTable;
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
use App\Models\ServiceMaster;
use App\Models\ServicePassingMarksMaster;
use App\Models\SubjectMaxMarksMaster;
use App\Models\SubjectMaster;
use App\Models\TermMaster;
use App\Services\COE\CourseCoordinatorService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
    public const EXAM_MODES   = ['Online', 'Offline', 'Offline & Online'];
    public const COMPONENT_EXAM_TYPES = ['Online Objective', 'Offline Objective', 'Online Descriptive', 'Offline Descriptive'];


    public function index(Request $request, ExaminationDriveDataTable $dataTable)
    {
        $scope = in_array($request->query('scope'), self::SCOPES, true)
            ? $request->query('scope') : 'active';

        return $dataTable->render('admin.coe.examination_drive.index', [
            'scope'     => $scope,
            'courses'   => $this->courseNames(),
            'services'  => $this->serviceOptions(),
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
            // service_master pk, not name: short names repeat (IDES, ITS).
            'service'          => ['required', 'integer', Rule::exists('service_master', 'pk')->where('active_inactive', 1)],
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
                'service_master_pk'          => (int) $data['service'],
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

        $notice = $drive->status === ExaminationDrive::STATUS_DRAFT
            ? ''
            : $this->notifyDriveFaculty($drive, $this->facultySubjects($drive->id), 'New Examination Drive');

        return response()->json(['id' => $drive->id, 'message' => trim('Examination drive created. ' . $notice)]);
    }

    /** Update an existing drive + replace its Subject/Faculty mapping rows. */
    public function update(Request $request, int $id): JsonResponse
    {
        $drive = ExaminationDrive::findOrFail($id);
        abort_if($drive->status === ExaminationDrive::STATUS_CLOSED, 422, 'A closed drive cannot be edited.');

        // Who already knew about the drive, to notify only what changes.
        $wasDraft = $drive->status === ExaminationDrive::STATUS_DRAFT;
        $before = $this->facultySubjects($drive->id);

        $data = $request->validate([
            'course'           => ['required', 'string', Rule::exists('course_master', 'course_name')],
            // service_master pk, not name: short names repeat (IDES, ITS).
            'service'          => ['required', 'integer', Rule::exists('service_master', 'pk')->where('active_inactive', 1)],
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
                'service_master_pk'          => (int) $data['service'],
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

        // Released from Draft: every mapped faculty hears of it. Otherwise only
        // faculty given a subject they did not have.
        $notice = '';
        if ($drive->status !== ExaminationDrive::STATUS_DRAFT) {
            $new = [];
            foreach ($this->facultySubjects($drive->id) as $facultyPk => $subjects) {
                $added = $wasDraft ? $subjects : array_values(array_diff($subjects, $before[$facultyPk] ?? []));
                if ($added) {
                    $new[$facultyPk] = $added;
                }
            }
            $notice = $new ? $this->notifyDriveFaculty($drive, $new, 'Examination Drive Assigned') : '';
        }

        return response()->json(['id' => $drive->id, 'message' => trim('Examination drive updated. ' . $notice)]);
    }

    /**
     * A drive's Subject -> Faculty mapping grouped by faculty.
     *
     * @return array<int, string[]> faculty_master_pk => subject names
     */
    private function facultySubjects(int $driveId): array
    {
        return ExaminationDriveFacultyMap::with('subject')
            ->where('examination_drive_id', $driveId)
            ->get()
            ->groupBy('faculty_master_pk')
            ->map(fn ($maps) => $maps->map(fn ($m) => trim((string) ($m->subject->subject_name ?? '')))->filter()->values()->all())
            ->all();
    }

    /**
     * Dashboard notification to each faculty for their subjects on the drive;
     * clicking it opens their Map Faculty setup page (config/notifications.php
     * exam_drive). Faculty reach the bell through faculty_master.employee_master_pk
     * (= user_credentials.user_id); those without one cannot be notified. A
     * failure is logged, never fails the save. Returns a line for the admin.
     *
     * @param  array<int, string[]>  $facultySubjects  faculty_master_pk => subject names
     */
    private function notifyDriveFaculty(ExaminationDrive $drive, array $facultySubjects, string $title): string
    {
        $userIds = FacultyMaster::whereIn('pk', array_keys($facultySubjects))
            ->whereIn('employee_master_pk', DB::table('user_credentials')->select('user_id'))
            ->pluck('employee_master_pk', 'pk');

        $name = filled($drive->drive_name) ? $drive->drive_name : 'Examination Drive #' . $drive->id;
        $sent = 0;
        try {
            $service = app(NotificationService::class);
            foreach ($userIds as $facultyPk => $userId) {
                $service->create(
                    (int) $userId, 'exam_drive', 'ExaminationDrive', $drive->id, $title,
                    "You are mapped to {$name} for " . implode(', ', $facultySubjects[$facultyPk])
                        . '. Set up its components and marks.'
                );
                $sent++;
            }
        } catch (\Throwable $e) {
            Log::error('Examination drive notification failed', ['drive_id' => $drive->id, 'error' => $e->getMessage()]);
        }

        $missing = count($facultySubjects) - $userIds->count();

        return trim(($sent ? "{$sent} faculty notified." : '')
            . ($missing ? " {$missing} faculty could not be notified (no login linked)." : ''));
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
    public function show(Request $request, int $id)
    {
        $drive = $this->findDriveOrFail($id);
        $subjectPk = $this->pageSubject($drive, $request);

        return view('admin.coe.examination_drive.show', [
            'drive'      => $this->driveViewModel($drive, $subjectPk),
            'subjectTabs' => $this->accessibleSubjects($drive),
            'subjectPk'  => $subjectPk,
            'components' => $this->componentNames(),
            'sources'    => self::SOURCES,
            'faculty'    => $this->facultyNames(),
            'examModes'  => self::EXAM_MODES,
        ]);
    }

    /** Replace a drive's component mapping rows (Map Faculty to Examination Drive → Save). */
    public function saveComponents(Request $request, int $id): JsonResponse
    {
        $drive = $this->findDriveOrFail($id);

        $data = $request->validate([
            'subject_master_pk'     => ['required', 'integer'],
            'rows'                  => ['array'],
            'rows.*.component'      => ['required', 'string', Rule::exists('component_master', 'component_name')],
            'rows.*.max_marks'      => ['required', 'numeric', 'min:0'],
            // Ignored: every row's passing marks come from Service Passing Marks.
            'rows.*.passing_marks'  => ['nullable', 'numeric', 'min:0'],
            'rows.*.source'         => ['required', 'string', Rule::in(self::SOURCES)],
            'rows.*.faculty'        => ['nullable', 'string', 'max:150'],
            'rows.*.weightage'      => ['required', 'numeric', 'min:0', 'max:100'],
            'rows.*.exam_date'      => ['nullable', 'date'],
            'rows.*.exam_end_date'  => ['nullable', 'date', 'after_or_equal:rows.*.exam_date'],
            'rows.*.exam_mode'      => ['nullable', 'string', Rule::in(self::EXAM_MODES)],
            'rows.*.exam_type'      => ['nullable', 'string', Rule::in(self::COMPONENT_EXAM_TYPES)],
            // JSON-encoded string, not an array: jQuery drops an empty array from the
            // request, so "[]" (cleared) would be indistinguishable from "not sent".
            'rows.*.sub_faculty'    => ['nullable', 'string', 'json'],
            // {"Faculty Name": "24"} - same JSON-string reason as sub_faculty.
            'rows.*.codes'          => ['nullable', 'string', 'json'],
        ], [
            'rows.*.exam_end_date.after_or_equal' => 'An exam range cannot end before it starts.',
        ]);

        $subjectPk = (int) $data['subject_master_pk'];
        if (! array_key_exists($subjectPk, $this->accessibleSubjects($drive))) {
            return response()->json(['message' => 'You are not mapped to this subject on the drive.'], 403);
        }

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

        $limits = $this->marksLimits($drive, $subjectPk);
        if ($error = $this->marksLimitError($rows, $limits)) {
            return response()->json(['message' => $error], 422);
        }

        $unknown = $this->unknownFacultyNames(
            $rows->flatMap(fn ($row) => array_merge([$row['faculty'] ?? ''], $row['sub_faculty'] ?? []))
                ->reject(fn ($name) => $name === 'Sargam 2.0')
                ->all()
        );
        if ($unknown) {
            return response()->json(['message' => 'Unknown faculty: ' . implode(', ', $unknown)], 422);
        }

        $otCodes = $this->courseOtCodes($drive);

        DB::transaction(function () use ($drive, $rows, $otCodes, $limits, $subjectPk) {
            // Only this subject's rows: other faculty's subjects are left alone.
            $subjectRows = fn () => ExaminationDriveComponentMap::where('examination_drive_id', $drive->id)
                ->where('subject_master_pk', $subjectPk);

            // Sub-coordinators, exam end date and exam type by component, so a page
            // that does not send them (admin Map Faculty) does not wipe what faculty
            // set on Complete Setup.
            $previous = $subjectRows()
                ->get(['component_master_pk', 'sub_faculty_names', 'faculty_codes', 'exam_end_date', 'exam_type'])
                ->keyBy('component_master_pk');

            $subjectRows()->delete();

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
                    'subject_master_pk'    => $subjectPk,
                    'component_master_pk'  => $componentPk,
                    'faculty_master_pk'    => $facultyPk,
                    'max_marks'            => $row['max_marks'],
                    'passing_marks'        => $limits['passing_marks'],
                    'source'               => $row['source'],
                    'weightage'            => $row['weightage'],
                    'exam_date'            => $row['exam_date'] ?? null,
                    'exam_end_date'        => array_key_exists('exam_end_date', $row)
                        ? $row['exam_end_date']
                        : ($previous[$componentPk]->exam_end_date ?? null),
                    'exam_mode'            => $row['exam_mode'] ?? null,
                    'exam_type'            => array_key_exists('exam_type', $row)
                        ? $row['exam_type']
                        : ($previous[$componentPk]->exam_type ?? null),
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

        // A mapped faculty's save sends the drive to the course's CC; an admin's does not.
        $notice = $this->isMappedFaculty($drive) ? $this->sendToCoordinator($drive) : '';

        return response()->json(['message' => trim('Component mapping saved. ' . $notice)]);
    }

    /**
     * Marks rules for one subject's component rows on a drive:
     *  - passing marks of every row = the drive service's Service Passing Marks;
     *  - total max marks <= the subject's Subject Max Marks;
     *  - total weightage <= 100.
     *
     * @return array{subject_name: string, subject_max: ?float, service_name: ?string, passing_marks: ?float}
     */
    private function marksLimits(ExaminationDrive $drive, int $subjectPk): array
    {
        $max = SubjectMaxMarksMaster::where('subject_master_pk', $subjectPk)->where('active_inactive', 1)->value('max_marks');
        $pass = $drive->service_master_pk
            ? ServicePassingMarksMaster::where('service_master_pk', $drive->service_master_pk)->where('active_inactive', 1)->value('passing_marks')
            : null;

        return [
            'subject_name' => trim((string) SubjectMaster::where('pk', $subjectPk)->value('subject_name')),
            'subject_max' => $max === null ? null : (float) $max,
            'service_name' => $drive->service_master_pk ? ServiceMaster::where('pk', $drive->service_master_pk)->value('service_short_name') : null,
            'passing_marks' => $pass === null ? null : (float) $pass,
        ];
    }

    /**
     * Subjects of the drive the logged-in user may set up, as [pk => name]:
     * a faculty mapped on the drive gets only their own subjects; any other
     * Faculty-role user gets none; everyone else (COE admins) gets all.
     * The login -> faculty link is faculty_master.employee_master_pk.
     *
     * @return array<int, string>
     */
    private function accessibleSubjects(ExaminationDrive $drive): array
    {
        $maps = $drive->facultyMaps->sortBy('id');
        $user = auth()->user();
        $facultyPks = $user
            ? FacultyMaster::where('employee_master_pk', $user->user_id)->pluck('pk')->all()
            : [];

        $own = $maps->whereIn('faculty_master_pk', $facultyPks);
        if ($own->isEmpty() && $user && $user->hasRole('Faculty') && ! $user->hasRole('Super Admin')) {
            return [];
        }

        return ($own->isNotEmpty() ? $own : $maps)
            ->mapWithKeys(fn ($map) => [(int) $map->subject_master_pk => trim((string) ($map->subject->subject_name ?? ''))])
            ->all();
    }

    /** ?subject= if the user may see it, else their first subject; 404 when they may see none. */
    private function pageSubject(ExaminationDrive $drive, Request $request): int
    {
        $subjects = $this->accessibleSubjects($drive);
        abort_if($subjects === [], 404);

        $asked = (int) $request->query('subject');

        return array_key_exists($asked, $subjects) ? $asked : array_key_first($subjects);
    }

    /** First broken marks rule for these rows, or null. */
    private function marksLimitError(Collection $rows, array $limits): ?string
    {
        $weightage = round($rows->sum(fn ($row) => (float) $row['weightage']), 2);
        if ($weightage > 100) {
            return "Total weightage cannot exceed 100% (now {$weightage}%).";
        }

        if ($rows->isNotEmpty() && $limits['passing_marks'] === null) {
            return $limits['service_name']
                ? "Passing marks for {$limits['service_name']} are not set in Service Passing Marks master."
                : 'Set the drive\'s Service first: passing marks come from Service Passing Marks.';
        }

        if ($rows->isNotEmpty() && $limits['subject_max'] === null) {
            return "Max marks for {$limits['subject_name']} are not set in Subject Max Marks master.";
        }
        $total = round($rows->sum(fn ($row) => (float) $row['max_marks']), 2);
        if ($limits['subject_max'] !== null && $total > $limits['subject_max']) {
            return "Total max marks ({$total}) cannot exceed {$limits['subject_max']} for {$limits['subject_name']}.";
        }

        return null;
    }

    /** Assign a single component's primary faculty + sub-coordinators (Assign Faculty modal). */
    public function assignFaculty(Request $request, int $id): JsonResponse
    {
        $drive = $this->findDriveOrFail($id);

        $data = $request->validate([
            'subject_master_pk' => ['required', 'integer'],
            'component'   => ['required', 'string', Rule::exists('component_master', 'component_name')],
            'faculty'     => ['nullable', 'string', 'max:150'],
            'sub_faculty' => ['array'],
            'sub_faculty.*' => ['string', 'max:150'],
            // Per faculty: a Multi-tab code (string) or a list of OT codes; cleanCodes() sanitises.
            'codes'       => ['nullable', 'array'],
        ]);

        if (! array_key_exists((int) $data['subject_master_pk'], $this->accessibleSubjects($drive))) {
            return response()->json(['message' => 'You are not mapped to this subject on the drive.'], 403);
        }

        $subNames = $this->cleanNames($data['sub_faculty'] ?? []);
        $unknown = $this->unknownFacultyNames(array_merge([$data['faculty'] ?? ''], $subNames));
        if ($unknown) {
            return response()->json(['message' => 'Unknown faculty: ' . implode(', ', $unknown)], 422);
        }

        $componentPk = ComponentMaster::where('component_name', $data['component'])->value('pk');
        $map = ExaminationDriveComponentMap::where('examination_drive_id', $id)
            ->where('subject_master_pk', (int) $data['subject_master_pk'])
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
                $this->courseOtCodes($drive)
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

    /** CC-end: drives of the courses the logged-in faculty coordinates (Super Admin: all). */
    public function ccIndex(Request $request, ExaminationDriveCcDataTable $dataTable)
    {
        $scope = in_array($request->query('scope'), self::SCOPES, true)
            ? $request->query('scope') : 'active';

        return $dataTable->render('admin.coe.examination_drive.cc_index', [
            'scope'     => $scope,
            'courses'   => $this->courseNames(),
            'terms'     => $this->termNames(),
            'phases'    => $this->phaseValues(),
            'examTypes' => $this->examTypeNames(),
        ]);
    }

    /** CC approves a pending drive with the exam dates they confirm, which replace the drive's dates. */
    public function ccApprove(Request $request, int $id): JsonResponse
    {
        $drive = $this->pendingDriveForCoordinator($id);

        $data = $request->validate([
            'proposed_start_date' => ['required', 'date'],
            'proposed_end_date'   => ['required', 'date', 'after_or_equal:proposed_start_date'],
        ], [
            'proposed_end_date.after_or_equal' => 'The proposed end exam date cannot be before the proposed start exam date.',
        ]);

        // The CC's confirmed dates become the drive's dates everywhere (COE,
        // admin and faculty lists); proposed_* keep a record of what they set.
        $drive->update([
            'cc_status'           => ExaminationDrive::CC_APPROVED,
            'start_date'          => $data['proposed_start_date'],
            'end_date'            => $data['proposed_end_date'],
            'proposed_start_date' => $data['proposed_start_date'],
            'proposed_end_date'   => $data['proposed_end_date'],
            'cc_remarks'          => null,
            'cc_action_by'        => auth()->user()->user_id,
            'cc_action_at'        => now(),
            'modified_date'       => now(),
        ]);

        return response()->json(['message' => 'Examination drive approved.']);
    }

    /** CC rejects a pending drive; faculty can change their setup and save it again. */
    public function ccReject(Request $request, int $id): JsonResponse
    {
        $drive = $this->pendingDriveForCoordinator($id);

        $data = $request->validate([
            'remarks' => ['required', 'string', 'max:500'],
        ], [
            'remarks.required' => 'Enter the reason for rejecting.',
        ]);

        $drive->update([
            'cc_status'     => ExaminationDrive::CC_REJECTED,
            'cc_remarks'    => $data['remarks'],
            'cc_action_by'  => auth()->user()->user_id,
            'cc_action_at'  => now(),
            'modified_date' => now(),
        ]);

        return response()->json(['message' => 'Examination drive rejected.']);
    }

    /** The drive, if the logged-in user is the CC of its course (or Super Admin) and it is pending at CC. */
    private function pendingDriveForCoordinator(int $id): ExaminationDrive
    {
        $drive = ExaminationDrive::findOrFail($id);
        $user = auth()->user();

        abort_unless(
            $user && ($user->hasRole('Super Admin') || app(CourseCoordinatorService::class)->isCoordinatorOf($user, (int) $drive->course_master_pk)),
            403, 'You are not the Course Coordinator of this drive\'s course.'
        );
        abort_unless($drive->cc_status === ExaminationDrive::CC_PENDING, 422, 'This drive is not pending at the Course Coordinator.');

        return $drive;
    }

    /** Whether the logged-in user is a faculty mapped on the drive. */
    private function isMappedFaculty(ExaminationDrive $drive): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }
        $facultyPks = FacultyMaster::where('employee_master_pk', $user->user_id)->pluck('pk')->all();

        return $drive->facultyMaps->whereIn('faculty_master_pk', $facultyPks)->isNotEmpty();
    }

    /**
     * Mark the drive pending at the course's CC and notify them, unless it is
     * already pending (a re-save while waiting sends nothing new). A drive the
     * CC approved or rejected goes back to them. Returns a line for the faculty.
     */
    private function sendToCoordinator(ExaminationDrive $drive): string
    {
        if ($drive->cc_status === ExaminationDrive::CC_PENDING) {
            return 'Waiting for the Course Coordinator\'s approval.';
        }

        $drive->update(['cc_status' => ExaminationDrive::CC_PENDING, 'modified_date' => now()]);

        $userIds = app(CourseCoordinatorService::class)->coordinatorUserIds((int) $drive->course_master_pk);
        if ($userIds === []) {
            return 'Sent for approval, but no Course Coordinator with a login is set for this course.';
        }

        $name = filled($drive->drive_name) ? $drive->drive_name : 'Examination Drive #' . $drive->id;
        try {
            app(NotificationService::class)->createMultiple(
                $userIds, 'exam_drive', 'ExaminationDriveCc', $drive->id, 'Examination Drive for Approval',
                "{$name} has been set up by faculty and is waiting for your approval."
            );
        } catch (\Throwable $e) {
            Log::error('Examination drive CC notification failed', ['drive_id' => $drive->id, 'error' => $e->getMessage()]);
        }

        return 'Sent to the Course Coordinator for approval.';
    }

    /** COE-end: list of drives for COE review (Approve / Reject). COE role and Super Admin only. */
    public function coeIndex(Request $request, ExaminationDriveCoeDataTable $dataTable)
    {
        abort_unless(auth()->user()?->hasAnyRole(['COE', 'Super Admin']), 403);

        $scope = in_array($request->query('scope'), self::SCOPES, true)
            ? $request->query('scope') : 'active';

        return $dataTable->render('admin.coe.examination_drive.coe_index', [
            'scope'     => $scope,
            'courses'   => $this->courseNames(),
            'terms'     => $this->termNames(),
            'phases'    => $this->phaseValues(),
            'examTypes' => $this->examTypeNames(),
        ]);
    }

    /** Faculty-end: Map Faculty to Examination Drive (editable component table + Assign Faculty modal). */
    public function facultySetup(Request $request, int $id)
    {
        $drive = $this->findDriveOrFail($id);
        $subjectPk = $this->pageSubject($drive, $request);

        // A Draft drive is not released to faculty yet - not in their list, not by URL.
        abort_if($drive->status === ExaminationDrive::STATUS_DRAFT, 404);

        return view('admin.coe.examination_drive.faculty_setup', [
            'drive'       => $this->driveViewModel($drive, $subjectPk),
            'components'  => $this->componentNames(),
            'sources'     => self::SOURCES,
            'faculty'     => $this->facultyNames(),
            'examModes'   => self::EXAM_MODES,
            'componentExamTypes' => self::COMPONENT_EXAM_TYPES,
            // Select Roll Number lists the real OTs enrolled in the drive's course.
            'otList'      => $this->courseOts((int) $drive->course_master_pk),
            'limits'      => $this->marksLimits($drive, $subjectPk),
            'subjectTabs' => $this->accessibleSubjects($drive),
            'subjectPk'   => $subjectPk,
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
            'examinationType', 'term', 'course', 'service',
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
    private function driveViewModel(ExaminationDrive $drive, ?int $subjectPk = null): array
    {
        // A Map Faculty page shows one subject's rows; coordinates shows all.
        $maps = $subjectPk === null
            ? $drive->componentMaps
            : $drive->componentMaps->where('subject_master_pk', $subjectPk);

        return [
            'id'         => $drive->id,
            'name'       => filled($drive->drive_name)
                ? $drive->drive_name
                : ($drive->examinationType->exam_type_name ?? 'Examination') . ' Drive — ' . $drive->academic_session,
            'exam_type'  => $drive->examinationType->exam_type_name ?? '-',
            'term'       => $drive->term->term_name ?? '-',
            'course'     => $drive->course->course_name ?? '-',
            'service'    => $drive->service->service_short_name ?? '-',
            'service_name' => $drive->service->service_name ?? '',
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
            'status'     => $drive->progress()[0],
            'archived'   => $drive->status === ExaminationDrive::STATUS_CLOSED,
            'component_rows' => $maps->map(fn ($map) => [
                'component'      => $map->component->component_name ?? '-',
                'max_marks'      => $map->max_marks,
                'passing_marks'  => $map->passing_marks,
                'source'         => $map->source ?: self::SOURCES[0],
                'faculty'        => trim($map->faculty->full_name ?? '') ?: '-',
                'weightage'      => $map->weightage,
                'exam_date'      => optional($map->exam_date)->format('d/m/Y')
                    ?: (optional($drive->start_date)->format('d/m/Y') ?: now()->format('d/m/Y')),
                'exam_end_date'  => optional($map->exam_end_date)->format('d/m/Y')
                    ?: (optional($drive->end_date)->format('d/m/Y') ?: now()->format('d/m/Y')),
                'exam_mode'      => $map->exam_mode ?: self::EXAM_MODES[0],
                'exam_type'      => $map->exam_type,
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

    /** Active service_master rows for the Service select, as [pk => name]. */
    private function serviceOptions(): array
    {
        return ServiceMaster::where('active_inactive', 1)
            ->orderBy('service_name')
            ->pluck('service_name', 'pk')
            ->map(fn ($name) => trim($name))
            ->filter()
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
