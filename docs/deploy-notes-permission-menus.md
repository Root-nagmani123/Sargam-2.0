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
php artisan migrate     # two migrations
```

Both are guarded in `up()` and `down()`, and the back-fill is idempotent and
scoped. The back-fill sets `is_container = 1` on every menu that has children and
no route or attachment. Measured on `testsargam6` (2026-09-15): **4 rows of 258**,
out of 43 parent menus.

Those rows can only be saved afterwards because the Add/Edit form now renders the
"This menu only holds sub-menus" control. Without it the rule rejects them, which
is what review finding F-002 was.

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
php artisan migrate:rollback --step=2     # both down() bodies are guarded
```

The back-fill only sets a flag the reverted code never reads, so rolling back
loses nothing. Files under `storage/app/public/menu-attachments/` remain and are
harmless.

## 6. Post-deploy checks (first working day)

- Open Roles, Assign Permission, Assign Dashboard, Topbar Categories, Side Menu
  Groups, Menus and Users.
- Run Print / CSV / Excel / PDF once on each with a search term.
- As a non-Super-Admin **without** the `roles` permission, request
  `/roles/export` — expect **403**.
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
