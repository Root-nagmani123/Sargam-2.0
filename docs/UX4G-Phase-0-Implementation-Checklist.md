# Phase 0 — Project Preparation & Implementation Checklist

**Status:** Audit complete. **Zero files modified.**
**Basis:** Working tree at HEAD `60715da05`.
**Companions:** `UX4G-Migration-Blueprint.md`, `UX4G-Decision-Note-R1-R2-R3.md`

---

## Phase Summary

Phase 0 audits the project structure and produces the implementation checklist. Nothing was modified.

The audit surfaced **one blocking discovery** that changes the shape of Phase 1 as specified: **the project's SCSS/asset build pipeline is dead and disconnected from every layout.** The `resources/scss/…` structure requested in Phase 1 would compile to a bundle that no page loads. A decision is required before Phase 1 can start (see §B).

---

## Objective

- Audit project structure, layouts, partials, components
- Identify Bootstrap imports, custom CSS, JS dependencies, jQuery plugins
- Produce an implementation checklist
- **Modify nothing**

---

## A. Audit Findings

### A.1 Shared layouts (5)

| Layout | Pages | Lines | Loads assets via | Notes |
|---|---:|---:|---|---|
| `admin/layouts/master.blade.php` | **494** | 1,318 | `pre_header` + `footer` partials | Primary target. Contains the frozen RBAC 5-pane resolver (`master.blade.php:62-91`). |
| `fc/layouts/master.blade.php` | 31 | 25 | `fc/layouts/pre_header` + `footer` | Clean. Already has `skip-link` + `<main id="content" tabindex="-1">`. Best-practice reference. |
| `layouts/app.blade.php` | 7 | — | inline | Laravel/UI auth scaffolding. |
| `admin/layouts/timetable.blade.php` | 4 | — | **self-contained** | Loads its own Bootstrap 5.3.6 + own CSS. Diverges from master. |
| `faculty/layouts/master.blade.php` | 1 | 674 | inline | Near-dead. Candidate for retirement. |

### A.2 Master Blade files & common partials

| File | Role |
|---|---|
| `admin/layouts/pre_header.blade.php` | **All 14 stylesheet `<link>` tags** (189 lines) — the single most important file for Phases 1–3 |
| `admin/layouts/footer.blade.php` | **41 `<script>` tags** (226 lines) — 27 local `asset()`, **14 external CDN** |
| `admin/layouts/header.blade.php` / `header_new.blade.php` | Top navbar |
| `admin/layouts/sidebar.blade.php` / `sidebar_new.blade.php` / `sidebar/` | RBAC sidebar |
| `components/menu/` (24 files) | Per-category sidebar menu partials |
| `partials/` (7 files) | Repeatable form rows (`qualification-row`, `employment-row`, …) + `step-indicator` |

### A.3 Reusable Blade components (50)

`resources/views/components/` — the Phase 11 surface:

`breadcrum` · `calendar` · `calendar1` · `checkbox` · `course-sidebar` · `dashboard-birthday-avatar` · `data-table/table` · `datatable-chrome` · `detail` · `dropdown` · `estate-workflow-stepper` · `fonts-sargam` · `input` · `input-file` · `jquery-3-6` · `mess-column-manager` · `mess-column-manager-assets` · `mess-datatable-search-helpers` · `mess-master-datatables` · `profile` · `select` · `session_message` · `view-item` · plus 24 `menu/*` and 3 `menu/partials/*`

### A.4 Bootstrap imports — **four versions in production**

| Version | Where | Live? |
|---|---|---|
| **5.3.6** (jsDelivr CDN) | `admin/layouts/footer.blade.php:4`, `timetable`, `fc/layouts/footer`, `layouts/app`, `auth/login`, `forms/show`, `report/joining_documents_report` | **YES — this is the Bootstrap the portal actually runs** |
| **5.3.0-alpha1** (jsDelivr CDN) | `admin/academics/faculty/index.blade.php` | Yes, on one page — **a pre-release on a production page** |
| 5.3.2 (local `admin_assets/libs/bootstrap/`) | `admin/forms/joining_document.blade.php` only | Effectively dead (1 reference) |
| 5.3.3 (`node_modules/bootstrap`) | — | Dead — never compiled (see §B) |

### A.5 Custom CSS

| Metric | Value |
|---|---:|
| `public/css/` | 505 KB across 30 files |
| `public/admin_assets/css/` | 1,198 KB |
| **Total shipped** | **~1.70 MB** |
| `!important` declarations | **7,128** (5,579 in `styles.css` alone) |
| Inline `<style>` blocks | 379 across 319 Blade files |
| Inline `style="…"` attributes | 2,956 |
| Stylesheets in critical path | 14 |
| Duplicate a11y stylesheet | `accesibility-style_v1.css` in both `css/` (36 KB) and `css/original/` (40 KB) |

Load order is documented in the blueprint §2.3. `public/css/sargam-app.css` is the sanctioned consolidated custom stylesheet and is loaded **last** — the correct long-term home.

### A.6 JavaScript dependencies

- **jQuery 3.7.1** — served from `code.jquery.com`. **`public/js/jquery-3.7.1.min.js` is 0 bytes.**
- 5,851 `$(…)` call sites across 192 Blade files
- 41 script tags in `footer.blade.php` (27 local, 14 CDN)
- 25 distinct external hosts across the app

### A.7 jQuery plugins — 35 vendored, usage measured

| Plugin | Refs in Blade | Disposition (blueprint §8) |
|---|---:|---|
| DataTables | 1,587 · 101 pages · 43 server-side · 55 Yajra classes | **KEEP** — re-theme only |
| Select2 | 708 | **KEEP** + re-skin |
| SweetAlert2 | 310 | **KEEP** |
| SimpleBar | 257 | **KEEP** |
| daterangepicker | 72 | **KEEP** (UX4G date picker unverified) |
| Summernote 0.8.18 | 67 | **KEEP** — but CDN-only, must self-host |
| FullCalendar | 74 | **KEEP** |
| Dropzone | 20 | **KEEP** |
| jquery-validation | 12 | **KEEP** |
| ApexCharts (516 KB) | 3 refs · **1 Blade** | **REVIEW** — lazy-load on that route |
| jquery-steps | 2 | **REPLACE** with UX4G Stepper |
| TinyMCE (428 KB), Quill (216 KB), jQuery UI (255 KB), jvectormap (169 KB), owl.carousel, inputmask, prismjs, nouislider, typeahead, magnific-popup, nestable, dragula, bootstrap-tree, bootstrap-switch, block-ui, jquery-raty, jquery-asColor* | **0** | **DELETE** — ~1.4 MB dead weight |

> `jquery.repeater` has 1 reference — verify before removal. `jQuery UI` may be a transitive dependency — audit before deleting.

---

## B. BLOCKING DISCOVERY — the build pipeline is dead

Phase 1 as written asks for:

```
resources/scss/vendors/ux4g/ … app.scss
```

**This cannot work as specified without additional changes.** Evidence:

| Probe | Result |
|---|---|
| Blade files referencing `@vite(` | **0** |
| Blade files referencing `mix(` | **0** |
| Blade files referencing `css/app.css` | **0** |
| Blade files referencing `js/app.js` | **0** |
| `resources/css/app.css` size | **0 bytes** |
| `webpack.mix.js` inputs | `resources/js/app.js`, `resources/css/app.css` — **`resources/sass/` is not compiled by Mix** |
| `resources/sass/app.scss` referenced by | `vite.config.js` only |
| `laravel-vite-plugin` installed? | **No** — `vite.config.js` cannot run |
| `resources/sass/_variables.scss` contents | Nunito font, `$body-bg: #f8fafc` — **default scaffolding, unused** |

**Conclusion.** Every layout loads hand-maintained raw files from `public/css/` and `public/admin_assets/`. The Mix pipeline compiles a 0-byte stylesheet that no page links. The Vite config is inert. Creating `resources/scss/…` and running a build would produce a bundle that **nothing loads** — the migration would appear to do nothing.

### The three ways forward

| | **Option A** — Wire up the full SCSS pipeline | **Option B** — Keep raw CSS in `public/` | **Option C** — SCSS source → compile into existing `public/css/` paths |
|---|---|---|---|
| Matches Phase 1 spec | Yes | No | **Yes (source layout)** |
| Layout changes needed | **Re-point all 5 layouts to a compiled bundle** | None | **None** |
| Pages at risk in Phase 1 | **537** | 0 | **0** |
| Build step required for deploys | Yes | No | Yes (output committed, so optional) |
| Gives token/`@layer` architecture | Yes | Partially | **Yes** |
| Reversible | Hard | Easy | **Easy — delete one `<link>`** |
| Phase 1 effort | ~120 h | ~40 h | **~56 h** |
| Risk | **High** | Low | **Low** |

### DECISION RECORDED — **Option C selected** (2026-08-03)

> Author in SCSS under `resources/scss/`, compile to the `public/css/` paths the layouts already load. **No layout asset-loading changes in Phase 1. Zero pages at risk.**

### Rationale — Option C

Author in SCSS exactly as Phase 1 specifies, but configure the build to **emit to the `public/css/` paths the layouts already load**. This:

- delivers the requested `resources/scss/vendors|overrides|components|pages` architecture,
- requires **no change to asset loading on 537 pages** in Phase 1,
- keeps the compiled CSS committed, so deploys are unaffected if the team doesn't run the build,
- honours *"prefer extension over replacement"* and *"never modify more than one major component at a time."*

Re-pointing layouts at a single compiled bundle remains desirable — but it belongs in **Phase 14 (Performance)**, after components are stable, not in Phase 1.

---

## C. Implementation Checklist

### C.-1 Execution log — decisions taken during implementation

| Date | Decision | Rationale |
|---|---|---|
| 2026-08-03 | **UX4G is NOT loaded globally.** It is vendored + build-ready (`ux4g-vendor.css` tamed to `@layer ux4g`, `ux4g-app.css` tokens), activated **per-component** in phases 5–12. | A 3-page probe showed a tamed+layered global load at ~0% change, but the full 58-page gate revealed ~14 pages changed (UX4G restyles table headers/toggles the existing theme doesn't control). A full design-system stylesheet cannot load globally with zero change. Global load reverted; per-component activation is the only zero-regression path. |
| 2026-08-03 | **`!important` reduction is folded into the component phases (5–12), not done as a wholesale Phase 3 pass.** | The big `!important` blocks are component-bound (`styles.css`→theme, `sidebar-modern.css`→Phase 7, `admin-header.css`→Phase 7, `custom.css`→sweep). Many `!important` are load-bearing (utilities). Reducing them *when their component migrates* is safe and useful; a decoupled wholesale purge is high-risk with no payoff. Phase 3's standalone scope = dead-CSS removal (done) + the SCSS architecture (built in Phase 1). |
| 2026-08-03 | **Design tokens** encode LBSNAA navy `#004384` (9.83:1 AAA) and the 3 WCAG-fixed semantics: info `#0067A6` (6.02:1), success `#2F7A12` (5.37:1), warning `#8F5716` (5.93:1) — the **darken-hue** option for P0-5. | Measured with the WCAG 2.1 formula. Darken chosen as the conventional default; still subject to design-team confirmation (P0-5). |

### C.0 Pre-flight gates — must clear before Phase 1

| # | Item | Why | Owner | Status |
|---|---|---|---|---|
| P0-1 | **Decide Option A / B / C** (§B) | Determines Phase 1 file layout | User | ✅ **Option C selected (2026-08-03)** |
| P0-2 | Confirm R-1 (retain LBSNAA navy `#004384`) | Phase 2 tokens | Management | ⏳ |
| P0-3 | Confirm R-2 (pin `UX4G@2.0.8`) | Phase 1 vendoring | Management | ⏳ |
| P0-4 | Confirm R-3 (self-host) | Phase 1 | Management | ✅ Already mandated ("Do NOT use CDN") |
| P0-5 | success/warning/info remediation | Phase 5 | Design | 🔶 **darken-hue applied as default (2026-08-03); design confirmation pending** |
| P0-6 | Confirm a Git branch for the migration | Rollback | User | ⏳ |
| P0-7 | Confirm staging environment for regression runs | Phase 15 | User | ⏳ |

### C.1 Stabilisation — **APPROVED to run before Phase 1** (2026-08-03)

These are **pre-existing production defects**, independent of UX4G. Fixing them first prevents migration bugs being misattributed.

| # | Item | Severity | Effort | Order |
|---|---|---|---|---|
| S-4 | Establish Playwright visual baseline over top 60 pages | **High** — without this, 537-page regression is unverifiable | 24 h | **1st — gates everything else** |
| S-1 | Restore `public/js/jquery-3.7.1.min.js` (0 bytes; jQuery served only from `code.jquery.com`) | **Critical** — CDN outage = total frontend failure | 2 h | 2nd |
| S-2 | Unify Bootstrap to one version (5.3.6 / 5.3.2 / 5.3.3 / **5.3.0-alpha1**) | **High** — a pre-release is live on a production page | 8 h | 3rd |
| S-3 | Triage Iconify (397 refs) + Font Awesome (169 refs) — neither has a confirmed stylesheet | High — icons may be rendering blank today | 8 h | 4th |
| S-5 | Remove `pinimg.com`, `codepen.io`, `wikimedia.org`, `front.codes`, Laravel marketing CDN refs | Medium — GIGW exposure | 4 h | 5th |
| S-6 | Delete ~1.4 MB zero-reference JS libraries | Low — free performance win | 8 h | 6th |

**S-4 runs first and alone.** It is the only item that changes no application code, and every subsequent item is validated against the baseline it produces. S-1 → S-6 are then executed **one at a time**, each with its own visual-diff verification, per the "never modify more than one major component at a time" rule.

#### Stage 0 execution order and gate criteria

| Step | Changes | Verified by | Gate to proceed |
|---|---|---|---|
| S-4 | Test files only — **no application code** | Baseline captures 60 pages without error | 60 baseline screenshots committed |
| S-1 | 1 binary file restored (`public/js/jquery-3.7.1.min.js`) | Visual diff = 0 changes; jQuery loads from local in DevTools | Zero diff |
| S-2 | ≤ 8 Blade files (script `src` only) | Visual diff = 0; all 350 `data-bs-toggle` triggers still fire | Zero diff + modal/dropdown/tab smoke test |
| S-3 | Report only, then ≤ 2 layout files | Icons render identically or improve | Zero regression |
| S-5 | ≤ 6 Blade files (remove/replace external refs) | Visual diff = 0 | Zero diff |
| S-6 | Delete unreferenced files under `public/admin_assets/libs/` | Visual diff = 0; no console 404s | Zero diff + clean console |

### C.2 Phase-by-phase readiness

| Phase | Prerequisite | Ready? |
|---|---|---|
| 1 — UX4G Setup | P0-1, P0-3; Noto Sans sourced; sprite gap accepted | ⏳ blocked on P0-1 |
| 2 — Design Tokens | Phase 1; P0-2, P0-5 | ⏳ |
| 3 — Global CSS | Phase 2; `@layer` order per blueprint §9.3 (corrected) | ⏳ |
| 4 — Utilities | Phase 3 | ⏳ |
| 5 — Buttons | Phase 2; P0-5 | ⏳ |
| 6 — Forms | Phase 5 | ⏳ |
| 7 — Navigation | Phase 3; **RBAC resolver contract-frozen** | ⏳ |
| 8 — Data components | Phase 5 | ⏳ |
| 9 — Interactive | Phase 3 | ⏳ |
| 10 — jQuery plugins | Phase 8 (DataTables theme depends on Tables) | ⏳ |
| 11 — Custom ERP components | Phases 5–9 | ⏳ |
| 12 — Pages | **All** component phases | ⏳ |
| 13 — Accessibility | Runs after every phase | ⏳ |
| 14 — Performance | Phase 12 | ⏳ |
| 15 — Regression | Runs after every phase; needs S-4 | ⏳ |
| 16 — Cleanup | Phase 12 | ⏳ |

### C.3 Contract-frozen — must not be touched by any phase

| Asset | Reason |
|---|---|
| `App\Services\SidebarMenu\SidebarNavResolver` | Resolves active tab/category/group/menu across server + client |
| `App\Services\SidebarMenu\MenuRouteMatcher` | Route→menu resolution |
| `master.blade.php:62-91` — 5-pane section resolution | Decouples a page's `@section` name from its RBAC tab; fragile |
| 55 Yajra DataTable PHP classes | Server-side pagination is backend-coupled |
| All controllers, routes, models, migrations, middleware, policies | Out of scope by mandate |

---

## Files Affected — Phase 0

**Modified: none.**

**Created (documentation only):**
- `docs/UX4G-Phase-0-Implementation-Checklist.md` (this file)

**Left untouched:** all 853 Blade templates, all CSS, all JS, `package.json`, `package-lock.json`, `composer.json`, `webpack.mix.js`, `vite.config.js`, and every backend file.

---

## Code Changes

None. Phase 0 is audit-only by mandate.

---

## Risks

| Risk | Severity | Mitigation |
|---|---|---|
| Proceeding to Phase 1 without resolving §B | **High** — would produce a dead bundle or force a 537-page asset-loading change | Resolve P0-1 first |
| Skipping C.1 stabilisation | **High** — migration bugs indistinguishable from pre-existing version-skew and CDN faults | Run Stage 0 first |
| Starting without a visual baseline (S-4) | **High** — regressions undetectable across 537 pages | Gate Phase 1 on S-4 |
| Assuming `--bs-primary` override re-brands UX4G | **Confirmed defect** — UX4G hard-codes `#613AF5` 38× (5 with `!important`) | Compiled override per Decision Note R-1 |
| Naive `@layer ux4g, app` ordering | **Confirmed defect** — UX4G ships 1,668 `!important`; cascade reverses layer order for `!important` | Strip at vendor time, or `@layer app-important, ux4g, app` |

---

## Testing Steps — Phase 0

Not applicable (no changes). Verification performed:

- `git status` — confirms only the new doc file is untracked
- `git diff package.json package-lock.json` — empty
- No Blade, CSS or JS file modified

---

## Accessibility Validation — Phase 0

Baseline recorded, not changed. Full gap report in blueprint §10. Headline: **9 skip-links across 537 pages**; `fc.layouts.master` implements it correctly and `admin.layouts.master` does not — one edit there covers 494 pages (Phase 13).

---

## Regression Validation — Phase 0

Not applicable. **S-4 (Playwright visual baseline) must be completed before any code-changing phase**, otherwise later phases cannot be validated.

---

## Rollback Strategy — Phase 0

Delete `docs/UX4G-Phase-0-Implementation-Checklist.md`. No application state changed.

---

## Completion Checklist — Phase 0

- [x] Project structure audited
- [x] Shared layouts identified (5)
- [x] Master Blade files identified
- [x] Common partials identified
- [x] Reusable components identified (50)
- [x] Bootstrap imports identified (**4 versions**)
- [x] Custom CSS identified (~1.70 MB, 7,128 `!important`)
- [x] JavaScript dependencies identified
- [x] jQuery plugins identified (35 vendored, usage measured)
- [x] Implementation checklist generated
- [x] **Nothing modified**
- [x] **P0-1 decision received — Option C** (2026-08-03)
- [x] **Stage 0 approved to run first** (2026-08-03)
- [ ] Approval to begin execution ← **awaiting**

---

## Next Phase

**Stage 0 — Stabilisation** (approved), then **Phase 1 — UX4G Setup**.

Stage 0 begins with **S-4 only**: a Playwright visual-regression baseline over the 60 highest-traffic pages. S-4 touches **no application code** — it adds test files under `tests/` and commits baseline screenshots. It is the safest possible first commit and is the prerequisite for verifying every subsequent step.

Phase 1 will then deliver, under Option C: self-hosted UX4G 2.0.8 (CSS/JS/Popper), self-hosted Noto Sans, the `resources/scss/` vendor+override structure compiling to existing `public/css/` paths, and **zero visual change** — proven against the S-4 baseline. It will not alter any component.
