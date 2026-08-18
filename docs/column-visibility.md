# Column Visibility (with remembered state)

How the "Columns" button on an admin index page works: a modal of per-column
checkboxes that hides/shows table columns, keeps the choice across reloads and
logins, and keeps the Excel/PDF downloads in sync with what's on screen.

**Reference implementation:** `resources/views/admin/memo_discipline/index.blade.php`
(Send Discipline Memo). Copy from there.

---

## Two variants exist — pick the one matching your page's chrome

| | `sn-colvis-*` (chip grid) | `colvis-item` (card grid) |
|---|---|---|
| Used by | Notice / Memo / Discipline pages (`.disc-page`, `.sn-*`, `.mnm-*`) | `programme-dt` pages (Attendance, Programme, Group Mapping, Subject…) |
| CSS | `public/css/notice-memo-discipline.css:1037-1127` | `public/css/custom.css:238-269` |
| Layout | `.sn-colvis-grid` — CSS grid, 3 cols (2 below 576px) | Bootstrap `.row g-3` |
| Trigger | `.disc-icon-btn` | `.programme-dt-btn-columns` |
| Adding a page | Free — styling is by class | **Must edit CSS** — `custom.css:238-269` selects by grid **ID**, so a new page's grid id has to be appended to those three selector groups |

The rest of this doc uses the `sn-colvis-*` variant. The JS contract is identical
for both — only the chip markup differs.

---

## 1. Markup

The modal body is an **empty container**; chips are generated from the table's
own `<th>` cells so the two can never drift apart.

```html
<div class="modal fade sn-colvis-modal" id="discColumnModal" tabindex="-1"
     aria-labelledby="discColumnModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="discColumnModalLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="sn-colvis-grid" id="discColumnGrid"></div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn-close-colvis" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
```

Trigger button, in the toolbar:

```html
<button type="button" class="disc-icon-btn" data-bs-toggle="modal" data-bs-target="#discColumnModal">
    <i class="bi bi-layout-three-columns"></i> Columns
</button>
```

---

## 2. The index-aligned key maps

A page that also exports needs two arrays that map **header index → key**. They
must stay in the same order as the `<th>` cells, with a blank entry for every
column that isn't sortable / isn't in the export.

```js
// header index -> sort key the server's export understands
var DISC_COLUMN_SORT_KEYS = ['', '', 'program', 'name', 'ot_code', 'cadre', 'email',
    'mobile', 'date', 'infraction', '', 'submitted', 'final', 'remarks',
    'conclusion_remark', 'created_date', 'status', ''
];

// header index -> export column key for the server's ?cols= filter
// '' = a column that isn't in the Excel/PDF at all (select box, Category, Action)
var DISC_EXPORT_COLUMN_KEYS = ['', 'sno', 'program', 'name', 'ot_code', 'cadre',
    'email', 'mobile', 'date', 'infraction', '', 'submitted', 'final', 'remarks',
    'conclusion_remark', 'created_date', 'status', ''
];
var DISC_EXPORT_COL_COUNT = DISC_EXPORT_COLUMN_KEYS.filter(Boolean).length;
```

> ⚠️ **Adding a column to the table means editing both arrays and the empty-state
> `colspan`.** They are positional. Miss one and the Columns modal will hide a
> different column than the one you ticked, and the download will export a third.

---

## 3. Persistence

Stored in `localStorage`, so the choice survives a refresh, an AJAX filter,
pagination, and a logout/login on that browser.

```js
// Keyed by user id so two people sharing a machine don't inherit each other's
// hidden columns.
var DISC_COLVIS_KEY = 'sargam.disciplineMemo.hiddenCols.{{ auth()->id() ?? 'guest' }}';

window.discReadHiddenCols = function () {
    try {
        var raw = window.localStorage.getItem(DISC_COLVIS_KEY);
        var arr = raw ? JSON.parse(raw) : [];
        return Array.isArray(arr) ? arr : [];
    } catch (e) {
        return []; // private mode / storage disabled / corrupt value
    }
};

window.discSaveHiddenCols = function () {
    var hidden = [];
    document.querySelectorAll('.disc-col-toggle').forEach(function (cb) {
        if (!cb.checked) hidden.push(cb.dataset.label);
    });
    try {
        window.localStorage.setItem(DISC_COLVIS_KEY, JSON.stringify(hidden));
    } catch (e) { /* storage unavailable — the preference just won't persist */ }
};
```

### Store LABELS, never indices

The stored value is a list of column **labels** (the header text). This is the
single most important decision in the whole feature.

Indices shift the moment a column is added to the table. When Email and Mobile
were inserted after Cadre, every index to their right moved by two — anyone
holding a saved preference would have had the *wrong* columns silently hidden,
with no error and no clue why.

A label that no longer matches any header is simply ignored, so a **renamed**
column comes back **visible** rather than hiding something else. That is the
safe direction to fail in.

### Scope

`localStorage` is **per browser, per device**. Same browser + logout/login → the
choice persists. Different laptop, or Chrome → Firefox → it doesn't. If the
preference must follow the user's account across devices, it needs a
server-side user-preferences table and a save endpoint instead; the read/save
functions above are the only two places that would change.

---

## 4. Building the chips (restores saved state)

```js
var $discGrid = $('#discColumnGrid');
// Whatever the user hid last time comes back unticked.
var discHidden = window.discReadHiddenCols();

$('#discTable thead th').each(function (i) {
    // Header cells with no text aren't real, nameable columns — the only one is
    // the row-select checkbox column. No label, no chip.
    var label = $(this).text().trim();
    if (!label) return;

    var id = 'discCol' + i;
    $discGrid.append(
        $('<label class="sn-colvis-chip">').attr({ 'for': id, title: label })
            .append($('<input type="checkbox" class="form-check-input disc-col-toggle">')
                .attr({ id: id, 'data-col': i, 'data-label': label })
                .prop('checked', discHidden.indexOf(label) === -1))
            .append(' ')
            .append($('<span>').text(label))
    );
});
```

Build with jQuery objects rather than string concatenation — `.text(label)`
escapes, string concat into `title="…"` does not.

---

## 5. Applying visibility

`discApplyColumnVisibility()` has **two modes**, and both are needed.

```js
window.discApplyColumnVisibility = function () {
    var toggles = document.querySelectorAll('.disc-col-toggle');
    if (toggles.length) {
        toggles.forEach(function (cb) {
            var nth = parseInt(cb.dataset.col, 10) + 1;
            $('#discTable tr').each(function () {
                $(this).children(':nth-child(' + nth + ')').toggle(cb.checked);
            });
        });
        return;
    }

    // First paint: the Columns modal is built in a LATER ready handler, so there
    // are no checkboxes to read yet. Fall back to the saved labels — otherwise
    // hidden columns flash back onto the screen on every page load until that
    // handler runs.
    var hidden = window.discReadHiddenCols();
    if (!hidden.length) return;
    $('#discTable thead th').each(function (i) {
        if (hidden.indexOf($(this).text().trim()) === -1) return;
        var nth = i + 1;
        $('#discTable tr').each(function () {
            $(this).children(':nth-child(' + nth + ')').hide();
        });
    });
};
```

**Why the fallback:** the table renders first, the colvis grid is built in a
second `$(document).ready` block. Without the label path there's a visible flash
of the hidden columns on every load.

**Why it must re-run after AJAX:** an AJAX filter replaces the table markup
wholesale, resetting every cell's inline `display`. The checkboxes live *outside*
that container and keep their state, so they — not the DOM — are the source of
truth. Call it from your table re-init.

---

## 6. Keeping the downloads in sync

A column hidden on screen must be absent from the Excel and PDF too. The
checkboxes drive `?cols=` on both download links:

```js
window.discUpdateDownloadCols = function () {
    var toggles = document.querySelectorAll('.disc-col-toggle');
    var keys = [];
    toggles.forEach(function (cb) {
        var key = DISC_EXPORT_COLUMN_KEYS[parseInt(cb.dataset.col, 10)];
        if (key && cb.checked) keys.push(key);
    });

    ['discDownloadLink', 'discDownloadPdfLink'].forEach(function (id) {
        var link = document.getElementById(id);
        if (!link) return;
        var base = link.href.split('?')[0];
        var params = new URLSearchParams(link.href.split('?')[1] || '');
        params.delete('cols');
        // Omit ?cols= entirely while nothing is hidden (and before the modal has
        // been built) — the server reads "no cols" as "every column".
        if (toggles.length && keys.length !== DISC_EXPORT_COL_COUNT) {
            params.set('cols', keys.join(','));
        }
        link.href = base + '?' + params.toString();
    });
};
```

Server side, `MemoDisciplineController::resolveDisciplineExportCols()` intersects
the requested keys against the canonical list from
`DisciplineMemoExport::columnDefs()`:

```php
$cols = array_values(array_intersect($known, $wanted));
```

Intersecting against `$known` (rather than trusting `$wanted`) keeps the
canonical column **order** and silently drops anything unrecognised, so a
hand-edited `?cols=` can't reorder the report or inject a column. Empty or
absent → every column.

`'sno'` is not a data column; it only drives the Excel `#` / PDF serial.

---

## 7. Wiring order

Both `@push('scripts')` blocks run in source order. The table re-init runs
first, the colvis grid is built second.

```js
// Block 1 — table re-init (also the AJAX re-render path)
window.reinitDiscTable = function () {
    ...
    window.discApplyColumnVisibility();  // label fallback on first run
    window.discUpdateDownloadCols();     // no-op until chips exist
    ... DataTable init ...
    window.discRenumberSerial();
    window.discFreezeColumns();
};

// Block 2 — after building the chips
window.discApplyColumnVisibility();  // now checkbox-driven, for real
window.discFreezeColumns();          // widths changed, re-measure pinned cols
window.discUpdateDownloadCols();     // stamp ?cols= now that chips exist

// On every toggle
$discGrid.on('change', '.disc-col-toggle', function () {
    window.discSaveHiddenCols();
    // ...toggle cells, then:
    window.discFreezeColumns();
    window.discUpdateDownloadCols();
});
```

`discFreezeColumns()` must re-run on every visibility change: hiding a column
changes the width every frozen (sticky) column to its right is offset by, so the
pinned `left` values have to be re-measured.

---

## 8. Checklist — adding this to a new page

1. Add the modal + "Columns" trigger button.
2. Build the chip grid from `thead th`, skipping label-less headers.
3. Define `DISC_COLVIS_KEY`-equivalent, keyed by `auth()->id()`, with a
   page-unique prefix.
4. Add read/save helpers (wrap `localStorage` in `try/catch` — private mode
   throws).
5. Store **labels**, not indices.
6. Apply-visibility function with the two modes (checkbox + label fallback).
7. Call apply + re-measure + download-sync from: page ready, after chip build,
   after every AJAX table re-render, and on every toggle.
8. If the page exports: add the index-aligned export-key array and the `?cols=`
   stamping, and make the server intersect against its canonical column list.
9. Using the `colvis-item` variant? Append your grid's ID to the three selector
   groups in `custom.css:238-269`, or it will be unstyled.

---

See also: [design.md](design.md) for the `--ds-*` token layer,
[new-design-index-page.md](new-design-index-page.md) for the surrounding page
chrome.
