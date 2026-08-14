# Sargam 2.0 — Shared Review Standards

> **Applies to:** Pull-request reviews and entire-codebase audits of `Root-nagmani123/Sargam-2.0`.
> **Purpose:** Define the common severity model, confidence model, finding format, automation baseline, developer database guidelines, security checks, transaction interpretation, testing expectations, and evidence requirements used by all reviews.

| Field | Value |
| --- | --- |
| Document version | v2.0 |
| Status | Local review standard |
| Owner | Review framework owner (named maintainer) |
| Approver | Engineering lead |
| Review cadence | Every 6 months, or on any change to schema conventions, role model, cache architecture, framework version, or tooling |
| Last reviewed | *(record date on adoption)* |

> **This is a reference module, not an entrypoint.** Agents load `review-core.md` and `agent-operating-rules.md` first, then pull sections of this file per the module map in `review-core.md` Section 9. Loading this document in full for every review dilutes instruction-following and wastes context.

---

## 1. Core review principles

- Verify conclusions against source code, not only the PR description, ticket text, or generated scan summary.
- Distinguish confirmed defects from risks, evidence requests, and improvement opportunities.
- State what was checked and found clean, not only what failed.
- Use concrete failure scenarios and practical remedies.
- Do not present runtime, query-plan, index-usage, timing, or scalability claims as confirmed unless supported by evidence.
- Prefer precise wording such as "can cause," "appears to," "static inspection indicates," or "requires runtime verification" when certainty is limited.
- Avoid style-only findings unless the style issue causes correctness, security, maintainability, or operational risk.
- **Automation first.** A reviewer must not spend manual effort hunting for anything an automated rule already detects. See Section 9. Reviewer attention belongs on domain logic, authorisation, concurrency, and blast radius.
- **Every conclusion must be closable.** A finding that states no evidence requirement, and a verdict that states no exit criteria, cannot be acted on.

---

## 2. Finding classification

Every finding must be classified as one of the following:

| Classification | Meaning | Affects PR verdict? |
| --- | --- | --- |
| **Introduced** | Created by the current PR or change set | Yes |
| **Worsened** | A pre-existing issue whose impact is materially increased | Yes |
| **Touched** | Pre-existing issue inside logic directly modified or depended upon | Yes, when material |
| **Legacy / Repository-wide** | Existing issue identified during a codebase audit, or noticed during a PR review outside the change path | No — record under "Follow-up items outside this PR" and add to the remediation register |
| **Unrelated** | Pre-existing issue outside the relevant change path with no dependency on the change | No — exclude entirely |

For PR reviews, only **Introduced**, **Worsened**, and materially **Touched** findings may affect the verdict. **Legacy / Repository-wide** findings are permitted in a PR report but only in the follow-up section, never in the verdict.

---

## 3. Finding types

Use one primary type per finding:

- Security
- Correctness
- Data integrity
- Reliability
- Concurrency
- Performance
- Migration
- API contract
- Frontend / UX
- Observability
- Configuration / supply chain
- Testing
- Maintainability
- Evidence required
- Architectural follow-up

---

## 4. Severity definitions

Severity is based on realistic impact, not merely on which guideline clause is involved.

### Blocker

Merging or releasing is unsafe because the issue is confirmed and severe.

Examples:

- Syntax or runtime failure on a real path
- Critical authorisation bypass
- Data corruption or irreversible data loss
- Exposed secret or sensitive information
- Irreversible or unsafe migration failure
- Confirmed wrong financial result

**Secret exposure is not only a finding — it is an incident.** A confirmed credential, token, or key in source, history, logs, or a committed artifact triggers the response path in Section 10.6 (rotate, purge, assess exposure) in parallel with the review. The review must not be closed on removal of the line alone.

### High

Likely material production impact or serious correctness/security risk.

Examples:

- Broken common workflow
- Serious object-level access-control issue
- Partial write or unsafe transaction
- Duplicate billing, payment, approval, or inventory operation
- Severe N+1 or unbounded query on a high-volume path
- Incorrect business decision under realistic input
- Privilege escalation via mass assignment

### Medium

Material but bounded risk requiring correction or explicit acceptance.

Examples:

- Missing pagination on a growing dataset
- Cache invalidation weakness
- Concurrency assumption not protected
- Backward-compatibility concern
- Performance risk under realistic scale
- Important testing or evidence gap
- Privileged action not audit-logged

### Low

Limited hygiene or maintainability issue with low operational impact.

Examples:

- Dead code
- Minor duplication
- Narrowly scoped inefficient query
- Misleading comments
- PR description inconsistency without functional impact

### Advisory

Useful improvement that should not block merge.

Examples:

- Refactoring opportunity
- Schema-normalisation follow-up
- Additional observability beyond the mandatory set
- Non-urgent architectural recommendation

### 4.1 "Material Medium" — definition

The verdict definitions depend on this term, so it is defined once here.

A Medium finding is **material** when **any** of the following is true:

- It can produce an incorrect user-visible or stored result under realistic input.
- It sits on a money, approval, permission, or data-retention path.
- It degrades a shared or high-frequency path rather than a single rarely used screen.
- It removes a safety property that previously existed (for example, invalidation, constraint, validation, or rollback).
- Its remediation after merge would require a data fix rather than a code fix.

A Medium finding that meets none of these is **non-material**: record it, do not block merge on it.

---

## 5. Confidence levels

Every finding must include confidence.

| Confidence | Meaning |
| --- | --- |
| **High** | Directly proven from source or schema |
| **Medium** | Strong indication, but runtime, schema, environment, or domain evidence is still required |
| **Low** | Plausible concern that should not block without further verification |

---

## 6. Severity × confidence — how findings affect a verdict

Severity states *how bad it would be*. Confidence states *how sure we are*. A verdict must be driven by both. A finding is **confirmed** only where this table says so.

| Severity ↓ / Confidence → | High | Medium | Low |
| --- | --- | --- | --- |
| **Blocker** | Confirmed Blocker — blocks merge | Downgrade to **Evidence required (Blocker candidate)** — blocks merge until evidence resolves it up or down; SLA applies | Record as Blocker candidate under follow-up; does not block |
| **High** | Confirmed High — blocks merge | **Evidence required (High candidate)** — "Approve with conditions" at minimum; evidence must be supplied before merge | Follow-up item; does not block |
| **Medium (material)** | Condition on merge | Condition on merge, satisfiable by evidence | Follow-up item |
| **Medium (non-material)** | Record | Record | Record |
| **Low / Advisory** | Record | Record | Record |

Rules:

- **No Blocker or High may be asserted at Low confidence.** Re-state it as an evidence request naming exactly what would confirm or clear it.
- A Medium-confidence Blocker/High is never silently downgraded to Medium. It keeps its candidate severity and becomes a merge condition, so it cannot be lost.
- If evidence is refused or unavailable, the finding does not disappear — the verdict becomes **Unable to conclude** for that area.
- Confidence may be revised on re-review. Record the revision and the evidence that caused it.

---

## 7. Mandatory finding format

```text
[HIGH][CORRECTNESS] Billing units may use the wrong meter record
Confidence: High
Classification: Introduced
Detection: Manual source review          # Manual | Automated rule <ID> | Trap <n> | Reported incident

Location:
app/Http/Controllers/Admin/EstateController.php:8120-8170
Function: calculateBillUnits()

Issue:
The lookup uses a formatted month string as the matching key.

Failure scenario:
If one path produces "June 2026" and another produces "Jun 2026",
the lookup silently fails and the code treats the meter record as missing.

Impact:
The generated bill amount may be incorrect.

Required change:
Use a stable numeric year-month key or separate numeric year/month columns.

Validation:
Add or provide evidence for normal month, meter-change month, and missing-meter cases.

Closure evidence:
Passing tests for the three cases above, plus a query confirming no existing
rows fail the new match.
```

- `Detection` is mandatory. It shows whether the finding came from a rule, a trap, or human judgement, and it feeds the automation feedback loop in Section 9.4.
- `Closure evidence` is mandatory for Blocker, High, and material Medium. It states what will be accepted as proof the finding is resolved, so closure is not a matter of opinion.
- Group repeated instances with the same root cause. Use representative locations rather than one comment per occurrence.

---

## 8. Review priority order

1. Breaking changes and blast radius
2. Logic and correctness
3. Security and authorisation
4. Data integrity
5. Concurrency and idempotency
6. Transaction and failure safety
7. Database and memory performance
8. Migration and deployment safety
9. API and frontend contracts
10. Observability and audit logging
11. Configuration, dependencies, and supply chain
12. Test adequacy
13. Maintainability and hygiene

---

## 9. Pre-review automation baseline

Manual review is expensive and inconsistent at exactly the things machines are good at. Everything in this section must run **before** a human review begins.

### 9.1 Required tooling

| Gate | Tool | Requirement |
| --- | --- | --- |
| Static analysis | PHPStan / Larastan | Declared baseline level, recorded in the repository. Level may only increase. |
| Style | Laravel Pint | Clean, or the review does not start |
| Dependency vulnerabilities | `composer audit`, `npm audit` | No unreviewed High/Critical advisory |
| Tests | PHPUnit / Pest | Suite runs; result recorded even where coverage is thin |
| Custom rules | Repository rule set (Section 9.2) | Clean, or each hit is triaged |

Where a gate cannot run, the review report must say so under evidence limitations. An unrunnable gate is an evidence gap, not a pass.

### 9.2 Automated trap rules

Each rule maps to a trap in `sargam-known-review-traps.md`. Implement as PHPStan rules, `grep`/`ripgrep` patterns in a pre-review script, or CI checks — whichever is cheapest for the pattern.

| Rule ID | Detects | Trap |
| --- | --- | --- |
| AUTO-01 | References to deleted files/classes/routes (dangling references) | 2 |
| AUTO-02 | `catch (\Exception` inside a block containing `beginTransaction` | 4 |
| AUTO-03 | `whereDate(`, `orWhereDate(`, `LOWER(` / `CAST(` inside `join`/`where` raw fragments | 5, 20 |
| AUTO-04 | `::all()`, `DB::table(...)->get()` without `select`, `SELECT *` | 6 |
| AUTO-05 | DataTables initialisation without `serverSide: true` | 7 |
| AUTO-06 | `Cache::put`/`remember` added without a matching `forget`/tag invalidation in the same change set | 8 |
| AUTO-07 | `Schema::hasTable(`/`hasColumn(` in controllers, services, or views | 10 |
| AUTO-08 | Stray bytes before `<?php` in any PHP file | 12 |
| AUTO-09 | Committed `.docx`, `.xlsx`, `.pdf`, or image blobs above a size threshold | 14 |
| AUTO-10 | `updateOrInsert(` added or removed | 15 |
| AUTO-11 | `DB::raw(` containing a variable, and `orderBy(` with a request-derived value | 23 |
| AUTO-12 | `env(` used outside `config/` | Section 18 |
| AUTO-13 | `request()->all()` passed to `create(`/`update(`/`fill(` | Section 10.1 |
| AUTO-14 | `{!! ` in Blade | Section 10.3 |
| AUTO-15 | `$guarded = []` or `Model::unguard(` | Section 10.1 |
| AUTO-16 | New migration without `down()` body | Section 14 |

### 9.3 Handling rule output

Automated hits are **candidates**, never confirmed findings. Each hit is either promoted to a finding with severity and confidence, or dismissed with a recorded reason. A dismissed hit is added to the rule's suppression list with that reason, so it is not rediscovered on the next run.

### 9.4 Feedback loop

Whenever a finding is raised manually that a rule *could* have caught, add or extend the rule in the same cycle. Whenever a rule produces a majority of false positives across two consecutive reviews, tighten or retire it. Record both in `review-history.md`.

---

## 10. Security review standard

### 10.1 Authentication and authorisation

Check:

- Correct route middleware
- Server-side permission checks
- Object-level authorisation / IDOR
- Export and download permissions
- Role-scope expansion
- Admin-only or sensitive actions
- Protection of another user's records by changing IDs
- **Mass assignment:** `$fillable` / `$guarded` on any model that receives request data; no `request()->all()` or `$request->except(...)` piped into `create()`, `update()`, or `fill()`; no `$guarded = []` or `unguard()` on models with role, status, ownership, amount, or approval columns
- **Authorisation is server-side only.** A hidden or disabled UI control is never the control.

### 10.2 Input and injection

Check:

- Request validation and normalisation
- SQL value binding
- Strict allow-listing for dynamic table names, column names, sorting, and raw SQL fragments
- Command injection
- Path traversal
- SSRF and unsafe outbound URLs
- Open redirects
- Mass-assignment surface (cross-reference 10.1)

Parameter binding protects values, not SQL identifiers.

### 10.3 Output and sensitive information

Check:

- Escaped Blade output
- Use of `{!! !!}`
- Stored and reflected XSS
- PII in API responses, exports, logs, and exceptions
- Secrets or credentials in source
- Excessive data returned to users

### 10.4 File handling

Check:

- Extension, MIME type, and size validation
- Safe generated filenames
- Storage path and public exposure — confirm the disk's `visibility` setting, not only the controller logic
- Download authorisation
- Path traversal
- Malware scanning or compensating controls where required

### 10.5 Dependencies and supply chain

Any change to `composer.json`, `composer.lock`, `package.json`, lockfiles, CI workflow files, Dockerfiles, or `config/` requires explicit review.

Check:

- Is each newly added package necessary, maintained, and from a known source?
- Does the lockfile change match the manifest change, with no unexplained transitive jumps?
- Does `composer audit` / `npm audit` report advisories against the new versions?
- Do CI workflow changes alter what runs before merge, or expose secrets to untrusted contexts?
- Do post-install or build scripts execute anything new?
- Does a framework or library major-version bump change defaults relied on elsewhere?

An unexplained lockfile change in an otherwise unrelated PR is a scope-reconciliation finding.

### 10.6 Secret exposure — response path

On a confirmed secret, credential, token, or key in source, git history, logs, or a committed artifact:

1. Treat it as compromised regardless of how briefly it was exposed.
2. Rotate the credential immediately — this is the primary remediation, not removing the line.
3. Determine exposure window and who could have read it (repository visibility, forks, clones, CI logs).
4. Purge from history where the repository history is or was reachable by anyone who should not hold the secret.
5. Move the value to environment configuration and add or confirm a secret-scanning gate.
6. Record the incident in `review-history.md` under the incident log.

The PR cannot be approved on step 2 alone.

---

## 11. Concurrency and idempotency

Review especially for billing, payments, approvals, imports, inventory, scheduled jobs, and notifications.

Check:

- Can the same request run twice?
- Can two users approve or update the same record concurrently?
- Can a retry duplicate data, amounts, or messages?
- Is uniqueness protected by a database constraint?
- Can status transitions be repeated or skipped?
- Can lost updates occur?
- Is `lockForUpdate()` or optimistic locking required?
- Is the operation safe when a queue job is retried?

An application-level `exists()` check followed by insert is not sufficient for critical uniqueness without a database constraint.

### 11.1 Scheduled tasks and queued jobs

Check:

- Scheduled commands that can overlap use `withoutOverlapping()`, and the lock expiry is sane for the task's realistic runtime.
- `$tries`, `$backoff`, and `$timeout` are set deliberately on jobs that touch money, approvals, or external systems.
- A `failed()` handler exists where partial work must be compensated or flagged.
- Failed jobs are monitored — a job that fails silently into `failed_jobs` with nobody watching is an operational finding.
- Jobs are safe to run twice, because retries and redeliveries will happen.
- A job dispatched inside a transaction uses `afterCommit`, or the queue is configured with `after_commit` — otherwise the worker may pick it up before the row exists.

---

## 12. Transaction review standard

Prefer:

```php
DB::transaction(function () {
    // Database operations
});
```

For manual transactions, verify:

```php
DB::beginTransaction();

try {
    // Database operations

    DB::commit();
} catch (\Throwable $e) {
    DB::rollBack();
    throw $e;
}
```

Check:

- All related writes that form one business action are atomic
- Every failure path rolls back
- Early returns cannot bypass commit or rollback
- `\Throwable`, not only `\Exception`, is handled
- Exceptions are not swallowed
- External APIs, emails, notifications, and file operations do not occur before commit unless compensation exists
- Post-commit work uses an after-commit mechanism where appropriate
- The transaction is as short as practical

A read-then-write sequence is a finding only when concurrent changes can alter the decision, multiple writes must succeed atomically, or a business invariant must be protected. Do not require a transaction for every independent single-row update.

---

## 13. Developer database guidelines — G1 to G8

### G1 — Restrict rows and columns

Avoid unrestricted queries such as:

```php
DB::table('table_name')->get();
```

or:

```sql
SELECT * FROM table_name;
```

Retrieve only required columns and apply appropriate filters where applicable.

A full-model read is a finding when it materially affects performance, memory, sensitive-data exposure, or payload size. Consider result-set size, table width, frequency, model accessors, later updates, and actual fields used. Use Appendix A.2 for volume; where the table is absent from Appendix A.2, state that volume is unverified.

### G2 — Pagination

Pagination must be implemented for listing, search, history, transaction, and reporting screens where data is unbounded or expected to grow.

```php
Model::select(['id', 'name', 'status'])
    ->where('status', 1)
    ->paginate(20);
```

Review pagination at the endpoint-query level, not merely at the controller-file level. Exports and small, documented, bounded reference datasets may require different treatment.

### G3 — Large batch processing

For large datasets, use:

```php
chunk()
chunkById()
cursor()
lazy()
```

Do not load the complete dataset into application memory unless clearly justified.

### G4 — Repeated database calls

Remove duplicate and unnecessary calls triggered during page load, refresh, API calls, AJAX requests, and repeated helper execution.

### G5 — Queries inside loops / N+1

Avoid executing database queries inside loops. Use eager loading, `withCount()`, grouped aggregates, `whereIn()` prefetching, or keyed lookup maps.

Report N+1 only after confirming that the receiver is a query builder or relation, not an in-memory Collection, and that the loop can grow.

**Hidden N+1 sources to check explicitly:**

- Model accessors and `$appends` that query, especially on models rendered in lists or serialised to API responses
- Relationship access inside Blade loops and inside DataTables row callbacks
- Policy or `can()` checks that load a relation per row
- Observers and model events firing per row inside a bulk operation

### G6 — Manual connection handling

Laravel normally manages request connections. Manually created connections, cursors, and statements must be properly released.

```php
DB::disconnect('connection_name');
```

### G7 — Transaction safety

Transactions must be committed or rolled back on all paths. Prefer `DB::transaction()` and catch `\Throwable` when manual handling is unavoidable.

### G8 — Sargable filtering and schema consistency

Avoid applying functions such as `DATE()`, `LOWER()`, `CAST()`, or `CONVERT()` to filtered or joined indexed columns where it prevents normal index access.

Distinguish:

| Usage | Typical concern |
| --- | --- |
| Function in `WHERE` | May prevent index filtering |
| Function in `JOIN` | Often high impact |
| Function in `ORDER BY` | May cause filesort |
| Function in `GROUP BY` | May require temporary processing |
| Function in `SELECT` only | Usually output formatting, not automatically an index issue |
| Function on a constant | Not the same as wrapping a column |

Do not claim that an existing index is blocked unless the schema confirms the index. Otherwise state that index coverage is unverified.

For a single-day datetime filter, use a half-open range:

```php
$start = Carbon::parse($date, $timezone)->startOfDay();
$endExclusive = $start->copy()->addDay();

$query
    ->where('issue_date', '>=', $start)
    ->where('issue_date', '<', $endExclusive);
```

Avoid `<= 23:59:59`, which can exclude fractional-second values. See Appendix A.1 for the canonical timezone convention.

---

## 14. Migration and deployment review

Check:

- `up()` and `down()` consistency
- Existing-row compatibility
- Data-loss risk
- Large-table locking
- Defaults and nullability
- Foreign keys and unique constraints
- Index coverage
- Backfill strategy
- Renamed or removed columns
- Irreversible operations
- Runtime DDL
- Application/schema deployment order
- Schema-cache invalidation

Ask:

> Can the old application run briefly with the new schema, and can the new application run briefly with the old schema?

### 14.1 Rollback and post-deploy verification

Any change classified Blocker-adjacent, High-risk, or schema-affecting must state, before merge:

| Item | Requirement |
| --- | --- |
| Rollback method | Code revert, `down()` migration, feature flag, or documented forward-fix-only with justification |
| Rollback safety | Whether rolling back after data has been written under the new schema is safe, and what is lost if not |
| Deployment order | Migration-then-code, code-then-migration, or expand/contract across two releases |
| Feature flag | Required where the change alters a money, approval, or high-traffic path and cannot be reverted cleanly |
| Post-deploy verification | Named checks to run after release: specific screen, specific query, error-rate or job-queue observation, and a time window |
| Owner and window | Who performs verification and when |

"Forward-fix only" is acceptable, but only when stated deliberately rather than discovered during an incident.

---

## 15. API contract review

Check:

- Request fields and validation
- Required versus optional fields
- Response shape
- HTTP status codes
- Error format
- Pagination metadata
- Null and empty-state behaviour
- Date and number formatting
- Backward compatibility
- Authentication and rate limiting
- Export and download behaviour

Classify contract changes as backward compatible, potentially breaking, or confirmed breaking.

---

## 16. Frontend and Blade review

Check:

- Duplicate AJAX calls
- Missing loading and error states
- Multiple-click duplicate submission
- Client/server validation mismatch
- `readonly` versus `disabled`
- `readonly` combined with `required`
- Unsafe raw HTML output
- Missing CSRF token
- Broken route or asset references
- Large JSON embedded in Blade
- Client-side pagination over an unbounded backend result
- Accessibility issues that block form use

---

## 17. Observability and audit logging

Observability is not an optional nicety on sensitive paths. Treat the following as mandatory checks, not advisory improvements.

**Audit logging is required for:**

- Approvals and rejections
- Financial operations — billing generation, adjustments, payments, refunds, write-offs
- Permission and role changes
- Record deletion and bulk updates
- Export and download of personal or financial data
- Administrative overrides of normal workflow

**For each such action, verify the log records:** actor, target record, action, timestamp, and outcome — and does **not** record credentials, full personal data, or full request payloads.

**Also check:**

- Errors are logged with enough context to diagnose, without leaking PII or secrets into log lines or exception messages
- Silent `catch` blocks that swallow an exception without logging are a finding
- New failure modes on background jobs are visible somewhere a human will actually look

A privileged action without an audit trail is a **Medium** finding by default, and **High** where it affects money, permissions, or deletion.

---

## 18. Configuration and environment

Check:

- `env()` is called only inside `config/` files. Elsewhere it returns `null` once configuration is cached — this is a runtime correctness issue, not a style issue.
- New configuration keys have sane defaults and are documented in `.env.example`.
- Filesystem disks used for uploads have the intended `visibility`; a private document on a public disk is a security finding.
- Cache, queue, and session driver assumptions in code match the deployed configuration.
- Debug-only behaviour is guarded and cannot be enabled in production by request input.
- No environment-specific hardcoded values (hostnames, paths, IDs) in application code.

---

## 19. Testing expectations

### 19.1 What must be covered

**Business logic**

- Happy path
- Empty state
- Null and zero values
- Invalid status transition
- Duplicate request

**Transactions**

- Mid-operation failure rolls back
- `Throwable` path rolls back
- External side effect does not occur before commit

**Permissions**

- Allowed role succeeds
- Disallowed role is denied
- User cannot access another user's record

**Date filters**

- Start boundary
- End boundary
- Fractional seconds
- Timezone boundary

**Billing / money**

- Exact amount and rounding
- Duplicate execution
- Missing lookup
- Domain assumptions

**Query optimisation**

- Results remain equivalent
- Query count does not grow linearly
- Large result set remains bounded

### 19.2 Reviewing test quality

Submitted tests are reviewed as code, not counted. A weak test is worse than no test because it produces false confidence.

Check:

- **Does it assert the thing that matters?** A test asserting only HTTP 200 on a billing endpoint does not test billing.
- **Would it fail if the bug were reintroduced?** If not, it is not a regression test. Where practical, confirm by reasoning about the pre-fix code.
- **Are the assertions specific?** `assertNotNull` and `assertTrue(count > 0)` rarely pin behaviour. Prefer exact values on money and status.
- **Is the fixture realistic?** Tests built only on freshly seeded happy-path data miss the messy legacy rows that cause production defects.
- **Is it independent?** Order dependence, shared static state, and reliance on real time (`now()` without freezing) produce flaky or falsely green suites.
- **Does mocking hide the defect?** Mocking the exact component under test, or mocking the database on a query-correctness test, defeats the purpose.
- **Negative cases:** at least one test proving the guard actually denies.

A PR that fixes a Blocker or High finding is expected to add a test that would have caught it. Where that is impractical, the reason is recorded.

---

## 20. Standing evidence checklist

For performance or database remediation, request or record:

- [ ] Query / API details
- [ ] Root cause
- [ ] Changes implemented
- [ ] Before-and-after execution time
- [ ] Pagination validation
- [ ] Database connection and transaction validation
- [ ] QA test evidence
- [ ] Query plan or index evidence where relevant
- [ ] Rollback and post-deploy verification plan, where Section 14.1 applies

Static review must clearly identify which evidence was not supplied.

---

## Appendix A — Canonical platform facts

These exist so that reviewers stop re-deriving, or guessing, the same facts. Anything marked TBD must be resolved by the named owner; until then, findings that depend on it are capped at **Medium** confidence.

### A.1 Time and dates

| Fact | Value |
| --- | --- |
| Application timezone (`config/app.php`) | *TBD — record on adoption* |
| Database storage timezone | *TBD* |
| Display timezone for users | *TBD* |
| Column types used for dates | *TBD — `DATE`, `DATETIME`, `TIMESTAMP` per convention* |
| Fractional-second precision in use | *TBD* |

Until these are recorded, every date-boundary finding must state its timezone assumption explicitly.

### A.2 Data volume reference

Severity for G1, G2, G3, G5, and G8 findings depends on realistic volume. Populate this from the production database and refresh at each audit.

| Table | Approx. rows | Growth rate | Width | Bounded / growing | Last measured |
| --- | --- | --- | --- | --- | --- |
| *(key transactional tables)* | TBD | TBD | TBD | TBD | TBD |
| *(key reference tables)* | TBD | TBD | TBD | Bounded? | TBD |

Rules:

- A table absent from this appendix has **unverified volume**; state that in the finding rather than assuming.
- Do not assign High severity to an unbounded-read finding on a table whose size is unknown — use Medium with an evidence request.
- Refresh at every full audit; note the measurement date.

### A.3 Index inventory

| Table | Index | Columns | Confirmed from | Date |
| --- | --- | --- | --- | --- |
| TBD | TBD | TBD | migration / `SHOW INDEX` | TBD |

Do not claim an index is blocked unless it appears here or in a migration.

---

## Appendix B — Framework governance

- **Versioning:** semantic-ish. Adding a check is a minor version; changing severity semantics, verdict rules, or the confidence mapping is a major version. Record the version in every report.
- **Change control:** changes to this document, the PR guidelines, or the audit guidelines are proposed by any reviewer and approved by the framework owner. Trap additions do not require the same weight — see the traps document.
- **Effectiveness:** measured by escape rate, tracked in `review-history.md`. If defects are escaping in a category, the gap is in this framework, not only in the code.
- **Review cadence:** every 6 months, or on change to schema conventions, role model, cache architecture, framework version, or tooling.

---

## Changelog

**v2.0**

- Added Section 6, severity × confidence mapping, and defined "confirmed."
- Added Section 4.1, definition of "material Medium."
- Added Section 9, pre-review automation baseline with rule IDs AUTO-01 to AUTO-16.
- Added `Detection` and `Closure evidence` to the mandatory finding format.
- Added mass assignment (10.1), dependencies and supply chain (10.5), secret-exposure response path (10.6).
- Added scheduled-task and queued-job safety (11.1).
- Added hidden N+1 sources to G5; Appendix A.2/A.3 referenced from G1 and G8.
- Added rollback and post-deploy verification (14.1).
- Added Section 17, observability and audit logging, as mandatory rather than advisory.
- Added Section 18, configuration and environment.
- Added Section 19.2, reviewing test quality.
- Added Appendix A (canonical platform facts) and Appendix B (framework governance).
- Clarified that Legacy / Repository-wide findings are permitted in PR reports under follow-up only.
