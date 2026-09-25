<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * PR #309 condition 5 - the DBA's decision on the user_id misalignment, executed.
 *
 * EnsureMemberRecordAccess::ownsMemberRecord() (F-024) added a contact-field proof
 * on top of user_credentials.user_id, because user_id alone was found pointing at
 * the wrong employee_master row for a meaningful number of accounts. That closed
 * an authorisation gap, but it also silently refused self-service for 32 real
 * category-'E' credentials that could not prove the row they were bound to. The
 * DBA reviewed reviews/pr-309-dba-brief-user-id-misalignment.md (kept outside the
 * repo - it names real people and carries their contact details) and decided,
 * per category:
 *
 *   A. 8 of the 9 demo-looking accounts (a shared login pattern) share an
 *      identical last_login to the second and were created within a 10-second
 *      window in 2013 - confirmed test/seed data. Already Active_inactive = 0
 *      for all eight; NO DATA CHANGE due here. One account (pk 2098) breaks
 *      the pattern (Active_inactive = 1) and was deliberately excluded from
 *      this decision - not touched by this migration.
 *   B. 5 accounts have a placeholder credential email (a@a.com) but are
 *      confirmed real and currently active. Decision: backfill
 *      user_credentials.email_id (and mobile_no where employee_master has one
 *      on file) from the linked employee_master row.
 *   C. 6 of the 7 accounts in the suspected user_id rotation are CONFIRMED by
 *      an independent signal (the credential's own name matches a DIFFERENT
 *      employee_master row than the one user_id points at, AND that row's
 *      contact info matches the credential's own contact info - both signals
 *      agreeing, not just the original cross-reference that raised the
 *      suspicion). The 6 corrected rows chain through each other into a 7th,
 *      uninvolved employee row that this migration does not touch.
 *      A 7th account in this cluster (pk 1397, user_id currently 11373) is
 *      DELIBERATELY EXCLUDED: its own name field does not match this
 *      cluster's names at all, so unlike the other 6, correcting it would be
 *      a guess rather than a confirmed identity. Left refused pending
 *      separate identification.
 *
 * F-059 (Blocker, this PR): an earlier version of this migration carried the
 * names, personal emails and mobile numbers behind these decisions as literal
 * values, committed to a public repository. Identity evidence for every row
 * below now lives ONLY in reviews/pr-309-dba-brief-user-id-misalignment.md,
 * kept outside this repository for exactly that reason. Rows are identified
 * here by pk alone, and the Category B contact values are never written into
 * source - up()/down() copy them from employee_master via SQL join at
 * migration time, so no personal value exists in this file at all.
 *
 * Every write below is guarded by the CURRENT (broken) value, so re-running
 * this migration after someone else has already fixed a row by hand is a
 * no-op for that row, not a clobber.
 */
return new class extends Migration
{
    /**
     * Category B: credential pks whose placeholder email (and, where
     * employee_master has one on file, mobile number) is backfilled from the
     * linked employee_master row. See the class docblock (F-059) for why no
     * email or mobile value appears here as a literal.
     */
    private const CONTACT_BACKFILL_PKS = [1441, 2120, 2195, 2201, 2389];

    /**
     * Category C: credential pk => [from (the wrong, current value), to (the
     * confirmed correct employee_master pk)]. `from` is asserted before
     * writing so this migration never overwrites a value someone already
     * corrected by hand, and `down()` can restore exactly what was here.
     * Identity evidence for each row is in the DBA brief (kept outside this
     * repository - see the class docblock, F-059).
     */
    private const USER_ID_CORRECTIONS = [
        1778 => ['from' => 10317, 'to' => 10525],
        1382 => ['from' => 10525, 'to' => 11373],
        1633 => ['from' => 10559, 'to' => 10317],
        1800 => ['from' => 10480, 'to' => 10559],
        1748 => ['from' => 10679, 'to' => 10480],
        1871 => ['from' => 11249, 'to' => 10679],
        // pk 1397 is DELIBERATELY NOT LISTED HERE. See the class docblock:
        // its own name does not match this cluster.
    ];

    public function up(): void
    {
        // Copied from employee_master at migration time - never a literal in
        // source (F-059). Guarded on the current placeholder value, exactly
        // as the per-row loop this replaced was.
        //
        // AND guarded on the credential's own NAME agreeing with the employee
        // row. EnsureMemberRecordAccess admits on a
        // contact match over this same join, so copying the employee row's
        // contact details into the credential would otherwise manufacture the
        // very proof the gate treats as independent - admitting the account
        // whatever its user_id points at. The name is the independent signal
        // category C already relies on; a row without it is left refused.
        foreach (self::CONTACT_BACKFILL_PKS as $credPk) {
            $row = DB::table('user_credentials as uc')
                ->join('employee_master as em', 'em.pk', '=', 'uc.user_id')
                ->where('uc.pk', $credPk)
                ->first([
                    'uc.first_name as cred_first',
                    'uc.last_name as cred_last',
                    'em.first_name as emp_first',
                    'em.middle_name as emp_middle',
                    'em.last_name as emp_last',
                ]);

            if (! $row || ! self::namesAgree(
                [$row->cred_first, $row->cred_last],
                [$row->emp_first, $row->emp_middle, $row->emp_last]
            )) {
                continue;
            }

            DB::table('user_credentials as uc')
                ->join('employee_master as em', 'em.pk', '=', 'uc.user_id')
                ->where('uc.pk', $credPk)
                ->whereRaw("LOWER(TRIM(uc.email_id)) = 'a@a.com'")
                ->whereRaw("TRIM(em.email) <> ''")
                ->update([
                    'uc.email_id' => DB::raw('TRIM(em.email)'),
                    // Only FILL an empty mobile_no, never overwrite one: an
                    // overwritten number could not be restored by down()
                    // 0 is not a real phone number on either side.
                    'uc.mobile_no' => DB::raw("CASE WHEN COALESCE(TRIM(uc.mobile_no), '') IN ('', '0') AND TRIM(em.mobile) NOT IN ('', '0') THEN TRIM(em.mobile) ELSE uc.mobile_no END"),
                    'uc.updated_date' => now(),
                ]);
        }

        foreach (self::USER_ID_CORRECTIONS as $credPk => $c) {
            DB::table('user_credentials')
                ->where('pk', $credPk)
                ->where('user_id', $c['from']) // guard: only if still the wrong value
                ->update(['user_id' => $c['to'], 'updated_date' => now()]);
        }
    }

    public function down(): void
    {
        foreach (self::USER_ID_CORRECTIONS as $credPk => $c) {
            DB::table('user_credentials')
                ->where('pk', $credPk)
                ->where('user_id', $c['to']) // only revert if still our corrected value
                ->update(['user_id' => $c['from'], 'updated_date' => now()]);
        }

        // Only revert a row whose email still matches its employee_master
        // row exactly - i.e. still carries what up() wrote, not a value
        // someone has since changed by hand.
        DB::table('user_credentials as uc')
            ->join('employee_master as em', 'em.pk', '=', 'uc.user_id')
            ->whereIn('uc.pk', self::CONTACT_BACKFILL_PKS)
            ->whereRaw('LOWER(TRIM(uc.email_id)) = LOWER(TRIM(em.email))')
            ->whereRaw("TRIM(em.email) <> ''")
            ->update([
                'uc.email_id' => 'a@a.com',
                'uc.updated_date' => now(),
            ]);

        // mobile_no is deliberately NOT reverted. up()
        // only fills an empty one, so the prior value was empty, but whether it
        // was NULL, '' or '0' was never recorded - and the version before this
        // nulled any number that merely EQUALLED employee_master's, including
        // numbers up() never wrote. A filled-in real number is left in place.
    }

    /**
     * Do the credential's own name and the employee row share a name token?
     * Same tokenisation as MemberRecordAccessTest::credentialsNamingSomebodyElse().
     *
     * @param  array<int, string|null>  $credential
     * @param  array<int, string|null>  $employee
     */
    private static function namesAgree(array $credential, array $employee): bool
    {
        $tokens = static function (array $parts): array {
            $out = [];
            foreach ($parts as $part) {
                foreach (preg_split('/[^a-z]+/', strtolower(trim((string) $part))) as $t) {
                    if (strlen($t) > 1) {
                        $out[] = $t;
                    }
                }
            }

            return $out;
        };

        return (bool) array_intersect($tokens($credential), $tokens($employee));
    }
};
