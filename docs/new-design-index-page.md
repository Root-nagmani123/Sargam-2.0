# The "New Design" — admin index pages

When a ticket says *"apply the new design"* to an admin listing screen, it means
**this page chrome**: the `programme-dt` toolbar, table panel and footer, as
built on the Attendance page.

**Canonical reference:** `resources/views/admin/attendance/index.blade.php`.
It is not the `employee_idcard` layout and not the old DataTables default chrome.

**Second reference —** `resources/views/admin/issue_management/categories/index.blade.php`
for everything Attendance doesn't show: the status badge + row-action stack
(§3b), the matching Add/Edit modals (§3c), the Laravel-paginated footer with its
own sort/search/per-page wiring (§4B), and the Download + Print export pair (§1).

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
    │   └── table-responsive > table.programme-dt-table   ← Status column + row actions §3b
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

### No status pills? Right-align the export row

A grid with nothing to filter by status still keeps the row — just drop the `<ul>`
and let the buttons sit alone on the right. Reuse `.programme-dt-btn-columns` for
them so they match the toolbar below
(`issue_management/categories/index.blade.php:171-180`):

```blade
<div class="d-flex flex-wrap justify-content-end gap-2 mb-3 ic-secondary-actions">
    <a href="{{ route('…export', ['format' => 'csv']) }}"
       class="btn programme-dt-btn-columns border-0 text-primary" title="Download as CSV">
        <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
    </a>
    <a href="{{ route('…export', ['format' => 'print']) }}" target="_blank" rel="noopener"
       class="btn programme-dt-btn-columns border-0 text-primary" title="Print">
        <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
    </a>
</div>
```

**Print is a server-rendered view, not `window.print()`.** One controller action
serves both formats off the same query so the CSV and the printout can't drift
apart (`IssueCategoryController@export`, `categories/export_print.blade.php` — a
`<body onload="window.print()">` page branded like the module's `export_pdf`).
Carry the grid's own `q` / `sort` / `dir` into the export links so the user gets
what they are looking at, not the unfiltered table.

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

  The revealed input can just be a **GET `<form>`** whose submit reloads the page
  — no JS beyond the reveal. Keep the other grid state (`per_page`, `sort`, `dir`)
  as `<input type="hidden">` inside it or the search will reset them
  (`#icSearchToggle` + `#icSearchWrap`, `categories/index.blade.php:195-209`).
  Start it un-hidden when a search is active: `class="… {{ filled($search) ? '' : 'd-none' }}"`.

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

## 3b. Status column + row actions

Reference: `issue_management/categories/index.blade.php` (blade rows) — Yajra
pages build the identical markup inside `addColumn('status'|'action', …)`.

The layout splits status **display** from the **control**:

- **Status column** = a soft badge, display only. `data-order` lets a client-side
  sort order by state.
- **Action column** = **Edit** · the **status switch** · **Delete**, each an
  icon over a caption. The switch stays in the action group — never drop it for a
  badge-only display, that removes the inline-toggle feature.

```blade
{{-- Status: soft badge --}}
<td data-order="{{ (int) $row->status }}">
    <span class="status-pill badge rounded-1 {{ $isActive ? 'bg-success-subtle' : 'bg-danger-subtle' }}">
        {{ $isActive ? 'Active' : 'Inactive' }}
    </span>
</td>

{{-- Action: three identical stacks --}}
<td>
    <div class="ic-act-group" role="group" aria-label="Row actions">
        <button type="button" class="ic-act ic-act--edit ic-edit-btn" data-id="…" data-name="…" data-status="…">
            <span class="ic-act__icon"><i class="bi bi-pencil-square" aria-hidden="true"></i></span>
            <span class="ic-act__label">Edit</span>
        </button>

        <label class="ic-act ic-act--toggle">
            <span class="ic-act__icon">
                <input class="form-check-input status-toggle" type="checkbox" role="switch"
                       data-table="<table>" data-column="<col>" data-id="{{ $row->pk }}" @checked($isActive)>
            </span>
            <span class="ic-act__label">{{ $isActive ? 'Deactivate' : 'Activate' }}</span>
        </label>

        {{-- Delete: guarded — a disabled <span> when the server would refuse it,
             a DELETE <form> otherwise --}}
    </div>
</td>
```

- **Soft badge.** Active = `bg-success-subtle`, Inactive = `bg-danger-subtle`.
  ⚠️ The theme ships the `*-subtle` **backgrounds** but not the
  `text-*-emphasis` colours, so the label renders black. Tint it in the page's
  scoped `<style>`:
  ```css
  .<page> .status-pill.bg-success-subtle { color: #146c43; }
  .<page> .status-pill.bg-danger-subtle  { color: #b02a37; }
  ```
- **Toggle wiring is automatic.** `custom.js:170` binds `.status-toggle` change
  globally (SweetAlert confirm → AJAX to `routes.toggleStatus`). You write **no**
  toggle JS. The badge and the switch live in different columns, so on success
  **reload the page** rather than hand-mirroring them:
  ```js
  $(document).ajaxSuccess(function (e, xhr, s) {
      if (s.url.indexOf('toggle-status') === -1 && s.url.indexOf('toggleStatus') === -1) return;
      setTimeout(function () { window.location.reload(); }, 600);
  });
  ```
- **Guard Delete against the server's own rule.** If `destroy()` refuses (e.g.
  "can't delete an active row"), render a muted disabled `<span>` with a `title`
  saying why, and the real DELETE `<form>` only in the allowed state. A red icon
  that always fails is worse than a greyed one.
- **The switch caption names the ACTION, not the state** — an Active row reads
  "Deactivate". The state is already shown by the badge one column over, so
  repeating it there is redundant *and* reads as a contradiction.
  ⚠️ `categories/index.blade.php:284` currently ships the inverse
  (`$isActive ? 'Activate' : 'Deactivate'`) — that line is the exception, not the
  pattern. Copy the rule above, not that line.

### The two alignment traps

Both cost real debugging time. Both are in the CSS you inherit.

**1. Never wrap the switch in `.form-check.form-switch` here.**
`custom.css:107-112` pulls `.form-check-input` left by `-2.375rem` whenever it
sits inside `.form-check.form-switch:has(.status-toggle)` — correct for the
switch-*beside*-label layout, wrong for switch-*above*-caption. At 4 classes it
out-specifies a 3-class page reset, so the wrapper collapses to **0px wide** and
the switch renders ~38px left of its caption. Drop the wrapper entirely: the
skin is keyed on `.form-check-input.status-toggle[type="checkbox"]`
(`custom.css:41`) and still applies without any `.form-check` ancestor.

**2. Give every action the same width.** Otherwise each column is sized by its
caption ("Edit" 21px vs "Deactivate" 58px) and the icon row comes out unevenly
spaced. One fixed-height icon strip keeps glyphs and the switch on one baseline:

```css
.<page> .ic-act-group { display: inline-flex; align-items: stretch; gap: 0.25rem; }

.<page> .ic-act {
    display: inline-flex; flex-direction: column;
    align-items: center; justify-content: flex-start; gap: 4px;
    min-width: 62px;                 /* ≈ the widest caption */
    font-size: 0.72rem; font-weight: 500; line-height: 1;
    background: transparent; border: 0; padding: 0; margin: 0; cursor: pointer;
}

.<page> .ic-act__icon {              /* one strip for glyphs AND the switch */
    display: flex; align-items: center; justify-content: center; height: 22px;
}
.<page> .ic-act__icon > i { font-size: 1.1rem; line-height: 1; }
.<page> .ic-act__icon .form-check-input { margin: 0; float: none; }
.<page> .ic-act__label { white-space: nowrap; }
```

Colours: Edit `#2563eb`, Delete `var(--bs-danger)`, disabled Delete `#98a2b3` at
`opacity .65`, toggle caption `#475467`. A wrapping `<form>` around Delete needs
`display: flex; margin: 0; padding: 0` so it adds no box of its own.

Verify by measuring, not by eye — every `.ic-act` should report the same width
and every `.ic-act__icon` the same `top`/`height`.

---

## 3c. Create / Edit modals

Create and Edit open **modals** — no page navigation — and they look **alike**:
same header, same tinted field card, same labels/controls, same footer pair.
Only the contents and the submit caption differ. Reference:
`categories/index.blade.php` (`#addCategoryModal` / `#editCategoryModal`).

```
modal-content (radius 12)
├── .ic-modal-header      title left · btn-close right · 1px #eaecf0 rule under
├── .ic-modal-body
│   └── .ic-field-card    #eef1fc, radius 10, padding 1rem   ← one per record
│       ├── .ic-form-label + .ic-req ("*")  → .form-control.ic-control
│       └── .ic-field-actions               → − / +   (Add only)
└── .ic-modal-footer      centred · .ic-btn-cancel (red outline) · .ic-btn-submit (#004384)
```

Tokens: card `#eef1fc`; label `.8125rem/600 #1f2937`; asterisk `#dc2626`;
control white on `#e5e7eb`, radius 8; remove `#ef4444`, add `#2563eb`, both
30×30 radius 7; Cancel `#dc2626` text on `#fca5a5`; submit brand `#004384`.
Scope the control rules to the two modal IDs — they must beat `.form-control`
without `!important`.

### Repeatable field cards (Add)

Where create accepts several records at once, each is one `.ic-field-card` and
the whole state is derived from the DOM after every change — **never** by nudging
the previous/next card:

```js
function syncFieldCards() {
    var $groups = $('#categoryFieldsContainer .category-field-group');
    var last = $groups.length - 1;
    $groups.each(function (index) {
        $(this).attr('data-index', index);
        $(this).find('.complaint-field').attr('name', 'categories[' + index + '][issue_category]');
        $(this).find('.description-field').attr('name', 'categories[' + index + '][description]');
        $(this).find('.remove-field-btn').toggle($groups.length > 1);  // − once >1 card
        $(this).find('.add-field-btn').toggle(index === last);         // + on the last only
    });
}
```

Call it after add, after remove, on `hidden.bs.modal`, and once on load. Clone
the **first** card as the template and blank its values — deriving visibility
afterwards is what stops a clone inheriting the template's hidden state (the bug
in §9's `.d-flex` note had exactly this shape).

### ⚠️ `required` + an optional extra card = dead end

If the user adds a card and leaves it empty, native validation blocks submit and
your handler — the one that would have dropped the blank card — never runs. The
field is invalid, invisible-ish, and the form just refuses to submit.

Put **`novalidate` on the create form** and let the submit handler own it:
count a card as filled if either field has a value, error only on half-filled
cards, and `prop('disabled', true)` the fully-blank ones so they are not posted.
Disable **`input, textarea`** — a `find('input')` alone silently posts empty
textareas and the controller writes blank rows.

Keep the `required` attributes for semantics/a11y; `novalidate` only stops the
browser from enforcing them.

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

The rows-per-page `<select>` is yours to wire — one handler, no DataTables
involved (`categories/index.blade.php:480-485`):

```js
$('#icPerPage').on('change', function () {
    var url = new URL(window.location.href);
    url.searchParams.set('per_page', this.value);
    url.searchParams.set('page', '1');       // page 1, or the user lands past the end
    window.location.href = url.toString();
});
```

Whitelist the value server-side (`in_array($perPage, self::PER_PAGE_OPTIONS, true)`)
and fold `per_page` / `q` / `sort` / `dir` into the **listing cache key** if the
controller memoises its page snapshot — otherwise every variant serves page 1's rows.

### Sortable headers without DataTables

Variant B has no client-side sorter, so a header caret must be a real link. Keep
a whitelist in the controller (`SORTABLE_COLUMNS` → column name or a `withCount`
alias) and flip direction on the active column:

```php
$sortUrl = fn (string $key) => request()->fullUrlWithQuery(array_merge($baseQuery, [
    'sort' => $key,
    'dir'  => ($sortKey === $key && $sortDir === 'asc') ? 'desc' : 'asc',
    'page' => 1,
]));
```

Only give a caret to columns you actually sort — a running "S. No." isn't one.

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
| `ic-*` | Centcom → Manage Categories (`issue_management/categories`) |

Within a page prefix, the row-action stack uses BEM-ish element names —
`.ic-act` / `.ic-act__icon` / `.ic-act__label`, modifiers `--edit` / `--toggle`
/ `--del` (§3b). Keep those element/modifier names when you port the pattern;
only the prefix changes.

`public/css` is flat, named `<module>-<audience>.css`
(`course-repository-admin.css`, `roles-admin.css`, …).

---

## 8. Checklist for a new index page

1. `<x-breadcrum>` heading, with the primary action button in its slot.
2. Status pills + Download row **above** the card (right-aligned exports alone if
   there are no pills — §1).
3. Toolbar: `Filters` label → filter selects → red `Reset Filters` on the left;
   `Columns` + search slot on the right (`ms-lg-auto`).
4. `.programme-dt-panel` > `.table-responsive` > `table.programme-dt-table`.
5. Status column = soft badge; Action column = Edit · switch · Delete as
   equal-width icon-over-label stacks (§3b). No `.form-check`/`.form-switch`
   wrapper around the switch.
6. Create + Edit in modals that look alike — shared header / field card / footer
   (§3c). `novalidate` on any create form with repeatable cards.
7. Footer — empty div if DataTables paginates, hand-written variant B if Laravel does.
8. If Laravel paginates: add `data-sargam-dt-ui="false"`, and wire `per_page` /
   `sort` / `dir` yourself (§4). Whitelist all three server-side and include them
   in the listing cache key.
9. Column visibility → [column-visibility.md](column-visibility.md). Add the new
   grid's ID to the `colvis-item` list in `custom.css:238-272` (§9).
10. Page CSS namespaced under a page-root class, tokens from
    [design.md](design.md), `?v={{ @filemtime(...) }}` on the link tag.
11. Use only **one** of `@push('scripts')` / `@section('scripts')` — the master
    layout renders both, so using both double-renders your script.

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
  `custom.css:238-272`, so a new page must be added there (three separate
  selector lists: base, `:hover`, `.form-check-input`).
- **`.d-flex` beats `jQuery.hide()`.** Bootstrap's display utilities are
  `!important`, so `.hide()` — which writes inline `display:none` — silently does
  nothing on an element carrying `d-flex`. This shipped as a live bug in the
  Manage Categories add-modal: the repeat-row `+`/`−` buttons were always both
  visible. If JS toggles an element's visibility, give it a plain
  (non-`!important`) `display` from your own scoped class, not `d-flex`. Cloning
  compounds it — a clone of a hidden template inherits the hidden state, so
  `.show()` the controls that must be live on the copy.
- **The status switch carries layout baggage.** `.form-check.form-switch:has(.status-toggle)`
  yanks the input `-2.375rem` left (`custom.css:107-112`). Fine beside a label,
  broken above a caption — see §3b.
- **Two token sets** — `--ds-*` (`sargam-app.css`) and `--gigw-*`
  (`notice-memo-discipline.css:14-23`). Prefer `--ds-*` in new work.
- **Adoption is partial.** ~41 views use `programme-dt-*`; ~15 more use
  `vendor.pagination.custom` *without* the footer, i.e. half-migrated.
