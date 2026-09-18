# Dependency security advisories — accepted risk

**Status:** accepted risk, recorded 2026-09-18.
**Owner:** Security owner (Ravi Patel).
**Revisit:** on any work touching signed URLs, the `email` validation rule, or file-upload
validation — and in any case before the next PHP upgrade.

`composer audit` reports four advisories, all against `laravel/framework`. They are recorded
here rather than fixed, because **no fix exists inside this project's dependency constraints**.
This file is the answer to "why is `composer audit` still red?", so that the next person does
not spend an afternoon rediscovering it.

## What was measured

Run on 2026-09-18 against this repository. These are command outputs, not recollections.

```
composer.json         "laravel/framework": "^9.0"
                      "php": "^8.0"
composer.lock pins    laravel/framework v9.52.21
vendor/ actually has  laravel/framework 9.52.22      <-- vendor is AHEAD of the lock
PHP on this host      8.2.12
```

## The advisories

| Advisory | Severity | Fixed in |
| --- | --- | --- |
| Temporary Signed URL Path Confusion — [GHSA-crmm-hgp2-wgrp](https://github.com/advisories/GHSA-crmm-hgp2-wgrp) | Medium | ≥ 12.61.1 |
| CRLF injection in the default `email` rule — [GHSA-5vg9-5847-vvmq](https://github.com/advisories/GHSA-5vg9-5847-vvmq) | High | ≥ 12.60.0 |
| CVE-2026-48019 — the same CRLF defect, separately catalogued | — | ≥ 12.60.0 |
| File Validation Bypass — [CVE-2025-27515](https://github.com/advisories/GHSA-78fx-h6xr-vch4) | Medium | ≥ 10.48.29 |

The "fixed in" column is the point. **Every fix landed above the `^9.0` ceiling in
`composer.json`.** There is no patched 9.x release to move to.

## Why `composer install` is not the remedy

It gets asked for, so here is the reasoning written down.

1. **`composer install` installs exactly what `composer.lock` pins** — which is
   `v9.52.21`, one of the versions the advisories are *against*. Installing the lock
   cannot move off a vulnerable version; that is what a lock is for.
2. **`composer update` cannot help either**, because resolution is bounded by
   `"laravel/framework": "^9.0"`. The best it can reach is 9.52.x, still below every fix
   above. Reaching them means editing the constraint, which is a major upgrade, not an
   update.
3. **On this host `composer install` does not even run.** It aborts during the platform
   check, before touching `vendor/`:

   ```
   Your lock file does not contain a compatible set of packages. Please run composer update.

     maennchen/zipstream-php   3.2.0   requires php-64bit ^8.3   (host has 8.2.12)
     openspout/openspout       v3.7.4  requires php ~7.3-~8.1    (host has 8.2.12)
     phpoffice/phpspreadsheet  1.30.0  depends on zipstream 3.2.0
   ```

   Note the third line: `phpoffice/phpspreadsheet` is what every `.xlsx` export in this
   application is built on.

Separately, and worth knowing before anyone runs it anyway: `vendor/` currently holds
**9.52.22** while the lock pins **9.52.21**, so the installed tree is ahead of the lock in at
least one package. `vendor/` is gitignored and there is no backup of it, so an install that
did succeed would be difficult to undo.

## What the real fix costs

A major framework upgrade — Laravel 9 → 12 — plus the PHP version that lock already wants
(8.3). Not scoped here; the items below are what a scoping exercise would have to price, and
they are listed so nobody mistakes this for a weekend job.

- **Three major versions of breaking changes** (9 → 10 → 11 → 12), across an application
  with 1,448 registered routes.
- **PHP 8.2.12 → 8.3** on every host, which is its own deployment step.
- **Five abandoned packages**, reported by the same `composer audit` run, which an upgrade
  would have to replace or vendor:

  | Abandoned | Suggested replacement | Note |
  | --- | --- | --- |
  | `adldap2/adldap2` | none offered | **LDAP authentication** — no drop-in successor |
  | `adldap2/adldap2-laravel` | none offered | same |
  | `fruitcake/laravel-cors` | none offered | CORS middleware |
  | `league/flysystem-azure-blob-storage` | `azure-oss/storage-blob-flysystem` | |
  | `tightenco/collect` | `illuminate/collections` | |

  `adldap2` is the one to look at first: it has no suggested replacement and it is on the
  authentication path — `app/Http/Controllers/Auth/LoginController.php` and
  `app/Http/Controllers/Admin/UserController.php` both reference it, and `config/ldap.php`
  configures it.

## What is *not* being claimed

This file records a decision, not a clean bill. The four advisories are live against the
version this application runs. The accepted-risk position is that the exposure is smaller
than the risk of a rushed major upgrade — not that the exposure is zero.

Nothing here has been mitigated in application code. If that changes — for example if the
`email` validation rule is wrapped, or signed URLs stop being used — update this file, because
a stale "accepted risk" is worse than a red audit.
