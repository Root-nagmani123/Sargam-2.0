<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Object-level gate for the member edit wizard.
 *
 * WHY THIS EXISTS SEPARATELY FROM EnsureMemberPiiAccess. The first round of this
 * work gated the four endpoints that return a member's personal data as a
 * DOCUMENT - show, print, export and excel-export. It did not gate the three
 * that return the same fields as a FORM:
 *
 *     member/edit/{id}
 *     member/profile/edit/{id}
 *     member/edit-step/{step}/{id}
 *
 * Those take a RAW INTEGER pk, not the encrypted id the gated routes use, so an
 * account holding no relevant role could walk 10001, 10002, ... and collect
 * permanent address, current address, father's name and personal email one
 * member per request - the same egress the export gate refuses, and with no
 * audit line, because logMemberPii() is only called from the gated methods.
 *
 * WHY NOT JUST `member.pii`. Because one of those three routes is
 * self-service: member.profile.edit.self redirects every user to
 * member/profile/edit/<their own employee pk>. Gating the wizard on the PII
 * permission alone would take a user's own profile away from them, which is a
 * different defect, not a fix. So the rule here is object-level:
 *
 *     an entitled account (Super Admin, or the holder of the grantable
 *     member.pii permission) may open ANY member's record;
 *     everybody else may open EXACTLY their own, and nobody else's.
 *
 * The caller's own record is user_credentials.user_id - the same mapping
 * MemberController::store() writes when it creates the credential, and the same
 * one member.profile.edit.self already relies on.
 *
 * THAT MAPPING IS NOT PROVEN, and this block is the place it would be believed,
 * so it says so instead. On testsargam6 (census run 2026-09-17), 1,547
 * credentials have a user_id matching an employee_master.pk. How many of those
 * name a DIFFERENT PERSON depends on how two names are compared, so the figures
 * quoted here are the ones stable under every rule tried - pairs sharing NO NAME
 * TOKEN AT ALL, which spelling variants, initials and a missing middle_name
 * cannot explain:
 *
 *     317  credentials with a BLANK user_category
 *       9  credentials with user_category = 'E'
 *          (user_credentials pks 1778, 1800, 1382, 1633, 1748, 2102, 1871,
 *           1397, 2528)
 *
 * Looser rules give 349 under an exact first-plus-last comparison and 575 under
 * normalised tokens including the employee's middle_name; both are dominated by
 * spelling noise, which is why they are not the numbers to act on. Whichever
 * count is quoted, quote the rule with it.
 *
 * For those accounts "their own record" is somebody else's, for reading here and
 * for writing through MemberController::authorizeMemberRecord().
 *
 * USER_CATEGORY IS NECESSARY BUT NOT SUFFICIENT. The column is not namespaced by
 * account category - credentials with user_category 'S' or blank carry user_id
 * values that fall inside employee_master's pk range, and nothing below checks
 * that the credential is an employee credential - but narrowing the rule to
 * user_category = 'E' does NOT fix this. That closes the 317 blank-category
 * cases and leaves the nine listed above open. Those nine rotate through the
 * block (ANJALI CHAUHAN -> Brijesh Patel -> AZAD SINGH -> ESWARA RAO -> SONALI
 * RAWAT), which reads as a block of user_id values written misaligned rather
 * than as a category being conflated. Any fix needs a name- or ownership-based
 * check on top of the category, and a test whose actor is one of those nine.
 *
 * Whether that is data to repair or a column being read for a purpose it does
 * not serve is a domain question this code cannot settle. It is open as PR #309
 * F-024, owner Engineering lead with the DBA. Until it is answered, read the
 * rule below as "the account whose user_id names this pk", NOT as "this person".
 * What is proven is the narrowing: before this middleware existed, every
 * authenticated account could open and rewrite EVERY member record.
 *
 * The comparison is deliberately string-wise on the ROUTE parameter rather than
 * on a looked-up model: the parameter is what the controller will use, so this
 * refuses before anything is loaded, and a mismatch cannot be laundered through
 * a cast.
 *
 * Both branches are executed by MemberRecordAccessTest - the entitled branch
 * and the own-record branch, each with its refusal.
 */
class EnsureMemberRecordAccess
{
    public function handle(Request $request, Closure $next)
    {
        // Entitled accounts keep the whole module, exactly as before.
        if (EnsureMemberPiiAccess::grantsAccess()) {
            return $next($request);
        }

        $requested = $request->route('id');
        $own = optional(auth()->user())->user_id;

        // `!== null` on both sides, then a string compare: user_id is nullable
        // for credentials that belong to no employee row, and null == null must
        // NOT be read as "this is my record".
        if ($requested !== null && $own !== null && (string) $own === (string) $requested) {
            return $next($request);
        }

        abort(403, 'You do not have access to this member record.');
    }
}
