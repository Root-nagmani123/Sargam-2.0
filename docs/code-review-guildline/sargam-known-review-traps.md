# Sargam 2.0 — Known Review Traps

> **Purpose:** Maintain verified, recurring codebase-specific risks that should be checked proactively during PR reviews and codebase audits.
> **Rule:** Add a trap only when it is likely to recur, independently understandable, not already covered by a general rule, and supported by verified source evidence.

| Field | Value |
| --- | --- |
| Document version | v2.0 |
| Owner | Review framework owner |
| Revalidation cadence | Every trap revalidated at least every 6 months |
| Last register-wide review | *(record on adoption)* |

---

## How this register works

Each trap carries the same metadata block. A trap without it is not usable, because a reviewer cannot tell whether it is still true.

| Field | Meaning |
| --- | --- |
| **Applies to** | PR review · Codebase audit · Both |
| **State** | Active · Stale · Archived · Candidate |
| **Detection** | How to check it consistently |
| **Rule** | Automated rule ID from the shared standards Section 9.2, or `Manual only` |
| **Verified** | Where and when the trap was last confirmed against source |
| **Revalidate when** | The change that invalidates the trap's evidence |

### Lifecycle states

| State | Meaning | Usable in a report? |
| --- | --- | --- |
| **Candidate** | Observed once; may not recur | As a prompt to check; not citable as an established pattern |
| **Active** | Verified within the last 6 months | Yes |
| **Stale** | Not verified for more than 6 months, or `Verified` not recorded | **Check it, but do not cite it as fact.** Any finding relying on a Stale trap is capped at Medium confidence until re-verified against current source |
| **Archived** | Fixed repository-wide, or no longer applicable | No. Retained with the reason, so it is not re-raised as a new discovery |

### Maintenance rules

- **Staleness:** a trap unverified for 6 months becomes Stale automatically. Re-verifying is cheap — confirm one current example in source and update the date.
- **Retirement:** when a trap's pattern is eliminated repository-wide, or an automated rule now blocks it at CI, move it to **Archived** with the date and evidence. Do not delete — deletion causes rediscovery.
- **Promotion to automation:** any trap detectable by pattern should carry a rule ID. A trap that stays `Manual only` for two cycles is reviewed to confirm automation is genuinely impractical.
- **Provenance:** each trap must trace to a PR, audit, or incident. A trap with no provenance is a Candidate.
- **Incident feedback:** after any production defect, ask whether an existing trap covered it. If not, add one and reference the incident.

> **Retrofit note (v2.0):** Traps 1–25 were carried forward from v1.0, which did not record verification metadata. Where provenance can be traced to `review-history.md`, it is recorded below; the remaining `Verified` fields read *not recorded* and those traps are **Stale** until a reviewer confirms one current example. Clearing this backlog is the first maintenance task on this register.

---

## ⚠ Provenance warning — read before citing any trap

**This register was produced by automated reviews under v1.0, which had no evidence rule.** Reviews at that time were not required to cite the source line supporting an assertion, and no human verified the results. Some traps may therefore describe general Laravel patterns rather than confirmed Sargam behaviour, and specific claims — table names, column pairs, file paths — may not correspond to anything in the repository.

Trap 9 is the visible example: it names `fc_registration_master` and `user_credentials` as a collation problem, then immediately hedges that the fact needs reverifying. That hedge suggests the original author was not certain the example was real.

Until re-verification is complete:

- **The entire register is `agent-asserted, unverified`.** No trap may be cited as established fact about this codebase.
- A trap is a **prompt to check**, not a conclusion. Confirm the pattern in current source before raising a finding on it.
- Any finding relying on an unverified trap is capped at **Medium confidence**, per the staleness rule.
- **First re-verification must quote the current source line and SHA that proves the trap.** A trap that cannot be proven in current source is proposed for archival, not carried forward.

The compounding risk this guards against: an unverified assertion enters the register, the next review treats it as codebase knowledge, and a finding is built on it. The staleness rule blocks that chain — do not bypass it by re-dating a trap without evidence.

---

## Trap register

### 1. Scope creep hidden in narrowly titled PRs

**Applies to:** PR review · **State:** Stale · **Rule:** Manual only

**Description:** Changes unrelated to the title may be described as minor.

**Detection:** Compare stated scope against actual files, defaults, flags, TTLs, routes, permissions, lockfiles, CI workflows, and config. Diff the file list against the description sentence by sentence. Any file the description does not account for is a scope question.

**Verified:** PR #251 — a UI-titled PR also carried cache, default, and declaration-date changes. Date and SHA not recorded.

**Revalidate when:** PR conventions or template change.

---

### 2. Deleted files with surviving references

**Applies to:** Both · **State:** Stale · **Rule:** AUTO-01

**Description:** Deleted controllers, models, views, or routes can leave dangling references and cause runtime failures.

**Detection:** For each deleted path, search for the class name, route name, view name, and file stem across app, routes, views, config, and JS. Run before deep review — a dangling reference invalidates other conclusions.

**Verified:** PR #250 — dangling-reference pre-scan completed. Date and SHA not recorded.

**Revalidate when:** Autoloading, route caching, or view-namespace conventions change.

---

### 3. Shared-utility ripple

**Applies to:** Both · **State:** Stale · **Rule:** Manual only

**Description:** Changes to `app/helpers.php`, `app/Support/*`, middleware, base classes, or shared cache utilities may affect many callers.

**Detection:** Enumerate callers of every changed shared function or method. Record the count in the report. For a signature, default, or return-shape change, examine each caller, not a sample. Automatically triggers a Tier 2 review.

**Verified:** Not recorded.

**Revalidate when:** `app/Support` structure or helper-loading strategy changes.

---

### 4. Transactions caught on `\Exception` only

**Applies to:** Both · **State:** Stale · **Rule:** AUTO-02

**Description:** Manual transactions should handle `\Throwable`, ensure rollback on all paths, rethrow appropriately, and avoid external side effects before commit.

**Detection:** For each `beginTransaction`, locate every exit path — return, throw, continue, break — and confirm each reaches commit or rollback. Check the catch type. Check for mail, HTTP, file, or queue side effects before commit.

**Verified:** Not recorded.

**Revalidate when:** PHP version or error-handling conventions change.

---

### 5. Index-defeating filters and joins

**Applies to:** Both · **State:** Stale · **Rule:** AUTO-03

**Description:** Common patterns include `whereDate`, `orWhereDate`, `CAST(...)` in joins, and `LOWER(...)` in filters.

**Detection:** Locate the function wrapper, then confirm column type, index evidence (shared standards Appendix A.3), and realistic volume (Appendix A.2) before assigning severity. Distinguish `WHERE` / `JOIN` / `ORDER BY` / `SELECT`-only usage per the G8 table.

**Verified:** Codebase audit, G1–G8 solution-wide scan. Date and SHA not recorded.

**Revalidate when:** Schema, index set, or database engine changes.

---

### 6. Unrestricted `SELECT *`, `::all()`, and broad `get()`

**Applies to:** Both · **State:** Stale · **Rule:** AUTO-04

**Description:** Risk is highest for wide or growing transactional tables, cache payloads, exports, and dropdowns that should be searchable.

**Detection:** Confirm the receiver is a query builder, not a Collection. Check the table against Appendix A.2. Check which fields are actually consumed downstream, including model accessors and `$appends`. Bounded reference tables are not automatically findings.

**Verified:** Codebase audit, G1–G8 solution-wide scan. Date and SHA not recorded.

**Revalidate when:** Table volumes change materially, or the volume reference is refreshed.

---

### 7. Client-side DataTables on growing datasets

**Applies to:** Both · **State:** Stale · **Rule:** AUTO-05

**Description:** A DataTable without server-side processing may ship all rows to the browser.

**Detection:** Check `serverSide` in the initialiser and confirm the backend endpoint is bounded and paginated. Confirm the expected maximum size against Appendix A.2. Also check whether the row callback issues per-row queries (see Trap 26).

**Verified:** Not recorded.

**Revalidate when:** DataTables version or the shared table component changes.

---

### 8. Cache TTL and invalidation

**Applies to:** Both · **State:** Stale · **Rule:** AUTO-06

**Description:** Any new or changed cache needs a correct invalidation path.

**Detection:** For each cache key written, find every write path to the underlying data and confirm invalidation on each. Check TTL against data volatility. Confirm failure or empty results are not cached for the full TTL without deliberate design. Confirm key construction cannot collide across tenants, users, or roles.

**Verified:** PR #253 — Redis cache implementation; invalidation and null-handling assessed as good. Date and SHA not recorded.

**Revalidate when:** Cache driver, key convention, or tagging support changes.

---

### 9. Collation and type mismatch

**Applies to:** Both · **State:** Stale · **Rule:** Manual only

**Description:** Cross-table comparisons may force `TRIM(CAST(...))` or `CONVERT(...)` wrappers. Prefer schema alignment over repeated query-level conversion.

**Detection:** For each cross-table join or comparison, confirm both columns' type, length, and collation from current schema. A conversion wrapper in application code is a symptom — trace it to the schema mismatch.

**Known example:** `fc_registration_master` and `user_credentials` have historically required collation/type reconciliation. **This is unverified against current schema — confirm before relying on it.**

**Verified:** Codebase audit. Date and SHA not recorded.

**Revalidate when:** Any migration touching these tables, or a database or collation-default change.

---

### 10. Schema introspection during requests

**Applies to:** Both · **State:** Stale · **Rule:** AUTO-07

**Description:** Repeated raw `Schema::hasTable()` or `Schema::hasColumn()` calls in hot paths.

**Detection:** Locate calls outside migrations and console commands. Assess call frequency per request. Prefer project-approved cached schema helpers.

**Verified:** Not recorded.

**Revalidate when:** A cached schema helper is introduced or changed.

---

### 11. Legal-form and readonly-field correctness

**Applies to:** Both · **State:** Stale · **Rule:** Manual only

**Description:** Hardcoded declarations, dates, and `readonly + required` combinations. A required readonly field without server-side prefill can block submission.

**Detection:** For each form, list readonly fields and confirm each is server-prefilled on both create and edit. Check declaration text and dates for hardcoded values that will age. Confirm `readonly` versus `disabled` semantics match the intended submission behaviour.

**Verified:** PR #251 — declaration-date changes reviewed. Date and SHA not recorded.

**Revalidate when:** The form component library or declaration wording changes.

---

### 12. Migration text, timestamps, and runtime DDL

**Applies to:** Both · **State:** Stale · **Rule:** AUTO-08, AUTO-16

**Description:** Stray text before `<?php`, `updateOrInsert` resetting `created_at`, large-table locks, and runtime DDL that fails to invalidate schema caches.

**Detection:** Check the first bytes of every migration file. Check `down()` exists and reverses `up()`. Check timestamp columns are preserved across upsert paths. Check for DDL executed outside migrations.

**Verified:** Not recorded.

**Revalidate when:** Framework migration behaviour or deployment tooling changes.

---

### 13. Limited automated test coverage

**Applies to:** Both · **State:** Stale · **Rule:** Manual only

**Description:** Where automated coverage is absent, static conclusions must be clearly separated from QA evidence.

**Detection:** Run the suite and record the actual result before stating anything about coverage. **Reverify the current repository test state at each review** rather than repeating a previous claim — this trap is the one most likely to become false silently as tests are added.

**Verified:** Not recorded.

**Revalidate when:** Any test infrastructure change, or at every audit.

---

### 14. Binary artifacts committed to the repository

**Applies to:** PR review · **State:** Stale · **Rule:** AUTO-09

**Description:** `.docx`, `.xlsx`, `.pdf`, and image blobs cannot be meaningfully line-reviewed and increase repository size.

**Detection:** Flag binary additions above the size threshold. Confirm whether the binary is a required product artifact. Confirm it is not hiding a meaningful change — an updated compiled asset can carry logic.

**Verified:** PR #253 — binary artifact issue raised. Date and SHA not recorded.

**Revalidate when:** Asset pipeline or storage strategy changes.

---

### 15. `updateOrInsert` refactors

**Applies to:** Both · **State:** Stale · **Rule:** AUTO-10

**Description:** Replacing `updateOrInsert` with manual read/update/insert changes semantics.

**Detection:** Confirm semantic equivalence, uniqueness assumptions, model-event behaviour (Query Builder bypasses Eloquent events), timestamp handling, and concurrency safety. A manual read-then-write without a unique constraint is race-prone — see Trap 21.

**Verified:** Not recorded.

**Revalidate when:** Eloquent upsert conventions change.

---

### 16. String-keyed cross-table matching

**Applies to:** Both · **State:** Stale · **Rule:** Manual only

**Description:** Matching by formatted text such as `"June 2026"` is fragile, especially on financial paths.

**Detection:** For each cross-table match, identify the key's type and how each side is generated. Any formatted, localised, or case-sensitive string used as a join or lookup key is a finding. Check both producers for format drift.

**Verified:** PR #249 — Estate fixes; string-key matching raised. Date and SHA not recorded.

**Revalidate when:** The affected schema is normalised to numeric or canonical keys.

---

### 17. Domain assumptions on money paths

**Applies to:** Both · **State:** Stale · **Rule:** Manual only

**Description:** Billing, units, amounts, and meter logic may rely on unstated real-world assumptions.

**Detection:** For each money calculation, write down the assumption in plain language and require domain-owner confirmation. Where the assumption cannot be confirmed, the finding is **Unable to conclude** for that area, not an approval. Require tests for meter-change, missing-record, and rounding cases.

**Verified:** PR #249 — money-path assumptions raised. Date and SHA not recorded.

**Revalidate when:** Billing rules, tariffs, or meter handling change.

---

### 18. Authorisation scope expansion

**Applies to:** PR review · **State:** Stale · **Rule:** Manual only

**Description:** Widening a permission condition can expose data or actions to new roles.

**Detection:** Diff the condition and enumerate the set of roles allowed before and after. List newly allowed roles by name in the report and confirm intent explicitly with the author. Never infer intent from the PR title. Automatically triggers a Tier 2 review.

**Verified:** PR #249 — authorisation expansion raised. Date and SHA not recorded.

**Revalidate when:** The role model or permission package changes.

---

### 19. New queries on legacy or unmigrated tables

**Applies to:** Both · **State:** Stale · **Rule:** Manual only

**Description:** A table with no repository migration has unknown index and constraint coverage.

**Detection:** For each table queried, confirm a migration exists in the repository. If not, request schema evidence and record the table as unverified in Appendix A.2/A.3. Do not assert index behaviour on such tables.

**Verified:** PR #249 — index evidence required. Date and SHA not recorded.

**Revalidate when:** Legacy tables are brought under migration control.

---

### 20. Incomplete `whereDate()` replacement

**Applies to:** Both · **State:** Stale · **Rule:** AUTO-03

**Description:** Replacing `whereDate()` with only `>= startOfDay()` is incomplete for a single-day filter.

**Detection:** Confirm both bounds exist and the upper bound is exclusive (`>= start`, `< nextDayStart`). Confirm the timezone used matches Appendix A.1. Reject `<= 23:59:59`, which excludes fractional seconds.

**Verified:** Not recorded.

**Revalidate when:** The canonical timezone convention or date column types change.

---

### 21. Application-level uniqueness without a database constraint

**Applies to:** Both · **State:** Stale · **Rule:** Manual only

**Description:** `exists()` followed by insert is race-prone.

**Detection:** For each uniqueness rule enforced in code, confirm a matching database unique constraint and duplicate-handling path. Where the constraint is absent, severity depends on whether a duplicate causes a wrong money, approval, or identity outcome.

**Verified:** Not recorded.

**Revalidate when:** Constraints are added, or the affected tables change.

---

### 22. Duplicate submission and retry safety

**Applies to:** Both · **State:** Stale · **Rule:** Manual only

**Description:** Approvals, imports, billing, payments, inventory, notifications, and jobs must remain safe when retried or submitted twice.

**Detection:** For each such operation ask: what happens on double-click, on browser resubmit, and on queue retry? Confirm an idempotency key, a status guard, a unique constraint, or a lock. Confirm `withoutOverlapping()` on schedulable commands and sensible `$tries`/`$timeout` on jobs. Confirm jobs dispatched inside transactions use `afterCommit`.

**Verified:** Not recorded.

**Revalidate when:** Queue driver or scheduler configuration changes.

---

### 23. Raw dynamic order/filter expressions

**Applies to:** Both · **State:** Stale · **Rule:** AUTO-11

**Description:** Request-controlled column names, table names, sorting directions, or raw SQL fragments require strict allow-listing.

**Detection:** Trace each `DB::raw`, `orderByRaw`, `whereRaw`, and dynamic `orderBy` argument back to its source. If any part derives from request input, require an allow-list — binding does not protect identifiers.

**Verified:** Not recorded.

**Revalidate when:** Query-building helpers or the DataTables server-side handler change.

---

### 24. File upload and document access

**Applies to:** Both · **State:** Stale · **Rule:** Manual only

**Description:** Upload and download paths carry type, size, path, permission, and traversal risk.

**Detection:** Verify extension and MIME validation, size limits, generated filename safety, storage disk **visibility setting**, download authorisation at the object level, and path-traversal protection. Confirm private documents are not on a public disk regardless of controller logic.

**Verified:** Not recorded.

**Revalidate when:** Filesystem disk configuration or storage provider changes.

---

### 25. Migration deployment compatibility

**Applies to:** Both · **State:** Stale · **Rule:** Manual only

**Description:** Old code may run briefly with the new schema and new code briefly with the old schema during rollout.

**Detection:** Answer both directions of the coexistence question explicitly. Where the answer is no, require expand/contract sequencing across two releases, and a documented rollback method per shared standards Section 14.1.

**Verified:** Not recorded.

**Revalidate when:** Deployment strategy changes — zero-downtime, blue/green, rolling.

---

### 26. Per-row queries in list rendering

**Applies to:** Both · **State:** Candidate · **Rule:** Manual only

**Description:** N+1 introduced not by an obvious loop but by a model accessor, an `$appends` attribute, a policy check, or a DataTables row callback executed once per row.

**Detection:** For any list or export, check the model's accessors and `$appends` for queries, check per-row `can()` / policy calls, and check Blade loops and row callbacks for relation access without eager loading. This hides from a loop-shaped scan, which is why it needs its own trap.

**Verified:** Not yet verified against Sargam source — raised as a Candidate during the v2.0 framework review.

**Revalidate when:** Confirm or retire after the next audit.

---

### 27. Mass assignment on privileged columns

**Applies to:** Both · **State:** Candidate · **Rule:** AUTO-13, AUTO-15

**Description:** `request()->all()` into `create()`/`update()`/`fill()`, or `$guarded = []`, on models carrying role, status, ownership, amount, or approval columns.

**Detection:** For each model receiving request input, list its columns and identify which are privilege- or money-bearing. Confirm `$fillable` excludes them, or that the controller passes an explicit field list.

**Verified:** Not yet verified against Sargam source — raised as a Candidate during the v2.0 framework review.

**Revalidate when:** Confirm or retire after the next audit.

---

### 28. `env()` outside configuration files

**Applies to:** Both · **State:** Candidate · **Rule:** AUTO-12

**Description:** `env()` returns `null` once configuration is cached, so a call outside `config/` silently changes behaviour between environments.

**Detection:** Scan for `env(` outside `config/`. Each hit is a correctness finding, not a style note, if configuration caching is used in any environment.

**Verified:** Not yet verified against Sargam source — raised as a Candidate during the v2.0 framework review.

**Revalidate when:** Confirm or retire after the next audit.

---

## Template for new traps

```text
### <number>. <trap name>

**Applies to:** PR review | Codebase audit | Both
**State:** Candidate | Active | Stale | Archived
**Rule:** AUTO-<nn> | Manual only

Description:
<one-line recurring risk>

Detection:
<how to check it consistently>

Verified:
<PR / audit / incident, date, branch or SHA>

Revalidate when:
<schema, role model, cache architecture, tooling, framework version, etc. changes>
```

---

## Archive

Move retired traps here rather than deleting them.

| Trap | Archived on | Reason | Evidence |
| --- | --- | --- | --- |
| *(none yet)* | | | |

---

## Changelog

**v2.0**

- Added the metadata block — State, Detection, Rule, Verified, Revalidate when — and retrofitted it to traps 1–25.
- Added lifecycle states, the 6-month staleness rule, and the Medium-confidence cap on findings that rely on Stale traps.
- Added the retirement and Archive mechanism so retired traps are not rediscovered.
- Linked every automatable trap to an automated rule ID from the shared standards.
- Traced provenance to `review-history.md` entries where possible; marked the remainder as unverified.
- Added Candidate traps 26 (per-row queries in list rendering), 27 (mass assignment on privileged columns), and 28 (`env()` outside config).
