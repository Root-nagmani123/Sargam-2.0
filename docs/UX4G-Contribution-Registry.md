# UX4G Contribution Registry

**Living document** (per the UX4G Enterprise Migration Framework, Phase D + Contribution
Registry). Records every ERP component that UX4G does **not** provide out-of-the-box, or
that needs a custom/kept-third-party implementation. Updated as components are assessed.

**Last updated:** 2026-08-04
**Legend — Classification:** `CONTRIBUTE` = candidate to contribute back to UX4G ·
`INTERNAL` = internal component only · `KEEP-3P` = keep third-party lib (no UX4G equal) ·
`ADOPT-UX4G` = UX4G already provides it; migrate when a feature workstream allows.

---

## C-1 · Multi-step Form Wizard (Stepper)

| Field | Value |
|---|---|
| **Current implementation** | `jquery-steps` (member create/edit `#wizard`) + 3 custom Blades (`estate-workflow-stepper`, `partials/step-indicator`, `fc/…/fc-stepper`) |
| **ERP module(s)** | Member Management, Estate, FC Registration |
| **Purpose** | Guided multi-step data entry with per-step server validation |
| **Government use cases** | Officer onboarding, estate allotment workflow, FC registration flow |
| **Accessibility** | `aria-current="step"` (added Phase 11), keyboard step nav, `role`/`aria-label` on the tracker |
| **Responsive** | Horizontal on desktop, vertical/scroll on mobile |
| **UX4G proposal** | UX4G **has** a Stepper (25 classes: `.stepper-vertical .stepper-mobile .stepper-head-check .stepper-progress-bar .stepper-invalid`) — adopt it for the tracker UI |
| **Required states** | active / done / todo / invalid / disabled |
| **Blocker** | The member `#wizard` embeds **AJAX step-validation business logic** (`/member/validate-step/{n}`) that UX4G's Stepper doesn't cover — migration = re-implement + full E2E, a **feature workstream** (forbidden in a zero-regression pass) |
| **Est. hours** | 24 | **Reusability** | High |
| **Classification** | **ADOPT-UX4G** (tracker) — deferred; custom validation stays INTERNAL |

## C-2 · Data Grid (server-side)

| Field | Value |
|---|---|
| **Current implementation** | DataTables.net (BS5 build) + Yajra server-side; `datatable-global-ui.js` toolbar |
| **ERP module(s)** | ~all list/index pages |
| **Purpose** | Server-side pagination, sort, search, column-visibility, export |
| **UX4G proposal** | UX4G provides styled tables but **no server-side data-grid** — no direct equivalent |
| **Accessibility** | axe R-A/R-D flagged pager `aria-command-name`/`link-name` + `scrollable-region-focusable` — fix at `datatable-global-ui.js` |
| **Est. hours** | n/a (keep) | **Reusability** | High |
| **Classification** | **KEEP-3P** — theme to UX4G tokens; no UX4G replacement |

## C-3 · Calendar

| Field | Value |
|---|---|
| **Current implementation** | FullCalendar v6 |
| **ERP module(s)** | Calendar, Timetable, Attendance |
| **Purpose** | Month/week event grid, date-scoped views |
| **UX4G proposal** | **No UX4G calendar** component |
| **Est. hours** | n/a (keep) | **Reusability** | High |
| **Classification** | **KEEP-3P** |

## C-4 · Rich Searchable Select / Combobox

| Field | Value |
|---|---|
| **Current implementation** | **Three** libs coexist: choices.js (×80), tom-select (×16), select2 (×10) |
| **ERP module(s)** | forms, filters app-wide |
| **Purpose** | Type-ahead single/multi select with search |
| **Accessibility** | axe `aria-input-field-name` (choices combobox needs a name) |
| **UX4G proposal** | UX4G "Search" + form controls exist but no full type-ahead combobox parity — **consolidate to one** (product decision) then theme |
| **Est. hours** | 16 (consolidation) | **Reusability** | High |
| **Classification** | **INTERNAL** (consolidate) — candidate **CONTRIBUTE** if generalised |

## C-5 · Global Success Toast

| Field | Value |
|---|---|
| **Current implementation** | Custom `sargam-success-toast.js` restyling every SweetAlert2 `icon:'success'` into one top-right card |
| **Purpose** | Consistent success feedback across all modules |
| **UX4G proposal** | UX4G has Toasts — could adopt; current custom layer is intentional (unifies SweetAlert output) |
| **Est. hours** | 8 | **Reusability** | High |
| **Classification** | **INTERNAL** |

## C-6 · Status Toggle Switch (in-grid)

| Field | Value |
|---|---|
| **Current implementation** | `.form-check-input.status-toggle role="switch"` in DataTable rows; AJAX toggle |
| **Accessibility** | Was nameless → `aria-label="Toggle active status"` added via shared draw-safe labeller (axe R-C) |
| **UX4G proposal** | Bootstrap/UX4G form-switch covers the control; keep + label |
| **Classification** | **ADOPT-UX4G** (styling) — DONE for a11y |

## C-7 · PDF / Print Templates

| Field | Value |
|---|---|
| **Current implementation** | DomPDF (`PDF::loadView`) — self-contained print Blades |
| **Note** | CSS custom properties unreliable in DomPDF → brand navy applied as **literal** `#004384` (not `var()`); keep literals in PDF templates |
| **Classification** | **KEEP-3P** (backend render) |

---

## Summary of dispositions

| Classification | Components |
|---|---|
| **KEEP-3P** (no UX4G equivalent) | Data Grid (C-2), Calendar (C-3), PDF (C-7) |
| **ADOPT-UX4G** (UX4G provides; migrate later) | Stepper tracker (C-1), Status switch (C-6) |
| **INTERNAL** | Combobox consolidation (C-4), Global toast (C-5) |
| **CONTRIBUTE candidate** | A generalised accessible combobox (C-4), if extracted |

**Open product decisions feeding this registry:** consolidate the 3 select libraries + 2
date pickers (C-4); schedule the member-wizard Stepper feature workstream (C-1).
