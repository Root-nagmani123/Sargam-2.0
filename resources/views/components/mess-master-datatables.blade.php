{{--
    Reusable DataTable with pagination and live auto-search for mess master modules.
    Options: tableId, searchPlaceholder, orderColumn (int|array), orderDir, actionColumnIndex (int|array),
    infoLabel, searchDelay, ordering, pageLength, lengthMenu, responsive (bool), scrollX (bool),
    searchHighlight (bool), searchHighlightExcludeColumns (int[] — extra columns to skip, merged with action columns),
    dom (string|null) — custom DataTables dom layout.
    columnManager (bool) — enable Columns show/hide dropdown (default true).
    columnManagerTitle (string|null) — unused; kept for compatibility.
    columnManagerLocked (int[]) — column indexes that stay visible.
--}}
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
    $pageLength = (int) ($pageLength ?? 10);
    $lengthMenu = $lengthMenu ?? [[10, 25, 50, 100], [10, 25, 50, 100]];
    $responsive = isset($responsive) ? (bool) $responsive : false;
    $scrollX = isset($scrollX) ? (bool) $scrollX : false;
    $searchSmart = isset($searchSmart) ? (bool) $searchSmart : true;
    // Client-side tables always use smart search (multi-word AND across row text).
    $searchSmartClient = true;
    $searchHighlight = isset($searchHighlight) ? (bool) $searchHighlight : true;
    $searchHighlightExcludeColumnsMerged = array_values(array_unique(array_merge(
        $actionColumnIndices,
        isset($searchHighlightExcludeColumns) ? (array) $searchHighlightExcludeColumns : []
    )));
    $columnDefs = count($actionColumnIndices) > 0
        ? [['orderable' => false, 'targets' => $actionColumnIndices]]
        : [];
    $serverSide = isset($serverSide) ? (bool) $serverSide : false;
    $ajaxUrlBase = isset($ajaxUrlBase) ? (string) $ajaxUrlBase : '';
    $serverSideColumnDefs = isset($serverSideColumnDefs) && is_array($serverSideColumnDefs) ? $serverSideColumnDefs : [];
    $dom = $dom ?? '<"row align-items-center mb-2"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row align-items-center mt-2"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>';
    $columnManager = isset($columnManager) ? (bool) $columnManager : true;
    $columnManagerTitle = $columnManagerTitle ?? 'Manage Columns';
    $columnManagerLocked = isset($columnManagerLocked) ? (array) $columnManagerLocked : [];
    $ajaxJsonCallback = isset($ajaxJsonCallback) ? (string) $ajaxJsonCallback : '';
@endphp
@if($columnManager)
    @include('components.mess-column-manager', ['tableId' => $tableId, 'title' => $columnManagerTitle])
@endif
@if($searchHighlight)
@push('styles')
<style>
    mark.dt-search-highlight {
        background-color: #fff3cd;
        color: inherit;
        padding: 0 0.04em;
        border-radius: 2px;
        font-weight: 600;
    }
</style>
@endpush
@endif
@push('scripts')
@include('components.mess-datatable-search-helpers')
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof window.jQuery === 'undefined' || !window.jQuery.fn.DataTable) return;
    var $ = window.jQuery;
    var $table = $('#{{ $tableId }}');
    if (!$table.length || $.fn.DataTable.isDataTable($table)) return;

    // DataTables does not support colspan/rowspan cells inside <tbody>.
    // Many mess tables render a single "no data" row with colspan when empty,
    // which causes "Incorrect column count" / "Requested unknown parameter" warnings.
    // Strip such rows (or any row whose cell count doesn't match header columns)
    // before initializing, so DataTables works cleanly with empty tables.
    var $firstHeaderRow = $table.find('thead tr').first();
    var expectedCols = $firstHeaderRow.children('th,td').length || 0;
    if (expectedCols > 0) {
        $table.find('tbody tr').each(function () {
            var $cells = $(this).children('th,td');
            if (!$cells.length) return;
            var hasSpan = $cells.is('[colspan],[rowspan]');
            if (hasSpan || (expectedCols && $cells.length !== expectedCols)) {
                $(this).remove();
            }
        });
    }

    var order = {!! json_encode($ordering ? (is_array($orderColumn) ? $orderColumn : [[$orderColumn, $orderDir]]) : []) !!};
    var columnDefsMerged = {!! json_encode(array_values(array_merge($columnDefs, $serverSideColumnDefs))) !!};
    var lengthMenu = {!! json_encode($lengthMenu) !!};
    var messDtNonOrderableColumns = {!! json_encode(array_values($actionColumnIndices)) !!};
    var messDtAjaxJsonCallbackName = {!! json_encode($ajaxJsonCallback) !!};

    function messMasterInvokeAjaxJsonCallback(settings) {
        if (!messDtAjaxJsonCallbackName) return;
        try {
            var json = new $.fn.dataTable.Api(settings).ajax.json();
            var fn = window[messDtAjaxJsonCallbackName];
            if (json && typeof fn === 'function') {
                fn(json);
            }
        } catch (e) {}
    }

    @if ($serverSide && $ajaxUrlBase !== '')
    var columnCount = $firstHeaderRow.children('th,td').length;
    var ajaxColumns = [];
    for (var ci = 0; ci < columnCount; ci++) {
        ajaxColumns.push({
            data: ci,
            orderable: messDtNonOrderableColumns.indexOf(ci) === -1,
            searchable: false,
            render: function(data, type) {
                if (type !== 'display' && type !== 'filter') {
                    try {
                        return $('<div>').append($.parseHTML(String(data))).text();
                    } catch (e) {
                        return String(data).replace(/<[^>]*>/g, '');
                    }
                }
                return data;
            }
        });
    }

    function sellingVouchersServerDtUrl() {
        var base = '{{ $ajaxUrlBase }}';
        var qs = window.location.search ? window.location.search.substring(1) : '';
        var sep = base.indexOf('?') === -1 ? '?' : '&';
        return base + (qs ? (sep + qs) : '');
    }

    window.messMasterDataTableAjaxUrlByTable = window.messMasterDataTableAjaxUrlByTable || {};
    window.messMasterDataTableAjaxUrlByTable['{{ $tableId }}'] = sellingVouchersServerDtUrl;

    function messMasterDtAjax(data, callback) {
        $.ajax({
            url: sellingVouchersServerDtUrl(),
            type: 'GET',
            data: data,
            dataType: 'json',
            cache: false,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).done(function(json) {
            callback(json);
        }).fail(function(xhr) {
            if (xhr && (xhr.status === 401 || xhr.status === 419 || xhr.status === 403)) {
                window.alert('Your session may have expired. The page will reload so you can sign in again.');
                window.location.reload();
                return;
            }
            callback({
                draw: data.draw,
                recordsTotal: 0,
                recordsFiltered: 0,
                data: [],
                error: 'Could not load table data. Try refreshing the page.'
            });
        });
    }

    $table.DataTable({
        ordering: {{ $ordering ? 'true' : 'false' }},
        order: order,
        serverSide: true,
        processing: true,
        ajax: messMasterDtAjax,
        columns: ajaxColumns,
        pageLength: {{ $pageLength }},
        lengthMenu: lengthMenu,
        searchDelay: {{ $searchDelay }},
        search: { smart: {{ $searchSmart ? 'true' : 'false' }} },
        responsive: {{ $responsive ? 'true' : 'false' }},
        scrollX: {{ $scrollX ? 'true' : 'false' }},
        @if($columnManager)
        colReorder: { realtime: false, fixedColumnsRight: {{ count($actionColumnIndices) > 0 ? 1 : 0 }} },
        @endif
        dom: {!! json_encode($dom) !!},
        language: {
            search: '',
            searchPlaceholder: '{{ addslashes($searchPlaceholder) }}',
            lengthMenu: 'Show _MENU_ entries',
            info: 'Showing _START_ to _END_ of _TOTAL_ {{ $infoLabel }}',
            infoEmpty: 'No {{ $infoLabel }}',
            emptyTable: 'No {{ $infoLabel }}',
            infoFiltered: '(filtered from _MAX_ total)',
            paginate: { first: '\u00AB\u00AB', last: '\u00BB\u00BB', next: '\u203A', previous: '\u2039' }
        },
        columnDefs: columnDefsMerged,
        initComplete: function(settings) {
            if (typeof window.messDataTableBindSearchInputTrim === 'function') {
                try { window.messDataTableBindSearchInputTrim(new $.fn.dataTable.Api(settings)); } catch (e) {}
            }
            if (typeof window.messDataTableApplyPaginateIcons === 'function') {
                try { window.messDataTableApplyPaginateIcons(new $.fn.dataTable.Api(settings)); } catch (e) {}
            }
            messMasterInvokeAjaxJsonCallback(settings);
        },
        drawCallback: function(settings) {
            if (typeof window.adjustAllDataTables === 'function') {
                try { window.adjustAllDataTables(); } catch (e) {}
            }
            if (typeof window.messDataTableApplyPaginateIcons === 'function') {
                try { window.messDataTableApplyPaginateIcons(new $.fn.dataTable.Api(settings)); } catch (e) {}
            }
            messMasterInvokeAjaxJsonCallback(settings);
            @if ($searchHighlight)
            if (typeof window.messDataTableApplySearchHighlight === 'function') {
                try {
                    window.messDataTableApplySearchHighlight(new $.fn.dataTable.Api(settings), {!! json_encode($searchHighlightExcludeColumnsMerged) !!});
                } catch (e) {}
            }
            @endif
        }
    });
    @else
    $table.DataTable({
        ordering: {{ $ordering ? 'true' : 'false' }},
        order: order,
        pageLength: {{ $pageLength }},
        lengthMenu: lengthMenu,
        searchDelay: {{ $searchDelay }},
        search: { smart: {{ $searchSmartClient ? 'true' : 'false' }} },
        @if(!$searchSmartClient)
        _messCustomMultiWordSearch: true,
        @endif
        responsive: {{ $responsive ? 'true' : 'false' }},
        scrollX: {{ $scrollX ? 'true' : 'false' }},
        @if($columnManager)
        colReorder: { realtime: false, fixedColumnsRight: {{ count($actionColumnIndices) > 0 ? 1 : 0 }} },
        @endif
        dom: {!! json_encode($dom) !!},
        language: {
            search: '',
            searchPlaceholder: '{{ addslashes($searchPlaceholder) }}',
            lengthMenu: 'Show _MENU_ entries',
            info: 'Showing _START_ to _END_ of _TOTAL_ {{ $infoLabel }}',
            infoEmpty: 'No {{ $infoLabel }}',
            emptyTable: 'No {{ $infoLabel }}',
            infoFiltered: '(filtered from _MAX_ total)',
            paginate: { first: '\u00AB\u00AB', last: '\u00BB\u00BB', next: '\u203A', previous: '\u2039' }
        },
        columnDefs: columnDefsMerged,
        initComplete: function(settings) {
            if (typeof window.messDataTableBindSearchInputTrim === 'function') {
                try { window.messDataTableBindSearchInputTrim(new $.fn.dataTable.Api(settings)); } catch (e) {}
            }
            if (typeof window.messDataTableApplyPaginateIcons === 'function') {
                try { window.messDataTableApplyPaginateIcons(new $.fn.dataTable.Api(settings)); } catch (e) {}
            }
            messMasterInvokeAjaxJsonCallback(settings);
        },
        drawCallback: function(settings) {
            if (typeof window.adjustAllDataTables === 'function') {
                try { window.adjustAllDataTables(); } catch (e) {}
            }
            if (typeof window.messDataTableApplyPaginateIcons === 'function') {
                try { window.messDataTableApplyPaginateIcons(new $.fn.dataTable.Api(settings)); } catch (e) {}
            }
            messMasterInvokeAjaxJsonCallback(settings);
            @if ($searchHighlight)
            if (typeof window.messDataTableApplySearchHighlight === 'function') {
                try {
                    window.messDataTableApplySearchHighlight(new $.fn.dataTable.Api(settings), {!! json_encode($searchHighlightExcludeColumnsMerged) !!});
                } catch (e) {}
            }
            @endif
        }
    });
    @endif

    window.messMasterDataTableRegistry = window.messMasterDataTableRegistry || [];
    if ($.fn.DataTable.isDataTable($table)) {
        var dtApi = $table.DataTable();
        window.messMasterDataTableRegistry.push(dtApi);

        @if($columnManager)
        function initMessColumnManagerWhenReady() {
            if (typeof window.MessColumnManager === 'undefined') {
                setTimeout(initMessColumnManagerWhenReady, 50);
                return;
            }
            window.MessColumnManager.init({
                tableId: '{{ $tableId }}',
                mode: 'datatable',
                dtApi: dtApi,
                $table: $table,
                colReorder: true,
                lockedColumns: {!! json_encode($columnManagerLocked) !!},
                skipColumns: {!! json_encode($actionColumnIndices) !!}
            });
        }
        initMessColumnManagerWhenReady();
        @endif
    }
});
</script>
@endpush
