# Deploy notes — Directory redesign (PR #317)

Applies to the `main_directory` release: the LBSNAA and OT directory grids, their
five-format export layer, the Super-Admin export gate and the download audit line.

No migration. No dependency change. `composer.lock` is byte-identical to `main`.

## 0. What this release changes beyond the redesign

The branch is named for a visual redesign, and three of the things in it are not
visual. They are listed here so the release record carries them, rather than
leaving them to be discovered from the diff:

1. **A capability is withdrawn.** Before this release, `?export=csv|excel` sat on
   the directory *page* routes, so any authenticated user could download either
   roster's home address, mobile, residence phone and personal email. The
   downloads now require a Super Admin, or a role holding the grantable
   `directory.export` permission. Everyone else keeps both grids and loses the
   file. **Reversible without a deploy** — grant `directory.export` to a role.
   If an office turns out to have been relying on the roster CSV, that is the
   remedy; do not widen the role check in code.
2. **Every served download writes an audit line** (`directory.export`) carrying
   the grid, format, actor, IP, filters, row count and capped flag — and no row
   data.
3. **Two tracked files stop being tracked** (`bootstrap/cache/packages.php`,
   `services.php`), which is why §1 below is a mandatory manual step on every
   host and why the rollback in §3 has a step of its own.

### 0.1 How the grant in (1) is actually performed

"Grant `directory.export` to a role" is a real remedy, but **no screen exists
whose purpose is granting it**, and that is worth knowing before the incident
rather than during it. The permissions CRUD route is commented out, and the
Roles → Assign permissions matrix offers exactly the permission names that
`menus` rows carry — so a permission reaches an administrator only by being on a
menu. The recommended grant is a database action, performed by the **DBA** at the
request of the **Engineering lead**:

```sql
INSERT INTO permissions (name, guard_name, created_at, updated_at)
VALUES ('directory.export', 'web', NOW(), NOW());

INSERT INTO role_has_permissions (permission_id, role_id)
VALUES (<the id that insert produced>, <the role's id>);
```

```bash
php artisan permission:cache-reset
```

The cache reset is **not optional**. Spatie serves the permission collection
from the application cache — the file driver here, with a 24-hour TTL — and
invalidates it only for writes made through its own model. A hand-written row is
invisible to it, and worse than invisible: the permission check raises on a name
the cached collection has never seen. That has already bitten this codebase once
(PR #309 F-025).

**A second route exists, and it is not the recommended one.** An earlier version
of this section said `directory.export` "never appears" on the roles screen,
because a menu's `permission_name` is `Str::slug($name, '_')`. That holds for
`MenuService::store()`, which overwrites the posted `permission_name` with the
slug. It does **not** hold for `MenuService::update()`, which computes the slug
into a local, uses it only to rename the `permissions` row, and then saves the
request data unmodified — so the menu edit form's free-text `permission_name`
field is written through verbatim, dot and all. The roles screen then offers that
value as a checkbox, and ticking it creates the permission and grants it.
Executed against a live database inside a rolled-back transaction, this is what
the two methods do with the same posted value:

```
store()   permission_name posted 'ignored_by_store'  → stored 'zz_review_probe_317'
update()  permission_name posted 'directory.export'  → stored 'directory.export'
```

Prefer the SQL above anyway. Using the screen means hijacking an unrelated menu's
`permission_name`, which leaves that menu pointing at a permission name with no
`permissions` row behind it — measured in the same probe. You would be repairing
a second menu's gate in the middle of the incident you are already handling. The
SQL touches nothing but the rows it names. Recorded as PR #317 **F-011**, with
`MenuService::update()` itself as **L-9**, owner **Engineering lead** — a separate
change, not part of this release.

If the capability turns out to be wanted often, the durable fix is a slug-shaped
name (`directory_export`) plus a guarded migration that ships both the
permissions row and a `menus` capability row, flushing Spatie's cache in `up()`
and `down()`. That is a code change with its own review — not a step to improvise
mid-incident.

A migration of that shape is **proposed** for the member module in PR #309, which
is **not merged**. Treat it as a pattern to follow, not a file to copy from this
tree — it is not in this release.

### 0.2 What the permission is not

It is not a boundary, and the release record should not be read as claiming one.
`POST roles/permissions/{id}` carries `auth` and nothing else, and the controller
behind it creates whatever permission name it is posted and grants it to the role
in the URL without checking the caller — so any authenticated account can hand
itself `directory.export` in a single request. That endpoint is **pre-existing
and untouched by this release**; it defeats every permission-based gate in the
application and is tracked as its own change (PR #317 F-007 / L-8), owner
**Security owner**, escalating to the **Engineering lead**.

What this release does achieve is still worth having, and is what the post-deploy
checks verify: the roster file is out of casual reach, and every download that is
served writes an audit line naming the actor, the IP, the filters and the row
count. The same fields remain readable by any authenticated user through the
ungated grid feed — that was true before this release too, and narrowing it is a
change to the feed routes with its own decision record.

## 1. Before pulling, on every host

This release **untracks** `bootstrap/cache/packages.php` and
`bootstrap/cache/services.php` and tracks `bootstrap/cache/.gitignore` in their
place, so the directory still exists on a clean checkout while its contents stop
being repository content.

Any host that has run `composer install` or `php artisan package:discover` since
its last pull has a locally modified *tracked* `packages.php`. Git refuses to
move to a commit that deletes a modified tracked file:

```
error: Your local changes to the following files would be overwritten by checkout:
        bootstrap/cache/packages.php
Please commit your changes or stash them before you switch branches.
Aborting
```

Run this first — the files are regenerated output, so discarding them costs
nothing:

```bash
git checkout -- bootstrap/cache/packages.php bootstrap/cache/services.php
```

Verified on a throwaway checkout: without the step the pull aborts; with it the
pull succeeds and `bootstrap/cache/` then holds only `.gitignore`.

## 2. Release

```bash
git pull
composer install --no-dev --optimize-autoloader
php artisan package:discover      # now mandatory: the manifests are no longer in the repository
php artisan config:cache && php artisan route:cache
```

`php artisan --version` must print a version before the release proceeds. On a
clean checkout of this head it does (Laravel 9.52.22, exit 0) — that is the whole
point of the tracked keeper.

## 3. Rollback

Code revert of the merge commit. Nothing is written to the database by this
change, so there is no data to undo.

Reverting re-tracks the two manifests, which means a host that has regenerated
them meets the same refusal in the other direction — run the same
`git checkout -- bootstrap/cache/*.php` before the revert checkout.

## 4. Post-deploy checks (first working day)

1. `php artisan --version` on every host.
2. As a **non**-Super-Admin: both directory grids open; every export link → 403.
3. As Super Admin: all five formats on both grids, once with a filter and once
   with a search term.
4. In the `.xlsx`, check a residence number that begins with `0` and an office
   extension: both must read exactly as stored, left-aligned, with no leading
   apostrophe and no lost digit.
5. Pick a section from the dropdown, then edit the URL to a section pk that is
   not offered (`?section=999999`). The grid must come back **empty** — never
   the full directory — and the export band must name the section it filtered on.
6. `storage/logs/laravel.log` carries exactly one `directory.export` line per
   download, with no row data, and a search term containing a line break appears
   escaped on that one line rather than starting a new record.
