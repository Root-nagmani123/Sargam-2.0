# Sargam 2.0 — Review History and Framework Effectiveness

> **Purpose:** Keep historical PR and audit outcomes outside the core review instructions so the main guidelines remain stable and focused — and measure whether the framework is actually working.

| Field | Value |
| --- | --- |
| Document version | v2.0 |
| Owner | Review framework owner |
| Update trigger | Every completed review, every re-review, every production incident |

---

## 0. Provenance of pre-v2.0 entries

**Every review below was produced by an automated agent under v1.0, and none was verified by a human.** v1.0 had no evidence rule, no requirement to cite supporting source lines, and no requirement to record gate output.

Consequences:

- **These reports are not ground truth.** Benchmarking a new framework version against them measures consistency between agent runs, not correctness. Shared blind spots and shared invented facts will look like agreement.
- Findings, verdicts, and the lessons drawn from them may rest on unverified assertions.
- The traps register derived from these reviews carries the same caveat — see the provenance warning in `sargam-known-review-traps.md`.

**Validation to run instead of a benchmark:** perform one Tier 2 review under v2.1, then have an engineer spend an hour checking only the factual assertions — do the cited files and line ranges exist and say what is claimed, do claimed indexes appear in migrations, do enumerated callers exist, did the gates actually run. Do not re-review the code. That single number, the fabrication rate, determines whether the framework is trustworthy enough to run. Record it below.

| Trial | Date | Reviewer agent | Assertions checked | Assertions unsupported | Fabrication rate |
| --- | --- | --- | --- | --- | --- |
| *(pending)* | | | | | |

---

## 1. Review log

One format only. Every entry must point at a real report path under the naming convention in `pr-review-guidelines.md` Section 15.

| Review | Scope / title | Tier | Verdict | Closed? | Key lesson | Report |
| --- | --- | --- | --- | --- | --- | --- |
| PR #246 | Code optimization | — | Request changes → Approve | Yes, after fix commit | Blocker and High issues resolved after fix commit; re-review had no defined protocol at the time | `reviews/pr-246-review.md` *(retrofit: currently `pr-246-code-review-and-guideline-audit.md`, `pr-246-review.html`)* |
| PR #251 | FC form changes | — | Reviewed *(verdict not recorded)* | Unknown | A UI-heavy PR also contained functional cache/default and declaration-date changes — title is not scope | *Path not recorded — retrofit required* |
| PR #253 | Redis cache implementation | — | Approve | Yes | Good cache invalidation and null-handling; low-level scope and binary-artifact issues | `reviews/pr-253-review.md` |
| PR #249 | Estate fixes / new design | — | Approve with conditions | **Unknown — conditions closure not recorded** | Money-path assumptions, string-key matching, authorisation expansion, and index evidence required | `reviews/pr-249-review.md`, `pr-249-review.html` |
| PR #250 | Optimize Mess Module | — | Partial | No — review incomplete | Dangling-reference pre-scan completed; full review not completed. Partial is not a merge authorisation | *Path not recorded — retrofit required* |
| Audit | G1–G8 solution-wide scan | — | Remediation register produced | Ongoing | Static candidates require manual triage before counting as confirmed defects | `reviews/audit-<date>-g1-g8.md`, `remediation-register.md` *(paths to retrofit)* |

**Retrofit backlog:** verdicts, tiers, dates, head SHAs, and report paths are incomplete for pre-v2.0 entries, and the closure state of PR #249's conditions is unknown. Reconstruct where records exist; mark unrecoverable fields as *not recorded* rather than guessing.

### 1.1 Entry format for new reviews

```text
| PR #<n> | <title> | <tier> | <verdict> | <Yes/No + date> | <one-line lesson> | reviews/pr-<n>-review.md |
```

An entry is added when the review is written and **updated when the verdict closes**. An "Approve with conditions" row with a blank Closed column after 5 working days is an escalation trigger, not a record-keeping detail.

---

## 2. Verdict transitions

Record every re-review outcome, so effort spent on re-reviews is visible.

| PR | From | To | Fix commit | Date | Original findings fixed / partially fixed / not fixed / accepted |
| --- | --- | --- | --- | --- | --- |
| #246 | Request changes | Approve | *not recorded* | *not recorded* | Blocker and High resolved |

---

## 3. Incident log

Every production defect is recorded here, whether or not it passed through review. This table is the framework's evidence base.

| Incident | Date | Severity | Root cause | Passed review? | Which review | Would the framework have caught it? | Action taken |
| --- | --- | --- | --- | --- | --- | --- | --- |
| *(none recorded yet — begin logging from adoption)* | | | | | | | |

### 3.1 Mandatory post-incident question

After every production defect, answer in writing:

> **Would this review framework have caught it?**

| Answer | Required action |
| --- | --- |
| **Yes, and review caught it but it shipped anyway** | Process failure, not framework failure. Fix the process — was it an unclosed condition, an unverified accepted risk, an expired evidence request? |
| **Yes, but the reviewer missed it** | Check whether the relevant standard is findable and specific. Ambiguous guidance is a framework gap. Consider promoting the check to an automated rule. |
| **No — no standard, trap, or rule covered it** | **Add one in the same cycle.** New trap in `sargam-known-review-traps.md`, or new automated rule, or a new check in the shared standards, referencing this incident as provenance. |
| **No — it was outside review scope entirely** | Record why (infrastructure, third-party, data issue). No framework change, but note the blind spot. |

No incident is closed until this question is answered.

---

## 4. Framework effectiveness metrics

Reviewed at each framework review cycle. If the code keeps breaking in a category, the gap is in the framework, not only in the code.

### 4.1 Escape rate — the primary metric

**Escape rate** = production defects in reviewed code that the review did not catch ÷ total production defects in reviewed code, per period.

| Period | Reviewed PRs | Production defects in reviewed code | Escaped (not raised in review) | Escape rate | Dominant escape category |
| --- | --- | --- | --- | --- | --- |
| *(begin recording)* | | | | | |

The **dominant escape category** is the most actionable field in this document. It says where to invest the next framework change.

### 4.2 Supporting metrics

| Metric | Why it matters | Current |
| --- | --- | --- |
| Findings per review by severity | Sudden drops may mean shallower reviews, not better code | |
| Confirmed vs dismissed automated candidates | Rules producing mostly noise are tightened or retired | |
| Conditions closed within 5 working days | Measures whether "Approve with conditions" is real | |
| Accepted risks live, and expired-unreviewed | Measures register decay | |
| Recurring findings at audit | Same root cause, new location — indicates a missing rule or trap | |
| Regressed findings at audit | Previously fixed, now back — indicates missing regression tests | |
| Traps in Stale state | Measures register maintenance | |
| Median time in Request changes | Long-lived rejected PRs should be re-scoped, not left open | |

### 4.3 Interpretation rules

- Rising escape rate in one category → add or sharpen the corresponding standard, trap, or rule.
- High recurring count → the standard exists but is not being applied; automate it or move it earlier in the method.
- High regressed count → fixes are shipping without regression tests; enforce the test requirement in the re-review protocol.
- Falling findings-per-review with flat escape rate → reviews are getting more efficient. Falling findings with rising escape rate → reviews are getting shallower.

---

## 5. Automation rule change log

Tracks rules added, tightened, or retired, per shared standards Section 9.4.

| Date | Rule ID | Change | Trigger | Effect |
| --- | --- | --- | --- | --- |
| *(on adoption)* | AUTO-01 … AUTO-16 | Initial rule set defined | v2.0 framework review | Baseline |

---

## 6. Framework version history

| Version | Date | Summary |
| --- | --- | --- |
| v1.0 | *not recorded* | Original split of PR review, codebase audit, shared standards, traps, and history |
| v2.0 | *(record on adoption)* | Severity × confidence mapping; automation baseline; verdict exit criteria and re-review protocol; review tiering; accepted-risk governance; audit coverage statement and baseline-diff metrics; trap metadata retrofit and staleness rules; escape-rate measurement; mass assignment, supply chain, observability, configuration, rollback, and test-quality checks |

---

## Changelog

**v2.0**

- Replaced the dual table/bullet entry formats with a single table format.
- Added Closed, Tier, and Verdict columns; flagged unrecorded verdicts and unclosed conditions.
- Applied the report naming convention and flagged "Local report" references as a retrofit backlog.
- Added Section 2, verdict transitions.
- Added Section 3, incident log, with the mandatory post-incident framework question.
- Added Section 4, framework effectiveness metrics, with escape rate as the primary measure.
- Added Section 5, automation rule change log, and Section 6, framework version history.
