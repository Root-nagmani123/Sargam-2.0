<?php

namespace App\Http\Controllers\Admin\Master;

use App\DataTables\Master\ElectionDriveDataTable;
use App\Exports\ElectionDriveExport;
use App\Exports\ElectionDriveNominationExport;
use App\Http\Controllers\Concerns\AuditsClubSocietyActions;
use App\Http\Controllers\Controller;
use App\Models\ElectionDrive;
use App\Models\Nomination;
use App\Services\ClubSociety\OfficeBearerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Communications -> Club/ Society -> Create Election Drive.
 *
 * An election drive hangs off a nomination drive: the societies polling and the
 * posts contested both come from there, so the modal loads its society table
 * once a nomination drive is picked. The two publish switches (election / result)
 * are independent, like the nomination drive's own pair.
 */
class ElectionDriveController extends Controller
{
    use AuditsClubSocietyActions;

    public function index(ElectionDriveDataTable $dataTable)
    {
        return $dataTable->render('admin.master.election_drive.index', [
            'nominationDrives' => $this->nominationDriveOptions(),
            'courses'          => $this->courseOptions('active'),
            'archiveCourses'   => $this->courseOptions('archive'),
        ]);
    }

    /**
     * Societies polling in a nomination drive, each with a per-role summary of
     * "accepted nominations / posts available" — the modal's Number of Post cell.
     * Loaded when a nomination drive is chosen, since the list depends on it.
     */
    public function societiesForNominationDrive(Request $request, $id)
    {
        try {
            $nominationDrivePk = decrypt($id);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => 'Invalid nomination drive.'], 422);
        }

        $coursePk = DB::table('nomination_drive')->where('pk', $nominationDrivePk)->value('course_master_pk');

        $societies = DB::table('nomination_drive_society as s')
            ->join('club_society_master as cs', 'cs.pk', '=', 's.club_society_master_pk')
            ->where('s.nomination_drive_pk', $nominationDrivePk)
            ->orderBy('cs.pk')
            ->get(['s.pk as drive_society_pk', 's.club_society_master_pk', 'cs.club_society_name']);

        // Posts open per society in that drive.
        $posts = DB::table('nomination_drive_society_post as p')
            ->join('club_society_role_master as r', 'r.pk', '=', 'p.club_society_role_master_pk')
            ->whereIn('p.nomination_drive_society_pk', $societies->pluck('drive_society_pk'))
            ->get(['p.nomination_drive_society_pk', 'p.club_society_role_master_pk', 'r.club_society_role_name'])
            ->groupBy('nomination_drive_society_pk');

        // How many posts each role carries, from the role programme mapping.
        $seats = DB::table('club_society_role_programme_mapping')
            ->where('course_master_pk', $coursePk)
            ->get(['club_society_master_pk', 'club_society_role_master_pk', 'number_of_post'])
            ->keyBy(fn ($r) => $r->club_society_master_pk . ':' . $r->club_society_role_master_pk);

        // Accepted nominations per (society, role) in this drive.
        $accepted = DB::table('nomination')
            ->where('nomination_drive_pk', $nominationDrivePk)
            ->where('status', 'accepted')
            ->groupBy('club_society_master_pk', 'club_society_role_master_pk')
            ->get([
                'club_society_master_pk',
                'club_society_role_master_pk',
                DB::raw('COUNT(DISTINCT nominee_student_pk) as accepted_count'),
            ])
            ->keyBy(fn ($r) => $r->club_society_master_pk . ':' . $r->club_society_role_master_pk);

        $payload = $societies->map(function ($society) use ($posts, $seats, $accepted) {
            $rows = collect($posts[$society->drive_society_pk] ?? [])->map(function ($post) use ($society, $seats, $accepted) {
                $key = $society->club_society_master_pk . ':' . $post->club_society_role_master_pk;

                return [
                    'role_name' => $post->club_society_role_name,
                    // "<accepted nominees> / <posts available>"
                    'accepted'  => (int) ($accepted[$key]->accepted_count ?? 0),
                    'seats'     => (int) ($seats[$key]->number_of_post ?? 0),
                ];
            })->values();

            return [
                'club_society_master_pk' => (int) $society->club_society_master_pk,
                'club_society_name'      => $society->club_society_name,
                'posts'                  => $rows,
            ];
        })->values();

        return response()->json(['status' => 'success', 'societies' => $payload]);
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
            'election_drive_name' => [
                'required', 'string', 'max:255',
                Rule::unique('election_drive', 'election_drive_name')->ignore($pk, 'pk'),
            ],
            'nomination_drive_pk' => ['required', 'integer', 'exists:nomination_drive,pk'],
            'drive_date'          => ['required', 'date'],
            'start_time'          => ['required', 'date_format:H:i'],
            'end_time'            => ['required', 'date_format:H:i', 'after:start_time'],
            'same_date_for_all'   => ['required', Rule::in(['0', '1', 0, 1])],
            'same_time_for_all'   => ['required', Rule::in(['0', '1', 0, 1])],

            'society_pks'         => ['required', 'array', 'min:1'],
            'society_pks.*'       => ['integer', 'exists:club_society_master,pk'],

            // Keyed by society pk; only the ticked societies are read below.
            'society_date'        => ['array'],
            'society_date.*'      => ['nullable', 'date'],
            'society_start_time'  => ['array'],
            'society_start_time.*'=> ['nullable', 'date_format:H:i'],
            'society_end_time'    => ['array'],
            'society_end_time.*'  => ['nullable', 'date_format:H:i'],
        ], [
            'election_drive_name.required' => 'Enter an election drive name.',
            'election_drive_name.unique'   => 'An election drive with this name already exists.',
            'nomination_drive_pk.required' => 'Please select a nomination drive.',
            'end_time.after'               => 'End time must be after the start time.',
            'society_pks.required'         => 'Select at least one society.',
            'society_pks.min'              => 'Select at least one society.',
        ]);

        $sameDate  = (string) $validated['same_date_for_all'] === '1';
        $sameTime  = (string) $validated['same_time_for_all'] === '1';
        $societies = array_values(array_unique(array_map('intval', $validated['society_pks'])));
        $sDate     = $request->input('society_date', []);
        $sStart    = $request->input('society_start_time', []);
        $sEnd      = $request->input('society_end_time', []);

        // Per-society rules that only apply to the ticked rows.
        $errors = [];
        foreach ($societies as $sid) {
            if (! $sameDate && empty($sDate[$sid])) {
                $errors["society_date.{$sid}"] = ['Enter a date.'];
            }

            if (! $sameTime) {
                if (empty($sStart[$sid])) {
                    $errors["society_start_time.{$sid}"] = ['Enter a start time.'];
                }
                if (empty($sEnd[$sid])) {
                    $errors["society_end_time.{$sid}"] = ['Enter an end time.'];
                }
                if (! empty($sStart[$sid]) && ! empty($sEnd[$sid]) && $sEnd[$sid] <= $sStart[$sid]) {
                    $errors["society_end_time.{$sid}"] = ['End time must be after the start time.'];
                }
            }
        }

        if ($errors) {
            return response()->json([
                'message' => 'Please correct the highlighted rows.',
                'errors'  => $errors,
            ], 422);
        }

        $nominationDrivePk = (int) $validated['nomination_drive_pk'];

        // Election state rules (PR #340 F-006). Checked inside the transaction,
        // under a lock on the nomination drive, so two saves cannot both pass.
        $conflict = DB::transaction(function () use ($pk, $validated, $nominationDrivePk, $sameDate, $sameTime, $societies, $sDate, $sStart, $sEnd) {
            DB::table('nomination_drive')->where('pk', $nominationDrivePk)->lockForUpdate()->first();

            // One nomination drive carries at most one election.
            $taken = DB::table('election_drive')
                ->where('nomination_drive_pk', $nominationDrivePk)
                ->where('active_inactive', 1)
                ->when($pk, fn ($q) => $q->where('pk', '!=', $pk))
                ->exists();
            if ($taken) {
                return 'This nomination drive already has an election drive.';
            }

            $payload = [
                'election_drive_name' => trim($validated['election_drive_name']),
                'nomination_drive_pk' => (int) $validated['nomination_drive_pk'],
                'drive_date'          => $validated['drive_date'],
                'start_time'          => $validated['start_time'],
                'end_time'            => $validated['end_time'],
                'same_date_for_all'   => $sameDate ? 1 : 0,
                'same_time_for_all'   => $sameTime ? 1 : 0,
            ];

            if ($pk) {
                $drive = ElectionDrive::lockForUpdate()->findOrFail($pk);

                // A published result belongs to its nomination drive; re-pointing
                // the election would silently rewrite who was elected.
                if ($drive->result_status && (int) $drive->nomination_drive_pk !== $nominationDrivePk) {
                    return 'The result of this election is published; unpublish it before changing the nomination drive.';
                }

                $drive->fill($payload)->save();
            } else {
                // Both publish switches start Pending; they are flipped from the
                // grid's Publish Election / Publish Result actions. forceFill:
                // the publish flags are not mass-assignable (F-010).
                $drive = (new ElectionDrive())->forceFill($payload + [
                    'election_publish_status' => 0,
                    'result_status'           => 0,
                    'active_inactive'         => 1,
                ]);
                $drive->save();
            }

            DB::table('election_drive_society')->where('election_drive_pk', $drive->pk)->delete();

            $rows = [];
            foreach ($societies as $sid) {
                $rows[] = [
                    'election_drive_pk'      => $drive->pk,
                    'club_society_master_pk' => $sid,
                    'drive_date'             => $sameDate ? null : ($sDate[$sid] ?? null),
                    'start_time'             => $sameTime ? null : ($sStart[$sid] ?? null),
                    'end_time'               => $sameTime ? null : ($sEnd[$sid] ?? null),
                ];
            }
            if ($rows) {
                DB::table('election_drive_society')->insert($rows);
            }

            $this->auditClubSociety($pk ? 'election_drive.update' : 'election_drive.create', [
                'election_drive_pk'   => $drive->pk,
                'nomination_drive_pk' => $nominationDrivePk,
                'society_pks'         => $societies,
            ]);

            return null;
        });

        if ($conflict) {
            return response()->json([
                'message' => $conflict,
                'errors'  => ['nomination_drive_pk' => [$conflict]],
            ], 422);
        }

        $message = $pk ? 'Election drive updated successfully.' : 'Election drive created successfully.';

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => $message]);
        }

        return redirect()->route('master.election.drive.index')->with('success', $message);
    }

    /** Drive + its societies, for the Edit modal's prefill. */
    public function edit(Request $request, $id)
    {
        try {
            $drive = ElectionDrive::findOrFail(decrypt($id));
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => 'Invalid record reference.'], 422);
        }

        return response()->json([
            'status'                 => 'success',
            'pk'                     => $id,
            'election_drive_name'    => $drive->election_drive_name,
            'nomination_drive_pk'    => (int) $drive->nomination_drive_pk,
            'nomination_drive_token' => encrypt($drive->nomination_drive_pk),
            'drive_date'             => optional($drive->drive_date)->format('Y-m-d'),
            'start_time'             => substr((string) $drive->start_time, 0, 5),
            'end_time'               => substr((string) $drive->end_time, 0, 5),
            'same_date_for_all'      => (int) $drive->same_date_for_all,
            'same_time_for_all'      => (int) $drive->same_time_for_all,
            'societies'              => DB::table('election_drive_society')
                ->where('election_drive_pk', $drive->pk)
                ->get(['club_society_master_pk', 'drive_date', 'start_time', 'end_time'])
                ->map(fn ($s) => [
                    'club_society_master_pk' => (int) $s->club_society_master_pk,
                    'drive_date'             => $s->drive_date ? substr($s->drive_date, 0, 10) : null,
                    'start_time'             => $s->start_time ? substr($s->start_time, 0, 5) : null,
                    'end_time'               => $s->end_time ? substr($s->end_time, 0, 5) : null,
                ])->values(),
        ]);
    }

    /** Publish Election / Publish Result — two independent switches. */
    public function publish(Request $request, $id)
    {
        $data = $request->validate([
            'field' => ['required', Rule::in(['election_publish_status', 'result_status'])],
            'value' => ['required', Rule::in(['0', '1', 0, 1])],
        ]);

        try {
            $drive = ElectionDrive::findOrFail(decrypt($id));
        } catch (\Throwable $e) {
            return $this->failure($request, 'Election drive not found.', 404);
        }

        // Results only make sense once the election itself is live.
        if ($data['field'] === 'result_status' && (int) $data['value'] === 1 && ! $drive->election_publish_status) {
            return $this->failure($request, 'Publish the election before publishing its result.', 422);
        }

        $before = [
            'election_publish_status' => (int) $drive->election_publish_status,
            'result_status'           => (int) $drive->result_status,
        ];

        $drive->{$data['field']} = (int) $data['value'] === 1 ? 1 : 0;

        // A result cannot stay live for an election that is no longer published,
        // or Officer Bearers keeps listing its winners (PR #340 F-006).
        $resultWithdrawn = $data['field'] === 'election_publish_status'
            && (int) $data['value'] === 0
            && $drive->result_status;
        if ($resultWithdrawn) {
            $drive->result_status = 0;
        }

        $drive->save();

        $this->auditClubSociety('election_drive.publish', [
            'election_drive_pk' => $drive->pk,
            'field'             => $data['field'],
            'from'              => $before,
            'to'                => [
                'election_publish_status' => (int) $drive->election_publish_status,
                'result_status'           => (int) $drive->result_status,
            ],
        ]);

        $label = $data['field'] === 'election_publish_status' ? 'Election' : 'Result';
        $state = (int) $data['value'] === 1 ? 'published' : 'unpublished';
        $extra = $resultWithdrawn ? ' Its result has been unpublished too.' : '';

        return response()->json(['status' => 'success', 'message' => "{$label} {$state}.{$extra}"]);
    }

    public function destroy(Request $request, $id)
    {
        try {
            $drive = ElectionDrive::findOrFail(decrypt($id));
        } catch (\Throwable $e) {
            return $this->failure($request, 'Election drive not found.', 404);
        }

        DB::transaction(function () use ($drive) {
            DB::table('election_drive_society')->where('election_drive_pk', $drive->pk)->delete();
            $drive->delete();
        });

        $this->auditClubSociety('election_drive.delete', [
            'election_drive_pk'   => $drive->pk,
            'election_drive_name' => $drive->election_drive_name,
            'nomination_drive_pk' => $drive->nomination_drive_pk,
            'result_status'       => (int) $drive->result_status,
        ]);

        $message = 'Election drive deleted successfully.';

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => $message]);
        }

        return redirect()->route('master.election.drive.index')->with('success', $message);
    }

    /** View Nominations: the linked nomination drive's nominees, grouped by post. */
    public function showNominations(Request $request, $id)
    {
        try {
            $drive = ElectionDrive::findOrFail(decrypt($id));
        } catch (\Throwable $e) {
            return redirect()->route('master.election.drive.index')->with('error', 'Election drive not found.');
        }

        $nominationDrive = DB::table('nomination_drive')->where('pk', $drive->nomination_drive_pk)->first();

        $filters = [
            'club_society_master_pk' => $request->input('club_society_master_pk'),
            'status'                 => $request->input('status'),
            'from'                   => $request->input('from'),
            'to'                     => $request->input('to'),
        ];

        return view('admin.master.election_drive.nominations', [
            'drive'           => $drive,
            'driveId'         => $id,
            'nominationDrive' => $nominationDrive,
            'groups'          => self::nominationGroups($drive->nomination_drive_pk, $filters),
            'societyOptions'  => self::societyOptions($drive->nomination_drive_pk),
            'filters'         => $filters,
        ]);
    }

    /**
     * Nominations for a drive, grouped by post — the shape the View Nominations
     * screen renders, and the same rows its Download / Print produce.
     */
    public static function nominationGroups($nominationDrivePk, array $filters = [])
    {
        $query = DB::table('nomination as n')
            ->join('club_society_master as cs', 'cs.pk', '=', 'n.club_society_master_pk')
            ->join('club_society_role_master as r', 'r.pk', '=', 'n.club_society_role_master_pk')
            ->leftJoin('student_master as st', 'st.pk', '=', 'n.nominee_student_pk')
            ->where('n.nomination_drive_pk', $nominationDrivePk)
            ->select([
                'r.pk as role_pk',
                'r.club_society_role_name as post_name',
                'cs.club_society_name',
                'n.nominee_student_pk',
                'st.display_name as nominee_name',
                'st.generated_OT_code as ot_code',
                'st.photo_path',
                DB::raw('COUNT(DISTINCT n.nominated_by_student_pk) as nominated_by_count'),
                DB::raw(Nomination::statusPrecedenceSql('n.status') . ' as status'),
                DB::raw('MIN(n.created_date) as first_nominated_at'),
            ])
            ->groupBy(
                'r.pk', 'r.club_society_role_name', 'cs.club_society_name',
                'n.nominee_student_pk', 'st.display_name', 'st.generated_OT_code', 'st.photo_path'
            )
            ->orderBy('r.club_society_role_name')
            ->orderBy('st.display_name');

        if (filled($filters['club_society_master_pk'] ?? null)) {
            $query->where('n.club_society_master_pk', $filters['club_society_master_pk']);
        }
        if (filled($filters['status'] ?? null)) {
            $query->where('n.status', $filters['status']);
        }
        if (filled($filters['from'] ?? null)) {
            $query->whereDate('n.created_date', '>=', $filters['from']);
        }
        if (filled($filters['to'] ?? null)) {
            $query->whereDate('n.created_date', '<=', $filters['to']);
        }

        return $query->get()->groupBy('post_name');
    }

    /** Societies taking part in a nomination drive — the screen's filter list. */
    public static function societyOptions($nominationDrivePk)
    {
        return DB::table('nomination_drive_society as s')
            ->join('club_society_master as cs', 'cs.pk', '=', 's.club_society_master_pk')
            ->where('s.nomination_drive_pk', $nominationDrivePk)
            ->orderBy('cs.club_society_name')
            ->get(['cs.pk', 'cs.club_society_name']);
    }

    /**
     * Results screen. No design was supplied for this action, so it presents the
     * plainest defensible reading: per post, the accepted nominees ranked by how
     * many people nominated them, with the top N (N = posts available) marked
     * elected. Reachable once the election is live, so a permission holder can
     * preview the result and draw any tie at the last seat before publishing it.
     */
    public function showResult(Request $request, $id)
    {
        try {
            $drive = ElectionDrive::findOrFail(decrypt($id));
        } catch (\Throwable $e) {
            return redirect()->route('master.election.drive.index')->with('error', 'Election drive not found.');
        }

        if (! $drive->election_publish_status && ! $drive->result_status) {
            return redirect()->route('master.election.drive.index')->with('error', 'Publish the election first.');
        }

        // Ranked via OfficeBearerService so this screen and the Officer Bearers
        // listing can never disagree about who was elected. `require_published`
        // is off: this is the pre-publish preview as well as the published result.
        $rows = OfficeBearerService::rankedQuery([
            'election_drive_pk' => $drive->pk,
            'require_published' => false,
        ])
            ->orderBy('club_society_name')
            ->orderBy('role_name')
            ->orderByDesc('votes')
            // Same order the ranking uses, so the Rank column matches the badge.
            ->orderBy('rank_in_post')
            ->get();

        $groups = $rows->groupBy(fn ($r) => $r->club_society_name . '|' . $r->role_name)
            ->map(function ($group) {
                $group = OfficeBearerService::markTies($group->values());

                return $group->map(function ($row) {
                    // Elected exactly when the shared rule says so.
                    $row->elected = (int) $row->seats > 0 && (int) $row->rank_in_post <= (int) $row->seats;
                    $row->nominee_name = $row->officer_bearer_name;
                    $row->post_name = $row->role_name;
                    return $row;
                });
            });

        return view('admin.master.election_drive.result', [
            'drive'   => $drive,
            'driveId' => $id,
            'groups'  => $groups,
            // A tie can only be drawn while the result is unpublished, so the
            // Officer Bearers listing never shows a winner that changes later.
            'canDrawTie' => ! $drive->result_status,
        ]);
    }

    /**
     * Break a tie at the last seat of one post by a random draw (F-007 decision).
     *
     * Run once per post, before the result is published, by a holder of the
     * screen's permission. The draw uses random_int() (a CSPRNG) and is saved in
     * election_drive_tie_pick, so the winner never changes on reload; the ranking
     * then seats the drawn nominees ahead of the others on equal votes.
     */
    public function drawTie(Request $request, $id)
    {
        $data = $request->validate([
            'society' => ['required', 'integer'],
            'role'    => ['required', 'integer'],
        ]);

        try {
            $drivePk = decrypt($id);
        } catch (\Throwable $e) {
            return $this->failure($request, 'Election drive not found.', 404);
        }

        $actor = auth()->id();
        if (! $actor) {
            return $this->failure($request, 'Sign in again to draw a tie.', 403);
        }

        [$status, $message, $draw] = DB::transaction(function () use ($drivePk, $data, $actor) {
            $drive = ElectionDrive::lockForUpdate()->find($drivePk);
            if (! $drive) {
                return [404, 'Election drive not found.', null];
            }
            if ($drive->result_status) {
                return [422, 'The result is already published. Unpublish it before drawing a tie.', null];
            }
            if (! $drive->election_publish_status) {
                return [422, 'Publish the election first.', null];
            }

            $postKey = [
                'election_drive_pk'           => $drive->pk,
                'club_society_master_pk'      => (int) $data['society'],
                'club_society_role_master_pk' => (int) $data['role'],
            ];
            $rows = OfficeBearerService::rankedQuery([
                'election_drive_pk'      => $drive->pk,
                'club_society_master_pk' => (int) $data['society'],
                'require_published'      => false,
            ])->get()->filter(fn ($r) => (int) $r->club_society_role_master_pk === (int) $data['role'])->values();

            $tie = OfficeBearerService::tieAtLastSeat($rows);
            if (! $tie) {
                return [422, 'There is no tie at the last seat for this post.', null];
            }

            $candidates = $tie['rows']->pluck('nominee_student_pk')->map(fn ($v) => (int) $v)->sort()->values()->all();

            // Same validity rule the result screen uses (F-011, F-012): a draw
            // that still decides this tie is final; a stale one is replaced.
            $existing = DB::table('election_drive_tie_pick')->where($postKey)->get();
            if ($existing->isNotEmpty()) {
                if (OfficeBearerService::drawStillValid($tie)) {
                    return [422, 'The tie for this post has already been drawn.', null];
                }
                DB::table('election_drive_tie_pick')->where($postKey)->delete();
            }

            // Fisher-Yates with a CSPRNG, then seat the first N.
            $pool = $candidates;
            for ($i = count($pool) - 1; $i > 0; $i--) {
                $j = random_int(0, $i);
                [$pool[$i], $pool[$j]] = [$pool[$j], $pool[$i]];
            }
            $picked = array_slice($pool, 0, $tie['seats']);

            $now = now();
            DB::table('election_drive_tie_pick')->insert(array_map(fn ($nominee) => $postKey + [
                'nominee_student_pk' => $nominee,
                'tied_nominee_pks'   => implode(',', $candidates),
                'picked_by'          => $actor,
                'picked_at'          => $now,
            ], $picked));

            return [200, 'Tie drawn at random.', $postKey + [
                'candidates'        => $candidates,
                'picked'            => $picked,
                'stale_draw_cleared' => $existing->pluck('nominee_student_pk')->map(fn ($v) => (int) $v)->all(),
            ]];
        });

        if ($status !== 200) {
            return $this->failure($request, $message, $status);
        }

        $this->auditClubSociety('election_drive.draw_tie', $draw);

        return response()->json(['status' => 'success', 'message' => $message]);
    }

    /**
     * The one definition of the grid, shared by the DataTable, the Excel export
     * and the print sheet. Active / Archived mirrors the COURSE's own status,
     * reached through the nomination drive.
     */
    public static function baseDriveQuery(string $status = 'active', $coursePk = null)
    {
        $query = DB::table('election_drive as e')
            ->join('nomination_drive as nd', 'nd.pk', '=', 'e.nomination_drive_pk')
            ->join('course_master as c', 'c.pk', '=', 'nd.course_master_pk')
            ->where('e.active_inactive', 1)
            ->where('c.active_inactive', $status === 'archive' ? 0 : 1)
            ->select([
                'e.pk',
                'e.election_drive_name',
                'nd.drive_name as nomination_drive_name',
                DB::raw("COALESCE(NULLIF(c.couse_short_name, ''), c.course_name) as course_name"),
                'e.election_publish_status',
                'e.result_status',
            ])
            ->orderBy('e.pk', 'desc');

        if (filled($coursePk)) {
            $query->where('nd.course_master_pk', $coursePk);
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
                new ElectionDriveExport(
                    $request->input('status_filter', 'active'),
                    $request->input('course_master_pk')
                ),
                'election_drive.xlsx'
            );
        } catch (\Throwable $e) {
            return redirect()->route('master.election.drive.index')
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

        return view('admin.master.election_drive.export_print', [
            'rows'       => self::driveRows($status, $coursePk),
            'exportDate' => now()->format('d M Y, h:i A'),
            'filterLine' => implode(' &nbsp;|&nbsp; ', $filterBits),
        ]);
    }

    /** Download / Print for the View Nominations screen — honours its filters. */
    public function exportNominations(Request $request, $id)
    {
        try {
            $drive = ElectionDrive::findOrFail(decrypt($id));
        } catch (\Throwable $e) {
            return redirect()->route('master.election.drive.index')->with('error', 'Election drive not found.');
        }

        try {
            return Excel::download(
                new ElectionDriveNominationExport($drive->nomination_drive_pk, $request->only(['club_society_master_pk', 'status', 'from', 'to'])),
                'election_drive_nominations.xlsx'
            );
        } catch (\Throwable $e) {
            return redirect()->route('master.election.drive.nominations', ['id' => $id])
                ->with('error', 'Error exporting data: ' . $e->getMessage());
        }
    }

    public function printNominations(Request $request, $id)
    {
        try {
            $drive = ElectionDrive::findOrFail(decrypt($id));
        } catch (\Throwable $e) {
            return redirect()->route('master.election.drive.index')->with('error', 'Election drive not found.');
        }

        $filters = $request->only(['club_society_master_pk', 'status', 'from', 'to']);

        return view('admin.master.election_drive.nomination_export_print', [
            'drive'      => $drive,
            'groups'     => self::nominationGroups($drive->nomination_drive_pk, $filters),
            'exportDate' => now()->format('d M Y, h:i A'),
        ]);
    }

    /** Nomination drives available to attach an election to. */
    private function nominationDriveOptions()
    {
        return DB::table('nomination_drive as nd')
            ->join('course_master as c', 'c.pk', '=', 'nd.course_master_pk')
            ->where('nd.active_inactive', 1)
            ->orderBy('nd.drive_name')
            ->get([
                'nd.pk',
                'nd.drive_name',
                'nd.course_master_pk',
                DB::raw("COALESCE(NULLIF(c.couse_short_name, ''), c.course_name) as course_name"),
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

        return redirect()->route('master.election.drive.index')->with('error', $message);
    }
}
