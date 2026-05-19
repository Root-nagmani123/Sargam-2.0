@once
@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/colreorder/1.7.0/css/colReorder.bootstrap5.min.css">
<style>
    .mess-col-manager-row.active { background-color: rgba(var(--bs-primary-rgb), 0.08); }
    .mess-col-manager-row .mess-col-drag { cursor: grab; touch-action: none; }
    .cursor-grab { cursor: grab; }
    .mess-col-manager-row.sortable-ghost { opacity: 0.45; }
    .mess-col-hidden { display: none !important; }
    .mess-column-manager-offcanvas { --bs-offcanvas-width: min(420px, 100vw); }
    @media (max-width: 575.98px) {
        .mess-col-manager-toolbar .btn span { font-size: 0; }
        .mess-col-manager-toolbar .btn .material-symbols-rounded { margin: 0 !important; }
    }
</style>
@endpush
@push('scripts')
<script src="https://cdn.datatables.net/colreorder/1.7.0/js/dataTables.colReorder.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script src="{{ asset('admin_assets/js/mess-column-manager.js') }}"></script>
@endpush
@endonce
