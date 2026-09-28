<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
 * Looser rules give 349 under an exact first-plus-last comparison that is
 * case-insensitive and trimmed - the other readings of "exact" give 356, 364 or
 * 371, so the rule is not optional - and 575 under normalised tokens including
 * the employee's middle_name. Both are dominated by spelling noise, which is
 * why they are not the numbers to act on. Whichever count is quoted, quote the
 * rule with it.
 *
 * For those accounts "their own record" is somebody else's, for reading here and
 * for writing through MemberController::authorizeMemberRecord().
 *
 * USER_CATEGORY IS NECESSARY BUT NOT SUFFICIENT. The column is not namespaced by
 * account category - credentials with user_category 'S' or blank carry user_id
 * values that fall inside employee_master's pk range, and nothing below checks
 * that the credential is an employee credential - but narrowing the rule to
 * user_category = 'E' does NOT fix this. That closes the 317 blank-category
 * cases and leaves the nine listed above open. Six of those nine land on an
 * employee_master row whose name belongs to ANOTHER credential in the same set,
 * and the set closes on itself - which reads as a block of user_id values
 * written misaligned rather than as a category being conflated. The other three
 * match nothing inside the set. Deliberately stated by pk and never by name:
 * this is a public repository - see PR #309 F-031. Any fix needs a name- or
 * ownership-based check on top of the category, and a test whose actor is one
 * of those nine.
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

        if (self::ownsMemberRecord($request->route('id'))) {
            return $next($request);
        }

        abort(403, 'You do not have access to this member record.');
    }

    /**
     * Does the authenticated credential DEMONSTRABLY own this employee row?
     *
     * This is the F-024 fix, and it is a narrowing, so the reasoning is written
     * down rather than left in a commit message.
     *
     * The old rule was `user_credentials.user_id === <the requested pk>` and
     * nothing else. The census above is why that is not ownership: 326
     * blank-category and 1 'S' credential resolve to an employee row, and for
     * ALL 327 of them the credential's own contact details match that row in no
     * respect whatsoever - 311 of them do not even share a name token with it.
     * Those accounts were not reaching "their own record"; they were reaching a
     * stranger's, with edit rights.
     *
     * So the rule now needs two things, and both are exact - no fuzzy name
     * comparison is used anywhere in this decision, deliberately. A name-based
     * test would be a rule an attacker can satisfy by editing their own
     * credential's name, which is a worse defect than the one being fixed:
     *
     *   1. user_category = 'E'. The column is not namespaced, so 'S' and blank
     *      credentials carry user_id values inside employee_master's pk range.
     *      Necessary, and on its own NOT sufficient - it leaves the nine
     *      E-category rows named above.
     *   2. A CONTACT PROOF: the credential's own email_id equals the employee
     *      row's email or officalemail, or its mobile_no equals the row's
     *      mobile. Both compared lower-cased and trimmed; mobile only when it
     *      is at least ten digits, so two blank or truncated numbers cannot
     *      match each other.
     *
     * MEASURED ON testsargam6 (census 2026-09-18, 1,547 credentials resolving
     * to an employee row):
     *
     *   admitted by the new rule      1,188   (E-category with a contact proof)
     *   refused, and rightly          327     (326 blank + 1 'S' - not one of
     *                                          them has a proof of any kind)
     *   refused, E-category           32      (8 of these are among the nine
     *                                          known-misaligned rows; the other
     *                                          24 look like their own record but
     *                                          cannot prove it from contact data)
     *
     * THE COST IS REAL AND IT IS THE 32. Those accounts lose self-service on
     * their own profile and get a 403 instead. That is the deliberate trade:
     * 359 accounts stop reaching a record they cannot prove is theirs, and 32
     * people are inconvenienced until their contact details are corrected -
     * which is a data repair with an obvious remedy, not a code change. The
     * alternative, leaving the gate as it was, keeps 327 accounts reading and
     * writing somebody else's personal record. Whether the underlying user_id
     * misalignment is repaired in data remains open with the DBA (F-024/F-029);
     * this makes the gate safe while that is decided, rather than waiting.
     *
     * One query, and it is the join that carries the decision - the caller's
     * pk, the requested pk and the proof are all resolved in the database
     * rather than compared in PHP against values a request could influence.
     */
    public static function ownsMemberRecord($requestedPk): bool
    {
        $user = auth()->user();

        // `!== null` on both sides: user_id is nullable for credentials that
        // belong to no employee row, and null == null must NOT be read as
        // "this is my record".
        if (! $user || $requestedPk === null || $user->user_id === null) {
            return false;
        }

        if ((string) $user->user_id !== (string) $requestedPk) {
            return false;
        }

        // Anything that is not an employee credential is refused here, whatever
        // its user_id happens to point at.
        if (strtoupper(trim((string) ($user->user_category ?? ''))) !== 'E') {
            return false;
        }

        // Every cross-table string comparison below carries an explicit
        // COLLATE. user_credentials and employee_master do not share a
        // collation on this server (utf8mb4_0900_ai_ci vs utf8mb4_unicode_ci),
        // and MySQL raises "Illegal mix of collations" rather than returning
        // false - which, in an authorisation check, is a 500 where a decision
        // was wanted. Pinning both sides makes the comparison deterministic and
        // portable across the two hosts' defaults.
        //
        // The collation is written out in full in each string rather than held
        // in a local and interpolated. It is a constant either way, but a raw
        // SQL fragment assembled from a PHP variable matches SAST-01 (CWE-89) -
        // a High-severity injection rule - and this is an authorisation path,
        // so the hit would have to be re-triaged as a false positive on every
        // review. There is no binding here and no request input anywhere in
        // this query; writing the literals out keeps that obvious to a reader
        // and to the scanner.
        // Memoised for the life of the request. The three guards above are free,
        // but this join is not, and the decision is now asked for more than once
        // per request: the header renders an "Edit Profile" item on EVERY admin
        // page (admin/layouts/master includes header_new), the member grid asks
        // once per feed, and authorizeMemberRecord() asks again inside the
        // wizard methods. Same actor, same pk, same answer - so ask the database
        // once. PR #309 F-047.
        //
        // The memo lives in the container rather than in a static because the
        // container IS rebuilt per test - Foundation\Testing\TestCase::setUp()
        // calls refreshApplication() - so nothing leaks from one test method into
        // the next the way a static would.
        //
        // IT IS NOT REBUILT PER REQUEST EVERYWHERE, and the difference is the
        // whole reason this paragraph is long. Under php-fpm the container dies
        // with the request, so the memo dies with it; laravel/octane is not
        // installed, and if it ever is, this needs revisiting. Inside the TEST
        // harness it does not die: MakesHttpRequests::call() resolves the kernel
        // from $this->app and handles the request against it, and
        // refreshApplication() runs only from setUp() - so two $this->get() calls
        // in ONE test method share this memo.
        //
        // What that costs a future test: change the contact data behind an
        // ownership verdict between two requests and the second request reads the
        // FIRST verdict, so the test passes while asserting the opposite of what
        // the code does - green, on an authorisation path. If you write that
        // test, drop the entry between the requests with
        // app()->forgetInstance(<the key built on the next line>). PR #309 F-048.
        $memo = 'member.ownership.'.$user->pk.':'.$requestedPk;

        if (app()->bound($memo)) {
            return app()->make($memo);
        }

        return app()->instance($memo, DB::table('user_credentials as uc')
            ->join('employee_master as em', 'em.pk', '=', 'uc.user_id')
            ->where('uc.pk', $user->pk)
            ->where(function ($q) {
                $q->where(function ($e) {
                    $e->whereRaw("TRIM(uc.email_id) COLLATE utf8mb4_unicode_ci <> '' COLLATE utf8mb4_unicode_ci")
                        ->where(function ($m) {
                            $m->whereRaw('LOWER(TRIM(uc.email_id)) COLLATE utf8mb4_unicode_ci = LOWER(TRIM(em.email)) COLLATE utf8mb4_unicode_ci')
                                ->orWhereRaw('LOWER(TRIM(uc.email_id)) COLLATE utf8mb4_unicode_ci = LOWER(TRIM(em.officalemail)) COLLATE utf8mb4_unicode_ci');
                        });
                })->orWhere(function ($m) {
                    // Ten digits minimum: two blank or truncated numbers must
                    // not be able to match each other.
                    $m->whereRaw('CHAR_LENGTH(TRIM(uc.mobile_no)) >= 10')
                        ->whereRaw('TRIM(uc.mobile_no) COLLATE utf8mb4_unicode_ci = TRIM(em.mobile) COLLATE utf8mb4_unicode_ci');
                });
            })
            ->exists());
    }

    /**
     * The one member pk this actor may edit, or null - resolved ONCE per request.
     *
     * This exists because a screen that offers a control the route refuses is
     * the defect this module keeps re-introducing, and the F-024 narrowing
     * re-introduced it: the rule moved from `user_id === pk` to `user_id === pk
     * AND user_category = 'E' AND a contact proof`, but the member grid, the
     * grid's cache key, that grid's regression test and the deploy note were all
     * left restating the OLD rule. 359 of the 1,547 credentials that resolve to
     * an employee row were offered an Edit link answering 403. PR #309 F-046.
     *
     * The fix is not "copy the new rule into the grid too" - that is what
     * produced four copies of the old one. Every reader now calls THIS, and
     * this calls ownsMemberRecord(), so there is one decision with one
     * implementation and nothing left to drift.
     *
     * ONE QUERY, NOT ONE PER ROW. ownsMemberRecord() refuses immediately unless
     * the requested pk equals the caller's own user_id, so at most one row in
     * any listing can be owned and it is knowable before the page is fetched.
     * Resolving it per row would have been a query per row; resolving it here
     * is a single memoised call whichever way the answer goes.
     */
    public static function ownedMemberPk(): ?string
    {
        $own = optional(auth()->user())->user_id;

        if ($own === null || ! self::ownsMemberRecord($own)) {
            return null;
        }

        return (string) $own;
    }
}
