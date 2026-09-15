# Deploy notes — Employee / Member redesign (`main_employee`)

No migration in this release. Two steps matter, and the first one is not optional
on a host that has run `composer install` on `main`.

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

## 2. Install and rebuild the manifests

```bash
composer install
php artisan package:discover
```

Both files are regenerated on the host and are now ignored by git, which is
where generated files belong.

## 3. Rollback

```bash
git revert <merge commit>
```

Nothing to undo in the database — this release adds no migration and rewrites no
data. Reverting re-tracks the two cache files, so hosts that have regenerated
them need the same `git checkout -- bootstrap/cache` step again first.

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
- Confirm two `member.pii.*` lines in `storage/logs/laravel.log`, each on a
  single line.
