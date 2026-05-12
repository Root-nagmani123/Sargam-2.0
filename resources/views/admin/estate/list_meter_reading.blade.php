@extends('admin.layouts.master')

@section('title', 'List Meter Reading - Sargam')

@section('content')
<style>
    /* List Meter Reading page: force search bar in top-right of header row */
    #listMeterReadingCard .dataTables_wrapper .row:first-child {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
    }
    #listMeterReadingCard .dataTables_wrapper .dataTables_length {
        text-align: left;
    }
    #listMeterReadingCard .dataTables_wrapper .dataTables_filter {
        width: auto;
        margin-left: auto;
        margin-bottom: 0.5rem;
        float: none !important;
        text-align: right !important;
    }
    #listMeterReadingCard .dataTables_wrapper .dataTables_filter label {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        justify-content: flex-end;
        margin: 0 !important;
    }
    @media (max-width: 767.98px) {
        #listMeterReadingCard .dataTables_wrapper .row:first-child {
            flex-direction: column;
            align-items: stretch;
        }
        #listMeterReadingCard .dataTables_wrapper .dataTables_filter {
            width: 100%;
            margin-left: 0;
            text-align: left !important;
        }
        #listMeterReadingCard .dataTables_wrapper .dataTables_filter label {
            justify-content: flex-start;
        }
    }
</style>
<div class="container-fluid px-2 px-sm-3 px-md-4">
    <x-breadcrum title="List Meter Reading" />

    <x-session_message />

    <div class="card shadow-sm border-0 rounded-3 mb-4">
        <div class="card-body p-4">
            <h1 class="h4 fw-bold text-dark mb-1">List Meter Reading</h1>
            <p class="text-muted small mb-4">Filter meter readings by Bill Month and Building Name.</p>

            <form id="listMeterReadingFilterForm" class="row g-3">
                <div class="col-12 col-md-4">
                    <label for="bill_month" class="form-label">Bill Month <span class="text-danger">*</span></label>
                    <input
                        type="month"
                        class="form-control"
                        id="bill_month"
                        name="bill_month"
                        value="{{ date('Y-m') }}"
                        max="{{ date('Y-m') }}"
                        required
                    >
                    <small class="text-muted d-block">Select Bill Month</small>
                </div>
                <div class="col-12 col-md-4">
                    <label for="employee_type" class="form-label">Employee Type <span class="text-danger">*</span></label>
                    <select class="form-select" id="employee_type" name="employee_type" required>
                        <option value="LBSNAA" selected>LBSNAA</option>
                        <option value="Other Employee">Other Employee</option>
                    </select>
                    <small class="text-muted d-block">Select Employee Type</small>
                </div>
                <div class="col-12 col-md-4">
                    <label for="block_id" class="form-label">Building Name <span class="text-danger">*</span></label>
                    <select class="form-select" id="block_id" name="block_id">
                        <option value="all">All</option>
                        @foreach($blocks ?? [] as $b)
                            <option value="{{ $b->pk }}">{{ $b->block_name ?? 'N/A' }}</option>
                        @endforeach
                    </select>
                    <small class="text-muted d-block">Select Building Name</small>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label d-block" style="height: 1.25em; margin-bottom: 0.5rem;" aria-hidden="true">&nbsp;</label>
                    <button type="button" class="btn btn-primary" id="btnShow">
                        <i class="bi bi-search me-1"></i> Show
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-3" id="listMeterReadingCard">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0" id="listMeterReadingTable">
                    <thead class="table-primary">
                        <tr>
                            <th>S.NO.</th>
                            <th>NAME</th>
                            <th>DESIGNATION</th>
                            <th>SECTION</th>
                            <th>UNIT TYPE</th>
                            <th>UNIT SUB TYPE</th>
                            <th>BUILDING NAME</th>
                            <th>HOUSE NO.</th>
                            <th>METER1 READING</th>
                            <th>METER2 READING</th>
                            <th class="text-center">EDIT</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr id="noDataRow">
                            <td colspan="11" class="text-center text-muted py-4">Select Bill Month and Building Name, then click Show to load data.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<style>.ts-dropdown { z-index: 1060 !important; }</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof TomSelect !== 'undefined') {
        var commonCfg = {
            allowEmptyOption: true,
            create: false,
            dropdownParent: 'body',
            maxOptions: null,
            hideSelected: false,
            onInitialize: function () { this.activeOption = null; }
        };
        ['employee_type', 'block_id'].forEach(function(id) {
            var el = document.getElementById(id);
            if (!el) return;
            if (el.tomselect) { try { el.tomselect.destroy(); } catch (e) {} }
            new TomSelect(el, Object.assign({}, commonCfg, {}));
        });
    }
    var dataTableInstance = null;
    var lastLoadedParams = null;

    function initOrReload() {
        var billMonth = document.getElementById('bill_month').value;
        var employeeType = document.getElementById('employee_type').value;
        var blockId = document.getElementById('block_id').value;
        if (!billMonth) {
            alert('Please select Bill Month.');
            return;
        }
        lastLoadedParams = { bill_month: billMonth, employee_type: employeeType, block_id: blockId };

        if (typeof $ === 'undefined' || !$.fn.DataTable) {
            alert('DataTable library not loaded.');
            return;
        }

        if (!dataTableInstance) {
            dataTableInstance = $('#listMeterReadingTable').DataTable({
                processing: true,
                serverSide: true,
                searching: true,
                pageLength: 10,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                ajax: {
                    url: '{{ route("admin.estate.list-meter-reading.data") }}',
                    data: function (d) {
                        // Always read latest filter values (avoid stale closure values on reload)
                        d.bill_month = document.getElementById('bill_month').value;
                        d.employee_type = document.getElementById('employee_type').value;
                        d.block_id = document.getElementById('block_id').value;
                    }
                },
                columns: [
                    { data: 'sno', name: 'sno', orderable: false, searchable: false },
                    { data: 'name', name: 'name' },
                    { data: 'designation', name: 'designation' },
                    { data: 'section', name: 'section' },
                    { data: 'unit_type', name: 'unit_type' },
                    { data: 'unit_sub_type', name: 'unit_sub_type' },
                    { data: 'building_name', name: 'building_name' },
                    { data: 'house_no', name: 'house_no' },
                    { data: 'meter1_reading', name: 'meter1_reading' },
                    { data: 'meter2_reading', name: 'meter2_reading' },
                    {
                        data: 'edit_url',
                        orderable: false,
                        searchable: false,
                        className: 'text-center',
                        render: function (data) {
                            var url = data || '#';
                            return '<a href="' + url + '" class="btn btn-sm btn-outline-success" title="Edit"><i class="material-icons">edit</i></a>';
                        }
                    }
                ],
                language: {
                    search: 'Search within table:',
                    lengthMenu: 'Show _MENU_ entries',
                    info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                    infoEmpty: 'Showing 0 to 0 of 0 entries',
                    infoFiltered: '(filtered from _MAX_ total entries)',
                    paginate: { first: 'First', last: 'Last', next: 'Next', previous: 'Previous' }
                },
                responsive: true,
                autoWidth: false,
                dom: '<"row mb-3"<"col-md-6 col-12"l><"col-md-6 col-12"f>>rt<"row align-items-center mt-2"<"col-12 col-md-5"i><"col-12 col-md-7"p>>'
            });
        } else {
            dataTableInstance.ajax.reload(null, true);
        }
    }

    document.getElementById('btnShow').addEventListener('click', initOrReload);

    // When user navigates back from edit/update screen, browsers may restore this page from bfcache.
    // In that case the table shows stale DOM. Refresh automatically to reflect saved readings.
    window.addEventListener('pageshow', function(event) {
        var navEntry = (performance && performance.getEntriesByType) ? performance.getEntriesByType('navigation')[0] : null;
        var isBackForward = (navEntry && navEntry.type === 'back_forward') || event.persisted;
        if (!isBackForward) return;
        if (lastLoadedParams && lastLoadedParams.bill_month && dataTableInstance) {
            dataTableInstance.ajax.reload(null, false);
        }
    });
});
</script>
@endpush
