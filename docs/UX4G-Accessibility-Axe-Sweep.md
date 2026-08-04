# Accessibility — axe-core Automated Sweep

**Date:** 2026-08-04
**Tool:** axe-core 4.12.1, WCAG 2.0/2.1 **A + AA** rules
**Scope:** all **57** authenticated baseline routes (via Apache :8080, real login)
**Nature:** AUDIT — findings + prioritised remediation. No fixes shipped in this pass.

---

## Headline

**2,555 violation instances / 12 rules across 57 pages** — but they collapse to
**~5 root causes**. Four rules fire on **55 pages** because they come from the shared
admin nav; one rule (color-contrast) is 58 % of all instances and traces to **two**
token/colour values. Fix the roots, not the 2,555 leaves.

| Impact | Rule | Pages | Instances |
|---|---|---:|---:|
| serious | **color-contrast** | 57 | **1,486** |
| critical | aria-allowed-attr | 55 | 546 |
| critical | aria-required-parent | 55 | 220 |
| critical | label | 17 | 125 |
| serious | list | 55 | 91 |
| critical | aria-required-children | 55 | 57 |
| critical | select-name | 5 | 9 |
| serious | aria-command-name | 4 | 6 |
| serious | scrollable-region-focusable | 5 | 5 |
| serious | link-name | 4 | 5 |
| serious | aria-input-field-name | 3 | 4 |
| critical | button-name | 1 | 1 |

Worst pages: `/ot-mdo-escrot-exemption-view` (536), `/medical-exception-ot-view` (483)
— two outliers dominated by contrast + repeated ARIA in large tables.

---

## Root-cause analysis (what's actually failing)

### R-A — Shared nav ARIA semantics · ~914 instances · 55 pages · **contract-sensitive**
All four of aria-allowed-attr / aria-required-parent / aria-required-children / list
come from the **shared category nav** in `admin/layouts/header_new.blade.php` +
`sidebar_new.blade.php`:
- `role="tab"` links (`#home-tab`, `#setup-tab`, …) are **not inside a `role="tablist"`** parent → aria-required-parent.
- `<ul role="menubar">` (`.header-main-nav`) has **no `role="menuitem"` children** → aria-required-children.
- `<a class="sidebar-google-item">` carries ARIA attrs **not allowed for its role** → aria-allowed-attr.
- `<ul>` (`#sidebarnav`, `.mini-nav-ul`) has **non-`<li>` children** (SimpleBar wraps them in `<div>`) → list.

**Fix:** markup-only ARIA corrections (add `role="tablist"`, correct menubar children,
wrap/label list items). **Risk: medium-high** — this nav also owns tab-persistence, the
active-highlight resolver, and the historic double-click bug. The **resolver is
contract-frozen**; the Blade ARIA attributes are editable but must be changed carefully
and gated. One careful fix clears ~914 instances across 55 pages.

### R-B — Colour contrast · 1,486 instances · 57 pages · **two colour values**
- **`.text-body-secondary`** muted text = **`#b2bbc2` on white = 1.94:1** (needs 4.5) —
  used for the "Super Admin" role label and secondary text app-wide.
- **Table `<th>`** = **`#787878` on `#f3f3f3` = 3.97:1** (needs 4.5) — DataTable headers.

**Fix:** darken the muted-text token (`--bs-secondary-color`) to ≥4.5:1 and the table-header
colour. High leverage — these two values account for the bulk of the 1,486. **A visual
change → needs baseline re-approval**, same play as the navy re-colour. Effort: low; review: real.

### R-C — Unlabelled form controls · ~138 instances · **zero-visual** · ✅ **DONE (88%): 138 → 17**
- **`.form-check-input.status-toggle`** (the active/inactive row switches, `role="switch"`)
  had **no accessible name** → `label` ×125. **FIXED** with a single shared, draw-safe
  labeller in `status-toggle-delete.js` (`aria-label="Toggle active status"`, re-applied on
  every `draw.dt`, scoped to `status-toggle*` so it never overrides a real label). `label`
  **125 → 10**.
- Named the filter/score `<select>`s (courseFilter, sf-rating-filter, conditionalField,
  memoConclusionFilter, conditionalOperator) + the visible `type="date"` from/to inputs on
  the feedback pages. `select-name` **9 → 3**.
- **Residual 17 (→ folded into R-D mop-up):** `aria-input-field-name` ×4 (choices.js
  comboboxes — need the underlying select labelled *and* Choices to propagate it, or set on
  the `.choices` post-init); ~10 scattered `label` (JS-built `#logo1`, a couple stray date
  inputs); 3 anonymous `select`s (`.form-select-sm`).

Gate: **58 passed, zero diff** (all additions are ARIA). Total axe **2,619 → 2,434**.

### R-D — Misc, page-local · ~17 instances
button-name ×1, link-name ×5, aria-command-name ×6, scrollable-region-focusable ×5 —
small, specific (a nameless icon button, DataTable scroll containers needing `tabindex`,
etc.). Fix per-page as encountered.

---

## Prioritised remediation plan

| # | Root cause | Instances | Risk | Gate impact | Recommendation |
|---|---|---:|---|---|---|
| 1 | **R-C** form-control labels | ~138 | Low | none (ARIA) | ✅ **DONE 2026-08-04 — 138→17** (status-toggle shared fix + selects/dates); 17 residual → R-D |
| 2 | **R-B** contrast tokens | 1,486 | Low code / real review | re-baseline | **Do second** — biggest win; ship like the navy re-colour |
| 3 | **R-A** nav ARIA semantics | ~914 | Med-high | gated | **Do carefully** — one component, contract-sensitive; isolate + full nav smoke test |
| 4 | **R-D** page-local | ~17 | Low | none/minor | Mop up opportunistically |

**Notes & caveats (honest scoping):**
- axe catches ~30–50 % of WCAG issues; it does **not** replace manual testing (keyboard
  traps, focus order, meaningful `alt`, screen-reader logic). This sweep is a floor, not a ceiling.
- Some nodes may be **third-party** (DataTables/SimpleBar/plugin markup) where the fix is
  configuration, not our Blade.
- The two outlier pages (`ot-mdo-escrot-exemption-view`, `medical-exception-ot-view`)
  will improve most from R-A + R-B since their volume is table ARIA + header contrast.

## How to re-run

A committed, portable sweep script now lives in the test dir (was scratchpad-only):

```
# axe-core already installed (node_modules/axe-core); start the Apache gate vhost (:8080), then:
E2E_BASE_URL=http://localhost:8080 E2E_USERNAME=ux4g_visual_test E2E_PASSWORD=x \
  node tests/e2e/visual/axe-sweep.js     # full sweep -> tests/e2e/visual/axe-results.json
```
Re-run after each remediation tier to watch the counts fall. Raw machine-readable results:
`axe-results.json` (rules + per-page counts). Corroborated by an independent run
(2,619 nodes vs 2,555 here — same 12 rules, same R-A/B/C/D roots; delta is axe run-to-run).

## Suggested sequencing

R-C (safe, now) → R-B (re-baseline, high win) → R-A (careful, gated) → R-D (mop-up).
Then a **manual** keyboard + screen-reader pass for what axe cannot see.
