@extends('admin.layouts.master')

@section('title', 'List Meter Reading - Sargam')

@section('setup_content')
<style>
    /* List Meter Reading page: search bar right-aligned (default DataTables style) */
    #listMeterReadingCard .dataTables_filter { float: right !important; text-align: right !important; }
    #listMeterReadingCard .dataTables_filter label { margin: 0 !important; }
    #listMeterReadingCard .dataTables_filter input { margin-left: 0.5rem !important; }
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
                    <div class="input-group">
                        <input type="month" class="form-control" id="bill_month" name="bill_month" value="{{ date('Y-m') }}" required>
                        <span class="input-group-text"><i class="bi bi-calendar3"></i></span>
                    </div>
                    <small class="text-muted d-block">Select Bill Month</small>
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
                            <th>EMPLOYEE TYPE</th>
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var dataTableInstance = null;
    var tableEl = document.getElementById('listMeterReadingTable');
    var tbody = tableEl ? tableEl.querySelector('tbody') : null;

    function destroyDataTable() {
        if (dataTableInstance && typeof $ !== 'undefined' && $.fn.DataTable && $.fn.DataTable.isDataTable('#listMeterReadingTable')) {
            dataTableInstance.destroy();
            dataTableInstance = null;
        }
    }

    function loadData() {
        var billMonth = document.getElementById('bill_month').value;
        var blockId = document.getElementById('block_id').value;
        if (!billMonth) {
            alert('Please select Bill Month.');
            return;
        }

        destroyDataTable();
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="11" class="text-center py-4">Loading...</td></tr>';
        }

        var url = '{{ route("admin.estate.list-meter-reading.data") }}';
        var params = new URLSearchParams({ bill_month: billMonth, block_id: blockId });

        fetch(url + '?' + params.toString())
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (!tbody) return;
                tbody.innerHTML = '';
                if (res.status && res.data && res.data.length > 0) {
                    res.data.forEach(function(row) {
                        var tr = document.createElement('tr');
                        tr.innerHTML =
                            '<td>' + (row.sno || '') + '</td>' +
                            '<td>' + (row.name || 'N/A') + '</td>' +
                            '<td>' + (row.employee_type || 'N/A') + '</td>' +
                            '<td>' + (row.section || 'N/A') + '</td>' +
                            '<td>' + (row.unit_type || 'N/A') + '</td>' +
                            '<td>' + (row.unit_sub_type || 'N/A') + '</td>' +
                            '<td>' + (row.building_name || 'N/A') + '</td>' +
                            '<td>' + (row.house_no || 'N/A') + '</td>' +
                            '<td>' + (row.meter1_reading || 'N/A') + '</td>' +
                            '<td>' + (row.meter2_reading || 'N/A') + '</td>' +
                            '<td class="text-center"><a href="' + (row.edit_url || '#') + '" class="btn btn-sm btn-outline-success" title="Edit"><i class="bi bi-pencil-square"></i></a></td>';
                        tbody.appendChild(tr);
                    });
                    if (typeof $ !== 'undefined' && $.fn.DataTable) {
                        dataTableInstance = $('#listMeterReadingTable').DataTable({
                            order: [[0, 'asc']],
                            pageLength: 10,
                            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
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
                            dom: '<"row flex-wrap align-items-center gap-2 mb-2"<"col-md-6"l><"col-md-6"f>>rt<"row align-items-center mt-2"<"col-12 col-md-5"i><"col-12 col-md-7"p>>'
                        });
                    }
                } else {
                    tbody.innerHTML = '<tr id="noDataRow"><td colspan="11" class="text-center text-muted py-4">No meter reading records found for the selected filters.</td></tr>';
                }
            })
            .catch(function() {
                if (tbody) {
                    tbody.innerHTML = '<tr><td colspan="11" class="text-center text-danger py-4">Failed to load data.</td></tr>';
                }
            });
    }

    document.getElementById('btnShow').addEventListener('click', loadData);
});
</script>
@endpush
