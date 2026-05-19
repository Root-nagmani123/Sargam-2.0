{{-- Auto-init DOM column manager for admin mess tables with data-mess-column-manager attribute. --}}
@include('components.mess-column-manager-assets')
@include('admin.mess.partials.column-manager-offcanvas-template')

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.jQuery === 'undefined' || typeof window.MessColumnManager === 'undefined') {
        return;
    }
    var $ = window.jQuery;

    function ensureOffcanvas(tableId, title) {
        if (document.getElementById('messColManagerOffcanvas-' + tableId)) {
            return;
        }
        var tpl = document.getElementById('messColManagerOffcanvasTemplate');
        if (!tpl) return;
        var html = tpl.innerHTML.replace(/__TABLE_ID__/g, tableId).replace(/__TITLE__/g, title || 'Manage Columns');
        document.body.insertAdjacentHTML('beforeend', html);
    }

    $('table[data-mess-column-manager][id]').each(function () {
        var tableId = this.id;
        if (!tableId) return;
        if ($.fn.DataTable && $.fn.DataTable.isDataTable('#' + tableId)) {
            return;
        }

        var title = $(this).data('mess-column-title') || 'Manage Columns';
        var locked = $(this).data('mess-column-locked');
        var skip = $(this).data('mess-column-skip');
        locked = locked ? String(locked).split(',').map(Number) : [];
        skip = skip ? String(skip).split(',').map(Number) : [];

        ensureOffcanvas(tableId, title);

        window.MessColumnManager.init({
            tableId: tableId,
            mode: 'dom',
            $table: $('#' + tableId),
            colReorder: false,
            lockedColumns: locked,
            skipColumns: skip
        });
    });
});
</script>
@endpush
