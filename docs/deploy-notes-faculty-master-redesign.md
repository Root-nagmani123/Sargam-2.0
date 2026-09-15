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

It is guarded, idempotent and reversible, and it **refuses rather than
half-applies** if the table holds duplicates. Confirm the count is zero before
you run it — the migration runs this query itself, so a non-zero result stops
the deploy with an exception rather than corrupting anything:

```sql
SELECT expertise_name, COUNT(*)
FROM faculty_expertise_master
WHERE expertise_name IS NOT NULL AND expertise_name <> ''
GROUP BY expertise_name
HAVING COUNT(*) > 1;
```

Verified 2026-09-15 on `testsargam6`: **0 duplicate groups over 10 rows**, and
the index `fem_expertise_name_unique` applied cleanly.

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
- `php artisan migrate:status` shows the new migration as run.
