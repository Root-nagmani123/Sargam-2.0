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
/* Filters wrap inside their own column; Columns/search keep the top-right corner. */
@media (min-width: 768px) {
    .mess-master-page .mess-dt-toolbar { flex-wrap: nowrap; align-items: flex-start; }
    .mess-master-page .mess-dt-filters { flex: 1 1 auto; min-width: 0; }
    .mess-master-page .mess-dt-tools { flex: none; flex-wrap: nowrap; }
}
.mess-master-page .mess-dt-search[hidden] { display: none !important; }
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
.mess-filterbar .mess-filter-range .form-control { min-width: 0; width: 9rem; }
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
    if (open && input) input.focus();
});

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
