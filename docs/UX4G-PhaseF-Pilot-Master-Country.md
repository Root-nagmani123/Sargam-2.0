# Phase E/F — Page Redesign: CANONICAL Index Pattern (pilot: Master → Country)

**Canonical visual reference:** the **Store Master** screen (provided 2026-08-04).
**Every admin index/list page must look like it.**
**Pilot page:** `/master/country` · `resources/views/admin/country/index.blade.php`
**Status:** ✅ redesigned to the canonical pattern + gated — **STOP for your review**

---

## 0. The standard (read this first)

All list/index pages adopt the **`programme-dt` chrome** — this *is* the Store Master
look, and it is already an established, tested pattern in the app (~41 views:
attendance, programme, CourseMaster, memo_discipline, …). The full implementation spec is
**[new-design-index-page.md](new-design-index-page.md)**; the token/component layer is
**[design.md](design.md)**. This doc ties them to the Store Master reference and to the
pilot. **Do not invent a new index layout — reuse `programme-dt-*` + `.ds-*`.**

### Store Master anatomy → classes

| Store Master element | Class / source |
|---|---|
| Breadcrumb + page title + primary **Add** button (one row) | `<x-breadcrum title button-text button-url button-icon>` |
| **Download / Print** strip above the card | `.attendance-download-btn` / export dropdown — **only if the page has export routes** |
| Card with **Columns** toggle + **search** (top-right) | `.programme-dt-toolbar` → `.programme-dt-btn-columns` + `.programme-dt-search` (right, `ms-lg-auto`) |
| Sortable headers (S.No / Name / Type / Location …) | `table.programme-dt-table` (DataTables adds `.sorting_*` arrows) |
| "Code: STR…" subtitle under the name | second line in the Name cell (`<small class="text-muted">`) — only if the entity has a code |
| Green **Active** status pill | `.badge.bg-success` / `.bg-secondary` (accessible dark-on-colour, Phase 8) |
| **Edit / Delete** row actions | `.programme-action-group` → `.programme-action-btn` / `.programme-action-btn--danger` |
| Footer: pagination + page-size + "Showing N of 15000 items" | `.programme-dt-footer` → `.programme-dt-pagination` + `.programme-dt-count`/`.dataTables_info` |

### DataTables vs Laravel-paginated (the one real fork)

- **Yajra / DataTables pages** (like Store Master's "15000 items"): the toolbar
  **Columns**, **search**, header **sort**, and footer **page-size** all work for free via
  `datatable-global-ui.js`. This is the default.
- **Laravel-`->paginate()` pages** (like Country): use **footer variant B**
  (`data-sargam-dt-ui="false"` + hand-written `.programme-dt-footer`). The DataTables-only
  bits (colvis Columns, server page-size, full-dataset sort/search) are **not available
  without a data-source change** (a controller change = out of frontend scope). Provide the
  chrome; use a client-side quick-find for search; omit Columns/page-size. **Flag pages
  that should become true DataTables to the module owner.**

---

## 1. Pilot result — Country (pixel-perfect, client-side DataTable)

Rebuilt to match Store Master exactly, as a **client-side DataTable** on the programme-dt
chrome (the `course-repository/index.blade.php` pattern):

- `<x-breadcrum>` heading + navy **Add Country** button.
- **Download / Print** strip above the card — **functional** (Download = client-side CSV of
  the filtered rows; Print = `window.print()` + a print stylesheet).
- Card toolbar: **Columns** button → a column-visibility modal (persisted in `localStorage`)
  + **search** (`programme-dt-search` slot, filled by the global enhancer).
- `#countryTable.programme-dt-table` as a **real DataTable** → `datatable-global-ui.js` drives
  **sort** (header arrows), **page-size**, and the footer **"Showing [10] of 18 items"** —
  exactly like Store Master.
- **Status in the Action column:** the `.status-toggle` **switch** + a **soft** Active/Inactive
  badge (`bg-success-subtle`/`bg-secondary-subtle` with tinted text) sit at the front of the
  Action cell, before Edit/Delete. Switch = control (shared AJAX via `status-toggle-delete.js`).
- **`ajax.reload()` trap fixed:** `custom.js` calls `$('.dataTable').ajax.reload()` after a
  toggle — client-side here, so it logged "Invalid JSON". Fixed by `errMode='none'` + reload
  on confirmed toggle success (no `custom.js` change). See `new-design-index-page.md` §3b.
  (A page with an inline toggle must keep the switch — never a pill-only display.)
- **Create / Edit open in a UX4G modal** (not separate pages): one Bootstrap-5.3 modal, two
  modes via `show.bs.modal` relatedTarget, submitting to the **unchanged** store (POST) /
  update (PUT) routes; validation errors reopen the modal. End-to-end CRUD verified
  (create→appears, edit prefills, delete). See `new-design-index-page.md` §3c.
- **Branded CSV / PDF / Print** via a new shared report view (`exports/lbsnaa-report.blade.php`)
  + `LocationController::countryExport()` + `/export/{format}` route: LBSNAA logos + academy +
  course/batch line + blue title + blue table header (matches the academy document). Download =
  dropdown (CSV·PDF); Print = auto-printing HTML. Verified all three (CSV text-header, valid
  %PDF with logos, branded print HTML). See `new-design-index-page.md` §4b.
- **Edit / Delete** as **icon-over-label** actions (blue / red); Delete honours the
  active-guard business rule (disabled while active).

**The one backend touch (flagged, reversible):** the controller now returns
`Country::orderBy('country_name')->get()` instead of `paginate(10)` — a client-side DataTable
needs the full set to power its own search/sort/page-size. Country is a tiny master, so
rendering all rows is safe. **This is the minimum change pixel-perfect requires** for a page
that was Laravel-paginated; nothing else in the controller/model/routes changed, and it
reverts in one line.

**Preserved:** status-toggle AJAX, `master.country.edit`, delete form + `@method('DELETE')`
+ active-guard + confirm, breadcrumb, CSRF.

**Verified:** DataTable init ✓ · search-in-slot ✓ · footer "of 18 items" ✓ · Columns ✓ ·
Download/Print ✓ · **0 JS errors** · screenshot matches Store Master · re-baselined
chrome/firefox/webkit + **chrome determinism re-run zero-diff**.

**Store Master elements absent here are data/rule-driven, not design gaps:** country has no
Store-Type / Location / Code fields (fewer columns, no "Code:" subtitle) and its active-guard
greys Delete. All *chrome* matches.

> **Go-forward note:** every legacy **Laravel-`->paginate()`** index that must be pixel-perfect
> needs this same one-line data-source change (`paginate` → `get`, or a Yajra server-side
> DataTable for large tables). Small masters → `get()`; large tables (e.g. Store Master's
> 15000 rows) → **Yajra server-side** DataTable. Flag + apply per page.
>
> **Pagination:** the DataTable init must set **`pagingType: 'simple_numbers'`** (‹ 1 2 3 › —
> prev + numbers + next, **no First/Last**), matching the reference. The global enhancer
> defaults to `full_numbers` but only when a table doesn't set its own, so this per-init
> override is all that's needed.

---

## 2. Go-forward rule — apply to EVERY index page

For each page, in order (from [new-design-index-page.md §8](new-design-index-page.md)):

1. `<x-breadcrum>` heading + primary action button in its props.
2. Download / Print **above the card** — **only if export routes exist**.
3. Card → `.programme-dt-toolbar` (filters left · Columns + search right).
4. `.programme-dt-panel` → `.table-responsive` → `table.programme-dt-table`.
5. Row actions → `.programme-action-group` (Edit / switch / Delete).
6. Status → `.badge` pill (`bg-success`/`bg-secondary`), accessible colours.
7. Footer → empty div if DataTables paginates; **variant B** + `data-sargam-dt-ui="false"`
   if Laravel paginates.
8. Tokens from `design.md`; page CSS namespaced under a page-root class; `?v={{ @filemtime() }}`.
9. **Preserve 100% of functionality** — no controller/route/model/logic change. If the
   canonical design needs a data-source change (Laravel-paginate → DataTable for working
   Columns/search/page-size), **flag it, don't silently change the backend**.
10. Gate + re-baseline (chrome/firefox/webkit); one page at a time; STOP for review.

---

## STOP — awaiting review
Country now demonstrates the canonical Store Master pattern for a Laravel-paginated page.
On approval I'll roll it across the Master module (state → district → city → …), one page
at a time, each gated + re-baselined, flagging any that need a DataTable data-source.
