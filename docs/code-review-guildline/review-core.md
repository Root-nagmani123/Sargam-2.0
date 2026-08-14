# Sargam 2.0 — Review Core

> **Always loaded. Read this in full before anything else.**
> Everything else in this framework is reference material, loaded on demand per Section 8.

| Field | Value |
| --- | --- |
| Framework version | v2.1 (agent layer) |
| Applies to | Automated reviews performed by Codex, Claude Code, or equivalent agents |
| Read next | `agent-operating-rules.md` — mandatory, no exceptions |

---

## 1. What a review is

Determine whether specific changes are safe to merge (PR review), or catalogue and prioritise risk across a codebase (audit). Produce a report a human can act on, and a structured findings file a system can process.

A review is **not** a summary of the diff, and not a list of everything that could theoretically be improved. It is a decision, with evidence.

---

## 2. The four rules that override everything

1. **Never assert what you have not verified.** Every file path, line number, column type, index, caller count, and gate result must come from something you actually read or ran in this session. See `agent-operating-rules.md` Section 2. This rule outranks completeness, helpfulness, and the desire to produce a clean report.
2. **"Unable to conclude" is a correct answer.** When source, schema, domain, or environment evidence is missing, say so and name who can supply it. A confident wrong verdict is worse than an honest gap.
3. **Do not act outside the review.** No commits, pushes, GitHub comments, branch changes, or edits to application source. Write only the files listed in the read/write contract.
4. **Severity without confidence is not a verdict.** Use the table in Section 5.

---

## 3. Finding classification

| Classification | Meaning | Affects PR verdict? |
| --- | --- | --- |
| **Introduced** | Created by this change set | Yes |
| **Worsened** | Pre-existing issue whose impact is materially increased | Yes |
| **Touched** | Pre-existing issue in logic directly modified or depended upon | Yes, when material |
| **Legacy / Repository-wide** | Pre-existing issue outside the change, or found in an audit | No — follow-up section only |
| **Unrelated** | Outside the change path, no dependency | No — exclude |

---

## 4. Severity

Based on realistic impact, not on which clause was triggered.

| Severity | Bar |
| --- | --- |
| **Blocker** | Merging is unsafe: runtime failure on a real path, authorisation bypass, data corruption or loss, exposed secret, unsafe irreversible migration, confirmed wrong financial result |
| **High** | Likely material production impact: broken common workflow, object-level access-control failure, partial write or unsafe transaction, duplicate money/approval/inventory operation, severe N+1 or unbounded query on a high-volume path, privilege escalation via mass assignment |
| **Medium** | Material but bounded: missing pagination on growing data, cache invalidation weakness, unprotected concurrency assumption, backward-compatibility concern, important evidence or test gap, privileged action without audit trail |
| **Low** | Hygiene: dead code, minor duplication, narrow inefficiency, misleading comments |
| **Advisory** | Improvement that should not block merge |

**A Medium is "material"** — and therefore capable of blocking merge — when any of these hold: it can produce a wrong user-visible or stored result under realistic input; it sits on a money, approval, permission, or retention path; it degrades a shared or high-frequency path; it removes a safety property that previously existed; or fixing it after merge would need a data fix rather than a code fix.

---

## 5. Confidence, and how it gates the verdict

| Confidence | Meaning |
| --- | --- |
| **High** | Directly proven from source or schema you read this session |
| **Medium** | Strong indication; runtime, schema, environment, or domain evidence still required |
| **Low** | Plausible concern; must not block without verification |

| Severity ↓ / Confidence → | High | Medium | Low |
| --- | --- | --- | --- |
| **Blocker** | Blocks merge | Evidence required (Blocker candidate) — blocks until resolved | Follow-up; does not block |
| **High** | Blocks merge | Evidence required (High candidate) — condition on merge | Follow-up; does not block |
| **Medium (material)** | Condition on merge | Condition on merge | Follow-up |
| **Medium (non-material)** | Record | Record | Record |
| **Low / Advisory** | Record | Record | Record |

**No Blocker or High may be asserted at Low confidence.** Restate it as an evidence request naming exactly what would confirm or clear it. A Medium-confidence Blocker keeps its candidate severity — it is never silently downgraded, so it cannot be lost.

---

## 6. Mandatory finding format

Every finding, in both the report and the structured file.

```text
[HIGH][CORRECTNESS] Billing units may use the wrong meter record
Confidence: High
Classification: Introduced
Detection: Manual source review        # Manual | Rule AUTO-nn | Trap n | Incident
Evidence: read app/Http/Controllers/Admin/EstateController.php lines 8112-8180

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
Use a stable numeric year-month key, or separate numeric year/month columns.

Closure evidence:
Passing tests for normal month, meter-change month, and missing-meter cases.
```

`Evidence`, `Detection`, and `Closure evidence` are mandatory. A finding missing any of them is not emitted. Group repeated instances by root cause with a representative location.

---

## 7. Verdicts

| Verdict | When | Exit |
| --- | --- | --- |
| **Approve** | No confirmed Blocker, High, or unresolved material Medium | Merge |
| **Approve with conditions** | No confirmed Blocker or High, but material Medium actions or evidence required | Numbered checklist, each with closure evidence and a named owner; **a human verifies conditions on money, permission, and migration paths** |
| **Request changes** | Confirmed Blocker/High, or unresolved material Medium that can cause wrong production behaviour | Fix commit, then re-review |
| **Unable to conclude** | Critical source, schema, domain, environment, or test evidence unavailable | Name what is missing and the human who can supply it. May apply to one area while the rest concludes |
| **Partial** | Review deliberately stopped before completion | **Never authorises a merge.** State what was covered, what was not, and why |

Audits do not use these. Audit outcomes are: critical remediation required · high-risk backlog identified · moderate technical debt · no critical issue found *(only with full-coverage manual statement)* · coverage insufficient to conclude.

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
11. Configuration, dependencies, supply chain
12. Test adequacy
13. Maintainability

---

## 9. Reference modules — load on demand

Do not load everything. Load what the change actually touches. Record which modules you loaded in the report header.

| Module | File · Section | Load when |
| --- | --- | --- |
| Agent operating rules | `agent-operating-rules.md` | **Always. Before any other module.** |
| Findings schema | `findings-schema.md` | **Always**, before emitting output |
| Method and tiers | `pr-review-guidelines.md` §3–5 | Every PR review |
| Audit method | `codebase-audit-guidelines.md` | Every audit |
| Known traps | `sargam-known-review-traps.md` | Every review — but see the provenance warning in that file |
| Database G1–G8 | `shared-review-standards.md` §13 | Any query, model, or migration change |
| Security detail | `shared-review-standards.md` §10 | Any route, policy, controller, upload, or dependency change |
| Concurrency and jobs | `shared-review-standards.md` §11–12 | Any transaction, job, approval, money, or status-transition change |
| Migration and rollback | `shared-review-standards.md` §14 | Any migration or schema change |
| API and frontend | `shared-review-standards.md` §15–16 | Any API, Blade, or JS change |
| Observability and config | `shared-review-standards.md` §17–18 | Privileged actions, logging, config, storage, `env()` |
| Testing | `shared-review-standards.md` §19 | Any test file changed, or any Blocker/High fix |
| Canonical facts | `shared-review-standards.md` Appendix A | Any volume, index, timezone, or date-boundary claim |

**Tier 0** (docs, comments, copy, styling, no logic) loads only the agent rules, the schema, and the method section. Do not pull billing, concurrency, or migration guidance into a documentation review.

---

## 10. Before you finish

- [ ] Every assertion traceable to something read or run this session
- [ ] Every Blocker/High at High confidence, or restated as an evidence request
- [ ] Every finding has Evidence, Detection, and Closure evidence
- [ ] Verdict carries exit criteria, owner, and — where required — a named human verifier
- [ ] Gate results recorded, including gates that could not run
- [ ] Modules loaded recorded in the header
- [ ] Structured findings file emitted and valid against the schema
- [ ] Only permitted files written; no commit, push, or GitHub post
