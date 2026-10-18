# Behavior-Driven Development (BDD) — Sargam 2.0

BDD describes a feature by its **observable behaviour** in the
**Given / When / Then** form, from the user's point of view, before you build it.
Where [TDD](tdd.md) drives the *design of a unit*, BDD drives *what the feature
should do* and keeps the test readable as a specification.

This doc covers how to practise BDD in *this* codebase.

---

## Course Repository search robustness (F-080)

- Emoji in the universal search, Course, Subject, or Author filters returns a
  search page (with matching or empty results), not an unavailable redirect.
- Text comparisons use `utf8mb4` without changing the legacy table encodings.
  Suggestions and relevance ranking use the same comparison helper.
- Course and Subject retain internal label spacing and Unicode text; controls,
  CR/LF, and Unicode line/paragraph separators are stripped. Malformed UTF-8 is
  replaced with the configured mbstring substitution character, not an empty filter.
- Search and suggestion failures log a fixed message and exception class only,
  never the exception message, SQL, bindings, or raw input.

Regression coverage: `tests/Feature/CourseRepositorySearchRobustnessTest.php`
and `tests/Unit/CourseRepositorySearchInputTest.php`. Database-backed cases use
rolled-back transactions against a development database; do not use
`RefreshDatabase` against the application's existing schema.

```bash
php vendor/bin/phpunit --filter 'CourseRepositorySearch(Robustness|Input)Test'
```

---

## Leave Approval PDF actor header (F-089)

- A report with actioned records from multiple faculty approvers or on-behalf
  operators shows `Approved/Rejected By: Multiple Approvers`.
- Faculty and operator IDs are separate identities, even when their numeric
  IDs or display names match. Unknown action sources are not attributed to a
  known actor in a mixed report.
- A single actor retains the same display name used in the report rows.
  On-behalf-only reports name the operator with the `Training Section` suffix.
  Empty, pending-only, and entirely unattributed reports omit the actor header.
- Each row keeps its individual actor; filtering and export access are unchanged.

Coverage: `tests/Unit/LeaveApprovalPdfHeaderTest.php` and
`tests/Feature/LeaveApprovalPdfMixedApproversTest.php`. The mixed-data feature
test creates rolled-back leave records, checks the actual export view data and
rendered header, and verifies a successful PDF download.

---

## Leave on Behalf correction follow-up (F-068)

The current operator path ignores an OT's Draft during overlap checks but has
no correction/cancel endpoint for its Approved records. The
[decision request and separate-PR acceptance criteria](leave-on-behalf-correction-decision.md)
record the observed behavior, unresolved Product Owner approvals and required
follow-up evidence. F-068 remains open; documentation is not risk acceptance.

---

## Course Repository complete display values (F-090)

- When the trimmed stored text is a strictly shorter, exact prefix of its
  corresponding keyword segment, cards use the complete segment. Prefix checks
  are literal and case-sensitive; `%` and `_` are not wildcards. Unrelated,
  equal or shorter keyword text does not replace stored text.
- Local numeric master IDs still resolve to their master names. A stored author
  code uniquely matching `faculty_master.faculty_code` resolves to the faculty's
  full name. Unknown/ambiguous codes keep the existing text fallback rather than
  choosing a person arbitrarily or duplicating document results.
- PHP decoration and SQL display expressions apply the same precedence:
  resolved master name, stored text with prefix completion, keyword fallback for
  blank/unresolved numeric values. Course facets/filters, Author filters, and
  Course/Author suggestions use these labels. Keyword search also includes
  resolved author names.
- Imported rows are not rewritten.

Coverage: `tests/Unit/CourseRepositoryDisplayTextTest.php` and
`tests/Feature/CourseRepositoryDisplayValuesTest.php`, plus existing local-PK,
legacy-subject, author-filter and emoji-search regressions. Feature fixtures are
rolled back; tests check display/filter agreement, Unicode/literal prefixes,
facets, suggestions and unique/ambiguous faculty-code resolution.

---

## Leave on Behalf asset stability (F-077)

- The register loads repository-managed Moment 2.30.1 and Date Range Picker
  3.1 JavaScript/CSS. No date-picker asset uses a CDN or a `latest` URL.
- Its Bootstrap Icons stylesheet remains pinned to 1.11.3 with SHA-384 SRI
  and anonymous CORS. Shared layout dependencies are outside this change.
- Date-range apply/clear and export search/reset behavior remain unchanged.

Coverage: `tests/e2e/leave-on-behalf-export-search.spec.js` loads the date
assets declared by the shipped view, verifies their versions and exercises
the picker and both export formats.

---

## Leave on Behalf export search (F-082)

- Excel and PDF downloads send the DataTable's current global search and apply
  the same OT code/name, course, nature and reason predicates as the grid.
- Course/date filters, operator course access and on-behalf-only scoping remain
  in force. Downloads contain all matching records, not only the current page.
- Empty search exports the full filtered register; a search with no matches
  exports no records. Array-valued search keeps the grid's scalar-only behavior.
- Reset Filters also clears the search sent with either download format.

Coverage: `tests/Feature/LeaveOnBehalfExportSearchTest.php` compares grid rows
with an actual XLSX download and PDF rendering data for every searched field,
empty/no-match searches and nested arrays. Fixtures are rolled back.
`tests/e2e/leave-on-behalf-export-search.spec.js` executes the shipped list
script with real DataTables and verifies both download requests and reset.

---

## Leave on Behalf flashed array input (F-081)

- Array-valued scalar fields fail validation and redirect normally. The returned
  form treats every flashed scalar-field value as scalar-only, using an empty
  string for arrays/null/objects rather than casting or rendering them.
- This applies to Course, Officer Trainee, Nature, dates, times, Reason and
  Contact Number. Scalar values remain selected/prefilled and HTML-escaped.
- Validation errors remain visible, and rejected submissions create no leave.
- End-date comparison runs only with a scalar start date; an invalid array
  start date is rejected by its date rule rather than passed to Carbon.

Coverage: `tests/Feature/LeaveOnBehalfArrayInputTest.php` posts nested arrays for
each field and all fields together, follows the real validation redirect to an
HTTP 200 form, and verifies empty fallbacks and unchanged valid scalar fields.
The scalar-only control verifies HTML escaping. Database fixture changes roll
back after each test.

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
