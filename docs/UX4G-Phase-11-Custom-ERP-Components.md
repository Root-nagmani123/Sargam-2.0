# Phase 11 — Custom ERP Components

**Status:** ✅ Complete
**Date:** 2026-08-04
**Depends on:** Phases 5–9 (component primitives) + Phase 10 (plugins)
**Gate result:** **58 passed, zero visual diff**

---

## Objective

Confirm the application's **bespoke** Blade components — the ones the ERP authored
itself, not Bootstrap/UX4G primitives — render and behave correctly under the UX4G
(Bootstrap 5.3) baseline, and bring their accessibility to parity where it is cheap
and risk-free. Resolve the one item the plugin audit flagged for this phase:
`jquery-steps` → UX4G Stepper.

**In scope:** custom component inventory + compatibility confirmation; zero-visual
a11y parity for the three steppers; a disposition for the `jquery-steps` wizard.
**Out of scope (migration rules):** the AJAX step-validation logic in the member
wizard, role/permission logic inside components, any markup that would alter render.

---

## Custom component inventory (by usage)

| Component | Usages | Bootstrap 5 / UX4G? | Action |
|---|---|---|---|
| `<x-breadcrum>` | 309 | ✅ clean | None |
| `<x-session>` (session_message) | 156 | ✅ BS5 (`btn-close`, `data-bs-dismiss`, `bootstrap.Alert` API) | None |
| `<x-input>` | 147 | ✅ clean | None |
| `<x-select>` | 77 | ✅ clean | None |
| `<x-estate-workflow-stepper>` | 7 | ✅ BS5 (`.alert .badge .d-flex .gap-*`, `bi` icons) | **+`aria-current`** |
| `partials/step-indicator` | FC reg | ✅ BS5 utilities + inline styles | **+`aria-current`** |
| `fc/…/fc-stepper` | FC forms | ✅ BS5 + already had `aria-current` | None (reference impl) |
| `<x-data-table.table>` | 4 | ✅ (Phase 8 tables) | None |
| `breadcrum / detail / view-item / profile / checkbox / input-file / dropdown` | misc | ✅ clean | None |

**Bootstrap-4-ism scan of `resources/views/components/` — none found.** No
`form-group`, `custom-control`, `custom-select`, `input-group-append/prepend`,
`badge-*`, `.close`, `float-left/right`, `data-toggle`. Every match in the scan was
legitimate BS5 (`btn-close`, `data-bs-dismiss`) or a custom class name that merely
contains the substring "close". **The custom component layer is already
UX4G-compatible and needs no migration.**

---

## What shipped this phase — stepper a11y parity

The app has **three** step components. `fc-stepper` was the best-built (had
`<nav aria-label>`, `aria-current="step"`, done-state check icons). The other two
lacked `aria-current` on the active step. Added it — **semantic-only, zero visual
change**, exactly the pattern used for pagination in Phase 7:

| File | Change |
|---|---|
| `components/estate-workflow-stepper.blade.php` | active `<span class="badge">` → `aria-current="step"` |
| `partials/step-indicator.blade.php` | active `<a>` → `aria-current="step"` (only when `$isCurrent`) |

All three steppers now announce the current step to assistive technology
consistently. No layout, color, or text change.

---

## `jquery-steps` member wizard — disposition: **KEEP** (do not replace now)

The plugin audit's "REPLACE with UX4G Stepper" item was investigated in full. Two
facts changed the disposition:

1. **The only real usage is the member create/edit wizard** (`#wizard`, 2 views).
   `form-wizard.js`'s other targets (`#example-basic`, `#example-form`,
   `#example-advanced-form`) exist in **0 views** — dead template demo code.
2. **The wizard is business logic, not styling.** Its `onStepChanging` handler makes
   a **synchronous AJAX call to `/member/validate-step/{n}`** to server-validate each
   step before advancing, tracks `formIsDirty`/`loadedSteps`, serializes step inputs,
   and creates the record in one shot from `onFinished`. Replacing the plugin means
   re-implementing all of that step-gating + server-validation flow against the UX4G
   Stepper's markup.

Replacing it therefore **cannot be done without rewriting view business logic**,
which the migration's own rules forbid ("Do not change Blade business logic; every
existing functionality must continue to work exactly as before"). `jquery-steps` is
also **Bootstrap-agnostic** (a pure jQuery plugin — it works unchanged under UX4G).
The migration blueprint's own component table already carries a **"Keep jquery-steps"**
disposition alongside the aspirational "REPLACE" row; this phase resolves that
contradiction in favour of **KEEP**.

**Recommendation:** treat the UX4G-Stepper migration of the member wizard as a
**separate, explicitly-approved feature workstream** (re-implement + full E2E of the
create/edit/validate/save flow), not part of a zero-regression frontend migration.

---

## Corrections to the Phase 10 audit (found while investigating)

- **`jquery-3-6.blade.php` loads jQuery local-first with a CDN `onerror` fallback**
  (`asset('js/jquery-3.6.0.min.js')`, falling back to `code.jquery.com` only if the
  local file 404s). So the `code.jquery.com/jquery-3.6.0` references flagged in
  Phase 10 are **resilient fallbacks, not primary loads** — materially lower risk
  than stated. For strict GIGW (no external call even on fallback) they can still be
  removed, but this drops from "High" to "Low" priority.
- **`form-wizard.js` is dead code** (0 real targets) — a clean removal candidate for
  the Phase 16 cleanup sweep.

---

## Files Affected — Phase 11

| File | Change |
|---|---|
| `resources/views/components/estate-workflow-stepper.blade.php` | +`aria-current="step"` on active step |
| `resources/views/partials/step-indicator.blade.php` | +`aria-current="step"` on active step |

No controller, route, model, migration, middleware, JS, or plugin change. No
render-affecting change.

## Risks

| Risk | Mitigation |
|---|---|
| aria attribute alters layout | Attribute-only; gate = zero diff confirms no visual effect. |
| Missed a BS4-ism in a custom component | Full grep of `components/` for the BS4→BS5 delta set; none found. |
| jquery-steps breaks under UX4G | It is Bootstrap-agnostic; kept as-is; member wizard confirmed functional. |

## Testing

- **Compatibility:** grep audit of all custom components for Bootstrap-4-isms — none.
- **Visual gate:** `baseline.spec.js`, chrome, via Apache :8080 → **58 passed, zero diff.**

## Rollback

`git checkout -- resources/views/components/estate-workflow-stepper.blade.php resources/views/partials/step-indicator.blade.php`. No data/schema touched.

## Follow-ups

1. **[Feature workstream, needs approval]** Member wizard `jquery-steps` → UX4G Stepper — only with a full re-implementation + E2E of the step-validation/save flow.
2. **[Phase 16]** Remove dead `form-wizard.js`.
3. **[Low / GIGW]** Optionally drop the jQuery CDN `onerror` fallback in `jquery-3-6.blade.php`.
