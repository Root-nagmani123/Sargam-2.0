# Sargam 2.0 — Entire Codebase Audit Guidelines

> **Purpose:** Systematically identify, classify, prioritise, and track security, correctness, reliability, scalability, database, testing, and maintainability risks across the full Sargam 2.0 repository or a declared module.

| Field | Value |
| --- | --- |
| Document version | v2.0 |
| Owner | Review framework owner |
| Approver | Engineering lead |
| Review cadence | Every 6 months, or with `shared-review-standards.md` |

**Agents:** read `review-core.md` and `agent-operating-rules.md` first. Audit mode requires the `coverage` array in the structured findings file — see `findings-schema.md`.

Read `shared-review-standards.md` and `sargam-known-review-traps.md` before starting.

---

## 1. Scope

Unlike a PR review, this audit includes legacy and pre-existing issues.

The audit may cover:

- Controllers
- Models and relationships
- Services and support utilities
- Routes and middleware
- Policies and permissions
- DataTables
- Blade views and frontend scripts
- Migrations and seeders
- Imports and exports
- Jobs and console commands
- Helpers and configuration
- Dependencies, lockfiles, and CI workflows
- Tests
- Database query patterns
- Caching and invalidation
- File storage and downloads
- Logging and audit trails

The audit scope must be declared as one of:

- Entire repository
- Specific module
- Specific risk category
- Specific release baseline

Record the branch/commit SHA and audit date.

---

## 2. Audit objectives

The audit must answer:

- What risks exist?
- Where are they located?
- What is their realistic impact?
- Which findings require immediate remediation?
- Which are accepted risks or false positives?
- What evidence is required to close each item?
- What should be fixed first, by whom, and in which release or sprint?
- **What was actually examined, and what was not?**
- **What changed since the previous audit — fixed, new, recurring, or regressed?**

---

## 3. Audit categories

### Security

- Authentication and authorisation gaps
- IDOR
- Mass assignment and privilege escalation
- Unsafe raw SQL or dynamic identifiers
- XSS and unsafe Blade output
- File upload/download risks
- Secrets and sensitive-data exposure
- Missing rate limits
- Vulnerable or unmaintained dependencies

### Correctness and data integrity

- Wrong calculations
- Null and boundary errors
- Unsafe status transitions
- Duplicate creation
- Missing database constraints
- Model-event bypass
- Inconsistent cross-table matching

### Concurrency and transaction safety

- Open transactions
- `catch (\Exception)` instead of `\Throwable`
- Read-then-write races
- Retry-unsafe jobs
- Overlapping scheduled commands
- Unmonitored failed jobs
- Duplicate approvals, billing, payments, or inventory updates

### Database and memory performance

- G1 unrestricted reads and unnecessary columns
- G2 missing pagination
- G3 full-memory batch processing
- G4 repeated calls
- G5 queries inside loops / N+1, including accessors and `$appends`
- G6 manual connection handling
- G7 transaction risks
- G8 non-sargable filters, joins, and schema mismatch
- Queries inside Blade
- Client-side DataTables on growing datasets
- Cache payload size and invalidation

### Migration and deployment

- Irreversible changes
- Large-table locks
- Missing indexes and constraints
- Unsafe deployment order
- Runtime DDL
- Schema-cache invalidation
- Absence of rollback paths on high-risk changes

### Observability and configuration

- Privileged actions without audit trails
- Swallowed exceptions
- PII or secrets in logs
- `env()` outside `config/`
- Storage disks with unintended visibility
- Environment-specific values hardcoded in application code

### Testing and architecture

- Missing regression coverage
- Weak or falsely green tests
- Large controllers and duplicated logic
- Shared-helper blast radius
- Binary artifacts in repository
- Unmaintainable generated or copied code

---

## 4. Audit methods

Use a combination of:

- The automation baseline in `shared-review-standards.md` Section 9, run repository-wide as the first step
- Static pattern scans
- Source-level manual validation
- Call-site enumeration
- Schema and migration inspection
- Route and policy inspection
- Query-plan or runtime evidence where available
- Module-owner or domain-owner confirmation for business assumptions

Heuristic and automated scan results are **candidates**, not automatically confirmed findings.

For every candidate:

1. Confirm the code path and receiver type.
2. Determine whether the issue is real or a false positive.
3. Assign severity and confidence, reconciled against the severity × confidence table in the shared standards.
4. Document impact and remediation.
5. Record evidence required for closure.

Populate or refresh **Appendix A.2 (data volume reference)** and **Appendix A.3 (index inventory)** of the shared standards as part of every full audit. Without them, most G1/G2/G5/G8 severities are guesses.

---

## 5. Audit finding record

Every finding must include:

| Field | Requirement |
| --- | --- |
| Finding ID | Stable unique ID |
| Category / clause | Security, G1–G8, migration, testing, etc. |
| Location | File, line, function |
| Severity | Blocker / High / Medium / Low / Advisory |
| Confidence | High / Medium / Low |
| Detection | Automated rule ID, trap number, manual review, or reported incident |
| Impact | Concrete failure or operational scenario |
| Recommended remediation | Practical next step |
| Validation | Test, QA, query plan, schema, or domain evidence |
| Closure evidence | What will be accepted as proof this is resolved |
| Status | Open / In progress / Fixed / Accepted risk / False positive |
| Owner | Person or team, when assigned |
| Target | Sprint, release, or date, when assigned |
| Verified baseline | Audit date and commit SHA |
| First seen | Audit in which the finding was first raised |

### 5.1 Accepted risk — governance

`Accepted risk` is the fastest way for a register to become a permanent list of unfixed High findings. It is therefore a governed state, not a status anyone can set.

An accepted risk is invalid unless it records **all** of:

| Field | Requirement |
| --- | --- |
| Accepted by | Named individual with authority to accept it. Blocker and High require the engineering lead; Medium requires the module owner. A reviewer or the author cannot accept their own finding. |
| Date accepted | Date |
| Rationale | Why the risk is tolerable now — business, cost, or sequencing reason. "Low priority" is not a rationale. |
| Compensating control | What limits the exposure meanwhile — monitoring, manual process, restricted access, low volume — or an explicit "none." |
| Expiry date | Mandatory. Maximum 6 months for High, 12 months for Medium. Blocker findings may not be accepted; they are fixed or the affected feature is disabled. |
| Review trigger | Conditions that force early re-review — volume growth, role-model change, schema change, related incident |

At expiry the finding **automatically returns to Open** at its original severity and must be re-accepted with fresh justification or remediated. Re-acceptance of the same finding more than twice is escalated to the engineering lead as a standing decision.

### 5.2 False positive — governance

A dismissed candidate must record:

| Field | Requirement |
| --- | --- |
| Dismissed by | Name |
| Date | Date |
| Reason | Why the pattern is not a defect here — receiver type, bounded dataset, confirmed index, unreachable path |
| Suppression | The rule suppression entry added so the candidate is not rediscovered |
| Revalidate when | The change that would make it a real finding again |

A false positive without a recorded reason will be rediscovered at every audit and re-triaged at full cost.

---

## 6. Remediation prioritisation

Prioritise using:

1. Security and data-integrity impact
2. User and transaction volume
3. Financial or approval sensitivity
4. Blast radius
5. Ease of exploitation or occurrence
6. Operational cost
7. Fix complexity and regression risk

Create at least three remediation bands. Each band carries a target response time, so the register does not stall.

### Immediate — target: current sprint

- Blocker and High findings
- Wrong financial or approval outcomes
- Authorisation gaps
- Open transaction risks
- Severe unbounded or N+1 paths

### Near-term — target: within two sprints

- Material Medium findings
- Missing pagination on growing data
- Cache invalidation gaps
- Schema mismatch and missing indexes
- Missing audit trails on privileged actions

### Planned — target: named release or backlog with review date

- Low and Advisory findings
- Refactoring
- Architectural cleanup
- Testability improvements

Anything in Immediate that is not started within the sprint is escalated, not silently rolled forward.

---

## 7. Required outputs

### 7.1 Executive audit report

Include:

- Scope and baseline
- **Coverage and method confidence (Section 7.2) — mandatory**
- Overall risk statement
- Top ten findings
- Module risk summary
- **Baseline comparison (Section 7.3)**
- Immediate, near-term, and planned actions
- Evidence limitations

### 7.2 Coverage and method confidence

The strongest conclusion an audit can reach — "no critical repository-wide issue found" — is also the easiest to state without support. It is therefore only permitted alongside an explicit coverage statement.

| Module / area | Method | Coverage | Confidence in negative finding |
| --- | --- | --- | --- |
| *(module)* | Manual source review | Full / Partial / Sampled | High / Medium / Low |
| *(module)* | Automated scan only | Full | Low — pattern-level only |
| *(module)* | Not covered | None | None |

Rules:

- **"No critical issue found" applies only to areas manually reviewed at Full coverage.** For everything else, the correct statement is "no critical issue detected by the methods applied," with the methods named.
- Automated-scan-only coverage never supports a High-confidence negative finding.
- Areas not covered must be listed by name. Silence about a module reads as a clean bill of health, and that is how audits mislead.
- State the proportion of the declared scope reached at each coverage level.

### 7.3 Baseline comparison

Every audit after the first reports movement against the previous baseline, so improvement is visible rather than assumed.

| Metric | Count | Notes |
| --- | --- | --- |
| Findings carried forward, still Open | | |
| Fixed since last audit | | Verified against closure evidence, not self-report |
| New findings | | |
| Recurring — same root cause, new location | | Indicates a missing rule, trap, or standard |
| Regressed — previously Fixed, now present again | | Indicates missing regression test |
| Accepted risks expired and reopened | | |
| False positives re-raised | | Indicates a missing suppression entry |

Recurring and regressed findings are the most valuable numbers in the audit. A recurring finding means the framework failed to prevent it — raise a trap or an automated rule in the same cycle. A regressed finding means the fix shipped without a regression test.

### 7.4 Remediation register

A line-level or function-level register with status, ownership, target, closure evidence, and the accepted-risk and false-positive fields from Sections 5.1 and 5.2. Maintained as a single living file, not regenerated per audit, so history is preserved.

### 7.5 Priority plan

Group work by:

- Immediate fixes
- Module backlog
- Schema changes
- Test enablement
- Automation rule additions
- Architectural follow-up

### 7.6 Audit outcomes

Do not use PR verdicts such as Approve or Request Changes for a codebase audit.

Suggested audit outcomes:

- Critical remediation required
- High-risk backlog identified
- Moderate technical debt requiring planned remediation
- No critical repository-wide issue found *(permitted only with a Full-coverage manual statement under Section 7.2)*
- Coverage insufficient to conclude

---

## 8. Connection between audits and PR reviews

A codebase-audit finding does not automatically become a PR finding.

It becomes relevant to a PR only when the PR:

- Introduces it
- Worsens it
- Directly modifies the affected logic
- Depends on it in a way that makes the new change unsafe

A recurring issue found during a PR review may be added to the remediation register, but it must be separated from the PR verdict.

Register items in the **Immediate** band that sit in code a PR is modifying should be raised to the author as an opportunity, not charged to the PR verdict.

---

## 9. Audit quality controls

- Do not count unvalidated heuristic or automated matches as confirmed defects.
- Group repeated occurrences by root cause.
- Separate output-only SQL formatting from filter/join index risks.
- Distinguish bounded reference datasets from unbounded transactional datasets.
- Verify whether `first()`, `count()`, `pluck()`, or `get()` runs on a query builder or an in-memory Collection.
- State when index coverage, runtime volume, or schema information is unknown; do not assign High severity to a volume-dependent finding on a table of unknown size.
- Record false-positive reasons and suppression entries so the same candidate is not repeatedly rediscovered.
- Revalidate repository facts after schema, tooling, or architecture changes.
- Reconcile every severity against the severity × confidence table before publishing.
- Report the audit's own limitations as prominently as its findings.

---

## Changelog

**v2.0**

- Added Section 5.1, accepted-risk governance with mandatory acceptor, rationale, compensating control, and expiry.
- Added Section 5.2, false-positive governance with suppression and revalidation.
- Added `Detection`, `Closure evidence`, and `First seen` to the finding record.
- Added Section 7.2, mandatory coverage and method confidence statement; restricted the "no critical issue found" outcome to Full-coverage manual review.
- Added Section 7.3, baseline comparison metrics including recurring and regressed counts.
- Added target response times to the remediation bands.
- Added the automation baseline as the first audit method, and made Appendix A.2/A.3 population an audit deliverable.
- Added observability, configuration, supply-chain, and mass-assignment audit categories.
- Added "Coverage insufficient to conclude" as an audit outcome.
