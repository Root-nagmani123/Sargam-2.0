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
On testsargam6, 1,547 credentials point at an `employee_master` row (census run
2026-09-17). How many of those name a *different person* depends on how two names
are compared, so the figures below are the ones that are stable under every rule
tried — pairs whose credential name and employee name share **no name token at
all**, which spelling variants, initials and a missing middle name cannot
explain:

| Count | `user_category` | Which credentials |
| --- | --- | --- |
| **317** | blank | no `user_category` recorded on the credential |
| **9** | `E` | `user_credentials` pks 1778, 1800, 1382, 1633, 1748, 2102, 1871, 1397, 2528 |

For reference, and so the two figures above can be told apart from any others in
circulation: the looser rules give **349** pairs under an exact first-plus-last
comparison and **575** under normalised tokens including the employee's
`middle_name`. Both of those are dominated by spelling noise — initials, missing
middle names, case — which is why this note quotes the 317 + 9 figure instead.
Whichever count you quote, quote the rule with it.

Those 326 accounts reach one other person's record, and can save it. Every other
member is refused. Whether those rows are data to repair or a column being read
for a purpose it does not serve is open as PR #309 F-024 — owner **Engineering
lead** with the **DBA** — and the last two checks in §5 below are the ones that
surface it. Note that `user_category` is necessary but **not sufficient** to
identify them: see §5. The narrowing itself is not in question: before this
release every authenticated account could open and rewrite every member record.

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

Rolling back **this release's own migration** — scope it with `--path`, as §3
does — removes the menus row, revokes the permission from every role that holds
it, and deletes the permission row. Any role that had been granted the download
loses it, which is the same state as before the release. That statement is about
this one migration; a bare `migrate:rollback` is a different and unvouched-for
operation, see §2.1 and §3.

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
php artisan migrate --path=database/migrations/2026_09_16_090000_add_member_pii_read_permission.php
php artisan permission:cache-reset
```

Both cache files are regenerated on the host and are now ignored by git, which
is where generated files belong.

The migrate step is **not optional in this release** — it is the first migration
this branch has ever carried, and it is what makes §0.1 possible. It is **scoped
with `--path` deliberately, and it must stay scoped.**

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

### 2.1 Why the migrate step is scoped

`php artisan migrate` with no `--path` does not run "this branch's migration". It
runs every pending migration in `database/migrations/`. On the review database
(testsargam6, `migrate:status` run 2026-09-17) that is **76 pending files**, of
which this release contributes exactly one. Four of the other 75 drop columns
from live tables in their `up()`:

```text
2026_04_28_154500_drop_legacy_columns_from_student_travel_plan_masters
2026_05_02_100000_fc_activity_department_user_use_credentials_pk
2026_06_02_210000_drop_course_string_from_fc_medical_tables
2026_06_10_000002_drop_title_column_from_employee_master
```

The last one is on this release's own table. Its `up()` drops
`employee_master.title`; its `down()` re-adds `title` as an **empty** nullable
column, so rolling it back restores the column definition and none of the data.
On testsargam6 that column is non-empty in **1,665 of 1,833 rows**. An unscoped
`migrate` run on the strength of this note would therefore destroy live data that
has nothing to do with this release, irreversibly.

The `--path` form above was verified with `--pretend`: it reports that one
migration file and nothing else.

**An unscoped `php artisan migrate` on this repository needs DBA sign-off
first** — on the four column drops listed above, and on whatever else is pending
on the target host, which has not been measured anywhere but testsargam6.
Production may be further ahead or further behind. Running the full pending set
is a separate and much larger release decision than the one this release is
making, and it is not this note's to authorise. Do not substitute it for the
scoped command.

## 3. Rollback

```bash
git revert <merge commit>
php artisan migrate:rollback --path=database/migrations/2026_09_16_090000_add_member_pii_read_permission.php
```

The code revert leaves one thing behind: the `member_pii_read` permission row and
its capability menu row, added by this release's migration. Roll that back too —
with the same `--path` scope, as above — or the permission survives with nothing
reading it. See §0.2. No member data is written or rewritten by this release, so
there is nothing else in the database to undo.

The rollback is scoped for the same reason the migrate step is, and **the claim
that rolling back is sound covers this release's own migration and nothing
else.** A bare `php artisan migrate:rollback` rolls back the whole last batch; if
anyone has run an unscoped `migrate` on the host, that batch is the full pending
set, and those `down()` bodies restore column *definitions*, not the rows that
were dropped — see §2.1. Nothing in this note vouches for rolling those back.

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
- **Open your own profile as an account whose `user_category` is blank** — an
  officer trainee, or any account that is not an employee — from the sidebar,
  and confirm the record shown is **yours**. If it is somebody else's, that is
  PR #309 F-024 in the wild: stop and tell the Engineering lead, because the same
  account can also save that record.
- **Then run that same check again as one of these nine accounts, whose
  `user_category` *is* `E`** — `user_credentials` pks **1778, 1800, 1382, 1633,
  1748, 2102, 1871, 1397, 2528** on testsargam6 (re-identify them on the target
  host with the no-shared-token rule in §0 before trusting these pks). Each
  one's `user_id` names a different *named* employee, so each of the nine must
  show somebody else's record. Report it the same way.

  This second check is not a duplicate of the first. **`user_category` is
  necessary but not sufficient.** Narrowing the rule to `user_category = 'E'` —
  the obvious reading of F-024, and the one the first check on its own would
  encourage — closes the 317 blank-category cases and leaves these nine open,
  and the first check *cannot* detect them, because it uses an account whose
  category is not `E`. A fix for F-024 that filters on category alone is not a
  fix; a name- or ownership-based check is required on top of it. Worth telling
  the DBA when you report it: the nine names rotate through the block (ANJALI
  CHAUHAN → REDACTED-NAME → REDACTED-NAME → REDACTED-NAME → REDACTED-NAME), which
  reads as a block of `user_id` values written misaligned rather than as a
  category being conflated.
- Confirm two `member.pii.*` lines in `storage/logs/laravel.log`, each on a
  single line.
