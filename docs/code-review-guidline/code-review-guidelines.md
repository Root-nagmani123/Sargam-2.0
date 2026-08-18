# Sargam 2.0 — Code Review Guidelines

> **Status:** Local reference (NOT committed to the repository).
> **Location:** `C:\Workspace\LBSNAA\code-review\code-review-guidelines.md`
> **Applies to:** every pull-request review of `Root-nagmani123/Sargam-2.0`.
> **Purpose:** the standing standard so obvious process, focus order, and known traps
> don't have to be restated each time. Read this before starting any PR review and apply
> it without prompting.

---

## 0. How this document is used

- This is the **review standard**. When a review is requested, follow the method (§2),
  work the focus order (§3), audit against the developer guidelines (§4), and watch for the
  codebase-specific traps (§5).
- The verdict is driven by **Breaking-change + Logic + Security**. Coding-standard
  (guideline) findings are "conditions on approval," not usually blockers on their own.
- Deliverable: **one local report per PR** (see §6), written to
  `C:\Workspace\LBSNAA\code-review\`, **outside the repository**. Nothing is committed,
  pushed, or posted to GitHub without explicit approval.

---

## 0.1 Living-document update protocol  ⚠️ READ EVERY REVIEW

> **This is a living document. Keep it current so we catch issues earlier over time.**
>
> **At the end of every PR review, before writing the final report, update this file:**
>
> 1. **New recurring trap?** If the review surfaced an issue type not already in §5 (Known
>    failure modes), **add it to §5** — with a one-line description and, where useful, a
>    file/pattern example. The test for "add it": *would I want to check for this proactively
>    on the next PR?* If yes, it belongs in §5.
> 2. **New review information?** If we learned something about how the codebase works, a new
>    convention, a new tool/environment quirk, or a new author-side pattern, fold it into the
>    relevant section (§4 targets, §5 traps, §9 environment).
> 3. **Log the review** in §10 (Reviews on record) — PR number, title, verdict, one-line outcome.
> 4. **Guideline text (§4) is canonical and verbatim — do not edit it.** New *checks* go in §5,
>    not §4. §4 changes only when the team issues a revised official guideline.
> 5. Note the update in the report's closing line (e.g. "Added trap #14 to the review guidelines").
>
> The goal: each review makes the next one faster and more complete. A trap found once should
> never have to be re-discovered from scratch.

---

## 1. Reviewer stance

- **Verify against source, not the diff summary or the PR description.** Check out the PR
  head into an isolated git worktree and read the real files.
- **Reconcile description vs. code** continuously. Most real findings so far came from the
  gap between what a PR *says* and what it *does*.
- **Report faithfully.** State what is broken, what is clean, and what could not be verified.
  Give every finding a `file:line:function`, a concrete failure scenario, and a remedy.
- **Static analysis only.** No execution timings, query-log numbers, or load results are
  produced here — those need the QA environment and must be requested from the author.

---

## 2. Review method (the repeatable process)

1. **Metadata** — `gh pr view <n> --json title,body,author,baseRefName,headRefName,state,additions,deletions,changedFiles,commits`.
2. **Diff + worktree** — save `gh pr diff <n>` and check out the head SHA into a worktree
   (`git worktree add --detach <path> <sha>`); **remove the worktree when done**.
3. **Shape first** — `git diff --numstat` to see what is added vs. deleted vs. rewritten,
   and classify each file (logic / view / config / migration / test).
4. **Work the focus order** (§3) against the real code.
5. **Audit against G1–G8** (§4) for any changed query/DB code. **For any PR that touches DB
   operations, produce the explicit per-operation validation table (§4.1) — not just a summary
   verdict.** Enumerate every `DB::`/Eloquent operation the PR added or changed and validate each
   against the relevant clauses; explicitly confirm transaction wrapping (G7) and column selection
   (G1), and mark pre-existing issues as such (not new violations).
6. **List verified-clean items** — what was checked and cleared, not only what failed.
7. **Write the report** (§6) and clean up the worktree.

---

## 3. Focus order & severity weighting

Ordered by **severity-per-effort** — cheapest high-signal checks first.

### 3.1 Breaking changes / blast radius — **first, always**
- Does the PR match its own description, or carry **unadvertised** changes?
- **Dangling references** — deleted controllers / models / views / routes still referenced
  anywhere → 500s.
- **Shared-code ripple** — changes to a helper, middleware, base class, or `Support/`
  utility that many callers depend on. Enumerate the callers.
- **Contract changes** — modified function signatures, return shapes, removed DB columns,
  changed defaults (TTLs, flags).

### 3.2 Logic / correctness — **second**
- Does it do what it claims, correctly? Edge cases: empty state, first-time use, missing
  prefill, null/zero.
- Data integrity: no silent data loss, no wrong values persisted, transactions safe.
- Runtime/syntactic breakage (stray text, wrong types, missing columns).

### 3.3 Security — **third** (government application — non-negotiable when present)
- AuthN/AuthZ on new routes and endpoints.
- Mass-assignment, SQL injection (raw-query bindings), stored XSS in rendered output.
- Sensitive-data exposure (full rosters, PII in logs/exports).

### 3.4 Coding standards / guidelines (G1–G8) — **fourth**
- The developer query/performance standard (§4). Mostly perf & maintainability → usually
  "before-merge, also fix," not an approve/reject driver on its own.

> **Description-vs-code reconciliation** is not a separate pass — it runs through 3.1 and 3.2.

---

## 4. Developer Guidelines — CANONICAL (verbatim, as provided by the team)

> **This section is the source of truth. Do not edit or paraphrase it.** It is the exact text
> the team issued. The `G1–G8` tags in `[brackets]` are a reviewer index for referencing
> findings in reports — they are annotations, not part of the official text. New *checks* go in
> §5, never here.

Please avoid using unrestricted queries such as: **[G1]**

```
DB::table('table_name')->get();
```

or:

```
SELECT * FROM table_name;
```

Only the required columns should be retrieved, and appropriate filters must be applied wherever applicable.

Pagination must be implemented for all listing, search, history, transaction, and reporting screens. Please use Laravel pagination methods such as: **[G2]**

```
Model::paginate(20);
```

or:

```
Model::select(['id', 'name', 'status'])
    ->where('status', 1)
    ->paginate(20);
```

Large datasets must not be fetched using get() without pagination unless there is a clearly justified requirement.

For large batch processing, please use Laravel methods such as: **[G3]**

```
chunk()
chunkById()
cursor()
lazy()
```

The complete dataset should not be loaded into application memory in a single request.

All queries that are repeatedly triggered during page load, refresh, API calls, loops, or AJAX requests must be reviewed. Duplicate and unnecessary database calls should be removed. **[G4]**

Please avoid executing database queries inside loops. The code must be reviewed for N+1 query issues, and Laravel eager loading should be used wherever required: **[G5]**

```
Model::with('relation')->get();
```

Laravel normally manages database connections during request processing. However, developers must ensure that transactions, cursors, statements, and manually created connections are properly closed or released. **[G6]**

Where manual database connections are created, they must be disconnected after completion:

```
DB::disconnect('connection_name');
```

No manually opened connection should be left active unnecessarily.

Database transactions must be properly committed or rolled back. Prefer using: **[G7]**

```
DB::transaction(function () {
    // Database operations
});
```

If manual transactions are used, proper exception handling must be ensured:

```
DB::beginTransaction();

try {
    // Database operations

    DB::commit();
} catch (\Throwable $e) {
    DB::rollBack();
    throw $e;
}
```

Please ensure that no transaction is left open. An uncommitted or unrolled-back transaction can hold database locks and impact other users and application requests.

Avoid applying functions such as DATE(), LOWER(), or CAST() directly on indexed columns in the WHERE condition, as this may prevent index utilization. **[G8]**

For example, avoid:

```
->whereDate('issue_date', '>=', '2026-06-19')
```

Where possible, use a direct date-time range:

```
->where('issue_date', '>=', '2026-06-19 00:00:00')
```

Also, retrieve only the required fields:

```
Model::select(['id', 'name', 'status'])->get();
```

Avoid retrieving all columns when only a few fields are required.

---

For the module currently under testing, the Development and QA teams must immediately review the following:

- All listing and search APIs
- Pagination implementation
- Usage of get() on large tables
- Queries executing inside loops
- N+1 query issues
- Repeated API and AJAX calls
- Database transaction handling
- Manual connection creation and closure
- Slow-running queries
- Full-table scans
- Connection pool and active connection utilization
- Memory utilization during large data retrieval

Please treat this as an immediate action item.

The concerned module developer / team must complete the review, fix the identified issues, and share confirmation along with the following details:

- Query / API details
- Root cause
- Changes implemented
- Before-and-after execution time
- Pagination validation
- Database connection and transaction validation
- QA test evidence

No large dataset should be fetched without pagination. No database query should be executed repeatedly without necessity. No manually created connection or transaction should remain open after completion of the request.

---

### Reviewer index (G1–G8 → clause)

| Tag | Clause |
| --- | --- |
| **G1** | No unrestricted queries (`DB::table()->get()`, `SELECT *`); required columns + filters only |
| **G2** | Pagination on all listing / search / history / transaction / reporting screens |
| **G3** | Batch processing via `chunk()` / `chunkById()` / `cursor()` / `lazy()` |
| **G4** | Remove duplicate / repeated DB calls (page load, refresh, API, loops, AJAX) |
| **G5** | No queries inside loops; review N+1; use eager loading (`with()`) |
| **G6** | Manually created connections closed/released (`DB::disconnect()`) |
| **G7** | Transactions committed or rolled back; prefer `DB::transaction()`; catch `\Throwable` |
| **G8** | No `DATE()`/`LOWER()`/`CAST()` on indexed columns in `WHERE`; use date-time ranges; select required fields |

### 4.1 Mandatory per-operation validation for DB-touching PRs

**Any PR that changes DB operations must be reviewed with an explicit per-operation table, not a
summary verdict.** For each `DB::`/Eloquent operation the PR adds or changes:

1. Name the operation and its location (`method` / `file:line`).
2. Tag the clauses it engages (G1–G8) with ✅ / ⚠️ / ❌.
3. **Explicitly confirm G7** — is the write wrapped in `DB::transaction()`? Read-then-write
   without a transaction, or a `catch (\Exception)` that should be `\Throwable`, is a finding.
4. **Explicitly confirm G1** — does a `->first()`/`->get()` read only the needed columns, or is it
   `SELECT *`? Note when a full read is justified (e.g. preserve-existing-values logic).
5. **Mark pre-existing issues as pre-existing** — an untouched `SELECT *` the PR merely moved is a
   note, not a new violation charged to this PR.
6. Check `->first()`/`->get()` in loops (G5/N+1), `updateOrInsert` refactors (equivalence, §5 #15),
   new indexes / `whereDate` / `CAST` (G8), and bulk vs. per-row inserts (G3).

Report shape (see PR #253's report `pr-253-review.md` §5.1 for a worked example):

```
| # | Operation | Location | Clauses |
| 1 | updateOrInsert → first()+update/insert | saveSingleFileField | G1 ⚠️(pre-existing) · G4 ✅ |
| 2 | same, in DB::transaction              | saveStepDataForStep  | G1 ✅ · G4 ✅ · G7 ✅ |
...
```

---

## 5. Known failure modes / guard rails (codebase-specific)

Recurring traps observed in this codebase. Check these every time so they need not be re-raised.

1. **Scope creep hidden in narrowly-titled PRs.** Changes unrelated to the title, often described
   as "minor" (e.g. a 288× cache-TTL default change inside a "form changes" PR). Always check the
   blast radius against the stated scope.
2. **Deleted files with surviving references.** A "removed unnecessary files" PR must be checked
   for dangling references (routes, views, controllers, model uses) → otherwise 500s.
3. **Shared-utility ripple.** Edits to `app/helpers.php`, `app/Support/*` (e.g. `DataTableRedisCache`),
   middleware, or base classes affect many callers. Enumerate every caller before approving.
4. **Transactions caught on `\Exception` only.** Should be `\Throwable` (see G7). Repo-wide this
   is the norm, not the exception — flag on any touched transaction.
5. **Index-defeating query patterns** (G8): `whereDate`/`orWhereDate`, `CAST(...)` in joins,
   `LOWER(...)` in `WHERE`. Very common; ~300 `whereDate` sites exist solution-wide.
6. **`SELECT *` / `::all()` / unrestricted `get()`** (G1) — including inside cache payloads,
   where an oversized rowset gets serialized on every request.
7. **Client-side DataTables.** A DataTable initialised without `serverSide: true` ships the whole
   result set into the HTML; convert to Yajra server-side. ~65 such views exist solution-wide.
8. **Cache TTL & staleness.** Any new/changed cache must have a correct **invalidation path**;
   check the default TTL and who inherits it. Watch for failure-path caching (caching `[]` on a
   transient error and serving it for the full TTL).
9. **Collation mismatch.** `fc_registration_master` (latin1) compared against `user_credentials`
   (utf8mb4) forces `TRIM(CAST(...))` wrappers and blocks indexes. Fix the schema, not the query.
10. **Schema introspection.** Prefer the cached `fc_schema_has_table()` / `fc_schema_has_column()`
    helpers over raw `Schema::hasTable()`/`hasColumn()` (which hit `information_schema` and contend
    under load).
11. **Legal-form correctness.** Watch hardcoded values and `readonly`+`required` combinations on
    FC document forms (e.g. a frozen hard-coded declaration date, or a required-readonly field with
    no server-side prefill → submit lock-out).
12. **Migrations.** Stray text before `<?php`; `updateOrInsert` resetting `created_at`; runtime DDL
    that must invalidate the schema cache.
13. **Test coverage.** The repo has effectively **no automated tests** (stub files only). "QA test
    evidence" therefore has no automated basis — note this as a standing gap.
14. **Binary artifacts committed to the repo.** Watch for `.docx`/`.xlsx`/`.pdf`/image blobs added
    under `docs/` or elsewhere — they can't be diffed or line-reviewed, bloat the repo, and hide
    their content from review. Prefer Markdown in-repo, or keep the artifact outside the repo.
    (Seen in PR #253: `FC-Form-Performance-Optimization-Report.docx`.)
15. **`updateOrInsert` refactors.** When `updateOrInsert` is replaced by manual
    read-then-update/insert, confirm semantic + concurrency equivalence. Laravel's
    `updateOrInsert` is itself `exists()`-then-`insert`/`update` (not atomic), so a hand-rolled
    version with the same shape is usually equivalent and often fewer queries — but verify the
    target table's uniqueness assumptions are unchanged. (Seen clean in PR #253.)
16. **String-keyed cross-table matching (esp. money paths).** When one query's result is matched to
    another by a formatted string key (e.g. `meter_change_month = "June 2026"`, or `date`/`month`
    text keys), confirm both sides produce the exact same format on every path. A silent key miss
    reads as "no match" and can change a computed amount. Demand a test. (Seen in PR #249 — the
    for-Other bill computes units from a `"F Y"` month key.)
17. **Domain assumptions on money paths.** Flag any billing/units/amount logic that rests on an
    unstated real-world assumption (e.g. "a replaced meter starts at 0"). It may be correct, but it
    must be confirmed with the domain owner, not assumed by the reviewer. (Seen in PR #249.)
18. **Authorization scope creep.** Watch for a permission gate widening from one role to several
    (e.g. `$isSuperAdmin` → `$isSuperAdmin || hasRole(...) || ...`). Even when it aligns with access
    elsewhere, it broadens who sees/exports data — confirm it's intended. (Seen in PR #249: 3 new
    training-admin roles gained full cross-course student list + export.)
19. **New query on a legacy/unmigrated table.** If a PR adds a `whereIn`/`where` query against a
    table with no migration in the repo, its index coverage is unknown — verify an index exists on
    the filtered columns or it's a growing full scan (G8). (Seen in PR #249: `estate_update_reading`.)
20. **New cache layer with partial invalidation coverage.** When a PR adds a cache (Redis or
    otherwise) over an aggregation that reads from multiple tables/mutation sources, enumerate
    *every* controller/path that writes rows the cache aggregates, and confirm each one calls the
    invalidation hook. It's common for the cache to be wired correctly from the controller(s) the
    author was actively working in, and silently missed from sibling controllers that write the
    same underlying data. Grep for the invalidation method name repo-wide and diff that list against
    every write path touching the cached tables — don't just check the paths the PR's diff touches.
    (Seen in PR #250 — High: `AvailableQuantityService::bumpCacheEpoch()` wired into
    `KitchenIssueController`/`SellingVoucherDateRangeController` but not `PurchaseOrderController`/
    `StoreAllocationController`, which mutate the same stock the cache aggregates.)
21. **N+1 fixes that shed correctness along with the query count.** When a PR replaces an in-PHP
    loop (`Model::get()->first(fn ($x) => ...)`) with a single SQL-only lookup, check whether the
    PHP closure was doing more than equality matching — a secondary disambiguation condition,
    a preference order, a fallback chain — that the new SQL query drops. The N+1 elimination itself
    is usually a legitimate win; the finding is the silently-narrowed match condition riding along
    with it. (Seen in PR #250 — Medium: `findEmployeePkByDisplayName`'s N+1 fix dropped
    department-based disambiguation for same-named employees, with no `ORDER BY` tie-breaker added
    to compensate.)
22. **Report screen vs. its own export using divergent computation.** When a PR refactors a
    reporting screen's on-page query (e.g. moving from Eloquent to raw SQL, or vice versa) but
    leaves the screen's Excel/PDF export on the older code path, confirm both paths compute totals
    identically — especially tax, rounding, and any conditional inclusion/exclusion logic. A partial
    refactor is an easy way to leave the screen and its own export silently disagreeing.
    (Seen in PR #250 — Medium: `ReportController`'s Stock Purchase Details screen total now
    includes tax; its Excel/PDF export, still on the pre-refactor Eloquent path, doesn't.)
23. **Shared query-builder extraction changing scope for one of its callers.** When two near-duplicate
    inline queries (e.g. an `index()` listing query and an `export()` query for the same data) are
    consolidated into one shared builder method, confirm the shared method's filters match *both*
    original queries, not just the one the author was primarily improving. It's common for the
    extraction to standardize on one caller's filter set and silently narrow/widen the other's scope.
    (Seen in PR #250 — Medium: extracting `buildProcessMessBillsUnionQueries()` from `index()`'s and
    `export()`'s previously-separate inline queries carried `index()`'s status-exclusion filter into
    `export()`, silently dropping rejected vouchers from the export that it used to include.)

---

## 6. Report template (one per PR)

```
# PR #<n> — "<title>" · Review
<branch> → <base> · head <sha> · <k> commits · <files> files, +<add>/-<del> · by <author>
Verified against checked-out source and audited against the developer guidelines.

## Verdict: <Approve | Approve with changes | Request changes>
<one-paragraph summary of the decision and why>

## What it does
<neutral description of the actual change>

## Findings
### Blocker
### High
### Medium
### Low
<each: ID — one-line summary. file:line:function. failure scenario. remedy. (guideline tag if any)>

## Verified clean
<what was checked and cleared>

## Guideline audit (G1–G8)
<clause-by-clause verdict for any changed DB/query code>

## Required before merge
<the minimal must-fix list>

## Evidence not supplied
<the §7 checklist items the author has not provided>
```

---

## 7. Severity definitions

- **Blocker** — must not merge (runtime/syntactic breakage, data corruption, security hole).
- **High** — production impact or data-correctness risk (leaked transaction, 404/500 on a real
  path, poisoned cache, unauthenticated sensitive endpoint).
- **Medium** — guideline violation, latent risk, or cross-cutting scope creep.
- **Low** — hygiene (dead code, unused imports, doc/description inconsistencies, copy edits).

---

## 8. Standing evidence checklist (what authors must supply)

Per the developer guidelines, a performance/DB remediation should be accompanied by:

- [ ] Query / API details
- [ ] Root cause
- [ ] Changes implemented
- [ ] Before-and-after execution time
- [ ] Pagination validation
- [ ] Database connection and transaction validation
- [ ] QA test evidence

Flag any of these that are missing. A one-line PR body ("Code optimization") does not meet this bar.

---

## 9. Environment & tooling conventions

- **Repo path:** `C:\Workspace\LBSNAA\Code-base\Sargam-2.0` — the workspace root
  (`Code-base\`) is **not** a git repo; target the `Sargam-2.0` subfolder for all git commands.
  Note: in some environments this path does not exist and the working checkout is elsewhere
  (e.g. `c:\xampp1\htdocs\Sargam-2.0`) — if so, still create/target
  `C:\Workspace\LBSNAA\code-review\` for deliverables and worktrees; only the source-repo path
  varies by machine.
- **Remote / auth:** `gh` CLI (authenticated). Use it for PR metadata, diffs, and commit lists.
  Not preinstalled on every machine — check with `gh --version` first; if missing, install via
  `winget install --id GitHub.cli` (Windows) and have the user run `gh auth login` interactively
  (device-code browser flow) since the agent session cannot complete the browser approval step
  itself. Confirm with `gh auth status` before proceeding.
- **Worktrees:** check out PR heads with `git worktree add --detach`; **always remove them** when
  the review ends.
- **Shell:** PowerShell primary. Notes: `Select-String` is **case-insensitive by default**
  (use `-CaseSensitive` for SQL-keyword scans); `rg` is not on PATH (use `Select-String`);
  backtick is the escape char (avoid inline backticks inside double-quoted strings). A Bash tool
  is also available for POSIX one-liners.
- **Deliverables:** one report per PR in `C:\Workspace\LBSNAA\code-review\`, **outside the repo**.
  **Deliver as HTML by default** — self-contained (no CDN/network), **mobile-first responsive**
  (readable on a phone browser: fluid layout, tables scroll inside their own container, tap-sized
  controls, no horizontal page scroll), theme-aware (light/dark), Sargam navy `#1a3c6e`. Keep the
  Markdown source alongside when useful, but the primary shareable artifact is the HTML.
- **Hard rules:** never commit or push review artifacts; never post to GitHub (comments/reviews)
  without explicit approval; keep the repo working tree clean.

---

## 10. Reviews on record (this workstream)

- **PR #246** "Code optimization" — full review + guideline audit; re-verified twice; fix-commit
  `09f06a3` verified (blocker + all High resolved). Reports:
  `pr-246-code-review-and-guideline-audit.md`, `pr-246-review.html`.
- **PR #251** "Feat/fc form changes" — full review + guideline audit + functional-vs-UI
  classification (mostly views, but functional: cache-TTL default change + frozen hardcoded
  declaration date).
- **PR #253** "Implementation redis-cache" (hardeeplbsnaa, MERGED) — **Approve**. Well-engineered FC
  form-structure + column-type caching; applies the F-02 lesson (never caches null), invalidation
  wired, OFF-by-default, jitter. Two low items: UI/content edits bundled into a cache PR (#1); a
  binary `.docx` committed (#14). Report: `pr-253-review.md`.
- **PR #249** "Feat/estate fixes new design" (devRishabhh, OPEN) — **Approve with changes**.
  Meter-replacement bill logic + dashboard tabs/filter. Tricky parts done right (column alignment,
  no N+1, avoids `whereDate`, batched lookup, cache bumps). 4 medium confirm-before-merge items on
  the money/auth paths: string-keyed month match drives for-Other bill amounts (#16), "new meter = 0"
  domain assumption (#17), auth expansion to 3 roles (#18), unindexed audit-table query (#19); + G7
  save-path transaction unverified. Report: `pr-249-review.md` / `.html`.
- **PR #250** "Optimize Mess Module" (VinitaGupt, OPEN) — **Approve with changes**. 103 files,
  +1755/-8487; ~8.5k of the deletion is dead Mess sub-feature scaffolding (17 controllers, 10
  models, ~60 views) — dangling-reference sweep across all 78 deleted files came back 100% clean.
  Two genuine pre-existing-bug fixes confirmed (fatal `Inventory` class-not-found on
  `KitchenIssueController::edit()`; multi-select client-type filter in
  `ProcessMessBillsEmployeeController::export()`). 1 High (new `AvailableQuantityService` Redis
  cache invalidated by only 2 of 4 controllers that mutate the aggregated stock — trap #20), 9
  Medium (screen-vs-export tax total mismatch #22, export silently narrowed scope via shared-query
  extraction #23, N+1 fix dropped employee disambiguation #21, unvalidated date input can 500 a
  listing, breaking response-shape change on an AJAX endpoint, two quantity-reconciliation UI
  regressions, uncached schema-introspection helper), 13 Low. 9/9 Medium+ findings sent to
  adversarial verification were confirmed, 0 refuted. Two new composite-index migrations reviewed
  separately — 7/9 indexes are excellent leftmost-prefix matches to real query patterns. Reports:
  `pr-250-review.md`, `pr-250-review.html`.
- **Solution-wide audit** — `sargam2-guideline-audit.html`, `sargam2-guideline-remediation-register.md`
  (992 findings across 643 PHP files; separate breadth-first exercise).

---

_This is a living document (see §0.1). After every review: add new traps to §5, log the PR in
§10, and fold any new review information into §4-targets / §5 / §9. §4 (canonical guidelines) is
verbatim and changes only when the team issues a revised official guideline._
