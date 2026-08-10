@extends('admin.layouts.master')

@section('title', 'Update Meter Details')

@php
    $estateSelfHomeTab = request('scope') === 'self'
        && (isEstateAuthority());

    $qYear = request('bill_year');
    $qMonth = request('bill_month');
    $filterDefaultYear = ($qYear !== null && $qYear !== '' && is_numeric($qYear)) ? (int) $qYear : now()->year;
    $filterDefaultMonth = ($qMonth !== null && $qMonth !== '' && is_numeric($qMonth) && (int) $qMonth >= 1 && (int) $qMonth <= 12)
        ? (int) $qMonth
        : now()->month;

    $canUpdateReadingAndMeterNo = isEstateAuthority();
@endphp
@section($estateSelfHomeTab ? 'content' : 'setup_content')
<div class="container-fluid rfe-page umn-page">
    <x-breadcrum title="Update Meter Details" :showBack="false">
        @if($canUpdateReadingAndMeterNo)
            <a href="{{ route('admin.estate.update-meter-reading') }}"
                class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 rounded-1 fw-semibold text-nowrap shadow-sm">
                <i class="bi bi-plus-lg" aria-hidden="true"></i>
                <span>Update Reading &amp; Meter Number</span>
            </a>
        @endif
    </x-breadcrum>

    <x-session_message />

    {{-- Exports sit above the card (docs/new-design-index-page.md §1). Both honour
         the applied Bill Year / Bill Month, the search box and the Columns choice. --}}
    <div class="d-flex flex-wrap align-items-center justify-content-end gap-2 mb-3">
        <button type="button" class="btn rfe-export-btn border-0" id="umnDownloadBtn">
            <i class="bi bi-download" aria-hidden="true"></i>
            <span>Download</span>
        </button>
        <button type="button" class="btn rfe-export-btn border-0" id="umnPrintBtn">
            <i class="bi bi-printer" aria-hidden="true"></i>
            <span>Print</span>
        </button>
    </div>

    <div class="card overflow-hidden rounded-1">
        <div class="card-body p-3 p-md-4">
            <div id="updateMeterNoCardBody">

                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar">
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <span class="programme-dt-filters-label">Filter</span>

                        <div class="programme-dt-filter-select">
                            <select id="filterBillYear" class="form-select" aria-label="Filter by bill year">
                                <option value="">Bill Year</option>
                                @foreach($billYears ?? [] as $year)
                                    <option value="{{ $year }}" {{ (int) $year === $filterDefaultYear ? 'selected' : '' }}>{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="programme-dt-filter-select">
                            <select id="filterBillMonth" class="form-select" aria-label="Filter by bill month">
                                <option value="">Bill Month</option>
                                @foreach(range(1, 12) as $m)
                                    <option value="{{ $m }}" {{ $m === $filterDefaultMonth ? 'selected' : '' }}>
                                        {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <button type="button" id="umnClearFilter" class="btn programme-dt-btn-reset">
                            Remove Filter
                        </button>
                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                        <button type="button" class="btn programme-dt-btn-columns" id="umnBtnColumns"
                            data-bs-toggle="modal" data-bs-target="#umnColumnVisibilityModal"
                            title="Show / hide columns">
                            <span>Columns</span>
                            <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                        </button>
                        {{-- Search is the toggle variant (docs/new-design-index-page.md §2):
                             two filters plus a 300px search box do not fit this toolbar. --}}
                        <button type="button" class="btn pd-search-toggle" id="umnSearchToggle"
                            aria-expanded="false" aria-controls="umnDtSearch" title="Search">
                            <i class="bi bi-search" aria-hidden="true"></i>
                            <span class="visually-hidden">Search</span>
                        </button>
                        <div id="umnDtSearch" class="programme-dt-search d-none" data-dt-search-for="updateMeterNoTable"></div>
                    </div>
                </div>

                <div class="programme-dt-panel">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0 w-100 programme-dt-table" id="updateMeterNoTable">
                            <thead>
                                <tr>
                                    <th>S. No.</th>
                                    <th>Name &amp; ID</th>
                                    <th>Employee Type</th>
                                    <th>Unit Type</th>
                                    <th>Unit Sub Type</th>
                                    <th>Building Name</th>
                                    <th>House Number</th>
                                    <th>Old Meter 1 No.</th>
                                    <th>New Meter 1 No.</th>
                                    <th>Old Meter 2 No.</th>
                                    <th>New Meter 2 No.</th>
                                    <th>Old Meter 1 Reading</th>
                                    <th>New Meter 1 Reading</th>
                                    <th>Old Meter 2 Reading</th>
                                    <th>New Meter 2 Reading</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    {{-- Left empty on purpose: datatable-global-ui.js fills this with the
                         pagination and the "Showing [10] of N items" count. --}}
                    <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                        data-dt-footer-for="updateMeterNoTable"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="umnColumnVisibilityModal" tabindex="-1" aria-labelledby="umnColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="umnColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="umnColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-1 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/estate-request-admin.css') }}?v={{ @filemtime(public_path('css/estate-request-admin.css')) ?: time() }}">
@endpush

@push('scripts')
<script>
$(function() {
    var $table = $('#updateMeterNoTable');

    // The endpoint only returns rows when BOTH bill year and month are given.
    var filters = {
        bill_year: ($('#filterBillYear').val() || '').trim(),
        bill_month: ($('#filterBillMonth').val() || '').trim()
    };

    var table = $table.DataTable({
        processing: true,
        serverSide: true,
        deferLoading: 0,
        ajax: {
            url: "{{ route('admin.estate.update-meter-no.list') }}",
            data: function(d) {
                if (filters.bill_year && filters.bill_month) {
                    d.bill_year = filters.bill_year;
                    d.bill_month = filters.bill_month;
                }
            }
        },
        columns: [
            { data: 'sn' },
            {
                data: null,
                render: function(row) {
                    // "Name & ID": name in ink, employee id as a muted suffix.
                    var name = (row.name && row.name !== 'N/A') ? row.name : '';
                    var id = row.employee_id || '';
                    if (!name && !id) return '<span class="rfe-muted">-</span>';
                    var html = name ? '<span class="rfe-name">' + $('<i>').text(name).html() + '</span>' : '';
                    if (id) {
                        html += (html ? ' ' : '') + '<span class="rfe-emp-id">' + (html ? '- ' : '') + $('<i>').text(id).html() + '</span>';
                    }
                    return html;
                }
            },
            { data: 'employee_type', defaultContent: '-' },
            { data: 'unit_type', defaultContent: '-' },
            { data: 'unit_sub_type', defaultContent: '-' },
            { data: 'building_name', defaultContent: '-' },
            { data: 'house_no', defaultContent: '-' },
            { data: 'old_meter1_no', defaultContent: '-' },
            { data: 'new_meter1_no', defaultContent: '-' },
            { data: 'old_meter2_no', defaultContent: '-' },
            { data: 'new_meter2_no', defaultContent: '-' },
            { data: 'old_meter1_reading', defaultContent: '-' },
            { data: 'new_meter1_reading', defaultContent: '-' },
            { data: 'old_meter2_reading', defaultContent: '-' },
            { data: 'new_meter2_reading', defaultContent: '-' }
        ],
        // The list endpoint fills blanks with an em dash; the design uses a plain
        // hyphen. Normalise on the way in rather than changing the shared mapper,
        // which the older screens still render.
        columnDefs: [{
            targets: [2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14],
            render: function(data) {
                return (data === null || data === '' || data === '—') ? '-' : data;
            }
        }],
        order: [[0, 'desc']],
        responsive: false,
        autoWidth: false
        // dom / language / pageLength come from datatable-global-ui.js.
    });

    function reload() {
        filters.bill_year = ($('#filterBillYear').val() || '').trim();
        filters.bill_month = ($('#filterBillMonth').val() || '').trim();
        // The ajax data callback only sends bill_year/bill_month when BOTH are
        // set; with neither the endpoint returns every saved reading, which is
        // exactly what "Remove Filter" should show. Always re-query — clear()
        // would blank the rows but leave the previous count in the footer.
        table.ajax.reload();
    }

    $('#filterBillYear, #filterBillMonth').on('change', reload);

    $('#umnClearFilter').on('click', function() {
        $('#filterBillYear').val('');
        $('#filterBillMonth').val('');
        table.search('');
        reload();
    });

    if (filters.bill_year && filters.bill_month) {
        table.ajax.reload();
    }

    /* ---------- Search toggle ---------- */
    $('#umnSearchToggle').on('click', function() {
        var $slot = $('#umnDtSearch');
        var opening = $slot.hasClass('d-none');
        $slot.toggleClass('d-none', !opening);
        $(this).attr('aria-expanded', opening ? 'true' : 'false');
        if (opening) {
            $slot.find('input').trigger('focus');
        } else if (table.search()) {
            // Collapsing clears the query — a hidden active filter is a trap.
            table.search('').draw();
            $slot.find('input').val('');
        }
    });

    /* ---------- Column visibility (persisted per browser, per user) ---------- */
    // Header index -> export column key. POSITIONAL: adding a table column means
    // adding an entry here too.
    var UMN_EXPORT_COLUMN_KEYS = ['sno', 'name_id', 'employee_type', 'unit_type', 'unit_sub_type',
        'building_name', 'house_no', 'old_meter1_no', 'new_meter1_no', 'old_meter2_no', 'new_meter2_no',
        'old_meter1_reading', 'new_meter1_reading', 'old_meter2_reading', 'new_meter2_reading'];
    var umnColStorageKey = 'sargam.updateMeterNo.hiddenCols.{{ auth()->id() ?? 'guest' }}';

    function umnGetHiddenCols() {
        try {
            var raw = localStorage.getItem(umnColStorageKey);
            var arr = raw ? JSON.parse(raw) : [];
            return Array.isArray(arr) ? arr : [];
        } catch (e) {
            return []; // private mode / storage disabled / corrupt value
        }
    }

    function umnPersistHiddenCols(arr) {
        try { localStorage.setItem(umnColStorageKey, JSON.stringify(arr)); } catch (e) {}
    }

    function umnVisibleExportCols() {
        var hidden = umnGetHiddenCols();
        return UMN_EXPORT_COLUMN_KEYS.filter(function(key, idx) {
            return hidden.indexOf(idx) === -1;
        });
    }

    function setupUmnColumns() {
        var hidden = umnGetHiddenCols();

        table.columns().every(function() {
            var idx = this.index();
            this.visible(hidden.indexOf(idx) === -1, false);
        });
        table.columns.adjust();

        var $grid = $('#umnColumnToggleGrid');
        if (!$grid.length) return;
        $grid.empty();

        table.columns().every(function() {
            var idx = this.index();
            var title = $(this.header()).text().replace(/\s+/g, ' ').trim();
            if (!title) return;

            var inputId = 'umncolvis_' + idx;
            var $cell = $('<div class="col-12 col-sm-6"></div>');
            var $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-1 px-3 py-2 mb-0 w-100"></label>')
                .attr('for', inputId);
            var $cb = $('<input type="checkbox" class="form-check-input m-0">')
                .attr('id', inputId)
                .prop('checked', hidden.indexOf(idx) === -1);

            $cb.on('change', function() {
                var h = umnGetHiddenCols();
                var pos = h.indexOf(idx);
                if (this.checked) {
                    if (pos !== -1) h.splice(pos, 1);
                } else {
                    if (pos === -1) h.push(idx);
                }
                umnPersistHiddenCols(h);
                table.column(idx).visible(this.checked, false);
                table.columns.adjust();
            });

            $label.append($cb).append($('<span></span>').text(title));
            $cell.append($label);
            $grid.append($cell);
        });
    }

    setupUmnColumns();

    /* ---------- Download / Print ---------- */
    function umnExportParams() {
        var params = {};
        if (filters.bill_year) params.bill_year = filters.bill_year;
        if (filters.bill_month) params.bill_month = filters.bill_month;
        var searchValue = table.search();
        if (searchValue) params.search = searchValue;
        params.cols = umnVisibleExportCols().join(',');
        return params;
    }

    $('#umnDownloadBtn').on('click', function() {
        window.location.href = '{{ route('admin.estate.update-meter-no.export') }}?' + $.param(umnExportParams());
    });

    $('#umnPrintBtn').on('click', function() {
        window.open('{{ route('admin.estate.update-meter-no.print') }}?' + $.param(umnExportParams()), '_blank');
    });
});
</script>
@endpush
