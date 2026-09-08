# Dependency and Toolchain Notes

Why `composer.json` and `composer.lock` look the way they do. Every entry here
was a deliberate decision that is not self-evident from the manifest, so this
file exists to stop the next person re-deriving it — or, worse, "fixing" it back.

---

## PHP constraint and the platform pin

Two settings that must be read together:

```json
"require":  { "php": "^8.2" },
"config":   { "platform": { "php": "8.2.12" } }
```

**`config.platform.php` — resolve as if PHP 8.2.12.**
This was added to fix a real inconsistency: the lock previously pinned
`zipstream-php` 3.2.0, a release requiring PHP >= 8.3, so that lock could not be
installed on an 8.2 host at all. Pinning the platform makes resolution
deterministic and independent of whichever PHP the person running `composer`
happens to have. Visible effect of the pin: `zipstream-php` 3.2.0 -> 3.1.2.

**`require.php` — the floor the application actually supports.**
This said `^8.0` while the lock was being resolved at 8.2.12, which was wrong in
a way that would only have shown up in production. The resolver, working at
8.2.12, selected packages that need 8.2 — `symfony/string`,
`symfony/css-selector`, `symfony/event-dispatcher`, `nette/utils` — while the
manifest still advertised support for 8.0. On an 8.0 or 8.1 host,
`composer install` would have accepted the declared constraint and the
application would have failed later, at runtime, for reasons that look nothing
like a dependency problem.

Raised to `^8.2` so the manifest states the truth. The practical effect is that
**`composer install` now refuses a host below 8.2** instead of installing
something that cannot run there.

A pin *older* than the running PHP is fine and expected — resolving as if 8.2.12
produces packages that also run on 8.3+. The dangerous direction is a host below
the floor, and that is now rejected outright rather than discovered in
production.

`tests/Unit/PhpPlatformConstraintTest.php` holds this together: it asserts the
declared floor is high enough for every locked package (the check that would
have caught the original mismatch), and that the PHP actually running the suite
satisfies the declaration — so any machine that runs the tests proves its own
compatibility rather than being assumed compatible.

### If you need to change the PHP version

1. Change `require.php` to the new floor.
2. Change `config.platform.php` to match that floor (not to your local version).
3. Run `composer update --lock` if only the constraint changed, or a real
   `composer update` if you intend package versions to move.
4. `PhpPlatformConstraintTest` will tell you if the two disagree.

---

## DataTables dependency swap

`yajra/laravel-datatables-export` was **removed**; these were added as direct
requirements:

- `yajra/laravel-datatables-buttons ^9.1`
- `yajra/laravel-datatables-html ^9.4`

The removed package also pulled in Livewire transitively. Nothing in the
application referenced either it or Livewire — verified by grep across the
codebase at the time of the swap — and `config/app.php` registers only the core
Yajra provider.

Export functionality does **not** depend on the removed package. The Master
module renders CSV / Excel / PDF / Print through
`App\Http\Controllers\Concerns\ExportsMasterGrid`, which uses Maatwebsite Excel
and DomPDF directly.

---

## `tools/phpstan/`

An isolated analyser toolchain with its **own** `composer.json`, deliberately
kept out of the root manifest so that installing a static analyser cannot drag
the application's dependency graph around — analysers tend to have opinionated
constraints on `nikic/php-parser` and friends.

**This does not yet wire up a working PHPStan gate.** There is still no
`phpstan.neon` at the repository root, so the analyser cannot actually be run
against the application. Tracked as `TOOL-2026-08-21-01`; likewise Pint has no
`pint.json` (`TOOL-2026-08-21-02`).

---

## Things not to do

- **Do not run `composer install` casually in a working tree that already has a
  populated `vendor/`.** `vendor/` here has drifted ahead of `composer.lock`, so
  a plain install can downgrade a large number of packages, including the PDF
  stack. If a provider class goes missing, the usual cause is a stale
  `bootstrap/cache/packages.php` — re-run `php artisan package:discover` first.
- **Do not remove the platform pin** to "let composer figure it out". That
  reintroduces resolution that depends on whoever ran it last.
- **Do not widen `require.php`** without checking the locked packages. The test
  will fail, and it will list exactly which packages cannot run on the wider
  floor.
