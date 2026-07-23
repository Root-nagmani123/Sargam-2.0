# The "New Design" — admin index pages

When a ticket says *"apply the new design"* to an admin listing screen, it means
**this page chrome**: the `programme-dt` toolbar, table panel and footer, as
built on the Attendance page.

**Canonical reference:** `resources/views/admin/attendance/index.blade.php`.
It is not the `employee_idcard` layout and not the old DataTables default chrome.

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
├── <x-breadcrum title="…">            ← page heading (+ primary action button)
├── status pills  ····  Download        ← OUTSIDE / ABOVE the card
└── card > card-body
    ├── toolbar   (filters left · columns + search right)
    ├── programme-dt-panel
    │   └── table-responsive > table.programme-dt-table
    └── programme-dt-footer            (pagination left · "Showing N of M items" right)
```

The status pills and Download button sit **above the card**, not inside it.

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

## 5. `datatable-global-ui.js` — the contract

627 lines, loaded after the DataTables CDN scripts. What it does for you:

**Global defaults.** `pageLength: 10`, `lengthMenu` 10/25/50/100/200,
`pagingType: 'full_numbers'`, `autoWidth: false`, and the language strings that
produce **"Showing [10] of 243 items"** (`lengthMenu: 'Showing _MENU_'`,
`info: 'of _TOTAL_ items'`).

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
2. Status pills + Download row **above** the card.
3. Toolbar: `Filters` label → filter selects → red `Reset Filters` on the left;
   `Columns` + search slot on the right (`ms-lg-auto`).
4. `.programme-dt-panel` > `.table-responsive` > `table.programme-dt-table`.
5. Footer — empty div if DataTables paginates, hand-written variant B if Laravel does.
6. If Laravel paginates: add `data-sargam-dt-ui="false"`.
7. Column visibility → [column-visibility.md](column-visibility.md).
8. Page CSS namespaced under a page-root class, tokens from
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
