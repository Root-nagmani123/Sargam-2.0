# Phase 13 — Accessibility

**Status:** ✅ Complete
**Date:** 2026-08-04
**Nature:** Runs across the whole surface; ships zero-visual WCAG fixes + a11y backlog
**Gate result:** **58 passed, zero visual diff**

---

## Objective

A dedicated WCAG 2.1 AA pass over the page surface: confirm the systemic
accessibility foundation, fix concrete **accessible-name** gaps on icon-only
controls (zero-visual), and catalogue the accessibility items that require a
*visual* change (and therefore design sign-off + baseline re-approval) as a tracked
backlog rather than forcing them into a zero-regression phase.

**In scope:** accessible names (`aria-label`), decorative-icon hiding (`aria-hidden`),
confirmation of lang/skip-link/titles/landmarks.
**Out of scope (needs design sign-off + re-baseline):** color/contrast changes,
focus-ring restyle, input-border contrast — catalogued below.

---

## Systemic foundation — audited, already in place ✅

The prior work + the app's own header left the fundamentals solid:

| WCAG item | Status | Evidence |
|---|---|---|
| `<html lang>` (3.1.1) | ✅ all layouts | admin `lang="en"`, app `lang="{{ locale }}"`, fc `lang="en"` |
| Skip to content (2.4.1) | ✅ present | `header_new.blade.php` — `visually-hidden-focusable .skip-link` → `#main-content` (marked "GIGW Mandatory") |
| Page `<title>` (2.4.2) | ✅ dynamic | admin master `@yield('title') … Sargam 2.0 …` |
| Header/nav accessible names (4.1.2) | ✅ exemplary | `aria-label` on font-size, language, dashboard, notifications, nav-toggle; `aria-labelledby` on dropdowns; `role="menubar"`; `aria-hidden` on decorative icons |
| Pagination ARIA (Phase 7) | ✅ | `vendor/pagination/custom.blade.php` |
| Stepper `aria-current` (Phase 11) | ✅ | all three steppers |

The `header_new` header (every admin page) is a model implementation — no fixes
needed there.

---

## Shipped this phase — accessible-name fixes (zero visual change)

Icon-only action controls with **no accessible name at all** (no `aria-label`, no
`title`, no text) — a screen reader announces nothing. Added `aria-label` for the
name and `aria-hidden="true"` on the now-redundant decorative glyph. **13 files, 15
controls.** No layout, color, or text change.

| Area | Files | Control → label |
|---|---|---|
| FC reports | `fc/report/overview`, `bank-details`, `form-overview` | search → **Search**; ✕ → **Clear filters** |
| FC document sub-forms | `debts_liabilities`, `family_details`, `generic`, `group_insurance`, `immovable_property`, `movable_property`, `nps` | ✕ remove-row → **Remove row** |
| Forms grid / sample docs | `admin/forms/partials/forms-grid`, `admin/sample-documents/index` | 🗑 → **Delete** |
| Enrollment pagination | `admin/registration/enrollement` (JS-built) | ‹ / › → **Previous page** / **Next page** |

All are additive ARIA attributes — gate confirms **zero visual diff**.

### Not touched (deliberately)
- Icon buttons that already carry a `title` (e.g. `define_house` add/remove,
  form-builder delete, `facultyFeedbackBell`): `title` supplies an accessible name,
  so these are **not silent failures**. Upgrading `title`→`aria-label` is a nice-to-have,
  logged as low-priority backlog — not worth touching the exemplary header for.

---

## Accessibility backlog — needs design sign-off + baseline re-approval

These are **visual** changes, so by the migration rules they cannot ship in a
zero-regression phase. Each needs a design decision, then its own gated change.

| # | Item | WCAG | Note |
|---|---|---|---|
| A11Y-1 | **Input border contrast** ~1.26:1 | 1.4.11 (needs 3:1) | Default control border too faint; darkening is a global visual change. Tie to the R-1 token decision. |
| A11Y-2 | **Focus-ring strength** | 2.4.7 | Theme has a *faint* ring (verified real in Phase 6; the "no ring" reading was a false premise). Strengthening is a visual change. |
| A11Y-3 | **4 non-focusable tooltips** (A-5, from Phase 9) | 1.4.13 / 2.1.1 | Tooltips on non-focusable elements — keyboard users can't trigger. Fix = `tabindex="0"` or move to a focusable host; slight focus-outline change, so gated. |
| A11Y-4 | **`.badge-success` #28a745 / white** in `joining_document` | 1.4.3 (2.20:1) | Same defect class as Phase 8 badges, but a page-local self-defined style on a printable doc. Low traffic. |
| A11Y-5 | `title`→`aria-label` on title-only icon buttons | 4.1.2 | Robustness upgrade; `title` already provides a name. Low priority. |

**Not yet audited at depth (future a11y sprint):** per-field form-label association
across all forms (1.3.1), `<img alt>` coverage (1.1.1), heading-order (1.3.1),
and a full automated axe-core sweep. Recommended as a dedicated audit with its own
tooling, distinct from this migration.

---

## Files Affected — Phase 13

13 Blade views (listed above). Additive ARIA attributes only — **no** controller,
route, model, JS logic, layout, or style change.

## Risks

| Risk | Mitigation |
|---|---|
| ARIA attribute alters rendering | Attribute-only + `aria-hidden` on already-decorative icons; gate = zero diff. |
| Wrong/duplicated Edit target | Verified each pattern was unique in its file before editing; `view:cache` compiled all templates clean. |
| Mislabeled control | Labels derived from each control's context (icon + handler + page). |

## Testing

- **Compile:** `php artisan view:cache` — all Blade templates cached successfully (0 errors).
- **Visual gate:** `baseline.spec.js`, chrome, via Apache :8080 → **58 passed, zero diff.**

## Rollback

`git checkout -- <the 13 files>`. No data/schema/logic touched.

## Follow-ups

1. **[Design]** Resolve A11Y-1/A11Y-2 (border + focus contrast) alongside the R-1 brand-navy decision, then ship as one gated visual change.
2. **[Small, gated]** A11Y-3 four non-focusable tooltips.
3. **[Dedicated sprint]** Full axe-core sweep: label association, `alt`, heading order.
