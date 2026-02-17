@php
    $tableId = $tableId ?? 'masterTable';
    $searchPlaceholder = $searchPlaceholder ?? 'Search...';
    $orderColumn = (int) ($orderColumn ?? 1);
    $actionColumnIndex = (int) ($actionColumnIndex ?? -1);
    $infoLabel = $infoLabel ?? 'entries';
@endphp
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (window.jQuery && $.fn.DataTable) {
        $('#{{ $tableId }}').DataTable({
            order: [[{{ $orderColumn }}, 'asc']],
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            searchDelay: 150,
            language: {
                search: '',
                searchPlaceholder: '{{ $searchPlaceholder }}',
                lengthMenu: 'Show _MENU_ entries',
                info: 'Showing _START_ to _END_ of _TOTAL_ {{ $infoLabel }}',
                infoEmpty: 'No {{ $infoLabel }}',
                infoFiltered: '(filtered from _MAX_ total)',
                paginate: { first: 'First', last: 'Last', next: 'Next', previous: 'Previous' }
            },
            columnDefs: @if($actionColumnIndex >= 0)[{ orderable: false, targets: {{ $actionColumnIndex }} }]@else[]@endif
        });
    }
});
</script>
@endpush
