<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Gate for the OT / LBSNAA directory downloads.
 *
 * ACCESS DECISION — this narrows an existing capability, so it is stated in
 * full rather than left to be inferred from the code:
 *
 *   Before: the directory exports were `?export=csv|excel` on the page routes
 *           themselves, so ANY authenticated user could download either
 *           roster. There was no check beyond `auth`.
 *   After:  a Super Admin may download, and so may the holder of the grantable
 *           `directory.export` permission — see REVERSIBLE WITHOUT A DEPLOY
 *           below, which is the other half of this decision and not an
 *           afterthought. No role holds that permission today. Every other role
 *           keeps the grids — search, sort, paging, the on-screen contact
 *           details — and loses the file.
 *
 *   BUT READ THE PERMISSION BRANCH AS A CONVENIENCE, NOT A BOUNDARY. An earlier
 *           version of this block added "so in practice this reads Super Admin
 *           only until somebody grants it", which describes the contents of a
 *           table as though it described a control. "Somebody" is not restricted
 *           to an administrator: POST roles/permissions/{id} carries `web` and
 *           Authenticate and nothing else, and the controller behind it
 *           firstOrCreate()s whatever permission NAME it is posted and grants it
 *           to the role in the URL, with no check on the caller. So any
 *           authenticated account can hand itself `directory.export` in one
 *           request — executed against a live database and recorded as PR #317
 *           F-007, with the ungated endpoint itself as L-8, owner Security owner
 *           escalating to Engineering lead. That endpoint defeats every
 *           permission-based gate in this application, not only this one, and
 *           fixing it is its own change; it must not ride along in a redesign
 *           branch. Until it is fixed, an access audit of these downloads should
 *           read this gate as: it takes the roster file out of casual reach and
 *           puts an audit line on every one that is served. It does not withstand
 *           a deliberate authenticated actor, and the same rows remain readable
 *           through the ungated feed in any case — see WHAT THIS GATE IS NOT.
 *
 * Why the split: the PAGES are a directory and are meant to be readable by
 * everyone. The EXPORTS are a different exposure — one GET returns the whole
 * staff roster's home address, residence phone, mobile and personal email as
 * a file that leaves the application entirely. This gate removes the convenient
 * download, and that is all it claims to do.
 *
 * WHAT THIS GATE IS NOT. It does not stop bulk extraction, and the sentence it
 * used to carry — "bulk extraction of personal data is the privileged act, not
 * looking someone up" — read as a security property when it is only a design
 * intent. /directory/lbsnaa/data stays open to every authenticated user by
 * design; it is a JSON feed returning address, mobile, residence number and
 * email per row, and `start` has no upper bound, so three scripted requests at
 * length=200 reconstruct the roster this gate refuses as a file — and without
 * the audit line the gated path writes. The change is still an improvement in
 * both directions (the base rendered all 443 rows into the page markup on every
 * load, with no paging at all, and wrote no audit line). But if bulk extraction
 * genuinely needs gating, that is a change to the FEED routes with its own
 * decision record, not something to read into this one.
 *
 * Why a role FIRST: the role check mirrors EnsureIssueReportsAdmin, which gates
 * the equally PII-bearing Reported Issues downloads with the same privilege
 * check, so the two PII download paths stay one rule. The named permission
 * beside it (the EnsureFcRegAdmin shape) is the widening path for a
 * non-Super-Admin office that turns out to need the roster file — it is already
 * wired, so widening never means editing this class.
 *
 * Both branches are executed by DirectoryExportGuardTest: a non-privileged
 * user gets 403 through the real router, a privileged one is passed through.
 *
 * KNOWN WINDOW — isSidebarPrivilegedUser() resolves through hasRole(), which
 * reads the session's user_roles before it asks the role tables. So a Super
 * Admin whose role is revoked mid-session keeps these downloads until they log
 * out. That is every hasRole() caller's behaviour, not this gate's, and it is
 * tracked repository-wide as PR311-L-2; it is recorded here because this gate
 * guards bulk PII, where the window costs more than it does elsewhere.
 *
 * REVERSIBLE WITHOUT A DEPLOY - the narrowing removes a capability every
 * authenticated user had, and the honest risk is that some office was using the
 * roster CSV and nobody knew. If that turns out to be so, the remedy must not be
 * to widen the role check in code, which is what the decision above argues
 * against. So the gate also admits the holder of a named permission: grant
 * EXPORT_PERMISSION to a role and that role has directory downloads back, with
 * no code change and an audit trail in the permission tables.
 *
 * No role holds this permission today, and it does not have to exist for the
 * gate to work: the lookup is wrapped because Spatie raises rather than
 * returning false when a permission name has never been defined.
 *
 * HOW THE GRANT IS ACTUALLY PERFORMED - written down because there is no screen
 * whose purpose is granting this, and a remedy nobody can find during the
 * incident it was written for is not a remedy. The permissions CRUD route is
 * commented out, and the Roles -> Assign permissions matrix offers exactly the
 * names carried by menus rows, so a permission reaches an administrator only by
 * being on a menu. The recommended grant is therefore a database action, by the
 * DBA, on request from the Engineering lead:
 *
 *     INSERT INTO permissions (name, guard_name, created_at, updated_at)
 *     VALUES ('directory.export', 'web', NOW(), NOW());
 *
 *     INSERT INTO role_has_permissions (permission_id, role_id)
 *     VALUES (<the id that insert produced>, <the role's id>);
 *
 *     php artisan permission:cache-reset
 *
 * The cache reset is not optional: Spatie serves the permission collection from
 * the application cache (file driver, 24-hour TTL) and invalidates it only for
 * writes made through its own model, so a hand-written row is invisible - and
 * worse than invisible, because hasPermissionTo() RAISES on a name the cached
 * collection has never seen. That failure has already been observed on this
 * codebase (PR #309 F-025).
 *
 * THERE IS A SECOND ROUTE, AND IT IS NOT RECOMMENDED. An earlier version of the
 * paragraph above said a dotted name "can never appear" on the roles screen,
 * because a menu's permission_name is Str::slug($name, '_'). That is true of
 * MenuService::store(), which overwrites whatever permission_name is posted with
 * the slug. It is NOT true of MenuService::update(), which computes the slug into
 * a local, uses it only to rename the permissions row, and then passes the
 * request data to $menu->update($data) unmodified - so the menu edit form's
 * free-text permission_name field is written through verbatim, dot and all. The
 * roles screen then renders that value as a checkbox and assignPermission()
 * creates the permission and grants it. Executed against a live database inside a
 * rolled-back transaction: store() -> 'zz_review_probe_317', update() with
 * permission_name='directory.export' -> 'directory.export'. Recorded as PR #317
 * F-011, with MenuService::update() itself as L-9.
 *
 * So the screen route exists. Prefer the SQL above anyway, and the reason is not
 * taste: reaching the roles screen this way means hijacking an unrelated menu's
 * permission_name, and that leaves the hijacked menu pointing at a permission
 * name with no permissions row behind it - measured in the same probe. You would
 * be repairing a second menu's gate during the incident you are already in. The
 * SQL touches nothing but the rows it names.
 *
 * If this capability turns out to be wanted often enough to deserve a toggle,
 * the durable shape is: rename the permission to a slug (`directory_export`),
 * because a slug is what the menu screens produce on their own and so is the
 * only shape that survives a later menu edit, and ship a guarded migration that
 * adds BOTH the permissions row and the matching menus
 * capability row, flushing Spatie's cache in up() AND down() for the reason
 * given just above. That is a code change with its own review, not a remedy to
 * reach for mid-incident - which is why the SQL above is here.
 *
 * Deliberately NOT cited here by file name: a migration of that shape is
 * PROPOSED for the member module in PR #309, which is not merged. It is a
 * pattern to follow, not a file to open - do not go looking for it in this
 * tree. An earlier version of this paragraph named it as though it were already
 * here, which is PR #317 F-010.
 */
class EnsureDirectoryExportAccess
{
    /** Grant this to a role to restore directory downloads for it. */
    public const EXPORT_PERMISSION = 'directory.export';

    public function handle(Request $request, Closure $next)
    {
        if (isSidebarPrivilegedUser() || $this->holdsExportPermission()) {
            return $next($request);
        }

        abort(403, 'You do not have access to directory downloads.');
    }

    private function holdsExportPermission(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        try {
            return (bool) $user->can(self::EXPORT_PERMISSION);
        } catch (\Throwable $e) {
            // The permission has not been created yet: not held, not an error.
            return false;
        }
    }
}
