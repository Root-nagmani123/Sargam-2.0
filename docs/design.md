# Design System — `sargam-app.css`

The single consolidated custom stylesheet for the ERP:
`public/css/sargam-app.css`. It is loaded **last** in every layout (after
Bootstrap and the admin theme `styles.css`), so its rules win on cascade order
without needing `!important`.

> **One file only.** All bespoke, app-wide UI styling lives here. Do **not**
> create new global stylesheets — edit this file. Page-specific tweaks belong in
> a page-scoped `<style>` inside that view's `@push('styles')` block, built on
> the `--ds-*` tokens below (see `student_medical_exemption/index.blade.php` for
> the reference pattern).

The file is organised into three layers.

---

## Layer A — Design tokens (`:root`)

CSS custom properties consumed by every component and every page-scoped style.
Use the token, never the raw value, so the whole app re-themes from one place.

### Spacing (4px scale)
| Token | Value | Notes |
|---|---|---|
| `--ds-space-1` | 0.25rem | 4px |
| `--ds-space-2` | 0.5rem | 8px |
| `--ds-space-3` | 1rem | 16px — **default** page/gap unit |
| `--ds-space-4` | 1.5rem | 24px — between sections |
| `--ds-space-5` | 2rem | 32px — between major blocks |
| `--ds-space-6` | 3rem | 48px |

### Radius
| Token | Value | Use |
|---|---|---|
| `--ds-radius-1` / `--ds-radius` | 4px | **default** — buttons, inputs, badges, chips |
| `--ds-radius-2` / `--ds-radius-card` | 8px | cards, modals, panels |
| `--ds-radius-0` | 0 | square |

### Shadow
| Token | Use |
|---|---|
| `--ds-shadow-sm` | hairline lift (hover chips, buttons) |
| `--ds-shadow` | default card elevation |
| `--ds-shadow-lg` | modals / popovers |

### Colour
| Token | Value | Meaning |
|---|---|---|
| `--ds-primary` | `var(--bs-primary, #004a93)` | brand blue |
| `--ds-secondary` | `#b12923` | brand red |
| `--ds-ink` | `#1f2937` | primary text |
| `--ds-ink-muted` | `#667085` | secondary text |
| `--ds-line` | `#e5e7eb` | hairline borders |
| `--ds-surface` | `#ffffff` | card surface |
| `--ds-surface-2` | `#f8fafc` | subtle fill / table headers |
| `--ds-canvas` | `#f1f4f9` | page background |

### Controls
| Token | Value | Use |
|---|---|---|
| `--ds-control-h` | 2.5rem (40px) | consistent input/button height |
| `--ds-control-h-sm` | 2rem (32px) | compact controls |
| `--ds-focus-ring` | `0 0 0 0.2rem rgba(0 74 147 / .20)` | keyboard focus ring |

---

## Layer B — Gentle app-wide refinements (visual only)

Conservative rules applied to **~400+ existing views**, so they are deliberately
narrow: no layout shifts, no broad `!important`, nothing that changes behaviour.

- **Type rendering** — antialiasing + `optimizeLegibility` on `body`.
- **Focus ring** — `:focus-visible` on `.btn`, `.form-control`, `.form-select`,
  `.page-link`, `a.dropdown-item` (keyboard only; mouse unaffected).
- **Table headers** — quieter enterprise look (weight/colour/spacing only, no
  size or text-transform overrides).
- **Control heights** — `min-height` (not `height`) on form controls.
- **Select2 hide rule** — ships the canonical `.select2-hidden-accessible`
  clip rule that the theme omits, preventing the "two dropdowns" bug.
- **Project-wide page padding** — the theme's flat `padding:10px` on every
  `.container-fluid` is realigned to `--ds-space-3` (16px; 8px on phones),
  scoped under `.page-wrapper` so login/public pages are untouched.
- **DataTables sort arrows** — re-shown (the theme hides them) and the active
  direction highlighted in brand blue.

You normally don't touch Layer B; it's the baseline that makes legacy screens
look consistent for free.

---

## Layer C — Opt-in components (`.ds-*`)

Inert until a page adds the class. Use these when **modernizing a screen**.

| Class | What it is |
|---|---|
| `.ds-page-header` / `.ds-page-title` / `.ds-page-subtitle` | page heading row (flex, space-between) |
| `.ds-card` + `.ds-card-header` / `.ds-card-body` | surface card (8px radius, shadow) |
| `.ds-card--accent` | card with a brand-coloured accent |
| `.ds-toolbar` / `.ds-toolbar-spacer` | filter/action toolbar row |
| `.ds-stat-card` (`.ds-stat-label`/`.ds-stat-value`/`.ds-stat-icon`) | KPI tile |
| `.ds-table-wrap` / `.ds-table-sticky` | scroll wrapper + sticky header |
| `.ds-actions` | row-action button cluster |
| `.ds-form-section` / `.ds-form-section-title` | grouped form block |
| `.ds-stepper` (`.ds-step`, `.ds-step-index`, `.is-active`/`.is-done`) | multi-step progress |
| `.ds-empty-state` | empty/zero-record placeholder |
| `.ds-stack-2/3/4` | vertical rhythm (`* + *` margin) |
| `.ds-section` / `.ds-block` | section (24px) / block (32px) bottom gaps |

---

## Usage rules

1. **Edit only this file** for global custom UI. New global CSS files fragment
   the system.
2. **Always use `--ds-*` tokens** in page-scoped styles — never hard-code
   `#004a93`, `16px`, etc.
3. **Layer C is opt-in.** Adding a `.ds-*` class is safe; it changes nothing
   until applied.
4. **Page-scoped styles** go in `@push('styles')` (rendered by the master
   layout's `@stack('styles')` in `<head>`), and should express only what
   Bootstrap utilities + `.ds-*` can't.
5. **Load order matters** — this file must remain the last stylesheet so it
   overrides the theme without `!important`.

---

## Applying the design to a mess-master index page

The `mess/**/index.blade.php` list pages all use the
`components.mess-master-datatables` component (client/server DataTable + the
`mess-column-manager.js` Columns UI + `data-mess-*` exports). To modernize one to
the new-design chrome **without** rewriting that working stack, follow this recipe
(reference implementation: `resources/views/mess/stores/index.blade.php`):

1. **Keep the component.** Don't convert to Yajra or the programme Columns modal
   for state — the component still owns init, live search, column state, and
   exports.
2. **Let the global enhancer build the chrome.** `public/js/datatable-global-ui.js`
   enhances the table (it only skips tables carrying `data-mess-column-manager`).
   Give it explicit slots — `<div class="programme-dt-search" data-dt-search-for="TID">`
   and `<div class="programme-dt-footer …" data-dt-footer-for="TID">` — and pass the
   component `'dom' => '<"dt-top"f>rt<"dt-foot"lip>'`. The enhancer relocates the
   search and renders the **"Showing [N▾] of M items"** footer + pagination; its
   `updateCount()` force-writes the info text, so the component's own `language`
   text is irrelevant. Collapse the emptied wrappers with
   `.dt-top:empty,.dt-foot:empty{display:none}`.
3. **Table + status:** add `programme-dt-table`, wrap in `.programme-dt-panel`, and
   render status with `programme-status-badge programme-status-badge--active/--inactive`.
4. **Page chrome:** put the primary action button in the `<x-breadcrum>` slot (it
   renders the header card), then a right-aligned Download / Print bar. Two options:
   (a) the quick client-side hooks `data-mess-excel-export="TID"` +
   `data-mess-print-table="TID" data-mess-print-template="lbsnaa"` (CSV + Officer's-
   Mess print, wired by `mess-column-manager.js`); or (b) the **branded server-side
   report** below when the page needs the official LBSNAA header — see "Branded
   report exports".
5. **Column visibility = the programme-style modal, not the mess dropdown.** Hide
   the injected dropdown (`.mess-col-manager-dropdown{display:none!important}`) but
   **keep the mess Column-manager initialised** — it is the visibility state engine
   that keeps export column-sync correct (its `resolveExportIndexes(TID)` feeds both
   the client-side exports and the branded report's `columns=` param; disabling it
   misaligns exports when a middle column is hidden). Add a `.programme-dt-btn-columns` button opening
   a `modal-lg` with a `#…ColumnToggleGrid`, and a small bridge: on `show.bs.modal`,
   read `MessColumnManager.get(TID)`, build checkboxes from `mgr.baseColumns` +
   `mgr.state`, and on change set `mgr.state.visibility[idx]`, `mgr.saveState()`,
   `mgr.apply()` (enforce ≥1 visible; retry-poll since the manager inits async).
6. **Pagination** is `full_numbers` (global default) — scope CSS to drop
   `.paginate_button.first/.last` and swap `.previous/.next .page-link` text for
   ‹ › arrows.
7. Load `bootstrap-icons` via `@push('styles')`; keep all page CSS scoped under a
   page class and built on `--ds-*` tokens.

---

## Branded report exports (Print PDF + styled XLSX)

The official LBSNAA report header — left emblem, Hindi + English academy line, the
75-years-of-Constitution logo (right), a centred blue title, and a blue table-header
band — is a **server-side** export, not a browser print. Both Print and Download
share that header so they stay visually in step (a plain CSV can't carry
logos/colour, so **Download is a styled `.xlsx`, not a CSV**). Reference pair:
`app/Http/Controllers/Admin/StudentMedicalExemptionController.php` +
`app/Exports/StudentMedicalExemptionExport.php`; simplest copy to clone is the Store
Master set (`StoreController@export`, `app/Exports/StoreMasterExport.php`,
`resources/views/mess/stores/export_pdf.blade.php`).

- **One controller `export()` method, `?format=pdf|excel`.** `pdf` →
  `Pdf::loadView(...)->setPaper('a4', …)` (Barryvdh DomPDF); `excel` →
  `Excel::download($export, '…xlsx', ExcelFormat::XLSX)` (Maatwebsite).
- **PDF blade** — the shared header markup (`$logoLeft`/`$logoRight`/`$titleHindi`
  as base64 data-URIs, `.inst-en`, optional `$courseName`/`$courseDuration`,
  `.report-title` with the `#004a93` bottom border, `thead th{background:#004a93}`,
  page-number `<script type="text/php">` needs `isPhpEnabled`). Devanagari is a
  **pre-shaped image** (`lbsnaa-title-hi.png`) — DomPDF can't shape Indic text.
- **XLSX** — a `WithEvents` export whose `AfterSheet` inserts the meta/heading rows,
  paints the blue title band + blue heading band, zebra-stripes the body, borders
  the table, and floats the logos over row 1 with `Drawing`. Column widths via
  `WithColumnWidths`.
- **Logos** live in `public/admin_assets/images/logos/` (`logo_new.png`,
  `constitution-75.png` ?: `Azadi-Ka-Amrit-Mahotsav-Logo.png`, `lbsnaa-title-hi.png`);
  the controller reads them with `public_path()` (PDF base64-encodes; XLSX sets the
  file path on a `Drawing`).
- **Filter/column parity:** the frontend builds the export URL with the live search
  term + `columns=` from `MessColumnManager.resolveExportIndexes(TID)` (or the
  DataTable's own column state); the export mirrors those and orders rows the same
  way the listing does, so the report matches the screen. **Print** opens the PDF
  inline (`?inline=1` → `$pdf->stream()`); **Download** streams the file.
- Modules with no course context (e.g. mess Store Master) simply leave
  `courseName`/`courseDuration` empty — the rest of the header is identical.

---

## Delete confirmation + success toast

Destructive actions use a branded confirm dialog, and all success feedback renders
as one global toast — never a native `confirm()` or a per-page inline alert.

- **Confirm dialog** — reuse the global `programme-confirm-*` design system
  (`public/css/custom.css`): a centred card with a red outlined `!` icon
  (`programme-confirm-icon--danger`), title, message, and two buttons —
  *outlined-red* **Cancel, Keep it** (`programme-confirm-cancel--danger`) + *solid-red*
  **Yes, Delete** (`programme-confirm-ok--danger`). The design-system title is brand
  blue; the mock wants it dark, so override `.…-title{color:#101828}`. Course Master
  (`admin/programme/index.blade.php`) is the original; the reusable mess version is
  `resources/views/mess/partials/delete-confirm.blade.php`.
- **Success toast** — SweetAlert2 + `public/js/sargam-success-toast.js` (both loaded
  globally in the master layout) turn **any** `Swal.fire({icon:'success', …})` into a
  top-right green-check "Success" card. So flash a `session('success')` and fire it as
  a success Swal on load — do **not** render your own inline `alert-success`
  (`[[global-success-toast]]`).
- **Reusable mess partial** — `@include('mess.partials.delete-confirm')` once per
  listing page. Give each delete `<form>` the class `mess-delete-form` (drop any native
  `onsubmit="return confirm(...)"`) plus optional `data-confirm-title` /
  `data-confirm-message`; a capture-phase submit interceptor shows the dialog and only
  the OK button re-submits the form. The same partial fires the success toast from
  `session('success')`, so the page must not also print an inline success alert.

---

## Add / Edit form modals

The new-design create/edit dialog (reference: the Add Store / Edit Store modals in
`resources/views/mess/stores/index.blade.php`) is a clean, rounded Bootstrap modal —
not the theme's `bg-light` header/footer bars.

- **Shell:** `.modal-content` at `border-radius:16px` + a soft shadow; a plain white
  `.modal-header` (bold ~1.5rem title + `.btn-close`) and `.modal-footer`, each with
  a single `1px solid var(--ds-line)` hairline separator. Scope all rules under a
  page-specific modal class (e.g. `.store-modal`) — **modals usually sit outside the
  page's `.<page>-page` wrapper**, so a wrapper-scoped selector won't reach them.
- **Fields:** label above input; `.form-label` at `font-weight:600; .875rem`; controls
  at `min-height:44px; border-radius:8px; 1px solid var(--ds-line)`, a muted
  `::placeholder` (`#98a2b3`), and the standard focus ring
  (`border:--ds-primary; box-shadow:0 0 0 3px rgba(0,74,147,.12)`). Give inputs real
  placeholder text ("e.g. …") and selects a `Select …` placeholder option.
- **Footer buttons:** right-aligned — **Cancel** is *outlined* in the brand red
  (`color/border: var(--ds-secondary)`, white fill, faint `#fff5f5` hover); the
  submit is a solid `.btn btn-primary`. Both `min-height:44px; border-radius:8px;
  font-weight:600`.
- **Placeholders vs. defaults:** showing a `Select …` placeholder (value `""`) instead
  of preselecting is fine when the field is server-side `nullable` with a sensible
  default (empty string → null via `ConvertEmptyStringsToNull`, then the controller
  fills it). Drop the HTML `required` on a single-option select so the placeholder can
  stay, and reset selects to `''` (not the old default) in the modal's
  `hidden.bs.modal` handler so it reopens matching the design.
- All values come from `--ds-*` tokens; the block lives in the page's
  `@push('styles')`.

---

See also:

- [master.md](master.md) — how this stylesheet and page-scoped
  `@push('styles')`/`@push('scripts')` blocks are wired into the layout.
- [new-design-index-page.md](new-design-index-page.md) — the "new design" admin
  listing chrome (`programme-dt` toolbar, table panel, footer). That pattern
  lives in `custom.css`, not here; this file is the token/component layer it
  builds on.
- [column-visibility.md](column-visibility.md) — the Columns modal, its
  remembered state, and keeping exports in sync.
- `resources/views/mess/stores/index.blade.php` — reference implementation of the
  "Applying the design to a mess-master index page" recipe above.
- `StoreController@export` + `app/Exports/StoreMasterExport.php` +
  `resources/views/mess/stores/export_pdf.blade.php` — reference implementation of
  the "Branded report exports" section above.
- The Add Store / Edit Store modals in `resources/views/mess/stores/index.blade.php`
  — reference implementation of the "Add / Edit form modals" section above.
- `resources/views/mess/partials/delete-confirm.blade.php` — reusable
  delete-confirmation dialog + success toast (the "Delete confirmation + success
  toast" section above); `public/js/sargam-success-toast.js` is the toast engine.
