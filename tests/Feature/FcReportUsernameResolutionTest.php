<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Pins how an FC report decides WHICH person a tracker row belongs to.
 *
 * `student_masters.user_id` is not one id space. It holds a `user_credentials.pk` for a
 * trainee who has been migrated to a login, and an `fc_registration_master.pk` for one who
 * registered through /fc/login and never was. Nothing on the row says which, and both tables
 * number from 1 — so a single integer can be a live credentials pk AND a live roster pk
 * belonging to two different people.
 *
 * That ambiguity has produced a wrong-identity bug twice, in opposite directions:
 *
 *   1. Reading `uc.user_name` first, unconditionally, showed a stranger's username for an
 *      unmigrated roster trainee. Production rendered roster pk 3 ("shailitm", Shalendra
 *      Kumar) as credentials pk 3 ("rohit.kumar").
 *   2. Fixing that by preferring the roster whenever the roster person is unmigrated then
 *      broke the mirror case: a MIGRATED trainee whose credentials pk happened to equal a
 *      different unmigrated person's roster pk started showing that other person's username.
 *
 * The resolution is that a join matching is coincidence, not evidence of identity. The roster
 * username is used only when the roster row can be corroborated as the same human — matching
 * mobile or email on the trainee's own profile row.
 *
 * These assertions are what stop either direction coming back. All three arms matter: remove
 * the corroboration and case 2 regresses; reorder the COALESCE and case 1 regresses.
 *
 * Per phpunit.xml, this suite runs against the database .env points at. Fixtures are created
 * inside a transaction and rolled back; nothing is migrated or truncated.
 *
 * Run with:  php artisan test --filter=FcReportUsernameResolution
 */
class FcReportUsernameResolutionTest extends TestCase
{
    use DatabaseTransactions;

    /** An id deliberately used as BOTH a credentials pk and a roster pk. */
    private const COLLIDING_ID = 90000001;

    private const CREDENTIALS_NAME = 'test_migrated_login';
    private const ROSTER_NAME      = 'test_roster_login';
    private const TRAINEE_MOBILE   = '9990000001';
    private const OTHER_MOBILE     = '9990000002';

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['user_credentials', 'fc_registration_master', 'student_masters', 'student_master_firsts'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("{$table} is absent in this environment.");
            }
        }

        if (fc_user_col('student_masters') !== 'user_id') {
            $this->markTestSkipped('Tracker is username-keyed here; the roster branch does not apply.');
        }
    }

    /**
     * Case 1 — the tracker id is a ROSTER pk. The roster row is this trainee (mobile matches),
     * so the roster username wins even though a credentials row sits at the same number.
     */
    public function test_roster_username_wins_when_the_roster_row_is_this_trainee(): void
    {
        $this->seedCollision(rosterContact: self::TRAINEE_MOBILE);

        $this->assertSame(
            self::ROSTER_NAME,
            $this->renderedUsername(),
            'An unmigrated roster trainee must render their roster username, not the unrelated '
            .'credentials row that happens to share the id.'
        );
    }

    /**
     * Case 2 — the tracker id is a CREDENTIALS pk. A roster row exists at the same number but
     * belongs to somebody else (mobile differs), so it must be ignored.
     */
    public function test_credentials_username_wins_when_the_roster_row_is_a_different_person(): void
    {
        $this->seedCollision(rosterContact: self::OTHER_MOBILE);

        $this->assertSame(
            self::CREDENTIALS_NAME,
            $this->renderedUsername(),
            'A roster row sitting at the same id is coincidence, not identity. Without matching '
            .'contact details it must not override the credentials username.'
        );
    }

    /** The roster branch is only ever consulted ahead of credentials — never after. */
    public function test_roster_case_is_evaluated_before_the_credentials_arm(): void
    {
        $sql = fc_report_login_username_sql('student_masters', 'sm');

        $rosterAt      = strpos($sql, 'uc_chk');
        $credentialsAt = strpos($sql, 'uc.user_name');

        $this->assertNotFalse($rosterAt, 'The roster CASE is missing from the resolution SQL.');
        $this->assertNotFalse($credentialsAt, 'The credentials arm is missing from the resolution SQL.');
        $this->assertLessThan(
            $credentialsAt,
            $rosterAt,
            'The roster CASE must precede uc.user_name in the COALESCE; behind it, it can never fire.'
        );
    }

    /** The corroboration itself must be present — without it, case 2 silently regresses. */
    public function test_roster_case_carries_the_identity_corroboration(): void
    {
        $sql = fc_report_roster_username_case('frm');

        $this->assertStringContainsString('uc_chk', $sql, 'Migration test missing from the roster CASE.');
        $this->assertStringContainsString('contact_no', $sql, 'Identity corroboration missing from the roster CASE.');
    }

    /** Callers without the profile join opt out rather than emitting an unjoinable alias. */
    public function test_corroboration_is_omitted_when_no_identity_alias_is_available(): void
    {
        $sql = fc_report_roster_username_case('frm', null);

        $this->assertStringContainsString('uc_chk', $sql);
        $this->assertStringNotContainsString('s1', $sql);
    }

    // ── fixtures ─────────────────────────────────────────────────────────────

    /**
     * One id, two people: a credentials login and an unrelated roster registration. The
     * trainee's own profile row carries TRAINEE_MOBILE; whether the roster row matches it is
     * what each test varies.
     */
    private function seedCollision(string $rosterContact): void
    {
        $id = self::COLLIDING_ID;

        DB::table('user_credentials')->insert(array_filter([
            'pk'         => $id,
            'user_name'  => self::CREDENTIALS_NAME,
            'first_name' => 'Migrated',
            'last_name'  => 'Trainee',
        ]));

        DB::table('fc_registration_master')->insert(array_filter([
            'pk'         => $id,
            'user_id'    => self::ROSTER_NAME,
            'contact_no' => $rosterContact,
        ]));

        DB::table('student_masters')->insert([
            'user_id' => $id,
            'form_id' => (int) (DB::table('fc_forms')->value('id') ?? 1),
            'status'  => 0,
        ]);

        DB::table('student_master_firsts')->insert([
            fc_user_col('student_master_firsts') => $id,
            'mobile_no'                          => self::TRAINEE_MOBILE,
        ]);
    }

    /** The resolution expression as the reports evaluate it, with the same joins. */
    private function renderedUsername(): ?string
    {
        return DB::table('student_masters as sm')
            ->leftJoin('user_credentials as uc', 'sm.user_id', '=', 'uc.pk')
            ->leftJoin('fc_registration_master as frm', 'frm.pk', '=', 'sm.user_id')
            ->leftJoin('user_credentials as uc_frm', 'uc_frm.user_name', '=', 'frm.user_id')
            ->leftJoin('student_master_firsts as s1', 's1.'.fc_user_col('student_master_firsts'), '=', 'sm.user_id')
            ->where('sm.user_id', self::COLLIDING_ID)
            ->selectRaw(fc_report_login_username_sql('student_masters', 'sm').' as login_username')
            ->value('login_username');
    }
}
