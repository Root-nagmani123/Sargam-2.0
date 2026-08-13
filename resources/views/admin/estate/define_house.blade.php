@extends('admin.layouts.master')

@section('title', 'Define House')

@section('setup_content')
<div class="container-fluid px-2 px-sm-3 px-md-4 dh-page">
    {{-- The breadcrumb component carries the page title and the primary action.
         Add / Edit are full pages now (define_house_form.blade.php), so this is a
         plain link — no modal, no JS. --}}
    <x-breadcrum title="Define House" :showBack="false" button-text="Add Define House"
        :button-url="route('admin.estate.define-house.create')" button-icon="add"
        button-class="btn btn-primary d-inline-flex align-items-center gap-2" />

    <div id="defineHouseAlerts"></div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    {{-- No status pills on this grid, so the export row sits alone on the right
         (new-design-index-page.md §1). Print is the server-rendered view, not
         window.print(), so the printout and the Excel can't drift apart. --}}
    <div class="d-flex flex-wrap justify-content-end gap-2 mb-3 dh-secondary-actions no-print">
        <button type="button" class="btn programme-dt-btn-columns border-0 text-primary" id="dhDownloadBtn"
            title="Download as Excel">
            <i class="bi bi-download" aria-hidden="true"></i>
            <span>Download</span>
        </button>
        <button type="button" class="btn programme-dt-btn-columns border-0 text-primary" id="dhPrintBtn" title="Print">
            <i class="bi bi-printer" aria-hidden="true"></i>
            <span>Print</span>
        </button>
    </div>

    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body p-3 p-md-4">
            {{-- Toolbar: nothing to filter by on this grid, so Columns + search
                 sit alone on the right (§2). --}}
            <div
                class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-end gap-3 mb-4 programme-dt-toolbar no-print">
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" data-bs-toggle="modal"
                        data-bs-target="#dhColumnModal" title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>

                    {{-- Toggle variant (§2): the icon reveals the DataTables search
                         slot, which datatable-global-ui.js fills for us. --}}
                    <button type="button" class="btn dh-search-toggle" id="dhSearchToggle" aria-expanded="false"
                        aria-controls="dhDtSearch" title="Search houses">
                        <i class="bi bi-search" aria-hidden="true"></i>
                    </button>
                    <div id="dhDtSearch" class="programme-dt-search d-none" data-dt-search-for="defineHouseTable"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive define-house-table-wrapper">
                    <table class="table table-hover align-middle mb-0 w-100 programme-dt-table" id="defineHouseTable">
                        <thead>
                            <tr>
                                <th>S. No.</th>
                                <th>Estate Name</th>
                                <th>Unit Type</th>
                                <th>Building Name</th>
                                <th>Unit Sub Type</th>
                                <th>House Number</th>
                                <th>Meter Number 1</th>
                                <th>Water Charge</th>
                                <th>Electric Charge</th>
                                <th>Licence Fee</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

            {{-- DataTables paginates, so the footer is an empty slot the global
                 UI script fills (§4A). --}}
            <div id="dhDtFooter"
                class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 no-print"
                data-dt-footer-for="defineHouseTable"></div>
        </div>
    </div>

    {{-- Column Visibility (column-visibility.md — colvis-item card grid) --}}
    <div class="modal fade" id="dhColumnModal" tabindex="-1" aria-labelledby="dhColumnModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-2">
                    <h5 class="modal-title fw-bold" id="dhColumnModalLabel">Column Visibility</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-0">
                    <hr class="mt-0">
                    <div class="row g-3" id="dhColumnToggleGrid"></div>
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

@push('styles')
<style>
    /* ── Define House — page-scoped chrome on top of programme-dt. Namespaced
       under .dh-page so nothing leaks (new-design-index-page.md §7); values come
       from the --ds-* tokens (design.md Layer A). */
    .dh-page .dh-secondary-actions .btn i {
        font-size: 1rem;
        line-height: 1;
    }

    /* Search reveal button — square, brand-outlined, matches the Columns height. */
    .dh-page .dh-search-toggle {
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

    .dh-page .dh-search-toggle[aria-expanded="true"],
    .dh-page .dh-search-toggle:hover {
        border-color: var(--ds-primary, #004a93);
        background: #f2f7fc;
    }

    /* Status: soft badge, display only (§3b). */
    .dh-page .dh-status {
        display: inline-block;
        padding: 0.25rem 0.625rem;
        border-radius: var(--ds-radius, 4px);
        font-size: 0.6875rem;
        font-weight: 500;
        white-space: nowrap;
    }

    .dh-page .dh-status--vacant {
        background: #ecfdf3;
        color: #067647;
    }

    .dh-page .dh-status--occupied {
        background: #eff4ff;
        color: #004a93;
    }

    .dh-page .dh-status--renovation {
        background: #fffaeb;
        color: #b54708;
    }

    /* Row actions — icon over caption, all stacks the same width so the glyph
       row stays on one baseline (§3b). */
    .dh-page .dh-act-group {
        display: inline-flex;
        align-items: stretch;
        gap: var(--ds-space-1, 0.25rem);
    }

    .dh-page .dh-act {
        display: inline-flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-start;
        gap: 4px;
        min-width: 52px;
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

    .dh-page .dh-act__icon {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 22px;
    }

    .dh-page .dh-act__icon > i {
        font-size: 1.1rem;
        line-height: 1;
    }

    .dh-page .dh-act__label {
        white-space: nowrap;
    }

    .dh-page .dh-act--edit {
        color: #2563eb;
    }

    .dh-page .dh-act--edit:hover {
        color: var(--ds-primary, #004a93);
    }

    .dh-page .dh-act--delete {
        color: #d92d20;
    }

    .dh-page .dh-act--delete:hover {
        color: #912018;
    }

    /* A serverSide table with no rows still owns the footer slot; keep the empty
       slot from leaving a gap under the panel. */
    .dh-page .programme-dt-footer:empty {
        display: none;
    }

    /* Pager reads as arrows + numbers, per the design: full_numbers still emits
       First/Last, which the mock doesn't show. */
    .dh-page .programme-dt-footer .paginate_button.first,
    .dh-page .programme-dt-footer .paginate_button.last {
        display: none;
    }

    /* DataTables Responsive would inject a control column and shift every
       Column-Visibility index; the .table-responsive wrapper handles overflow. */
    .dh-page .programme-dt-table td.dtr-control,
    .dh-page .programme-dt-table th.dtr-control {
        display: none;
    }

    .define-house-table-wrapper {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    const dataUrl = "{{ route('admin.estate.define-house.data') }}";
    const downloadUrl = "{{ route('admin.estate.define-house.download') }}";
    const printUrl = "{{ route('admin.estate.define-house.print') }}";
    const editUrlBase = "{{ route('admin.estate.define-house.edit', ['id' => '__ID__']) }}";
    const deleteUrlBase = "{{ route('admin.estate.define-house.destroy', ['id' => '__ID__']) }}".replace('__ID__', '');

    // Header index -> the export's column key. Positional: adding a column means
    // editing this array AND the server-side sort map in
    // computeDefineHouseDataTablePayload (column-visibility.md §2).
    // '' = a column the export doesn't carry.
    const DH_EXPORT_KEYS = ['sno', 'estate_name', 'unit_type', 'building_name', 'unit_sub_type', 'house_no',
        'meter_one', 'water_charge', 'electric_charge', 'licence_fee', 'status', ''
    ];

    // Hidden columns are remembered by LABEL, never index — a label that no
    // longer matches any header is ignored, so a renamed column comes back
    // visible instead of hiding a different one (column-visibility.md §3).
    const DH_COLVIS_KEY = 'sargam.defineHouse.hiddenCols.' + @json(auth()->id() ?? 'guest');

    function escapeHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function showPageAlert(type, message) {
        var icon = type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill';
        var html = '<div class="alert alert-' + type + ' alert-dismissible fade show d-flex align-items-center rounded-3 shadow-sm" role="alert">' +
            '<i class="bi ' + icon + ' me-2 flex-shrink-0" aria-hidden="true"></i>' +
            '<span class="flex-grow-1">' + escapeHtml(message) + '</span>' +
            '<button type="button" class="btn-close flex-shrink-0" data-bs-dismiss="alert" aria-label="Close"></button>' +
        '</div>';
        $('#defineHouseAlerts').html(html);
        $('html, body').animate({ scrollTop: 0 }, 150);
    }

    function money(v) {
        return v != null ? parseFloat(v).toFixed(2) : '0.00';
    }

    /**
     * The label a house shows. Mirrors EstateDefineHouseExport::statusLabel()
     * so the grid, the sheet and the printout agree.
     */
    function statusOf(row) {
        var renovation = parseInt(row.vacant_renovation_status != null ? row.vacant_renovation_status : 1, 10);
        if (renovation === 0) return 'Under Renovation';
        var allotted = parseInt(row.used_home_status != null ? row.used_home_status : 0, 10) === 1;
        return (renovation === 2 || allotted) ? 'Occupied' : 'Vacant';
    }

    // DataTable server-side. No dom / language / lengthMenu here:
    // datatable-global-ui.js owns those, and hand-rolling them breaks the chrome
    // relocation (new-design-index-page.md §3, §5).
    var table = $('#defineHouseTable').DataTable({
        processing: true,
        serverSide: true,
        autoWidth: false,
        responsive: false,
        ajax: {
            url: dataUrl,
            type: 'GET'
        },
        columns: [
            { data: null, orderable: false, searchable: false, render: function(data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
            { data: 'estate_name', name: 'estate_name' },
            { data: 'unit_type', name: 'unit_type' },
            { data: 'building_name', name: 'building_name' },
            { data: 'unit_sub_type', name: 'unit_sub_type' },
            { data: 'house_no', name: 'house_no' },
            { data: 'meter_one', name: 'meter_one' },
            { data: 'water_charge', name: 'water_charge', render: function(v) { return money(v); } },
            { data: 'electric_charge', name: 'electric_charge', render: function(v) { return money(v); } },
            { data: 'licence_fee', name: 'licence_fee', render: function(v) { return money(v); } },
            {
                data: null,
                name: 'status',
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    var label = statusOf(row);
                    if (type !== 'display') return label;
                    var modifier = label === 'Under Renovation' ? 'renovation'
                        : (label === 'Occupied' ? 'occupied' : 'vacant');
                    return '<span class="dh-status dh-status--' + modifier + '">' + label + '</span>';
                }
            },
            {
                data: 'pk',
                orderable: false,
                searchable: false,
                render: function(pk, type, row) {
                    if (!pk) return '';
                    var editUrl = editUrlBase.replace('__ID__', pk);
                    var deleteUrl = (deleteUrlBase.slice(-1) === '/' ? deleteUrlBase : deleteUrlBase + '/') + pk;
                    return '' +
                        '<div class="dh-act-group" role="group" aria-label="Row actions">' +
                            '<a href="' + editUrl + '" class="dh-act dh-act--edit" title="Edit">' +
                                '<span class="dh-act__icon"><i class="bi bi-pencil" aria-hidden="true"></i></span>' +
                                '<span class="dh-act__label">Edit</span>' +
                            '</a>' +
                            '<button type="button" class="dh-act dh-act--delete btn-delete-house" data-url="' + deleteUrl + '" title="Delete">' +
                                '<span class="dh-act__icon"><i class="bi bi-trash" aria-hidden="true"></i></span>' +
                                '<span class="dh-act__label">Delete</span>' +
                            '</button>' +
                        '</div>';
                }
            }
        ],
        order: []
    });

    // Show / hide columns
    function readHiddenCols() {
        try {
            var raw = window.localStorage.getItem(DH_COLVIS_KEY);
            var arr = raw ? JSON.parse(raw) : [];
            return Array.isArray(arr) ? arr : [];
        } catch (e) {
            return [];
        }
    }

    function saveHiddenCols() {
        var hidden = [];
        $('#dhColumnToggleGrid .dh-col-toggle').each(function() {
            if (!this.checked) hidden.push($(this).data('label'));
        });
        try {
            window.localStorage.setItem(DH_COLVIS_KEY, JSON.stringify(hidden));
        } catch (e) { /* private mode — the choice just won't persist */ }
    }

    function buildDefineHouseColumnToggle() {
        var $grid = $('#dhColumnToggleGrid');
        if (!$grid.length) return;
        $grid.empty();

        table.columns().every(function(i) {
            var col = this;
            var label = $(col.header()).text().trim();
            if (!label || label === 'Action') return; // the controls column never hides

            var inputId = 'dhColVis_' + i;
            var $cb = $('<input type="checkbox" class="form-check-input m-0 dh-col-toggle">')
                .attr({ id: inputId, 'data-column': i, 'data-label': label })
                .prop('checked', col.visible());

            $cb.on('change', function() {
                table.column(i).visible(this.checked);
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
        if (!hidden.length) return;
        table.columns().every(function() {
            var label = $(this.header()).text().trim();
            if (label && hidden.indexOf(label) !== -1) this.visible(false, false);
        });
        table.columns.adjust().draw(false);
    }

    table.on('init.dt', function() {
        buildDefineHouseColumnToggle();
        applySavedVisibility();
        // Rebuild after restore so the checkboxes mirror what is showing.
        buildDefineHouseColumnToggle();
    });

    /** Export links carry what the user is looking at: search, sort, visible columns. */
    function exportQuery() {
        var params = new URLSearchParams();
        params.set('search', table.search() || '');
        var order = table.order();
        if (order && order.length) {
            params.set('order[0][column]', order[0][0]);
            params.set('order[0][dir]', order[0][1]);
        }
        var cols = [];
        $('#defineHouseTable thead th').each(function(i) {
            var key = DH_EXPORT_KEYS[i];
            if (!key) return;
            if (table.column(i).visible()) cols.push(key);
        });
        if (cols.length) params.set('cols', cols.join(','));
        return params.toString();
    }

    $('#dhDownloadBtn').on('click', function() {
        window.location.href = downloadUrl + '?' + exportQuery();
    });

    $('#dhPrintBtn').on('click', function() {
        window.open(printUrl + '?' + exportQuery(), '_blank', 'noopener');
    });

    $('#dhSearchToggle').on('click', function() {
        var $wrap = $('#dhDtSearch');
        var open = $wrap.hasClass('d-none');
        $wrap.toggleClass('d-none', !open);
        $(this).attr('aria-expanded', open ? 'true' : 'false');
        if (open) $wrap.find('input').trigger('focus');
    });

    // Delete Estate House - simple confirm + alert
    $(document).on('click', '.btn-delete-house', function(e) {
        e.preventDefault();
        var url = $(this).data('url');
        if (!url) return;
        if (!confirm('Are you sure you want to delete this house? This action cannot be undone.')) {
            return;
        }
        $.ajax({
            url: url,
            type: 'DELETE',
            data: {
                _token: '{{ csrf_token() }}',
            },
            success: function(res) {
                if (res && res.success) {
                    table.ajax.reload(null, false);
                    showPageAlert('success', res.message || 'Estate house deleted successfully.');
                } else {
                    var msg = (res && res.message) ? res.message : 'Failed to delete.';
                    showPageAlert('danger', msg);
                }
            },
            error: function(xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to delete.';
                showPageAlert('danger', msg);
            }
        });
    });
});
</script>
@endpush
