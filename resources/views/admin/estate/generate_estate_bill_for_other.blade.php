@extends('admin.layouts.master')

@section('title', 'Generate Estate Bill for Other')

@section('setup_content')
@push('styles')
<style>
    /* ── Generate Estate Bill for Other — page-scoped chrome on top of
       programme-dt. Namespaced under .gebo-page so nothing leaks
       (new-design-index-page.md §7); values come from the --ds-* tokens
       (design.md Layer A). */
    .gebo-page .gebo-secondary-actions .btn i {
        font-size: 1rem;
        line-height: 1;
    }

    .gebo-page .gebo-select-all {
        color: var(--ds-primary, #004a93);
        font-weight: 500;
        cursor: pointer;
    }

    /* Search reveal button — square, brand-outlined, matches the Columns height. */
    .gebo-page .gebo-search-toggle {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: var(--ds-control-h, 2.5rem);
        height: var(--ds-control-h, 2.5rem);
        padding: 0;
        color: var(--ds-primary, #004a93);
        background: var(--ds-surface, #fff);
        border: 1px solid var(--ds-line, #d0d5dd);
        border-radius: var(--ds-radius, 4px);
    }

    .gebo-page .gebo-search-toggle[aria-expanded="true"],
    .gebo-page .gebo-search-toggle:hover {
        border-color: var(--ds-primary, #004a93);
        background: #f2f7fc;
    }

    .gebo-page .programme-dt-filter-select .form-select {
        min-height: var(--ds-control-h, 2.5rem);
    }

    /* The request number sits under the name, as the design shows. */
    .gebo-page .gebo-sub {
        display: block;
        font-size: 0.6875rem;
        color: var(--ds-ink-muted, #667085);
    }

    /* Two-meter rows render one value per line, so those cells must wrap. */
    .gebo-page .programme-dt-table td.gebo-col-lines {
        white-space: normal;
    }

    /* The two widest text columns wrap rather than stretch the table sideways. */
    .gebo-page .programme-dt-table td.gebo-col-wrap {
        white-space: normal;
        max-width: 220px;
    }

    .gebo-page .gebo-empty {
        color: var(--ds-ink-muted, #667085);
    }

    /* An un-initialised table still owns the footer slot; keep the empty slot
       from leaving a gap under the panel. */
    .gebo-page .programme-dt-footer:empty {
        display: none;
    }

    /* Pager reads as arrows + numbers, per the design: full_numbers still emits
       First/Last, which the mock doesn't show. */
    .gebo-page .programme-dt-footer .paginate_button.first,
    .gebo-page .programme-dt-footer .paginate_button.last {
        display: none;
    }

    /* DataTables Responsive would inject a control column and shift every
       Column-Visibility index; the .table-responsive wrapper handles overflow. */
    .gebo-page .programme-dt-table td.dtr-control,
    .gebo-page .programme-dt-table th.dtr-control {
        display: none;
    }
</style>
@endpush

<div class="container-fluid px-2 px-sm-3 px-md-4 gebo-page">
    <x-breadcrum title="Generate Estate Bill for Other"></x-breadcrum>
    <x-session_message />

    {{-- No status pills on this grid, so Select All sits left and the export row
         right (new-design-index-page.md §1). Print is the server-rendered view,
         not window.print(), so the printout and the Excel can't drift apart. --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 gebo-secondary-actions">
        <div class="form-check mb-0 d-flex align-items-center gap-2">
            <input class="form-check-input mt-0" type="checkbox" id="check_all_bills" aria-describedby="check_all_help">
            <label class="form-check-label gebo-select-all" for="check_all_bills">Select All</label>
            <span id="check_all_help" class="visually-hidden">Select or clear every bill row</span>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            {{-- Bulk action is revealed by a selection, as on Generate Estate
                 Bill; it prints the selected BILL DOCUMENTS, not the grid. --}}
            <button type="button" class="btn programme-dt-btn-columns border-0 text-primary d-none" id="geboPrintSelected"
                title="Print the selected bills in a single tab">
                <i class="bi bi-printer" aria-hidden="true"></i>
                <span>Print Selected</span>
            </button>
            <button type="button" class="btn programme-dt-btn-columns border-0 text-primary" id="geboDownloadBtn"
                title="Download as Excel">
                <i class="bi bi-download" aria-hidden="true"></i>
                <span>Download</span>
            </button>
            <button type="button" class="btn programme-dt-btn-columns border-0 text-primary" id="geboPrintBtn"
                title="Print this list">
                <i class="bi bi-printer" aria-hidden="true"></i>
                <span>Print</span>
            </button>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body p-3 p-md-4">
            {{-- Toolbar: filters left · Columns + search right (§2) --}}
            <div
                class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Filter</span>

                    <div class="programme-dt-filter-select">
                        <select class="form-select" id="bill_month" name="bill_month" aria-label="Bill Month">
                            <option value="">Bill Month</option>
                            @foreach($billMonthOptions ?? [] as $value => $label)
                            <option value="{{ $value }}" @selected($loop->first)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="button" class="btn programme-dt-btn-reset" id="geboResetFilters">Remove Filter</button>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" data-bs-toggle="modal"
                        data-bs-target="#geboColumnModal" title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>

                    {{-- Toggle variant (§2): the icon reveals the DataTables search
                         slot, which datatable-global-ui.js fills for us. --}}
                    <button type="button" class="btn gebo-search-toggle" id="geboSearchToggle" aria-expanded="false"
                        aria-controls="geboDtSearch" title="Search bills">
                        <i class="bi bi-search" aria-hidden="true"></i>
                    </button>
                    <div id="geboDtSearch" class="programme-dt-search d-none" data-dt-search-for="billForOtherTable">
                    </div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 w-100 programme-dt-table" id="billForOtherTable">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 40px;">
                                    <input type="checkbox" id="billForOtherCheckAll" class="form-check-input"
                                        title="Select all" aria-label="Select all rows">
                                </th>
                                <th>S. No.</th>
                                <th>Name &amp; ID</th>
                                <th>Section Name</th>
                                <th>House Number</th>
                                <th>From Date</th>
                                <th>To Date</th>
                                <th>Meter No.</th>
                                <th>Previous Meter Reading</th>
                                <th>Current Meter Reading</th>
                                <th>Unit Consumed</th>
                                <th>Total Charge</th>
                                <th>Licence Fee</th>
                                <th>Water Fee</th>
                                <th>Grand Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr id="noDataRow">
                                <td colspan="15" class="text-center gebo-empty py-4">Select a Bill Month to load bills.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- DataTables paginates, so the footer is an empty slot the global
                 UI script fills (§4A). --}}
            <div id="geboDtFooter"
                class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                data-dt-footer-for="billForOtherTable"></div>
        </div>
    </div>

    {{-- Column Visibility (column-visibility.md — colvis-item card grid) --}}
    <div class="modal fade" id="geboColumnModal" tabindex="-1" aria-labelledby="geboColumnModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-2">
                    <h5 class="modal-title fw-bold" id="geboColumnModalLabel">Column Visibility</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-0">
                    <hr class="mt-0">
                    <div class="row g-3" id="geboColumnToggleGrid"></div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-primary rounded-3 px-4"
                        data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(function () {
        var dataUrl = @json(route('admin.estate.generate-estate-bill-for-other.data'));
        var downloadUrl = @json(route('admin.estate.generate-estate-bill-for-other.download'));
        var printUrl = @json(route('admin.estate.generate-estate-bill-for-other.print'));
        var printAllUrl = @json(route('admin.estate.reports.bill-report-print-all'));

        // Header index -> the export's column key. Positional: adding a column
        // means editing this array AND the empty-state colspan
        // (column-visibility.md §2). '' = a column the export doesn't carry.
        var GEBO_EXPORT_KEYS = ['', 'sno', 'name_id', 'section', 'house_no', 'from_date', 'to_date',
            'meter_no', 'prev_reading', 'curr_reading', 'unit_consumed', 'total_charge', 'licence_fee',
            'water_charges', 'grand_total'
        ];

        // Hidden columns are remembered by LABEL, never index — a label that no
        // longer matches any header is ignored, so a renamed column comes back
        // visible instead of hiding a different one (column-visibility.md §3).
        var GEBO_COLVIS_KEY = 'sargam.generateBillForOther.hiddenCols.' + @json(auth()->id() ?? 'guest');

        var dt = null;

        function readHiddenCols() {
            try {
                var raw = window.localStorage.getItem(GEBO_COLVIS_KEY);
                var arr = raw ? JSON.parse(raw) : [];
                return Array.isArray(arr) ? arr : [];
            } catch (e) {
                return [];
            }
        }

        function saveHiddenCols() {
            var hidden = [];
            $('#geboColumnToggleGrid .gebo-col-toggle').each(function () {
                if (!this.checked) hidden.push($(this).data('label'));
            });
            try {
                window.localStorage.setItem(GEBO_COLVIS_KEY, JSON.stringify(hidden));
            } catch (e) { /* private mode — the choice just won't persist */ }
        }

        function escapeHtml(str) {
            if (str == null || str === '') return '';
            return $('<div>').text(String(str)).html();
        }

        /** '1620' -> '₹ 1,620.00'; blanks stay as the grid's dash. */
        function formatMoney(n) {
            if (n == null || n === '' || isNaN(n)) return '—';
            return '₹ ' + parseFloat(n).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
        }

        /** Newline-joined values (one line per meter) become <br>-joined cells. */
        function multiLine(value) {
            var text = String(value == null ? '' : value);
            if (text === '') return '—';
            return text.split('\n').map(escapeHtml).join('<br>');
        }

        /** Export links carry what the user is looking at: filters, search, visible columns. */
        function exportQuery() {
            var params = new URLSearchParams({ bill_month: $('#bill_month').val() || '' });
            params.set('search', dt ? (dt.search() || '') : '');
            var cols = [];
            $('#billForOtherTable thead th').each(function (i) {
                var key = GEBO_EXPORT_KEYS[i];
                if (!key) return;
                if (!dt || dt.column(i).visible()) cols.push(key);
            });
            if (cols.length) params.set('cols', cols.join(','));
            return params.toString();
        }

        function buildColumnToggles() {
            if (!dt) return;
            var $grid = $('#geboColumnToggleGrid');
            if (!$grid.length) return;
            $grid.empty();

            dt.columns().every(function (i) {
                var col = this;
                var label = $(col.header()).text().trim();
                if (!label) return; // the checkbox column has no label, so no chip

                var inputId = 'geboColVis_' + i;
                var $cb = $('<input type="checkbox" class="form-check-input m-0 gebo-col-toggle">')
                    .attr({ id: inputId, 'data-column': i, 'data-label': label })
                    .prop('checked', col.visible());

                $cb.on('change', function () {
                    dt.column(i).visible(this.checked);
                    saveHiddenCols();
                });

                $grid.append(
                    $('<div class="col-12 col-sm-6 col-md-4"></div>').append(
                        $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                            .attr('for', inputId)
                            .append($cb)
                            .append($('<span></span>').text(label))
                    )
                );
            });
        }

        function applySavedVisibility() {
            var hidden = readHiddenCols();
            if (!hidden.length || !dt) return;
            dt.columns().every(function (i) {
                var label = $(this.header()).text().trim();
                if (label && hidden.indexOf(label) !== -1) this.visible(false, false);
            });
            dt.columns.adjust().draw(false);
        }

        function destroyTable() {
            if (dt && $.fn.DataTable.isDataTable('#billForOtherTable')) {
                dt.destroy();
            }
            dt = null;
        }

        function rowHtml(row) {
            var pk = row.pk != null ? row.pk : '';
            var name = escapeHtml(row.name || '—');
            if (row.request_no) {
                name += '<span class="gebo-sub">- ' + escapeHtml(row.request_no) + '</span>';
            }

            return '<tr>' +
                '<td class="text-center"><input type="checkbox" class="form-check-input bill-row-check"' +
                ' value="' + escapeHtml(pk) + '" data-pk="' + escapeHtml(pk) + '"' +
                ' aria-label="Select this bill"></td>' +
                '<td>' + escapeHtml(row.sno || '') + '</td>' +
                '<td class="gebo-col-wrap">' + name + '</td>' +
                '<td class="gebo-col-wrap">' + escapeHtml(row.section || '—') + '</td>' +
                '<td>' + escapeHtml(row.house_no || '—') + '</td>' +
                '<td data-order="' + escapeHtml(row.from_date_sort || '') + '">' + escapeHtml(row.from_date || '—') + '</td>' +
                '<td data-order="' + escapeHtml(row.to_date_sort || '') + '">' + escapeHtml(row.to_date || '—') + '</td>' +
                '<td class="gebo-col-lines">' + multiLine(row.meter_no) + '</td>' +
                '<td class="gebo-col-lines">' + multiLine(row.prev_reading) + '</td>' +
                '<td class="gebo-col-lines">' + multiLine(row.curr_reading) + '</td>' +
                '<td>' + escapeHtml(row.unit_consumed != null ? row.unit_consumed : '—') + '</td>' +
                '<td data-order="' + (row.total_charge || 0) + '">' + formatMoney(row.total_charge) + '</td>' +
                '<td data-order="' + (row.licence_fee || 0) + '">' + formatMoney(row.licence_fee) + '</td>' +
                '<td data-order="' + (row.water_charges || 0) + '">' + formatMoney(row.water_charges) + '</td>' +
                '<td class="fw-semibold" data-order="' + (row.grand_total || 0) + '">' + formatMoney(row.grand_total) + '</td>' +
                '</tr>';
        }

        function loadBillForOther() {
            var billMonth = $('#bill_month').val();
            if (!billMonth) return;

            destroyTable();
            $('#billForOtherTable tbody').html(
                '<tr id="noDataRow"><td colspan="15" class="text-center gebo-empty py-4">Loading…</td></tr>'
            );
            syncSelectionUi();

            $.ajax({
                url: dataUrl,
                type: 'GET',
                data: { bill_month: billMonth },
                dataType: 'json',
                success: function (res) {
                    destroyTable();
                    var data = (res && res.data) ? res.data : [];
                    var tbody = $('#billForOtherTable tbody');
                    tbody.empty();

                    if (!data.length) {
                        tbody.append(
                            '<tr id="noDataRow"><td colspan="15" class="text-center gebo-empty py-4">' +
                            'No bills for the selected month.</td></tr>'
                        );
                        syncSelectionUi();
                        return;
                    }

                    tbody.append(data.map(rowHtml).join(''));

                    // No dom / language / lengthMenu here: datatable-global-ui.js
                    // owns those, and hand-rolling them breaks the chrome
                    // relocation (new-design-index-page.md §3, §5).
                    dt = $('#billForOtherTable').DataTable({
                        order: [[1, 'asc']],
                        autoWidth: false,
                        responsive: false,
                        columnDefs: [{ targets: 0, orderable: false, searchable: false }]
                    });

                    buildColumnToggles();
                    applySavedVisibility();
                    // Rebuild after restore so the checkboxes mirror what is showing.
                    buildColumnToggles();
                    syncSelectionUi();

                    // Paging replaces the rows, so the header checkbox has to be
                    // re-read against whatever is on screen now.
                    dt.on('draw', syncSelectionUi);
                },
                error: function () {
                    destroyTable();
                    $('#billForOtherTable tbody').html(
                        '<tr id="noDataRow"><td colspan="15" class="text-center text-danger py-4">' +
                        'Failed to load data. Please try again.</td></tr>'
                    );
                    syncSelectionUi();
                }
            });
        }

        function syncSelectionUi() {
            // Off-page rows live in the DataTable's node cache, not the DOM, so a
            // DOM-only count would hide "Print Selected" the moment the user
            // pages away from their selection.
            var $all = dt ? $(dt.rows().nodes()).find('.bill-row-check')
                : $('#billForOtherTable .bill-row-check');
            var checked = $all.filter(':checked').length;
            var allChecked = $all.length > 0 && $all.length === checked;
            $('#billForOtherCheckAll').prop('checked', allChecked);
            $('#check_all_bills').prop('checked', allChecked);
            $('#geboPrintSelected').toggleClass('d-none', checked === 0);
        }

        function setAllChecked(checked) {
            // Off-page rows live in the DataTable's node cache, not the DOM, so
            // select-all has to reach through the API to cover every page.
            if (dt) {
                $(dt.rows().nodes()).find('.bill-row-check').prop('checked', checked);
            } else {
                $('#billForOtherTable .bill-row-check').prop('checked', checked);
            }
            syncSelectionUi();
        }

        $('#billForOtherCheckAll, #check_all_bills').on('change', function () {
            setAllChecked(this.checked);
        });

        $(document).on('change', '#billForOtherTable .bill-row-check', syncSelectionUi);

        $('#bill_month').on('change', loadBillForOther);

        $('#geboResetFilters').on('click', function () {
            $('#bill_month').prop('selectedIndex', 1);
            if (dt) dt.search('');
            loadBillForOther();
        });

        $('#geboSearchToggle').on('click', function () {
            var $wrap = $('#geboDtSearch');
            var open = $wrap.hasClass('d-none');
            $wrap.toggleClass('d-none', !open);
            $(this).attr('aria-expanded', open ? 'true' : 'false');
            if (open) $wrap.find('input').trigger('focus');
        });

        function requireMonth() {
            if ($('#bill_month').val()) return true;
            if (window.Swal) {
                Swal.fire({ icon: 'info', title: 'Select a Bill Month first.' });
            } else {
                window.alert('Select a Bill Month first.');
            }
            return false;
        }

        $('#geboDownloadBtn').on('click', function () {
            if (!requireMonth()) return;
            window.location.href = downloadUrl + '?' + exportQuery();
        });

        $('#geboPrintBtn').on('click', function () {
            if (!requireMonth()) return;
            window.open(printUrl + '?' + exportQuery(), '_blank', 'noopener');
        });

        // Print Selected keeps the page's original feature: the A4 BILL documents
        // for the ticked rows, not the grid.
        $('#geboPrintSelected').on('click', function () {
            var pks = [];
            var $rows = dt ? $(dt.rows().nodes()).find('.bill-row-check:checked')
                : $('#billForOtherTable .bill-row-check:checked');
            $rows.each(function () {
                var pk = ($(this).data('pk') || '').toString().trim();
                if (pk) pks.push(pk);
            });
            if (!pks.length) return;

            window.open(
                printAllUrl +
                '?bill_month=' + encodeURIComponent(($('#bill_month').val() || '').toString().trim()) +
                '&selected_pks=' + encodeURIComponent(pks.join(',')) +
                '&is_other=1',
                '_blank', 'noopener'
            );
        });

        // A month is preselected, so load straight away.
        if ($('#bill_month').val()) loadBillForOther();
    });
</script>
@endpush
