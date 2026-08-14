# Sargam 2.0 — Structured Findings Schema

> **Purpose:** Every review emits a machine-readable findings file alongside the human report, so the remediation register, effectiveness metrics, and report validation are mechanical rather than transcribed by hand.

| Field | Value |
| --- | --- |
| Version | v1.0 |
| Emitted to | `reviews/pr-<n>-findings.json` or `reviews/audit-<date>-<scope>-findings.json` |
| Validation | Every file must pass Section 4 before the review is considered complete |

---

## 1. Why this exists

A prose report cannot be checked. A structured file can — you can assert mechanically that no Blocker was emitted at Low confidence, that every finding has closure evidence, and that every claimed location has supporting evidence. Section 4 turns the framework's own rules into automated assertions against the reviewer's output.

The JSON is the source of truth for the register and metrics. The Markdown report is the source of truth for the human reader. Both are emitted; they must not disagree.

---

## 2. Schema

```json
{
  "$schema": "https://json-schema.org/draft/2020-12/schema",
  "title": "Sargam 2.0 Review Findings",
  "type": "object",
  "required": ["framework_version", "review", "preflight", "findings", "verdict", "human_actions", "framework_feedback"],
  "properties": {
    "framework_version": { "type": "string" },
    "schema_version": { "type": "string", "const": "1.0" },

    "review": {
      "type": "object",
      "required": ["mode", "identifier", "date", "agent", "modules_loaded"],
      "properties": {
        "mode":       { "enum": ["pr", "audit"] },
        "identifier": { "type": "string", "description": "PR number, or audit scope" },
        "title":      { "type": "string" },
        "tier":       { "enum": ["0", "1", "2", "n/a"] },
        "tier_trigger": { "type": "string" },
        "base_branch":{ "type": "string" },
        "head_sha":   { "type": "string" },
        "date":       { "type": "string", "format": "date" },
        "agent":      { "type": "string", "description": "Agent and model performing the review" },
        "modules_loaded": { "type": "array", "items": { "type": "string" } }
      }
    },

    "preflight": {
      "type": "object",
      "required": ["worktree", "tools"],
      "properties": {
        "worktree":       { "enum": ["confirmed", "failed"] },
        "schema_access":  { "type": "boolean" },
        "network_access": { "type": "boolean" },
        "tools": {
          "type": "array",
          "items": {
            "type": "object",
            "required": ["name", "status"],
            "properties": {
              "name":   { "type": "string" },
              "status": { "enum": ["available", "unavailable", "not_run", "failed"] },
              "result": { "enum": ["clean", "issues", "unknown"] },
              "output_recorded": { "type": "boolean" },
              "notes":  { "type": "string" }
            }
          }
        }
      }
    },

    "findings": {
      "type": "array",
      "items": {
        "type": "object",
        "required": [
          "id", "severity", "confidence", "classification", "type",
          "title", "location", "issue", "impact", "required_change",
          "detection", "evidence", "closure_evidence", "status"
        ],
        "properties": {
          "id":       { "type": "string", "pattern": "^F-[0-9]{3}$" },
          "severity": { "enum": ["blocker", "high", "medium", "low", "advisory"] },
          "confidence": { "enum": ["high", "medium", "low"] },
          "material": { "type": "boolean", "description": "Required when severity is medium" },
          "classification": {
            "enum": ["introduced", "worsened", "touched", "legacy", "unrelated"]
          },
          "type": {
            "enum": ["security", "correctness", "data_integrity", "reliability",
                     "concurrency", "performance", "migration", "api_contract",
                     "frontend", "observability", "configuration", "testing",
                     "maintainability", "evidence_required", "architectural"]
          },
          "title": { "type": "string" },
          "location": {
            "type": "object",
            "required": ["file"],
            "properties": {
              "file":       { "type": "string" },
              "line_start": { "type": "integer" },
              "line_end":   { "type": "integer" },
              "function":   { "type": "string" }
            }
          },
          "additional_locations": { "type": "array", "items": { "type": "object" } },
          "issue":            { "type": "string" },
          "failure_scenario": { "type": "string" },
          "impact":           { "type": "string" },
          "required_change":  { "type": "string" },
          "detection": {
            "type": "object",
            "required": ["method"],
            "properties": {
              "method":  { "enum": ["manual", "rule", "trap", "incident"] },
              "rule_id": { "type": "string", "pattern": "^AUTO-[0-9]{2}$" },
              "trap_id": { "type": "integer" }
            }
          },
          "evidence": {
            "type": "array",
            "minItems": 1,
            "description": "Concrete session actions supporting this finding",
            "items": {
              "type": "object",
              "required": ["kind", "reference"],
              "properties": {
                "kind": { "enum": ["file_read", "search", "tool_output", "schema", "test_run"] },
                "reference": { "type": "string" },
                "detail":    { "type": "string" }
              }
            }
          },
          "unverified": {
            "type": "array",
            "description": "What could not be confirmed, and who can supply it",
            "items": {
              "type": "object",
              "required": ["claim", "needed", "owner_role"],
              "properties": {
                "claim":      { "type": "string" },
                "needed":     { "type": "string" },
                "owner_role": { "type": "string" }
              }
            }
          },
          "closure_evidence": { "type": "string" },
          "blocks_merge":     { "type": "boolean" },
          "grouped_instances":{ "type": "integer" },
          "status": {
            "enum": ["open", "in_progress", "fixed", "accepted_risk", "false_positive"]
          },
          "first_seen": { "type": "string" },
          "disposition": {
            "type": "object",
            "description": "Re-review only",
            "properties": {
              "outcome": { "enum": ["fixed", "partially_fixed", "not_fixed",
                                    "no_longer_applicable", "accepted_risk"] },
              "evidence": { "type": "string" },
              "fix_commit": { "type": "string" }
            }
          }
        }
      }
    },

    "verified_clean": {
      "type": "array",
      "items": {
        "type": "object",
        "required": ["check", "evidence"],
        "properties": {
          "check":    { "type": "string" },
          "evidence": { "type": "string" }
        }
      }
    },

    "db_operations": {
      "type": "array",
      "items": {
        "type": "object",
        "required": ["ref", "operation", "location", "classification", "clauses"],
        "properties": {
          "ref":            { "type": "integer" },
          "operation":      { "type": "string" },
          "location":       { "type": "string" },
          "classification": { "enum": ["introduced", "worsened", "touched", "pre_existing"] },
          "enumerated_individually": { "type": "boolean" },
          "clauses": {
            "type": "object",
            "description": "G1-G8 result per clause",
            "additionalProperties": { "enum": ["pass", "fail", "evidence_needed", "n/a"] }
          },
          "finding_ids": { "type": "array", "items": { "type": "string" } }
        }
      }
    },

    "verdict": {
      "type": "object",
      "required": ["decision", "summary", "exit_criteria"],
      "properties": {
        "decision": {
          "enum": ["approve", "approve_with_conditions", "request_changes",
                   "unable_to_conclude", "partial",
                   "critical_remediation_required", "high_risk_backlog",
                   "moderate_technical_debt", "no_critical_issue_found",
                   "coverage_insufficient"]
        },
        "summary": { "type": "string" },
        "exit_criteria": { "type": "string" },
        "conditions": {
          "type": "array",
          "items": {
            "type": "object",
            "required": ["condition", "closure_evidence", "owner_role"],
            "properties": {
              "condition":         { "type": "string" },
              "closure_evidence":  { "type": "string" },
              "owner_role":        { "type": "string" },
              "human_verification_required": { "type": "boolean" },
              "finding_ids":       { "type": "array", "items": { "type": "string" } },
              "closed":            { "type": "boolean" }
            }
          }
        },
        "unable_to_conclude_areas": {
          "type": "array",
          "items": {
            "type": "object",
            "required": ["area", "missing", "owner_role"],
            "properties": {
              "area":       { "type": "string" },
              "missing":    { "type": "string" },
              "owner_role": { "type": "string" }
            }
          }
        },
        "partial_scope": {
          "type": "object",
          "properties": {
            "covered":    { "type": "string" },
            "not_covered":{ "type": "string" },
            "reason":     { "type": "string" }
          }
        }
      }
    },

    "coverage": {
      "type": "array",
      "description": "Audit mode only. Mandatory when mode is audit.",
      "items": {
        "type": "object",
        "required": ["area", "method", "coverage", "negative_confidence"],
        "properties": {
          "area":     { "type": "string" },
          "method":   { "enum": ["manual", "automated_scan", "not_covered"] },
          "coverage": { "enum": ["full", "partial", "sampled", "none"] },
          "negative_confidence": { "enum": ["high", "medium", "low", "none"] }
        }
      }
    },

    "human_actions": {
      "type": "array",
      "items": {
        "type": "object",
        "required": ["action", "owner_role", "blocking"],
        "properties": {
          "action":      { "type": "string" },
          "owner_role":  { "type": "string" },
          "blocking":    { "type": "boolean" },
          "finding_ids": { "type": "array", "items": { "type": "string" } }
        }
      }
    },

    "evidence_gaps": {
      "type": "array",
      "items": {
        "type": "object",
        "required": ["gap", "owner_role"],
        "properties": {
          "gap":         { "type": "string" },
          "owner_role":  { "type": "string" },
          "affects":     { "type": "array", "items": { "type": "string" } }
        }
      }
    },

    "framework_feedback": {
      "type": "object",
      "required": ["assertions_removed_at_self_check"],
      "properties": {
        "new_trap_candidates": { "type": "array", "items": { "type": "object" } },
        "rule_proposals":      { "type": "array", "items": { "type": "object" } },
        "noisy_rules":         { "type": "array", "items": { "type": "object" } },
        "stale_traps_hit":     { "type": "array", "items": { "type": "object" } },
        "guidance_gaps":       { "type": "array", "items": { "type": "string" } },
        "assertions_removed_at_self_check": { "type": "integer" }
      }
    }
  }
}
```

---

## 3. Minimal example

```json
{
  "framework_version": "2.1",
  "schema_version": "1.0",
  "review": {
    "mode": "pr",
    "identifier": "254",
    "title": "Add meter reading import",
    "tier": "2",
    "tier_trigger": "money path — billing units",
    "base_branch": "develop",
    "head_sha": "a1b2c3d",
    "date": "2026-07-27",
    "agent": "claude-code",
    "modules_loaded": ["agent-operating-rules", "findings-schema",
                       "pr-guidelines-3-5", "shared-13", "shared-11-12", "traps"]
  },
  "preflight": {
    "worktree": "confirmed",
    "schema_access": false,
    "network_access": true,
    "tools": [
      { "name": "phpstan", "status": "available", "result": "clean", "output_recorded": true },
      { "name": "composer audit", "status": "available", "result": "clean", "output_recorded": true },
      { "name": "phpunit", "status": "failed", "result": "unknown", "output_recorded": true,
        "notes": "suite errors on bootstrap; recorded as evidence gap" }
    ]
  },
  "findings": [
    {
      "id": "F-001",
      "severity": "high",
      "confidence": "high",
      "classification": "introduced",
      "type": "concurrency",
      "title": "Import can create duplicate readings on retry",
      "location": {
        "file": "app/Imports/MeterReadingImport.php",
        "line_start": 44, "line_end": 71, "function": "model()"
      },
      "issue": "exists() check followed by insert, with no unique constraint on (meter_id, reading_date).",
      "failure_scenario": "A retried queue job re-processes the same row and inserts a second reading.",
      "impact": "Duplicate readings feed the billing calculation and can produce an inflated bill.",
      "required_change": "Add a unique constraint and use upsert, or an idempotency key on the import batch.",
      "detection": { "method": "trap", "trap_id": 21 },
      "evidence": [
        { "kind": "file_read", "reference": "app/Imports/MeterReadingImport.php:44-71" },
        { "kind": "search", "reference": "rg 'unique' database/migrations/*meter*", "detail": "no unique index found on meter_readings" }
      ],
      "unverified": [
        { "claim": "No unique constraint exists in the deployed database",
          "needed": "SHOW INDEX FROM meter_readings",
          "owner_role": "DBA" }
      ],
      "closure_evidence": "Migration adding the unique constraint, plus a test asserting a retried import does not duplicate.",
      "blocks_merge": true,
      "status": "open"
    }
  ],
  "verified_clean": [
    { "check": "New import route carries auth and estate.manage permission middleware",
      "evidence": "routes/web.php:212-218 read; policy method confirmed in EstatePolicy.php:88" }
  ],
  "db_operations": [
    { "ref": 1, "operation": "insert meter_readings", "location": "MeterReadingImport::model()",
      "classification": "introduced", "enumerated_individually": true,
      "clauses": { "G1": "pass", "G5": "pass", "G7": "fail" },
      "finding_ids": ["F-001"] }
  ],
  "verdict": {
    "decision": "request_changes",
    "summary": "One confirmed High on the billing path: the import is not retry-safe and can duplicate readings.",
    "exit_criteria": "F-001 fixed with unique constraint and regression test; re-review of fix diff.",
    "unable_to_conclude_areas": [
      { "area": "index coverage on meter_readings", "missing": "SHOW INDEX output", "owner_role": "DBA" }
    ]
  },
  "human_actions": [
    { "action": "Confirm whether duplicate readings in an import batch are ever legitimate",
      "owner_role": "Estate module owner", "blocking": true, "finding_ids": ["F-001"] }
  ],
  "evidence_gaps": [
    { "gap": "Test suite does not run; no QA evidence available", "owner_role": "QA", "affects": ["F-001"] }
  ],
  "framework_feedback": {
    "rule_proposals": [
      { "proposal": "Extend AUTO-13 to flag exists()-then-insert inside Import classes",
        "reason": "Trap 21 hit manually; pattern is mechanically detectable" }
    ],
    "stale_traps_hit": [ { "trap_id": 21, "reverified_this_session": true } ],
    "assertions_removed_at_self_check": 2
  }
}
```

---

## 4. Validation assertions

Run these against every emitted findings file. A failure means the review is not complete — not that the assertion should be relaxed.

**Evidence integrity**

- Every finding has `evidence` with at least one entry.
- Every `location.file` appears in at least one `evidence.reference` for that finding.
- Every finding with `line_start` has a `file_read` evidence entry covering that range.
- Every tool with `result: clean` has `output_recorded: true`.
- No tool has `status: not_run` while its result is reported as clean.

**Severity discipline**

- No finding with `severity: blocker` or `high` has `confidence: low`.
- Every `severity: medium` finding declares `material`.
- `blocks_merge: true` only where the severity × confidence table permits it.

**Completeness**

- Every finding has non-empty `closure_evidence`.
- Every finding has a unique `id` matching `^F-[0-9]{3}$`; IDs are contiguous from `F-001`.
- Findings are ordered blocker → advisory, then by file, then by line.

**Verdict consistency**

- `approve` requires no finding with `blocks_merge: true`.
- `approve_with_conditions` requires at least one entry in `verdict.conditions`; each has `closure_evidence` and `owner_role`.
- Any condition whose `finding_ids` reference a money, permission, or migration finding has `human_verification_required: true`.
- `request_changes` requires at least one confirmed blocker/high, or a material medium with `blocks_merge: true`.
- `unable_to_conclude` requires at least one entry in `unable_to_conclude_areas`, each with an `owner_role`.
- `partial` requires `partial_scope` fully populated, and `blocks_merge` is irrelevant — a partial never authorises merge.
- `no_critical_issue_found` requires `mode: audit` and every `coverage` entry to be `method: manual` with `coverage: full`.

**Mode-specific**

- `mode: audit` requires a non-empty `coverage` array.
- `mode: pr` requires `tier` and `tier_trigger`.
- Findings with `classification: legacy` or `unrelated` must have `blocks_merge: false` in PR mode.

**Handoff**

- Every `unverified` entry has an `owner_role`.
- Every finding with an `unverified` entry has `confidence` no higher than `medium`.
- `human_actions` contains an entry for every blocking `unable_to_conclude_areas` item.

---

## 5. Downstream use

| Consumer | Uses |
| --- | --- |
| `remediation-register.md` | Findings with `classification: legacy`, or any unresolved finding at review close |
| `review-history.md` §1 | `review`, `verdict.decision`, one-line lesson from `framework_feedback` |
| `review-history.md` §4 | Findings-per-review by severity; confirmed vs dismissed rule candidates; conditions closed |
| Escape-rate analysis | Incident root cause matched against emitted `findings` to determine whether the review caught it |
| Trap maintenance | `stale_traps_hit`, `new_trap_candidates` |
| Rule tuning | `rule_proposals`, `noisy_rules` |

The `assertions_removed_at_self_check` count is worth tracking across reviews. A persistent zero, on a framework whose main risk is fabrication, is more likely to mean the self-check is not being performed than that nothing needed removing.
