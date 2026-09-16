# Deploy notes — Directory redesign (PR #317)

Applies to the `main_directory` release: the LBSNAA and OT directory grids, their
five-format export layer, the Super-Admin export gate and the download audit line.

No migration. No dependency change. `composer.lock` is byte-identical to `main`.

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
