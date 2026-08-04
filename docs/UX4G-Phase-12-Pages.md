# Phase 12 — Pages

**Status:** ✅ Complete — **confirmation + catalogue, no code change**
**Date:** 2026-08-04
**Depends on:** all component phases (5–11), now complete
**Gate:** No code changed this phase ⇒ the Phase 11 gate (58 passed, zero diff, current HEAD) certifies this exact state. See §Gate.

---

## Objective

With every shared component migrated (Phases 5–11), verify the **page surface**
itself — the ~537 admin/FC pages that consume those components — renders and behaves
correctly under the UX4G (Bootstrap 5.3) baseline, and catalogue any page-level
residual for later phases. Fix genuine, safe page-level defects; touch nothing that
already works.

**In scope:** page-level BS4-ism audit; page-level style/token-debt catalogue;
compatibility confirmation.
**Out of scope (migration rules):** bulk re-coloring to tokens (a visual change needing
design sign-off + re-baseline — not a zero-regression edit); rewriting page business
logic; page-level accessibility (that is **Phase 13**).

---

## Key premise (why this phase is light)

The application is **already Bootstrap 5.3** (Stage 0 / S-2 unified four BS5 versions),
and UX4G **is** Bootstrap 5.3 recompiled and is loaded **per-component**, not globally.
So pages do not need a BS4→BS5 rewrite — they already run on BS5, and UX4G does not
restyle them unless a component is explicitly activated. Phase 4 already swept the
utility-level BS4-isms. Phase 12 is therefore a **page-level residual audit**, not a
migration.

---

## Page-level BS4-ism audit — result: no live defect

Scanned all page blades (excluding already-migrated `components/` and `layouts/`, and
PDF/print templates which self-define their classes) for the BS4→BS5 breaking set:

| Pattern | Raw hits | Verdict |
|---|---|---|
| `.close` | 218 files | **False positive** — all `btn-close` (BS5) or custom classes (`birthday-banner-close`, `status-close`, `sidebar-mini-toggle-text-close`). No BS4 `.close`. |
| `data-target=` | 2 files | **False positive** — a **custom search-filter widget's** own JS attribute (`.an-filter[data-target="anAvailable"]`), not a Bootstrap trigger. Renaming would break the JS. |
| `.form-row` | 1 file | **False positive** — custom `.leave-form-row`. |
| `.custom-select` | 1 file | **Benign** — belt-and-suspenders on a BS5 `.form-select`; the page self-styles `.custom-select`. |
| `.badge-success/.badge-warning` | 1 file | **Benign** — CSS rule *definitions* in the page's own `<style>` (`joining_document`), self-contained. |
| **`.form-group`** | **13 files** | **Works today** — the app ships its **own** `.form-group` rule in `public/css/forms.css:131` (+ `spacing-system.css`, `styles.css`). It is an intentional app-level compatibility shim, independent of Bootstrap. **Not touched** (changing to `mb-3` would risk double-margins / a regression). |

**Conclusion: there is no page-level BS4 class that constitutes a live defect.** The
pages are already UX4G-compatible.

---

## Page-level tech-debt catalogue (for the global-activation / tokenization phase)

These are **not defects today** — they render correctly. They are the debt that
determines how much work the eventual *global* UX4G activation + brand-token adoption
will take. Recorded here so that phase can be scoped, not fixed now (fixing = mass
visual change, out of a zero-regression migration).

| Debt | Measure | Impact when UX4G goes global |
|---|---|---|
| Inline `<style>` blocks in views | **302** blades | Page CSS overrides tokens; must be reviewed per page |
| Inline `style=""` attributes | **~2,934** | Highest-specificity; unaffected by any stylesheet/token |
| Hardcoded hex colors in views | **~4,781** | Will **not** adopt `--bs-*` tokens automatically |
| **Brand-navy fragmentation** | `#004a93` ×747, `#1a3c6e` ×60, token `#004384` | **Three** different "brand blues" used interchangeably — no single source of truth; none tokenized |
| Bootstrap-default & green literals | `#0d6efd` ×53, `#198754` ×49, `#28a745` ×16 | Bypass semantic tokens |

**Headline:** the dominant hardcoded brand color is **`#004a93` (×747)** — a *lighter*
navy than the R-1 LBSNAA token **`#004384`** (9.83:1 AAA). Whichever is canonical is a
**design decision** (R-1 follow-up), after which a dedicated tokenization pass can
replace the literals. That pass is a visual change by definition and belongs to the
global-activation phase with its own baseline re-approval — **explicitly not** this
zero-regression phase.

---

## Files Affected — Phase 12

**None.** No page was modified. Every audited item is either a false positive, a
benign self-contained style, or a working app-level shim.

## Risks

| Risk | Mitigation |
|---|---|
| A real BS4-ism missed | Full breaking-set grep across the page surface; each hit hand-verified in context. |
| `.form-group` silently unstyled | Confirmed styled by `forms.css` — works; left untouched. |
| Assuming "no change" hides a regression | No code changed ⇒ state is byte-identical to the Phase 11 gate (58/58). |

## Gate

Phase 12 introduces **zero code change**, so the working tree is identical to the state
the **Phase 11 gate already certified — 58 passed, zero visual diff** across 57
authenticated pages + preflight (chrome, via Apache :8080). Re-running the pixel gate
would test nothing new. Any later page-level *fix* (e.g. the tokenization pass) must be
gated on its own with baseline re-approval.

## Rollback

Nothing to roll back — no change.

## Follow-ups

1. **[Design / R-1]** Choose the canonical brand navy (`#004a93` vs `#004384`), then run a **tokenization pass** replacing hardcoded literals with `--bs-*` — a dedicated, separately-gated visual change.
2. **[Global-activation phase]** Review the 302 page-level `<style>` blocks for token overrides.
3. **[Phase 13]** Page-level accessibility (labels, alt text, aria on custom widgets) — the dedicated Accessibility phase.
