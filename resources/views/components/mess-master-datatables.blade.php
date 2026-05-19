{{--
    Reusable DataTable with pagination and live auto-search for mess master modules.
    Options: tableId, searchPlaceholder, orderColumn (int|array), orderDir, actionColumnIndex (int|array),
    infoLabel, searchDelay, ordering, pageLength, lengthMenu, responsive (bool), scrollX (bool),
    searchHighlight (bool), searchHighlightExcludeColumns (int[] — extra columns to skip, merged with action columns),
    dom (string|null) — custom DataTables dom layout.
    columnManager (bool) — enable Manage Columns offcanvas (default true).
    columnManagerTitle (string|null) — offcanvas title.
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
<script>
(function() {
    if (typeof window.messDataTableApplySearchHighlight !== 'undefined') {
        return;
    }

    var HL_CLASS = 'dt-search-highlight';

    function escapeRegExp(s) {
        return String(s).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    function unwrapSearchMarks(cell) {
        cell.querySelectorAll('mark.' + HL_CLASS).forEach(function(m) {
            var parent = m.parentNode;
            if (!parent) return;
            while (m.firstChild) parent.insertBefore(m.firstChild, m);
            parent.removeChild(m);
        });
        if (cell.normalize) cell.normalize();
    }

    function replaceMatchesInTextNode(textNode, regex) {
        var text = textNode.nodeValue;
        if (text === null || text === '') return;

        var r = new RegExp(regex.source, 'gi');
        if (!r.test(text)) return;
        r.lastIndex = 0;

        var lastIndex = 0;
        var frag = document.createDocumentFragment();
        var match;
        var emptyGuard = 0;
        while ((match = r.exec(text)) !== null) {
            if (match.index > lastIndex) {
                frag.appendChild(document.createTextNode(text.slice(lastIndex, match.index)));
            }
            var mk = document.createElement('mark');
            mk.className = HL_CLASS;
            mk.appendChild(document.createTextNode(match[0]));
            frag.appendChild(mk);
            lastIndex = match.index + match[0].length;
            if (match[0].length === 0) {
                r.lastIndex++;
                if (++emptyGuard > text.length + 50) break;
            }
            if (r.lastIndex >= text.length) break;
        }
        frag.appendChild(document.createTextNode(text.slice(lastIndex)));
        if (!frag.childNodes.length) return;
        textNode.parentNode.replaceChild(frag, textNode);
    }

    /** Highlight inside cell while skipping existing marks so we do not double-wrap nested matches. */
    function highlightCellPlainTextRoots(cell, regex) {
        var nodes = [];
        var w = document.createTreeWalker(cell, NodeFilter.SHOW_TEXT, null, false);
        var n;
        while ((n = w.nextNode())) {
            var anc = n.parentElement;
            if (!anc) continue;
            if (anc.closest && anc.closest('mark.' + HL_CLASS)) continue;
            var up = anc;
            var skipAncest = false;
            while (up) {
                var tag = String(up.tagName || '').toUpperCase();
                if (tag === 'SCRIPT' || tag === 'STYLE' || tag === 'NOSCRIPT') {
                    skipAncest = true;
                    break;
                }
                up = up.parentElement;
            }
            if (skipAncest) continue;
            nodes.push(n);
        }
        nodes.forEach(function(textNode) {
            if (!textNode.parentElement) return;
            replaceMatchesInTextNode(textNode, regex);
        });
    }

    /**
     * @param {$.fn.dataTable.Api} api
     * @param {number[]} excludedColIndices
     */
    window.messDataTableApplySearchHighlight = function(api, excludedColIndices) {
        if (!api) return;

        var raw = '';
        try {
            raw = (typeof api.search === 'function' ? api.search() : '') || '';
        } catch (e) {
            raw = '';
        }
        var search = String(raw).trim();

        var tableEl = api.table().node ? api.table().node() : api.table()[0];
        if (!tableEl) return;

        Array.prototype.slice.call(tableEl.querySelectorAll('tbody td')).forEach(function(td) {
            unwrapSearchMarks(td);
        });

        if (!search) return;

        var skip = {};
        (excludedColIndices || []).forEach(function(idx) {
            skip[Number(idx)] = true;
        });

        var terms = search.split(/\s+/).map(function(t) { return t.trim(); }).filter(Boolean);
        var seen = {};
        var uniq = [];
        terms.forEach(function(t) {
            var k = t.toLowerCase();
            if (!seen[k]) {
                seen[k] = true;
                uniq.push(t);
            }
        });
        uniq.sort(function(a, b) {
            return b.length - a.length;
        });

        var escaped = uniq.filter(function(t) {
            return t.length > 0;
        }).map(escapeRegExp);
        if (!escaped.length) return;

        var mergedRe = new RegExp('(?:' + escaped.join('|') + ')', 'gi');

        api.rows({
            search: 'applied'
        }).every(function() {
            var rowEl = this.node();
            if (!rowEl) return;
            if (rowEl.querySelector && rowEl.querySelector('td[colspan]')) return;
            var cells = rowEl.cells;
            for (var c = 0; c < cells.length; c++) {
                if (skip[c]) continue;
                highlightCellPlainTextRoots(cells[c], mergedRe);
            }
        });
    };

    /** Trim/collapse spaces in DataTables search box (NBSP, double spaces). */
    window.messDataTableNormalizeSearchValue = function(val) {
        return String(val || '').replace(/\u00a0/g, ' ').trim().replace(/\s+/g, ' ');
    };

    /** Strip HTML from a cell value for client-side row matching. */
    window.messDataTableStripHtmlForSearch = function(s) {
        if (typeof window.jQuery === 'undefined') {
            return String(s).replace(/<[^>]*>/g, '');
        }
        try {
            return window.jQuery('<div>').append(window.jQuery.parseHTML(String(s))).text();
        } catch (e) {
            return String(s).replace(/<[^>]*>/g, '');
        }
    };

    /**
     * When search.smart is false, DataTables matches the full phrase only.
     * This hook applies space-separated AND matching across visible row text.
     */
    if (typeof window.jQuery !== 'undefined' && window.jQuery.fn.dataTable && !window._messDtMultiWordSearchHooked) {
        window._messDtMultiWordSearchHooked = true;
        window.jQuery.fn.dataTable.ext.search.push(function(settings, data) {
            if (!settings._messCustomMultiWordSearch) {
                return true;
            }
            var api = new window.jQuery.fn.dataTable.Api(settings);
            var raw = window.messDataTableNormalizeSearchValue(
                typeof api.search === 'function' ? api.search() : ''
            );
            if (!raw) {
                return true;
            }
            var tokens = raw.split(/\s+/).filter(Boolean);
            if (!tokens.length) {
                return true;
            }
            var haystack = data.map(function(cell) {
                return window.messDataTableStripHtmlForSearch(cell).replace(/\s+/g, ' ').trim();
            }).join(' ').toLowerCase();
            return tokens.every(function(t) {
                return haystack.indexOf(String(t).toLowerCase()) !== -1;
            });
        });
    }

    window.messDataTableBindSearchInputTrim = function(api) {
        if (!api || !api.table) {
            return;
        }
        var container = api.table().container();
        if (!container) {
            return;
        }
        var $filter = window.jQuery(container).find('.dataTables_filter input');
        $filter.off('input.messDtSearchTrim').on('input.messDtSearchTrim', function() {
            var normalized = window.messDataTableNormalizeSearchValue(this.value);
            if (this.value !== normalized) {
                this.value = normalized;
            }
            if (typeof api.search === 'function' && api.search() !== normalized) {
                api.search(normalized).draw();
            }
        });
    };
})();

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

    @if ($serverSide && $ajaxUrlBase !== '')
    var columnCount = $firstHeaderRow.children('th,td').length;
    var ajaxColumns = [];
    for (var ci = 0; ci < columnCount; ci++) {
        ajaxColumns.push({
            data: ci,
            orderable: false,
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
            paginate: { first: 'First', last: 'Last', next: 'Next', previous: 'Previous' }
        },
        columnDefs: columnDefsMerged,
        initComplete: function(settings) {
            if (typeof window.messDataTableBindSearchInputTrim === 'function') {
                try { window.messDataTableBindSearchInputTrim(new $.fn.dataTable.Api(settings)); } catch (e) {}
            }
        },
        drawCallback: function(settings) {
            if (typeof window.adjustAllDataTables === 'function') {
                try { window.adjustAllDataTables(); } catch (e) {}
            }
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
            paginate: { first: 'First', last: 'Last', next: 'Next', previous: 'Previous' }
        },
        columnDefs: columnDefsMerged,
        initComplete: function(settings) {
            if (typeof window.messDataTableBindSearchInputTrim === 'function') {
                try { window.messDataTableBindSearchInputTrim(new $.fn.dataTable.Api(settings)); } catch (e) {}
            }
        },
        drawCallback: function(settings) {
            if (typeof window.adjustAllDataTables === 'function') {
                try { window.adjustAllDataTables(); } catch (e) {}
            }
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
