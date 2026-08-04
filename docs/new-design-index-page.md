# The "New Design" — admin index pages

When a ticket says *"apply the new design"* to an admin listing screen, it means
**this page chrome**: the `programme-dt` toolbar, table panel and footer, as
built on the Attendance page.

**Canonical references:** `admin/attendance/index.blade.php` (the chrome) and
`admin/country/index.blade.php` for the **full modern pattern** — client-side DataTable,
`simple_numbers` pagination, Download/Print, soft status badge + icon-over-label actions,
and **modal** create/edit (§3b, §3c). It is not the `employee_idcard` layout and not the
old DataTables default chrome.

This doc covers page *chrome*. The `--ds-*` token and `.ds-*` component layer is
documented separately in [design.md](design.md); column visibility has its own
doc, [column-visibility.md](column-visibility.md).

---

## Where the CSS lives

| Layer | File | Loaded |
|---|---|---|
| `programme-dt-*` chrome | `public/css/custom.css:104-640` | `admin/layouts/pre_header.blade.php:19` |
| `--ds-*` tokens + `.ds-*` components | `public/css/sargam-app.css` | `pre_header.blade.php:39` — **must stay last** |
| Global DataTables behaviour | `public/js/datatable-global-ui.js` | `admin/layouts/footer.blade.php:72` |
| Page-specific | `public/css/<module>-admin.css` or an inline `<style>` | `@push('styles')` / `@section('css')` |

Page CSS is cache-busted with:

```blade
<link rel="stylesheet" href="{{ asset('css/foo.css') }}?v={{ @filemtime(public_path('css/foo.css')) ?: time() }}">
```

The `@` plus `?: time()` fallback is the dominant idiom (~35 call sites) — keep it.

---

## Page skeleton

Order on the page, top to bottom:

```
container-fluid <module>-page
├── <x-breadcrum title="…">            ← page heading + primary action (opens create modal §3c)
├── Download / Print  (or status pills + Download)   ← ABOVE the card
└── card > card-body
    ├── toolbar   (filters left · Columns + search right)
    ├── programme-dt-panel
    │   └── table-responsive > table.programme-dt-table   ← Status column + row actions §3b
    └── programme-dt-footer            (pagination ‹ 1 2 3 › §5 · "Showing N of M items")
+ Create / Edit modal §3c    +    Column-visibility modal
```

The action row (status pills / Download / Print) sits **above the card**, not inside it.

---

## 1. Status pills + Download

`attendance/index.blade.php:231-246`:

```html
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
    <ul class="nav nav-pills gap-2 p-1 rounded-1 programme-status-tabs bg-white mb-0"
        role="group" aria-label="Filter courses by status">
        <li class="nav-item" role="presentation">
            <button type="button" class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill active"
                    data-att-status="active" aria-pressed="true" aria-current="true">Active</button>
        </li>
        <li class="nav-item" role="presentation">
            <button type="button" class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill"
                    data-att-status="archive" aria-pressed="false">Archived</button>
        </li>
    </ul>

    <button type="button" class="btn attendance-download-btn border-0">
        <i class="bi bi-download" aria-hidden="true"></i>
        <span>Download</span>
    </button>
</div>
```

`rounded-1` (4px), not pills — see the mandate in `sargam-app.css:15-20`.

If the page exports more than one format, make Download a dropdown instead
(`memo_discipline/index.blade.php:202-210` has the Excel + PDF version).

---

## 2. Toolbar

`attendance/index.blade.php:252-365`, structure only:

```html
<div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4
            programme-dt-toolbar">

    <!-- LEFT: label + filters + reset -->
    <div class="d-flex flex-wrap align-items-center gap-3">
        <span class="programme-dt-filters-label">Filters</span>

        <div class="programme-dt-filter-select">
            <select name="course_master_pk" class="form-select">
                <option value="">Course Name</option>
                …
            </select>
        </div>

        <div class="programme-dt-filter-select">
            <select class="form-select" aria-label="Attendance Type">…</select>
        </div>

        <button type="button" class="btn programme-dt-btn-reset">Reset Filters</button>
    </div>

    <!-- RIGHT: columns + search -->
    <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
        <button type="button" class="btn programme-dt-btn-columns"
                data-bs-toggle="modal" data-bs-target="#…ColumnVisibilityModal"
                title="Show / hide columns">
            <span>Columns</span>
            <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
        </button>

        <div id="attendanceDtSearch" class="programme-dt-search" data-dt-search-for="attendanceTable"></div>
    </div>
</div>
```

Key classes (`custom.css`):

| Class | Line | What it gives you |
|---|---|---|
| `.programme-dt-toolbar` | 132 | `min-height:40px` only — layout comes from the Bootstrap utilities on the same element |
| `.programme-dt-filters-label` | 136 | the grey "Filters" word |
| `.programme-dt-filter-select` | 143 | 180px wrapper; also themes Choices.js inside it |
| `.programme-dt-btn-reset` | 172 | the **red** reset — `#912018` on a `#fda29b` border |
| `.programme-dt-btn-columns` | 189 | grey outline, inverts to solid `#747475` on hover |
| `.programme-dt-search` | 271 | 300px slot with the search glyph injected via `::before` |

### Filter overflow

More than ~4 filters gets crowded. Attendance moves the tail into a `+N Filters`
dropdown (`attendance/index.blade.php`, `#attendanceMoreFiltersWrap`) rather than
wrapping to a second row.

### Two search variants

- **Slot (preferred).** Leave `.programme-dt-search` empty with
  `data-dt-search-for="<tableId>"`; `datatable-global-ui.js` moves DataTables'
  own `.dataTables_filter` into it. No markup, no JS on your side.
- **Toggle.** An icon button reveals a `d-none` input — used where the search is
  server-side and not a DataTables filter (`#discSearchToggle` +
  `.disc-search-wrap`, `memo_discipline/index.blade.php:280-289`).

### Reset Filters is a `<button>`, not a link

`attendance:351` and `programme/index.blade.php:55` both use
`<button class="btn programme-dt-btn-reset">`. The discipline page's `<a class="disc-reset">`
is a **different** component with a different red (`#f04438`) — don't copy it into
a `programme-dt` page.

---

## 3. Table panel

```html
<div class="programme-dt-panel">
    <div class="table-responsive">
        <table id="attendanceTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table">
            <thead><tr><th>S. No.</th>…<th>Action</th></tr></thead>
            <tbody>…</tbody>
        </table>
    </div>
</div>
```

Yajra pages pass the same class list server-side:

```blade
{!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table']) !!}
```

`.programme-dt-table` (custom.css:359) gives `#f2f4f7` headers, 16px cell
padding, a 3%-primary hover row, a muted first column (S. No.) and a wrapping,
420px-max second column.

> **Never hand-roll `dom`/`colVis` options on a Yajra table** — it breaks the
> init. Use the global UI script plus the column-visibility modal.

---

## 3b. Status column + row actions (the `country/index` reference)

The settled layout splits status **display** from the **control**:

- **Status column** = a **soft badge** only (state *display*; `data-order` lets DataTables
  sort by state).
- **Action column** = **Edit** (icon + label) · the **toggle switch** (the inline
  active/inactive *control*) · **Delete** (icon + label). The switch stays in the action
  group — never drop it for a pill-only display; that removes the inline-toggle feature.

```html
{{-- Status column: soft badge (green Active / red Inactive) --}}
<td data-order="{{ $row->active_inactive }}">
    <span class="status-pill badge {{ $row->active_inactive == 1 ? 'bg-success-subtle' : 'bg-danger-subtle' }}">
        {{ $row->active_inactive == 1 ? 'Active' : 'Inactive' }}
    </span>
</td>

{{-- Action column: Edit · toggle · Delete --}}
<td>
    <div class="d-inline-flex align-items-center justify-content-center gap-3" role="group" aria-label="Row actions">
        {{-- Edit opens the UX4G modal — see §3c --}}
        <button type="button" class="country-act country-act--edit"
                data-bs-toggle="modal" data-bs-target="#<page>FormModal" data-mode="edit"
                data-id="{{ $row->pk }}" data-name="{{ $row->name }}" data-status="{{ $row->active_inactive }}">
            <i class="bi bi-pencil-square" aria-hidden="true"></i><span>Edit</span>
        </button>

        <div class="form-check form-switch m-0">
            <input type="checkbox" class="form-check-input status-toggle" role="switch"
                   data-table="<table>" data-column="active_inactive"
                   data-id="{{ $row->pk }}" {{ $row->active_inactive == 1 ? 'checked' : '' }}>
        </div>

        {{-- Delete: guarded — a disabled <span> when active, a DELETE <form> when inactive --}}
    </div>
</td>
```

- **Toggle wiring is automatic.** `status-toggle-delete.js` binds `.status-toggle` change
  globally (AJAX + syncs the row's Delete control) and adds `aria-label="Toggle active status"`
  on load and each `draw.dt` (a bare switch has no accessible name). You write **no** toggle
  JS. On success, **reload the page** (§ ajax.reload trap below) so the Status-column badge
  and the active-guard repaint from fresh data — the switch and badge are in different
  columns, so don't hand-mirror them.
- **Soft badge.** Active = `bg-success-subtle`, Inactive = `bg-danger-subtle`. ⚠️ The theme
  ships the `*-subtle` **backgrounds** but not the `text-*-emphasis` colours (label renders
  black) — set the tinted text in the page's scoped `<style>`:
  ```css
  .<page> .status-pill.bg-success-subtle { color: #146c43; }  /* green */
  .<page> .status-pill.bg-danger-subtle  { color: #b02a37; }  /* red   */
  ```
- **Row actions = icon over label.** Edit (`bi-pencil-square` + "Edit", blue `#2563eb`) ·
  Delete (`bi-trash3` + "Delete", `--bs-danger`; muted + disabled when the active-guard
  forbids deletion).
- **Alternative placement.** A small grid may keep the switch inside `.programme-action-group`
  as a `.programme-action-switch` (like `building_floor_room_mapping`) with no separate Status
  column — but the `country/index` split above is the reference.

### ⚠️ Client-side DataTable + status toggle — the `ajax.reload()` trap

On a **success**, shared `custom.js` runs `$('.dataTable').DataTable().ajax.reload()` (it
assumes every grid is server-side). On a **client-side** DataTable (no `ajax` source) this
logs DataTables' **"Invalid JSON response"**. Do **not** patch `custom.js` — instead, in the
page's DataTable init:

```js
$.fn.dataTable.ext.errMode = 'none';                 // silence the stray reload (only this
                                                     // table inits on this page)
$(document).ajaxSuccess(function (e, xhr, s) {        // on a confirmed+successful toggle,
    if (s && s.url && /toggle-?status/i.test(s.url))  // reload for correct fresh state
        window.location.reload();                     // (new active-guard + badge)
});
```

The toggle itself shows a **SweetAlert confirm** ("Yes, deactivate") before the AJAX — do
**not** optimistically flip the badge on `change`, or a cancel leaves it wrong; let the
reload (on real success) repaint the row. Server-side/Yajra grids don't need any of this.

---

## 3c. Create / Edit in a UX4G modal

Create and Edit open a **UX4G (Bootstrap 5.3) modal** — **no separate page navigation**.
One modal serves both modes; the form submits to the **unchanged** store (POST) / update
(PUT) routes (no controller/route change). Reference: `country/index`.

- **Triggers.** The Add button and each row's Edit are `data-bs-toggle="modal"
  data-bs-target="#<page>FormModal"` with `data-mode="create|edit"` (Edit also carries
  `data-id` / `data-name` / `data-status`). Bootstrap hands the trigger to `show.bs.modal`
  as `event.relatedTarget`.
- **Mode switch** in the `show.bs.modal` handler: set the form `action` (store vs
  `/update/{id}`), the spoofed `_method` (POST vs PUT), the field **name**
  (`country_name[]` for create — store expects an array — vs `country_name` for edit), and
  prefill name/status for edit.
- **⚠️ Status value mismatch.** The inline toggle stores inactive as `0`, but the create/edit
  `<select>` uses `1`/`2` — map `data-status === '1' ? '1' : '2'` or the select comes up blank.
- **Validation errors.** The controller redirects back to the index with `$errors` + old
  input. Render the errors inside the modal, carry `_form_mode` / `_edit_id` as hidden fields,
  and on load reopen with `bootstrap.Modal.getOrCreateInstance(el).show(triggerEl)`.
- **It IS the UX4G modal** — `.modal .modal-dialog-centered > .modal-content` with
  `.modal-header/.modal-body/.modal-footer`, `.btn-close`, standard form controls.
- **Cascading FK selects (parent → child) — do it client-side, no AJAX.** For pages whose
  create/edit form has dependent dropdowns (district = Country→State; city = Country→State→
  District), the index controller already passes the parent lookups (`$countries`, `$states`,
  `$districts`). Embed the *child* lists as JSON and filter in the browser:
  ```blade
  var STATES = @json($states->map(fn($s)=>['pk'=>(string)$s->pk,'name'=>$s->state_name,'country'=>(string)$s->country_master_pk])->values());
  function fillStates(countryId, selectId){ /* rebuild #state <option>s where st.country===countryId; then .val(selectId) */ }
  $('#country').on('change', function(){ fillStates(this.value, null); });
  ```
  On `show.bs.modal` **edit**, prefill top-down: set country → `fillStates(country, state)` →
  `fillDistricts(state, district)`. This makes edit prefill **synchronous** (no async race)
  and needs **no new routes** — the existing `get-states`/`get-districts` AJAX endpoints stay
  untouched. (Reference: `district/index`, `city/index`.) Only reach for AJAX if a child list
  is too large to embed; the master lookups here are small (≤~850 rows).

---

## 4. Footer

Two variants, visually identical.

### A — DataTables (JS fills it)

Leave an empty div; `datatable-global-ui.js` populates it.

```html
<div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
     data-dt-footer-for="attendanceTable"></div>
```

### B — Laravel paginator (hand-written)

For server-side paginated pages that aren't DataTables-driven. Reuses the
`.dataTables_length` / `.dataTables_info` class names so the same CSS applies
(`memo_discipline/index.blade.php:658-679`):

```blade
<div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3">
    <div class="programme-dt-pagination">
        {{ $memos->links('vendor.pagination.custom') }}
    </div>
    <div class="programme-dt-count d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
        <div class="dataTables_length">
            <label class="mb-0">Showing
                <select id="discPerPage" class="form-select form-select-sm" aria-label="Rows per page">
                    @foreach(['10','25','50','100','200','all'] as $pp)
                    <option value="{{ $pp }}" {{ $discPerPage === $pp ? 'selected' : '' }}>{{ $pp === 'all' ? 'All' : $pp }}</option>
                    @endforeach
                </select>
            </label>
        </div>
        <div class="dataTables_info">of {{ number_format($memos->total()) }} items</div>
    </div>
</div>
```

`vendor/pagination/custom.blade.php` renders `‹` / `›` — the same glyphs the JS
uses for its pager, which is why the two variants match.

---

## 4b. Branded exports — CSV / PDF / Print

Download (CSV + PDF) and Print produce the **LBSNAA branded document** — logos + Hindi/English
academy name + course/batch line + blue report title + blue table header — matching every
other report. **Reuse the shared view**, don't rebuild the header.

- **Shared view:** `resources/views/exports/lbsnaa-report.blade.php`. Generic — pass
  `$reportTitle`, `$headings` (string[]), `$rows` (array of cell arrays), `$subtitle` /
  `$subtitle2` (course + batch lines), the logo data-URIs, and `$autoPrint` (Print only). A
  `Status` heading auto-renders green/red pills.
- **Controller** (see `LocationController::countryExport($format)` + `exportAssets()`):
  build `$headings`/`$rows`, resolve logos, then branch —
  ```php
  if ($format === 'csv')   return response()->streamDownload(fn()=>{ /* UTF-8 BOM, then Hindi + English academy + course + batch + title text rows, blank, headings + rows via fputcsv */ }, $file, ['Content-Type'=>'text/csv; charset=UTF-8']);
  if ($format === 'print') return view('exports.lbsnaa-report', $data + ['autoPrint'=>true]);   // HTML + window.print()
  return Pdf::loadView('exports.lbsnaa-report', $data)->setPaper('a4','landscape')->download($file);   // DomPDF
  ```
- **Logos** (data-URIs via `exportAssets()`): left `logo_new.png`, right `constitution-75.png`
  → falls back to `Azadi-Ka-Amrit-Mahotsav-Logo.png`, Hindi title `lbsnaa-title-hi.png`. Brand
  blue is `#004384` (title + table header). CSV can't carry logos/colour — it gets the **same
  header lines as the PDF/Print**, in order, as leading text rows: **Hindi academy name** →
  English academy name → course → batch → report title → blank → column headings → data.
  Write a **UTF-8 BOM** (`fwrite($out, "\xEF\xBB\xBF")`) first so Excel renders the Devanagari
  (and any non-ASCII data) correctly instead of mojibake; pad each header line across all
  columns so it spans the sheet width like the centred design header.
- **Route:** `GET …/export/{format}` `->whereIn('format',['csv','pdf','print'])`.
- **Wire (above the card):** a **Download dropdown** (CSV · PDF) linking to the export route,
  plus a **Print** link (`target="_blank"` → the auto-printing HTML). No client-side CSV/JS.
- **Read-only** — the export controller never touches create/update logic.
- **Share one helper across a controller's lists.** `LocationController::brandedExport($format,
  $reportTitle, $headings, $rows, $filenameBase)` holds the CSV/PDF/Print branching; each
  `xxxExport()` just builds `$rows` and delegates. Country/State/District/City all route through it.
- **⚠️ Large lists + DomPDF.** DomPDF is memory/CPU-hungry — City (~1,664 rows) peaks ~700 MB
  and ~60 s. Guard the PDF branch the way the app already does elsewhere (Calendar/Feedback
  controllers): `@ini_set('memory_limit','1024M'); @set_time_limit(300);` before `Pdf::loadView`.
  CSV stays instant regardless. For very large tables prefer a Yajra server-side grid (see §5)
  and consider whether a full-list PDF is even wanted.

---

## 5. `datatable-global-ui.js` — the contract

627 lines, loaded after the DataTables CDN scripts. What it does for you:

**Global defaults.** `pageLength: 10`, `lengthMenu` 10/25/50/100/200,
`pagingType: 'full_numbers'`, `autoWidth: false`, and the language strings that
produce **"Showing [10] of 243 items"** (`lengthMenu: 'Showing _MENU_'`,
`info: 'of _TOTAL_ items'`).

> **Pagination style — set `pagingType: 'simple_numbers'` per table.** The reference
> pager is **‹ 1 2 3 … ›** (prev + numbers + next, **no First / Last**). The global default
> is `full_numbers` (which adds First/Last), but it is applied only when a table doesn't
> set its own (`if (!settings.oInit.pagingType)`), so passing `pagingType: 'simple_numbers'`
> in your `.DataTable({…})` init wins — with no change to the shared default. Do this on
> every migrated grid.

**Chrome relocation.** Its `dom` renders `f`/`i`/`l`/`p` into a hidden row, then
`enhance()` moves the filter into your `.programme-dt-search` slot and rebuilds
the footer as `.programme-dt-pagination` + `.programme-dt-count`.

**Slot resolution**, in precedence order:

1. `data-dt-search` / `data-dt-footer` on the `<table>` (a selector string)
2. `[data-dt-search-for="<tableId>"]` / `[data-dt-footer-for="<tableId>"]`
3. the first `.programme-dt-search` / `.programme-dt-footer` inside the nearest
   `.programme-dt-panel`, `.card-body`, `.datatables`, or a `*-dt-card` scope
4. otherwise it creates one

**Sortable headers.** It monkey-patches `$.fn.DataTable` so every init site gets
normalised sorting, and gives server-side tables a client-side sort of the
currently loaded page — reusing `.sorting_asc` / `.sorting_desc` so the arrow
styling in `sargam-app.css:391-448` applies. This runs even when the UI
enhancement is opted out of.

### Opting out

```html
<table data-sargam-dt-ui="false">        <!-- also honoured on any ancestor -->
<table class="dt-legacy-layout">
```

Opt out when the page does its **own** server-side pagination with a
hand-written `programme-dt-footer` — otherwise the enhancer hijacks and empties
it. That's exactly why `memo_discipline/index.blade.php:503` carries the flag.
`data-sargam-dt-ui="true"` on the table itself wins over an ancestor opt-out.

Public API: `window.SargamDataTableUI = { enhance, updateCount, shouldEnhance, DEFAULT_DOM, DEFAULT_LANGUAGE }`.

---

## 6. Shortcut — the Blade component

`resources/views/components/datatable-chrome.blade.php` packages toolbar +
panel + footer. Props: `tableId`, `showSearch`, `toolbar` slot.

```blade
<x-datatable-chrome table-id="myTable">
    <x-slot:toolbar>
        <span class="programme-dt-filters-label">Filters</span>
        <div class="programme-dt-filter-select"><select class="form-select">…</select></div>
        <button type="button" class="btn programme-dt-btn-reset">Reset Filters</button>
    </x-slot:toolbar>

    {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table']) !!}
</x-datatable-chrome>
```

---

## 7. Naming conventions

Page CSS is namespaced under a page-root class (`.disc-page .disc-tab`,
`.attendance-page .attendance-download-btn`) so it can't leak. Only
`sargam-app.css` does the opposite, deliberately, to reach all ~354 admin views.

| Prefix | Module |
|---|---|
| `--ds-*` / `.ds-*` | global design system (`sargam-app.css`) |
| `programme-dt-*`, `programme-status-*`, `programme-action-*` | shared DataTables chrome (`custom.css`) |
| `sn-*` / `mnm-*` / `disc-*` | Send Direct Notice / Send Memo-Notice / Send Discipline Memo — all three in `notice-memo-discipline.css` |
| `gm-*` | Group Mapping |
| `sm-*` | Subject Master / Module |
| `attendance-*` | Attendance |

`public/css` is flat, named `<module>-<audience>.css`
(`course-repository-admin.css`, `roles-admin.css`, …).

---

## 8. Checklist for a new index page

1. `<x-breadcrum>` heading, with the primary action button in its slot.
2. Status pills / **Download (CSV·PDF) + Print** row **above** the card — branded exports via
   the shared LBSNAA report view (§4b).
3. Toolbar: `Filters` label → filter selects → red `Reset Filters` on the left;
   `Columns` + search slot on the right (`ms-lg-auto`).
4. `.programme-dt-panel` > `.table-responsive` > `table.programme-dt-table`.
5. Footer — empty div if DataTables paginates, hand-written variant B if Laravel does.
6. If Laravel paginates: add `data-sargam-dt-ui="false"`.
7. **Status column:** if the list toggles active/inactive inline, keep the
   `.status-toggle` switch (§3b) — never a pill-only display.
8. **Pagination:** DataTable init sets `pagingType: 'simple_numbers'` (‹ 1 2 3 › — no First/Last, §5).
9. **Create / Edit** open in a **UX4G modal** (§3c), submitting to the unchanged store/update routes.
10. Column visibility → [column-visibility.md](column-visibility.md).
11. Page CSS namespaced under a page-root class, tokens from
   [design.md](design.md), `?v={{ @filemtime(...) }}` on the link tag.

---

## 9. Known inconsistencies

Real traps, not nitpicks:

- **Pill pagination.** `admin/layouts/pre_header.blade.php:58-187` has an inline
  `<style>` forcing `.page-link { border-radius: 999px !important }` with a blue
  gradient active state. It contradicts both this pattern and the "no rounded-pill"
  mandate. `.programme-dt-footer` selectors are more specific and win *inside the
  footer* — but any un-migrated DataTable on the page still renders pills.
- **Two reds for Reset Filters** — `#912018`/`#fda29b` (`programme-dt-btn-reset`)
  vs `#f04438` (`disc-reset`).
- **Two column-visibility modals** — the `sn-colvis-*` chip grid and the
  `colvis-item` card grid. The latter is styled by a hard-coded **ID list** at
  `custom.css:238-269`, so a new page must be added there.
- **Two token sets** — `--ds-*` (`sargam-app.css`) and `--gigw-*`
  (`notice-memo-discipline.css:14-23`). Prefer `--ds-*` in new work.
- **Adoption is partial.** ~41 views use `programme-dt-*`; ~15 more use
  `vendor.pagination.custom` *without* the footer, i.e. half-migrated.
