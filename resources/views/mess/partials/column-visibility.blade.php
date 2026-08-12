{{--
    Mess Management — shared "Columns" modal (programme / attendance style).

    Renders the modal AND pushes its bridge script, so a page needs one include
    plus a trigger button:

        <button type="button" class="btn programme-dt-btn-columns"
                data-bs-toggle="modal" data-bs-target="#{{ $modalId }}">
            <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
        </button>

        @include('mess.partials.column-visibility', ['tableId' => 'foosTable', 'key' => 'foos'])

    The mess Column-manager (components/mess-column-manager.blade.php) owns the
    visibility state and drives the Download / Print column sync; this modal is only
    its programme-styled UI. The manager's own injected dropdown is hidden by each
    page's `.mess-col-manager-dropdown { display: none }` rule.

    Options:
      $tableId (string, required) — the DataTable id.
      $key     (string)           — id prefix; defaults to $tableId.
      $title   (string)           — modal heading.
--}}
@php
    $key = $key ?? $tableId;
    $modalId = $modalId ?? ($key . 'ColumnVisibilityModal');
    $gridId = $gridId ?? ($key . 'ColumnToggleGrid');
    $title = $title ?? 'Column Visibility';
@endphp
@once
@push('styles')
<style>
    /* Tile styling for every mess Column-Visibility grid (mirrors programme / attendance). */
    .mess-colvis-grid .colvis-item {
        cursor: pointer;
        transition: border-color 0.15s ease, background-color 0.15s ease;
    }

    .mess-colvis-grid .colvis-item:hover {
        border-color: var(--ds-primary, #004384) !important;
        background-color: rgba(0, 67, 132, 0.04);
    }

    .mess-colvis-grid .colvis-item .form-check-input { cursor: pointer; flex-shrink: 0; }
</style>
@endpush
@endonce

<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $key }}ColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="{{ $key }}ColumnVisibilityLabel">{{ $title }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3 mess-colvis-grid" id="{{ $gridId }}"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var TABLE_ID = @json($tableId);
    var $ = window.jQuery;
    var grid = document.getElementById(@json($gridId));
    var modalEl = document.getElementById(@json($modalId));
    if (!$ || !grid || !modalEl) return;

    function getMgr() {
        return (window.MessColumnManager && typeof window.MessColumnManager.get === 'function')
            ? window.MessColumnManager.get(TABLE_ID)
            : null;
    }

    function visibleCount(mgr) {
        return mgr.baseColumns.filter(function (c) {
            return mgr.state.visibility[String(c.index)] !== false;
        }).length;
    }

    function buildGrid() {
        var mgr = getMgr();
        if (!mgr || !mgr.baseColumns || !mgr.baseColumns.length) return false;

        grid.innerHTML = '';
        (mgr.state.order || []).forEach(function (idx) {
            var col = mgr.baseColumns.filter(function (c) { return c.index === idx; })[0];
            if (!col) return;

            var isVisible = mgr.state.visibility[String(col.index)] !== false;
            var inputId = @json($key) + 'colvis_' + col.index;

            var cell = document.createElement('div');
            cell.className = 'col-12 col-sm-6 col-md-4';

            var label = document.createElement('label');
            label.className = 'colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100';
            label.setAttribute('for', inputId);

            var cb = document.createElement('input');
            cb.type = 'checkbox';
            cb.className = 'form-check-input m-0';
            cb.id = inputId;
            cb.checked = isVisible;
            if (col.locked) cb.disabled = true;

            cb.addEventListener('change', function () {
                var m = getMgr();
                if (!m) return;
                if (!cb.checked && visibleCount(m) <= 1) {
                    cb.checked = true;
                    window.alert('At least one column must remain visible.');
                    return;
                }
                m.state.visibility[String(col.index)] = cb.checked;
                m.saveState();
                m.apply();
            });

            var span = document.createElement('span');
            span.textContent = col.label;

            label.appendChild(cb);
            label.appendChild(span);
            cell.appendChild(label);
            grid.appendChild(cell);
        });
        return true;
    }

    modalEl.addEventListener('show.bs.modal', function () {
        if (buildGrid()) return;
        // Column-manager still initialising — retry briefly.
        var tries = 0;
        var timer = setInterval(function () {
            if (buildGrid() || ++tries > 20) clearInterval(timer);
        }, 100);
    });
})();
</script>
@endpush
