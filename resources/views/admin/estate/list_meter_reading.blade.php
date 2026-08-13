@extends('admin.layouts.master')

@section('title', 'List Meter Reading')

@section('setup_content')
@push('styles')
<style>
    /* ── List Meter Reading — page-scoped chrome on top of programme-dt ──
       Namespaced under .lmr-page so nothing leaks (new-design-index-page.md §7).
       Values come from the --ds-* tokens (design.md Layer A). */
    .lmr-page .lmr-secondary-actions .btn i {
        font-size: 1rem;
        line-height: 1;
    }

    /* Row actions — icon over caption, all stacks the same width so the
       glyph row stays on one baseline (new-design-index-page.md §3b). */
    .lmr-page .lmr-act-group {
        display: inline-flex;
        align-items: stretch;
        gap: var(--ds-space-1, 0.25rem);
    }

    .lmr-page .lmr-act {
        display: inline-flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-start;
        gap: 4px;
        min-width: 62px;
        margin: 0;
        padding: 0;
        border: 0;
        background: transparent;
        font-size: 0.72rem;
        font-weight: 500;
        line-height: 1;
        text-decoration: none;
        cursor: pointer;
    }

    .lmr-page .lmr-act__icon {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 22px;
    }

    .lmr-page .lmr-act__icon > i {
        font-size: 1.1rem;
        line-height: 1;
    }

    .lmr-page .lmr-act__label {
        white-space: nowrap;
    }

    .lmr-page .lmr-act--edit {
        color: #2563eb;
    }

    .lmr-page .lmr-act--edit:hover {
        color: var(--ds-primary, #004a93);
    }

    /* The grid is wide; let the two widest text columns wrap rather than
       stretch the table sideways (only column 2 wraps by default — §3). */
    .lmr-page .programme-dt-table td.lmr-col-wrap,
    .lmr-page .programme-dt-table th.lmr-col-wrap {
        white-space: normal;
        max-width: 260px;
    }

    .lmr-page .lmr-empty {
        color: var(--ds-ink-muted, #667085);
    }

    /* A serverSide table with no rows still owns the footer slot; keep the
       "no data" line from colliding with the pager. */
    .lmr-page .programme-dt-footer:empty {
        display: none;
    }

    /* Pager reads as arrows + numbers, per the design: full_numbers still
       emits First/Last, which the mock doesn't show. */
    .lmr-page .programme-dt-footer .paginate_button.first,
    .lmr-page .programme-dt-footer .paginate_button.last {
        display: none;
    }

    /* DataTables Responsive is on by default here and injects a control
       column; the .table-responsive wrapper already handles overflow, and the
       extra column would shift every Column-Visibility index. */
    .lmr-page .programme-dt-table td.dtr-control,
    .lmr-page .programme-dt-table th.dtr-control {
        display: none;
    }
</style>
@endpush

<div class="container-fluid px-2 px-sm-3 px-md-4 lmr-page">
    <x-breadcrum title="List Meter Reading" :showBack="false" />

    <x-session_message />

    {{-- No status pills on this grid, so the export row sits alone on the right
         (new-design-index-page.md §1). Print is the server-rendered view, not
         window.print(), so the printout and the Excel can't drift apart. --}}
    <div class="d-flex flex-wrap justify-content-end gap-2 mb-3 lmr-secondary-actions">
        <button type="button" class="btn programme-dt-btn-columns border-0 text-primary" id="lmrDownloadBtn"
            title="Download as Excel">
            <i class="bi bi-download" aria-hidden="true"></i>
            <span>Download</span>
        </button>
        <button type="button" class="btn programme-dt-btn-columns border-0 text-primary" id="lmrPrintBtn" title="Print">
            <i class="bi bi-printer" aria-hidden="true"></i>
            <span>Print</span>
        </button>
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

                    <div class="programme-dt-filter-select">
                        <select class="form-select" id="employee_type" name="employee_type" aria-label="Employee Type">
                            <option value="LBSNAA">LBSNAA</option>
                            <option value="Other Employee">Other Employee</option>
                        </select>
                    </div>

                    <div class="programme-dt-filter-select">
                        <select class="form-select" id="block_id" name="block_id" aria-label="Building Name">
                            <option value="all">Building Name</option>
                            @foreach($blocks ?? [] as $b)
                            <option value="{{ $b->pk }}">{{ $b->block_name ?? 'N/A' }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="button" class="btn programme-dt-btn-reset" id="lmrResetFilters">Remove Filter</button>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" data-bs-toggle="modal"
                        data-bs-target="#lmrColumnModal" title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>

                    <div id="lmrDtSearch" class="programme-dt-search" data-dt-search-for="listMeterReadingTable"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 w-100 programme-dt-table"
                        id="listMeterReadingTable">
                        <thead>
                            <tr>
                                <th>S. No.</th>
                                <th>Name &amp; ID</th>
                                <th>Designation</th>
                                <th>Section</th>
                                <th>Unit Type</th>
                                <th>Unit Sub Type</th>
                                <th>Building Name</th>
                                <th>House Number</th>
                                <th>Meter 1 Reading</th>
                                <th>Meter 2 Reading</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr id="noDataRow">
                                <td colspan="11" class="text-center lmr-empty py-4">Select a Bill Month to load
                                    readings.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- DataTables paginates, so the footer is an empty slot the global
                 UI script fills (§4A). --}}
            <div id="lmrDtFooter"
                class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                data-dt-footer-for="listMeterReadingTable"></div>
        </div>
    </div>

    {{-- Column Visibility (column-visibility.md — colvis-item card grid) --}}
    <div class="modal fade" id="lmrColumnModal" tabindex="-1" aria-labelledby="lmrColumnModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-2">
                    <h5 class="modal-title fw-bold" id="lmrColumnModalLabel">Column Visibility</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-0">
                    <hr class="mt-0">
                    <div class="row g-3" id="lmrColumnToggleGrid"></div>
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
        var dataUrl = @json(route('admin.estate.list-meter-reading.data'));
        var downloadUrl = @json(route('admin.estate.list-meter-reading.download'));
        var printUrl = @json(route('admin.estate.list-meter-reading.print'));

        // Header index -> the export's column key. Positional: adding a column
        // means editing this array AND the empty-state colspan
        // (column-visibility.md §2). '' = a column the export doesn't carry.
        var LMR_EXPORT_KEYS = ['sno', 'name_id', 'designation', 'section', 'unit_type',
            'unit_sub_type', 'building_name', 'house_no', 'meter1_reading', 'meter2_reading', ''
        ];

        // Hidden columns are remembered by LABEL, never index — a label that no
        // longer matches any header is ignored, so a renamed column comes back
        // visible instead of hiding a different one (column-visibility.md §3).
        var LMR_COLVIS_KEY = 'sargam.listMeterReading.hiddenCols.' + @json(auth()->id() ?? 'guest');

        function readHiddenCols() {
            try {
                var raw = window.localStorage.getItem(LMR_COLVIS_KEY);
                var arr = raw ? JSON.parse(raw) : [];
                return Array.isArray(arr) ? arr : [];
            } catch (e) {
                return [];
            }
        }

        function saveHiddenCols() {
            var hidden = [];
            $('#lmrColumnToggleGrid .lmr-col-toggle').each(function () {
                if (!this.checked) hidden.push($(this).data('label'));
            });
            try {
                window.localStorage.setItem(LMR_COLVIS_KEY, JSON.stringify(hidden));
            } catch (e) { /* private mode — the choice just won't persist */ }
        }

        var dt = null;

        function currentFilters() {
            return {
                bill_month: $('#bill_month').val() || '',
                employee_type: $('#employee_type').val() || '',
                block_id: $('#block_id').val() || 'all'
            };
        }

        /** Export links carry what the user is looking at: filters, search, visible columns. */
        function exportQuery() {
            var params = new URLSearchParams(currentFilters());
            params.set('search', dt ? (dt.search() || '') : '');
            var cols = [];
            $('#listMeterReadingTable thead th').each(function (i) {
                var key = LMR_EXPORT_KEYS[i];
                if (!key) return;
                if (!dt || dt.column(i).visible()) cols.push(key);
            });
            if (cols.length) params.set('cols', cols.join(','));
            return params.toString();
        }

        function buildColumnToggles() {
            if (!dt) return;
            var $grid = $('#lmrColumnToggleGrid');
            if (!$grid.length) return;
            $grid.empty();

            dt.columns().every(function (i) {
                var col = this;
                var label = $(col.header()).text().trim();
                if (!label) return; // no label, no chip

                var inputId = 'lmrColVis_' + i;
                var $cb = $('<input type="checkbox" class="form-check-input m-0 lmr-col-toggle">')
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

        function initTable() {
            if (dt) return dt;

            $('#noDataRow').remove();

            // No dom / language / lengthMenu here: datatable-global-ui.js owns
            // those, and hand-rolling them breaks the chrome relocation
            // (new-design-index-page.md §3, §5).
            dt = $('#listMeterReadingTable').DataTable({
                processing: true,
                serverSide: true,
                searching: true,
                searchDelay: 400,
                autoWidth: false,
                responsive: false,
                order: [],
                ajax: {
                    url: dataUrl,
                    data: function (d) {
                        var f = currentFilters();
                        d.bill_month = f.bill_month;
                        d.employee_type = f.employee_type;
                        d.block_id = f.block_id;
                    }
                },
                columns: [
                    { data: 'sno', name: 'sno', orderable: false, searchable: false },
                    { data: 'name', name: 'name', className: 'lmr-col-wrap' },
                    { data: 'designation', name: 'designation', className: 'lmr-col-wrap' },
                    { data: 'section', name: 'section' },
                    { data: 'unit_type', name: 'unit_type' },
                    { data: 'unit_sub_type', name: 'unit_sub_type' },
                    { data: 'building_name', name: 'building_name' },
                    { data: 'house_no', name: 'house_no' },
                    { data: 'meter1_reading', name: 'meter1_reading' },
                    { data: 'meter2_reading', name: 'meter2_reading' },
                    {
                        data: 'edit_url',
                        name: 'edit_url',
                        orderable: false,
                        searchable: false,
                        render: function (data, type) {
                            if (type !== 'display') return '';
                            var url = data || '#';
                            var $a = $('<a class="lmr-act lmr-act--edit" title="Edit reading">')
                                .attr('href', url)
                                .append('<span class="lmr-act__icon"><i class="bi bi-pencil" aria-hidden="true"></i></span>')
                                .append($('<span class="lmr-act__label">').text('Edit'));
                            return $('<div class="lmr-act-group" role="group" aria-label="Row actions">')
                                .append($a).prop('outerHTML');
                        }
                    }
                ]
            });

            dt.on('init.dt', function () {
                buildColumnToggles();
                applySavedVisibility();
                // Rebuild after restore so the checkboxes mirror what is showing.
                buildColumnToggles();
            });

            return dt;
        }

        function reload() {
            if (!$('#bill_month').val()) return;
            if (!dt) { initTable(); return; }
            dt.ajax.reload(null, false);
        }

        $('#bill_month, #employee_type, #block_id').on('change', reload);

        $('#lmrResetFilters').on('click', function () {
            $('#bill_month').prop('selectedIndex', 0);
            $('#employee_type').val('LBSNAA');
            $('#block_id').val('all');
            if (dt) {
                dt.search('');
                reload();
            }
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

        $('#lmrDownloadBtn').on('click', function () {
            if (!requireMonth()) return;
            window.location.href = downloadUrl + '?' + exportQuery();
        });

        $('#lmrPrintBtn').on('click', function () {
            if (!requireMonth()) return;
            window.open(printUrl + '?' + exportQuery(), '_blank', 'noopener');
        });

        // A month is preselected, so load straight away.
        if ($('#bill_month').val()) initTable();
    });
</script>
@endpush
