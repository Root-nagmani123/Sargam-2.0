# UX4G Enterprise Migration Framework — Compliance & Gap Map

**Purpose:** reconcile the work done so far against the *UX4G Enterprise Migration
Framework* (the Master Prompt PDF), show where we comply, and name the gaps.
**Date:** 2026-08-04

---

## The one strategic thing to decide (read this first)

The framework's **Primary Objective** is a **redesign**: *"make every screen appear as
though it was originally designed using UX4G … Do not simply replace Bootstrap classes."*

The work delivered so far is a **zero-regression compatibility migration**: UX4G
foundation + brand tokens + accessibility/perf/cross-browser hardening, with **every
change gated to zero visual diff** (pages deliberately look the *same*).

These are **sequential, not contradictory** — the framework itself orders it this way
(Phase B foundation → Phase C components → *then* Phase E/F page redesign). We have
completed the foundation, the shared components, and the review/testing tiers. **The
per-page UX4G redesign (Phase E/F / Sprint 10) is the remaining major body of work** —
and by definition it *changes* each page's appearance, so it is **not** zero-regression:
each redesigned page is reviewed and **re-baselined**, one page at a time, with a STOP for
approval after each (the framework's Stop Condition).

**Decision needed:** proceed into per-page UX4G redesign (Phase E/F)? If yes, name the
first module (framework suggests Authentication → Dashboard → Member → …).

---

## Absolute Rules — honored throughout ✅

Never modified: routes, controllers, models, middleware, APIs, auth/authz, database,
validation, business rules. Never broke: workflows, permissions, reports, exports,
integrations. **Frontend-only, every change `git checkout`-revertible.** (One nuance
surfaced and respected: the member-wizard step-validation is *view* business logic — so
replacing jquery-steps is deferred as a feature workstream, not done in-migration.)

## Migration Philosophy loop — followed ✅
Audit → Analyse → Design → Validate → Implement → Test → Review → Approve → Next. Every
phase produced a deliverable and was **gated against the Playwright visual baseline**
before proceeding.

## Phase & Sprint mapping

| Framework | Our work | Status |
|---|---|---|
| **Phase A — Audit** (module/page/bootstrap/plugin/CSS/JS inventories) | `UX4G-Migration-Blueprint.md` (18 sections) + Phase-0 checklist inventories | ✅ |
| **Phase B — Foundation** (tokens, colours, type, states) | Phase 1–2 SCSS/token architecture; **R-1 brand navy `#004384`** decided + executed | ✅ |
| **Phase C — Shared Components** | Phases 5–11: buttons, badges, **forms** (border+focus), nav/pagination, tables, interactive, plugins, custom components | ✅ |
| **Phase D — Missing/Custom Components** | **`UX4G-Contribution-Registry.md`** (created now) | ✅ |
| **Phase E/F — Module/Page redesign** | Phase 12 confirmed page-level *compatibility* only (no redesign) | ⏳ **remaining** |
| **UX Review** | partial (component-level); per-page UX pass pending | 🔶 |
| **Accessibility Review** (WCAG 2.1 AA, GIGW, ARIA, contrast, focus) | Phase 13 + **axe-core sweep** (`UX4G-Accessibility-Axe-Sweep.md`); A11Y-1/A11Y-2 shipped; R-C labels 138→17 | 🔶 in progress (R-A/R-B pending) |
| **Responsive Review** | viewport 1366 baseline; full device matrix pending | 🔶 |
| **Performance Review** | Phase 14 (`UX4G-Phase-14-Performance.md`) — dup-asset removal, cache-header finding | ✅ |
| **Sprints 0–11** | Stage 0 + Phases 1–16 | ✅ |
| **Sprint 12 — UAT / Prod / Docs** | not started | ⏳ |

## Testing Requirements — mapping
Functional ✅ (smoke + no-JS-error checks) · Visual regression ✅ (57+11 routes, gated) ·
Browser ✅ (chrome/firefox/webkit, Phase 15) · Accessibility 🔶 (axe sweep + remediation) ·
Responsive 🔶 (single viewport) · **Print/PDF** 🔶 (DomPDF navy handled; no dedicated print
regression) · Performance ✅.

## Mandated artifacts — done / partial / pending

| Artifact | State |
|---|---|
| Audit / Inventory | ✅ Blueprint + checklist |
| Design tokens | ✅ |
| Contribution Registry | ✅ created |
| WCAG report | ✅ axe sweep (A/AA) |
| Performance report | ✅ Phase 14 |
| Cross-browser report | ✅ Phase 15 |
| Adoption / Completion summary | ✅ `UX4G-Migration-Completion-Summary.md` |
| **GIGW 3.0 report** | 🔶 partial (skip-link, self-hosting, no-CDN done; no formal report) |
| **Per-page Compliance Score** (% per category) | ⏳ not per-page |
| **Bootstrap Removal report** | ⏳ **intentionally deferred** — UX4G *is* Bootstrap 5.3; removal is N/A / only "after every page approved" |
| UAT / Production-readiness / KT / User+Dev docs | ⏳ Sprint 12 |

## Going forward — process commitments (from the framework)
- **Stop Condition:** for **page-level** redesign work, complete **one page**, then STOP
  for review before the next (we already gate per change; this tightens it to per-page).
- **Never migrate the whole project together;** module-by-module, page-by-page.
- Update the **Contribution Registry** whenever a new missing component is found.
- Keep **quality over speed**; production-ready + accessible + reversible.

---

## Recommended next actions (in framework order)
1. **Finish the Accessibility Review** already in flight — **R-B** contrast tokens (needs a
   design value decision) then **R-A** nav ARIA — closing WCAG AA.
2. Produce the **GIGW 3.0** compliance report (mostly assembled from existing work).
3. **Phase E/F redesign — STARTED.** Canonical index-page design is the **Store Master /
   `programme-dt` chrome** (`UX4G-PhaseF-Pilot-Master-Country.md` + `new-design-index-page.md`)
   — **every list page must match it**. Pilot `/master/country` done + gated. Rolling across
   modules one page at a time with STOP-for-review + re-baseline; Laravel-paginated pages use
   footer variant B, and any needing a DataTable data-source are flagged (not silently changed).
