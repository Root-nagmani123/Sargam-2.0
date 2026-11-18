# UX4G 3.0 Migration Report — SARGAM ERP

Status: **Phases 1–4 done (foundation, shell, shared components, dashboard). Module pages (phase 5+) not started.**
Date: 2026-11-18. Branch: `ux4g_audit`.

## 1. Current architecture

- Laravel 9 + Blade (942 views), jQuery, Bootstrap 5.3.6 (CSS via the 1 MB admin theme `public/admin_assets/css/styles.css`, JS via jsDelivr bundle).
- Layouts: `admin.layouts.master` (474 views), `fc.layouts.master` (31), `admin.layouts.timetable` (4), `layouts.app` (7).
- Live shell partials: `admin/layouts/header_new.blade.php`, `admin/layouts/sidebar_new.blade.php`. `header.blade.php`, `sidebar.blade.php` and `sidebar/*.blade.php` are **not included anywhere** (dead code — delete in a separate PR).
- Sidebar menu HTML for `#sidebarnav` and the group rail is built in `App\Http\Controllers\SidebarController`.

## 2. UI frameworks found

| Framework | Usage | Decision |
|---|---|---|
| Bootstrap 5.3 (CSS + JS) | ~600 views (`btn` 607, `card` 535, `form-control` 389, `modal` 191, `data-bs-*` 311) | Stays until each module is migrated; UX4G sits beside it |
| Tailwind | none | — |
| Foundation (Zurb) | none (grep hits are the "Foundation Course" domain) | — |
| Icons | Material Symbols 251 files, Bootstrap Icons 313, Iconify 28, Font Awesome 18, Tabler 2 | Standardise on Material (see §11) |
| UX4G v1.0 | only `accesibility-style_v1.css` + `weights.js` (old widget) on login/FC/timetable | Superseded |

## 3. UX4G integration method

- Source: official CDN `https://cdn.ux4g.gov.in/UX4G@3.3.2/` (the npm package `ux4g-web-components@3.0.3` is older and ships an 8.4 MB single CSS with base64 fonts; its README itself points to CDN 3.3.2).
- **Self-hosted**, pinned: `node scripts/vendor-ux4g.mjs 3.3.2` writes `public/vendor/ux4g/3.3.2/` (one flattened `ux4g.css`, 1.1 MB / 162 KB gzip, Material icon fonts, `ux4g-custom.js`) plus `SOURCE.json` with a sha256 of every upstream file. No CSP change needed (CDN host is not in `script-src`); no third-party request at runtime.
- Loaded by `<x-ux4g-styles />` **first** in all four layouts, before Bootstrap/theme CSS.

### Deliberate deviations from the CDN `index.css` (all in the vendor script header)

| Deviation | Why |
|---|---|
| Unlayered (no `@layer`) | Layered CSS always loses to unlayered Bootstrap; `ux4g-*` buttons rendered with Bootstrap's `button{border-radius:0}`. Loaded first + unlayered, `ux4g-*` classes win on specificity and legacy rules still win ties, so unmigrated pages are unchanged. |
| `reset/reset.css` skipped | Sets `outline:0` on every element, `display:block` on every `img`, strips `ul` bullets — breaks unmigrated pages. Bootstrap Reboot remains the reset. |
| `foundations/fonts.css` skipped | `@import`s ~20 Google font families for the theme inspector. SARGAM loads Noto Sans, Noto Sans Display, Noto Sans Devanagari via `<x-fonts-sargam />`. |
| `foundations/focus.css` skipped | Adds `position:relative` to every `:focus-visible` (moves absolutely-positioned controls). Same ring re-declared in `sargam-app.css` without it. |
| Brand themes (Neel/Gul/Rekh) skipped | SARGAM uses its own primary scale. |
| `ux4g.js` not shipped; `ux4g-custom.js` shipped but **not loaded** | `ux4g.js` binds `[data-bs-toggle=dropdown/collapse/tab/...]` → double-toggles Bootstrap widgets. `ux4g-custom.js` adds `is-open` + `body{overflow:hidden}` to Bootstrap `.modal` on `data-bs-toggle="modal"` clicks and never removes them → page scroll locked after closing any Bootstrap modal. Load it only on pages with no Bootstrap JS widgets. |

Known class collision: UX4G `modal.css` has a bare `.is-open{display:block}`; 10 app views toggle their own `is-open`. Safe because UX4G loads first (equal specificity → app wins). Re-check if load order ever changes.

## 4. Components migrated (this pass)

See `UX4G-COMPONENT-MAPPING.md`. Summary: global tokens/typography/focus ring; breadcrumb (349 views via `<x-breadcrum>`) → UX4G Breadcrumb + page `<h1>`; DataTables pagination (two patterns) → UX4G tokens; header, sidebar, mobile bottom bar → accessibility fixes + Material icons; dashboard notices + calendar → accessibility + tokens.

## 5. Components pending

Buttons, form controls, cards, tables, modals, alerts, badges/status, tabs, dropdowns in **module views** (≈600 files). Strategy: one module per PR, starting with the Attendance/`programme-dt-*` pattern; replace Bootstrap JS widgets in that module before loading `ux4g-custom.js` there.

## 6. Deprecated styles removed

- Template purple `#7367f0` (pagination active state, `custom.css` ×2, `roles-admin.css` ×1) → UX4G primary.
- Bootstrap `#0d6efd` gradient/pill DataTables pagination in `pre_header` → UX4G tokens, 4px radius.
- Montserrat removed from the shared font link (all direct references fall back to Noto Sans).
- Two Iconify icons in the header → Material.
- Hover-lift/translate animation on pagination, breadcrumb-shell hover shadow.

## 7–10. Accessibility / responsive / GIGW / WCAG improvements

Measured with axe-core 4.10.3 (WCAG 2.0/2.1 A + AA tags) in Chrome against the running app (local DB `testsargam8`, Super Admin account), same pages before and after:

| Page | axe violations before | after |
|---|---|---|
| `/dashboard` | 85 nodes / 6 rules | 7 nodes / 1 rule (documented exemption, below) |
| `/admin/roles` | 26 / 5 | 0 |
| `/admin/setup/member` | 25 / 5 | 0 |
| `/faculty/create` | 26 / 5 | 0 |

Fixes:
- **Contrast (1.4.3)**: theme `--bs-secondary-color` was `rgba(152,164,174,.75)` ≈ 1.9:1 → affects `text-body-secondary`, breadcrumbs, hints **app-wide**; now UX4G neutral secondary (10.4:1). Upstream UX4G info/warning text tokens fail on white (2.21 / 2.38) → overridden to darker official shades. Notice byline/date/pill, calendar weekday headers, mobile active tab (was white on pale tint).
- **Focus visible (2.4.7)**: focus ring is now the UX4G outline (2px primary, 2px offset). The old box-shadow ring was erased by `.shadow-none !important` on many inputs; header language select and module tabs had none. Tab-walk of the first 60 stops on 3 pages: 0 without a visible indicator.
- **Name/role/value (4.1.2)**: header module nav `menubar`→`tablist`; sidebar group links `aria-selected`→`aria-current`; mobile bottom bar → navigation list with `aria-current="page"`; hamburger and navbar toggler were nameless/role-less `<a>` → `<button>` with labels; dashboard notice card no longer nests a link inside `role="button"`.
- **Info & relationships (1.3.1)**: sidebar submenu `<ul>` now nested inside its `<li>` (was a sibling); calendar grid now has `role="row"`; breadcrumb page title is `<h1>` (was `<h4>`) on every page using `<x-breadcrum>`.
- **Screen readers**: Material ligature icons in the sidebar/header now `aria-hidden` (were read aloud: "chevron_right").
- **Focus order (2.4.3)**: notice dialog returns focus to the notice that opened it.
- **Bug fixed on the way**: Enter on a notice's "View attachment" link opened the modal instead of the file.
- **Responsive**: 4 pages × 9 widths (320, 375, 390, 414, 768, 1024, 1280, 1440, 1920): all HTTP 200, 0 px horizontal overflow, before and after.

Exemption kept: 7 contrast nodes on `/dashboard` are the faded previous/next-month days in the calendar — `aria-hidden`, non-interactive, decorative (WCAG 1.4.3 "incidental" exception).

## 11. Accessibility widget

Not integrated. The official page (ux4g.gov.in/resources/accessibility-widget) publishes no script URL, version or init code; it points to doc.ux4g.gov.in and support.ux4g@digitalindia.gov.in. Next step: obtain the official snippet from NeGD, then check it against `SecurityHeaders` `script-src`. The old v1 widget assets (`accesibility-style_v1.css`, `weights.js`) remain on login/FC/timetable and are untouched.

## 12. Remaining technical debt

- ≈600 module views still on Bootstrap components; 449 views with inline `style=""`, 371 with `<style>` blocks; 1,206 `!important` and 355 unique hex colours in `public/css`.
- Icons: Bootstrap Icons (313 files), Iconify, Font Awesome, Tabler still in modules. Shell + sidebar stay on the app's local **Material Symbols Rounded** (same Material family UX4G specifies, superset of the UX4G "Material Icons" font: `user_attributes` and possibly some DB-stored menu icons do not exist in the UX4G font — verified in Chrome).
- `dashboard-main.css` (1,800 lines) and `admin-header.css` still contain raw hex values; only failing colours were tokenised.
- Mobile bottom bar "Finance" dropdown links (Budget, Accounts) are `href="#"` placeholders.
- `--ds-accent` (#2563eb) and `--ds-canvas` remain SARGAM raw values pending a design decision.
- Localisation: only 29 translation calls and `lang/en.json`; nearly all UI strings are hardcoded English.
- Dead files: `admin/layouts/header.blade.php`, `sidebar.blade.php`, `sidebar/*.blade.php`.

## 13. Testing results

| Test | Result |
|---|---|
| App renders (4 pages × 9 widths) | 200, no overflow, before and after |
| Console errors | unchanged: 1 pre-existing 404 (`/storage/members/*.jpg`, missing local file) |
| axe-core WCAG 2.1 A/AA | see table above |
| Keyboard tab-walk (60 stops × 3 pages) | 0 stops without visible focus |
| Sidebar submenu expand/collapse, group switch, `aria-current` sync | pass |
| Skip link → `#main-content` | pass |
| Mobile hamburger opens sidebar | pass |
| Dashboard notice: Enter opens, Esc closes, focus returns, click opens, attachment link opens file | pass |
| Calendar day keyboard select | pass |
| Phase-1 visual regression (dashboard, roles @1440/768/375) | only heading font change (Noto Sans Display, +4 px) |

Not tested: Firefox and Edge (only Chrome was driven), screen readers (NVDA/JAWS), Lighthouse, roles other than Super Admin, module pages beyond the four above, PHPUnit suite.

## 14. Known limitations

- Verified against a local test database whose migration table shows 79 pending migrations; pages used here rendered fine, other pages may not.
- WCAG/GIGW: these results cover four pages and the shared shell only. **SARGAM is not claimed WCAG 2.1 AA or GIGW 3.0 compliant.**

## Backend changes

One file, presentation only: `app/Http/Controllers/SidebarController.php` — the HTML strings it returns now nest submenu `<ul>` inside its `<li>`, mark icons `aria-hidden`, add `aria-controls`, and use `aria-current` instead of `aria-selected` on group links. No query, permission, or route logic changed.
