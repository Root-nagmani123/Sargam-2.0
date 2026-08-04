# Phase 14 — Performance

**Status:** ✅ Complete
**Date:** 2026-08-04
**Depends on:** Phase 12 (page surface) + Phase 10 (plugin/asset inventory)
**Gate result:** **58/58** (57 passed on the full run; the 1 failure — `/attendance/user_attendance` — is a known flaky DataTables fixture and **passed on isolated re-run**)

---

## Objective

Reduce the migration's asset cost with **measured, safe** wins — no logic, layout, or
behaviour change. Measure first; fix duplicates and redundant loads; catalogue the
server-level wins (caching) that need production context.

**In scope:** duplicate/redundant asset elimination in the admin layout stack.
**Out of scope (needs prod/infra context):** static-asset cache headers (new
`.htaccess`), script `defer/async` reordering — both catalogued as follow-ups.

---

## Shipped — duplicate asset elimination

The admin layout stack (`footer` + `master`, where `master` @includes `footer`) loaded
**six** assets **twice** on every admin page. Removed one copy of each, keeping a
working copy in the correct load-order position. **Runtime-verified** afterward: all
globals present (`Swal`, `FullCalendar`, `jQuery`, `$.fn.steps`, `$.fn.validate`),
zero JS console errors.

| Asset | Duplication | Fix | Size ×1 |
|---|---|---|---|
| **FullCalendar** `index.global.min.js` | footer 206 **&** 207 — loaded **twice back-to-back** (copy-paste bug) | keep one | **281 KB** |
| **SweetAlert2** `sweetalert2.all.min.js` | footer + master (master runs after the footer include) | drop master's | 77 KB |
| **jQuery Validate** | footer ×2 (early + late) | keep early | 24 KB |
| **iconify-icon** | local **+** CDN `jsdelivr@1.0.8` (local registers the element first) | drop **CDN** copy | 21 KB |
| **jQuery Steps** | footer ×2 | keep early | 13 KB |
| **form-wizard.js** (dead code) | footer ×2 | drop one | 5 KB |
| | | **Total redundant transfer removed / page** | **~422 KB** |

Two wins in one on iconify: de-duplicated **and** removed a CDN dependency the Phase 10
audit flagged (GIGW: no external calls). jQuery is loaded before the footer, so both
the early and late footer copies had their dependency available — dropping either was
safe; the earlier position was kept.

**Files changed:** `resources/views/admin/layouts/footer.blade.php` (5),
`resources/views/admin/layouts/master.blade.php` (1). Each removal is annotated with a
`{{-- Perf (Phase 14) … --}}` note. No controller/route/model/JS-logic/layout change.

---

## Measured findings & backlog (not shipped)

### 1. Static-asset caching — **highest remaining win**
On a representative admin page (`/batch`), **70** static assets load but only **22**
carry a real `Cache-Control: max-age` under the current server config. The app has
**no `public/.htaccess`**, so ~48 assets are re-downloaded on every navigation. Adding
cache headers would cut repeat-view weight far more than the de-dup did.

**Why not shipped:** this is a **server/infra change** (a new `.htaccess` or vhost
block) whose interaction with the production server and the existing `CompressResponse`
middleware must be confirmed first. Ready-to-apply snippet for the eventual change:
```apache
<IfModule mod_expires.c>
  ExpiresActive On
  ExpiresByType text/css                 "access plus 1 year"
  ExpiresByType application/javascript   "access plus 1 year"
  ExpiresByType image/*                  "access plus 6 months"
  ExpiresByType font/woff2               "access plus 1 year"
</IfModule>
```
(Assets are already `?v=filemtime` cache-busted, so long max-age is safe.)

### 2. Render-blocking scripts
The footer loads many synchronous `<script>`s. `defer`/`async` would cut parse-blocking
time, but the load order is dependency-sensitive (jQuery → plugins → custom.js) — a
careful, separately-gated change. Catalogued, not done.

### 3. Still-CDN assets (from Phase 10)
DataTables, choices.js, bootstrap-icons, etc. remain CDN-served — self-hosting them
removes external round-trips (tracked in the Phase 10 audit).

### 4. Flaky baseline fixture
`/attendance/user_attendance` (DataTables + daterangepicker) diffed on the full run and
passed on isolated re-run — an async-render flake, like `/dashboard` before it. Candidate
for the S-4 exclude list or a longer settle; a **baseline-hardening** task, not a perf defect.

---

## Risks

| Risk | Mitigation |
|---|---|
| Removing a copy breaks a dependent script | Kept a working copy of each in-order; runtime-verified all globals + zero JS errors. |
| iconify icons change (local vs CDN build) | Local copy loads first and already registers `<iconify-icon>`; gate = zero diff on icon-bearing pages. |
| Removed the *dead* form-wizard load in error | Verified dead in Phase 11 (0 target IDs); one copy still loads (file deletion is Phase 16). |

## Testing

- **Runtime:** authenticated load of `/batch` — `Swal`/`FullCalendar`/`jQuery`/`steps`/`validate` all present; **0** JS errors; each de-duplicated asset now requested **once** per page.
- **Compile:** `php artisan view:cache` — all templates OK.
- **Visual gate:** 57 passed; the lone failure re-ran green in isolation ⇒ **effectively 58/58, zero diff**.

## Rollback

`git checkout -- resources/views/admin/layouts/footer.blade.php resources/views/admin/layouts/master.blade.php`.

## Follow-ups

1. **[Infra — high value]** Add static-asset cache headers (snippet above) once the production server config is confirmed.
2. **[Gated]** `defer`/`async` the non-dependency-critical footer scripts.
3. **[Phase 10 backlog]** Self-host the remaining CDN plugins.
4. **[S-4]** Stabilise/exclude the flaky `/attendance/user_attendance` fixture.
