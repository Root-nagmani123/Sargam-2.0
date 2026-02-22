{{--
    Reusable DataTable with pagination and live auto-search for mess master modules.
    Options: tableId, searchPlaceholder, orderColumn (int|array), orderDir, actionColumnIndex (int|array),
    infoLabel, searchDelay, ordering, pageLength, lengthMenu.
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
    $responsive = isset($responsive) ? (bool) $responsive : true;
    $ordering = isset($ordering) ? (bool) $ordering : true;
    $pageLength = (int) ($pageLength ?? 10);
    $lengthMenu = $lengthMenu ?? [[10, 25, 50, 100], [10, 25, 50, 100]];
    $columnDefs = count($actionColumnIndices) > 0
        ? [['orderable' => false, 'targets' => $actionColumnIndices]]
        : [];
@endphp
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof window.jQuery === 'undefined' || !window.jQuery.fn.DataTable) return;
    var $ = window.jQuery;
    var $table = $('#{{ $tableId }}');
    if (!$table.length || $.fn.DataTable.isDataTable($table)) return;

    var order = @json($ordering ? (is_array($orderColumn) ? $orderColumn : [[$orderColumn, $orderDir]]) : []);
    var columnDefs = @json($columnDefs);
    var lengthMenu = @json($lengthMenu);

    $table.DataTable({
        responsive: {{ $responsive ? 'true' : 'false' }},
        ordering: {{ $ordering ? 'true' : 'false' }},
        order: order,
        pageLength: {{ $pageLength }},
        lengthMenu: lengthMenu,
        searchDelay: {{ $searchDelay }},
        dom: '<"row align-items-center mb-2"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row align-items-center mt-2"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        language: {
            search: '',
            searchPlaceholder: '{{ addslashes($searchPlaceholder) }}',
            lengthMenu: 'Show _MENU_ entries',
            info: 'Showing _START_ to _END_ of _TOTAL_ {{ $infoLabel }}',
            infoEmpty: 'No {{ $infoLabel }}',
            infoFiltered: '(filtered from _MAX_ total)',
            paginate: { first: 'First', last: 'Last', next: 'Next', previous: 'Previous' }
        },
        columnDefs: columnDefs,
        drawCallback: function() {
            if (typeof window.adjustAllDataTables === 'function') {
                try { window.adjustAllDataTables(); } catch (e) {}
            }
        }
    });
});
</script>
@endpush
