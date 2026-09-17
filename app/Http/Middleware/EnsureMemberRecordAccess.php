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
 * so it says so instead. On testsargam6, of the 1,547 credentials whose user_id
 * matches an employee_master.pk, 332 name a row whose FIRST AND LAST NAME DO NOT
 * MATCH the credential's own - so for those accounts "their own record" is
 * somebody else's, for reading here and for writing through
 * MemberController::authorizeMemberRecord(). The column is not namespaced by
 * account category: credentials with user_category 'S' or blank carry user_id
 * values that fall inside employee_master's pk range, and nothing below checks
 * that the credential is an employee credential.
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
