<?php

namespace App\Http\Controllers\Admin\COE;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

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
    public const SUBJECTS     = ['Law', 'Leadership', 'Ethics & Integrity', 'Discipline', 'Public Policy'];
    public const COMPONENTS   = ['Ethics & Integrity', 'Discipline', 'Case Study', 'Presentation'];
    public const EXAM_MODES   = ['Online', 'Offline'];

    private static array $FACULTY = [
        'Sandra Calderon', 'Adrian Harrington', 'John Doe', 'Lee Brown', 'Casey Sims',
        'Gene Brewer', 'Homer Green', 'Eileen Lane', 'Helen Riley', 'Johnathan Juarez',
        'Sharon Ross', 'Jan Harmon', 'Max Jordan', 'Preston Berry',
    ];

    public function index(Request $request)
    {
        $scope = in_array($request->query('scope'), self::SCOPES, true)
            ? $request->query('scope') : 'active';

        $drives = $this->sampleDrives()->where('archived', $scope === 'archived')->values();

        return view('admin.coe.examination_drive.index', [
            'scope'         => $scope,
            'drives'        => $drives,
            'courses'       => self::COURSES,
            'terms'         => self::TERMS,
            'phases'        => self::PHASES,
            'examTypes'     => self::EXAM_TYPES,
            'statuses'      => self::STATUSES,
            'subjects'      => self::SUBJECTS,
            'faculty'       => self::$FACULTY,
        ]);
    }

    /** Map Faculty to Drive — editable component table. */
    public function show(int $id)
    {
        $drive = $this->sampleDrives()->firstWhere('id', $id);
        abort_if($drive === null, 404);

        return view('admin.coe.examination_drive.show', [
            'drive'      => $drive,
            'components' => self::COMPONENTS,
            'sources'    => self::SOURCES,
            'faculty'    => self::$FACULTY,
            'examModes'  => self::EXAM_MODES,
        ]);
    }

    /** Faculty-end: list of drives assigned to this faculty member. */
    public function facultyIndex(Request $request)
    {
        $scope  = in_array($request->query('scope'), self::SCOPES, true)
            ? $request->query('scope') : 'active';

        // Faculty sees only drives where status is 'Pending at Faculty'
        $drives = $this->sampleDrives()
            ->where('archived', $scope === 'archived')
            ->map(fn ($d) => array_merge($d, ['status' => 'Pending at Faculty']))
            ->values();

        return view('admin.coe.examination_drive.faculty_index', [
            'scope'     => $scope,
            'drives'    => $drives,
            'courses'   => self::COURSES,
            'terms'     => self::TERMS,
            'phases'    => self::PHASES,
            'examTypes' => self::EXAM_TYPES,
        ]);
    }

    /** Faculty-end: Map Faculty to Examination Drive (editable component table + Assign Faculty modal). */
    public function facultySetup(int $id)
    {
        $drive = $this->sampleDrives()->firstWhere('id', $id);
        abort_if($drive === null, 404);

        // Sample unique codes for the Select Roll Number sub-modal
        $uniqueCodes = collect(range(342, 360))->map(fn ($n) => 'UC' . $n)->values()->all();

        return view('admin.coe.examination_drive.faculty_setup', [
            'drive'       => $drive,
            'components'  => self::COMPONENTS,
            'sources'     => self::SOURCES,
            'faculty'     => self::$FACULTY,
            'examModes'   => self::EXAM_MODES,
            'uniqueCodes' => $uniqueCodes,
        ]);
    }

    /** Faculty Coordinates & Components — view with Assign Faculty modal. */
    public function coordinates(int $id)
    {
        $drive = $this->sampleDrives()->firstWhere('id', $id);
        abort_if($drive === null, 404);

        return view('admin.coe.examination_drive.coordinates', [
            'drive'    => $drive,
            'faculty'  => self::$FACULTY,
            'examModes'=> self::EXAM_MODES,
        ]);
    }

    /** @return Collection<int, array> */
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
