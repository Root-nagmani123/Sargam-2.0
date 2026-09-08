<?php

namespace App\Http\Controllers\Admin\Master;

use App\DataTables\Master\NominationDriveDataTable;
use App\Exports\NominationDriveExport;
use App\Exports\NominationDriveNomineeExport;
use App\Http\Controllers\Controller;
use App\Models\ClubSocietyMaster;
use App\Models\ClubSocietyRoleMaster;
use App\Models\Nomination;
use App\Models\NominationDrive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Communications -> Club/ Society -> Create Nomination Drive.
 *
 * A drive opens nominations for a course. Each drive lists the societies taking
 * part, optionally with their own window, and the posts open in each. The two
 * status columns (accept / withdraw) are independent switches on the row.
 */
class NominationDriveController extends Controller
{
    public function index(NominationDriveDataTable $dataTable)
    {
        return $dataTable->render('admin.master.nomination_drive.index', [
            'clubSocieties'  => ClubSocietyMaster::where('active_inactive', 1)->orderBy('pk')->get(['pk', 'club_society_name']),
            'roles'          => ClubSocietyRoleMaster::where('active_inactive', 1)->orderBy('pk')->get(['pk', 'club_society_role_name']),
            'courses'        => $this->courseOptions('active'),
            'archiveCourses' => $this->courseOptions('archive'),
        ]);
    }

    public function store(Request $request)
    {
        $pk = null;
        if ($request->filled('pk')) {
            try {
                $pk = decrypt($request->input('pk'));
            } catch (\Throwable $e) {
                return $this->failure($request, 'Invalid record reference. Please reload the page and try again.');
            }
        }

        $validated = $request->validate([
            'drive_name' => [
                'required', 'string', 'max:255',
                Rule::unique('nomination_drive', 'drive_name')->ignore($pk, 'pk'),
            ],
            'course_master_pk'      => ['required', 'integer', 'exists:course_master,pk'],
            'start_date'            => ['required', 'date'],
            'end_date'              => ['required', 'date', 'after_or_equal:start_date'],
            'self_nomination_allow' => ['required', Rule::in(['0', '1', 0, 1])],
            'same_date_for_all'     => ['required', Rule::in(['0', '1', 0, 1])],

            'society_pks'           => ['required', 'array', 'min:1'],
            'society_pks.*'         => ['integer', 'exists:club_society_master,pk'],

            // Keyed by society pk; only the ticked societies are read below.
            'society_start_date'    => ['array'],
            'society_start_date.*'  => ['nullable', 'date'],
            'society_end_date'      => ['array'],
            'society_end_date.*'    => ['nullable', 'date'],
            'society_posts'         => ['array'],
        ], [
            'drive_name.required'       => 'Enter a drive name.',
            'drive_name.unique'         => 'A drive with this name already exists.',
            'course_master_pk.required' => 'Please select a course.',
            'end_date.after_or_equal'   => 'End date cannot be before the start date.',
            'society_pks.required'      => 'Select at least one society.',
            'society_pks.min'           => 'Select at least one society.',
        ]);

        $sameDates   = (string) $validated['same_date_for_all'] === '1';
        $societyPks  = array_values(array_unique(array_map('intval', $validated['society_pks'])));
        $sStart      = $request->input('society_start_date', []);
        $sEnd        = $request->input('society_end_date', []);
        $sPosts      = $request->input('society_posts', []);

        // Per-society rules that only apply to the ticked rows. The rule array
        // cannot see which rows are ticked, so they are checked here.
        $errors = [];
        foreach ($societyPks as $sid) {
            if (empty($sPosts[$sid]) || ! is_array($sPosts[$sid])) {
                $errors["society_posts.{$sid}"] = ['Select at least one post.'];
            }

            if (! $sameDates) {
                $from = $sStart[$sid] ?? null;
                $to   = $sEnd[$sid] ?? null;

                if (! $from) {
                    $errors["society_start_date.{$sid}"] = ['Enter a start date.'];
                }
                if (! $to) {
                    $errors["society_end_date.{$sid}"] = ['Enter an end date.'];
                }
                if ($from && $to && strtotime($to) < strtotime($from)) {
                    $errors["society_end_date.{$sid}"] = ['End date cannot be before the start date.'];
                }
            }
        }

        if ($errors) {
            return response()->json([
                'message' => 'Please correct the highlighted rows.',
                'errors'  => $errors,
            ], 422);
        }

        $drivePk = DB::transaction(function () use ($pk, $validated, $sameDates, $societyPks, $sStart, $sEnd, $sPosts) {
            $payload = [
                'drive_name'            => trim($validated['drive_name']),
                'course_master_pk'      => (int) $validated['course_master_pk'],
                'start_date'            => $validated['start_date'],
                'end_date'              => $validated['end_date'],
                'self_nomination_allow' => (string) $validated['self_nomination_allow'] === '1' ? 1 : 0,
            ];

            if ($pk) {
                $drive = NominationDrive::findOrFail($pk);
                $drive->fill($payload)->save();
            } else {
                // New drives start accepting nominations and allowing withdrawal;
                // both are switched per-row from the grid afterwards.
                $drive = NominationDrive::create($payload + [
                    'nomination_accept_status'   => 1,
                    'nomination_withdraw_status' => 1,
                    'active_inactive'            => 1,
                ]);
            }

            // Replace the society/post set so unticking removes it.
            $oldSocietyPks = DB::table('nomination_drive_society')
                ->where('nomination_drive_pk', $drive->pk)->pluck('pk');
            DB::table('nomination_drive_society_post')
                ->whereIn('nomination_drive_society_pk', $oldSocietyPks)->delete();
            DB::table('nomination_drive_society')
                ->where('nomination_drive_pk', $drive->pk)->delete();

            foreach ($societyPks as $sid) {
                $societyRowPk = DB::table('nomination_drive_society')->insertGetId([
                    'nomination_drive_pk'    => $drive->pk,
                    'club_society_master_pk' => $sid,
                    // Null means "use the drive's own window".
                    'start_date'             => $sameDates ? null : ($sStart[$sid] ?? null),
                    'end_date'               => $sameDates ? null : ($sEnd[$sid] ?? null),
                ]);

                $postRows = [];
                foreach ((array) ($sPosts[$sid] ?? []) as $rolePk) {
                    $postRows[] = [
                        'nomination_drive_society_pk' => $societyRowPk,
                        'club_society_role_master_pk' => (int) $rolePk,
                    ];
                }
                if ($postRows) {
                    DB::table('nomination_drive_society_post')->insert($postRows);
                }
            }

            return $drive->pk;
        });

        $message = $pk ? 'Nomination drive updated successfully.' : 'Nomination drive created successfully.';

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => $message, 'pk' => $drivePk]);
        }

        return redirect()->route('master.nomination.drive.index')->with('success', $message);
    }

    /** Drive + its societies/posts, for the Edit modal's prefill. */
    public function edit(Request $request, $id)
    {
        try {
            $drive = NominationDrive::findOrFail(decrypt($id));
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => 'Invalid record reference.'], 422);
        }

        $societies = DB::table('nomination_drive_society as s')
            ->where('s.nomination_drive_pk', $drive->pk)
            ->get(['s.pk', 's.club_society_master_pk', 's.start_date', 's.end_date']);

        $postsBySociety = DB::table('nomination_drive_society_post')
            ->whereIn('nomination_drive_society_pk', $societies->pluck('pk'))
            ->get(['nomination_drive_society_pk', 'club_society_role_master_pk'])
            ->groupBy('nomination_drive_society_pk');

        return response()->json([
            'status'                => 'success',
            'pk'                    => $id,
            'drive_name'            => $drive->drive_name,
            'course_master_pk'      => (int) $drive->course_master_pk,
            'start_date'            => optional($drive->start_date)->format('Y-m-d'),
            'end_date'              => optional($drive->end_date)->format('Y-m-d'),
            'self_nomination_allow' => (int) $drive->self_nomination_allow,
            // Society rows carry their own window only when dates were not shared.
            'same_date_for_all'     => $societies->every(fn ($s) => is_null($s->start_date) && is_null($s->end_date)) ? 1 : 0,
            'societies'             => $societies->map(fn ($s) => [
                'club_society_master_pk' => (int) $s->club_society_master_pk,
                'start_date'             => $s->start_date ? substr($s->start_date, 0, 10) : null,
                'end_date'               => $s->end_date ? substr($s->end_date, 0, 10) : null,
                'post_pks'               => collect($postsBySociety[$s->pk] ?? [])
                    ->pluck('club_society_role_master_pk')->map(fn ($v) => (int) $v)->values(),
            ])->values(),
        ]);
    }

    /** Flip one of the two independent status switches. */
    public function toggleStatus(Request $request, $id)
    {
        $data = $request->validate([
            'field' => ['required', Rule::in(['nomination_accept_status', 'nomination_withdraw_status'])],
            'value' => ['required', Rule::in(['0', '1', 0, 1])],
        ]);

        try {
            $drive = NominationDrive::findOrFail(decrypt($id));
        } catch (\Throwable $e) {
            return $this->failure($request, 'Nomination drive not found.', 404);
        }

        $drive->{$data['field']} = (int) $data['value'] === 1 ? 1 : 0;
        $drive->save();

        $label = $data['field'] === 'nomination_accept_status' ? 'Nomination accept' : 'Nomination withdraw';
        $state = (int) $data['value'] === 1 ? 'enabled' : 'disabled';

        return response()->json(['status' => 'success', 'message' => "{$label} {$state}."]);
    }

    public function destroy(Request $request, $id)
    {
        try {
            $drive = NominationDrive::findOrFail(decrypt($id));
        } catch (\Throwable $e) {
            return $this->failure($request, 'Nomination drive not found.', 404);
        }

        DB::transaction(function () use ($drive) {
            $societyPks = DB::table('nomination_drive_society')
                ->where('nomination_drive_pk', $drive->pk)->pluck('pk');
            DB::table('nomination_drive_society_post')
                ->whereIn('nomination_drive_society_pk', $societyPks)->delete();
            DB::table('nomination_drive_society')->where('nomination_drive_pk', $drive->pk)->delete();
            DB::table('nomination')->where('nomination_drive_pk', $drive->pk)->delete();
            $drive->delete();
        });

        $message = 'Nomination drive deleted successfully.';

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => $message]);
        }

        return redirect()->route('master.nomination.drive.index')->with('success', $message);
    }

    /** The drive's own screen: every nomination received, one row per nominee+post. */
    public function show(Request $request, $id)
    {
        try {
            $drive = NominationDrive::findOrFail(decrypt($id));
        } catch (\Throwable $e) {
            return redirect()->route('master.nomination.drive.index')->with('error', 'Nomination drive not found.');
        }

        return view('admin.master.nomination_drive.show', [
            'drive'     => $drive,
            'driveId'   => $id,
            'rows'      => self::nomineeRows($drive->pk),
            'canWithdraw' => (bool) $drive->nomination_withdraw_status,
        ]);
    }

    /** Withdraw one nomination from the drive's View screen. */
    public function withdrawNomination(Request $request, $id)
    {
        try {
            $nomination = Nomination::findOrFail(decrypt($id));
        } catch (\Throwable $e) {
            return $this->failure($request, 'Nomination not found.', 404);
        }

        $drive = NominationDrive::find($nomination->nomination_drive_pk);
        if ($drive && ! $drive->nomination_withdraw_status) {
            return $this->failure($request, 'Withdrawal is disabled for this drive.', 422);
        }

        $nomination->status = Nomination::STATUS_WITHDRAWN;
        $nomination->save();

        return response()->json(['status' => 'success', 'message' => 'Nomination withdrawn.']);
    }

    /**
     * Nominee rows for a drive's View screen. One row per (society, post,
     * nominee): who was nominated, by how many people, and how many nominations
     * that post requires (from the Club/ Society Role Programme Mapping).
     */
    public static function nomineeRows($drivePk, ?string $status = null)
    {
        $query = DB::table('nomination as n')
            ->join('nomination_drive as d', 'd.pk', '=', 'n.nomination_drive_pk')
            ->join('club_society_master as cs', 'cs.pk', '=', 'n.club_society_master_pk')
            ->join('club_society_role_master as r', 'r.pk', '=', 'n.club_society_role_master_pk')
            ->leftJoin('student_master as st', 'st.pk', '=', 'n.nominee_student_pk')
            // Required nomination count comes from the role programme mapping
            // for the same course + society + post.
            ->leftJoin('club_society_role_programme_mapping as rpm', function ($join) {
                $join->on('rpm.course_master_pk', '=', 'd.course_master_pk')
                     ->on('rpm.club_society_master_pk', '=', 'n.club_society_master_pk')
                     ->on('rpm.club_society_role_master_pk', '=', 'n.club_society_role_master_pk');
            })
            ->where('n.nomination_drive_pk', $drivePk)
            ->select([
                'cs.club_society_name',
                'r.club_society_role_name as post_name',
                'st.display_name as nominee_name',
                'st.generated_OT_code as ot_code',
                'n.nominee_student_pk',
                'n.club_society_master_pk',
                'n.club_society_role_master_pk',
                DB::raw('COUNT(DISTINCT n.nominated_by_student_pk) as nominated_by_count'),
                DB::raw('COALESCE(MAX(rpm.number_of_nomination), 0) as required_nomination'),
                DB::raw('MIN(n.pk) as nomination_pk'),
                DB::raw("MAX(n.status) as status"),
            ])
            ->groupBy(
                'cs.club_society_name', 'r.club_society_role_name', 'st.display_name',
                'st.generated_OT_code', 'n.nominee_student_pk',
                'n.club_society_master_pk', 'n.club_society_role_master_pk'
            )
            ->orderBy('cs.club_society_name')
            ->orderBy('st.display_name');

        if ($status) {
            $query->where('n.status', $status);
        }

        return $query->get();
    }

    /**
     * The one definition of the drive grid, shared by the DataTable, the Excel
     * export and the print sheet so the three cannot drift apart.
     *
     * Active / Archived mirrors the COURSE's own status, the same split Course
     * Master and Attendance use.
     */
    public static function baseDriveQuery(string $status = 'active', $coursePk = null)
    {
        $query = DB::table('nomination_drive as d')
            ->join('course_master as c', 'c.pk', '=', 'd.course_master_pk')
            ->where('d.active_inactive', 1)
            ->where('c.active_inactive', $status === 'archive' ? 0 : 1)
            ->select([
                'd.pk',
                'd.drive_name',
                DB::raw("COALESCE(NULLIF(c.couse_short_name, ''), c.course_name) as course_name"),
                'd.start_date',
                'd.end_date',
                'd.nomination_accept_status',
                'd.nomination_withdraw_status',
            ])
            ->orderBy('d.pk', 'desc');

        if (filled($coursePk)) {
            $query->where('d.course_master_pk', $coursePk);
        }

        return $query;
    }

    public static function driveRows(string $status = 'active', $coursePk = null)
    {
        return self::baseDriveQuery($status, $coursePk)->get();
    }

    public function export(Request $request)
    {
        try {
            return Excel::download(
                new NominationDriveExport(
                    $request->input('status_filter', 'active'),
                    $request->input('course_master_pk')
                ),
                'nomination_drive.xlsx'
            );
        } catch (\Throwable $e) {
            return redirect()->route('master.nomination.drive.index')
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

        return view('admin.master.nomination_drive.export_print', [
            'rows'       => self::driveRows($status, $coursePk),
            'exportDate' => now()->format('d M Y, h:i A'),
            'filterLine' => implode(' &nbsp;|&nbsp; ', $filterBits),
        ]);
    }

    /** Excel for one drive's nominee list (the View screen's Download). */
    public function exportNominees(Request $request, $id)
    {
        try {
            $drive = NominationDrive::findOrFail(decrypt($id));
        } catch (\Throwable $e) {
            return redirect()->route('master.nomination.drive.index')->with('error', 'Nomination drive not found.');
        }

        try {
            return Excel::download(new NominationDriveNomineeExport($drive->pk), 'nomination_drive_nominees.xlsx');
        } catch (\Throwable $e) {
            return redirect()->route('master.nomination.drive.show', ['id' => $id])
                ->with('error', 'Error exporting data: ' . $e->getMessage());
        }
    }

    /** Branded print sheet for one drive's nominee list. */
    public function printNominees(Request $request, $id)
    {
        try {
            $drive = NominationDrive::findOrFail(decrypt($id));
        } catch (\Throwable $e) {
            return redirect()->route('master.nomination.drive.index')->with('error', 'Nomination drive not found.');
        }

        return view('admin.master.nomination_drive.nominee_export_print', [
            'drive'      => $drive,
            'rows'       => self::nomineeRows($drive->pk),
            'exportDate' => now()->format('d M Y, h:i A'),
        ]);
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

        return redirect()->route('master.nomination.drive.index')->with('error', $message);
    }
}
