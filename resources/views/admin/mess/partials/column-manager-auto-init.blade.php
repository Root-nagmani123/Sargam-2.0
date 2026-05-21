{{-- DataTables + column visibility for admin mess list tables (data-mess-column-manager). --}}
@include('components.mess-column-manager-assets')

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.jQuery === 'undefined' || !window.jQuery.fn.DataTable) {
        return;
    }
    var $ = window.jQuery;

    function parseIndexList(raw) {
        if (raw === undefined || raw === null || raw === '') {
            return [];
        }
        return String(raw).split(',').map(function (v) {
            return parseInt(v, 10);
        }).filter(function (n) {
            return !isNaN(n);
        });
    }

    function stripInvalidTbodyRows($table) {
        var expectedCols = $table.find('thead tr').first().children('th,td').length || 0;
        if (expectedCols <= 0) {
            return;
        }
        $table.find('tbody tr').each(function () {
            var $cells = $(this).children('th,td');
            if (!$cells.length) {
                return;
            }
            if ($cells.is('[colspan],[rowspan]') || $cells.length !== expectedCols) {
                $(this).remove();
            }
        });
    }

    function buildColumnDefs(skip) {
        if (!skip.length) {
            return [];
        }
        return [{ orderable: false, targets: skip }];
    }

    $('table[data-mess-column-manager][id]').each(function () {
        var tableId = this.id;
        if (!tableId) {
            return;
        }

        /* Modals with custom search/pagination (e.g. Generate Invoice) use MessColumnManager in DOM mode only. */
        if ($(this).closest('#addProcessMessBillsModal').length) {
            return;
        }

        var $table = $('#' + tableId);
        var skip = parseIndexList($table.data('mess-column-skip'));
        var locked = parseIndexList($table.data('mess-column-locked'));
        var isDataTable = $.fn.DataTable.isDataTable($table);

        if (!isDataTable) {
            stripInvalidTbodyRows($table);
            var dtSettings = {
                ordering: true,
                order: [],
                pageLength: 10,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                responsive: false,
                autoWidth: false,
                columnDefs: buildColumnDefs(skip),
                search: { smart: false },
                dom: '<"row align-items-center mb-2"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row align-items-center mt-2"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                language: {
                    search: '',
                    searchPlaceholder: 'Search...',
                    lengthMenu: 'Show _MENU_ entries',
                    info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                    infoEmpty: 'No entries',
                    emptyTable: 'No data available',
                    infoFiltered: '(filtered from _MAX_ total)',
                    paginate: { first: 'First', last: 'Last', next: 'Next', previous: 'Previous' }
                },
                initComplete: function (settings) {
                    if (typeof window.messDataTableBindSearchInputTrim === 'function') {
                        try {
                            window.messDataTableBindSearchInputTrim(new $.fn.dataTable.Api(settings));
                        } catch (e) {}
                    }
                },
                drawCallback: function () {
                    if (typeof window.adjustAllDataTables === 'function') {
                        try { window.adjustAllDataTables(); } catch (e) {}
                    }
                }
            };
            dtSettings._messCustomMultiWordSearch = true;
            $table.DataTable(dtSettings);

            $table.closest('.card-body, .datatables').find('.pagination').closest('nav, ul.pagination, .d-flex').addClass('d-none');
            isDataTable = true;
        }

        if (typeof window.MessColumnManager === 'undefined') {
            return;
        }

        if (window.MessColumnManager.get(tableId)) {
            return;
        }

        var initOptions = {
            tableId: tableId,
            $table: $table,
            colReorder: false,
            lockedColumns: locked,
            skipColumns: skip
        };

        if (isDataTable) {
            initOptions.mode = 'datatable';
            initOptions.dtApi = $table.DataTable();
        } else {
            initOptions.mode = 'dom';
        }

        window.MessColumnManager.init(initOptions);
    });
});
</script>
@endpush
