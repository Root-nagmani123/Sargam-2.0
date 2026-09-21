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
 * repo - it names real people) and decided, per category:
 *
 *   A. 8 of the 9 demo-looking accounts (login NOP0xxx) share an identical
 *      last_login to the second and were created within a 10-second window in
 *      2013 - confirmed test/seed data. Already Active_inactive = 0 for all
 *      eight; NO DATA CHANGE due here. NOP00127 (pk 2098) breaks the pattern
 *      (Active_inactive = 1) and was deliberately excluded from this decision -
 *      not touched by this migration.
 *   B. 5 accounts have a placeholder credential email (a@a.com) but are
 *      confirmed real and currently active. Decision: backfill
 *      user_credentials.email_id (and mobile_no where employee_master has one)
 *      from the linked employee_master row.
 *   C. 6 of the 7 accounts in the suspected user_id rotation are CONFIRMED by
 *      an independent signal (the credential's own first_name/last_name matches
 *      a DIFFERENT employee_master row than the one user_id points at, AND that
 *      row's contact info matches the credential's own contact info - both
 *      signals agreeing, not just the original cross-reference that raised the
 *      suspicion). The chain closes cleanly:
 *        kt231(wrong)->bhawana's true row->anjali's true row->brijesh's true
 *        row->azad's true row->eswara's true row->sonali's true row->(a
 *        different, uninvolved employee - REDACTED-NAME, pk 11249, who is
 *        not part of this decision).
 *      kt231 (pk 1397, user_id currently 11373) is DELIBERATELY EXCLUDED: its
 *      own name field ("REDACTED-NAME") does not match this cluster's names at
 *      all, so unlike the other 6, correcting it would be a guess rather than a
 *      confirmed identity. Left refused pending separate identification.
 *
 * Every one of the 11 rows this migration touches is left findable in its own
 * commented block below with the evidence for that specific row, and every
 * write is guarded by the CURRENT (broken) value so re-running this migration
 * after someone else has already fixed a row by hand is a no-op for that row,
 * not a clobber.
 */
return new class extends Migration
{
    /**
     * Category B: credential pk => [email_id, mobile_no] to write, taken
     * verbatim from the linked employee_master row. mobile_no is included only
     * where employee_master actually has one (0 is not a real phone number).
     */
    private const CONTACT_BACKFILL = [
        // vikash.krishali, user_id 10042 - employee_master has no mobile on file.
        1441 => ['email_id' => 'REDACTED-EMAIL', 'mobile_no' => null],
        // sumitra, user_id 11038 - employee_master has no mobile on file.
        2120 => ['email_id' => 'Sumitrabhandari190@gmail.com', 'mobile_no' => null],
        // devender.malhotra, user_id 11168.
        2195 => ['email_id' => 'REDACTED-EMAIL', 'mobile_no' => 'REDACTED-MOBILE'],
        // rohitkumar, user_id 11177 - employee_master has no mobile on file.
        2201 => ['email_id' => 'REDACTED-EMAIL', 'mobile_no' => null],
        // praveenk, user_id 11471.
        2389 => ['email_id' => 'REDACTED-EMAIL', 'mobile_no' => 'REDACTED-MOBILE'],
    ];

    /**
     * Category C: credential pk => [from (the wrong, current value), to (the
     * confirmed correct employee_master pk)]. `from` is asserted before
     * writing so this migration never overwrites a value someone already
     * corrected by hand, and `down()` can restore exactly what was here.
     */
    private const USER_ID_CORRECTIONS = [
        // REDACTED-LOGIN: wrongly pointed at REDACTED-LOGIN's true record (10317).
        // Own name 'REDACTED-NAME' matches em.pk=10525 'REDACTED-NAME',
        // whose email (REDACTED-EMAIL) is exactly the credential's own.
        1778 => ['login' => 'REDACTED-LOGIN', 'from' => 10317, 'to' => 10525],
        // REDACTED-LOGIN: wrongly pointed at REDACTED-LOGIN's true record (10525).
        // Own name 'REDACTED-NAME' matches em.pk=11373 'BHAWANA P NAWREKAR
        // PORWAL', whose email (REDACTED-EMAIL) is exactly the
        // credential's own.
        1382 => ['login' => 'REDACTED-LOGIN', 'from' => 10525, 'to' => 11373],
        // REDACTED-LOGIN: wrongly pointed at REDACTED-LOGIN's true record (10559).
        // Own name 'REDACTED-NAME' matches em.pk=10317 'Brijesh Dipakbhai
        // Patel', whose email (REDACTED-EMAIL) is exactly the
        // credential's own.
        1633 => ['login' => 'REDACTED-LOGIN', 'from' => 10559, 'to' => 10317],
        // REDACTED-LOGIN: wrongly pointed at REDACTED-LOGIN's true record (10480).
        // Own name 'REDACTED-NAME' matches em.pk=10559 'REDACTED-NAME', whose email
        // AND mobile (REDACTED-EMAIL / REDACTED-MOBILE) are exactly the
        // credential's own.
        1800 => ['login' => 'REDACTED-LOGIN', 'from' => 10480, 'to' => 10559],
        // REDACTED-LOGIN: wrongly pointed at REDACTED-LOGIN's true record (10679).
        // Own name 'REDACTED-NAME' matches em.pk=10480 'REDACTED-NAME', whose
        // email (REDACTED-EMAIL) is exactly the credential's own.
        1748 => ['login' => 'REDACTED-LOGIN', 'from' => 10679, 'to' => 10480],
        // REDACTED-LOGIN: wrongly pointed at a different, uninvolved employee
        // (REDACTED-NAME, pk 11249). Own name 'REDACTED-NAME' matches
        // em.pk=10679 'REDACTED-NAME', whose email (REDACTED-EMAIL)
        // is exactly the credential's own (mobile differs by one digit -
        // REDACTED-MOBILE vs the credential's REDACTED-MOBILE - a plausible update-drift
        // typo, not treated as a mismatch given the exact email match).
        1871 => ['login' => 'REDACTED-LOGIN', 'from' => 11249, 'to' => 10679],
        // kt231 (pk 1397) is DELIBERATELY NOT LISTED HERE. See the class
        // docblock: its own name does not match this cluster.
    ];

    public function up(): void
    {
        foreach (self::CONTACT_BACKFILL as $credPk => $contact) {
            $current = DB::table('user_credentials')->where('pk', $credPk)->first();

            if (! $current) {
                continue;
            }

            // Idempotent / non-clobbering: only touch the placeholder value
            // this migration was written against. If it no longer matches,
            // someone else already changed it - leave it alone.
            if (! in_array(strtolower(trim((string) $current->email_id)), ['a@a.com'], true)) {
                continue;
            }

            $update = ['email_id' => $contact['email_id'], 'updated_date' => now()];

            if ($contact['mobile_no'] !== null) {
                $update['mobile_no'] = $contact['mobile_no'];
            }

            DB::table('user_credentials')->where('pk', $credPk)->update($update);
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

        foreach (self::CONTACT_BACKFILL as $credPk => $contact) {
            DB::table('user_credentials')
                ->where('pk', $credPk)
                ->where('email_id', $contact['email_id']) // only revert if still our written value
                ->update(['email_id' => 'a@a.com', 'updated_date' => now()]);

            if ($contact['mobile_no'] !== null) {
                DB::table('user_credentials')
                    ->where('pk', $credPk)
                    ->where('mobile_no', $contact['mobile_no'])
                    ->update(['mobile_no' => null]);
            }
        }
    }
};
