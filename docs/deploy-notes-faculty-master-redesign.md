# Deploy notes — Faculty / Master redesign (`main_faculty`)

Everything here is a one-time step for **this** release. Nothing in it is
optional on a host that has run `composer install` on `main`.

---

## 1. Before pulling: release the tracked bootstrap cache files

This release stops tracking `bootstrap/cache/packages.php` and
`bootstrap/cache/services.php` and adds a tracked `bootstrap/cache/.gitignore`
keeper in their place, so a clean checkout still has the directory Laravel needs
at boot.

Any host that has run `composer install` (and therefore `package:discover`) on
`main` has a **locally modified tracked file**, and git refuses to move to a
commit that deletes it:

```
error: Your local changes to the following files would be overwritten by checkout:
        bootstrap/cache/packages.php
Aborting
```

Run this first, on every host:

```bash
git checkout -- bootstrap/cache/packages.php bootstrap/cache/services.php
```

Then pull as usual. The same note applies in reverse if this release is ever
reverted.

## 2. Confirm the PHP version

This release raises the floor to **PHP ^8.2** (`composer.json`, with
`platform.php = 8.2.12`). A host below 8.2 will be refused by composer rather
than failing at runtime, but check before you start:

```bash
php -v
```

## 3. Install and rebuild the manifests

```bash
composer install
php artisan package:discover
```

`bootstrap/cache/*.php` are now generated on the host and ignored by git, which
is where they should have been all along.

## 4. Migration

One migration: a UNIQUE index on `faculty_expertise_master.expertise_name`.

It is guarded, idempotent and reversible, and **it will not stop your deploy**.

If the table holds duplicate names the index cannot be created — but choosing
which of two expertise rows survives is a data decision, and not one to take
under pressure with a release half out of the door. So the migration skips the
index, writes a warning naming the offending values to the application log and
to STDERR, and **returns normally**. Nothing is half-applied: the index is
either created or it is not.

The uniqueness users experience is unaffected meanwhile — the store path
validates with `Rule::unique()->ignore()` and still catches MySQL 1062 — so the
index is defence in depth rather than the only guard.

**Re-running `php artisan migrate` will not add the index later.** Laravel
records the migration as run the moment it returns, skip or not, so `migrate`
answers "Nothing to migrate". Once the rows are merged or renamed, add the index
directly:

```sql
ALTER TABLE `faculty_expertise_master`
  ADD UNIQUE INDEX `fem_expertise_name_unique` (`expertise_name`);
```

The message looks like this:

```
[migration] fem_expertise_name_unique NOT created: 1 duplicate expertise_name
value(s) in faculty_expertise_master ("Public Administration" x2). This migration
is now recorded as run, so `php artisan migrate` will not retry it. Merge or
rename the rows, then run: ALTER TABLE `faculty_expertise_master` ADD UNIQUE
INDEX `fem_expertise_name_unique` (`expertise_name`); application-level
uniqueness is unaffected in the meantime.
```

To know in advance, run the query the migration runs — a zero result means the
index will be created on this host. Only NULL is exempt from a UNIQUE index;
repeated empty strings collide like any other value, so they are counted:

```sql
SELECT expertise_name, COUNT(*)
FROM faculty_expertise_master
WHERE expertise_name IS NOT NULL
GROUP BY expertise_name
HAVING COUNT(*) > 1;
```

Executed 2026-09-24 on `testsargam6`, a **development** database and not
production: **0 duplicate groups over 10 rows, 0 empty-string and 0 NULL
names**, with `fem_expertise_name_unique` present. Run it on the production host
too if you want to know beforehand whether the index will land — but the deploy
no longer depends on the answer.

```bash
php artisan migrate
```

Migrate-then-release and release-then-migrate are both safe: old code with the
new index works (it just cannot insert a duplicate), and new code with the old
schema works (the controller still catches MySQL 1062 and turns it into a field
error).

## 5. Rollback

```bash
git revert <merge commit>
php artisan migrate:rollback --step=1   # guarded down(), drops the index only
```

No data is rewritten by this release, so a rollback loses nothing. Cache keys
moved v1 → v2; rolling back serves the v1 keys again, which is harmless.

## 6. Post-deploy checks (first working day)

- `php artisan --version` on every host **before** the release is announced.
- Open all four listings: Faculty, Appellation, Faculty Expertise, Faculty Type.
- On the Faculty grid run CSV, Excel, PDF and Print with a search term and one
  column hidden — all four must show the same columns and the same rows.
- Open **Download → Full Details** and check a record that has a bank account
  number: it must show every digit, not `1.23457E+17`.
- Toggle one Faculty Expertise row; confirm the badge and the cached list agree.
- Toggle one **Stream** and one **Hostel Building** row.
- On the Stream listing, confirm an **active** stream shows Delete greyed out
  and an inactive one offers it.
- Confirm one `Master grid export` and one `Faculty full-detail workbook export`
  line in `storage/logs/laravel.log`.
- `php artisan migrate:status` shows the new migration as run — which it does
  even when the index was skipped, so also check
  `SHOW INDEX FROM faculty_expertise_master WHERE Key_name = 'fem_expertise_name_unique'`
  returns a row. If it does not, see section 4.
- Toggle any status switch and confirm one `Toggle-status change` line in the
  log naming your user, the table, the row and the old and new value.
