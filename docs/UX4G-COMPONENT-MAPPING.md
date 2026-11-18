# UX4G Component Mapping — SARGAM

UX4G 3.3.2 class names below were read from the shipped CSS (`public/vendor/ux4g/3.3.2/ux4g.css`). Where the package README disagrees with the CSS (e.g. button sizes are `ux4g-btn-xs/s/m/l/xl`, not `-sm/-md`; breadcrumb needs `-list/-item/-link`), the CSS wins.

Status: **Done** = migrated and verified in the running app · **Partial** = tokens/a11y done, markup still Bootstrap · **Pending** = not started · **Blocked** = needs a decision or upstream input.

## Foundation and shell

| SARGAM component | Current implementation | UX4G replacement | File(s) | Status |
|---|---|---|---|---|
| Design tokens | `--ds-*` raw values | UX4G tokens; `--ds-*` are aliases | `public/css/sargam-app.css` | Done |
| Typography | Noto Sans + Montserrat headings | `--ux4g-font-family-base/display` (Noto Sans / Noto Sans Display + Devanagari) | `components/fonts-sargam.blade.php`, `public/css/custom.css` | Done |
| Focus ring | box-shadow (erased by `.shadow-none`) | UX4G focus ring (outline) | `sargam-app.css` | Done |
| Skip link | `.skip-link` → `#main-content` | (no UX4G component) | `header_new.blade.php` | Done (verified) |
| Header / top utility bar | Bootstrap navbar + custom CSS | UX4G Navbar pattern composed from tokens (behaviour stays Bootstrap) | `admin/layouts/header_new.blade.php`, `public/css/admin-header.css` | Partial — a11y fixed; CSS still raw hex |
| Header module tabs | `role="menubar"` + `role="tab"` | tablist semantics | `header_new.blade.php` | Done |
| Mobile bottom bar | menubar/tab roles mixed with buttons | navigation list + `aria-current="page"` | `header_new.blade.php`, `pre_header.blade.php` (`sargamSetNavActive`), `nav-state.js`, `sidebar-navigation-fixed.js`, `master.blade.php` | Done |
| Hamburger / navbar toggler | `<a>` without name/role, Iconify | `<button>` + `aria-label`, Material icon | `header_new.blade.php` | Done |
| Sidebar group rail | `<a aria-selected>` | `aria-current`; icons `aria-hidden` | `sidebar_new.blade.php`, `SidebarController@groups`, `master.blade.php` | Done |
| Sidebar menu (`#sidebarnav`) | submenu `<ul>` sibling of `<li>` | valid nested list, `aria-controls`, icons `aria-hidden` | `SidebarController` (HTML string) | Done |
| Sidebar visual | custom Google-style CSS | UX4G Sidebar (`components/sidebar.css`) | `sidebar_new.blade.php` | Pending |
| Breadcrumb | Bootstrap `.breadcrumb` + `<h4>` title | **UX4G Breadcrumb** (`ux4g-breadcrumb ux4g-breadcrumb-icon`, `-list/-item/-link`) + `<h1>` title | `components/breadcrum.blade.php` (349 views) | Done |
| Breadcrumb default action button | `btn btn-primary btn-sm` | `ux4g-btn-primary ux4g-btn-s` | `components/breadcrum.blade.php` | Done (4 callers) |
| Footer | theme footer | UX4G Footer (`ux4g-footer-wrapper`) | `admin/layouts/footer.blade.php` | Pending |
| Accessibility widget | v1 `weights.js` on login/FC/timetable | Official UX4G widget | — | Blocked (no official snippet published) |

## Data display

| SARGAM component | Current implementation | UX4G replacement | File(s) | Status |
|---|---|---|---|---|
| DataTables pagination (classic) | Bootstrap blue `#0d6efd` gradient pills | UX4G tokens (primary-strong fill, radius-sm) | `admin/layouts/pre_header.blade.php` | Done |
| DataTables pagination (`programme-dt-*`) | template purple `#7367f0` border | UX4G primary | `public/css/custom.css`, `public/css/roles-admin.css` | Done |
| Tables | Bootstrap `.table` + DataTables bs5 | UX4G Table (`ux4g-table ux4g-table-m`) | 377 views | Pending (DataTables bs5 styling must be ported first) |
| Cards / KPI cards | Bootstrap `.card` + custom | UX4G Card (`ux4g-card ux4g-card-outline`) | 535 views, `dashboard.blade.php` | Pending |
| Badges / status | Bootstrap `.badge` | UX4G Tag (`ux4g-tag-tonal-{success,warning,error,info,neutral}`) with text (+ icon) | 232 views | Pending |
| Dashboard notice card | `div role="button"` containing a link | native `<button>` title; tokens for byline/date/pill | `admin/dashboard.blade.php`, `public/css/dashboard-main.css` | Done |
| Dashboard calendar | ARIA grid without rows; `#adb5bd` weekdays | `role="row"` (`display:contents`); UX4G tertiary text | `components/calendar.blade.php`, `admin_assets/css/dashboard-calendar.css` | Done |

## Forms and actions

| SARGAM component | Current implementation | UX4G replacement | File(s) | Status |
|---|---|---|---|---|
| Button | Bootstrap `.btn-*` | `ux4g-btn-{primary,outline-primary,tonal-primary,text-primary,danger,outline-neutral}` + `ux4g-btn-{xs,s,m,l,xl}` | 607 views | Pending |
| Icon button | custom | `ux4g-icon-btn ux4g-icon-btn-{variant} ux4g-icon-btn-{size}` | — | Pending |
| Input / textarea | `.form-control` | `ux4g-input-container ux4g-input-{size} ux4g-input-{state}` | 389 views | Partial (focus ring) |
| Select | `.form-select`, select2 (114), Choices (44) | UX4G Select / Dropdown (needs `ux4g-custom.js`) | 310 views | Pending |
| Checkbox / radio / switch | `.form-check` | `ux4g-checkbox` / `ux4g-radio` / `ux4g-switch` | — | Partial (focus ring) |
| Date picker | bootstrap-datepicker, flatpickr, `type=date` | UX4G Date-time | 133 views | Pending |
| File upload | dropzone, native | UX4G Upload | — | Pending |

## Feedback and overlays

| SARGAM component | Current implementation | UX4G replacement | File(s) | Status |
|---|---|---|---|---|
| Alert | Bootstrap `.alert` | `ux4g-alert ux4g-alert-{info,success,warning,error}` | 236 views | Pending |
| Toast | toastr (35), SweetAlert2 (69), `sargam-success-toast.js` | UX4G context alert / toast | — | Pending |
| Modal | Bootstrap modal JS (172 files) | `ux4g-modal-backdrop` + `ux4g-modal-box` (needs `ux4g-custom.js`; conflicts with Bootstrap modals — see report §3) | 191 views | Pending |
| Tabs | Bootstrap `nav-tabs` | `ux4g-tab ux4g-tab-underline` | 51 views | Pending |
| Accordion | Bootstrap | `ux4g-accordion` | 11 views | Pending |
| Tooltip / popover | Bootstrap JS | `ux4g-tooltip` / `ux4g-popover` | 25 views | Pending |
| Empty state | ad hoc | UX4G Empty state | — | Pending |
| Loading | `sargam-loader`, spinners | `ux4g-spinner-primary-full` | — | Pending |

## Custom SARGAM components (no UX4G equivalent)

| Component | Why custom | Rules |
|---|---|---|
| `window.sargamSetNavActive(el, on)` (pre_header) | keeps desktop tabs (`aria-selected`) and mobile links (`aria-current`) in sync from 4 existing scripts | presentation only |
| Calendar month grid | UX4G date-time picker is an input, not a month dashboard view | UX4G tokens; ARIA grid |
| `programme-dt-*` DataTables toolbar | DataTables integration | UX4G tokens for colour/radius |
