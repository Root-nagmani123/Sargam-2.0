# Test-Driven Development (TDD) — Sargam 2.0

TDD means writing a **failing test first**, then the minimum code to pass it,
then refactoring — the **red → green → refactor** loop. This doc describes how to
do that in *this* codebase (Laravel + PHPUnit), not TDD in the abstract.

---

## The stack (what's actually installed)

| Tool | Version | Role |
|---|---|---|
| `phpunit/phpunit` | ^9.5 | test runner (Laravel's default) |
| `mockery/mockery` | ^1.4 | mocks / spies for collaborators |
| `fakerphp/faker` | ^1.9 | fake data for factories |

Config: `phpunit.xml`. Two suites: **Unit** (`tests/Unit`) and **Feature**
(`tests/Feature`). Base class `tests/TestCase.php` (+ `CreatesApplication`).
There is **no Pest and no `composer test` script** — run PHPUnit directly.

> `tests/e2e/erp-smoke.spec.js` is a **Playwright** browser smoke test, run
> separately from PHPUnit. See [bdd.md](bdd.md) for behavior/e2e testing.

---

## Running tests

```bash
php artisan test                          # all suites, pretty output
php artisan test --testsuite=Unit         # one suite
php artisan test tests/Feature/ExportTest.php   # one file
php artisan test --filter=it_excludes_document_column   # one method
vendor/bin/phpunit                        # raw runner (same config)
```

**Database:** `phpunit.xml` sets `APP_ENV=testing` with array cache/session/queue.
The `DB_CONNECTION=sqlite` / `:memory:` lines are **commented out** — uncomment
them (or point `.env.testing` at a disposable MySQL schema) before writing tests
that hit the DB, and add the `RefreshDatabase` trait so each test starts clean.

---

## Unit vs Feature — where a test goes

- **Unit** (`tests/Unit`) — a single class in isolation, no framework boot / no
  DB. Pure logic: a mapper, a formatter, a value object, a service method whose
  collaborators you mock. Fast.
- **Feature** (`tests/Feature`) — a slice through the framework: a route,
  controller, validation, DB, redirect/JSON response. Uses `RefreshDatabase`.

Rule of thumb: **test behaviour at the lowest level that still proves the
behaviour.** Prefer a Unit test; reach for Feature when the behaviour only exists
once HTTP + DB are involved.

---

## The loop, with a real example

Take the export work in `app/Exports/StudentMedicalExemptionExport.php`. A TDD
cycle for the rule *"exports must not include the Document column"*:

**1. Red — write the failing test first** (`tests/Unit/StudentMedicalExemptionExportTest.php`):

```php
namespace Tests\Unit;

use App\Exports\StudentMedicalExemptionExport;
use PHPUnit\Framework\TestCase;

class StudentMedicalExemptionExportTest extends TestCase
{
    /** @test */
    public function column_headings_exclude_document_and_action(): void
    {
        $headings = (new StudentMedicalExemptionExport())->columnHeadings();

        $this->assertNotContains('Document', $headings);
        $this->assertNotContains('Action', $headings);
        $this->assertSame('Diagnosis / Remarks', end($headings)); // last column
    }
}
```

Run it, watch it **fail** (red). A failing test proves the test can fail — a test
that was green from the start proves nothing.

**2. Green — minimum code to pass.** Remove `'Document'` from `columnHeadings()`.
Re-run: green.

**3. Refactor** — clean up names/dupes with the test as a safety net; keep it
green.

> Feature-level counterpart (needs DB + auth): hit
> `route('student.medical.exemption.export', ['format' => 'excel'])` as a logged-in
> user and assert `assertOk()` + the `Content-Type` is the XLSX mime — proving the
> wiring, not just the class.

---

## Conventions for this repo

- **Namespace mirrors the folder**: `Tests\Unit\…`, `Tests\Feature\…`.
- **File suffix `Test.php`** (required by `phpunit.xml`'s `suffix="Test.php"`).
- **Name the behaviour, not the method**: `it_rejects_overlapping_exemptions`,
  not `testStore`. Use the `/** @test */` annotation or a `test_` prefix.
- **One behaviour per test**; arrange-act-assert, blank-line separated.
- **Mock collaborators, not the subject** (Mockery). Don't mock the class you're
  testing.
- **Factories + Faker** for DB fixtures; never rely on prod/seed data.
- **`RefreshDatabase`** on every Feature test that touches the DB.

---

## When to apply TDD here

- **Always** for pure logic: exemption date/overlap rules, export mappers,
  services, helpers, scopes. These are cheap to test-first and regression-prone.
- **Usually** for controllers/validation via Feature tests (auth, permissions,
  422 shapes).
- **Skip** for Blade markup, one-off migrations, and pure config — cover those
  with a browser smoke test instead (see [bdd.md](bdd.md)).

See also: [bdd.md](bdd.md) (behaviour/acceptance level) · [design.md](design.md)
· [master.md](master.md).
