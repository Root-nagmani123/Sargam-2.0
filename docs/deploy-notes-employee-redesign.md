# Deploy notes — Employee / Member redesign (`main_employee`)

**One migration** (`2026_09_16_090000_add_member_pii_read_permission`), guarded,
idempotent and reversible — see §0.1. Two other steps matter, and the first one
is not optional on a host that has run `composer install` on `main`.

---

## 0. A capability is withdrawn in this release

The branch is named for a redesign. This is the part of it that is not visual,
and the part a release manager needs to know about.

**Before:** every route under `/member` was protected by `auth` and nothing
else. Any authenticated account could download the whole member roster in four
formats, take the legacy full-details dump, open any member's profile, print any
member's profile sheet, and open any member's edit wizard by walking raw integer
pks — collecting date of birth, both addresses, father's name, personal email and
mobile one member per request.

**After:**

| Endpoint | Who can reach it now |
| --- | --- |
| `member/export/{csv,excel,pdf,print}` | Super Admin, or a role granted `member_pii_read` |
| `member/excel-export` (full details) | Super Admin, or a role granted `member_pii_read` |
| `member/show/{id}`, `member/print/{id}` | Super Admin, or a role granted `member_pii_read` |
| `member/edit/{id}`, `member/profile/edit/{id}`, `member/edit-step/{step}/{id}` | as above, **plus** any user for their own record |
| `POST member/update` | as above, **plus** any user for their own record |
| The member listing itself | unchanged — every authenticated user |

Self-service is deliberately preserved: `member/profile/edit` still redirects a
user to their own record and still works for everybody.

One qualification on the two "plus any user for their own record" rows, because
the table would otherwise be read as stronger than it is. "Their own record" is
`user_credentials.user_id`, and that column is not namespaced by account type.
On testsargam6, 332 of the 1,547 credentials that point at an `employee_master`
row name a row whose name does not match the credential's own — so those accounts
reach one other person's record, and can save it. Every other member is refused.
Whether those 332 rows are data to repair or a column being read for a purpose it
does not serve is open as PR #309 F-024 — owner **Engineering lead** with the
**DBA** — and the last two checks in §5 below are the ones that surface it. The
narrowing itself is not in question: before this release every authenticated
account could open and rewrite every member record.

### 0.1 Restoring access to a role, without a deploy

The narrowing is reversible from the application, and the migration in this
release is what makes that true. It creates the `member_pii_read` permission and
a **capability row** under Employee on the role-assignment screen, so:

> Roles → *(role)* → Assign permissions → **Employee → Employee Downloads** → on.

That row carries `exclude_from_admin = 1`, so no administrator's sidebar changes.
Granting the permission is recorded in the permission tables like any other
grant. **Do not widen the role check in code** — that is the decision this gate
exists to record.

If an office reports losing the roster download after this release, that grant is
the remedy. Owner: **Engineering lead**, with the Release / deploy owner once
that row in `owners.md` is filled.

**Do not read this permission as a boundary.** Granting it is not restricted to
an administrator: `POST roles/permissions/{id}` carries `auth` and nothing else,
and the controller behind it creates whatever permission name it is posted and
grants it to the role in the URL, with no check on the caller. Any authenticated
account can therefore hand itself `member_pii_read` in one request. That endpoint
is pre-existing, is not touched by this release, and defeats every
permission-based gate in the application — it is tracked as its own change
(PR #317 L-8 / PR #309 F-027), owner Engineering lead. What this release does
achieve is real and worth stating plainly: the member roster and the per-member
profile sheets are out of casual reach, and every served download now writes an
audit line naming the actor, the IP and the row count.

### 0.2 Rolling the migration back

`php artisan migrate:rollback` on this batch removes the menus row, revokes the
permission from every role that holds it, and deletes the permission row. Any
role that had been granted the download loses it, which is the same state as
before the release.

---

## 1. Before pulling: release the tracked bootstrap cache files

This release stops tracking `bootstrap/cache/packages.php` and
`bootstrap/cache/services.php`, and adds a tracked `bootstrap/cache/.gitignore`
keeper so a clean checkout still has the directory Laravel requires at boot.

Git tracks files, not directories, so **without that keeper a fresh clone has no
`bootstrap/cache` directory at all** and the application refuses to boot with:

```
The .../bootstrap/cache directory must be present and writable.
```

And any host that already ran `package:discover` on `main` has a locally modified
tracked file, which makes git refuse the checkout outright:

```
error: Your local changes to the following files would be overwritten by checkout:
        bootstrap/cache/packages.php
Aborting
```

On every host, before pulling:

```bash
git checkout -- bootstrap/cache/packages.php bootstrap/cache/services.php
```

## 2. Install, rebuild the manifests, migrate

```bash
composer install
php artisan package:discover
php artisan migrate
php artisan permission:cache-reset
```

Both cache files are regenerated on the host and are now ignored by git, which
is where generated files belong.

`php artisan migrate` is **not optional in this release** — it is the first
migration this branch has ever carried, and it is what makes §0.1 possible.

`php artisan permission:cache-reset` is belt and braces. Spatie keeps the
permission collection in the application cache (the file driver here, 24-hour
TTL) and invalidates it only for writes made through its own model; the
migration writes with the query builder and therefore flushes the cache itself,
in both directions. If that flush cannot reach the cache — an unreachable
backend at deploy time — it is swallowed rather than failing a migration whose
database work has already committed, and this command is the manual equivalent.
Without either, the **Assign permissions** toggle in §0.1 answers with a 500
until the entry expires: `firstOrCreate()` finds the row the migration wrote, so
it creates nothing and flushes nothing, and `hasPermissionTo()` then raises on a
name the cached collection has never seen.

## 3. Rollback

```bash
git revert <merge commit>
```

The code revert leaves one thing behind: the `member_pii_read` permission row and
its capability menu row, added by this release's migration. Roll that back too —
`php artisan migrate:rollback` on this batch — or the permission survives with
nothing reading it. See §0.2. No member data is written or rewritten by this
release, so there is nothing else in the database to undo.

Reverting re-tracks the two cache files, so hosts that have regenerated them need
the same `git checkout -- bootstrap/cache` step again first.

## 4. Merge order with the Faculty release

Both releases touch `UserController::toggleStatus()` and its allow-list. They now
carry the **same** shape — one row per table with `id_column` and `columns` — so
whichever merges second should keep that shape and simply take the union of the
rows. Do not reintroduce a hard-coded key column: `venue_master` is keyed by
`venue_id` and has no `pk` column, so a hard-coded `pk` makes that toggle a dead
button (it raises "Unknown column 'pk'" and the switch reverts).

## 5. Post-deploy checks (first working day)

- `php artisan --version` on every host **before** the release is announced.
- Open Members and each of the five master screens.
- On Members run CSV, Excel, PDF and Print with a filter and a search term; all
  four must name the same filter on the sheet and show the same rows.
- Open one member's Print sheet; edit a character of the URL and confirm it is a
  **404 page**, not a 500.
- In the Excel download, check an employee id and a mobile number: no leading
  apostrophe, and a long id must show every digit.
- Toggle one row on a master screen **and one on Venue Master** — the venue
  switch is the one that used to be keyed wrongly.
- Add and edit one Employee Group.
- **Grant the download to a role and confirm it succeeds.** Roles → *(any
  role)* → Assign permissions → **Employee → Employee Downloads** → on. It must
  return success, not a 500 page. A 500 here means `php artisan migrate` ran
  without the permission cache being flushed — run
  `php artisan permission:cache-reset` and try again, and report it, because the
  migration is supposed to do that itself.
- **Open your own profile as an account that is not an employee** — an officer
  trainee, or any account whose `user_category` is not `E` — from the sidebar,
  and confirm the record shown is **yours**. If it is somebody else's, that is
  PR #309 F-024 in the wild: stop and tell the Engineering lead, because the same
  account can also save that record.
- Confirm two `member.pii.*` lines in `storage/logs/laravel.log`, each on a
  single line.
