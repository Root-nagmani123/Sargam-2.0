# Sargam 2.0 Review Framework

This folder separates pull-request review from entire-codebase auditing while preserving the strongest practices from the original review guideline.

| Field | Value |
| --- | --- |
| Framework version | v2.1 (agent layer) |
| Owner | Review framework owner (named maintainer) |
| Approver | Engineering lead |
| Review cadence | Every 6 months, or on change to schema conventions, role model, cache architecture, framework version, or tooling |

---

## Entrypoints

### `AGENTS.md` / `CLAUDE.md`

Thin routers at the workspace root. Codex reads `AGENTS.md`, Claude Code reads `CLAUDE.md`; both point at the same load order. Nothing else should be pasted in by the operator.

### `review-core.md`

**Always loaded, in full.** The compact decision layer: severity, confidence and the mapping between them, finding classification, the finding format, verdict definitions, priority order, and the module map that governs what else gets loaded. Around 200 lines, so it keeps its weight in context.

### `agent-operating-rules.md`

**Always loaded, in full. Outranks every other document.** Anti-fabrication evidence contract, human-handoff protocol, preflight capability discovery, the file read/write contract, determinism rules, learning capture, and stop conditions.

### `findings-schema.md`

The structured output contract — JSON Schema, a worked example, and validation assertions that turn the framework's own rules into automated checks against the reviewer's output.

---

## Reference modules — loaded on demand

### `shared-review-standards.md`

Common rules for both review modes:

- Severity, confidence, and **how the two interact to determine a verdict**
- Finding format, including detection source and closure evidence
- **Pre-review automation baseline** and the AUTO-01 … AUTO-16 rule set
- Security — including mass assignment, dependencies and supply chain, and the secret-exposure response path
- Concurrency, scheduled tasks, queued jobs, and transactions
- G1–G8 database guidelines
- Migration, **rollback and post-deploy verification**, API, frontend, and testing checks
- **Observability and audit logging**, and configuration/environment checks
- Evidence requirements
- **Appendix A** — canonical platform facts: timezone convention, data volume reference, index inventory
- **Appendix B** — framework governance

### `pr-review-guidelines.md`

Use for a specific PR. Focuses on changed and directly affected code and produces a merge-oriented verdict.

Adds in v2.0: **review effort tiers**, automation gates, risk-based database enumeration, **verdict exit criteria with owners and time bounds**, the **re-review protocol**, and a **report naming convention**.

### `codebase-audit-guidelines.md`

Use for a full repository or module audit. Includes legacy issues and produces a prioritised remediation programme rather than a PR verdict.

Adds in v2.0: **accepted-risk and false-positive governance**, a mandatory **coverage and method confidence statement**, **baseline-diff metrics** (fixed, new, recurring, regressed), and remediation-band target times.

### `sargam-known-review-traps.md`

Living codebase-specific knowledge that applies to future PR reviews and audits.

Adds in v2.0: full metadata per trap (**State, Detection, Rule, Verified, Revalidate when**), **lifecycle states and a 6-month staleness rule**, an **Archive** for retired traps, and links from each automatable trap to its automated rule.

### `review-history.md`

Historical review outcomes, kept outside the core prompt to prevent guideline bloat.

Adds in v2.0: a single entry format, verdict transitions, an **incident log with a mandatory post-incident question**, **framework effectiveness metrics led by escape rate**, and an automation rule change log.

---

## Usage

Reviews are performed by agents (Codex or Claude Code). The router files handle load order; operators do not paste guidelines in manually.

### PR review

1. `review-core.md`
2. `agent-operating-rules.md`
3. `findings-schema.md`
4. `pr-review-guidelines.md` §3–5, plus modules the change actually touches
5. `sargam-known-review-traps.md` — subject to its provenance warning

Then: preflight → declare tier → run gates → review → emit report + findings JSON → append to `review-history.md`.

### Entire codebase audit

1. `review-core.md`
2. `agent-operating-rules.md`
3. `findings-schema.md`
4. `codebase-audit-guidelines.md`
5. `sargam-known-review-traps.md`

Then: automation baseline repository-wide → triage candidates → refresh Appendix A.2/A.3 → coverage statement, baseline comparison, and findings.

### Module loading discipline

Load what the change touches, not everything. A Tier 0 documentation review loads the core, the agent rules, the schema, and the method section — nothing about billing, concurrency, or migrations. Record modules loaded in the report header.

---

## Operating principles

**Assert nothing unverified.** Every path, line number, column type, index, caller count, and gate result must trace to something read or run in that session. This outranks completeness. The framework's largest risk is not a missed defect — it is a confident invented fact that propagates into the register and the traps file.

**Handoffs are explicit.** Credential rotation, domain confirmation, risk acceptance, escalation, and condition verification on money, permission, and migration paths belong to named humans. The agent emits the request and stops.

**Automation first.** No reviewer spends manual effort on anything a rule already detects. Automation runs before the human review starts, and its results are recorded in the report.

**Severity without confidence is not a verdict.** A Blocker at Low confidence is an evidence request, not a merge block. The mapping table in the shared standards is authoritative.

**Every conclusion is closable.** Findings state their closure evidence. Verdicts state exit criteria, owner, and time bound. An accepted risk states who accepted it and when it expires.

**Coverage is part of the finding.** An audit that reports no critical issue must also report what it actually examined. Silence about a module is not a clean bill of health.

**The framework learns.** Every production defect is checked against the framework. If nothing here would have caught it, a trap or rule is added in the same cycle. Escape rate is the measure of whether any of this is working.

**Agreement between agents is not evidence.** Models with similar training, given identical prompts, produce correlated errors. Where two agents review the same change, treat divergence as the signal worth investigating and convergence as no evidence at all.

---

## Important separation rule

A codebase-audit finding is not automatically a PR finding. It affects a PR only when the PR introduces it, worsens it, modifies the affected logic, or depends on it in a way that makes the new change unsafe.

Legacy findings noticed during a PR review are recorded under "Follow-up items outside this PR" and added to the remediation register. They never affect the PR verdict.

---

## Adoption checklist

Sequenced deliberately — evidence rules first, trust in the register last.

**Phase 1 — before any review runs**

- [ ] Place `AGENTS.md` and `CLAUDE.md` at the workspace root
- [ ] Confirm the agent loads `review-core.md` and `agent-operating-rules.md` first
- [ ] Set up `reviews/` and `remediation-register.md`
- [ ] Name the framework owner and approver, and the human verifier for money/permission/migration conditions

**Phase 2 — one human-audited trial**

- [ ] Run a single Tier 2 review under v2.1
- [ ] Engineer spends one hour checking only factual assertions — files, line ranges, indexes, callers, gate output
- [ ] Record the fabrication rate in `review-history.md` §0
- [ ] Proceed only if assertions hold; if not, fix the evidence rules before anything else

**Phase 3 — rebuild the knowledge base**

- [ ] Re-verify traps 1–25 against current source, quoting the line and SHA that proves each
- [ ] Archive traps that cannot be proven
- [ ] Confirm or retire Candidate traps 26–28
- [ ] Re-triage the existing remediation register against the evidence rules

**Phase 4 — operate and measure**

- [ ] Implement rules AUTO-01 … AUTO-16
- [ ] Record the PHPStan/Larastan baseline level in the repository
- [ ] Populate Appendix A.1 (timezone convention) — blocks accurate date-boundary findings until done
- [ ] Populate Appendix A.2 (data volume reference) — blocks accurate G1/G2/G5/G8 severities until done
- [ ] Populate Appendix A.3 (index inventory) from migrations and `SHOW INDEX`
- [ ] Retrofit `review-history.md` entries to the naming convention; establish the closure state of PR #249's conditions
- [ ] Begin the incident log and escape-rate measurement from the adoption date
