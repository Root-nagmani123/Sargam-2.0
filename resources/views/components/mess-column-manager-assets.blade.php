@once
@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/colreorder/1.7.0/css/colReorder.bootstrap5.min.css">
<style>
    .mess-col-hidden { display: none !important; }
    .mess-col-manager-menu { min-width: 12rem; max-height: min(70vh, 320px); overflow-y: auto; }
    .mess-col-manager-menu .dropdown-item { cursor: default; }
    .mess-col-manager-menu .form-check-label { cursor: pointer; user-select: none; }
    .mess-col-manager-dropdown .dropdown-toggle::after { margin-left: 0.35rem; }
    #masterTable_wrapper .dataTables_filter label,
    [id$="Table_wrapper"] .dataTables_filter label { margin-bottom: 0; }
    @media (max-width: 575.98px) {
        .mess-col-manager-dropdown .dropdown-toggle { font-size: 0.875rem; padding: 0.25rem 0.5rem; }
    }
</style>
@endpush
@push('scripts')
@include('components.mess-datatable-search-helpers')
<script src="https://cdn.datatables.net/colreorder/1.7.0/js/dataTables.colReorder.min.js"></script>
<script src="{{ asset('admin_assets/js/mess-column-manager.js') }}?v=3"></script>
@endpush
@endonce
