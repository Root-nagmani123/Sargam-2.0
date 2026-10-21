# Behavior-Driven Development (BDD) — Sargam 2.0

BDD describes a feature by its **observable behaviour** in the
**Given / When / Then** form, from the user's point of view, before you build it.
Where [TDD](tdd.md) drives the *design of a unit*, BDD drives *what the feature
should do* and keeps the test readable as a specification.

This doc covers how to practise BDD in *this* codebase.

---

## What's installed (and what isn't)

- **No Behat, no Cucumber, no Pest** in `composer.json`. So there is no
  `.feature`/Gherkin runner today.
- BDD here is practised in two grounded ways:
  1. **Given/When/Then Feature tests** in PHPUnit (`tests/Feature`) — the
     pragmatic default, no new dependency.
  2. **Playwright** browser specs in `tests/e2e` (e.g. `erp-smoke.spec.js`) for
     end-to-end user journeys.
- If the team wants **true Gherkin `.feature` files**, add Behat — see the
  optional section at the end. Don't assume it's there.

---

## Pattern 1 — Given/When/Then Feature tests (default)

Write the scenario in plain language first, then express it as a Feature test
whose structure mirrors the three clauses. Example — the medical-exemption export
behaviour:

```gherkin
Feature: Export medical exemptions
  Scenario: Admin downloads the styled Excel export
    Given I am signed in as an admin
      And a medical exemption exists for an active course
    When I request the export with format "excel"
    Then I receive an .xlsx download
      And the file does not contain a "Document" column
```

```php
namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExportMedicalExemptionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function admin_downloads_the_styled_excel_export(): void
    {
        // Given
        $admin = User::factory()->admin()->create();
        StudentMedicalExemption::factory()->forActiveCourse()->create();

        // When
        $response = $this->actingAs($admin)->get(
            route('student.medical.exemption.export', ['format' => 'excel'])
        );

        // Then
        $response->assertOk();
        $this->assertStringContainsString(
            'spreadsheetml',                       // xlsx mime
            $response->headers->get('content-type')
        );
    }
}
```

Guidelines:

- **The test method name is the scenario title** (`admin_downloads_the_styled_excel_export`).
- **Comment the three clauses** (`// Given / // When / // Then`) so the test
  reads as a spec.
- **Assert observable outcomes** (status, headers, DB rows, JSON shape,
  redirects) — never internal implementation detail.
- **One scenario per test.** Multiple scenarios for a feature → multiple methods
  in one test class.

---

## Pattern 2 — End-to-end behaviour with Playwright

For journeys that only exist in the browser (sidebar navigation, DataTable
filters, modal Add/Edit flows, print/download buttons), use Playwright specs in
`tests/e2e`. `erp-smoke.spec.js` is the existing reference.

```js
// tests/e2e/medical-exemption-export.spec.js
test('admin can download the medical exemption Excel', async ({ page }) => {
  // Given
  await loginAsAdmin(page);
  await page.goto('/admin/student-medical-exemption');

  // When
  await page.getByRole('button', { name: 'Download' }).click();
  const [download] = await Promise.all([
    page.waitForEvent('download'),
    page.getByText('Download Excel').click(),
  ]);

  // Then
  expect(download.suggestedFilename()).toMatch(/\.xlsx$/);
});
```

Run e2e separately from PHPUnit (Playwright CLI, e.g. `npx playwright test`).
These are slower — reserve them for **critical user journeys**, not exhaustive
logic (that belongs in [TDD](tdd.md) unit tests).

---

## Choosing the level

| Behaviour lives in… | Test it with |
|---|---|
| Pure logic (rules, mappers, formatters) | Unit test — [tdd.md](tdd.md) |
| Route + controller + DB + validation | Given/When/Then **Feature** test (Pattern 1) |
| A multi-step browser journey / UI wiring | **Playwright** e2e (Pattern 2) |

Push each behaviour to the **lowest level that still proves it** — Feature/e2e
tests are valuable but slow, so don't re-test pure logic there.

---

## The BDD workflow

1. **Describe** the scenario in Given/When/Then (in the ticket, or as a comment
   block) — agree on it *before* coding.
2. **Write the failing acceptance test** (Feature or e2e) that encodes it.
3. **Drive the internals with TDD** unit tests until the acceptance test passes.
4. **Refactor** with both nets green.
5. Keep the scenario wording and the test method name in sync — the test is the
   living specification.

---

## Optional: adding real Gherkin (Behat)

Only if the team wants human-readable `.feature` files as the source of truth:

```bash
composer require --dev behat/behat behat/mink laravel/dusk   # not currently installed
vendor/bin/behat --init
```

You'd then keep `.feature` files (Gherkin) with step-definition classes mapping
each `Given/When/Then` line to code. This is a **deliberate stack addition** —
discuss before introducing it, since it adds a second test runner alongside
PHPUnit and Playwright.

See also: [tdd.md](tdd.md) · [design.md](design.md) · [master.md](master.md).
