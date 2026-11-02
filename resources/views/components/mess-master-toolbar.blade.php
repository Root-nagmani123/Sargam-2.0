{{--
    Table-card toolbar for Mess Management master screens (Figma "Mess Management View").
    Left: optional filters (slot). Right: search (revealed by the icon button), Columns.
    The DataTables search box is moved into the search slot by datatable-global-ui.js and the
    Columns dropdown is mounted by mess-column-manager.js (messColManagerMount-<tableId>).
    Wrap the page in .mess-master-page to opt in to the styles below.
    :columns="false" / :search="false" drop those tools for tables without a column manager / DataTables search.
--}}
@props(['tableId', 'columns' => true, 'search' => true])
<div class="mess-dt-toolbar no-print">
    <div class="mess-dt-filters">{{ $slot }}</div>
    <div class="mess-dt-tools">
        @if($search)
        <div class="programme-dt-search mess-dt-search" id="{{ $tableId }}SearchSlot" data-dt-search-for="{{ $tableId }}" hidden></div>
        @endif
        @if($columns)
        {{-- The column manager's own dropdown lives here, hidden: it stays the state engine the modal drives. --}}
        <div id="messColManagerMount-{{ $tableId }}" hidden></div>
        <button type="button" class="mess-dt-columns-btn" data-bs-toggle="modal" data-bs-target="#{{ $tableId }}ColumnModal">
            <span>Columns</span>
            <i class="material-symbols-rounded" aria-hidden="true">view_column</i>
        </button>
        @endif
        @if($search)
        <button type="button" class="mess-dt-search-toggle" data-mess-search-toggle="{{ $tableId }}"
            aria-controls="{{ $tableId }}SearchSlot" aria-expanded="false" aria-label="Search" title="Search">
            <i class="material-symbols-rounded" aria-hidden="true">search</i>
        </button>
        @endif
    </div>
</div>

@if($columns)
<div class="modal fade mess-colvis-modal" id="{{ $tableId }}ColumnModal" tabindex="-1"
    aria-labelledby="{{ $tableId }}ColumnModalLabel" aria-hidden="true" data-mess-colvis="{{ $tableId }}">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="{{ $tableId }}ColumnModalLabel">Column Visibility</h5>
            </div>
            <div class="modal-body">
                <div class="mess-colvis-grid"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endif

@once
@push('styles')
<style>
/* ── Mess Management master screens ─────────────────────────────── */
.mess-master-page .mess-export-bar {
    display: flex;
    justify-content: flex-end;
    gap: var(--ds-space-2);
    margin-bottom: var(--ds-space-3);
}
.mess-master-page .mess-export-bar .btn {
    display: inline-flex;
    align-items: center;
    gap: var(--ds-space-2);
    min-height: var(--ds-control-h);
    background: var(--ds-surface);
    border: 0;
    color: var(--ds-primary);
    border-radius: var(--ds-radius-card);
}
.mess-master-page .mess-export-bar .btn:hover { background: var(--ds-surface-2); }

.mess-master-page .mess-dt-card { padding: var(--ds-space-3); }
.mess-master-page .mess-dt-toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: var(--ds-space-3);
    margin-bottom: var(--ds-space-3);
}
.mess-master-page .mess-dt-filters,
.mess-master-page .mess-dt-tools {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--ds-space-2);
}
.mess-master-page .mess-dt-filters:empty { display: none; }
.mess-master-page .mess-dt-tools { margin-left: auto; }
/* One row: filters take what the tools leave and overflow into "+N Filters" (MessToolbarLayout below,
   driven by the toolbar's own width). Only when not even that fits do the tools drop to a second line. */
.mess-master-page .mess-dt-toolbar { flex-wrap: nowrap; }
.mess-master-page .mess-dt-filters { flex: 1 1 0; min-width: 0; }
.mess-master-page .mess-dt-filters > form { flex: 1 1 auto; min-width: 0; } /* a wrapping form otherwise shrinks to its widest control */
.mess-master-page .mess-dt-filters > form > .form-select { width: auto; } /* Bootstrap's 100% would push its label onto a line of its own */
.mess-master-page .mess-dt-search { flex: none; width: 300px; } /* custom.css makes it 100% below 992px */
.mess-master-page .mess-dt-tools { flex: none; flex-wrap: nowrap; }
.mess-master-page .mess-dt-toolbar.is-stacked { flex-wrap: wrap; }
.mess-master-page .mess-dt-toolbar.is-stacked .mess-dt-filters { flex-basis: 100%; }
.mess-master-page .mess-dt-search[hidden],
.mess-master-page .mess-dt-search-toggle[hidden] { display: none !important; }
.mess-master-page .mess-dt-search-toggle {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--ds-control-h);
    height: var(--ds-control-h);
    border: 0;
    border-radius: var(--ds-radius-card);
    background: rgba(var(--bs-primary-rgb), .1);
    color: var(--ds-primary);
}
.mess-master-page .mess-dt-search-toggle[aria-expanded="true"] { background: var(--ds-primary); color: #fff; }

.mess-master-page .mess-dt-columns-btn {
    display: inline-flex;
    align-items: center;
    gap: var(--ds-space-2);
    min-height: var(--ds-control-h);
    padding: 0 var(--ds-space-3);
    font-size: .875rem;
    color: var(--ds-primary);
    background: var(--ds-surface);
    border: 1px solid var(--ds-ink-muted);
    border-radius: var(--ds-radius-card);
}
.mess-master-page .mess-dt-columns-btn:hover { background: var(--ds-surface-2); }

/* Column Visibility modal: chips that flow, title over an inset divider, outlined Close. */
.mess-colvis-modal .modal-content { border: 0; border-radius: var(--ds-radius-card); box-shadow: var(--ds-shadow-lg); }
.mess-colvis-modal .modal-header {
    margin: 0 var(--ds-space-4);
    padding: var(--ds-space-4) 0 var(--ds-space-3);
    border-bottom: 1px solid var(--ds-line);
}
.mess-colvis-modal .modal-title { font-size: 1.125rem; font-weight: 500; color: var(--ds-ink); }
.mess-colvis-modal .modal-body { padding: var(--ds-space-4); }
.mess-colvis-modal .modal-footer { border-top: 0; padding: 0 var(--ds-space-4) var(--ds-space-4); }
.mess-colvis-modal .modal-footer .btn { min-height: var(--ds-control-h); padding: 0 var(--ds-space-4); border-radius: var(--ds-radius-card); }
.mess-colvis-grid { display: flex; flex-wrap: wrap; gap: var(--ds-space-3); }
.mess-colvis-chip {
    display: inline-flex;
    align-items: center;
    gap: var(--ds-space-2);
    min-height: var(--ds-control-h);
    margin: 0;
    padding: 0 var(--ds-space-3);
    border: 1px solid var(--ds-line);
    border-radius: var(--ds-radius);
    font-size: .875rem;
    color: var(--ds-ink);
    cursor: pointer;
}
.mess-colvis-chip:hover { border-color: var(--ds-ink-muted); }
.mess-colvis-chip .form-check-input { margin: 0; float: none; }
.mess-colvis-chip:has(input:disabled) { cursor: not-allowed; opacity: .6; }

/* Table: grey header band, sentence-case labels. */
.mess-master-page .mess-dt-card .table thead th {
    background: var(--ds-line);
    color: var(--ds-ink);
    font-size: .75rem;
    font-weight: 500;
    text-transform: none;
    letter-spacing: 0;
    border-bottom: 0;
}
.mess-master-page .mess-dt-card .table tbody td { font-size: .8125rem; }

.mess-status {
    display: inline-block;
    padding: .25rem .75rem;
    border-radius: var(--ds-radius);
    font-size: .75rem;
    font-weight: 500;
    text-transform: capitalize;
}
.mess-status--success { background: var(--bs-success-bg-subtle); color: #146c34; }
.mess-status--danger { background: rgba(var(--ds-secondary-rgb), .1); color: var(--ds-secondary); }
.mess-status--warning { background: #fff4d6; color: #8a5300; }
.mess-status--primary { background: rgba(var(--bs-primary-rgb), .1); color: var(--ds-primary); }
.mess-status + .mess-status { margin-top: var(--ds-space-1); }

/* Filter row inside the toolbar: compact unlabeled controls, date range, "+N Filter" popover. */
.mess-filterbar { display: flex; flex-wrap: wrap; align-items: center; gap: var(--ds-space-2); margin: 0; }
.mess-filterbar-label { color: var(--ds-ink-muted); font-size: .875rem; }
.mess-filterbar .form-select,
.mess-filterbar .form-control {
    width: auto;
    min-width: 7.5rem;
    height: var(--ds-control-h);
    font-size: .875rem;
    border-radius: var(--ds-radius-card);
}
.mess-filterbar .mess-filter-field { width: 10rem; }
.mess-filterbar .mess-filter-range { display: inline-flex; align-items: center; gap: var(--ds-space-2); }
.mess-filterbar .mess-filter-range .form-control { min-width: 0; width: 15rem; }
.mess-filter-more .mess-filter-range { display: flex; }
.mess-filterbar .mess-filter-more .mess-filter-range .form-control,
.mess-dt-toolbar .mess-filterbar .mess-filter-more .mess-filter-field { width: 100%; } /* beats page-set row widths */
.form-control.mess-date-range {
    padding-right: 2.25rem;
    background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23667085' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='4' width='18' height='18' rx='2'/%3E%3Cpath d='M16 2v4M8 2v4M3 10h18'/%3E%3C/svg%3E") no-repeat right .75rem center / 16px;
}
.mess-filterbar .btn { min-height: var(--ds-control-h); border-radius: var(--ds-radius-card); }
.mess-filter-more-btn {
    border: 0;
    background: none;
    padding: 0 var(--ds-space-1);
    color: var(--ds-primary);
    font-size: .875rem;
    text-decoration: underline;
}
.mess-filter-more-btn.is-active { font-weight: 600; }
.mess-filter-more { min-width: 20rem; padding: var(--ds-space-3); border-radius: var(--ds-radius-card); box-shadow: var(--ds-shadow-lg); }
.mess-filter-more-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: var(--ds-space-3);
    padding-bottom: var(--ds-space-2);
    border-bottom: 1px solid var(--ds-line);
    font-weight: 500;
}
.mess-filter-more .form-select,
.mess-filter-more .form-control { width: 100%; }
.mess-filter-more > * + * { margin-top: var(--ds-space-2); }

.mess-row-actions { display: flex; align-items: flex-start; gap: var(--ds-space-3); }
.mess-row-actions form { margin: 0; }
.mess-row-action {
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
    padding: 0;
    border: 0;
    background: none;
    color: var(--ds-primary);
    font-size: .75rem;
    line-height: 1;
}
.mess-row-action i { font-size: 1.25rem; }
.mess-row-action--danger { color: var(--ds-secondary); }
.mess-row-action--success { color: #146c34; }
.mess-row-action--warning { color: #b45309; }
.mess-row-action:disabled { opacity: .45; cursor: not-allowed; }
.mess-row-action:disabled span { text-decoration: none; }
.mess-row-action:hover span { text-decoration: underline; }

/* ── Searchable dropdowns drawn like the inputs of their own form ──────────────────────
   Widgets in use: Choices markup (.choices > .choices__inner, also the Tom Select shim some pages
   build) and Tom Select (.ts-wrapper.form-select > .ts-control). Each gets the theme .form-control
   box: white, 1px border, 8px corners, same text size and height. The size follows the FORM, not the
   select's own class (several -sm selects sit in regular-size forms): regular by default, small in a
   form whose inputs are .form-control-sm, 40px in toolbar filter rows.
   !important: these widgets ship their own CSS and several pages restyle them by #id. */
.choices > .choices__inner,
.ts-wrapper.form-select {
    display: flex !important;
    flex-wrap: wrap;
    align-items: center;
    min-height: calc(1.5em + 20px + 2 * var(--bs-border-width)) !important;
    font-size: .875rem !important;
    line-height: 1.5 !important;
    background-color: #fff !important;
    border: var(--bs-border-width) solid var(--bs-border-color) !important;
    border-radius: 8px !important;
    box-shadow: none !important;
}
.choices > .choices__inner { padding: 0 36px 0 16px !important; }
/* The Tom Select shim wraps Choices markup in .ts-wrapper, whose own 38px min-height stretched the box. */
.choices.ts-wrapper { min-height: 0 !important; }
.ts-wrapper.form-select { padding: 0 !important; }
.ts-wrapper.form-select > .ts-control {
    flex: 1;
    align-self: stretch;
    align-items: center;
    min-height: 0 !important;
    padding: 0 36px 0 16px !important;
    border: 0 !important;
    background: transparent !important;
    box-shadow: none !important;
    font-size: inherit !important;
    line-height: inherit !important;
}
.choices__list--single { padding: 0 !important; }
/* Multi-selects type into an inline input that Choices paints grey, so the placeholder sat on a chip. */
.choices__inner .choices__input {
    margin: 0 !important;
    padding: 0 !important;
    background: transparent !important;
    font-size: inherit !important;
}
.choices__list--single .choices__item,
.ts-wrapper.single .ts-control > .item { font-size: inherit !important; }
.choices.is-focused > .choices__inner,
.choices.is-open > .choices__inner,
.ts-wrapper.form-select.focus {
    border-color: #b1adff !important;
    box-shadow: 0 0 0 .25rem rgba(99, 91, 255, .25) !important;
}
/* Small forms (inputs are .form-control-sm: 32px, 4px 8px padding, .765625rem). */
:is(form, .modal-body):has(.form-control-sm) :is(.choices > .choices__inner, .ts-wrapper.form-select) {
    min-height: 32px !important;
    font-size: .765625rem !important;
}
:is(form, .modal-body):has(.form-control-sm) .choices > .choices__inner,
:is(form, .modal-body):has(.form-control-sm) .ts-wrapper.form-select > .ts-control { padding: 0 30px 0 8px !important; }
/* Toolbar filter rows: same 40px box as the row's own inputs. */
.mess-dt-toolbar :is(.choices > .choices__inner, .ts-wrapper.form-select) {
    min-height: var(--ds-control-h) !important;
    border-radius: var(--ds-radius-card) !important;
}
.mess-dt-toolbar .choices > .choices__inner,
.mess-dt-toolbar .ts-wrapper.form-select > .ts-control { padding: 0 32px 0 12px !important; }
/* Multi-selects in a filter row stay one line: chosen items are chips cut short with "…" instead of
   stacking the box to several lines (the full list shows when the dropdown opens). */
.mess-dt-toolbar .ts-wrapper.multi > .ts-control,
.mess-dt-toolbar .choices[data-type*="select-multiple"] > .choices__inner,
.mess-dt-toolbar .choices[data-type*="select-multiple"] .choices__list--multiple {
    flex-wrap: nowrap !important;
    overflow: hidden;
}
.mess-dt-toolbar .ts-wrapper.multi > .ts-control > .item,
.mess-dt-toolbar .choices__list--multiple .choices__item {
    flex: 0 1 auto;
    min-width: 0;
    max-width: 7.5rem;
    margin: 0 4px 0 0 !important;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* ── Date ranges: one input, two-month range calendar (Figma). Built by MessDateRange below. ── */
.flatpickr-calendar.mess-range-cal {
    padding: 10px 0 8px; /* no side padding: flatpickr sets the exact width for two months */
    border: 1px solid var(--ds-line);
    border-radius: var(--ds-radius-card);
    box-shadow: var(--ds-shadow-lg);
    font-family: inherit;
}
.flatpickr-calendar.mess-range-cal::before,
.flatpickr-calendar.mess-range-cal::after { display: none; }
.mess-range-cal .flatpickr-months { position: relative; padding-bottom: 4px; }
.mess-range-cal .flatpickr-month { height: 34px; color: var(--ds-ink); }
.mess-range-cal .flatpickr-current-month { padding-top: 7px; font-size: .9375rem; font-weight: 600; }
.mess-range-cal .flatpickr-current-month .cur-month { font-weight: 600; margin-left: 0; }
.mess-range-cal .flatpickr-current-month input.cur-year { font-weight: 600; }
.mess-range-cal .numInputWrapper span { display: none; }
.mess-range-cal .numInputWrapper:hover { background: none; }
.mess-range-cal .flatpickr-prev-month,
.mess-range-cal .flatpickr-next-month,
.mess-range-cal .mess-fp-year {
    position: absolute;
    top: 0;
    height: 34px;
    padding: 0 6px;
    display: flex;
    align-items: center;
    color: var(--ds-ink-muted);
    fill: var(--ds-ink-muted);
    font-size: 1.25rem;
    line-height: 1;
    cursor: pointer;
    z-index: 3;
}
.mess-range-cal .flatpickr-prev-month { left: 26px !important; }
.mess-range-cal .flatpickr-next-month { right: 26px !important; }
.mess-range-cal .mess-fp-year-prev { left: 2px; }
.mess-range-cal .mess-fp-year-next { right: 2px; }
.mess-range-cal .flatpickr-prev-month:hover,
.mess-range-cal .flatpickr-next-month:hover,
.mess-range-cal .mess-fp-year:hover { color: var(--ds-primary); fill: var(--ds-primary); }
.mess-range-cal .flatpickr-prev-month svg,
.mess-range-cal .flatpickr-next-month svg { width: 11px; height: 11px; }
.mess-range-cal span.flatpickr-weekday { color: var(--ds-ink); font-weight: 500; font-size: .8125rem; }
.mess-range-cal .flatpickr-day {
    height: 34px;
    line-height: 32px;
    margin: 1px 0;
    border: 1px solid transparent;
    border-radius: 4px;
    box-shadow: none !important; /* flatpickr joins range days with shadows */
    color: var(--ds-ink);
    font-size: .8125rem;
}
.mess-range-cal .flatpickr-day.prevMonthDay,
.mess-range-cal .flatpickr-day.nextMonthDay { color: #b8bec8; }
/* flatpickr hides neighbouring-month days when it shows two months; the design shows them greyed. */
.mess-range-cal .flatpickr-day.hidden { visibility: visible; }
.mess-range-cal .flatpickr-day:hover { background: rgba(var(--bs-primary-rgb), .08); border-color: transparent; }
.mess-range-cal .flatpickr-day.today { border-color: var(--ds-ink); }
.mess-range-cal .flatpickr-day.inRange { background: rgba(var(--bs-primary-rgb), .14); border-color: transparent; border-radius: 4px; }
.mess-range-cal .flatpickr-day.selected,
.mess-range-cal .flatpickr-day.startRange,
.mess-range-cal .flatpickr-day.endRange {
    background: var(--ds-primary);
    border-color: var(--ds-primary);
    border-radius: 4px;
    color: #fff;
}
/* Neighbouring-month days stay plain grey even inside the range (the range shows in their own month). */
.mess-range-cal .flatpickr-day:is(.prevMonthDay, .nextMonthDay):is(.selected, .startRange, .endRange, .inRange) {
    background: none;
    border-color: transparent;
    color: #b8bec8;
}

/* "Leave the Form?" dialog buttons: amber dark enough for white text (WCAG AA). */
.mess-leave-yes { background: #9a5b00; border: 1px solid #9a5b00; color: #fff; }
.mess-leave-yes:hover, .mess-leave-yes:focus { background: #7d4a00; border-color: #7d4a00; color: #fff; }
.mess-leave-no { background: #fff; border: 1px solid #9a5b00; color: #9a5b00; }
.mess-leave-no:hover, .mess-leave-no:focus { background: #fff4d6; border-color: #7d4a00; color: #7d4a00; }

/* Pager: arrows + numbers only (see pagingType in mess-master-datatables). */
.mess-master-page .programme-dt-footer .paginate_button.first,
.mess-master-page .programme-dt-footer .paginate_button.last { display: none; }

/* Modals: plain header with a divider, borderless footer, outlined Cancel. */
.mess-master-page ~ .modal .modal-header { background: var(--ds-surface); }
.mess-master-page ~ .modal .modal-footer { background: var(--ds-surface); border-top: 0; }
</style>
@endpush
@push('scripts')
<script>
document.addEventListener('click', function (e) {
    var closeMore = e.target.closest('[data-mess-filter-more-close]');
    if (closeMore) {
        var dd = closeMore.closest('.dropdown').querySelector('[data-bs-toggle="dropdown"]');
        if (dd && window.bootstrap) bootstrap.Dropdown.getOrCreateInstance(dd).hide();
        return;
    }
    var toggle = e.target.closest('[data-mess-search-toggle]');
    if (!toggle) return;
    var slot = document.getElementById(toggle.getAttribute('aria-controls'));
    if (!slot) return;
    var input = slot.querySelector('input');
    var open = slot.hidden;
    // Never hide a box that is still filtering the table.
    if (!open && input && input.value.trim() !== '') { input.focus(); return; }
    slot.hidden = !open;
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    var bar = toggle.closest('.mess-dt-toolbar');
    bar._messSearchOpen = open;
    if (window.MessToolbarLayout) window.MessToolbarLayout(bar);
    if (open && input) input.focus();
});

// Date ranges: one input with a two-month flatpickr range calendar. The page's From / To inputs stay in
// the form as hidden fields with the same name, id and value format (Y-m-d for browser date inputs,
// dd-mm-yyyy otherwise), so backends and page scripts read them unchanged. A complete range, or an
// emptied box, writes both fields and fires `change` on them; a lone start date is never sent.
// Pairs inside .mess-filter-range bind themselves; others call MessDateRange.bind(fromInput, toInput).
// Page code that sets the hidden fields itself calls MessDateRange.sync(fromInput).
window.MessDateRange = (function () {
    var waiting = null;
    function withFlatpickr(fn) {
        if (window.flatpickr) { fn(); return; }
        if (waiting) { waiting.push(fn); return; }
        waiting = [fn];
        var base = 'https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/';
        if (!document.querySelector('link[href*="flatpickr"]')) {
            var css = document.createElement('link');
            css.rel = 'stylesheet';
            css.href = base + 'flatpickr.min.css';
            css.integrity = 'sha512-MQXduO8IQnJVq1qmySpN87QQkiR1bZHtorbJBD0tzy7/0U9+YIC93QWHeGTEoojMVHWWNkoCp8V6OzVSYrX0oQ==';
            css.crossOrigin = 'anonymous';
            document.head.appendChild(css);
        }
        var js = document.createElement('script');
        js.src = base + 'flatpickr.min.js';
        js.integrity = 'sha512-K/oyQtMXpxI4+K0W7H25UopjM8pzq0yrVdFdG21Fh5dBe91I40pDd9A4lzNlHPHBIP2cwZuoxaUSX0GJSObvGA==';
        js.crossOrigin = 'anonymous';
        js.onload = function () { waiting.forEach(function (f) { f(); }); waiting = null; };
        document.head.appendChild(js);
    }
    function pad(n) { return (n < 10 ? '0' : '') + n; }
    function dmy(d) { return pad(d.getDate()) + '-' + pad(d.getMonth() + 1) + '-' + d.getFullYear(); }
    function ymd(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
    function parse(v) {
        var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(v || '');
        if (m) return new Date(+m[1], m[2] - 1, +m[3]);
        m = /^(\d{1,2})-(\d{1,2})-(\d{4})$/.exec(v || '');
        return m ? new Date(+m[3], m[2] - 1, +m[1]) : null;
    }
    function bind(from, to) {
        if (!from || !to || from._messRange) return;
        from._messRange = to._messRange = true;
        var fmt = from.type === 'date' ? ymd : dmy;
        var box = document.createElement('input');
        box.type = 'text';
        box.className = 'form-control mess-date-range';
        box.placeholder = 'Date range';
        box.autocomplete = 'off';
        box.setAttribute('aria-label', 'Date range');
        [from, to].forEach(function (i) {
            if (i._flatpickr) i._flatpickr.destroy();
            i.type = 'hidden';
        });
        from.parentNode.insertBefore(box, from);
        var dash = to.previousElementSibling;
        if (dash && dash.tagName === 'SPAN') dash.remove();

        function read(useDefault) {
            var a = parse(from.value) || (useDefault && parse(from.getAttribute('data-default-ymd')));
            var b = parse(to.value) || (useDefault && parse(to.getAttribute('data-default-ymd')));
            return a && b ? [a, b] : [];
        }
        var committed = read(true);
        if (committed.length) { from.value = fmt(committed[0]); to.value = fmt(committed[1]); }
        box.value = committed.length ? dmy(committed[0]) + ' to ' + dmy(committed[1]) : '';
        function commit(dates) {
            committed = dates.slice();
            var a = dates.length ? fmt(dates[0]) : '', b = dates.length ? fmt(dates[1]) : '';
            if (a === from.value && b === to.value) return;
            from.value = a;
            to.value = b;
            [from, to].forEach(function (i) { i.dispatchEvent(new Event('change', { bubbles: true })); });
        }
        from._messRangeSync = function () {
            committed = read(false);
            if (box._flatpickr) box._flatpickr.setDate(committed, false);
            else box.value = committed.length ? dmy(committed[0]) + ' to ' + dmy(committed[1]) : '';
        };

        withFlatpickr(function () {
            function decorate(sel, str, fp) {
                fp.calendarContainer.classList.add('mess-range-cal');
                var months = fp.calendarContainer.querySelector('.flatpickr-months');
                [['prev', -1, '«', 'Previous year'], ['next', 1, '»', 'Next year']].forEach(function (y) {
                    var b = document.createElement('span');
                    b.className = 'mess-fp-year mess-fp-year-' + y[0];
                    b.textContent = y[2];
                    b.title = y[3];
                    b.setAttribute('aria-label', y[3]);
                    b.addEventListener('click', function (e) { e.stopPropagation(); fp.changeYear(fp.currentYear + y[1]); });
                    months.appendChild(b);
                });
            }
            function emptied(sel) { return sel.length === 0 && box.value.trim() === ''; }
            flatpickr(box, {
                mode: 'range',
                dateFormat: 'd-m-Y',
                defaultDate: committed,
                allowInput: true,
                showMonths: 2,
                monthSelectorType: 'static',
                shorthandCurrentMonth: true,
                disableMobile: true,
                locale: { firstDayOfWeek: 1, rangeSeparator: ' to ' },
                onReady: decorate,
                onChange: function (sel) {
                    if (sel.length === 2 || emptied(sel)) commit(sel);
                },
                onClose: function (sel, str, fp) {
                    // Closed on a lone start date or an unreadable entry: back to the range in force.
                    if (sel.length !== 2 && !emptied(sel)) fp.setDate(committed, false);
                }
            });
        });
    }
    function sync(from) { if (from && from._messRangeSync) from._messRangeSync(); }
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.mess-filter-range').forEach(function (w) {
            var inputs = w.querySelectorAll('input');
            if (inputs.length !== 2) return;
            bind(inputs[0], inputs[1]);
        });
    });
    // Calendars live at body level (a calendar inside a "+N Filter" popover gets clipped by the table
    // card), so a click in one is "outside" the popover: keep the popover open for it.
    document.addEventListener('hide.bs.dropdown', function (e) {
        var target = e.clickEvent && e.clickEvent.target;
        if (target && target.closest && target.closest('.flatpickr-calendar')) e.preventDefault();
    });
    return { bind: bind, sync: sync };
})();

// Filter bars (.mess-filterbar, [data-mess-auto-filter]) apply as soon as a filter changes: there is no
// Apply button. The form submits normally, so page submit handlers still run. Only a change within a
// moment of a real click or key counts: page scripts that restore filter cascades on load fire `change`
// too, and a submit from them would reload the page in a loop.
(function () {
    var userAt = 0;
    ['pointerdown', 'keydown'].forEach(function (t) {
        document.addEventListener(t, function (e) { if (e.isTrusted) userAt = Date.now(); }, true);
    });
    function isOpen(s) {
        var ch = s.closest('.choices');
        return s.tagName === 'SELECT' && ((s.tomselect && s.tomselect.isOpen) || (ch && ch.classList.contains('is-open')));
    }
    function apply(form, s) {
        // The changed select still open is mid-pick (several options): apply once it closes.
        if (!isOpen(s)) { form.requestSubmit(); return; }
        if (s._messApplyWait) return;
        s._messApplyWait = true;
        function go() { s._messApplyWait = false; apply(form, s); }
        if (s.tomselect && s.tomselect.isOpen) {
            s.tomselect.on('dropdown_close', function once() { s.tomselect.off('dropdown_close', once); go(); });
        } else {
            s.addEventListener('hideDropdown', go, { once: true });
        }
    }
    document.addEventListener('change', function (e) {
        var el = e.target, form = el.form;
        if (!form || !el.name || !form.matches('.mess-filterbar, [data-mess-auto-filter]')) return;
        if (Date.now() - userAt > 1500) return;
        clearTimeout(form._messApplyTimer);
        // A typed browser date fires `change` per segment: let the user finish.
        form._messApplyTimer = setTimeout(function () { apply(form, el); }, el.tagName === 'SELECT' || el.type === 'hidden' ? 150 : 700);
    });
})();

// Responsive toolbar, driven by the toolbar's own width (ResizeObserver), not breakpoints. Filters that
// do not fit beside the tools move into the "+N Filters" popover, lowest priority first, and the search
// box folds into its icon when not even the top filter fits beside it. Filters are moved, never rebuilt:
// values and Tom Select / Choices / flatpickr survive and no `change` fires, so nothing is re-requested.
window.MessToolbarLayout = (function () {
    // Higher first; keys come from the existing field names (filterKey). Unknown names rank last.
    var PRIORITY = ['date', 'status', 'client_type', 'client_category', 'buyer', 'store', 'vendor',
        'category', 'view', 'item', 'course', 'return_status', 'invoice_sent', 'per_page'];
    var SKIP = '.mess-filterbar-label, input[type="hidden"], .dropdown, a, button, noscript, script, .ts-wrapper, .mess-filter-more-head';

    function filterKey(unit) {
        var el = unit.head.matches('[name]') ? unit.head : unit.head.querySelector('[name]');
        var n = el ? el.name.replace(/\[\]$/, '') : '';
        if (/date/.test(n)) return 'date';
        if (n === 'client_type' || n === 'client_type_slug') return 'client_type';
        if (n === 'client_type_pk') return 'client_category';
        if (/^buyer/.test(n)) return 'buyer';
        if (/store/.test(n)) return 'store';
        if (/vendor/.test(n)) return 'vendor';
        if (/category/.test(n)) return 'category';
        if (n === 'view_type') return 'view';
        if (n === 'item_id') return 'item';
        if (/course/.test(n)) return 'course';
        return n;
    }
    // A unit's nodes, resolved each time because plugins wrap selects after page load.
    function nodesOf(unit) {
        var head = unit.head.closest('.choices') || unit.head; // Choices wraps a bare select
        var nodes = unit.label ? [unit.label, head] : [head];
        var ts = unit.head.tomselect && unit.head.tomselect.wrapper; // Tom Select sits beside it
        if (ts && ts !== head && !head.contains(ts)) nodes.push(ts);
        return nodes;
    }
    function shown(n) { return getComputedStyle(n).display !== 'none'; }
    function inFlow(n) { var cs = getComputedStyle(n); return cs.display !== 'none' && cs.position !== 'absolute'; } // not Tom Select's 1px hidden select
    function width(nodes, gap) {
        var ws = nodes.filter(inFlow).map(function (n) { return n.getBoundingClientRect().width; }).filter(Boolean);
        return ws.reduce(function (a, b) { return a + b; }, 0) + Math.max(0, ws.length - 1) * gap;
    }
    function busy(unit) {
        var a = document.activeElement, box = unit.head.querySelector && unit.head.querySelector('.mess-date-range');
        return (box && box._flatpickr && box._flatpickr.isOpen)
            || (unit.head.tomselect && unit.head.tomselect.isOpen)
            || nodesOf(unit).some(function (n) {
                return (a && a !== document.body && n.contains(a)) || n.matches('.choices.is-open') || !!n.querySelector('.choices.is-open');
            });
    }
    function collect(container, inPanel, units) {
        var kids = [].slice.call(container.children);
        for (var i = 0; i < kids.length; i++) {
            var n = kids[i];
            if (n.matches(SKIP)) continue;
            var unit = { head: n, label: null, inPanel: inPanel };
            if (n.tagName === 'LABEL' && kids[i + 1]) { unit.label = n; unit.head = kids[++i]; }
            if (unit.head.classList.contains('choices')) unit.head = unit.head.querySelector('select') || unit.head;
            units.push(unit);
        }
    }
    function place(st, unit, toPanel) {
        nodesOf(unit).forEach(function (n) {
            if (toPanel) st.panel.appendChild(n); else st.form.insertBefore(n, unit.anchor);
        });
        unit.inPanel = toPanel;
    }

    function init(tb) {
        var form = tb.querySelector('.mess-dt-filters form');
        var st = { form: form, units: [], tools: tb.querySelector('.mess-dt-tools'),
            slot: tb.querySelector('.mess-dt-search'), toggle: tb.querySelector('.mess-dt-search-toggle'),
            cols: tb.querySelector('.mess-dt-columns-btn') };
        if (form && form.classList.contains('mess-filterbar')) {
            var more = form.querySelector(':scope > .dropdown');
            if (!more) {
                more = document.createElement('div');
                more.className = 'dropdown';
                more.innerHTML = '<button type="button" class="mess-filter-more-btn" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false"></button>'
                    + '<div class="dropdown-menu mess-filter-more"><div class="mess-filter-more-head"><span>Filters</span>'
                    + '<button type="button" class="btn-close" data-mess-filter-more-close aria-label="Close filters"></button></div></div>';
                form.insertBefore(more, form.querySelector(':scope > a')); // before Remove Filter
            }
            st.more = more;
            st.btn = more.querySelector('.mess-filter-more-btn');
            st.panel = more.querySelector('.mess-filter-more');
            collect(form, false, st.units);
            collect(st.panel, true, st.units);
            st.units.forEach(function (u, i) {
                u.order = i;
                u.rank = PRIORITY.indexOf(filterKey(u));
                if (u.rank < 0) u.rank = PRIORITY.length;
                u.anchor = document.createComment('mess-filter');
                form.insertBefore(u.anchor, u.inPanel ? more : nodesOf(u)[0]);
            });
            // Every filter starts in the row so it can be measured; layout() moves out what does not fit.
            st.units.forEach(function (u) { if (u.inPanel) place(st, u, false); });
            st.btn.textContent = '+9 Filters';
            st.moreW = more.getBoundingClientRect().width;
        }
        if (st.slot) {
            var wasHidden = st.slot.hidden;
            st.slot.hidden = false;
            st.searchW = st.slot.getBoundingClientRect().width;
            st.slot.hidden = wasHidden;
            st.toggleW = st.toggle.getBoundingClientRect().width;
        }
        tb._mess = st;
        ro.observe(tb);
        st.units.forEach(function (u) { ro.observe(u.head); });
        layout(tb);
    }

    function layout(tb) {
        var st = tb._mess, W = tb.getBoundingClientRect().width;
        if (!st || !W) return;
        var gapTb = parseFloat(getComputedStyle(tb).columnGap) || 0;
        var gapTools = parseFloat(getComputedStyle(st.tools).columnGap) || 0;
        var gapF = st.form ? parseFloat(getComputedStyle(st.form).columnGap) || 0 : 0;
        if (st.toggle && !st.toggle.hidden) st.toggleW = st.toggle.getBoundingClientRect().width || st.toggleW;
        var colW = st.cols ? st.cols.getBoundingClientRect().width : 0;
        function toolsW(mode) { // 'expanded': search box; 'icon': toggle only; 'open': box + toggle
            var ws = [colW];
            if (st.slot) {
                if (mode !== 'icon') ws.push(st.searchW);
                if (mode !== 'expanded') ws.push(st.toggleW);
            }
            ws = ws.filter(Boolean);
            return ws.reduce(function (a, b) { return a + b; }, 0) + Math.max(0, ws.length - 1) * gapTools;
        }
        var unitNodes = [];
        st.units.forEach(function (u) { unitNodes = unitNodes.concat(nodesOf(u)); });
        var fixedW = st.form ? [].filter.call(st.form.children, function (n) {
            return n !== st.more && unitNodes.indexOf(n) < 0 && inFlow(n);
        }).map(function (n) { return n.getBoundingClientRect().width; }).filter(Boolean) : [];
        var live = st.units.filter(function (u) { return nodesOf(u).some(shown); }); // d-none fields stay put
        live.forEach(function (u) { if (!u.inPanel) u.w = width(nodesOf(u), gapF); }); // popover ones keep their row width
        var ranked = live.slice().sort(function (a, b) { return a.rank - b.rank || a.order - b.order; });
        function need(k) { // filter row width with the top k filters showing
            var rest = k < ranked.length, items = fixedW.length + k + (rest ? 1 : 0);
            var w = fixedW.concat(ranked.slice(0, k).map(function (u) { return u.w; }), rest ? [st.moreW] : [])
                .reduce(function (a, b) { return a + b; }, 0);
            return w + Math.max(0, items - 1) * gapF;
        }
        function room(mode) { return W - toolsW(mode) - (st.form ? gapTb : 0); }
        var input = st.slot && st.slot.querySelector('input');
        var keepOpen = tb._messSearchOpen || (input && input.value.trim() !== ''); // never hide an active search
        var mode = 'icon';
        if (st.slot && need(Math.min(1, ranked.length)) <= room('expanded')) mode = 'expanded';
        else if (keepOpen) mode = 'open';
        var avail = room(mode);
        // Not even "Filter +N Filters" fits beside the tools: filters take a line of their own.
        var stacked = !!st.form && need(0) > avail;
        if (stacked) {
            avail = W;
            mode = st.slot && toolsW('expanded') <= W ? 'expanded' : (keepOpen ? 'open' : 'icon');
        }
        var k = ranked.length;
        while (k > 0 && need(k) > avail) k--;

        var show = ranked.slice(0, k);
        var moves = st.units.filter(function (u) {
            var toPanel = live.indexOf(u) >= 0 && show.indexOf(u) < 0;
            return toPanel !== u.inPanel;
        });
        // Moving a filter the user is in would drop focus or an open list: wait until they leave it.
        if (moves.some(busy)) { st.pending = true; return; }
        st.pending = false;
        moves.forEach(function (u) { place(st, u, !u.inPanel); });
        if (moves.length) st.units.forEach(function (u) { if (u.inPanel) place(st, u, true); }); // popover keeps page order
        if (st.more) {
            var hidden = ranked.length - k;
            st.more.hidden = hidden === 0;
            st.btn.textContent = '+' + hidden + (hidden === 1 ? ' Filter' : ' Filters');
        }
        tb.classList.toggle('is-stacked', stacked);
        if (st.slot) {
            st.slot.hidden = mode === 'icon';
            st.toggle.hidden = mode === 'expanded';
            st.toggle.setAttribute('aria-expanded', mode === 'open' ? 'true' : 'false');
            // Opened by hand on a screen too narrow for the full box: give it what is left beside the buttons.
            var fit = W - toolsW('open') + st.searchW;
            st.slot.style.width = mode === 'open' && fit < st.searchW ? Math.max(0, fit) + 'px' : '';
        }
    }

    function schedule(tb) {
        if (tb._messRaf) return;
        tb._messRaf = requestAnimationFrame(function () { tb._messRaf = 0; layout(tb); });
    }
    var ro = new ResizeObserver(function (entries) {
        entries.forEach(function (e) {
            var tb = e.target.closest('.mess-dt-toolbar');
            if (tb && tb._mess) schedule(tb);
        });
    });
    document.addEventListener('focusout', function () {
        setTimeout(function () {
            document.querySelectorAll('.mess-dt-toolbar').forEach(function (tb) { if (tb._mess && tb._mess.pending) schedule(tb); });
        }, 50);
    });
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.mess-dt-toolbar').forEach(init);
    });
    return schedule;
})();

// Column Visibility modal: each chip proxies the hidden mess-column-manager checkbox,
// so saved state, the "one column must stay" rule and export column-sync all stay in that script.
(function () {
    function hiddenToggles(tableId) {
        return document.querySelectorAll('#messColManagerMenu-' + tableId + ' input.mess-col-toggle');
    }
    function build(modal) {
        var grid = modal.querySelector('.mess-colvis-grid');
        grid.innerHTML = '';
        hiddenToggles(modal.getAttribute('data-mess-colvis')).forEach(function (src) {
            var chip = document.createElement('label');
            chip.className = 'mess-colvis-chip';
            var cb = document.createElement('input');
            cb.type = 'checkbox';
            cb.className = 'form-check-input';
            cb.checked = src.checked;
            cb.disabled = src.disabled;
            var text = document.createElement('span');
            text.textContent = src.closest('.form-check').querySelector('label').textContent;
            chip.append(cb, text);
            cb.addEventListener('change', function () {
                window.jQuery(src).prop('checked', cb.checked).trigger('change');
                cb.checked = src.checked; // the manager may refuse hiding the last visible column
            });
            grid.appendChild(chip);
        });
    }
    // "Leave the Form?" — opt in with data-mess-confirm-leave on a modal that wraps a <form>.
    // Dirty = form values differ from a snapshot taken just after the modal opened.
    function formSnapshot(form) {
        return JSON.stringify(Array.from(new FormData(form), function (kv) {
            return [kv[0], kv[1] instanceof File ? kv[1].name : kv[1]];
        }));
    }
    document.addEventListener('shown.bs.modal', function (e) {
        var modal = e.target;
        var form = modal.hasAttribute('data-mess-confirm-leave') && modal.querySelector('form');
        if (!form) return;
        setTimeout(function () { modal._messClean = formSnapshot(form); }, 300); // after the page's own on-open defaults
        form.addEventListener('submit', function () { modal._messClean = null; }, { capture: true, once: true });
    });
    document.addEventListener('hide.bs.modal', function (e) {
        var modal = e.target;
        if (!modal._messClean || modal._messLeaving || !window.Swal) return;
        var form = modal.querySelector('form');
        if (!form || formSnapshot(form) === modal._messClean) return;
        e.preventDefault();
        var inst = bootstrap.Modal.getInstance(modal);
        if (inst && inst._focustrap) inst._focustrap.deactivate(); // else the modal steals focus from the dialog
        Swal.fire({
            icon: 'warning',
            title: 'Leave the Form?',
            text: "Are you sure you want to leave? The details you filled won't be saved.",
            showCancelButton: true,
            reverseButtons: true,
            confirmButtonText: 'Yes, Leave',
            cancelButtonText: 'No, Continue filling',
            buttonsStyling: false,
            customClass: { confirmButton: 'btn mess-leave-yes px-4 ms-2', cancelButton: 'btn mess-leave-no px-4' }
        }).then(function (r) {
            if (r.isConfirmed) {
                modal._messLeaving = true;
                inst.hide();
            } else if (inst && inst._focustrap) {
                inst._focustrap.activate();
            }
        });
    });
    document.addEventListener('hidden.bs.modal', function (e) {
        e.target._messClean = null;
        e.target._messLeaving = false;
    });

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.mess-colvis-modal').forEach(function (modal) {
            document.body.appendChild(modal); // keep the fixed-position modal out of the card's stacking context
            modal.addEventListener('show.bs.modal', function () { build(modal); });
        });
    });
})();
</script>
@endpush
@endonce
