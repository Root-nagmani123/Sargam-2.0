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

There is a second and wider bypass, recorded here because this section is where a
reader learns what bounds the download, and leaving it out would make that bound
look tighter than it is. `GET /feedback/student-feedback-url` carries the `web`
middleware group and nothing else — read from `php artisan route:list`, not from
the routes file by eye — and `CalendarController::studentFeedback_url()` takes a
`username` query parameter, looks it up with `User::where('user_name', ...)` and
calls `Auth::login()` on whatever row comes back, with no check on the caller and
no restriction on which account may be assumed. So the roster download does not
require an authenticated actor at all: it requires a `user_name`. That endpoint is
**pre-existing and untouched by this release** — it is present at the merge base
`bacac0bd3`, this release's only additions to `routes/web.php` are lines 209 and
211–224, and `CalendarController` is not among the 22 changed files. It is **not
fixed**, it is not scheduled here, and it is raised in the PR #317 fix record of
2026-09-21 for the review register to carry an id. Read it as the outer bound on
everything this section says: the permission self-grant above is the narrower of
the two paths to the same file.

What this release does achieve is still worth having, and is what the post-deploy
checks verify: the roster file is out of casual reach, and every download that is
served writes an audit line naming the actor, the IP, the filters and the row
count. The same fields remain readable by any authenticated user through the
ungated grid feed — that was true before this release too, and narrowing it is a
change to the feed routes with its own decision record.

### 0.3 Release sign-off

Recorded 2026-09-18. The review held this release at *Approve with conditions* on two
owners who did not exist; both rows are now filled.

The security paragraph at the end of this section was **rewritten on 2026-09-21** because
the version signed off on 2026-09-18 told its reader the section 0.2 limitation was already
remedied, and it is not (PR #317 F-013). Section 0.2 was widened on the same date to record a
second, unauthenticated path to the same download. The owners and the pre-pull acceptance
below are unchanged.

**Sign-off re-taken 2026-09-23** by the Release / deploy owner (Ravi Patel), in session,
against this document as committed on that date — the commit whose parent is `2f61777f2`,
the merge into main. It therefore covers section 0.4 (the merge resolution and its
ratification), sections 1, 2 and 3 as they stand after that merge, and both bypass paths in
section 0.2. What was accepted is unchanged in substance from the 2026-09-21 entry below: the
export gate does **not** withstand an authenticated account which self-grants
`directory.export`, nor an unauthenticated actor who knows a `user_name`; both are
pre-existing and neither is closed by this release. It supersedes the 2026-09-21 entry,
which is retained below as history.

**Sign-off re-taken 2026-09-21** by the Release / deploy owner (Ravi Patel), in session,
against this text as it stands at commit `44a188dc5`'s successor — that is, including both
bypass paths in section 0.2 and the two risk paragraphs below. What was accepted, stated so
that a later reader does not have to infer it: this release ships an export gate that keeps
the roster file out of casual reach and writes an audit line for every download served, and
that does **not** withstand an authenticated account which self-grants `directory.export`,
nor an unauthenticated actor who knows a `user_name`. Both paths are pre-existing, neither is
closed by this release, and the decision was to ship on that basis rather than hold. The
earlier 2026-09-18 acceptance is superseded by this one and is retained above as history.

| Role | Person | What they own here |
| --- | --- | --- |
| Release / deploy owner | Ravi Patel | Section 1 below on every host, the section 3 rollback, and the section 4 checks |
| Security owner | Ravi Patel | The `directory.export` grant decision, and the dependency advisories in `docs/security-advisories.md` |

The section 1 pre-pull step is **approved and owned**, not executed: approval names who
is accountable for running it, it does not run it. It is still a manual step on every
host and the release still aborts half-applied if it is skipped.

**Section 0.2 is accurate for this release, and stays accurate after it merges.** Sign
this release off on that basis. At the head being signed off, `POST roles/permissions/{id}`
is still reachable with `auth` and nothing else, so any account that can log in can hand
itself `directory.export` in a single request and then download either roster. The section 4
check that a non-Super-Admin is refused the CSV will pass, and it proves that the gate
refuses an account which has **not** granted itself the permission — not that no account
can obtain one.

**And the permission is the narrower of the two paths.** Section 0.2 also records an
unauthenticated route that returns a session as any named user, which reaches the same
download without the permission and without a login. It is pre-existing, untouched by this
release, and unfixed. You are signing off a release whose export gate is real and worth
having against casual access, and which does not withstand either an authenticated actor
who self-grants or an unauthenticated one who knows a `user_name`. That is the premise; if
it is not acceptable, the answer is to hold the release, not to soften this paragraph.

A remedy for `POST roles/permissions/{id}` — the first of the two paths, not the
unauthenticated one — has been written and is **not part of this release**. It is
tracked as **PR #317 F-007 / L-8**, owner **Security owner**, and it ships as its own change
because a repository-wide authorisation fix does not belong in a redesign branch. It is
deliberately cited here by finding id rather than by branch name: a branch stops resolving
the moment it is merged or deleted, which is the same defect as naming a migration that is
not in the tree (PR #317 F-010). Nothing in this release depends on it landing first. When
it does land, section 0.2 and the `EnsureDirectoryExportAccess` docblock are revisited
together, and the accuracy of this paragraph is what tells you they still need it.

**No remedy has been written for the unauthenticated route.** Nothing in this release, and
nothing on any branch this release can see, closes it.

### 0.4 What the merge into main resolves, and why

This release was written when main sat at `bacac0bd3`. While it was in review main moved
on, and it did something that matters here: it made **this release's exact change, then
reverted it**. So the merge does not apply cleanly, and the resolution is a decision rather
than a formality. It is recorded here because sections 1, 2 and 3 below are built on the
outcome, and a reader who resolves it the other way will follow a procedure that no longer
describes their tree. Raised as PR #317 **F-016**.

What the two sides did, read from the commits rather than from either branch's summary:

| Commit | On | What it did |
| --- | --- | --- |
| `7c302997a` | main | Untracked the two manifests — the same change as this release. Its message records the reason: the committed manifest listed 22 packages, two of them absent from vendor/, and **every artisan command died** with `Class "Livewire\LivewireServiceProvider" not found` |
| `c9fd46e73` | main | **Reverted that**, four hours later. The message records no reason |
| `adb068731` | main | Took the opposite route instead: keep the manifests tracked, hand-edit them, and add a blanket `bootstrap/` ignore |

**The decision: untracking stands, and the revert is overridden.** The grounds are the ones
`7c302997a` already established and `c9fd46e73` does not answer — these files are build
output; `composer.json` regenerates them on every install through its `post-autoload-dump`
hook; Laravel rebuilds them on boot when absent; and Laravel ships a `bootstrap/cache`
keeper for exactly this purpose. The alternative is a manifest hand-edited to match one
machine's vendor directory, which is the condition that produced the outage in the first
place. **This is an engineering decision that overrides another branch's revert, so it is
the Engineering lead's to ratify, not the merge resolver's** — it is written down here so
that ratification has something to point at.

Three conflicts, and how each was taken:

| Path | Conflict | Resolution |
| --- | --- | --- |
| `bootstrap/cache/packages.php` | modify/delete | **Deleted.** Note that git leaves the *other* side in the tree by default, so this one has to be taken deliberately |
| `bootstrap/cache/services.php` | modify/delete | **Deleted**, same |
| `.gitignore` | content | Both sides kept — main's Playwright artifact entries and this release's two explicit cache entries — **except** main's blanket `bootstrap/`, which is deliberately dropped |

The blanket `bootstrap/` is dropped because it is wider than its intent: it covers
`bootstrap/app.php` and `bootstrap/providers.php`, which are source, and it covers the
keeper this release tracks on purpose. Tracked files ignore `.gitignore`, so nothing breaks
today — it is a trap laid for whoever next deletes and re-adds one of those files. The two
explicit entries say the same thing exactly.

**Sections 1, 2 and 3 below are unchanged and remain accurate after this merge** — that is
the point of resolving it this way, and it was verified on the merged tree rather than
assumed:

- `php artisan --version` prints **Laravel Framework 9.52.22**, exit 0, on a merged tree
  whose `bootstrap/cache` holds only the keeper — so section 2's precondition holds and the
  manifests really are rebuilt on boot.
- `php artisan package:discover` completes and regenerates both files, and `git status`
  then reports nothing — so the ignore rule covers the regenerated output, which is what
  makes section 1's pre-pull step safe to run repeatedly.
- The suite is **281 tests, 0 failures** on the merged tree, identical to this release
  before the merge (1,437 assertions at the merge commit; an earlier revision of this line
  said 1,436, one short of what an independent run measured).

Section 1 applies to more hosts after this merge, not fewer: a host currently tracking
main's hand-edited manifests meets the same refusal on the way in, for the same reason.

**Ratified 2026-09-23** by the Engineering lead (Ravi Patel), in session: untracking the
two manifests stands, and it overrides main's revert `c9fd46e73`. The ratification rests
on the grounds above; the revert's own reason is still unrecorded, so if one surfaces later
it is weighed against these grounds, not assumed to outrank them.

**The section 0.3 sign-off now carries this.** The 2026-09-21 sign-off was taken against a
text that did not describe a merge resolution, so it was re-taken on 2026-09-23 against
this section as ratified — see section 0.3.

### 0.5 An index for the OT roster feed — already a migration on main; do not add it by hand

The OT grid, its count and its export all read `student_master_course__map` filtered by
course, and this release moves that query from once per page load onto every grid draw
(PR #317 F-002). It is not a merge condition: at today's volume the scan is cheap.

**The index already ships, as a migration that is on `main`.**
`database/migrations/2026_08_19_120000_add_student_master_course_map_lookup_index.php`
(commit `2b8e64dd0`, an ancestor of this release's base) creates
`smcm_course_active_student_index` on exactly the columns this feed needs:
`(course_master_pk, active_inactive, student_master_pk)`. The leading columns match the
query's `course_master_pk = ?` and `active_inactive = 1`, and the trailing
`student_master_pk` lets the join to `student_master` read from the index. On any host where
that migration has run, there is nothing to do.

Measured 2026-09-23 on testsargam6 against the largest course (456 officer trainees), with an
index on those three columns built on a session-only `TEMPORARY` copy of the table:

| | Access to the map table | Rows examined | Time |
| --- | --- | --- | --- |
| No index | full scan, no key | 3,796 | 9.3 ms |
| With the index | `ref` on the index | 456 | 1.7 ms |

Where the migration is still pending, run **that one file**. A bare `php artisan migrate`
would also run every other pending migration on the host:

```bash
php artisan migrate:status | grep 2026_08_19_120000     # "Pending" -> run the next line
php artisan migrate --path=database/migrations/2026_08_19_120000_add_student_master_course_map_lookup_index.php
```

**Rolling back this release does not touch this index.** It belongs to `main` (commit
`2b8e64dd0`), not to this release, and main's own Group Mapping lookup uses the same
columns, so leave it in place when reverting the directory redesign.

Do not use `php artisan migrate:rollback --path=<this file>` to remove it either. Laravel
9's rollback only considers the **latest batch**, so on any host that has migrated since,
that command drops nothing. It prints "Migration not found" for the other files in the
batch and exits without error. If the index ever has to go, the DBA drops it by name,
after confirming it exists:

```sql
SHOW INDEX FROM student_master_course__map WHERE Key_name = 'smcm_course_active_student_index';
ALTER TABLE student_master_course__map DROP INDEX smcm_course_active_student_index;
```

**Do not create the index by hand, under any name.** An earlier version of this section gave
a hand-written `ALTER TABLE … ADD INDEX idx_smcm_course_active_student (…)` instead. The
migration's guard looks for its own index **by name**, so on a host where that hand-made index
exists, the migration still runs and builds a second index on the same three columns.
MySQL 8.0.46 accepts that silently: executed 2026-09-23 on a `TEMPORARY` table, both
`idx_smcm_course_active_student` and `smcm_course_active_student_index` were created on
identical columns. The duplicate costs write time and disk and buys nothing.

**testsargam6 is in exactly that state** (checked 2026-09-23, read-only):
`idx_smcm_course_active_student` is present, applied from the earlier text, and the migration
is `Pending`. Before anyone runs the migration there, drop the hand-made index first:

```sql
ALTER TABLE student_master_course__map DROP INDEX idx_smcm_course_active_student;
```

Check every other host with
`SHOW INDEX FROM student_master_course__map WHERE Key_name = 'idx_smcm_course_active_student'`
and treat a hit the same way.

Online build: the migration uses the schema builder, which issues a plain
`ALTER TABLE … ADD INDEX` with no `ALGORITHM`/`LOCK` clause. Whether the live table builds it
without blocking writes is **not verified**, so run it in a quiet window. Owner: **DBA**.

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

## 5. Also in this release: PRs #322 and #326

Two separate fixes were merged into this branch and ship with it (merge commits
`7b8f6ec4c` and `a1f4b9802`). Neither touches a directory file.

| PR | What it changes | Files |
| --- | --- | --- |
| #322 | Closes four unclosed Blade sections. The one in **both master layouts** leaked an output buffer on every admin and faculty page, flushing stray bytes ahead of `<!DOCTYPE html>` | both master layouts, `admin/country/create`, `course-repository/user/class-material-subject-wise`, 2 tests |
| #326 | Fixes the `/faculty_dashboard` HTTP 500 (a missing component and a deleted include). **Nothing else**: at #326's own head the route renders for every authenticated user | `faculty/layouts/master`, `components/menu/material_management`, 2 tests |

The **access control on `/faculty_dashboard` is not in #326.** It is this PR's own work, made
after the merges:

| Commit | What it changes |
| --- | --- |
| `e5299f834` | Restricts the route to **Faculty and Super Admin**; every other role gets 403 (review finding F-019). `routes/web.php` |
| the F-024 fix commit | Renders the page on the admin layout, whose sidebar is filtered by the RBAC menu table, instead of the faculty layout's unfiltered static admin partials, so a Faculty account sees only the menus its roles are granted (review finding F-024). `faculty/dashboard.blade.php` |

**Do not merge #322 or #326 to `main` on their own.** #326 without #317 puts the 500 fix on
`main` with no restriction, which serves the unfiltered admin menu to every logged-in account,
Officer Trainees included. #317 already carries both; close #322 and #326 as superseded, or merge
#317 first.

**Rollback.** The section 3 revert of the merge commit also reverts both fixes and the
restriction. Expect the stray pre-doctype output to return on every admin page, and
`/faculty_dashboard` to return to HTTP 500 for everyone. Nothing in any of them writes to the
database.

**Post-deploy checks**, in addition to section 4:

1. Open any admin page as Super Admin, then view the source. The first bytes are
   `<!DOCTYPE html>`, with nothing before them.
2. `/faculty_dashboard`: 200 for a Faculty account, 403 for an Officer Trainee and for an
   Employee-only account. As the Faculty account, the sidebar matches the one on its own
   `/dashboard`: no Programme, Group Mapping, Memo Type or Memo Conclusion master links.
3. The MDO/Escort Exemption create page still has its dual-list styling (its page CSS arrives
   through `admin.layouts.pre_header`'s `@yield('css')`).
