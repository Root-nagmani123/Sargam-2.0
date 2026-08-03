# Phase 10 — jQuery Plugins & Third-Party Widgets

**Status:** ✅ Complete (audit + one concrete self-host shipped and gated)
**Date:** 2026-08-03
**Depends on:** Phase 8 (data components) — DataTables theming rides on the Tables work
**Gate result:** **58 passed, zero visual diff** (see §Testing)

---

## Objective

Confirm that every third-party jQuery/JS plugin the ERP depends on continues to
work under the UX4G (Bootstrap 5.3) baseline, and give each one an explicit
**disposition** — *keep / theme / replace / remove / self-host*. UX4G ≡ Bootstrap
5.3, so plugins that already target Bootstrap 5 need **no code change** — only a
compatibility confirmation. The phase ships one concrete fix (SweetAlert2
self-hosting) and records the remaining self-host work as gated follow-ups rather
than doing 250+ CDN edits in a single high-risk sweep.

**In scope:** plugin inventory, per-plugin disposition, one self-host fix, gate.
**Out of scope (no backend/logic change, per migration rules):** consolidating the
three overlapping `<select>` libraries, replacing plugins wholesale, changing any
plugin's initialisation or the data it operates on.

---

## What shipped this phase

### SweetAlert2 — CDN → self-hosted ✅

The app loaded SweetAlert2 from `cdn.jsdelivr.net/npm/sweetalert2@11` on **every
admin page** (footer + master layouts) — a hard external dependency that breaks all
confirmation dialogs and the global success toast during any CDN/offline event, and
a GIGW red flag for a government portal.

| Item | Before | After |
|---|---|---|
| Source | `cdn.jsdelivr.net/npm/sweetalert2@11` | `admin_assets/libs/sweetalert2/dist/sweetalert2.all.min.js` (local) |
| Version | v11.26.25 (whatever `@11` resolved to that day) | v11.26.25 (**pinned**, byte-identical) |
| Bundle | `.all` browser build | `.all` browser build (exposes `window.Swal` + injects its own CSS) |
| Refs repointed | — | 12 files |

**Verification (real browser, authenticated):**
- Loads locally, HTTP 200, no CDN request, no 4xx.
- `window.Swal` = `function`, `Swal.version` = `11.26.25`.
- `Swal.fire({icon:'success'})` renders the global success toast correctly.
- Zero JS console errors on `/batch`, `/attendance`.
- No remaining `cdn/*sweetalert*` references in any Blade file.

> Gotcha recorded: `sweetalert2@11` (no path) resolves to `sweetalert2.all.min.js`
> — the browser bundle. The `sweetalert2.min.js` **core** build does *not* expose
> `window.Swal` and must not be substituted. A duplicate `<script>` (footer +
> master) is pre-existing and intentional (custom.js needs Swal before it runs);
> left as-is — harmless, not this phase's concern.

---

## Plugin inventory & dispositions

### A. KEEP — already vendored locally, Bootstrap-5 compatible, no code change

| Plugin | Where | Disposition | Notes |
|---|---|---|---|
| **DataTables.net** | `libs/datatables.net` | KEEP + confirm theme | Bootstrap-5 dataTables build; visual chrome already validated in the Phase 8 tables gate. No change. |
| **FullCalendar** | `libs/fullcalendar` | KEEP | v6 global build, framework-agnostic. Excluded from the pixel baseline (async/date-dependent) but functionally intact. |
| **jquery-validation** | `libs/jquery-validation` | KEEP | Behaviour-only; no visual surface. |
| **SweetAlert2** | `libs/sweetalert2` | KEEP (now self-hosted) | See above. |
| **select2** (local) | `libs/select2` | KEEP + theme-check | Local copy exists; some pages still load it from CDN (see B). |
| **daterangepicker** (local) | `libs/daterangepicker` | KEEP + theme-check | Local copy exists; 18 CDN refs remain (see B). |

### B. SELF-HOST — still loaded from CDN (GIGW / offline risk); repoint or vendor

Ordered by surface. **None fixed this phase** beyond SweetAlert2 — each is its own
gated follow-up (repoint = low risk; vendor-then-repoint = medium).

| Plugin | CDN refs | Local copy? | Action | Priority |
|---|---|---|---|---|
| **jQuery 3.6.0** (`code.jquery.com`) | 15 | ✅ `public/js/jquery-3.6.0.min.js` exists | **Repoint** to local (S-1 restored local jQuery; these pages were missed, and pin a 3rd version) | **High** |
| **choices.js** | 80 | ❌ | Vendor + repoint | **High** (largest surface) |
| **bootstrap-icons** | 35 | ❌ | Vendor + repoint | High |
| **daterangepicker** | 18 | ✅ `libs/daterangepicker` | Repoint to local | High |
| **tom-select** | 16 | ❌ | Vendor + repoint | Medium |
| **flatpickr** | 16 | ❌ | Vendor + repoint | Medium |
| **summernote** | 16 (12 jsdelivr + 4 cdnjs) | ❌ | Vendor + repoint | Medium |
| **select2** (+bs5 theme) | 10 (7 + 3) | ✅ `libs/select2` | Repoint to local | Medium |
| **Google Fonts** (`fonts.googleapis.com`) | 28 | ❌ | Self-host WOFF2 (GIGW: no Google calls) | Medium |
| **pdfmake / jszip / xlsx / html2pdf** | 8 | ❌ | Vendor (export libs) | Low |
| **font-awesome / prism / jquery-validate / iconify** | ~5 | mixed | Vendor / consolidate with S-3 icon work | Low |

> **jQuery version fragmentation:** three versions are live across pages —
> `3.7.1` (local, S-1), `3.6.4` (×1 CDN), `3.6.0` (×15 CDN). Same class of defect
> as the Bootstrap "four versions" finding (S-2). Consolidating to the single local
> 3.7.1 is the clean end-state but is **behaviour-sensitive** (plugin compat) and is
> flagged, not force-changed, here.

### C. REPLACE — scheduled for a later phase

| Plugin | Refs | Disposition |
|---|---|---|
| **jquery-steps** | local + 2 CDN | REPLACE with the **UX4G Stepper** component in **Phase 11** (Custom ERP components). Kept working until then. |

### D. REMOVE — already deleted in Stage 0 / S-6

TinyMCE, Quill, jQuery UI, jvectormap and other zero-reference libraries (~1.4 MB)
were removed in S-6 with a zero-diff gate. No further action.

### E. Consolidation opportunities (flagged, NOT actioned — needs product decision)

- **Three `<select>` enhancers coexist:** choices.js (80), tom-select (16),
  select2 (10). Standardising on one would cut ~two libraries and unify styling —
  but it changes initialisation/markup on many forms, so it is out of a pure
  frontend-migration's scope. Recorded for the design/product team.
- **Two date pickers coexist:** flatpickr (16) + daterangepicker (18). Same note.

---

## Files Affected — Phase 10

| File(s) | Change |
|---|---|
| `resources/views/admin/layouts/master.blade.php` | SweetAlert2 `src`: CDN → local `.all.min.js` |
| `resources/views/admin/layouts/footer.blade.php` | SweetAlert2 `src`: CDN → local `.all.min.js` |
| `resources/views/admin/layouts/timetable.blade.php` + 9 FC/misc views | SweetAlert2 `src` repointed |
| `public/admin_assets/libs/sweetalert2/dist/sweetalert2.all.min.js` | Vendored v11.26.25 (79 KB) |

**No** controller, route, model, migration, middleware, or plugin-init change.
Every edit is a script-`src` swap to a byte-identical local file, or an added
vendor asset.

---

## Risks

| Risk | Mitigation |
|---|---|
| Wrong SweetAlert2 build (core vs `.all`) → `window.Swal` undefined | Verified `window.Swal` + toast in a real browser before gating. |
| Self-hosted version drifts from CDN behaviour | Pinned the **exact** version the CDN was serving (11.26.25). |
| Repointing jQuery 3.6.0 → local changes behaviour on 15 pages | Deferred as its own gated follow-up, not bundled here. |
| Visual regression from any change | Full 58-page gate = zero diff. |

## Testing

- **Functional:** real-browser check of `window.Swal`, version, and toast render on
  authenticated pages; no console errors; no residual CDN sweetalert refs.
- **Visual gate:** `baseline.spec.js`, chrome, 57 authenticated + preflight →
  **58 passed, zero diff.**
- **Harness note:** the gate must run against **XAMPP Apache** (ephemeral vhost on
  :8080), not `php artisan serve` — the Windows dev server is single-threaded (cannot
  fork) and refuses the parallel asset burst with `ERR_CONNECTION_REFUSED`, which also
  drops assets and corrupts screenshots. Apache (process-pooled) serves the burst
  cleanly. Command + vhost details recorded in the team memory / `gate-vhost.conf`.

## Rollback

`git checkout -- resources/views/admin/layouts/master.blade.php resources/views/admin/layouts/footer.blade.php …`
restores the CDN `src`; delete the vendored `sweetalert2.all.min.js`. No data or
schema touched — rollback is a pure revert.

---

## Follow-ups (recorded for later phases)

1. **[High]** Repoint 15× `code.jquery.com/jquery-3.6.0` → local; consolidate jQuery to one version.
2. **[High]** Vendor + repoint choices.js (80) and bootstrap-icons (35).
3. **[High]** Repoint daterangepicker (18) and select2 (10) to their existing local copies.
4. **[Med]** Vendor tom-select, flatpickr, summernote; self-host Google Fonts (GIGW).
5. **[Med]** Vendor export libs (pdfmake/jszip/xlsx/html2pdf).
6. **[Phase 11]** Replace jquery-steps with the UX4G Stepper.
7. **[Product]** Decide single `<select>` library and single date picker (consolidation).
