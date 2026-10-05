# Deploy notes — Permissions & sidebar menus redesign (`main_permission`)

Two migrations, one new middleware alias, and one one-time step on hosts that
have run `composer install` before.

---

## What this release actually contains

The PR title says "Permission and menus new design". It also carries, none of
which the title implies:

- a new **authorisation middleware**, `menu.permission:<perm>`, and six export
  routes gated on it;
- **two schema migrations** on `menus` — `attachment` and `is_container` — plus a
  back-fill that flags every existing parent with no destination as a container;
- a **new validation rule**: a menu must have a Url or an Attachment unless it is
  a container;
- **menu attachments** (upload, storage, sidebar link, grid link);
- an **upload-hardening sweep** over 40 call sites in five controllers
  (`safe_upload_extension()` replacing `getClientOriginalExtension()`), touching
  the ID-card, course-repository and memo modules;
- the **Users export** rewritten around one column list, with a 750-row PDF cap;
- **breadcrumb / SidebarNavResolver** behaviour changes;
- `bootstrap/cache/*.php` moved from tracked to generated (see
  `docs/bootstrap-cache.md`).

## 1. Before pulling: release the tracked bootstrap cache files

This release untracks `bootstrap/cache/packages.php` and `services.php` and ships
a tracked `bootstrap/cache/.gitignore` keeper instead. A host that has run
`package:discover` on an older commit has a locally modified tracked file, and
git will refuse the checkout:

```bash
git checkout -- bootstrap/cache/packages.php bootstrap/cache/services.php
```

## 2. Install and rebuild the manifests

```bash
composer install
php artisan package:discover
```

`package:discover` is required, not optional — see `docs/bootstrap-cache.md`.

## 3. Migrations

```bash
php artisan migrate     # four migrations
```

Two alter `menus` (`attachment`, `is_container`). Two are **data** migrations that
grant permissions — see section 3a. They are not optional: skipping `migrate`
leaves real accounts locked out of screens the sidebar still offers them.

The two schema migrations are guarded in `up()` and `down()`, and the back-fill is
idempotent and scoped. The back-fill sets `is_container = 1` on every menu that has
children and no route or attachment. Measured on `testsargam6` (2026-09-15):
**4 rows of 258**, out of 43 parent menus.

Those rows can only be saved afterwards because the Add/Edit form now renders the
"This menu only holds sub-menus" control. Without it the rule rejects them, which
is what review finding F-002 was.

## 3a. This release NARROWS who can reach User Management and Roles

This is the largest behavioural change in the release and the PR title does not
imply it. Read it before deploying.

Before this release, `admin/users/*` and the Roles screen carried `web,auth` only:
**every authenticated account could reach them**, and `admin/users/assign-role-save`
would hand any caller the Super Admin role. That was review finding F-015, and it is
why the gates exist.

After this release every `admin.users.*` route carries `menu.permission:users` and
every Roles route carries `menu.permission:roles`. A route now admits Super Admin,
or a holder of that permission, and nobody else.

The catch is that the sidebar advertises those screens on **role** names while the
routes gate on **permissions**, and the two disagree.
`resources/views/components/menu/setup_activities.blade.php` shows the block to
`Admin`, `Super Admin`, `Training-Induction`, `Training-MCTP` and `IST`. On
`testsargam6` only two of those five roles exist, and only Super Admin held `users`
— so **10 Training-Induction accounts would have seen the links and got 403**. That
was finding F-017.

`2026_09_17_000001_grant_user_management_to_training_induction.php` closes it by
granting `users` and `roles` to Training-Induction, restoring the access that role
had before the gate. It logs what it did, is safe to re-run, and `down()` revokes.

**If `Admin`, `Training-MCTP` or `IST` are ever created as roles, they will hit the
same 403.** Grant them `users` (and `roles` if they need the Roles screen) at the
point of creation, or remove them from `$showUserManagement`. Nothing detects this
automatically.

### Granting `users` is an administration right, not a view

`users` admits the holder to `admin.users.assign-role-save`, which writes roles to
users. This release adds a guard in `UserController::assignRoleSave()`: a caller who
is not Super Admin may not grant **or** revoke the Super Admin role, in either
direction. Without it, granting `users` to a role would have let those accounts make
themselves Super Admin in one request — confirmed by an executed probe — which
bypasses every `menu.permission` gate, since `EnsureMenuPermission` admits
`isSidebarPrivilegedUser()` before it reads any permission. Pinned by
`tests/Feature/RoleAssignmentEscalationTest.php`.

`roles` still lets its holder grant any **existing** permission to any role via
`assign.roles.permissions`. Inventing a new permission name is refused, but every
permission the application uses already exists. That is a deliberate, instructed
widening to 10 accounts, and it stops short of Super Admin because of the guard
above. Narrow it by dropping `roles` from that migration's `PERMISSIONS` list.

### `2026_09_17_000002_create_bank_detail_report_permission.php`

Unrelated to the above and not a PR #311 finding. Five `admin/reports/bank-report*`
routes carry `can:bank_detail_report` and no such permission row existed. `can:` has
no Super Admin bypass in this application, so those five routes returned 403 to
**every** account while the sidebar advertised the screen. The migration creates the
row via the Spatie model so the permission cache is flushed.

## 4. Merge order with the Faculty and Employee releases

All three releases now ship the **same** `bootstrap/cache/.gitignore` keeper
(byte-identical) and the same `App\Support\Concerns\BindsExportCellsAsText`, so
those merge without a conflict in any order.

Two things still need a decision when the second and third merge:

- **`app/Http/Kernel.php`** — this release adds the `menu.permission` alias and
  `main` adds `memo.notice.manager` at the same line. Already merged in here and
  resolved keeping BOTH. Dropping either breaks every route that names it.
- **`app/Exports/BrandedGridExport.php`** — this release and the Employee release
  both add a class at that path, with different constructors. They are not
  interchangeable; the later PR must adopt one and update its own call sites.
  **Engineering lead's call** — this is the one item here that a reviewer cannot
  settle.

## 5. Rollback

```bash
git revert <merge commit>
php artisan migrate:rollback --step=4     # every down() body is guarded
```

Rolling back the two data migrations revokes `users`/`roles` from
Training-Induction and deletes the `bank_detail_report` permission row. Do that
only together with the code revert: without the gates, the revoked permissions are
not needed; with the gates and without the grant, 10 accounts are locked out.

The back-fill only sets a flag the reverted code never reads, so rolling back
loses nothing. Files under `storage/app/public/menu-attachments/` remain and are
harmless.

## 6. Post-deploy checks (first working day)

- Open Roles, Assign Permission, Assign Dashboard, Topbar Categories, Side Menu
  Groups, Menus and Users.
- Run Print / CSV / Excel / PDF once on each with a search term.
- As a non-Super-Admin **without** the `roles` permission, request
  `/roles/export` — expect **403**.
- As a **Training-Induction** account, open User Permissions and Roles from the
  sidebar — both must return **200**. A 403 here means `migrate` did not run, and
  is the single check that tells you the F-017 grant was actually applied.
- As that same Training-Induction account, open Assign Role for any user, tick
  **Super Admin** and save — expect **403** and no change to that user's roles.
  Then assign an ordinary role to confirm normal administration still works.
- As a Super Admin, assign Super Admin to a test account — this must still work.
- Open `/admin/reports/bank-report` as Super Admin — expect **200**, not 403.
- As Super Admin, **create a parent-only menu** and **edit an existing
  container** — both must save (this is F-002).
- Upload a PDF attachment to a menu and open it from the sidebar. Remember the
  file is public to anyone with the link — see the accepted-risk note in
  `MenuController::storeAttachment()`.
- Toggle one menu group and read the toast: it must say **Activated** when you
  have just switched it on.
- Confirm the breadcrumb on `/roles` shows the full trail.
- Give a category a name containing `<b>bold</b>` and confirm the Menus and
  Side Menu Groups grids show the angle brackets as text, not as formatting.
