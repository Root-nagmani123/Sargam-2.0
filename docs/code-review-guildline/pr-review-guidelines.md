# Sargam 2.0 — Pull Request Review Guidelines

> **Status:** Local review standard; not committed to the application repository unless explicitly approved by the framework owner.
> **Applies to:** Every pull-request review of `Root-nagmani123/Sargam-2.0`.
> **Purpose:** Determine whether changes introduced or materially affected by a specific PR are safe, correct, secure, reliable, and ready to merge.

| Field | Value |
| --- | --- |
| Document version | v2.0 |
| Owner | Review framework owner |
| Approver | Engineering lead |
| Review cadence | Every 6 months, or with `shared-review-standards.md` |

**Agents:** read `review-core.md` and `agent-operating-rules.md` first, then load this document's Sections 3–5 plus the modules the change actually touches. Do not load this file in full for a Tier 0 review.

Read `shared-review-standards.md` (per module map) and `sargam-known-review-traps.md` before starting.

---

## 1. Scope and boundaries

This is a **PR-level review**, not a repository-wide audit.

Review:

- Changed lines
- Complete changed functions
- Direct callers and dependencies
- Related routes, models, services, policies, migrations, views, jobs, commands, and tests
- Existing behaviour materially affected by the change

Do not report as PR findings:

- Unrelated legacy technical debt
- Untouched guideline violations outside the changed execution path
- Style-only observations
- Speculative concerns without a concrete failure scenario
- Repository-wide scan results that the PR neither introduces nor worsens

Every finding that affects the verdict must be classified as **Introduced**, **Worsened**, or materially **Touched**. A pre-existing issue noticed during the review is classified **Legacy / Repository-wide**, recorded under "Follow-up items outside this PR," added to the remediation register, and excluded from the verdict — except where it creates an immediate dependency risk for the change, in which case it is reclassified **Touched** with the dependency stated.

---

## 2. Reviewer stance

- Verify against checked-out source, not only the diff summary or PR description.
- Reconcile description versus code throughout the review.
- Enumerate callers when shared helpers, middleware, base classes, or `Support/` utilities change.
- Report what is broken, what is clean, and what could not be verified.
- Do not claim runtime performance, query count, index usage, or load behaviour without evidence.
- Do not assert a Blocker or High finding at Low confidence — see the severity × confidence table in the shared standards.
- Do not spend manual effort on anything the automation baseline already detects.
- Do not commit, push, post comments, or submit a GitHub review without explicit approval.

---

## 3. Review effort tiers

A three-line config change and a two-thousand-line refactor cannot receive the same twelve-step method — if they do, the method gets skipped. Declare the tier in the report header.

| Tier | Entry criteria | Method |
| --- | --- | --- |
| **Tier 0 — Light** | Documentation, comments, copy text, styling with no logic; no route, migration, permission, dependency, config, money, or query change | Automation gates (Section 4) + scope reconciliation + confirm no hidden logic change. Short report. |
| **Tier 1 — Standard** | Ordinary feature or fix; bounded blast radius; no money, permission, migration, or shared-utility change | Full method, Sections 5–11, proportionate depth |
| **Tier 2 — Deep** | **Any** of: money/billing/payment path · approval or status-transition logic · authentication, authorisation, or role scope · migration or schema change · shared utility, helper, middleware, or base class · dependency or CI change · deletion of files · > 500 changed logic lines · queue/scheduled-job changes | Full method plus mandatory caller enumeration, migration/rollback review, concurrency review, and named post-deploy verification |

Tier is set by the highest matching criterion, not by line count alone. A one-line change to a permission condition is Tier 2. If a Tier 0 or Tier 1 review uncovers a Tier 2 trigger, re-tier and say so in the report.

---

## 4. Automation gates before manual review

Run the pre-review automation baseline from `shared-review-standards.md` Section 9 before reading code:

- PHPStan / Larastan at the declared level
- Pint
- `composer audit` / `npm audit`
- Test suite
- Custom trap rules AUTO-01 to AUTO-16

Record the result of each gate in the report. A gate that could not run is an evidence gap and is listed under "Evidence not supplied." Automated hits are candidates: promote each to a finding or dismiss it with a recorded reason.

---

## 5. Repeatable review method

1. **Metadata**
   - Retrieve title, body, author, branches, state, additions, deletions, changed files, and commits.
2. **Tier the review**
   - Apply Section 3 and record the tier and its trigger.
3. **Diff and isolated worktree**
   - Save the PR diff.
   - Check out the PR head SHA in a detached worktree.
   - Remove the worktree after completion.
4. **Automation gates**
   - Run Section 4 and record results.
5. **Shape the change**
   - Use file and line statistics.
   - Classify files as logic, view, config, migration, test, generated, binary, dependency, CI, or documentation.
6. **Review in priority order**
   - Breaking changes
   - Logic/correctness
   - Security
   - Data integrity
   - Concurrency/idempotency
   - Transactions
   - Database/memory performance
   - Migration/deployment safety
   - API/frontend contracts
   - Observability and audit logging
   - Configuration, dependencies, supply chain
   - Tests
7. **Audit changed DB operations**
   - Apply G1–G8 only to added, changed, or directly affected operations.
8. **List verified-clean areas**
   - Record important checks that passed.
9. **Write the report**
   - Use the standard report structure and naming convention below.
10. **Clean up**
    - Remove the worktree and confirm the repository remains clean.
11. **Record the outcome**
    - Add the entry to `review-history.md` in the required format.

---

## 6. Breaking changes and blast radius

Always check first:

- Does the PR match its title and description?
- Are there unadvertised changes?
- Do deleted files still have surviving references?
- Do shared utility changes affect many callers?
- Are function signatures, response shapes, defaults, flags, TTLs, routes, columns, or statuses changed?
- Can the old and new application versions coexist with the migration/deployment sequence?
- Are binary files or generated artifacts hiding meaningful changes?
- Do lockfiles, CI workflows, or `config/` files change without explanation in the PR description?

---

## 7. Logic, correctness, and data integrity

Check:

- Empty state and first-time use
- Null, zero, false, and missing values
- Wrong defaults or hardcoded values
- Partial updates and silent data loss
- Status-transition rules
- Financial, billing, units, or inventory calculations
- Date and timezone boundaries
- Duplicate creation
- Referential integrity
- Incorrect `first()` assumptions
- Model event changes caused by Query Builder refactors

Every material finding must include a concrete failure scenario.

---

## 8. Security

Apply the full security standard from `shared-review-standards.md`.

For every new or changed route/API, explicitly verify:

- Authentication
- Role/permission check
- Object-level authorisation
- Request validation
- Mass-assignment surface on any model receiving request input
- Sensitive response fields
- Export/download permissions
- Raw SQL, file handling, and unsafe output where applicable

For every changed dependency, lockfile, CI workflow, or config file, apply the supply-chain checks.

Security findings take precedence over performance-guideline findings in the verdict.

---

## 9. Concurrency and idempotency

Explicitly check high-risk operations:

- Approvals
- Billing and payments
- Inventory changes
- Imports
- Scheduled jobs and queued jobs, including retry and overlap behaviour
- Notifications
- Status transitions

A read-then-write pattern is a finding only when concurrency can change the outcome, multiple writes must be atomic, or a business invariant is at risk.

---

## 10. Changed database-operation audit

### 10.1 Enumeration trigger — risk-based, not count-based

The following are **always enumerated individually**, whatever the total number of operations:

- Writes — insert, update, delete, upsert
- Transactions
- Raw SQL
- Queries inside loops
- Unbounded reads
- Financial, billing, or inventory operations
- Permission-sensitive operations
- Schema changes
- Queries on tables absent from the Appendix A.2 volume reference

Remaining **read-only** operations may be grouped by method or pattern where they are equivalent — for example, "8 × keyed `find()` on reference tables, all `select`-restricted, G1 ✅."

Grouping is a reporting convenience, never a review shortcut. Every operation is examined; grouping only affects how it is written up.

### 10.2 Required table

```text
| # | Operation | Location | Classification | Clauses / result |
| 1 | updateOrInsert → first()+update/insert | saveSingleFileField | Introduced | G1 ✅ · G4 ✅ · G7 ⚠️ concurrency evidence needed |
```

For each operation, consider:

- G1 required columns and filters
- G2 pagination where it feeds a list
- G3 batch processing
- G4 repeated calls
- G5 loop execution / N+1, including accessors and `$appends`
- G6 manual connection handling
- G7 atomicity and failure paths
- G8 filtering, joins, schema types, and index evidence

Mark pre-existing issues as pre-existing. Do not charge unrelated issues to the PR.

---

## 11. Verified-clean section

The report must state important checks that passed, for example:

- No dangling references after file deletion
- New route has expected middleware and policy check
- Transaction covers all related writes and uses `\Throwable`
- No query inside the changed loop
- Cache invalidation exists on all writes
- Migration is backward compatible
- API response shape remains unchanged
- No mass-assignment exposure on the changed model
- Privileged action is audit-logged

Do not use generic claims such as "security looks fine." Name what was checked.

---

## 12. Verdict definitions and exit criteria

Verdicts are determined using the severity × confidence table in `shared-review-standards.md`. Every non-Approve verdict has an owner, exit criteria, and a time bound.

### Approve

No confirmed Blocker, High, or unresolved material Medium finding.

- **Exit:** merge.
- **Non-material Medium, Low, and Advisory items** are recorded in the report and, where they warrant tracking, added to the remediation register. They do not block.

### Approve with conditions

No confirmed Blocker or High finding, but clearly identified Medium-risk actions or evidence are required before merge.

- **Conditions must be enumerated as a numbered checklist** in "Required before merge," each with the closure evidence that will satisfy it.
- **Owner:** PR author, unless another owner is named per condition.
- **Verifier:** for conditions on **money, permission, or migration paths, a named human verifies** — not the reviewing agent. Where the code author and the reviewer are both agents with no memory between sessions, there is no meaningful separation of duties, and an agent marking its own condition satisfied is not a control. For all other conditions, a subsequent agent review may verify, provided it records the closure evidence it actually observed.
- **Exit:** every condition evidenced and ticked by the verifier. The verification is recorded as a short closure note appended to the report — not a verbal "done."
- **Time bound:** conditions unresolved after 5 working days are escalated to the engineering lead; the PR is either re-scoped, split, or moved to Request changes. An agent does not track elapsed working days or perform escalation — it emits the escalation item with the named recipient and stops (see `agent-operating-rules.md` Section 3).
- **Merge is blocked until closure.** If the branch protection cannot express this, the reviewer holds approval until conditions are met.

### Request changes

One or more confirmed Blocker/High findings, or unresolved material Medium findings that can cause incorrect production behaviour.

- **Exit:** a fix commit, then a re-review under Section 13.
- **Owner:** PR author.
- **Time bound:** no automatic expiry, but a PR open more than 10 working days in this state is re-scoped or closed rather than left to rot.

### Unable to conclude

Critical source, schema, domain confirmation, environment information, or test evidence is unavailable.

- **The report must name exactly what is missing and who can supply it.** "Unable to conclude" without a named information owner is not a valid verdict.
- **Owner:** the named information owner — DBA for schema and index evidence, module or domain owner for business assumptions, QA for test evidence, DevOps for environment.
- **Time bound:** 5 working days to supply. On expiry, escalate to the engineering lead, who either supplies the evidence, accepts the risk in writing under the accepted-risk rules in the audit guidelines, or rejects the PR.
- This verdict may apply to a **specific area** while the rest of the review concludes. Where it does, say which area, and treat the PR as not approvable until that area resolves.

### Partial

A review that was started and deliberately stopped before completion — for example, a pre-scan performed to unblock another decision.

- **Not a merge verdict. A Partial review never authorises a merge.**
- The report must state: what was covered, what was not, why it stopped, and what remains to complete a real verdict.
- Recorded in `review-history.md` as Partial with the remaining scope named.

---

## 13. Re-review protocol

Applies after a fix commit following Request changes or Approve with conditions.

- **Scope:** the fix diff, plus the regression surface it touches — callers, related paths, and anything the fix could have destabilised. Not the whole PR again.
- **Previously cleared items are not reopened** unless the fix commit touched them, or unless new information invalidates the earlier clearance. Reopening cleared items without cause wastes the author's time and erodes trust in the verified-clean section.
- **Each original finding gets an explicit disposition:** Fixed (with the evidence that proves it) · Partially fixed (what remains) · Not fixed · No longer applicable (why) · Accepted risk (by whom, with expiry).
- **A fix for a Blocker or High is expected to carry a regression test** that would have failed against the pre-fix code. Where impractical, record the reason.
- **Confidence may be revised** in either direction; record the revision and its cause.
- **Verdict transition is recorded** — for example, "Request changes → Approve, after fix commit `<sha>`" — in both the report and `review-history.md`.
- **Re-review the automation gates** on the new head SHA. A fix commit can introduce a new gate failure.

---

## 14. PR report template

```text
# PR #<n> — "<title>" · Review
<branch> → <base> · head <sha> · <k> commits · <files> files, +<add>/-<del> · by <author>
Tier: <0 Light | 1 Standard | 2 Deep> (trigger: <criterion>)
Framework: shared-review-standards <version> · Reviewed <date> by <reviewer>
Verified against checked-out source and reviewed under the Sargam shared standards.

## Verdict: <Approve | Approve with conditions | Request changes | Unable to conclude | Partial>
<one-paragraph decision summary>
Exit criteria: <what closes this verdict, who owns it, by when>

## Automation gate results
| Gate | Result | Notes |

## What the PR actually changes
<neutral implementation summary>

## Scope reconciliation
<description versus source, including any unadvertised changes,
 and any unexplained dependency, lockfile, CI, or config changes>

## Findings
### Blocker
### High
### Medium
### Low
### Advisory
<each finding follows the mandatory shared format, including
 Detection and Closure evidence>

## Verified clean
<important checks performed and cleared>

## Changed database-operation audit
<risk-based G1–G8 table>

## Required before merge
1. [ ] <condition> — closure evidence: <what will satisfy it> — owner: <name>
2. [ ] ...

## Evidence not supplied
<missing runtime, QA, schema, timing, query-plan, or domain evidence,
 each with the named owner who can supply it>

## Rollback and post-deploy verification
<required for Tier 2; method, order, flag, named checks, owner, window>

## Follow-up items outside this PR
<Legacy / Repository-wide items added to the remediation register;
 these do not affect the verdict>

## Re-review log
<fix commit sha, disposition of each original finding, verdict transition>
```

---

## 15. Report naming and storage

One convention, so that `review-history.md` entries stay citable.

- **Location:** `reviews/` in the local review workspace.
- **PR review:** `pr-<n>-review.md`
- **Re-review appended to the same file** under a `## Re-review — <date>` heading. Do not create `pr-<n>-review-v2.md`.
- **Rendered copy, where produced:** `pr-<n>-review.html`, same base name.
- **Audit:** `audit-<YYYY-MM-DD>-<scope>.md`, for example `audit-2026-07-14-mess-module.md`.
- **Remediation register:** `remediation-register.md`, single living file.
- Every entry in `review-history.md` must point at a real path under this convention. "Local report" is not an acceptable reference.

---

## 16. Final checklist

- [ ] Review tier declared and justified
- [ ] Automation gates run and results recorded
- [ ] PR description reconciled against actual source
- [ ] Changed functions reviewed, not only changed lines
- [ ] Direct callers/dependencies checked where needed
- [ ] Routes, permissions, object-level access, and mass assignment checked
- [ ] Dependency, lockfile, CI, and config changes reviewed
- [ ] Logic, data integrity, concurrency, and failure paths checked
- [ ] Migrations, deployment order, and rollback plan checked (Tier 2)
- [ ] Changed DB operations audited using G1–G8, with risk-based enumeration
- [ ] Findings classified as Introduced, Worsened, Touched, or Legacy
- [ ] Severity and confidence included, and reconciled against the severity × confidence table
- [ ] Closure evidence stated for every Blocker, High, and material Medium
- [ ] Duplicate findings grouped
- [ ] Verified-clean items listed by name
- [ ] Missing evidence identified with a named owner
- [ ] Audit-logging expectations checked on privileged actions
- [ ] Verdict carries exit criteria, owner, and time bound
- [ ] Report saved under the naming convention
- [ ] Outcome recorded in `review-history.md`
- [ ] Worktree removed
- [ ] No commit, push, or GitHub post without approval

---

## Changelog

**v2.0**

- Added Section 3, review effort tiers, so method depth scales with risk.
- Added Section 4, mandatory automation gates before manual review.
- Replaced the 20-operation count threshold with risk-based enumeration (Section 10.1).
- Added exit criteria, owners, and time bounds to every verdict; defined the Partial state (Section 12).
- Added Section 13, re-review protocol.
- Added Section 15, report naming and storage convention.
- Reconciled legacy-finding handling with the shared classification table.
- Added mass assignment, supply chain, observability, and rollback to the method, template, and checklist.
- Report template now records tier, framework version, automation results, and re-review log.
