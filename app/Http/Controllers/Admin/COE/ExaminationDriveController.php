<?php

namespace App\Http\Controllers\Admin\COE;

use Carbon\Carbon;
use App\DataTables\ExaminationDriveCcDataTable;
use App\DataTables\ExaminationDriveCoeDataTable;
use App\DataTables\ExaminationDriveDataTable;
use App\DataTables\ExaminationDriveFacultyDataTable;
use App\Http\Controllers\Concerns\ExportsMasterGrid;
use App\Http\Controllers\Controller;
use App\Models\COE\ParticipantCode;
use App\Models\COE\RollNumberAllocation;
use App\Models\ComponentMaster;
use App\Models\CourseMaster;
use App\Models\ExaminationDrive;
use App\Models\ExaminationDriveComponentMap;
use App\Models\ExaminationDriveFacultyMap;
use App\Models\ExaminationTypeMaster;
use App\Models\FacultyMaster;
use App\Models\ServiceMaster;
use App\Models\SubjectMaxMarksMaster;
use App\Models\SubjectMaster;
use App\Models\TermMaster;
use App\Services\COE\CourseCoordinatorService;
use App\Services\COE\ReexamService;
use App\Services\COE\ResultMarksException;
use App\Services\COE\ResultMarksService;
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
 * Sub-pages:
 *   index          — drive list (Active / Archived tabs)
 *   show/{id}      — Map Faculty to Examination Drive (editable component table)
 *   coordinates/{id} — Faculty Coordinates & Components (view with Assign Faculty modal)
 */
class ExaminationDriveController extends Controller
{
    use ExportsMasterGrid;

    public const SCOPES       = ['active', 'archived'];
    public const SOURCES      = ['Faculty', 'Sargam', 'Sargam 2.0'];
    public const EXAM_MODES   = ['Online', 'Offline', 'Offline & Online'];
    public const COMPONENT_EXAM_TYPES = ['Online Objective', 'Offline Objective', 'Online Descriptive', 'Offline Descriptive'];

    /**
     * Exam Types a component may take for its Exam Mode: Online -> the Online types, Offline ->
     * the Offline types, Offline & Online (or anything else) -> all. Matched on the type's first
     * word, so a new "Online ..." / "Offline ..." type falls into place by itself. The faculty
     * setup page filters its dropdown with the same rule.
     *
     * @return list<string>
     */
    public static function examTypesFor(?string $mode): array
    {
        $prefix = match ($mode) {
            'Online'  => 'Online ',
            'Offline' => 'Offline ',
            default   => null,
        };

        return $prefix === null
            ? self::COMPONENT_EXAM_TYPES
            : array_values(array_filter(self::COMPONENT_EXAM_TYPES, fn ($t) => str_starts_with($t, $prefix)));
    }


    public function index(Request $request, ExaminationDriveDataTable $dataTable)
    {
        $scope = in_array($request->query('scope'), self::SCOPES, true)
            ? $request->query('scope') : 'active';

        return $dataTable->render('admin.coe.examination_drive.index', [
            'scope'     => $scope,
            // Active tab: running courses; Archived tab: archived ones (CourseMaster scopes).
            'courses'   => $this->courseNames($scope),
            'services'  => $this->serviceOptions(),
            'terms'     => $this->termNames(),
            'phases'    => $this->phaseValues(),
            'examTypes' => $this->examTypeNames(),
            // Create / Edit modal: only active types and terms can be chosen.
            'activeTerms'     => $this->termNames(true),
            'activeExamTypes' => $this->examTypeNames(true),
            // Start / End Date limits per course in the Create / Edit modal.
            'courseDates' => $this->courseDatesByName(),
            'statuses'  => ExaminationDrive::STATUSES,
            'subjects'  => $this->subjectNames(),
            // Subject and Faculty Mapping: Internal faculty only.
            'faculty'   => $this->facultyNames(true),
        ]);
    }

    /** Persist a new drive + its Subject/Faculty mapping rows. */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'drive_name'       => ['required', 'string', 'max:150'],
            'course'           => ['required', 'string', Rule::exists('course_master', 'course_name')],
            // No Service on a drive: passing marks follow each OT's own service
            // (ResultMarksService::passingPercent). Examination Type is optional.
            // Only active types / terms can be picked for a new drive.
            'exam_type'        => ['nullable', 'string', Rule::exists('examination_type_master', 'exam_type_name')->where('active_inactive', 1)],
            'term'             => ['required', 'string', Rule::exists('term_master', 'term_name')->where('active_inactive', 1)],
            'academic_session' => ['required', 'string', 'max:10'],
            'status'           => ['required', Rule::in(ExaminationDrive::STATUSES)],
            'start_date'       => ['required', 'date'],
            'end_date'         => ['required', 'date', 'after_or_equal:start_date'],
            // At least one complete Subject + Faculty row (store and update).
            'mappings'              => ['required', 'array', $this->needsSubjectFaculty()],
            'mappings.*.subject'    => ['required_with:mappings.*.faculty', 'nullable', 'string', 'max:150'],
            'mappings.*.faculty'    => ['required_with:mappings.*.subject', 'nullable', 'string', 'max:150', $this->internalFaculty()],
        ], [
            'mappings.required' => 'Add at least one Subject and Faculty in Subject and Faculty Mapping.',
        ]);

        $examTypePk = filled($data['exam_type'] ?? null)
            ? ExaminationTypeMaster::where('exam_type_name', $data['exam_type'])->value('pk')
            : null;
        $termPk     = TermMaster::where('term_name', $data['term'])->value('pk');
        $coursePk   = CourseMaster::where('course_name', $data['course'])->value('pk');
        $this->assertWithinCourse((int) $coursePk, $data['start_date'], $data['end_date'], 'start_date', 'Drive dates');
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
        abort_if($drive->cc_status === ExaminationDrive::CC_APPROVED, 422, 'This drive has been approved by the Course Coordinator and cannot be edited.');
        abort_if($drive->cc_status === ExaminationDrive::CC_PENDING, 422, 'This drive is pending at the Course Coordinator and cannot be edited.');
        // A re-exam drive is a copy of its main drive; editing would replace its faculty mapping.
        abort_if(\App\Services\COE\ReexamService::isReexamDrive((int) $drive->id), 422, 'A re-exam drive is copied from its main drive and cannot be edited here.');

        // Who already knew about the drive, to notify only what changes.
        $wasDraft = $drive->status === ExaminationDrive::STATUS_DRAFT;
        $before = $this->facultySubjects($drive->id);

        // The drive's current type / term stay valid even if since deactivated; a new choice must be active.
        $keepType = ExaminationTypeMaster::where('pk', $drive->examination_type_master_pk)->value('exam_type_name');
        $keepTerm = TermMaster::where('pk', $drive->term_master_pk)->value('term_name');
        $activeOrCurrent = fn (string $table, string $column, ?string $current) => Rule::exists($table, $column)
            ->where(fn ($q) => $q->where('active_inactive', 1)->when($current !== null, fn ($q) => $q->orWhere($column, $current)));

        $data = $request->validate([
            'drive_name'       => ['required', 'string', 'max:150'],
            'course'           => ['required', 'string', Rule::exists('course_master', 'course_name')],
            // No Service (see store()); Examination Type is optional.
            'exam_type'        => ['nullable', 'string', $activeOrCurrent('examination_type_master', 'exam_type_name', $keepType)],
            'term'             => ['required', 'string', $activeOrCurrent('term_master', 'term_name', $keepTerm)],
            'academic_session' => ['required', 'string', 'max:10'],
            'status'           => ['required', Rule::in(ExaminationDrive::STATUSES)],
            'start_date'       => ['required', 'date'],
            'end_date'         => ['required', 'date', 'after_or_equal:start_date'],
            // At least one complete Subject + Faculty row (store and update).
            'mappings'              => ['required', 'array', $this->needsSubjectFaculty()],
            'mappings.*.subject'    => ['required_with:mappings.*.faculty', 'nullable', 'string', 'max:150'],
            'mappings.*.faculty'    => ['required_with:mappings.*.subject', 'nullable', 'string', 'max:150', $this->internalFaculty($drive)],
        ], [
            'mappings.required' => 'Add at least one Subject and Faculty in Subject and Faculty Mapping.',
        ]);

        $examTypePk = filled($data['exam_type'] ?? null)
            ? ExaminationTypeMaster::where('exam_type_name', $data['exam_type'])->value('pk')
            : null;
        $termPk     = TermMaster::where('term_name', $data['term'])->value('pk');
        $coursePk   = CourseMaster::where('course_name', $data['course'])->value('pk');
        $this->assertWithinCourse((int) $coursePk, $data['start_date'], $data['end_date'], 'start_date', 'Drive dates');
        $statusCode = array_search($data['status'], ExaminationDrive::STATUSES, true);

        // service_master_pk is no longer written: an older drive keeps whatever it had.
        DB::transaction(function () use ($drive, $data, $examTypePk, $termPk, $coursePk, $statusCode) {
            $drive->update([
                'drive_name'                 => $data['drive_name'],
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
        abort_if($drive->cc_status === ExaminationDrive::CC_PENDING, 422, 'This drive is pending at the Course Coordinator and cannot be deleted.');
        abort_if($drive->cc_status === ExaminationDrive::CC_APPROVED, 422, 'This drive has been approved by the Course Coordinator and cannot be deleted.');
        abort_if(\App\Services\COE\ReexamService::hasReexams((int) $drive->id), 422, 'This drive has re-exams and cannot be deleted.');
        // A re-exam drive's link and candidate list go with it (coe_reexams cascade).
        $drive->delete();

        return response()->json(['message' => 'Examination drive deleted.']);
    }

    /**
     * CSV / Excel / PDF / Print of the drive list as shown on screen, in Sargam's
     * master export format (ExportsMasterGrid). Uses the grid's own query, so the
     * tab (scope) and the Course / Term / Type filters (course_filter, term_filter,
     * type_filter) give the same rows; ?q= is the search box, ?cols= the visible columns.
     */
    public function export(Request $request, ExaminationDriveDataTable $dataTable, string $format)
    {
        return $this->exportDrives($request, $dataTable->query(new ExaminationDrive()), $format, 'Examination Drive Configuration', 'ExaminationDrives');
    }

    /** Faculty list export: the faculty grid's own query (no Draft drives, its Status filter). */
    public function facultyExport(Request $request, ExaminationDriveFacultyDataTable $dataTable, string $format)
    {
        // The faculty grid has no Service column, so neither does its export.
        return $this->exportDrives($request, $dataTable->query(new ExaminationDrive()), $format, 'Examination Drive (Faculty)', 'ExaminationDrivesFaculty', ['service']);
    }

    /** One drive-list export (Sargam master format, landscape) for whichever grid query it is given. */
    private function exportDrives(Request $request, \Illuminate\Database\Eloquent\Builder $query, string $format, string $title, string $slug, array $without = [])
    {
        $format = strtolower($format);
        abort_unless(in_array($format, self::$exportFormats, true), 404);

        $term = trim((string) $request->query('q', ''));
        if ($term !== '') {
            $like = '%' . $term . '%';
            $query->where(fn ($q) => $q->where('drive_name', 'like', $like)
                ->orWhereHas('course', fn ($c) => $c->where('course_name', 'like', $like))
                ->orWhereHas('term', fn ($c) => $c->where('term_name', 'like', $like))
                ->orWhereHas('examinationType', fn ($c) => $c->where('exam_type_name', 'like', $like)));
        }

        $date = fn ($d) => optional($d)->format('d-m-Y') ?? '';
        $columns = [
            'sno' => ['heading' => 'S. No.', 'width' => '4%', 'align' => 'center', 'value' => fn ($row, int $i) => $i + 1],
            'name' => ['heading' => 'Drive Name', 'width' => '10%', 'value' => fn ($row) => filled($row->drive_name)
                ? $row->drive_name : ($row->examinationType->exam_type_name ?? 'Examination') . ' Drive — ' . $row->academic_session],
            'exam_type' => ['heading' => 'Examination Type', 'width' => '9%', 'value' => fn ($row) => $row->examinationType->exam_type_name ?? '-'],
            'term' => ['heading' => 'Term', 'width' => '7%', 'value' => fn ($row) => $row->term->term_name ?? '-'],
            'course' => ['heading' => 'Course', 'width' => '13%', 'value' => fn ($row) => $row->course->course_name ?? '-'],
            'service' => ['heading' => 'Service', 'width' => '6%', 'value' => fn ($row) => $row->service->service_short_name ?? '-'],
            'session' => ['heading' => 'Academic Session', 'width' => '6%', 'align' => 'center', 'value' => fn ($row) => $row->academic_session],
            'faculty' => ['heading' => 'Faculty', 'width' => '13%', 'value' => fn ($row) => implode(', ', $row->driveFacultyNames()) ?: '-'],
            'sub_coords' => ['heading' => 'Sub-Coordinates', 'width' => '6%', 'align' => 'center', 'value' => fn ($row) => count($row->subCoordinatorNames()) ?: '-'],
            'components' => ['heading' => 'Components', 'width' => '6%', 'align' => 'center', 'value' => fn ($row) => $row->component_maps_count ?: '-'],
            'start' => ['heading' => 'Start Date', 'width' => '7%', 'align' => 'center', 'value' => fn ($row) => $date($row->start_date)],
            'end' => ['heading' => 'End Date', 'width' => '7%', 'align' => 'center', 'value' => fn ($row) => $date($row->end_date)],
            'status' => ['heading' => 'Status', 'width' => '6%', 'align' => 'center', 'value' => fn ($row) => $row->progress()[0]],
        ];

        $filters = array_values(array_filter([
            $request->query('scope') === 'archived' ? 'Archived drives' : 'Active drives',
            filled($request->query('course_filter')) ? 'Course: ' . $request->query('course_filter') : null,
            filled($request->query('term_filter')) ? 'Term: ' . $request->query('term_filter') : null,
            filled($request->query('type_filter')) ? 'Examination Type: ' . $request->query('type_filter') : null,
            filled($request->query('status_filter')) ? 'Status: ' . (ExaminationDriveDataTable::STATUS_FILTERS[$request->query('status_filter')] ?? '') : null,
            $term !== '' ? 'Search: ' . $term : null,
        ]));

        return $this->renderMasterExport(
            $format,
            $query->get(),
            $this->resolveExportColumns($request, array_diff_key($columns, array_flip($without))),
            $title,
            $slug,
            implode('  |  ', $filters),
            'No examination drives to export',
            'landscape'
        );
    }

    /**
     * Validation rule: a Subject and Faculty Mapping faculty must be an active
     * Internal faculty. On edit, a faculty already mapped on the drive is kept
     * (drives created before this rule may map Guest faculty).
     */
    private function internalFaculty(?ExaminationDrive $drive = null): \Closure
    {
        $allowed = $this->facultyNames(true);
        if ($drive) {
            $allowed = array_merge($allowed, $drive->driveFacultyNames());
        }

        return function (string $attribute, $value, \Closure $fail) use ($allowed) {
            if (filled($value) && ! in_array(trim((string) $value), $allowed, true)) {
                $fail(trim((string) $value) . ' is not an Internal faculty. Only Internal faculty can be mapped to a drive.');
            }
        };
    }

    /** Validation rule: the Subject and Faculty Mapping has at least one row with both filled. */
    private function needsSubjectFaculty(): \Closure
    {
        return function (string $attribute, $rows, \Closure $fail) {
            $complete = collect(is_array($rows) ? $rows : [])
                ->contains(fn ($row) => is_array($row) && filled($row['subject'] ?? null) && filled($row['faculty'] ?? null));
            if (! $complete) {
                $fail('Add at least one Subject and Faculty in Subject and Faculty Mapping.');
            }
        };
    }

    /**
     * A course's dates as [start, end] (Y-m-d or null): course_master.start_year
     * holds the start date (despite its name; CourseController reads it so) and
     * end_date the end.
     *
     * @return array{0: ?string, 1: ?string}
     */
    private function courseDateRange(int $coursePk): array
    {
        $course = CourseMaster::where('pk', $coursePk)->first(['start_year', 'end_date']);
        $date = fn ($v) => $v ? \Carbon\Carbon::parse($v)->toDateString() : null;

        return [$date($course->start_year ?? null), $date($course->end_date ?? null)];
    }

    /** 422 on $field unless $start..$end sits inside the course's dates (a missing course date is not checked). */
    private function assertWithinCourse(int $coursePk, string $start, string $end, string $field, string $what): void
    {
        [$from, $to] = $this->courseDateRange($coursePk);
        $start = \Carbon\Carbon::parse($start)->toDateString();
        $end = \Carbon\Carbon::parse($end)->toDateString();

        if (($from && $start < $from) || ($to && $end > $to)) {
            $show = fn (?string $d) => $d ? \Carbon\Carbon::parse($d)->format('d-m-Y') : '…';
            throw \Illuminate\Validation\ValidationException::withMessages([
                $field => "{$what} must be within the course dates ({$show($from)} to {$show($to)}).",
            ]);
        }
    }

    /** Course name => [start, end] for the Create / Edit modal's date limits. */
    private function courseDatesByName(): array
    {
        return CourseMaster::get(['course_name', 'start_year', 'end_date'])
            ->mapWithKeys(fn ($c) => [$c->course_name => [
                $c->start_year ? \Carbon\Carbon::parse($c->start_year)->toDateString() : null,
                $c->end_date ? \Carbon\Carbon::parse($c->end_date)->toDateString() : null,
            ]])->all();
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
            // Ignored: passing marks are fixed — 50% of max marks, 25% for Bhutan services
            // (ResultMarksService::passingPercent), judged per OT at result time.
            'rows.*.passing_marks'  => ['nullable', 'numeric', 'min:0'],
            'rows.*.source'         => ['required', 'string', Rule::in(self::SOURCES)],
            // A Faculty-evaluated component needs its faculty.
            'rows.*.faculty'        => ['required_if:rows.*.source,Faculty', 'nullable', 'string', 'max:150'],
            // Ignored: worked out from Max Marks (withShareWeightage()).
            'rows.*.weightage'      => ['nullable', 'numeric', 'min:0', 'max:100'],
            // Required when the page sends them (faculty setup sends all four; the admin
            // Map Faculty page sends no Exam Type / range end, which keep their stored values).
            'rows.*.exam_date'      => ['sometimes', 'required', 'date'],
            'rows.*.exam_end_date'  => ['sometimes', 'required', 'date', 'after_or_equal:rows.*.exam_date'],
            // Tentative Exam Range with times, "Y-m-d H:i" (faculty setup). exam_date / exam_end_date
            // are then filled from these; the admin Map Faculty page sends only exam_date.
            'rows.*.exam_start_at'  => ['sometimes', 'required', 'date_format:Y-m-d H:i'],
            'rows.*.exam_end_at'    => ['sometimes', 'required', 'date_format:Y-m-d H:i', 'after:rows.*.exam_start_at'],
            'rows.*.exam_mode'      => ['sometimes', 'required', 'string', Rule::in(self::EXAM_MODES)],
            'rows.*.exam_type'      => ['sometimes', 'required', 'string', Rule::in(self::COMPONENT_EXAM_TYPES)],
            // JSON-encoded string, not an array: jQuery drops an empty array from the
            // request, so "[]" (cleared) would be indistinguishable from "not sent".
            'rows.*.sub_faculty'    => ['nullable', 'string', 'json'],
            // {"Faculty Name": ["FC101-001", ...]} roll numbers - same JSON-string reason as sub_faculty.
            'rows.*.codes'          => ['nullable', 'string', 'json'],
            // {"Faculty Name": ["UPC0001", ...]} Unique Participant Codes (Multi Faculty tab).
            'rows.*.unique_codes'   => ['nullable', 'string', 'json'],
        ], [
            'rows.*.exam_end_date.after_or_equal' => 'An exam range cannot end before it starts.',
            'rows.*.faculty.required_if' => 'Assign the faculty for every component evaluated through Faculty.',
            'rows.*.exam_date.required' => 'Select the Tentative Exam Range for every component.',
            'rows.*.exam_end_date.required' => 'Select the Tentative Exam Range for every component.',
            'rows.*.exam_start_at.required' => 'Select the Tentative Exam Range start (date and time) for every component.',
            'rows.*.exam_end_at.required' => 'Select the Tentative Exam Range end (date and time) for every component.',
            'rows.*.exam_start_at.date_format' => 'The Tentative Exam Range start must be a date and time.',
            'rows.*.exam_end_at.date_format' => 'The Tentative Exam Range end must be a date and time.',
            'rows.*.exam_end_at.after' => 'The Tentative Exam Range must end after it starts.',
            'rows.*.exam_mode.required' => 'Select the Exam Mode for every component.',
            'rows.*.exam_type.required' => 'Select the Exam Type for every component.',
        ]);

        $subjectPk = (int) $data['subject_master_pk'];
        if (! array_key_exists($subjectPk, $this->accessibleSubjects($drive))) {
            return response()->json(['message' => 'You are not mapped to this subject on the drive.'], 403);
        }
        if ($locked = $this->facultyLockMessage($drive)) {
            return response()->json(['message' => $locked], 422);
        }
        // Re-exam: everything stays as copied from the main drive; only the Tentative Exam
        // Range is taken from the request (ReexamService::setupRows). The checks below then
        // run on those rows as on any drive (inside the re-exam dates, no slot clash ...).
        if (ReexamService::isReexamDrive((int) $drive->id)) {
            try {
                $data['rows'] = app(ReexamService::class)->setupRows($drive, $subjectPk, $data['rows'] ?? []);
            } catch (ResultMarksException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
        }
        // Each component's exam range must sit inside the drive's dates.
        $from = optional($drive->start_date)->toDateString();
        $to = optional($drive->end_date)->toDateString();
        foreach ($data['rows'] ?? [] as $row) {
            $start = $row['exam_date'] ?? null;
            $end = $row['exam_end_date'] ?? $start;
            if ($start && (($from && $start < $from) || ($to && $end > $to))) {
                return response()->json(['message' => "The exam range of {$row['component']} must be within the drive dates ("
                    . $drive->start_date->format('d-m-Y') . ' to ' . $drive->end_date->format('d-m-Y') . ').'], 422);
            }
            // With times: compared on the date part, any time that day allowed.
            $startAt = isset($row['exam_start_at']) ? substr($row['exam_start_at'], 0, 10) : null;
            $endAt = isset($row['exam_end_at']) ? substr($row['exam_end_at'], 0, 10) : null;
            if (($startAt && $from && $startAt < $from) || ($endAt && $to && $endAt > $to)) {
                return response()->json(['message' => "The Tentative Exam Range of {$row['component']} must be within the drive dates ("
                    . $drive->start_date->format('d-m-Y') . ' to ' . $drive->end_date->format('d-m-Y') . ').'], 422);
            }
        }
        // Exam Type must suit the Exam Mode (examTypesFor()); checked where a page sends both.
        foreach ($data['rows'] ?? [] as $row) {
            if (filled($row['exam_type'] ?? null) && array_key_exists('exam_mode', $row)
                && ! in_array($row['exam_type'], self::examTypesFor($row['exam_mode']), true)) {
                return response()->json(['message' => "{$row['component']}: Exam Type {$row['exam_type']} does not match Exam Mode {$row['exam_mode']}. "
                    . 'Choose one of: ' . implode(', ', self::examTypesFor($row['exam_mode'])) . '.'], 422);
            }
        }
        // Pending at CC: what was submitted stays; faculty may correct it, not remove it.
        if ($this->isMappedFaculty($drive) && $drive->cc_status === ExaminationDrive::CC_PENDING) {
            $kept = collect($data['rows'] ?? [])->pluck('component')->all();
            $removed = $drive->componentMaps->where('subject_master_pk', $subjectPk)
                ->map(fn ($m) => $m->component->component_name ?? null)->filter()
                ->reject(fn ($name) => in_array($name, $kept, true));
            if ($removed->isNotEmpty()) {
                return response()->json(['message' => 'This drive is pending at the Course Coordinator, so submitted components cannot be removed: '
                    . $removed->implode(', ') . '.'], 422);
            }
        }

        $rows = collect($data['rows'] ?? [])->map(function (array $row) {
            if (array_key_exists('sub_faculty', $row) && $row['sub_faculty'] !== null) {
                $row['sub_faculty'] = $this->cleanNames((array) json_decode($row['sub_faculty'], true));
            } else {
                unset($row['sub_faculty']);
            }

            // The date-only columns follow the date + time ones when those are sent.
            if (filled($row['exam_start_at'] ?? null)) {
                $row['exam_date'] = substr($row['exam_start_at'], 0, 10);
            }
            if (filled($row['exam_end_at'] ?? null)) {
                $row['exam_end_date'] = substr($row['exam_end_at'], 0, 10);
            }

            foreach (['codes', 'unique_codes'] as $key) {
                if (array_key_exists($key, $row) && $row[$key] !== null) {
                    $row[$key] = (array) json_decode($row[$key], true);
                } else {
                    unset($row[$key]);
                }
            }

            return $row;
        });
        $rows = self::withShareWeightage($rows);

        if ($error = $this->slotClashError($drive, $subjectPk, $rows)) {
            return response()->json(['message' => $error], 422);
        }

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
        if ($error = $this->notInternalError($drive, $subjectPk,
            $rows->flatMap(fn ($row) => array_merge([$row['faculty'] ?? ''], $row['sub_faculty'] ?? []))->reject(fn ($name) => $name === 'Sargam 2.0')->all())) {
            return response()->json(['message' => $error], 422);
        }
        // One code, one faculty across the subject (the request carries all of its rows;
        // a row that does not send a list keeps its stored one).
        $stored = $drive->componentMaps->where('subject_master_pk', $subjectPk)->keyBy(fn ($m) => $m->component->component_name ?? '');
        $listsOf = fn (string $key, string $column) => $rows->map(fn ($row) => array_key_exists($key, $row)
            ? $row[$key] : ($stored[$row['component']]->{$column} ?? []))->all();
        $subjectName = $limits['subject_name'] ?? '';
        if ($error = $this->sharedCodesError($listsOf('codes', 'faculty_codes'), 'Roll number', $subjectName)
            ?? $this->sharedCodesError($listsOf('unique_codes', 'faculty_unique_codes'), 'Unique Participant Code', $subjectName)) {
            return response()->json(['message' => $error], 422);
        }

        $rollNumbers = array_column($this->courseRollNumbers($drive), 'code');
        $uniqueCodes = $this->courseUniqueCodes($drive);

        DB::transaction(function () use ($drive, $rows, $rollNumbers, $uniqueCodes, $limits, $subjectPk) {
            // Only this subject's rows: other faculty's subjects are left alone.
            $subjectRows = fn () => ExaminationDriveComponentMap::where('examination_drive_id', $drive->id)
                ->where('subject_master_pk', $subjectPk);

            // Sub-coordinators, exam end date and exam type by component, so a page
            // that does not send them (admin Map Faculty) does not wipe what faculty
            // set on Complete Setup.
            $previous = $subjectRows()
                ->get(['component_master_pk', 'sub_faculty_names', 'faculty_codes', 'faculty_unique_codes', 'exam_end_date', 'exam_type', 'exam_start_at', 'exam_end_at'])
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
                    // The standard (50%) value, for reference only: the result applies the
                    // OT's own service percentage (Bhutan 25%) to max_marks.
                    'passing_marks'        => ResultMarksService::passingMarks((float) $row['max_marks'], null),
                    'source'               => $row['source'],
                    'weightage'            => $row['weightage'],
                    'exam_date'            => $row['exam_date'] ?? null,
                    'exam_end_date'        => array_key_exists('exam_end_date', $row)
                        ? $row['exam_end_date']
                        : ($previous[$componentPk]->exam_end_date ?? null),
                    // Tentative Exam Range times. A page that sends only dates (admin Map
                    // Faculty) keeps the stored times while their date is unchanged; a changed
                    // date drops them, so the row falls back to its dates (whole days).
                    'exam_start_at'        => array_key_exists('exam_start_at', $row)
                        ? $row['exam_start_at']
                        : $this->keepTime($previous[$componentPk]->exam_start_at ?? null, $row['exam_date'] ?? null),
                    'exam_end_at'          => array_key_exists('exam_end_at', $row)
                        ? $row['exam_end_at']
                        : $this->keepTime($previous[$componentPk]->exam_end_at ?? null,
                            array_key_exists('exam_end_date', $row) ? $row['exam_end_date'] : optional($previous[$componentPk]->exam_end_date ?? null)->toDateString()),
                    'exam_mode'            => $row['exam_mode'] ?? null,
                    'exam_type'            => array_key_exists('exam_type', $row)
                        ? $row['exam_type']
                        : ($previous[$componentPk]->exam_type ?? null),
                    'sub_faculty_names'    => $subs = array_key_exists('sub_faculty', $row)
                        ? ($row['sub_faculty'] ?: null)
                        : ($previous[$componentPk]->sub_faculty_names ?? null),
                    'faculty_codes'        => $this->cleanCodes(
                        array_key_exists('codes', $row) ? $row['codes'] : ($previous[$componentPk]->faculty_codes ?? []),
                        $people = array_merge([trim((string) ($row['faculty'] ?? ''))], $subs ?? []),
                        $rollNumbers
                    ),
                    'faculty_unique_codes' => $this->cleanCodes(
                        array_key_exists('unique_codes', $row) ? $row['unique_codes'] : ($previous[$componentPk]->faculty_unique_codes ?? []),
                        $people,
                        $uniqueCodes
                    ),
                    'created_date'         => now(),
                ]);
            }
        });

        // A mapped faculty's save sends the drive to the course's CC only once every
        // mapped subject is set up; until then it stays Pending at Faculty. An admin's save never sends it.
        $notice = '';
        if ($this->isMappedFaculty($drive)) {
            [$done, $total] = $this->subjectsSetUp($drive);
            $notice = $done === $total
                ? $this->sendToCoordinator($drive)
                : "{$done} of {$total} subjects are set up. The drive goes to the Course Coordinator once all faculty complete their setup.";
        }

        return response()->json(['message' => trim('Component mapping saved. ' . $notice)]);
    }

    /**
     * No two exams of a course may overlap: the OTs of a course cannot sit two at once,
     * whichever drive, subject or faculty they belong to.
     *
     * The faculty setup save: each submitted row's date + time Tentative Exam Range is checked
     * (courseSlotClash()) — this drive + subject's own stored rows are left out, as this save
     * replaces them.
     *
     * @return string|null the first clash, or null
     */
    private function slotClashError(ExaminationDrive $drive, int $subjectPk, Collection $rows): ?string
    {
        $mine = $rows->filter(fn ($row) => filled($row['exam_start_at'] ?? null) && filled($row['exam_end_at'] ?? null))
            ->map(fn ($row) => [
                'component' => $row['component'],
                'start'     => Carbon::createFromFormat('Y-m-d H:i', $row['exam_start_at']),
                'end'       => Carbon::createFromFormat('Y-m-d H:i', $row['exam_end_at']),
            ])->values();

        return $this->courseSlotClash((int) $drive->course_master_pk, $mine, 'Tentative Exam Range',
            fn ($q) => $q->where('examination_drive_component_maps.examination_drive_id', '!=', $drive->id)
                ->orWhere('examination_drive_component_maps.subject_master_pk', '!=', $subjectPk)
                ->orWhereNull('examination_drive_component_maps.subject_master_pk'));
    }

    /**
     * The shared clash rule. $mine (component, start, end) is checked against
     *  1. itself, pair by pair, and
     *  2. every stored component of the course on a drive that is not closed, by the slot that
     *     counts (ExaminationDriveComponentMap::slotStart/End: the CC's confirmed slot once set,
     *     else the tentative one); rows $keep does not match are left out.
     * Overlap means start < other end and other start < end, so back-to-back slots are allowed.
     * A stored row with no times (only its dates, by default the whole drive) is not compared.
     *
     * @param  Collection<int, array{component:string, start:Carbon, end:Carbon}>  $mine
     * @param  \Closure  $keep  where-clause selecting the stored rows to compare with
     */
    private function courseSlotClash(int $coursePk, Collection $mine, string $label, \Closure $keep): ?string
    {
        if ($mine->isEmpty()) {
            return null;
        }
        $fmt = fn (Carbon $start, Carbon $end) => $start->format('d-m-Y H:i') . ' to '
            . ($start->isSameDay($end) ? $end->format('H:i') : $end->format('d-m-Y H:i'));
        $overlaps = fn (Carbon $aStart, Carbon $aEnd, Carbon $bStart, Carbon $bEnd) => $aStart->lt($bEnd) && $bStart->lt($aEnd);

        // 1. Among the slots being saved.
        foreach ($mine->values() as $i => $a) {
            foreach ($mine->values()->slice($i + 1) as $b) {
                if ($overlaps($a['start'], $a['end'], $b['start'], $b['end'])) {
                    return "The {$label} of {$a['component']} ({$fmt($a['start'], $a['end'])}) overlaps "
                        . "{$b['component']} ({$fmt($b['start'], $b['end'])}). Give each component its own slot.";
                }
            }
        }

        // 2. Against the course's other stored slots.
        $others = ExaminationDriveComponentMap::query()
            ->join('examination_drives as ed', 'ed.id', '=', 'examination_drive_component_maps.examination_drive_id')
            ->where('ed.course_master_pk', $coursePk)
            ->where('ed.status', '!=', ExaminationDrive::STATUS_CLOSED)
            ->where(fn ($q) => $q->whereNotNull('examination_drive_component_maps.proposed_start_at')
                ->orWhereNotNull('examination_drive_component_maps.exam_start_at'))
            ->where($keep)
            ->with(['component', 'subject', 'faculty'])
            ->get(['examination_drive_component_maps.*', 'ed.drive_name']);

        foreach ($mine as $a) {
            foreach ($others as $b) {
                $bStart = $b->slotStart();
                $bEnd = $b->slotEnd();
                if ($bStart && $bEnd && $overlaps($a['start'], $a['end'], $bStart, $bEnd)) {
                    $who = trim(implode(', ', array_filter([
                        trim((string) ($b->subject->subject_name ?? '')),
                        trim((string) ($b->component->component_name ?? '')),
                    ])));
                    $faculty = trim((string) ($b->faculty->full_name ?? ''));
                    $driveName = filled($b->drive_name) ? trim($b->drive_name) : 'Examination Drive #' . $b->examination_drive_id;

                    return "The {$label} of {$a['component']} ({$fmt($a['start'], $a['end'])}) clashes with "
                        . "{$who} on {$driveName}" . ($faculty !== '' ? " ({$faculty})" : '')
                        . " ({$fmt($bStart, $bEnd)}). The OTs of this course cannot sit two exams at once; choose another slot.";
                }
            }
        }

        return null;
    }

    /**
     * Add Component (faculty setup page): a new component, saved straight into Component
     * Master so every drive can use it from now on.
     *
     * Same access as the page: the drive must not be Draft, the user must be able to open one
     * of its subjects (pageSubject()), and the setup must still be editable. Duplicates are
     * refused, compared case- and space-insensitively ("Case  study" = "Case Study"), against
     * inactive components too — the COE re-activates those rather than a second copy being made.
     */
    public function addComponent(Request $request, int $id): JsonResponse
    {
        $drive = $this->findDriveOrFail($id);
        abort_if($drive->status === ExaminationDrive::STATUS_DRAFT, 404);
        $this->pageSubject($drive, $request);
        if ($locked = $this->facultyLockMessage($drive)) {
            return response()->json(['message' => $locked], 422);
        }

        $request->merge(['component_name' => self::componentNameKey((string) $request->input('component_name'), false)]);
        $data = $request->validate([
            'component_name' => ['required', 'string', 'min:2', 'max:100', 'no_html'],
        ], [], ['component_name' => 'Component']);

        $key = self::componentNameKey($data['component_name']);
        $existing = ComponentMaster::all(['pk', 'component_name', 'active_inactive'])
            ->first(fn ($c) => self::componentNameKey((string) $c->component_name) === $key);
        if ($existing) {
            return response()->json(['message' => (int) $existing->active_inactive === 1
                ? "\"{$existing->component_name}\" already exists. Select it from the Components list."
                : "\"{$existing->component_name}\" already exists but is inactive. Ask the COE to activate it in Component Master."], 422);
        }

        $component = ComponentMaster::create([
            'component_name' => $data['component_name'],
            'active_inactive' => 1,
            'created_date' => now(),
            'modified_date' => now(),
        ]);

        return response()->json([
            'message' => "Component \"{$component->component_name}\" added to Component Master.",
            'name' => $component->component_name,
        ]);
    }

    /**
     * A component name tidied (trimmed, inner spaces collapsed); with $compare, also lower-cased
     * so two spellings of the same name compare equal.
     */
    public static function componentNameKey(string $name, bool $compare = true): string
    {
        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? '');

        return $compare ? mb_strtolower($name) : $name;
    }

    /** A stored date + time, kept only while it still falls on $date (Y-m-d); else null. */
    private function keepTime($stored, ?string $date)
    {
        if ($stored === null) {
            return null;
        }

        return $date === null || $stored->toDateString() === substr($date, 0, 10) ? $stored : null;
    }

    /**
     * Weightage % is not entered: each component's share of the subject's total Max
     * Marks (40 of 90 -> 44.44). Rounded to 2 places, with the rounding remainder on the
     * last row so a subject's rows always total exactly 100. All zero when there are no
     * marks yet. Whatever the page sent is overwritten. Same rule as the setup screens' JS.
     */
    public static function withShareWeightage(Collection $rows): Collection
    {
        $max = fn (array $row) => (float) ($row['max_marks'] ?? 0);
        $total = round($rows->sum($max), 2);
        // The remainder goes to the last row that has marks, never to an empty one.
        $last = $rows->filter(fn ($row) => $max($row) > 0)->keys()->last();
        $given = 0.0;

        return $rows->map(function (array $row, $key) use ($total, $last, $max, &$given) {
            if ($total <= 0 || $max($row) <= 0) {
                $row['weightage'] = 0;
            } elseif ($key === $last) {
                $row['weightage'] = round(100 - $given, 2);
            } else {
                $row['weightage'] = round($max($row) / $total * 100, 2);
                $given += $row['weightage'];
            }

            return $row;
        });
    }

    /**
     * Marks rules for one subject's component rows on a drive:
     *  - passing marks are fixed: 50% of each row's max marks, 25% for Bhutan services
     *    (ResultMarksService::passingPercent), so nothing to look up or enter here;
     *  - total max marks <= the subject's Subject Max Marks;
     *  - total weightage <= 100.
     *
     * @return array{subject_name: string, subject_max: ?float}
     */
    private function marksLimits(ExaminationDrive $drive, int $subjectPk): array
    {
        $max = SubjectMaxMarksMaster::where('subject_master_pk', $subjectPk)->where('active_inactive', 1)->value('max_marks');

        return [
            'subject_name' => trim((string) SubjectMaster::where('pk', $subjectPk)->value('subject_name')),
            'subject_max' => $max === null ? null : (float) $max,
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
            // Per faculty: roll numbers picked in Select Roll Number; cleanCodes() sanitises.
            'codes'       => ['nullable', 'array'],
            // Per faculty: Unique Participant Codes picked on the Multi Faculty tab.
            'unique_codes' => ['nullable', 'array'],
        ]);

        if (! array_key_exists((int) $data['subject_master_pk'], $this->accessibleSubjects($drive))) {
            return response()->json(['message' => 'You are not mapped to this subject on the drive.'], 403);
        }
        if ($locked = $this->facultyLockMessage($drive)) {
            return response()->json(['message' => $locked], 422);
        }
        if (ReexamService::isReexamDrive((int) $drive->id)) {
            return response()->json(['message' => 'A re-exam keeps the faculty of its main drive. Only the Tentative Exam Range can be set.'], 422);
        }

        $subNames = $this->cleanNames($data['sub_faculty'] ?? []);
        $unknown = $this->unknownFacultyNames(array_merge([$data['faculty'] ?? ''], $subNames));
        if ($unknown) {
            return response()->json(['message' => 'Unknown faculty: ' . implode(', ', $unknown)], 422);
        }
        if ($error = $this->notInternalError($drive, (int) $data['subject_master_pk'], array_merge([$data['faculty'] ?? ''], $subNames))) {
            return response()->json(['message' => $error], 422);
        }
        // One code, one faculty across the subject: this row plus the subject's other rows as stored.
        $otherRows = $drive->componentMaps
            ->where('subject_master_pk', (int) $data['subject_master_pk'])
            ->reject(fn ($m) => ($m->component->component_name ?? null) === $data['component']);
        $subjectName = trim((string) SubjectMaster::where('pk', (int) $data['subject_master_pk'])->value('subject_name'));
        if ($error = $this->sharedCodesError(array_merge([$data['codes'] ?? []], $otherRows->map(fn ($m) => $m->faculty_codes ?? [])->all()), 'Roll number', $subjectName)
            ?? $this->sharedCodesError(array_merge([$data['unique_codes'] ?? []], $otherRows->map(fn ($m) => $m->faculty_unique_codes ?? [])->all()), 'Unique Participant Code', $subjectName)) {
            return response()->json(['message' => $error], 422);
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
            // Either list is kept as stored when the request does not send it.
            'faculty_codes'      => $this->cleanCodes(
                array_key_exists('codes', $data) ? ($data['codes'] ?? []) : ($map->faculty_codes ?? []),
                $people = array_merge([trim((string) ($data['faculty'] ?? ''))], $subNames),
                array_column($this->courseRollNumbers($drive), 'code')
            ),
            'faculty_unique_codes' => $this->cleanCodes(
                array_key_exists('unique_codes', $data) ? ($data['unique_codes'] ?? []) : ($map->faculty_unique_codes ?? []),
                $people,
                $this->courseUniqueCodes($drive)
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
        $maps = $drive->componentMaps()->with(['component', 'subject'])->orderBy('id')->get();
        if ($maps->isEmpty()) {
            return response()->json(['message' => 'This drive has no components to schedule. Ask the faculty to complete the setup first.'], 422);
        }

        // One proposed slot per component, keyed by its row id: slots[<id>][start|end] = "Y-m-d\TH:i".
        $data = $request->validate([
            'slots'         => ['required', 'array'],
            'slots.*.start' => ['required', 'date_format:Y-m-d\TH:i'],
            'slots.*.end'   => ['required', 'date_format:Y-m-d\TH:i'],
        ], [
            'slots.required' => 'Enter the proposed exam date and time for every component.',
            'slots.*.start.required' => 'Enter the proposed start exam date and time for every component.',
            'slots.*.end.required' => 'Enter the proposed end exam date and time for every component.',
            'slots.*.start.date_format' => 'Enter the proposed start exam date and time for every component.',
            'slots.*.end.date_format' => 'Enter the proposed end exam date and time for every component.',
        ]);

        $label = fn ($map) => trim(implode(' – ', array_filter([
            trim((string) ($map->subject->subject_name ?? '')), trim((string) ($map->component->component_name ?? '')),
        ]))) ?: 'Component #' . $map->id;
        $slots = collect();
        foreach ($maps as $map) {
            $slot = $data['slots'][$map->id] ?? null;
            if (! $slot) {
                return response()->json(['message' => "Enter the proposed exam date and time for {$label($map)}."], 422);
            }
            $start = Carbon::createFromFormat('Y-m-d\TH:i', $slot['start']);
            $end = Carbon::createFromFormat('Y-m-d\TH:i', $slot['end']);
            if ($end->lte($start)) {
                return response()->json(['message' => "The proposed exam of {$label($map)} must end after it starts."], 422);
            }
            $slots->push(['map' => $map, 'component' => $label($map), 'start' => $start, 'end' => $end]);
        }

        // Within the course dates (the drive's dates are set from these slots below). A
        // re-exam may run after the course ends: its slots sit inside the re-exam dates COE set.
        if (ReexamService::isReexamDrive((int) $drive->id)) {
            $from = optional($drive->start_date)->toDateString();
            $to = optional($drive->end_date)->toDateString();
            if (($from && $slots->min('start')->toDateString() < $from) || ($to && $slots->max('end')->toDateString() > $to)) {
                return response()->json(['message' => 'The proposed exam dates must be within the re-exam dates ('
                    . $drive->start_date->format('d-m-Y') . ' to ' . $drive->end_date->format('d-m-Y') . ').'], 422);
            }
        } else {
            $this->assertWithinCourse((int) $drive->course_master_pk, $slots->min('start')->toDateString(),
                $slots->max('end')->toDateString(), 'slots', 'The proposed exam dates');
        }
        // Same clash rule as the faculty setup; this drive's own rows are replaced, so left out.
        if ($error = $this->courseSlotClash((int) $drive->course_master_pk, $slots, 'proposed exam slot',
            fn ($q) => $q->where('examination_drive_component_maps.examination_drive_id', '!=', $drive->id))) {
            return response()->json(['message' => $error], 422);
        }

        DB::transaction(function () use ($drive, $slots) {
            foreach ($slots as $slot) {
                $slot['map']->update(['proposed_start_at' => $slot['start'], 'proposed_end_at' => $slot['end']]);
            }

            // The drive spans its components' confirmed slots; its dates take the date part
            // (read everywhere in COE) and proposed_* keep the times.
            $first = $slots->min('start');
            $last = $slots->max('end');
            $drive->update([
                'cc_status'           => ExaminationDrive::CC_APPROVED,
                'start_date'          => $first->toDateString(),
                'end_date'            => $last->toDateString(),
                'proposed_start_date' => $first,
                'proposed_end_date'   => $last,
                'cc_remarks'          => null,
                'cc_action_by'        => auth()->user()->user_id,
                'cc_action_at'        => now(),
                'modified_date'       => now(),
            ]);
        });

        $notice = $this->notifyFacultyApproved($drive->fresh());
        // Components whose exam is already over open for marks entry right away. After the
        // CC's slots are saved above, so "over" is judged on the confirmed slots.
        try {
            app(\App\Services\COE\ResultMarksService::class)->notifyEntryReady((int) $drive->id);
        } catch (\Throwable $e) {
            Log::error('Marks entry ready notification failed', ['drive_id' => $drive->id, 'error' => $e->getMessage()]);
        }

        return response()->json(['message' => trim('Examination drive approved. ' . $notice)]);
    }

    /**
     * Tell every faculty mapped on the drive that the Course Coordinator approved it
     * and the question paper can now be created; the notification opens My Question
     * Paper (config/notifications.php exam_drive.ExaminationDriveApproved). Faculty
     * without a linked login (faculty_master.employee_master_pk) cannot be reached.
     * A failure is logged, never fails the approval.
     */
    private function notifyFacultyApproved(ExaminationDrive $drive): string
    {
        $facultySubjects = $this->facultySubjects($drive->id);
        $userIds = FacultyMaster::whereIn('pk', array_keys($facultySubjects))
            ->whereIn('employee_master_pk', DB::table('user_credentials')->select('user_id'))
            ->pluck('employee_master_pk', 'pk');

        $name = filled($drive->drive_name) ? $drive->drive_name : 'Examination Drive #' . $drive->id;
        // With the CC's times when the proposal has them.
        $dates = $drive->proposed_start_date && $drive->proposed_end_date
            ? $drive->proposed_start_date->format('d-m-Y H:i') . ' to ' . $drive->proposed_end_date->format('d-m-Y H:i')
            : optional($drive->start_date)->format('d-m-Y') . ' to ' . optional($drive->end_date)->format('d-m-Y');
        $sent = 0;
        try {
            $service = app(NotificationService::class);
            foreach ($userIds as $facultyPk => $userId) {
                $service->create(
                    (int) $userId, 'exam_drive', 'ExaminationDriveApproved', $drive->id, 'Examination Drive Approved',
                    "{$name} has been approved by the Course Coordinator (exam dates {$dates}). "
                        . 'You can now create the question paper for ' . implode(', ', $facultySubjects[$facultyPk]) . '.'
                );
                $sent++;
            }
        } catch (\Throwable $e) {
            Log::error('Examination drive approval notification failed', ['drive_id' => $drive->id, 'error' => $e->getMessage()]);
        }

        $missing = count($facultySubjects) - $userIds->count();

        return trim(($sent ? "{$sent} faculty notified." : '')
            . ($missing ? " {$missing} faculty could not be notified (no login linked)." : ''));
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
    /**
     * Full drive details for the CC's Approve window: drive info, Subject -> Faculty
     * mapping and every component set up by the faculty. Only the CC of the
     * drive's course (or Super Admin) may read it.
     */
    public function ccDetails(int $id): JsonResponse
    {
        $drive = ExaminationDrive::with(['facultyMaps.subject', 'facultyMaps.faculty'])->findOrFail($id);
        $user = auth()->user();
        abort_unless(
            $user && ($user->hasRole('Super Admin') || app(CourseCoordinatorService::class)->isCoordinatorOf($user, (int) $drive->course_master_pk)),
            403, 'You are not the Course Coordinator of this drive\'s course.'
        );

        $view = $this->driveViewModel($drive);

        return response()->json([
            'name'       => $view['name'],
            'course'     => $view['course'],
            'service'    => $view['service_name'] ?: $view['service'],
            'exam_type'  => $view['exam_type'],
            'term'       => $view['term'],
            'session'    => $view['session'],
            'start_date' => $view['start_date'],
            'end_date'   => $view['end_date'],
            'status'     => $view['status'],
            'mapping'    => $drive->facultyMaps->map(fn ($map) => [
                'subject' => trim((string) ($map->subject->subject_name ?? '')) ?: '-',
                'faculty' => trim((string) ($map->faculty->full_name ?? '')) ?: '-',
            ])->values(),
            'components' => collect($view['component_rows'])->map(fn ($row) => [
                'id'            => $row['id'],
                'cc_start'      => $row['cc_start'],
                'cc_end'        => $row['cc_end'],
                'subject'       => $row['subject'],
                'component'     => $row['component'],
                'max_marks'     => $row['max_marks'],
                'passing_marks' => $row['passing_marks'],
                'weightage'     => $row['weightage'],
                'exam_date'     => $row['exam_range'],
                'exam_mode'     => $row['exam_mode'],
                'exam_type'     => $row['exam_type'] ?: '-',
                'faculty'       => implode(', ', $row['sub_coordinators']) ?: $row['faculty'],
            ])->values(),
        ]);
    }

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

    /**
     * Why a mapped faculty may not change the drive's setup any more, or null.
     * Approved by the Course Coordinator = final for faculty (admins are not affected).
     */
    private function facultyLockMessage(ExaminationDrive $drive): ?string
    {
        return $this->isMappedFaculty($drive) && $drive->cc_status === ExaminationDrive::CC_APPROVED
            ? 'This drive has been approved by the Course Coordinator and can no longer be edited.'
            : null;
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
     * How many of the drive's mapped subjects have component rows saved, of how many.
     *
     * @return array{0: int, 1: int}
     */
    private function subjectsSetUp(ExaminationDrive $drive): array
    {
        $subjects = $drive->facultyMaps->pluck('subject_master_pk')->unique();
        // A re-exam's rows are copied in from the start: set up = every component dated.
        $saved = ReexamService::isReexamDrive((int) $drive->id)
            ? collect(ReexamService::datedSubjects((int) $drive->id))->intersect($subjects->map(fn ($pk) => (int) $pk))
            : ExaminationDriveComponentMap::where('examination_drive_id', $drive->id)
                ->whereIn('subject_master_pk', $subjects)->distinct()->pluck('subject_master_pk');

        return [$saved->count(), $subjects->count()];
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
            // Every component, inactive too, for Add Component's as-you-type duplicate check.
            'allComponents' => ComponentMaster::orderBy('component_name')->get(['component_name', 'active_inactive'])
                ->map(fn ($c) => ['name' => trim((string) $c->component_name), 'active' => (int) $c->active_inactive === 1])->values(),
            'addComponentUrl' => route('admin.coe.faculty-examination-drive.components.store', ['id' => $drive->id, 'subject' => $subjectPk]),
            'sources'     => self::SOURCES,
            // Assign Faculty: Internal faculty only, plus anyone already assigned on
            // this subject's rows (set before the rule) so they still show.
            'faculty'     => $this->setupFacultyNames($drive, $subjectPk),
            'examModes'   => self::EXAM_MODES,
            'componentExamTypes' => self::COMPONENT_EXAM_TYPES,
            // Select Roll Number / Select Unique Codes list the course's generated
            // roll numbers and Unique Participant Codes (COE › Roll Numbers / Unique Participant Code).
            'rollNumbers' => $this->courseRollNumbers($drive),
            // Exam Range picker limits: the drive's own dates.
            'driveRange'  => [optional($drive->start_date)->toDateString(), optional($drive->end_date)->toDateString()],
            'uniqueCodes' => $this->courseUniqueCodes($drive),
            'limits'      => $this->marksLimits($drive, $subjectPk),
            // Approved: read-only; pending at CC: submitted rows cannot be removed (faculty only).
            'readOnly'    => $this->facultyLockMessage($drive) !== null,
            'keepSubmitted' => $this->isMappedFaculty($drive) && $drive->cc_status === ExaminationDrive::CC_PENDING,
            'subjectTabs' => $this->accessibleSubjects($drive),
            'subjectPk'   => $subjectPk,
            // Re-exam: only the Tentative Exam Range is editable (ReexamService::setupRows).
            'reexam'      => $this->reexamInfo($drive),
        ]);
    }

    /** ['no' => 1, 'main' => 'Law Drive'] when $drive is a re-exam, else null. */
    private function reexamInfo(ExaminationDrive $drive): ?array
    {
        $link = ReexamService::linksFor([(int) $drive->id])[(int) $drive->id] ?? null;
        if (! $link) {
            return null;
        }
        $main = ExaminationDrive::find($link->main_drive_id);

        return ['no' => (int) $link->reexam_no, 'main' => filled($main?->drive_name) ? trim($main->drive_name) : 'Examination Drive #' . $link->main_drive_id];
    }

    /**
     * Download / Print of the Map Faculty (faculty setup) page: the drive's saved
     * components for the page's subject, in the Sargam master export format
     * (CSV / Excel / PDF / Print). Same access as the page: a mapped faculty gets
     * only their own subject, a Draft drive is not available.
     */
    public function facultySetupExport(Request $request, int $id, string $format)
    {
        $drive = $this->findDriveOrFail($id);
        abort_if($drive->status === ExaminationDrive::STATUS_DRAFT, 404);
        $subjectPk = $this->pageSubject($drive, $request);
        $view = $this->driveViewModel($drive, $subjectPk);

        $codes = fn (array $byFaculty) => collect($byFaculty)->map(fn ($c) => is_array($c) ? count($c) : 1)->sum();
        // 90.00 -> 90, 12.50 -> 12.5, as on the page.
        $num = fn ($v) => $v === null || $v === '' ? '-' : rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
        $columns = [
            'sno'        => ['heading' => 'S. No.', 'width' => '4%', 'align' => 'center', 'value' => fn ($row, int $i) => $i + 1],
            'subject'    => ['heading' => 'Subject', 'width' => '11%', 'value' => fn ($row) => $row['subject']],
            'component'  => ['heading' => 'Component', 'width' => '11%', 'value' => fn ($row) => $row['component']],
            'max'        => ['heading' => 'Max Marks', 'width' => '6%', 'align' => 'center', 'value' => fn ($row) => $num($row['max_marks'])],
            'pass'       => ['heading' => 'Passing Marks', 'width' => '6%', 'align' => 'center', 'value' => fn ($row) => $row['passing_marks']],
            'source'     => ['heading' => 'Evaluation Through', 'width' => '8%', 'value' => fn ($row) => $row['source']],
            'faculty'    => ['heading' => 'Faculty', 'width' => '15%', 'value' => fn ($row) => implode(', ', $row['sub_coordinators']) ?: $row['faculty']],
            'weightage'  => ['heading' => 'Weightage %', 'width' => '6%', 'align' => 'center', 'value' => fn ($row) => $num($row['weightage'])],
            'range'      => ['heading' => 'Tentative Exam Range', 'width' => '11%', 'align' => 'center', 'value' => fn ($row) => $row['exam_range']],
            'mode'       => ['heading' => 'Exam Mode', 'width' => '7%', 'value' => fn ($row) => $row['exam_mode']],
            'type'       => ['heading' => 'Exam Type', 'width' => '8%', 'value' => fn ($row) => $row['exam_type'] ?: '-'],
            'rolls'      => ['heading' => 'Roll Numbers Assigned', 'width' => '7%', 'align' => 'center', 'value' => fn ($row) => $codes($row['faculty_codes']) ?: '-'],
        ];

        $filters = implode('  |  ', [
            'Drive: ' . $view['name'],
            'Course: ' . $view['course'],
            'Term: ' . $view['term'],
            'Academic Session: ' . $view['session'],
            'Passing: 50% of max marks (Bhutan services 25%)',
            'Drive Dates: ' . ($view['start_date'] ?? '-') . ' to ' . ($view['end_date'] ?? '-'),
            'Status: ' . $view['status'],
        ]);

        return $this->renderMasterExport(
            strtolower($format),
            collect($view['component_rows']),
            $this->resolveExportColumns($request, $columns),
            'Faculty Coordinates & Components',
            'FacultyCoordinatesComponents_' . $drive->id,
            $filters,
            'No components set up for this subject yet',
            'landscape'
        );
    }

    /**
     * Download / Print of Faculty Coordinates & Components (coordinates page), in
     * the Sargam master export format (CSV / Excel / PDF / Print): one line per
     * faculty per component, with their roll numbers and Unique Participant Codes.
     */
    public function coordinatesExport(Request $request, int $id, string $format)
    {
        $drive = $this->findDriveOrFail($id);
        $view = $this->driveViewModel($drive);
        $num = fn ($v) => $v === null || $v === '' ? '-' : rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');

        // One line per person on the component (system-sourced rows: one line).
        $lines = collect($view['component_rows'])->flatMap(function (array $row) {
            $system = in_array($row['source'], ['Sargam', 'Sargam 2.0'], true);
            $people = $system ? [$row['source']] : ($row['sub_coordinators'] ?: ['-']);

            return collect($people)->map(fn ($person) => $row + [
                'person' => $person,
                'rolls'  => $system ? '-' : (self::codeRangeText($row['faculty_codes'][$person] ?? null) ?: 'Not allocated'),
                'uniq'   => $system ? '-' : (self::codeRangeText($row['faculty_unique_codes'][$person] ?? null) ?: 'Not allocated'),
            ]);
        })->values();

        $columns = [
            'sno'       => ['heading' => 'S. No.', 'width' => '4%', 'align' => 'center', 'value' => fn ($row, int $i) => $i + 1],
            'subject'   => ['heading' => 'Subject', 'width' => '9%', 'value' => fn ($row) => $row['subject']],
            'component' => ['heading' => 'Component', 'width' => '9%', 'value' => fn ($row) => $row['component']],
            'max'       => ['heading' => 'Max Marks', 'width' => '5%', 'align' => 'center', 'value' => fn ($row) => $num($row['max_marks'])],
            'pass'      => ['heading' => 'Passing Marks', 'width' => '5%', 'align' => 'center', 'value' => fn ($row) => $row['passing_marks']],
            'source'    => ['heading' => 'Source', 'width' => '6%', 'value' => fn ($row) => $row['source']],
            'faculty'   => ['heading' => 'Faculty / Subcoordinator', 'width' => '11%', 'value' => fn ($row) => $row['person']],
            'rolls'     => ['heading' => "OT's Roll Number", 'width' => '12%', 'value' => fn ($row) => $row['rolls']],
            'uniq'      => ['heading' => 'Unique Participant Codes', 'width' => '11%', 'value' => fn ($row) => $row['uniq']],
            'weightage' => ['heading' => 'Weightage %', 'width' => '6%', 'align' => 'center', 'value' => fn ($row) => $num($row['weightage'])],
            'range'     => ['heading' => 'Tentative Exam Range', 'width' => '9%', 'align' => 'center', 'value' => fn ($row) => $row['exam_range']],
            'mode'      => ['heading' => 'Exam Mode', 'width' => '6%', 'value' => fn ($row) => $row['exam_mode']],
            'type'      => ['heading' => 'Exam Type', 'width' => '7%', 'value' => fn ($row) => $row['exam_type'] ?: '-'],
        ];

        $filters = implode('  |  ', [
            'Drive: ' . $view['name'],
            'Examination Type: ' . $view['exam_type'],
            'Term: ' . $view['term'],
            'Course: ' . $view['course'],
            'Academic Session: ' . $view['session'],
            'Faculty: ' . $view['faculty'],
            'Status: ' . $view['status'],
        ]);

        return $this->renderMasterExport(
            strtolower($format),
            $lines,
            $this->resolveExportColumns($request, $columns),
            'Faculty Coordinates & Components',
            'FacultyCoordinates_' . $drive->id,
            $filters,
            'No components mapped to this drive yet',
            'landscape'
        );
    }

    /**
     * Codes as ranges of consecutive codes: "AI-0001 – AI-0004, AI-0007 (5 OTs)";
     * a single free-text code as is; '' for none. Shared with coordinates.blade.php.
     */
    public static function codeRangeText($codes): string
    {
        if ($codes === null || $codes === '' || $codes === []) {
            return '';
        }
        if (! is_array($codes)) {
            return (string) $codes;
        }
        $parts = [];
        $run = null; // [prefix, last number, first code, last code]
        foreach (array_values($codes) as $code) {
            $code = (string) $code;
            if (preg_match('/^(.*?)(\d+)$/', $code, $m) && $run && $run[0] === $m[1] && (int) $m[2] === $run[1] + 1) {
                $run[1] = (int) $m[2];
                $run[3] = $code;
                continue;
            }
            if ($run) {
                $parts[] = $run[2] === $run[3] ? $run[2] : $run[2] . ' – ' . $run[3];
            }
            $run = preg_match('/^(.*?)(\d+)$/', $code, $m) ? [$m[1], (int) $m[2], $code, $code] : null;
            if (! $run) {
                $parts[] = $code;
            }
        }
        if ($run) {
            $parts[] = $run[2] === $run[3] ? $run[2] : $run[2] . ' – ' . $run[3];
        }

        return implode(', ', $parts) . ' (' . count($codes) . ' OT' . (count($codes) === 1 ? '' : 's') . ')';
    }

    /**
     * The drive course's roll numbers (coe_roll_numbers, generated or frozen) as
     * [code, name], in serial order. A course with several batches uses the batch
     * matching the drive's academic session, else its latest one.
     *
     * @return array<int, array{code: string, name: string}>
     */
    private function courseRollNumbers(ExaminationDrive $drive): array
    {
        $allocations = RollNumberAllocation::where('course_master_pk', $drive->course_master_pk);
        $allocation = (clone $allocations)->where('batch', (int) $drive->academic_session)->first()
            ?? $allocations->orderByDesc('batch')->first();
        if (! $allocation) {
            return [];
        }

        return DB::table('coe_roll_numbers as r')
            ->join('student_master as s', 's.pk', '=', 'r.student_master_pk')
            ->where('r.roll_number_allocation_pk', $allocation->pk)
            ->orderBy('r.serial_number')
            ->get(['r.roll_number as code', 's.display_name as name'])
            ->map(fn ($r) => ['code' => trim($r->code), 'name' => trim((string) $r->name)])
            ->all();
    }

    /**
     * The drive course's Unique Participant Codes (coe_participant_codes, pending
     * or frozen), codes only, in code order; same batch rule as roll numbers.
     *
     * @return string[]
     */
    private function courseUniqueCodes(ExaminationDrive $drive): array
    {
        $codes = ParticipantCode::where('course_master_pk', $drive->course_master_pk);
        $batch = (clone $codes)->where('batch', (int) $drive->academic_session)->exists()
            ? (int) $drive->academic_session
            : (clone $codes)->max('batch');
        if ($batch === null) {
            return [];
        }

        return $codes->where('batch', $batch)
            ->pluck('unique_code')
            ->map(fn ($c) => trim($c))
            ->sort(fn ($a, $b) => $this->compareOtCodes($a, $b))
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
            // Re-exam drive: its main drive + candidates; main drive: its re-exams (ReexamService::overview).
            'reexam'   => app(ReexamService::class)->overview($drive),
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
     * Codes per faculty (roll numbers or Unique Participant Codes), kept only for
     * names still on the row and, when $allowed is given, only codes in it.
     *
     * @param  array<mixed>  $codes
     * @param  string[]  $names
     * @param  string[]|null  $allowed
     * @return array<string, string[]|string>|null
     */
    private function cleanCodes(array $codes, array $names, ?array $allowed = null): ?array
    {
        $clean = [];
        foreach ($codes as $name => $code) {
            if (! is_string($name) || ! in_array(trim($name), $names, true)) {
                continue;
            }
            if (is_array($code)) {
                // Codes picked from a list: only the course's real ones.
                $list = collect($code)
                    ->filter(fn ($c) => is_scalar($c))
                    ->map(fn ($c) => trim((string) $c))
                    ->filter()
                    ->when($allowed !== null, fn ($l) => $l->filter(fn ($c) => in_array($c, $allowed, true)))
                    ->unique()
                    ->sort(fn ($a, $b) => $this->compareOtCodes($a, $b))
                    ->values()
                    ->all();
                if ($list) {
                    $clean[trim($name)] = $list;
                }
            } elseif ($allowed === null && is_scalar($code) && trim((string) $code) !== '') {
                // A single free-text code, only where no list restricts the values.
                $clean[trim($name)] = mb_substr(trim((string) $code), 0, 20);
            }
        }

        return $clean ?: null;
    }

    /**
     * Orders codes by prefix, then number value: A2 < A5 < A10 < A044 (=44), and
     * then the rest naturally, so IT128-001 < IT128-002 < IT128-010.
     * strnatcmp() alone reads a leading zero as a fraction and puts A044 before A05;
     * the rest is compared as text because "-002" <=> "-001" compares as numbers (-2 < -1).
     */
    private function compareOtCodes(string $a, string $b): int
    {
        $split = fn (string $c) => preg_match('/^(\D*)(\d+)(.*)$/', $c, $m) ? [strtoupper($m[1]), (int) $m[2], $m[3]] : [strtoupper($c), 0, ''];
        [$pa, $pb] = [$split($a), $split($b)];

        return [$pa[0], $pa[1]] <=> [$pb[0], $pb[1]] ?: strnatcmp($pa[2], $pb[2]);
    }

    /**
     * Names not found in faculty_master. Rejecting these, instead of storing
     * them, keeps free text out of sub_faculty_names.
     *
     * @param  string[]  $names
     * @return string[]
     */
    /**
     * Assign Faculty list on the faculty setup page: active Internal faculty, plus
     * names already assigned on this subject's component rows.
     *
     * @return string[]
     */
    private function setupFacultyNames(ExaminationDrive $drive, int $subjectPk): array
    {
        $names = $this->facultyNames(true);
        foreach ($this->assignedFacultyNames($drive, $subjectPk) as $name) {
            if (! in_array($name, $names, true)) {
                $names[] = $name;
            }
        }
        sort($names, SORT_NATURAL | SORT_FLAG_CASE);

        return $names;
    }

    /** @return string[] faculty + sub-faculty already stored on the subject's component rows */
    private function assignedFacultyNames(ExaminationDrive $drive, int $subjectPk): array
    {
        return $drive->componentMaps->where('subject_master_pk', $subjectPk)
            ->flatMap(fn ($map) => array_merge([trim((string) ($map->faculty->full_name ?? ''))], $map->sub_faculty_names ?? []))
            ->map(fn ($name) => trim((string) $name))
            ->filter()->unique()->values()->all();
    }

    /**
     * A mapped faculty (the setup page) may assign only Internal faculty; names
     * already on the subject's rows are kept. Admins on Map Faculty are not limited.
     *
     * @param  string[]  $names
     */
    private function notInternalError(ExaminationDrive $drive, int $subjectPk, array $names): ?string
    {
        if (! $this->isMappedFaculty($drive)) {
            return null;
        }
        $allowed = array_merge($this->facultyNames(true), $this->assignedFacultyNames($drive, $subjectPk));
        $bad = array_values(array_diff($this->cleanNames($names), $allowed));

        return $bad ? implode(', ', $bad) . (count($bad) === 1 ? ' is' : ' are') . ' not Internal faculty. Only Internal faculty can be assigned.' : null;
    }

    /**
     * One code, one faculty per subject: a roll number or Unique Participant Code
     * given to two different faculty on the subject's component rows is refused.
     * The same faculty may keep a code on several components; another subject
     * (evaluated by its own faculty) is not affected.
     *
     * @param  array<int, mixed>  $lists  one {faculty: [codes]} map per component row
     */
    private function sharedCodesError(array $lists, string $what, string $subject): ?string
    {
        $owners = [];
        foreach ($lists as $byFaculty) {
            foreach (is_array($byFaculty) ? $byFaculty : [] as $name => $codes) {
                foreach (is_array($codes) ? $codes : [] as $code) {
                    $code = trim((string) $code);
                    if ($code !== '') {
                        $owners[$code][trim((string) $name)] = true;
                    }
                }
            }
        }
        $shared = array_filter($owners, fn ($names) => count($names) > 1);
        if (! $shared) {
            return null;
        }
        $code = array_key_first($shared);

        return "{$what} {$code} is assigned to more than one faculty in {$subject} ("
            . implode(', ', array_keys($shared[$code])) . '). Each ' . strtolower($what) . ' can be assigned to one faculty only'
            . (count($shared) > 1 ? ' (' . count($shared) . ' codes affected).' : '.');
    }

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
            'componentMaps.component', 'componentMaps.faculty', 'componentMaps.subject',
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
                'id'             => (int) $map->id,
                'component'      => $map->component->component_name ?? '-',
                // CC approval pickers ("Y-m-d\TH:i"): the CC's slot if set, else the tentative
                // one, else its dates as whole days.
                'cc_start'       => optional($map->proposed_start_at ?? $map->exam_start_at)->format('Y-m-d\TH:i')
                    ?? (optional($map->exam_date ?? $drive->start_date)->format('Y-m-d') ? ($map->exam_date ?? $drive->start_date)->format('Y-m-d') . 'T00:00' : ''),
                'cc_end'         => optional($map->proposed_end_at ?? $map->exam_end_at)->format('Y-m-d\TH:i')
                    ?? (optional($map->exam_end_date ?? $drive->end_date)->format('Y-m-d') ? ($map->exam_end_date ?? $drive->end_date)->format('Y-m-d') . 'T23:59' : ''),
                'subject'        => trim((string) ($map->subject->subject_name ?? '')) ?: '-',
                'max_marks'      => $map->max_marks,
                'passing_marks'  => ResultMarksService::passingMarksLabel($map->max_marks === null ? null : (float) $map->max_marks),
                'source'         => $map->source ?: self::SOURCES[0],
                'faculty'        => trim($map->faculty->full_name ?? '') ?: '-',
                'weightage'      => $map->weightage,
                'exam_date'      => optional($map->exam_date)->format('d/m/Y')
                    ?: (optional($drive->start_date)->format('d/m/Y') ?: now()->format('d/m/Y')),
                'exam_end_date'  => optional($map->exam_end_date)->format('d/m/Y')
                    ?: (optional($drive->end_date)->format('d/m/Y') ?: now()->format('d/m/Y')),
                // Tentative Exam Range times: "Y-m-d H:i" for the pickers ('' when not set), and the
                // label every list / export shows — with times when set, else the dates.
                'exam_start_at'  => optional($map->exam_start_at)->format('Y-m-d H:i') ?? '',
                'exam_end_at'    => optional($map->exam_end_at)->format('Y-m-d H:i') ?? '',
                // No range saved yet (a re-exam's copied rows start without one).
                'dated'          => $map->exam_date !== null,
                'exam_range'     => ($map->exam_start_at
                        ? $map->exam_start_at->format('d-m-Y H:i')
                        : (optional($map->exam_date)->format('d-m-Y') ?: (optional($drive->start_date)->format('d-m-Y') ?: '-')))
                    . ' to '
                    . ($map->exam_end_at
                        ? $map->exam_end_at->format('d-m-Y H:i')
                        : (optional($map->exam_end_date)->format('d-m-Y') ?: (optional($drive->end_date)->format('d-m-Y') ?: '-'))),
                'exam_mode'      => $map->exam_mode ?: self::EXAM_MODES[0],
                'exam_type'      => $map->exam_type,
                'sub_faculty'    => $map->sub_faculty_names ?? [],
                // Setup faculty + further names, as shown in SUB-COORDINATES.
                'sub_coordinators' => $map->subCoordinatorNames(),
                'faculty_codes'    => $map->faculty_codes ?? [],
                'faculty_unique_codes' => $map->faculty_unique_codes ?? [],
            ])->values()->all(),
        ];
    }

    /** @return Collection<int, array> */
    /** Course names; for a drive-list tab, only that tab's courses (CourseMaster::activeRunning / archived). */
    private function courseNames(?string $scope = null): array
    {
        $query = CourseMaster::query();
        if ($scope === 'archived') {
            $query->archived();
        } elseif ($scope === 'active') {
            $query->activeRunning();
        }

        return $query->orderBy('course_name')->pluck('course_name')->filter()->unique()->values()->all();
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
    /** faculty_master.faculty_type of Internal faculty (faculty_type_master pk 1). */
    private const INTERNAL_FACULTY_TYPE = 1;

    /** Active faculty names; $internalOnly = Internal faculty only (Create / Edit Drive mapping). */
    private function facultyNames(bool $internalOnly = false): array
    {
        return FacultyMaster::where('active_inactive', 1)
            ->when($internalOnly, fn ($q) => $q->where('faculty_type', self::INTERNAL_FACULTY_TYPE))
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

    private function termNames(bool $activeOnly = false): array
    {
        return TermMaster::when($activeOnly, fn ($q) => $q->where('active_inactive', 1))
            ->orderBy('term_name')->pluck('term_name')->filter()->unique()->values()->all();
    }

    private function examTypeNames(bool $activeOnly = false): array
    {
        return ExaminationTypeMaster::when($activeOnly, fn ($q) => $q->where('active_inactive', 1))
            ->orderBy('exam_type_name')->pluck('exam_type_name')->filter()->unique()->values()->all();
    }

    private function phaseValues(): array
    {
        return ExaminationDrive::whereNotNull('phase')->distinct()->orderBy('phase')->pluck('phase')->filter()->values()->all();
    }
}
