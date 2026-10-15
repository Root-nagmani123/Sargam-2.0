# `bootstrap/cache/` — generated, not tracked

`bootstrap/cache/packages.php` and `services.php` are **generated** files. Laravel
writes them from whatever is actually installed in `vendor/`, during
`composer install` and on `php artisan package:discover`.

They are therefore **not tracked**. What is tracked is a keeper:

```
bootstrap/cache/.gitignore
    *
    !.gitignore
```

Two separate reasons that file has to exist, and both have bitten this
repository:

1. **Git tracks files, not directories.** With the generated files untracked and
   nothing in their place, a fresh clone has no `bootstrap/cache` directory at
   all, and Laravel refuses to boot: *"The .../bootstrap/cache directory must be
   present and writable."* Every route returns an error, on every clean host and
   every CI runner.
2. **A committed manifest is one machine's vendor tree, frozen.** If it names a
   provider that is not installed, boot fails outright. If it omits one that is
   installed, that package is silently never booted — which is what the tracked
   copies were doing here: both `Livewire\LivewireServiceProvider` and
   `Yajra\DataTables\ExportServiceProvider` exist in `vendor/` and neither
   appeared in the committed manifests (verified 2026-09-15). Nothing in the
   application references either, so it caused no visible failure — but the same
   mechanism applied to a provider that IS used is an outage.

## What this means when you deploy

```bash
composer install
php artisan package:discover
```

`package:discover` is not optional. A deploy that only pulls code now ships
without a manifest, and Laravel rebuilds it on the first request — which works,
but does the discovery work inside a user's request instead of during the
release.

## One-time step for hosts upgrading past this change

A host that has already run `composer install` on an older commit has a locally
modified **tracked** `packages.php`, and git will refuse to move to a commit that
deletes it:

```
error: Your local changes to the following files would be overwritten by checkout:
        bootstrap/cache/packages.php
Aborting
```

Release the files first, then pull:

```bash
git checkout -- bootstrap/cache/packages.php bootstrap/cache/services.php
```

## A note on `composer install` in this repository

`vendor/` has historically been **ahead of `composer.lock`** on developer
machines here, so a bare `composer install` can downgrade a large number of
packages (dompdf, mPDF and much of Symfony among them). That is a real hazard,
but it is a *lockfile* problem and it should be fixed as one — by reconciling
`composer.json`, `composer.lock` and `vendor/` deliberately — not by committing a
generated manifest to paper over it.

If you hit "service provider class not found", the fix is
`php artisan package:discover`, not editing a manifest by hand.
