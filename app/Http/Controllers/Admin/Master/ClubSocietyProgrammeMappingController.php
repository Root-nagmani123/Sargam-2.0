<?php

namespace App\Http\Controllers\Admin\Master;

use App\DataTables\Master\ClubSocietyProgrammeMappingDataTable;
use App\Exports\ClubSocietyProgrammeMappingExport;
use App\Http\Controllers\Controller;
use App\Models\ClubSocietyMaster;
use App\Models\ClubSocietyProgrammeMapping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Communications -> Club/ Society -> Club/ Society Programme Mapping.
 *
 * A course is mapped to many clubs. The grid shows one line per course; the
 * modal edits the whole set of clubs for a course at once, so store() replaces
 * that course's rows rather than appending.
 */
class ClubSocietyProgrammeMappingController extends Controller
{
    public function index(ClubSocietyProgrammeMappingDataTable $dataTable)
    {
        return $dataTable->render('admin.master.club_society_programme_mapping.index', [
            'clubSocieties' => $this->clubOptions(),
            'courses'       => $this->courseOptions('active'),
            'archiveCourses' => $this->courseOptions('archive'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_master_pk'     => ['required', 'integer', 'exists:course_master,pk'],
            'club_society_pks'     => ['required', 'array', 'min:1'],
            'club_society_pks.*'   => ['integer', 'exists:club_society_master,pk'],
        ], [
            'course_master_pk.required' => 'Please select a course.',
            'club_society_pks.required' => 'Select at least one club/ society.',
            'club_society_pks.min'      => 'Select at least one club/ society.',
        ]);

        $coursePk = (int) $validated['course_master_pk'];
        $clubPks  = array_values(array_unique(array_map('intval', $validated['club_society_pks'])));

        $isEdit = ClubSocietyProgrammeMapping::where('course_master_pk', $coursePk)->exists();

        // Replace the whole set for this course so unchecking a club removes it.
        DB::transaction(function () use ($coursePk, $clubPks) {
            ClubSocietyProgrammeMapping::where('course_master_pk', $coursePk)->delete();

            $rows = array_map(fn ($clubPk) => [
                'course_master_pk'       => $coursePk,
                'club_society_master_pk' => $clubPk,
                'active_inactive'        => 1,
            ], $clubPks);

            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('club_society_programme_mapping')->insert($chunk);
            }
        });

        $message = $isEdit
            ? 'Club/ Society programme mapping updated successfully.'
            : 'Club/ Society programme mapping created successfully.';

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => $message]);
        }

        return redirect()->route('master.club.society.programme.mapping.index')->with('success', $message);
    }

    /** Clubs already mapped to a course — drives the modal's checked boxes. */
    public function edit(Request $request, $id)
    {
        try {
            $coursePk = decrypt($id);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => 'Invalid record reference.'], 422);
        }

        return response()->json([
            'status'           => 'success',
            'course_master_pk' => (int) $coursePk,
            'club_society_pks' => ClubSocietyProgrammeMapping::where('course_master_pk', $coursePk)
                ->pluck('club_society_master_pk')
                ->map(fn ($v) => (int) $v)
                ->values(),
        ]);
    }

    public function destroy(Request $request, $id)
    {
        try {
            $coursePk = decrypt($id);
        } catch (\Throwable $e) {
            return $this->failure($request, 'Mapping not found.', 404);
        }

        $deleted = ClubSocietyProgrammeMapping::where('course_master_pk', $coursePk)->delete();

        if (! $deleted) {
            return $this->failure($request, 'Mapping not found.', 404);
        }

        $message = 'Club/ Society programme mapping deleted successfully.';

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => $message]);
        }

        return redirect()->route('master.club.society.programme.mapping.index')->with('success', $message);
    }

    public function export(Request $request)
    {
        try {
            return Excel::download(
                new ClubSocietyProgrammeMappingExport(
                    $request->input('status_filter', 'active'),
                    $request->input('course_master_pk')
                ),
                'club_society_programme_mapping.xlsx'
            );
        } catch (\Throwable $e) {
            return redirect()
                ->route('master.club.society.programme.mapping.index')
                ->with('error', 'Error exporting data: ' . $e->getMessage());
        }
    }

    /**
     * Branded print sheet. Honours the same Active/Archived pill and course
     * filter as the grid, so the paper matches what was on screen.
     */
    public function print(Request $request)
    {
        $status   = $request->input('status_filter', 'active');
        $coursePk = $request->input('course_master_pk');

        $rows = self::mappingRows($status, $coursePk);

        $filterBits = ['<strong>Status:</strong> ' . ($status === 'archive' ? 'Archived' : 'Active')];
        if (filled($coursePk)) {
            $courseName = DB::table('course_master')->where('pk', $coursePk)->value('course_name');
            if ($courseName) {
                $filterBits[] = '<strong>Course:</strong> ' . e($courseName);
            }
        }

        return view('admin.master.club_society_programme_mapping.export_print', [
            'rows'       => $rows,
            'exportDate' => now()->format('d M Y, h:i A'),
            'filterLine' => implode(' &nbsp;|&nbsp; ', $filterBits),
        ]);
    }

    /**
     * The one definition of the grid: one row per course, every mapped club
     * collapsed into a comma-separated list. The DataTable, the Excel export and
     * the print sheet all build on this, so the three can't drift apart.
     *
     * Active / Archived mirrors the COURSE's own status — the same split Course
     * Master and Attendance use — not a status on the mapping itself.
     */
    public static function baseMappingQuery(string $status = 'active', $coursePk = null)
    {
        // GROUP_CONCAT silently truncates at group_concat_max_len (1024 by
        // default). 22 clubs fit today, but the club master is user-editable, so
        // raise it for this connection rather than lose names without an error.
        DB::statement('SET SESSION group_concat_max_len = 1000000');

        $query = DB::table('club_society_programme_mapping as m')
            ->join('course_master as c', 'c.pk', '=', 'm.course_master_pk')
            ->leftJoin('club_society_master as cs', 'cs.pk', '=', 'm.club_society_master_pk')
            ->where('m.active_inactive', 1)
            ->where('c.active_inactive', $status === 'archive' ? 0 : 1)
            ->select([
                'm.course_master_pk',
                DB::raw("COALESCE(NULLIF(c.couse_short_name, ''), c.course_name) as course_name"),
                DB::raw("GROUP_CONCAT(cs.club_society_name ORDER BY cs.pk SEPARATOR ', ') as club_names"),
            ])
            ->groupBy('m.course_master_pk', DB::raw("COALESCE(NULLIF(c.couse_short_name, ''), c.course_name)"))
            ->orderBy(DB::raw("COALESCE(NULLIF(c.couse_short_name, ''), c.course_name)"));

        if (filled($coursePk)) {
            $query->where('m.course_master_pk', $coursePk);
        }

        return $query;
    }

    /** Materialised grid rows, for the Excel export and the print sheet. */
    public static function mappingRows(string $status = 'active', $coursePk = null)
    {
        return self::baseMappingQuery($status, $coursePk)->get();
    }

    /** Active clubs for the modal checkbox grid. */
    private function clubOptions()
    {
        return ClubSocietyMaster::where('active_inactive', 1)
            ->orderBy('pk')
            ->get(['pk', 'club_society_name']);
    }

    /** Courses for the filter dropdown and the modal's Course select. */
    private function courseOptions(string $status)
    {
        return DB::table('course_master')
            ->where('active_inactive', $status === 'archive' ? 0 : 1)
            ->orderBy('course_name')
            ->get(['pk', 'course_name', 'couse_short_name']);
    }

    private function failure(Request $request, string $message, int $status = 422)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'error', 'message' => $message], $status);
        }

        return redirect()->route('master.club.society.programme.mapping.index')->with('error', $message);
    }
}
