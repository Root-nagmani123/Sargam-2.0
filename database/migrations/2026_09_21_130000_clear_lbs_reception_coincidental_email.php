<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * PR #309 - a second, unrelated instance of F-024's original bug, found while
 * fixing the first one.
 *
 * The 2026_09_21_120000 migration corrected 6 credentials whose user_id had
 * been pointing at the wrong employee_master row. tests/Feature/
 * MemberRecordAccessTest::test_a_credential_naming_somebody_elses_record_is_refused
 * finds ANY credential/employee pair sharing no name token and asserts the
 * gate refuses it, but originally stopped at the FIRST such pair - one of the
 * 6 just fixed. Making it exhaustive (this commit) surfaced a second,
 * pre-existing pair that ALSO passes EnsureMemberRecordAccess::
 * ownsMemberRecord() despite sharing no name token:
 *
 *   user_credentials.pk = 2102, login 'lbs.reception', first/last name
 *   'LBSNAA'/'RECEPTION' - a shared front-desk account, not a person.
 *   Its user_id (11013) points at an employee_master row whose first/last
 *   name is literally 'Super'/'Admin', dob = the system's launch date
 *   (2018-07-06, matching this credential's own reg_date to the second),
 *   home_town_details = the literal string 'nil', and designation/department
 *   foreign keys that are 10-digit epoch-timestamp-shaped values rather than
 *   real lookups - synthetic setup data, not a real employee record.
 *
 * They pass only because both rows happen to carry the identical placeholder
 * address lbs@lbs.com - the setup account's contact address, reused as a
 * stand-in on both sides when both were created. This is NOT the same failure
 * mode as the 6 corrected in 2026_09_21_120000 (real people whose user_id
 * pointed at a different real person's record): this account is confirmed
 * dormant (Active_inactive = 0, jbp_enabled = 0 - login disabled at the
 * application level independently of this gate, last_login 2019-05-16) and
 * the target is not a real employee's personal data.
 *
 * DECIDED (2026-09-21, DBA/Engineering-lead authority, in session): clear the
 * coincidental email so the two rows no longer match on contact info, rather
 * than carry a named exception in the test. Nothing about the account's
 * Active_inactive/jbp_enabled/login flags is touched - those were already
 * correct and are not this migration's concern.
 */
return new class extends Migration
{
    private const CRED_PK = 2102;

    private const OLD_EMAIL = 'lbs@lbs.com';

    public function up(): void
    {
        DB::table('user_credentials')
            ->where('pk', self::CRED_PK)
            ->where('email_id', self::OLD_EMAIL) // guard: no-op if already changed
            ->update(['email_id' => null, 'updated_date' => now()]);
    }

    public function down(): void
    {
        DB::table('user_credentials')
            ->where('pk', self::CRED_PK)
            ->whereNull('email_id')
            ->update(['email_id' => self::OLD_EMAIL, 'updated_date' => now()]);
    }
};
