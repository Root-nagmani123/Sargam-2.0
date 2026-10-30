<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\Master\NominationDriveController;
use App\Models\Nomination;
use App\Services\ClubSociety\OfficeBearerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

/**
 * Closure tests for PR #340 F-002..F-006 and F-010, run against an in-memory
 * SQLite database built by the PR's own table migrations, so they need no
 * application database and never touch one (trap 39).
 *
 * The route gate is covered by ClubSocietyRouteGateTest; it is switched off here
 * so these tests reach the controller bodies.
 */
class ClubSocietyElectionRulesTest extends TestCase
{
    private const TABLE_MIGRATIONS = [
        '2026_09_07_000001_create_club_society_master_table.php',
        '2026_09_07_000003_create_club_society_role_master_table.php',
        '2026_09_07_000005_create_club_society_programme_mapping_table.php',
        '2026_09_07_000007_create_club_society_role_programme_mapping_table.php',
        '2026_09_07_000009_create_nomination_drive_tables.php',
        '2026_09_07_000011_create_election_drive_tables.php',
        '2026_09_07_000014_create_election_drive_tie_pick_table.php',
    ];

    private const ASHA   = 101;
    private const BHARAT = 102;

    private ?string $previousConnection = null;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.club_society_probe' => [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]]);

        $this->previousConnection = DB::getDefaultConnection();
        DB::setDefaultConnection('club_society_probe');

        Schema::create('course_master', function ($t) {
            $t->increments('pk');
            $t->string('course_name')->nullable();
            $t->string('couse_short_name')->nullable();
            $t->integer('active_inactive')->default(1);
        });
        Schema::create('student_master', function ($t) {
            $t->increments('pk');
            $t->string('display_name')->nullable();
            $t->string('generated_OT_code')->nullable();
            $t->string('photo_path')->nullable();
        });

        foreach (self::TABLE_MIGRATIONS as $file) {
            (include database_path('migrations/society/'.$file))->up();
        }

        $this->withoutMiddleware();
        $this->seedElection();
    }

    protected function tearDown(): void
    {
        DB::purge('club_society_probe');
        DB::setDefaultConnection($this->previousConnection);

        parent::tearDown();
    }

    /**
     * Course 1, club 1 with post (role) 1 carrying one seat. Asha has 3 accepted
     * nominators, Bharat 2. Election drive 1 polls club 1.
     */
    private function seedElection(): void
    {
        DB::table('course_master')->insert(['pk' => 1, 'course_name' => 'Phase I', 'active_inactive' => 1]);
        DB::table('student_master')->insert([
            ['pk' => self::ASHA, 'display_name' => 'Asha'],
            ['pk' => self::BHARAT, 'display_name' => 'Bharat'],
            ['pk' => 1, 'display_name' => 'N1'], ['pk' => 2, 'display_name' => 'N2'],
            ['pk' => 3, 'display_name' => 'N3'], ['pk' => 4, 'display_name' => 'N4'],
            ['pk' => 5, 'display_name' => 'N5'],
        ]);
        DB::table('club_society_master')->insert(['pk' => 1, 'club_society_name' => 'Club', 'active_inactive' => 1]);
        DB::table('club_society_role_master')->insert(['pk' => 1, 'club_society_role_name' => 'Secretary', 'active_inactive' => 1]);
        DB::table('club_society_role_programme_mapping')->insert([
            'course_master_pk' => 1, 'club_society_master_pk' => 1, 'club_society_role_master_pk' => 1,
            'number_of_post' => 1, 'required_nomination' => 0, 'active_inactive' => 1,
        ]);
        DB::table('nomination_drive')->insert([
            'pk' => 1, 'drive_name' => 'ND', 'course_master_pk' => 1, 'start_date' => '2026-10-01', 'end_date' => '2026-10-10',
            'self_nomination_allow' => 0, 'nomination_accept_status' => 1, 'nomination_withdraw_status' => 1, 'active_inactive' => 1,
        ]);
        DB::table('nomination_drive_society')->insert(['pk' => 1, 'nomination_drive_pk' => 1, 'club_society_master_pk' => 1]);
        DB::table('nomination_drive_society_post')->insert(['nomination_drive_society_pk' => 1, 'club_society_role_master_pk' => 1]);

        foreach ([[self::ASHA, 1], [self::ASHA, 2], [self::ASHA, 3], [self::BHARAT, 4], [self::BHARAT, 5]] as [$nominee, $by]) {
            DB::table('nomination')->insert([
                'nomination_drive_pk' => 1, 'club_society_master_pk' => 1, 'club_society_role_master_pk' => 1,
                'nominee_student_pk' => $nominee, 'nominated_by_student_pk' => $by, 'status' => 'accepted',
            ]);
        }

        DB::table('election_drive')->insert([
            'pk' => 1, 'election_drive_name' => 'E1', 'nomination_drive_pk' => 1, 'drive_date' => '2026-10-15',
            'start_time' => '09:00', 'end_time' => '17:00', 'election_publish_status' => 1, 'result_status' => 1, 'active_inactive' => 1,
        ]);
        DB::table('election_drive_society')->insert(['election_drive_pk' => 1, 'club_society_master_pk' => 1]);
    }

    private function electedNames(): array
    {
        return OfficeBearerService::electedQuery()->pluck('officer_bearer_name')->all();
    }

    private function electionPayload(array $overrides = []): array
    {
        return array_merge([
            'election_drive_name' => 'E2', 'nomination_drive_pk' => 1, 'drive_date' => '2026-10-20',
            'start_time' => '09:00', 'end_time' => '17:00', 'same_date_for_all' => 1, 'same_time_for_all' => 1,
            'society_pks' => [1],
        ], $overrides);
    }

    // ---- F-003 ------------------------------------------------------------

    public function test_withdraw_removes_the_nominee_from_every_row_and_from_the_result(): void
    {
        $this->assertSame(['Asha'], $this->electedNames());

        // Withdrawal is only allowed while the result is unpublished (F-007 rule).
        DB::table('election_drive')->where('pk', 1)->update(['result_status' => 0]);
        $minPk = DB::table('nomination')->where('nominee_student_pk', self::ASHA)->min('pk');
        $this->postJson(route('master.nomination.drive.nomination.withdraw', ['id' => encrypt($minPk)]))
            ->assertOk();
        DB::table('election_drive')->where('pk', 1)->update(['result_status' => 1]);

        $this->assertSame(
            ['withdrawn', 'withdrawn', 'withdrawn'],
            DB::table('nomination')->where('nominee_student_pk', self::ASHA)->orderBy('pk')->pluck('status')->all()
        );
        $this->assertSame(['Bharat'], $this->electedNames());

        $asha = NominationDriveController::nomineeRows(1)->firstWhere('nominee_student_pk', self::ASHA);
        $this->assertSame('withdrawn', $asha->status);
    }

    public function test_a_partially_withdrawn_nominee_is_never_elected(): void
    {
        // Legacy shape the old withdraw left behind: one row withdrawn, two accepted.
        $minPk = DB::table('nomination')->where('nominee_student_pk', self::ASHA)->min('pk');
        DB::table('nomination')->where('pk', $minPk)->update(['status' => 'withdrawn']);

        $this->assertSame(['Bharat'], $this->electedNames());
    }

    public function test_mixed_pending_and_accepted_rows_display_as_accepted(): void
    {
        $minPk = DB::table('nomination')->where('nominee_student_pk', self::BHARAT)->min('pk');
        DB::table('nomination')->where('pk', $minPk)->update(['status' => 'pending']);

        $bharat = NominationDriveController::nomineeRows(1)->firstWhere('nominee_student_pk', self::BHARAT);
        $this->assertSame('accepted', $bharat->status);
    }

    // ---- F-006 ------------------------------------------------------------

    public function test_unpublishing_the_election_withdraws_its_result(): void
    {
        $this->postJson(route('master.election.drive.publish', ['id' => encrypt(1)]), [
            'field' => 'election_publish_status', 'value' => 0,
        ])->assertOk();

        $row = DB::table('election_drive')->where('pk', 1)->first();
        $this->assertSame([0, 0], [(int) $row->election_publish_status, (int) $row->result_status]);
        $this->assertSame([], $this->electedNames());
    }

    public function test_a_second_election_on_the_same_nomination_drive_is_refused(): void
    {
        $this->postJson(route('master.election.drive.store'), $this->electionPayload())
            ->assertStatus(422)
            ->assertJsonValidationErrors('nomination_drive_pk');

        $this->assertSame(1, DB::table('election_drive')->count());
        $this->assertSame(['Asha'], $this->electedNames());
    }

    public function test_an_election_with_a_published_result_cannot_be_re_pointed(): void
    {
        DB::table('nomination_drive')->insert([
            'pk' => 2, 'drive_name' => 'ND2', 'course_master_pk' => 1, 'start_date' => '2026-10-01', 'end_date' => '2026-10-10',
            'self_nomination_allow' => 0, 'nomination_accept_status' => 1, 'nomination_withdraw_status' => 1, 'active_inactive' => 1,
        ]);

        $this->postJson(route('master.election.drive.store'), $this->electionPayload([
            'pk' => encrypt(1), 'election_drive_name' => 'E1', 'nomination_drive_pk' => 2,
        ]))->assertStatus(422)->assertJsonValidationErrors('nomination_drive_pk');

        $this->assertSame(1, (int) DB::table('election_drive')->where('pk', 1)->value('nomination_drive_pk'));
    }

    public function test_editing_an_election_without_re_pointing_still_saves(): void
    {
        $this->postJson(route('master.election.drive.store'), $this->electionPayload([
            'pk' => encrypt(1), 'election_drive_name' => 'E1 renamed',
        ]))->assertOk();

        $this->assertSame('E1 renamed', DB::table('election_drive')->where('pk', 1)->value('election_drive_name'));
    }

    public function test_new_election_drive_still_starts_pending(): void
    {
        DB::table('election_drive_society')->delete();
        DB::table('election_drive')->delete();

        $this->postJson(route('master.election.drive.store'), $this->electionPayload())->assertOk();

        $row = DB::table('election_drive')->first();
        $this->assertSame([0, 0, 1], [(int) $row->election_publish_status, (int) $row->result_status, (int) $row->active_inactive]);
    }

    public function test_result_screen_is_refused_until_the_election_is_published(): void
    {
        DB::table('election_drive')->where('pk', 1)->update(['election_publish_status' => 0, 'result_status' => 0]);

        $this->get(route('master.election.drive.result', ['id' => encrypt(1)]))
            ->assertRedirect(route('master.election.drive.index'));
    }

    // ---- F-005 ------------------------------------------------------------

    public function test_a_club_in_use_cannot_be_deleted(): void
    {
        $this->deleteJson(route('master.club.society.destroy', ['id' => encrypt(1)]))->assertStatus(422);

        $this->assertSame(1, DB::table('club_society_master')->count());
        $this->assertSame(['Asha'], $this->electedNames());
    }

    public function test_a_role_in_use_cannot_be_deleted(): void
    {
        $this->deleteJson(route('master.club.society.role.destroy', ['id' => encrypt(1)]))->assertStatus(422);

        $this->assertSame(1, DB::table('club_society_role_master')->count());
    }

    public function test_an_unused_club_can_still_be_deleted(): void
    {
        DB::table('club_society_master')->insert(['pk' => 2, 'club_society_name' => 'Unused', 'active_inactive' => 1]);

        $this->deleteJson(route('master.club.society.destroy', ['id' => encrypt(2)]))->assertOk();

        $this->assertFalse(DB::table('club_society_master')->where('pk', 2)->exists());
    }

    public function test_a_nomination_drive_with_an_election_cannot_be_deleted(): void
    {
        $this->deleteJson(route('master.nomination.drive.destroy', ['id' => encrypt(1)]))->assertStatus(422);

        $this->assertSame(5, DB::table('nomination')->count());
        $this->assertSame(1, DB::table('nomination_drive')->count());
    }

    public function test_a_nomination_drive_without_an_election_still_deletes_with_its_nominations(): void
    {
        DB::table('election_drive_society')->delete();
        DB::table('election_drive')->delete();

        $this->deleteJson(route('master.nomination.drive.destroy', ['id' => encrypt(1)]))->assertOk();

        $this->assertSame(0, DB::table('nomination_drive')->count());
        $this->assertSame(0, DB::table('nomination')->count());
    }

    // ---- F-002 ------------------------------------------------------------

    public function test_privileged_actions_write_an_audit_line(): void
    {
        $lines = [];
        \Illuminate\Support\Facades\Log::listen(function ($event) use (&$lines) {
            $lines[] = $event->message;
        });

        $this->postJson(route('master.election.drive.publish', ['id' => encrypt(1)]), [
            'field' => 'result_status', 'value' => 0,
        ])->assertOk();
        $minPk = DB::table('nomination')->where('nominee_student_pk', self::BHARAT)->min('pk');
        $this->postJson(route('master.nomination.drive.nomination.withdraw', ['id' => encrypt($minPk)]))->assertOk();
        $this->deleteJson(route('master.election.drive.destroy', ['id' => encrypt(1)]))->assertOk();

        $this->assertSame(
            ['club_society.election_drive.publish', 'club_society.nomination.withdraw', 'club_society.election_drive.delete'],
            array_values(array_filter($lines, fn ($m) => str_starts_with($m, 'club_society.')))
        );
    }

    // ---- F-004 / F-010 ----------------------------------------------------

    public function test_every_module_export_binds_formula_shaped_text_as_a_string(): void
    {
        foreach ([
            'ClubSocietyMaster', 'ClubSocietyProgrammeMapping', 'ClubSocietyRoleMaster', 'ClubSocietyRoleProgrammeMapping',
            'ElectionDrive', 'ElectionDriveNomination', 'NominationDrive', 'NominationDriveNominee', 'OfficeBearer',
        ] as $name) {
            $class  = 'App\\Exports\\'.$name.'Export';
            $export = (new \ReflectionClass($class))->newInstanceWithoutConstructor();
            $cell   = (new Spreadsheet())->getActiveSheet()->getCell('A1');

            $export->bindValue($cell, '=HYPERLINK("http://example.invalid","x")');

            $this->assertSame(DataType::TYPE_STRING, $cell->getDataType(), "{$class} wrote a formula");
        }
    }

    public function test_nomination_status_is_not_mass_assignable(): void
    {
        $nomination = Nomination::create([
            'nomination_drive_pk' => 1, 'club_society_master_pk' => 1, 'club_society_role_master_pk' => 1,
            'nominee_student_pk' => 1, 'nominated_by_student_pk' => 2, 'status' => 'accepted',
        ]);

        $this->assertSame('pending', DB::table('nomination')->where('pk', $nomination->pk)->value('status'));
    }

    // ---- F-007: ties are shown, the outcome is unchanged ------------------

    private function resultRows(): \Illuminate\Support\Collection
    {
        $rows = OfficeBearerService::rankedQuery(['election_drive_pk' => 1, 'require_published' => false])
            ->orderByDesc('votes')->orderBy('rank_in_post')->get();

        return OfficeBearerService::markTies($rows);
    }

    /** Bharat gains a third nominator: 3 v 3 for one seat. */
    private function makeTie(): void
    {
        DB::table('student_master')->insert(['pk' => 6, 'display_name' => 'N6']);
        DB::table('nomination')->insert([
            'nomination_drive_pk' => 1, 'club_society_master_pk' => 1, 'club_society_role_master_pk' => 1,
            'nominee_student_pk' => self::BHARAT, 'nominated_by_student_pk' => 6, 'status' => 'accepted',
        ]);
    }

    public function test_a_tie_at_the_last_seat_is_flagged_without_changing_who_is_elected(): void
    {
        $this->makeTie();

        $rows = $this->resultRows();

        $this->assertSame([true, true], $rows->pluck('tied')->all());
        // No draw run: falls back to lower record number.
        $this->assertSame(['Asha'], $this->electedNames());
    }

    public function test_no_tie_is_flagged_when_the_last_seat_is_clear(): void
    {
        $this->assertSame([false, false], $this->resultRows()->pluck('tied')->all());
    }

    // ---- F-007: random tie-break draw ---------------------------------------

    private function drawTie(): \Illuminate\Testing\TestResponse
    {
        $this->actingAs((new \App\Models\User())->forceFill(['pk' => 77]));

        return $this->postJson(route('master.election.drive.result.draw.tie', ['id' => encrypt(1)]), [
            'society' => 1, 'role' => 1,
        ]);
    }

    public function test_a_tie_is_drawn_once_and_the_drawn_nominee_is_elected(): void
    {
        $this->makeTie();
        DB::table('election_drive')->where('pk', 1)->update(['result_status' => 0]);

        $this->drawTie()->assertOk();

        $pick = DB::table('election_drive_tie_pick')->get();
        $this->assertCount(1, $pick);
        $this->assertContains((int) $pick[0]->nominee_student_pk, [self::ASHA, self::BHARAT]);
        $this->assertSame(self::ASHA.','.self::BHARAT, $pick[0]->tied_nominee_pks);
        $this->assertSame(77, (int) $pick[0]->picked_by);

        // Publish, then the drawn nominee is the officer bearer — every time.
        DB::table('election_drive')->where('pk', 1)->update(['result_status' => 1]);
        $winner = (int) $pick[0]->nominee_student_pk === self::ASHA ? 'Asha' : 'Bharat';
        $this->assertSame([$winner], $this->electedNames());
        $this->assertSame([$winner], $this->electedNames());

        $rows = $this->resultRows();
        $this->assertTrue($rows->every(fn ($r) => $r->tie_drawn));

        // Cannot be re-drawn.
        DB::table('election_drive')->where('pk', 1)->update(['result_status' => 0]);
        $this->drawTie()->assertStatus(422);
        $this->assertCount(1, DB::table('election_drive_tie_pick')->get());
    }

    public function test_both_outcomes_of_the_draw_occur(): void
    {
        // The draw is random, not a disguised fixed order.
        $this->makeTie();
        DB::table('election_drive')->where('pk', 1)->update(['result_status' => 0]);

        $seen = [];
        for ($i = 0; $i < 40 && count($seen) < 2; $i++) {
            DB::table('election_drive_tie_pick')->delete();
            $this->drawTie()->assertOk();
            $seen[(int) DB::table('election_drive_tie_pick')->value('nominee_student_pk')] = true;
        }

        $this->assertCount(2, $seen);
    }

    public function test_a_tie_cannot_be_drawn_once_the_result_is_published(): void
    {
        $this->makeTie();

        $this->drawTie()->assertStatus(422);
        $this->assertSame(0, DB::table('election_drive_tie_pick')->count());
    }

    public function test_a_post_without_a_tie_cannot_be_drawn(): void
    {
        DB::table('election_drive')->where('pk', 1)->update(['result_status' => 0]);

        $this->drawTie()->assertStatus(422);
        $this->assertSame(0, DB::table('election_drive_tie_pick')->count());
    }

    public function test_a_partial_tie_draws_only_the_seats_left(): void
    {
        // Two seats: Asha 3 is seated; Bharat 2 and Chetan 2 tie for the second.
        DB::table('club_society_role_programme_mapping')->update(['number_of_post' => 2]);
        DB::table('student_master')->insert([
            ['pk' => 103, 'display_name' => 'Chetan'], ['pk' => 6, 'display_name' => 'N6'], ['pk' => 7, 'display_name' => 'N7'],
        ]);
        foreach ([6, 7] as $by) {
            DB::table('nomination')->insert([
                'nomination_drive_pk' => 1, 'club_society_master_pk' => 1, 'club_society_role_master_pk' => 1,
                'nominee_student_pk' => 103, 'nominated_by_student_pk' => $by, 'status' => 'accepted',
            ]);
        }
        DB::table('election_drive')->where('pk', 1)->update(['result_status' => 0]);

        $this->drawTie()->assertOk();

        $pick = DB::table('election_drive_tie_pick')->get();
        $this->assertCount(1, $pick);
        $this->assertSame(self::BHARAT.',103', $pick[0]->tied_nominee_pks);

        DB::table('election_drive')->where('pk', 1)->update(['result_status' => 1]);
        $elected = $this->electedNames();
        sort($elected);
        $expected = ['Asha', (int) $pick[0]->nominee_student_pk === 103 ? 'Chetan' : 'Bharat'];
        sort($expected);
        $this->assertSame($expected, $elected);
    }

    // ---- F-011: a draw is valid only for the tie it was drawn from ---------

    /** Asha, Bharat and Chetan tie 3-3-3 for one seat; result unpublished. */
    private function makeThreeWayTie(): void
    {
        $this->makeTie();
        DB::table('student_master')->insert([
            ['pk' => 103, 'display_name' => 'Chetan'], ['pk' => 7, 'display_name' => 'N7'],
            ['pk' => 8, 'display_name' => 'N8'], ['pk' => 9, 'display_name' => 'N9'],
        ]);
        foreach ([7, 8, 9] as $by) {
            DB::table('nomination')->insert([
                'nomination_drive_pk' => 1, 'club_society_master_pk' => 1, 'club_society_role_master_pk' => 1,
                'nominee_student_pk' => 103, 'nominated_by_student_pk' => $by, 'status' => 'accepted',
            ]);
        }
        DB::table('election_drive')->where('pk', 1)->update(['result_status' => 0]);
    }

    public function test_withdrawing_the_drawn_nominee_voids_the_draw_and_allows_a_redraw(): void
    {
        $this->makeThreeWayTie();
        $this->drawTie()->assertOk();
        $drawn = (int) DB::table('election_drive_tie_pick')->value('nominee_student_pk');

        $minPk = DB::table('nomination')->where('nominee_student_pk', $drawn)->min('pk');
        $this->postJson(route('master.nomination.drive.nomination.withdraw', ['id' => encrypt($minPk)]))->assertOk();

        // The draw is stale: the screen offers the draw again.
        $rows = $this->resultRows();
        $this->assertSame(2, $rows->where('tied', true)->count());
        $this->assertFalse($rows->first()->tie_drawn);

        // Redraw among the remaining two replaces the stale pick; the seat follows it.
        $this->drawTie()->assertOk();
        $this->assertSame(1, DB::table('election_drive_tie_pick')->count());
        $pick = DB::table('election_drive_tie_pick')->first();
        $remaining = array_values(array_diff([self::ASHA, self::BHARAT, 103], [$drawn]));
        sort($remaining);
        $this->assertSame(implode(',', $remaining), $pick->tied_nominee_pks);

        DB::table('election_drive')->where('pk', 1)->update(['result_status' => 1]);
        $names = [self::ASHA => 'Asha', self::BHARAT => 'Bharat', 103 => 'Chetan'];
        $this->assertSame([$names[(int) $pick->nominee_student_pk]], $this->electedNames());
    }

    public function test_a_stale_stored_draw_is_replaced_not_refused(): void
    {
        // A pick saved for a tie that no longer matches the current one.
        $this->makeThreeWayTie();
        DB::table('election_drive_tie_pick')->insert([
            'election_drive_pk' => 1, 'club_society_master_pk' => 1, 'club_society_role_master_pk' => 1,
            'nominee_student_pk' => self::ASHA, 'tied_nominee_pks' => self::ASHA.','.self::BHARAT, 'picked_by' => 1,
        ]);

        $this->drawTie()->assertOk();

        $pick = DB::table('election_drive_tie_pick')->get();
        $this->assertCount(1, $pick);
        $this->assertSame(self::ASHA.','.self::BHARAT.',103', $pick[0]->tied_nominee_pks);
    }

    // ---- F-012: withdrawals never rewrite a valid or published draw --------

    /** Asha 3 v Bharat 3 for one seat, plus Dev with 1 nominator; result unpublished. */
    private function makeTieWithOutsider(): void
    {
        $this->makeTie();
        DB::table('student_master')->insert([['pk' => 104, 'display_name' => 'Dev'], ['pk' => 10, 'display_name' => 'N10']]);
        DB::table('nomination')->insert([
            'nomination_drive_pk' => 1, 'club_society_master_pk' => 1, 'club_society_role_master_pk' => 1,
            'nominee_student_pk' => 104, 'nominated_by_student_pk' => 10, 'status' => 'accepted',
        ]);
        DB::table('election_drive')->where('pk', 1)->update(['result_status' => 0]);
    }

    private function withdraw(int $nominee): void
    {
        $minPk = DB::table('nomination')->where('nominee_student_pk', $nominee)->min('pk');
        $this->postJson(route('master.nomination.drive.nomination.withdraw', ['id' => encrypt($minPk)]))->assertOk();
    }

    public function test_after_publish_a_withdrawal_is_refused_and_the_officer_bearer_stays(): void
    {
        $this->makeTieWithOutsider();
        $this->drawTie()->assertOk();
        $winner = (int) DB::table('election_drive_tie_pick')->value('nominee_student_pk') === self::ASHA ? 'Asha' : 'Bharat';
        DB::table('election_drive')->where('pk', 1)->update(['result_status' => 1]);
        $this->assertSame([$winner], $this->electedNames());

        // Neither an outsider nor the drawn winner can withdraw once published.
        foreach ([104, $winner === 'Asha' ? self::ASHA : self::BHARAT] as $nominee) {
            $minPk = DB::table('nomination')->where('nominee_student_pk', $nominee)->min('pk');
            $this->postJson(route('master.nomination.drive.nomination.withdraw', ['id' => encrypt($minPk)]))
                ->assertStatus(422);
        }
        $this->assertSame(0, DB::table('nomination')->where('status', 'withdrawn')->count());

        $this->assertSame(1, DB::table('election_drive_tie_pick')->count());
        $this->assertSame([$winner], $this->electedNames());
    }

    public function test_before_publish_a_withdrawal_outside_the_tie_keeps_a_valid_draw(): void
    {
        $this->makeTieWithOutsider();
        $this->drawTie()->assertOk();
        $picked = (int) DB::table('election_drive_tie_pick')->value('nominee_student_pk');

        $this->withdraw(104);

        $this->assertTrue($this->resultRows()->first()->tie_drawn);
        $this->drawTie()->assertStatus(422);
        $this->assertSame($picked, (int) DB::table('election_drive_tie_pick')->value('nominee_student_pk'));
    }

    public function test_withdrawing_an_undrawn_tied_nominee_keeps_the_draw(): void
    {
        // Three-way tie; one of the two nominees the draw did not seat withdraws.
        $this->makeThreeWayTie();
        $this->drawTie()->assertOk();
        $picked = (int) DB::table('election_drive_tie_pick')->value('nominee_student_pk');
        $loser = current(array_diff([self::ASHA, self::BHARAT, 103], [$picked]));

        $this->withdraw($loser);

        $this->assertTrue($this->resultRows()->first()->tie_drawn);
        $this->drawTie()->assertStatus(422);
    }

    // ---- F-007 rule: no withdrawal once the result is published ------------

    public function test_no_withdrawal_after_the_result_is_published(): void
    {
        $minPk = DB::table('nomination')->where('nominee_student_pk', self::ASHA)->min('pk');

        $this->postJson(route('master.nomination.drive.nomination.withdraw', ['id' => encrypt($minPk)]))
            ->assertStatus(422);

        $this->assertSame(0, DB::table('nomination')->where('status', 'withdrawn')->count());
        $this->assertSame(['Asha'], $this->electedNames());
    }

    public function test_withdrawal_works_again_once_the_result_is_unpublished(): void
    {
        DB::table('election_drive')->where('pk', 1)->update(['result_status' => 0]);
        $minPk = DB::table('nomination')->where('nominee_student_pk', self::ASHA)->min('pk');

        $this->postJson(route('master.nomination.drive.nomination.withdraw', ['id' => encrypt($minPk)]))
            ->assertOk();
    }

    // ---- F-008: rollbacks never take data with them -----------------------

    public function test_rollback_refuses_to_drop_tables_that_hold_rows(): void
    {
        try {
            (include database_path('migrations/society/2026_09_07_000009_create_nomination_drive_tables.php'))->down();
            $this->fail('down() dropped tables that hold rows');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Refusing to roll back', $e->getMessage());
        }

        // Checked before the first drop: nothing in the migration was dropped.
        foreach (['nomination', 'nomination_drive_society_post', 'nomination_drive_society', 'nomination_drive'] as $table) {
            $this->assertTrue(Schema::hasTable($table), "{$table} was dropped");
        }
        $this->assertSame(5, DB::table('nomination')->count());
    }

    public function test_rollback_still_drops_empty_tables(): void
    {
        DB::table('election_drive_society')->delete();
        DB::table('election_drive')->delete();

        (include database_path('migrations/society/2026_09_07_000011_create_election_drive_tables.php'))->down();

        $this->assertFalse(Schema::hasTable('election_drive'));
        $this->assertFalse(Schema::hasTable('election_drive_society'));
    }

    public function test_menu_rollback_keeps_a_permission_that_has_been_granted(): void
    {
        config(['cache.default' => 'array']);
        $this->createMenuTables();
        DB::table('permissions')->insert([
            ['id' => 1, 'name' => 'create_election_drive', 'guard_name' => 'web'],
            ['id' => 2, 'name' => 'create_nomination_drive', 'guard_name' => 'web'],
        ]);
        DB::table('role_has_permissions')->insert(['permission_id' => 1, 'role_id' => 9]);

        (include database_path('migrations/society/2026_09_07_000012_add_election_drive_sidebar_menu.php'))->down();
        (include database_path('migrations/society/2026_09_07_000010_add_nomination_drive_sidebar_menu.php'))->down();

        $this->assertTrue(DB::table('permissions')->where('id', 1)->exists(), 'a granted permission was deleted');
        $this->assertFalse(DB::table('permissions')->where('id', 2)->exists(), 'an unused permission was kept');
    }

    private function createMenuTables(): void
    {
        Schema::create('sidebar_categories', function ($t) {
            $t->increments('id'); $t->string('slug'); $t->timestamp('deleted_at')->nullable();
        });
        Schema::create('menu_groups', function ($t) {
            $t->increments('id'); $t->integer('category_id'); $t->string('name'); $t->timestamp('deleted_at')->nullable();
        });
        Schema::create('menus', function ($t) {
            $t->increments('id'); $t->string('route')->nullable(); $t->integer('group_id')->nullable();
            $t->timestamps(); $t->timestamp('deleted_at')->nullable();
        });
        Schema::create('permissions', function ($t) {
            $t->increments('id'); $t->string('name'); $t->string('guard_name');
        });
        Schema::create('role_has_permissions', function ($t) {
            $t->integer('permission_id'); $t->integer('role_id');
        });
        Schema::create('model_has_permissions', function ($t) {
            $t->integer('permission_id'); $t->string('model_type'); $t->integer('model_id');
        });
    }
}
