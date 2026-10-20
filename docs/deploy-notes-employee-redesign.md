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
| **9** | `E` | `user_credentials` pks 1778, 1800, 1382, 1633, 1748, 2102, 1871, 1397, 2528 — **six of these (1778, 1800, 1382, 1633, 1748, 1871) were repaired by migration `2026_09_21_120000` and no longer mismatch; 2102's coincidental email was cleared by `2026_09_21_130000`. Only 1397 and 2528 remain, and 2528 is `Active_inactive` = 0. Figures in this row describe the state the rule was designed against (2026-09-18), not the state after deploy — see §0.3.** |

For reference, and so the two figures above can be told apart from any others in
circulation: the looser rules give **349** pairs under an exact first-plus-last
comparison that is **case-insensitive and trimmed** — the other readings of
"exact" give 356, 364 or 371, so quoting this figure without its rule says
nothing — and **575** under normalised tokens including the employee's
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

**Know what the grant restores, because the screen label says less than it
does.** The row reads *Employee Downloads*, but `member_pii_read` is the
entitlement flag for the whole member module, checked in five places through
`EnsureMemberPiiAccess::grantsAccess()`. Granting it hands the role three
things, not one:

1. **The four document routes** — `show/{id}`, `print/{id}`, `export/{format}`
   and the legacy `excel-export`. This is the download the office asked for.
2. **Cross-member access to the edit wizard** — `edit/{id}`,
   `profile/edit/{id}`, the edit-step routes and `POST member/update` — which
   without it reach the holder's own record only. So the grant also confers the
   ability to open and *save* any member's record.
3. **The two destructive mutations** — `POST member/{id}/toggle-status` and
   `DELETE member/delete/{id}`. These moved behind this permission in the
   round-12 fix (PR #309 F-038 / R11-001); before it they sat outside every
   gate. **`destroy()` is not a soft delete.** It removes the
   `employee_master` row, the member's `user_credentials` row, and every
   `EmployeeRoleMapping` attached to that credential — three tables, no
   transaction wrapping them, and no undo. The only precondition is that the
   member is inactive, and the same grant includes the toggle that makes them
   inactive.

So: **grant it to restore a download and you have also granted the ability to
delete any employee and their login.** If that is not what you want, the answer
is not a narrower grant — there is no narrower grant — it is to leave the
permission ungranted and have someone who **already** holds Super Admin run the
export on the office's behalf. Do not issue a Super Admin account to solve a
download request, and do not grant `member_pii_read` to solve one either unless
you are content for that role to hold an irreversible delete.

**Who can grant this permission.** On `main`, three routes could put
`member_pii_read` (or any other permission) in the hands of any signed-in
account, and this release closes all three. `POST admin/users/assign-role-save`
gave any account any role, Super Admin included (PR #309 F-073; see 0.5).
`POST roles/permissions/{id}` created
and granted whatever name it was posted (PR #309 F-027 / PR #317 L-8); it is now
Super Admin only (`EnsureRoleAdmin`). The sidebar menu editor renamed the
`permissions` row whenever a menu's name changed, and a rename moves that
permission to every role holding it, so one edit could turn a permission an
account already held into `member_pii_read` or `users` (PR #309 F-077); the
sidebar category, menu-group and menu **write** routes (store, update, destroy,
status) are now Super Admin only too. Their read routes, and the sidebar feed
every user loads, are unchanged.

So after this release only a Super Admin can grant the permission or create it
under another name. The code change does not undo a grant or rename made while
these routes were open, so check who holds the two names before relying on
it. On each server, after the migration has run:

```sql
SELECT p.name AS permission, 'role' AS via, r.name AS holder
FROM role_has_permissions x
JOIN permissions p ON p.id = x.permission_id
JOIN roles r ON r.id = x.role_id
WHERE p.name IN ('member_pii_read', 'users')
UNION ALL
SELECT p.name, 'direct', CAST(x.model_id AS CHAR)
FROM model_has_permissions x
JOIN permissions p ON p.id = x.permission_id
WHERE p.name IN ('member_pii_read', 'users');
```

Anything other than Super Admin, or a role you granted on purpose, is the
signature of an earlier rename or grant: remove it before relying on either
gate. Do **not** rely on looking for duplicate `(name, guard_name)` rows. A
rename made before this release is adopted by the migration (it inserts
`member_pii_read` only when no row by that name exists), and a rename done in
two steps never creates a duplicate. Both leave the duplicate check clean while
an ordinary account still passes the gate (PR #309 F-079). This query does not
show accounts that gave themselves a role through `assign-role-save` on `main`:
0.5's Super Admin membership check catches those that took Super Admin, and
any other role taken that way needs its membership reviewed the same way.
Separately (DBA): the
`unique(name, guard_name)` index the permission-table migration declares is
missing on at least the development database. What this release achieves is
worth stating plainly: the member roster and the per-member profile sheets are
out of casual reach, and every served download now writes an audit line naming
the actor, the IP and the row count.

### 0.2 Rolling the migration back

Rolling back **this release's own migration** — scope it with `--path`, as §3
does — removes the menus row, revokes the permission from every role that holds
it, and deletes the permission row. Any role that had been granted the download
loses it, which is the same state as before the release. That statement is about
this one migration; a bare `migrate:rollback` is a different and unvouched-for
operation, see §2.1 and §3.

### 0.3 "Their own record" now means a record they can be shown to own

**This one changes what live accounts can do, with no migration and no setting.
Read it before deploying.**

Until this release the self-service rule was `user_credentials.user_id` equals
the requested employee pk, and nothing else. That column is not ownership. A
census of `testsargam6` (2026-09-18) over the **1,547** credentials whose
`user_id` resolves to an `employee_master` row found:

| `user_category` | credentials | match the row on email or mobile | share no name token with it |
| --- | --- | --- | --- |
| `E` | 1,220 | **1,188** | 9 |
| *(blank)* | 326 | **0** | 311 |
| `S` | 1 | **0** | 0 |

Every blank-category and `S` credential — **327 accounts** — matched its
supposed record on *nothing*, and 311 of them did not even share a name token
with it. Those people were not opening their own profile. They were opening a
stranger's, with the right to save it, and the sidebar's **My Profile** link
sent them there. That is PR #309 F-024.

The rule is now: `user_category = 'E'` **and** the credential's own `email_id`
matches the row's `email`/`officalemail`, or its `mobile_no` matches the row's
`mobile`. No name comparison is used anywhere in the decision — a name test
would be one an attacker satisfies by editing their own credential.

**Who is affected on the day:**

> **Re-measured 2026-09-21 (round 24), after the condition-5 repair migrations
> ran.** The figures in this section moved, and the ones that moved most are the
> ones a deployer acts on. The census above (2026-09-18) is left as written
> because it is what the rule was designed against; the numbers below are the
> ones that describe deploy day.

- **1,198 accounts** — unchanged, self-service works exactly as before. Up from
  1,188: migration `2026_09_21_120000` repaired 11 accounts whose `user_id`
  pointed at the wrong `employee_master` row.
- **327 accounts** (blank / `S` category) — now get **403** on the profile they
  used to reach. They have lost nothing of their own; they have stopped seeing
  somebody else's personal data.
- **8 employee accounts** — these are the ones to expect a ticket from, and
  **this is the figure that replaces the 32 quoted here before the repair.**
  Their `user_category` is `E`, so they are probably looking at their own
  record, but their credential's email and mobile match it in neither field, so
  the application cannot tell them apart from the 327. **The remedy is a data
  fix, not a code change:** correct `user_credentials.email_id` or `mobile_no`
  to match the employee row, and access returns on the next request. Do not
  widen the rule in code to accommodate them.
- **14 further employee accounts** are also refused but are **`Active_inactive`
  = 0** — they cannot log in at all, so they will not generate a ticket and do
  not need a data fix before deploy.

  On testsargam6 the 8 active ones are `user_credentials` pks **1397, 1632,
  1729, 1776, 2091, 2098, 2154, 3070** — two of which (**1397**, **2098**) were
  deliberately left refused by the condition-5 repair because their identity
  could not be confirmed, and are still awaiting a DBA decision. **Re-derive
  this set on the target host rather than trusting those pks** — they are
  testsargam6 pks, and the repair migration changes which accounts are in the
  set:

  ```sql
  SELECT uc.pk, uc.Active_inactive
  FROM user_credentials uc
  JOIN employee_master em ON em.pk = uc.user_id
  WHERE UPPER(TRIM(uc.user_category)) = 'E'
    AND uc.Active_inactive = 1
    AND NOT (
      (LOWER(TRIM(COALESCE(uc.email_id,''))) COLLATE utf8mb4_unicode_ci
         = LOWER(TRIM(COALESCE(em.email,''))) COLLATE utf8mb4_unicode_ci
       AND TRIM(COALESCE(em.email,'')) <> '')
      OR (LOWER(TRIM(COALESCE(uc.email_id,''))) COLLATE utf8mb4_unicode_ci
         = LOWER(TRIM(COALESCE(em.officalemail,''))) COLLATE utf8mb4_unicode_ci
       AND TRIM(COALESCE(em.officalemail,'')) <> '')
      OR (TRIM(COALESCE(uc.mobile_no,'')) COLLATE utf8mb4_unicode_ci
         = TRIM(COALESCE(em.mobile,'')) COLLATE utf8mb4_unicode_ci
       AND CHAR_LENGTH(TRIM(COALESCE(uc.mobile_no,''))) >= 10)
    );
  ```

  **The `COLLATE` clauses are not decoration.** `user_credentials` and
  `employee_master` do not share a collation on this server
  (`utf8mb4_0900_ai_ci` vs `utf8mb4_unicode_ci`); without them MySQL raises
  *"Illegal mix of collations"* (error 1267) and the query fails outright rather
  than returning a wrong answer. Verified by running it both ways, 2026-09-21.

Report any such ticket to the **Engineering lead** with the credential pk, and
to the **DBA** alongside the F-024/F-029 question of whether the underlying
`user_id` misalignment is repaired in data. Nine `E`-category rows are known to
be misaligned outright (pks listed in §5); eight of those nine are closed by
this rule, and the ninth matches on email and is therefore still admitted —
that one is a data question, and it is the only known case this gate does not
answer.

### 0.4 Role and permission administration now requires Super Admin

`POST roles/permissions/{id}` carried `auth` and nothing else, and the
controller behind it created whatever permission name it was posted and granted
it to the role in the URL, with no check on the caller. **Any authenticated
account could grant itself any permission**, which made every `can()`-based gate
in this application — including `member_pii_read` above — advisory rather than
enforced. PR #309 F-027 / PR #317 L-8.

Every endpoint that changes what a role can do is now Super-Admin-only. The
check sits in `RoleController`'s constructor rather than on the route, because
the controller answers on two mounts (`roles/*` and `admin/roles/*`) and a
route-level gate would have closed one of them. Reads — the roles listing, the
dashboard-card screen — are untouched.

The sidebar menu editor is a second way to change what a role can do: renaming a
menu renames its `permissions` row, and every role holding that row now holds the
new name (PR #309 F-077). Its write routes — category, menu-group and menu store,
update, destroy and the status toggles — are Super Admin only as well, on the
route group in `routes/web.php`. The sidebar screens' reads and the sidebar feed
are untouched.

**What to expect:** nothing, for anyone who was administering roles through the
UI, because that screen is already offered to Super Admin alone. If a
non-Super-Admin account reports losing role administration, do not widen the
gate: that account was relying on the defect. Route it to the Engineering lead.

### 0.5 Assigning roles to users now requires the `users` permission — and `users` is Super-Admin-grade

On `main`, `POST admin/users/assign-role-save` carries `auth` and nothing else,
and `UserController::assignRoleSave()` writes whatever role ids it is posted onto
whatever account it is posted. **Any signed-in account can make itself Super
Admin with one request** (PR #309 review F-073, executed against `main`'s code).
This release gates the assign-role routes on the `users` permission and re-checks
it inside the method; a caller who is not Super Admin may not change anyone's
Super Admin membership.

**Treat `users` as a Super-Admin-grade permission** (decided 2026-09-25). Apart
from Super Admin itself, a holder may give any role to any account, its own
included, so it effectively holds every permission any role carries. Grant it
only to accounts you would make Super Admin. On the review database only the
Super Admin role holds it.

**Until this release is live, the hole is open wherever `main` runs.** After
deploying, list the Super Admin members on the host and confirm each one with the
Security owner:

```sql
SELECT mhr.model_id AS user_credentials_pk
FROM model_has_roles mhr JOIN roles r ON r.id = mhr.role_id
WHERE r.name = 'Super Admin';
```

### 0.6 Passwordless sign-in now depends on `APP_ENV`, not on the Host header

`LoginController::authenticate()` has a branch that signs a user in with **no
password check**. It was chosen by `request()->getHost()`, which returns the
client-supplied `Host` header because `TrustHosts` is not registered. So one
request carrying `Host: localhost` (or `127.0.0.1`, `dev.local`,
`98.70.99.215`, `74.225.234.234`) signed in as any user, Super Admin included
(PR #309 review F-066). **It is now chosen by `APP_ENV=local` only**, which a
request cannot set.

**Before deploying, on every host, check the environment:**

```bash
php artisan tinker --execute="echo app()->environment();"
```

- **Production must print `production`.** If it prints `local`, the passwordless
  branch is open to everyone who can reach the login page. Fix `APP_ENV` first.
- **A shared dev or staging server that used passwordless sign-in** — including
  any reached as `98.70.99.215` or `74.225.234.234` — loses it unless its
  `APP_ENV` is `local`. That is the intended effect on anything reachable by other
  people. Its users sign in with real credentials from now on.
- Developer machines with `APP_ENV=local` keep working exactly as before.

**Still owed, as a separate change:** the two hard-coded passwords in the
non-local branch (one for student accounts, one that skips LDAP) are unchanged by
this release. They have been in a public repository and must be treated as
compromised. Removing them needs a real credential path for the student accounts
first. See `reviews/pr-309-F066-mitigation-brief.md` STEP 2 (review workspace).

---

## 1. Before pulling: the bootstrap cache manifests stay tracked

`bootstrap/cache/packages.php` and `bootstrap/cache/services.php` stay
**tracked**, exactly as on `main` (decided 2026-09-25). The committed copies match
this release's `composer.lock`: every package with a service provider is listed,
and nothing listed is missing.

Any host that already ran `package:discover` has locally modified copies, and git
refuses the checkout:

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
php artisan migrate \
  --path=database/migrations/2026_09_16_090000_add_member_pii_read_permission.php \
  --path=database/migrations/2026_09_21_120000_repair_member_user_id_misalignment.php \
  --path=database/migrations/2026_09_21_130000_clear_lbs_reception_coincidental_email.php
php artisan permission:cache-reset
```

`package:discover` rewrites the two tracked manifests from the installed
packages, so they show as modified afterwards. That is expected on a deployed
host. Do not commit them from the host, and run the `git checkout --` step above
before the next pull.

The migrate step is **not optional in this release**, and it is **scoped with
`--path` deliberately, and it must stay scoped.** It names this release's three
migrations and nothing else:

- `2026_09_16_090000` creates the `member_pii_read` permission — what makes §0.1
  possible.
- `2026_09_21_120000` and `2026_09_21_130000` are the DBA-approved data repairs
  that §0.3's deploy-day figures assume have run. **Skip them and those figures
  are wrong:** the 11 accounts the first one repairs (6 whose `user_id` points at
  somebody else's record, 5 with a placeholder email) stay refused.

Both repairs guard every write on the current (broken) value, so running them on
a host where someone already fixed a row by hand changes nothing for that row.
**`2026_09_21_120000` was changed after it first ran on the review database** (its
contact backfill now requires the credential's own name to agree with the
employee row, and it only fills an empty `mobile_no` instead of overwriting one).
Laravel never re-runs a migration it has recorded, so **before deploying, check
whether any host other than testsargam6 already ran it** (`migrate:status`).
On any host that did, the DBA compares `user_credentials.mobile_no` for pks 1441,
2120, 2195, 2201 and 2389 with a backup from before that run (PR #309 review F-075).

`2026_08_19_130000_move_course_repository_documents_off_public_disk` is already on
`main` and is **not** in this scoped command. This release makes it fail loudly:
if `course-repository:secure-documents` cannot move a file, the migration throws and
`migrate` stops, so later migrations in the same run do not apply until the cause
is fixed. Whoever runs it (in its own release) should expect that.

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

**Roll the migration back first, then revert the code — in that order.** This
release *adds* the migration file, so `git revert <merge commit>` deletes it, and
`migrate:rollback --path=` does not skip a path that has gone missing: it throws
`Illuminate\Contracts\Filesystem\FileNotFoundException` and rolls back nothing.
Reverting first therefore leaves you on reverted code with the permission row
still in the database — the exact state this section exists to prevent.

```bash
php artisan migrate:status   # this release's migration must still be the last batch
php artisan migrate:rollback --path=database/migrations/2026_09_16_090000_add_member_pii_read_permission.php
git revert <merge commit>
```

**Do not skip the `migrate:status` line.** `migrate:rollback` takes its candidate
list from the **last batch** and only then filters it by `--path`, so the scoped
command undoes this release's migration *only while that migration is still the
last batch*. If anything else has migrated on the host since, it prints
`Migration not found`, exits **0** and rolls back nothing — no error, so it is
easy to believe it worked. In that case remove the `member_pii_read` permission
row and its menus row by hand per §0.1 instead.

The order matters because the code revert on its own leaves one thing behind: the
`member_pii_read` permission row and its capability menu row, added by this
release's migration. Roll that back too — with the same `--path` scope — or the
permission survives with nothing reading it. See §0.2.

**Leave the two data repairs in place** (`2026_09_21_120000`,
`2026_09_21_130000`). They correct `user_credentials` rows the DBA confirmed were
wrong, and nothing in the reverted code depends on them. Rolling them back would
re-point 6 accounts at somebody else's record. They share a batch with
`2026_09_16_090000` when run by §2's command. The scoped rollback above names
only the permission migration, so it leaves them alone; do not widen it. If they
ever must be undone, that is the DBA's call. Their `down()` restores the old
`user_id` values and emails but deliberately leaves `mobile_no` as it is.

**What this order costs, so it is not a surprise mid-rollback.** Between the
rollback and the *deployed* revert, the new code is still live with the
permission gone. For that window a role that had been **granted**
`member_pii_read` — the §0.1 remedy — is treated exactly as an account that was
never granted it, on **every** surface the grant unlocks, not only the
downloads:

- refused `show`, `print`, `excel-export` and `export/{format}`;
- refused `edit/{id}`, `profile/edit/{id}`, `edit-step/{step}/{id}` and
  `update-validate-step/{step}/{id}` for **any member but themselves** — their
  own record still opens, because `member.record` falls through to an
  own-record comparison;
- refused `POST member/update` when the posted `emp_id` is someone else's. This
  one lands on a **save**, so a wizard already open on another member's record
  cannot be written back;
- refused `POST member/{id}/toggle-status` and `DELETE member/delete/{id}` for
  **every** member, including their own. Unlike the bullets above there is no
  own-record fallthrough here: both mutations are gated on `member.pii` alone,
  deliberately, because `destroy()` deletes the caller's own `user_credentials`
  row and role mappings and an own-record branch would be a working
  self-delete. During the window, deactivating and deleting members is a Super
  Admin action only;
- served a listing whose Action column no longer offers View or Print, offers
  **Edit only on a row that operator can be shown to own** — `MemberDataTable`
  asks `EnsureMemberRecordAccess::ownedMemberPk()`, which is the same method the
  gate itself uses, so the column cannot offer a link `member.record` would
  refuse. An operator among the **359** §0.3 refuses therefore sees **no Edit on
  any row**, which is correct and is what they should expect: if §0.3 has taken
  their self-service away, the button goes with it rather than staying behind to
  answer 403. (Until this was corrected the column still used the pre-§0.3 rule
  and offered those 359 accounts an Edit link the route refused — PR #309 F-046.)
  It no longer offers the **status toggle** or **Delete** on any row, matching the
  bullet above so the screen never shows a control the endpoint will refuse, and
  its Download and Print toolbar is gone.

One rule in five places: `EnsureMemberPiiAccess::grantsAccess()` is the
entitlement flag for the whole module, not only for the exports. The `member.pii`
gate itself, `member.record`, `MemberController::update()`, `MemberDataTable` and
the listing view all ask it the same question. Expect all five bullets, not just
the first.

The loss is immediate, not delayed: `down()` flushes Spatie's cache after
deleting the row, so the next request re-reads the permission tables and
`$user->can('member_pii_read')` comes back false — Spatie's
`checkPermissionTo()` returns false for a permission that no longer exists
rather than throwing. **Super Admin is unaffected**: `grantsAccess()`
short-circuits on the role, and the rollback deletes a permission, not a role.
The revert restores access for everyone once it lands — the reverted code has no
gate at all. Do not reorder to avoid this; the other order does not work at all,
as above.

The rollback is scoped for the same reason the migrate step is, and **the claim
that rolling back is sound covers this release's own migration and nothing
else.** A bare `php artisan migrate:rollback` rolls back the whole last batch; if
anyone has run an unscoped `migrate` on the host, that batch is the full pending
set, and those `down()` bodies restore column *definitions*, not the rows that
were dropped — see §2.1. Nothing in this note vouches for rolling those back.

The two cache files are tracked before and after this release, so a revert
changes nothing about them; hosts that have regenerated them still need the
`git checkout -- bootstrap/cache` step from §1 before pulling. **Reverting re-opens
§0.5's escalation** on that host.

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
- **Then run that same check again as an account whose `user_category` *is*
  `E`, and which the gate still refuses.**

  > **Rewritten 2026-09-21 (round 24). This step used to name nine fixed pks —
  > 1778, 1800, 1382, 1633, 1748, 2102, 1871, 1397, 2528 — and tell you that
  > "each of the nine must show somebody else's record." After the condition-5
  > repair migrations, that instruction is wrong for **six** of the nine: pks
  > 1778, 1800, 1382, 1633, 1748 and 1871 were repaired and now correctly show
  > their **own** record. A deployer following the old text would have seen six
  > accounts "fail" the check by passing it, and reported a defect that had just
  > been fixed. Of the remaining three, **two are `Active_inactive` = 0** and
  > cannot be logged into at all, leaving exactly one (pk 1397) that could still
  > perform the check as written. Verified against testsargam6 2026-09-21.

  Do not use a frozen pk list for this. Derive the set on the host you are
  deploying to, with the query in §0.3 above, and pick any account it returns.
  Each one's `user_id` points at an `employee_master` row it cannot prove it
  owns, so **it must not be able to open or save that record** — the gate must
  refuse it. Report it the same way if it does not.

  This second check is not a duplicate of the first. **`user_category` is
  necessary but not sufficient.** Narrowing the rule to `user_category = 'E'` —
  the obvious reading of F-024, and the one the first check on its own would
  encourage — closes the blank-category cases and leaves these open, and the
  first check *cannot* detect them, because it uses an account whose category is
  not `E`. A fix for F-024 that filters on category alone is not a fix; a
  contact-proof check is required on top of it.

  The original block of nine was a set of `user_id` values written misaligned —
  each landing on an `employee_master` row belonging to *another* credential in
  the same set, the set closing on itself — rather than a category being
  conflated. **Six of those were repaired by migration
  `2026_09_21_120000`**, which is why this step no longer names them. Stated by
  pk and never by name on purpose: this repository is public, see PR #309 F-031
  and F-059.
- **Self-service still works for an account that can prove ownership.** Sign in
  as an employee account whose `user_credentials.email_id` matches its
  `employee_master` row, open **My Profile**, and confirm the record is theirs.
  Then confirm the count: `SELECT COUNT(*) FROM user_credentials uc JOIN
  employee_master em ON em.pk = uc.user_id WHERE UPPER(TRIM(uc.user_category)) =
  'E' AND (LOWER(TRIM(uc.email_id)) = LOWER(TRIM(em.email)) OR
  LOWER(TRIM(uc.email_id)) = LOWER(TRIM(em.officalemail)))` — on the review
  database that is 1,179 before the condition-5 repair and **1,198 with the
  mobile fallback after it** (re-measured 2026-09-21; it was 1,188 before the
  repair). A number near zero on the target host means the contact data does not
  line up there and §0.3 will lock out far more than the **8** active accounts
  it locks out here: **stop and tell the Engineering lead before announcing the
  release.**
- **And a blank-category account no longer reaches a stranger's record.** Sign
  in as an account whose `user_category` is blank, open **My Profile**, and
  confirm **403** — not somebody else's name. Before this release it showed one.
- **Role administration is Super-Admin-only.** As a non-Super-Admin account,
  `POST roles/permissions/{id}` with any permission name must return **403**,
  and `SELECT * FROM permissions WHERE name = '<that name>'` must return no row
  — a 403 that still created the permission would be the same escalation with a
  tidier response. Then confirm a Super Admin can still toggle a permission on
  the Roles screen.
- **The two destructive mutations refuse a non-entitled account.** As an
  account with no entitlement, on the Members grid: the Action column must show
  **no status toggle and no Delete** on any row. Then, because a missing button
  is not a gate, call the endpoints directly — `POST /member/<pk>/toggle-status`
  and `DELETE /member/delete/<encrypted pk>` — and confirm **403** on both, then
  confirm in the database that the member's `status` is unchanged and the row is
  still present. Until the round-12 fix these carried `auth` and nothing else,
  so any authenticated account could deactivate any member and then delete them
  along with their login.
- **And that they admit an entitled one.** As Super Admin, confirm the Action
  column offers Edit, View, Print, the status toggle and Delete, and that
  toggling a member's status succeeds. Do **not** exercise Delete on a live
  member to satisfy this check — the suite covers it inside a rolled-back
  transaction.
- **The Action column agrees with the gate for an account §0.3 REFUSES.** The
  check above uses Super Admin, and a Super Admin sees every control, so it
  cannot detect the screen and the gate disagreeing — that is how PR #309 F-046
  reached round 13 unnoticed. Take one credential pk from the refused list
  produced in §0.3 (the ones whose `user_category` is not `E`, or that carry no
  matching email or mobile), log in as it, and open the Members grid. The Action
  column must offer **no Edit on any row, including the row whose pk equals that
  credential's `user_id`**. Then open `/member/edit/<that user_id>` directly and
  confirm **403**. Screen and route must give the same answer; a visible Edit
  button with a 403 behind it is the failure this check exists to catch.
- **Both doors to the edit wizard refuse the same record.** As a non-entitled
  account, open `/member/edit/<somebody else's pk>` **and**
  `/admin/setup/member/edit/<the same pk>`. Both must be **403**. The second URL
  is a pre-existing mirror route onto the same controller method that carried
  `auth` alone until the round-12 fix (PR #309 F-041); it returned 200 while the
  first returned 403. Then open the same two URLs on **your own** pk and confirm
  both are 200 — the fix must not have taken self-service away.
- Confirm the `member.pii.*` lines in `storage/logs/laravel.log`, each on a
  single line: a download writes one, and after the mutation checks above there
  must also be a `member.pii.toggle_status` and — if Delete was exercised on a
  disposable record — a `member.pii.destroy`, each naming `user_pk` and `ip`. A
  privileged mutation with no trail is what let the ungated version of these
  endpoints go unnoticed.
