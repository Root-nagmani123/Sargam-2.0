{{--
    Reusable DataTable with Bootstrap 5 styling for mess master modules.
    Uses dataTables.bootstrap5: form-select/form-control on length & search, Bootstrap grid in dom.
    Table element should use: table table-striped table-hover table-bordered align-middle mb-0 w-100
    Options: tableId, searchPlaceholder, orderColumn (int|array), orderDir, actionColumnIndex (int|array),
    infoLabel, searchDelay, ordering, responsive, scrollX, pageLength, lengthMenu, useGlobalBootstrap.
--}}
<style>
/* Responsive DataTable + hide length dropdown arrow for mess-master-datatables */
.mess-master-dt-wrapper { width: 100%; max-width: 100%; overflow-x: auto; }
/* Target by wrapper id so it applies as soon as DataTables creates the DOM (no dependency on .mess-master-dt-wrapper) */
#{{ $tableId }}_wrapper .dataTables_length select,
.mess-master-dt-wrapper .dataTables_length select {
    appearance: none !important;
    -webkit-appearance: none !important;
    -moz-appearance: none !important;
    background-image: none !important;
    background-position: unset !important;
    background-repeat: unset !important;
    background-size: unset !important;
    --bs-form-select-bg-img: none !important;
    --bs-form-select-bg-icon: none !important;
    padding-right: 1.75rem;
}
@media (max-width: 767.98px) {
    .mess-master-dt-wrapper .dataTables_wrapper .row:first-child,
    .mess-master-dt-wrapper .dataTables_wrapper .dt-row:first-child {
        flex-direction: column !important;
        align-items: stretch !important;
        gap: 0.75rem;
    }
    .mess-master-dt-wrapper .dataTables_length,
    .mess-master-dt-wrapper .dataTables_filter { text-align: left !important; margin-bottom: 0; }
    .mess-master-dt-wrapper .dataTables_length label,
    .mess-master-dt-wrapper .dataTables_filter label {
        display: flex; flex-direction: column; align-items: stretch; gap: 0.25rem; width: 100%;
    }
    .mess-master-dt-wrapper .dataTables_length select { width: 100%; max-width: 100%; min-width: 0; margin: 0; }
    .mess-master-dt-wrapper .dataTables_filter input { width: 100% !important; max-width: 100% !important; margin-left: 0 !important; }
    .mess-master-dt-wrapper .dataTables_wrapper .row:last-child,
    .mess-master-dt-wrapper .dataTables_wrapper .dt-row:last-child {
        flex-direction: column !important;
        align-items: stretch !important;
        gap: 0.5rem;
    }
    .mess-master-dt-wrapper .dataTables_info,
    .mess-master-dt-wrapper .dataTables_paginate { text-align: left !important; }
    .mess-master-dt-wrapper .dataTables_paginate { margin-top: 0.25rem; }
}
</style>
@php
    $tableId = $tableId ?? 'masterTable';
    $searchPlaceholder = $searchPlaceholder ?? 'Search...';
    $orderColumn = $orderColumn ?? 1;
    $orderDir = $orderDir ?? 'asc';
    $actionColumnIndex = $actionColumnIndex ?? -1;
    $actionColumnIndices = is_array($actionColumnIndex) ? $actionColumnIndex : ($actionColumnIndex >= 0 ? [$actionColumnIndex] : []);
    $infoLabel = $infoLabel ?? 'entries';
    $searchDelay = (int) ($searchDelay ?? 300);
    $ordering = isset($ordering) ? (bool) $ordering : true;
    $responsive = isset($responsive) ? (bool) $responsive : false;
    $scrollX = isset($scrollX) ? (bool) $scrollX : true;
    $useGlobalBootstrap = isset($useGlobalBootstrap) ? (bool) $useGlobalBootstrap : false;
    $pageLength = (int) ($pageLength ?? 10);
    $lengthMenu = $lengthMenu ?? [[10, 25, 50, 100], [10, 25, 50, 100]];
    $columnDefs = count($actionColumnIndices) > 0
        ? [['orderable' => false, 'targets' => $actionColumnIndices]]
        : [];
    $order = $ordering ? (is_array($orderColumn) ? $orderColumn : [[$orderColumn, $orderDir]]) : [];
    $config = [
        'tableId' => $tableId,
        'ordering' => $ordering,
        'responsive' => $responsive,
        'scrollX' => $scrollX,
        'useGlobalBootstrap' => $useGlobalBootstrap,
        'order' => $order,
        'pageLength' => $pageLength,
        'lengthMenu' => $lengthMenu,
        'searchDelay' => $searchDelay,
        'columnDefs' => $columnDefs,
        'language' => [
            'search' => '',
            'searchPlaceholder' => $searchPlaceholder,
            'lengthMenu' => 'Show _MENU_ entries',
            'info' => 'Showing _START_ to _END_ of _TOTAL_ ' . $infoLabel,
            'infoEmpty' => 'No ' . $infoLabel,
            'infoFiltered' => '(filtered from _MAX_ total)',
            'paginate' => ['first' => 'First', 'last' => 'Last', 'next' => 'Next', 'previous' => 'Previous'],
        ],
    ];
@endphp
@push('scripts')
<script type="application/json" class="mess-master-datatables-config">@json($config)</script>
<script>
(function() {
    var el = document.currentScript.previousElementSibling;
    if (!el || el.type !== 'application/json' || !el.classList.contains('mess-master-datatables-config')) return;
    var config;
    try { config = JSON.parse(el.textContent); } catch (e) { return; }
    function hideLengthSelectArrow(wrapper) {
        if (!wrapper || !wrapper.length) return;
        var sel = wrapper[0].querySelector('.dataTables_length select');
        if (!sel || sel.dataset.mmNoArrow === '1') return;
        sel.dataset.mmNoArrow = '1';
        sel.style.setProperty('appearance', 'none', 'important');
        sel.style.setProperty('-webkit-appearance', 'none', 'important');
        sel.style.setProperty('-moz-appearance', 'none', 'important');
        sel.style.setProperty('background-image', 'none', 'important');
        sel.style.setProperty('background-position', 'unset', 'important');
        sel.style.setProperty('background-repeat', 'unset', 'important');
        sel.style.setProperty('background-size', 'unset', 'important');
        sel.style.setProperty('--bs-form-select-bg-img', 'none', 'important');
        sel.style.setProperty('--bs-form-select-bg-icon', 'none', 'important');
    }
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof window.jQuery === 'undefined' || !window.jQuery.fn.DataTable) return;
        var $ = window.jQuery;
        var $table = $('#' + config.tableId);
        if (!$table.length || $.fn.DataTable.isDataTable($table)) return;
        var dtConfig = {
            ordering: config.ordering,
            responsive: config.responsive,
            scrollX: config.scrollX,
            order: config.order,
            pageLength: config.pageLength,
            lengthMenu: config.lengthMenu,
            searchDelay: config.searchDelay,
            columnDefs: config.columnDefs,
            initComplete: function() {
                var wrapper = $table.closest('.dataTables_wrapper');
                wrapper.addClass('mess-master-dt-wrapper');
                var lenSel = wrapper.find('.dataTables_length select')[0];
                if (lenSel) {
                    lenSel.classList.add('form-select', 'form-select-sm');
                }
                var filterInput = wrapper.find('.dataTables_filter input')[0];
                if (filterInput) {
                    filterInput.classList.add('form-control', 'form-control-sm');
                    if (config.searchPlaceholder) filterInput.placeholder = config.searchPlaceholder;
                }
                hideLengthSelectArrow(wrapper);
                setTimeout(function() { hideLengthSelectArrow(wrapper); }, 0);
                setTimeout(function() { hideLengthSelectArrow(wrapper); }, 150);
            },
            drawCallback: function() {
                var wrapper = $table.closest('.dataTables_wrapper');
                hideLengthSelectArrow(wrapper);
                if (typeof window.adjustAllDataTables === 'function') {
                    try { window.adjustAllDataTables(); } catch (e) {}
                }
            }
        };
        if (config.useGlobalBootstrap) {
            dtConfig.language = { search: '' };
        } else {
            dtConfig.dom = '<"row align-items-center mb-2"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row align-items-center mt-2"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>';
            dtConfig.language = config.language;
        }
        $table.DataTable(dtConfig);
    });
})();
</script>
@endpush

