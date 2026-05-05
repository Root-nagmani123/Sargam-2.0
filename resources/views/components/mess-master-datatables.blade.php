{{--
    Reusable DataTable with pagination and live auto-search for mess master modules.
    Options: tableId, searchPlaceholder, orderColumn (int|array), orderDir, actionColumnIndex (int|array),
    infoLabel, searchDelay, ordering, pageLength, lengthMenu, responsive (bool), scrollX (bool),
    searchHighlight (bool), searchHighlightExcludeColumns (int[] — extra columns to skip, merged with action columns).
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
    $searchHighlight = isset($searchHighlight) ? (bool) $searchHighlight : true;
    $searchHighlightExcludeColumnsMerged = array_values(array_unique(array_merge(
        $actionColumnIndices,
        isset($searchHighlightExcludeColumns) ? (array) $searchHighlightExcludeColumns : []
    )));
    $columnDefs = count($actionColumnIndices) > 0
        ? [['orderable' => false, 'targets' => $actionColumnIndices]]
        : [];
@endphp
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
    var columnDefs = {!! json_encode($columnDefs) !!};
    var lengthMenu = {!! json_encode($lengthMenu) !!};

    $table.DataTable({
        ordering: {{ $ordering ? 'true' : 'false' }},
        order: order,
        pageLength: {{ $pageLength }},
        lengthMenu: lengthMenu,
        searchDelay: {{ $searchDelay }},
        search: { smart: {{ $searchSmart ? 'true' : 'false' }} },
        responsive: {{ $responsive ? 'true' : 'false' }},
        scrollX: {{ $scrollX ? 'true' : 'false' }},
        dom: '<"row align-items-center mb-2"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row align-items-center mt-2"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
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
        columnDefs: columnDefs,
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
});
</script>
@endpush
