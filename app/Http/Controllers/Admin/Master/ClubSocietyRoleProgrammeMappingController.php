<?php

namespace App\Http\Controllers\Admin\Master;

use App\DataTables\Master\ClubSocietyRoleProgrammeMappingDataTable;
use App\Exports\ClubSocietyRoleProgrammeMappingExport;
use App\Http\Controllers\Controller;
use App\Models\ClubSocietyMaster;
use App\Models\ClubSocietyRoleMaster;
use App\Models\ClubSocietyRoleProgrammeMapping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Communications -> Club/ Society -> Club/ Society Role Programme Mapping.
 *
 * For one (course, club) pair the modal defines which roles exist, how many
 * posts each has, and whether it needs nominations. The grid shows one line per
 * (course, club); store() therefore replaces that pair's rows rather than
 * appending, so unticking a role removes it.
 */
class ClubSocietyRoleProgrammeMappingController extends Controller
{
    public function index(ClubSocietyRoleProgrammeMappingDataTable $dataTable)
    {
        return $dataTable->render('admin.master.club_society_role_programme_mapping.index', [
            'roles'          => ClubSocietyRoleMaster::where('active_inactive', 1)->orderBy('pk')->get(['pk', 'club_society_role_name']),
            'clubSocieties'  => ClubSocietyMaster::where('active_inactive', 1)->orderBy('pk')->get(['pk', 'club_society_name']),
            'courses'        => $this->courseOptions('active'),
            'archiveCourses' => $this->courseOptions('archive'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_master_pk'       => ['required', 'integer', 'exists:course_master,pk'],
            'club_society_master_pk' => ['required', 'integer', 'exists:club_society_master,pk'],
            'role_pks'               => ['required', 'array', 'min:1'],
            'role_pks.*'             => ['integer', 'exists:club_society_role_master,pk'],

            // Keyed by role pk — only the ticked roles are read below.
            'number_of_post'         => ['array'],
            'number_of_post.*'       => ['nullable', 'integer', 'min:1', 'max:9999'],
            'required_nomination'    => ['array'],
            'required_nomination.*'  => ['nullable', Rule::in(['0', '1', 0, 1])],
            'number_of_nomination'   => ['array'],
            'number_of_nomination.*' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ], [
            'course_master_pk.required'       => 'Please select a course.',
            'club_society_master_pk.required' => 'Please select a club/ society.',
            'role_pks.required'               => 'Select at least one role.',
            'role_pks.min'                    => 'Select at least one role.',
        ]);

        $coursePk = (int) $validated['course_master_pk'];
        $clubPk   = (int) $validated['club_society_master_pk'];
        $rolePks  = array_values(array_unique(array_map('intval', $validated['role_pks'])));

        $posts       = $request->input('number_of_post', []);
        $needsNom    = $request->input('required_nomination', []);
        $nominations = $request->input('number_of_nomination', []);

        // Per-role rules that only apply to the ticked rows — validated here
        // rather than in the rule array, which cannot see which rows are ticked.
        $errors = [];
        foreach ($rolePks as $rolePk) {
            $post = $posts[$rolePk] ?? null;
            if ($post === null || $post === '' || (int) $post < 1) {
                $errors["number_of_post.{$rolePk}"] = ['Enter the number of posts.'];
            }

            $nomRequired = (string) ($needsNom[$rolePk] ?? '') === '1';
            if ($nomRequired) {
                $nom = $nominations[$rolePk] ?? null;
                if ($nom === null || $nom === '' || (int) $nom < 1) {
                    $errors["number_of_nomination.{$rolePk}"] = ['Enter the number of nominations.'];
                }
            }
        }

        if ($errors) {
            return response()->json([
                'message' => 'Please correct the highlighted rows.',
                'errors'  => $errors,
            ], 422);
        }

        DB::transaction(function () use ($coursePk, $clubPk, $rolePks, $posts, $needsNom, $nominations) {
            ClubSocietyRoleProgrammeMapping::where('course_master_pk', $coursePk)
                ->where('club_society_master_pk', $clubPk)
                ->delete();

            $rows = [];
            foreach ($rolePks as $rolePk) {
                $nomRequired = (string) ($needsNom[$rolePk] ?? '') === '1';

                $rows[] = [
                    'course_master_pk'            => $coursePk,
                    'club_society_master_pk'      => $clubPk,
                    'club_society_role_master_pk' => $rolePk,
                    'number_of_post'              => (int) ($posts[$rolePk] ?? 1),
                    'required_nomination'         => $nomRequired ? 1 : 0,
                    'number_of_nomination'        => $nomRequired ? (int) ($nominations[$rolePk] ?? 0) : null,
                    'active_inactive'             => 1,
                ];
            }

            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('club_society_role_programme_mapping')->insert($chunk);
            }
        });

        $message = 'Club/ Society role programme mapping saved successfully.';

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => $message]);
        }

        return redirect()->route('master.club.society.role.programme.mapping.index')->with('success', $message);
    }

    /**
     * The saved rows for one (course, club) pair. Drives both the Edit modal's
     * prefill and the read-only View modal.
     */
    public function show(Request $request, $id)
    {
        $key = self::decodeGroupKey($id);
        if (! $key) {
            return response()->json(['status' => 'error', 'message' => 'Invalid record reference.'], 422);
        }

        [$coursePk, $clubPk] = $key;

        $rows = DB::table('club_society_role_programme_mapping as m')
            ->join('club_society_role_master as r', 'r.pk', '=', 'm.club_society_role_master_pk')
            ->where('m.course_master_pk', $coursePk)
            ->where('m.club_society_master_pk', $clubPk)
            ->orderBy('r.pk')
            ->get([
                'm.club_society_role_master_pk as role_pk',
                'r.club_society_role_name as role_name',
                'm.number_of_post',
                'm.required_nomination',
                'm.number_of_nomination',
            ]);

        return response()->json([
            'status'                 => 'success',
            'course_master_pk'       => (int) $coursePk,
            'club_society_master_pk' => (int) $clubPk,
            // Same label the grid shows (short code, falling back to full name).
            'course_name'            => DB::table('course_master')->where('pk', $coursePk)
                ->selectRaw("COALESCE(NULLIF(couse_short_name, ''), course_name) as label")->value('label'),
            'club_society_name'      => DB::table('club_society_master')->where('pk', $clubPk)->value('club_society_name'),
            'rows'                   => $rows,
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $key = self::decodeGroupKey($id);
        if (! $key) {
            return $this->failure($request, 'Mapping not found.', 404);
        }

        [$coursePk, $clubPk] = $key;

        $deleted = ClubSocietyRoleProgrammeMapping::where('course_master_pk', $coursePk)
            ->where('club_society_master_pk', $clubPk)
            ->delete();

        if (! $deleted) {
            return $this->failure($request, 'Mapping not found.', 404);
        }

        $message = 'Club/ Society role programme mapping deleted successfully.';

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => $message]);
        }

        return redirect()->route('master.club.society.role.programme.mapping.index')->with('success', $message);
    }

    public function export(Request $request)
    {
        try {
            return Excel::download(
                new ClubSocietyRoleProgrammeMappingExport(
                    $request->input('status_filter', 'active'),
                    $request->input('course_master_pk')
                ),
                'club_society_role_programme_mapping.xlsx'
            );
        } catch (\Throwable $e) {
            return redirect()
                ->route('master.club.society.role.programme.mapping.index')
                ->with('error', 'Error exporting data: ' . $e->getMessage());
        }
    }

    public function print(Request $request)
    {
        $status   = $request->input('status_filter', 'active');
        $coursePk = $request->input('course_master_pk');

        $filterBits = ['<strong>Status:</strong> ' . ($status === 'archive' ? 'Archived' : 'Active')];
        if (filled($coursePk)) {
            $courseName = DB::table('course_master')->where('pk', $coursePk)->value('course_name');
            if ($courseName) {
                $filterBits[] = '<strong>Course:</strong> ' . e($courseName);
            }
        }

        return view('admin.master.club_society_role_programme_mapping.export_print', [
            'rows'       => self::mappingRows($status, $coursePk),
            'exportDate' => now()->format('d M Y, h:i A'),
            'filterLine' => implode(' &nbsp;|&nbsp; ', $filterBits),
        ]);
    }

    /**
     * The one definition of the grid: a row per (course, club) with the roles
     * collapsed into a comma-separated list. The DataTable, the Excel export and
     * the print sheet all build on this so they cannot drift apart.
     *
     * Active / Archived mirrors the COURSE's own status, the same split Course
     * Master and Attendance use.
     */
    public static function baseMappingQuery(string $status = 'active', $coursePk = null)
    {
        // GROUP_CONCAT silently truncates at group_concat_max_len (1024 by
        // default); the role master is user-editable, so raise it rather than
        // lose names with no error.
        DB::statement('SET SESSION group_concat_max_len = 1000000');

        $query = DB::table('club_society_role_programme_mapping as m')
            ->join('course_master as c', 'c.pk', '=', 'm.course_master_pk')
            ->join('club_society_master as cs', 'cs.pk', '=', 'm.club_society_master_pk')
            ->leftJoin('club_society_role_master as r', 'r.pk', '=', 'm.club_society_role_master_pk')
            ->where('m.active_inactive', 1)
            ->where('c.active_inactive', $status === 'archive' ? 0 : 1)
            ->select([
                'm.course_master_pk',
                'm.club_society_master_pk',
                DB::raw("COALESCE(NULLIF(c.couse_short_name, ''), c.course_name) as course_name"),
                'cs.club_society_name',
                DB::raw("GROUP_CONCAT(r.club_society_role_name ORDER BY r.club_society_role_name SEPARATOR ', ') as role_names"),
            ])
            ->groupBy('m.course_master_pk', 'm.club_society_master_pk', DB::raw("COALESCE(NULLIF(c.couse_short_name, ''), c.course_name)"), 'cs.club_society_name')
            ->orderBy(DB::raw("COALESCE(NULLIF(c.couse_short_name, ''), c.course_name)"))
            ->orderBy('cs.pk');

        if (filled($coursePk)) {
            $query->where('m.course_master_pk', $coursePk);
        }

        return $query;
    }

    public static function mappingRows(string $status = 'active', $coursePk = null)
    {
        return self::baseMappingQuery($status, $coursePk)->get();
    }

    /**
     * A grid row is keyed by a (course, club) PAIR, so the row id encrypts both.
     * Returns [coursePk, clubPk] or null when the token is unusable.
     */
    public static function encodeGroupKey($coursePk, $clubPk): string
    {
        return encrypt($coursePk . '|' . $clubPk);
    }

    public static function decodeGroupKey($token): ?array
    {
        try {
            $parts = explode('|', (string) decrypt($token));
        } catch (\Throwable $e) {
            return null;
        }

        if (count($parts) !== 2 || ! is_numeric($parts[0]) || ! is_numeric($parts[1])) {
            return null;
        }

        return [(int) $parts[0], (int) $parts[1]];
    }

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

        return redirect()->route('master.club.society.role.programme.mapping.index')->with('error', $message);
    }
}
