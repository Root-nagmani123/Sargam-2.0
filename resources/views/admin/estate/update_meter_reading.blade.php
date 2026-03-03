@extends('admin.layouts.master')

@section('title', 'Update Meter Reading - Sargam')

@section('setup_content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.estate.update-meter-no') }}">Update Meter No.</a></li>
            <li class="breadcrumb-item active" aria-current="page">Update Meter Reading</li>
        </ol>
    </nav>

    <!-- Page Title -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">
            <a href="{{ route('admin.estate.update-meter-no') }}" class="text-decoration-none text-dark">
                <i class="bi bi-arrow-left me-2"></i>Update Meter Reading
            </a>
        </h2>
    </div>

    <!-- Form Card -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0">Please Update Meter Reading</h5>
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <form id="meterReadingFilterForm">
                @csrf
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="bill_month" class="form-label">Meter Change Month <span class="text-danger">*</span></label>
                        <input type="month" class="form-control" id="bill_month" name="bill_month" placeholder="Select month">
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> Select Meter Change Month
                        </small>
                    </div>
                    <div class="col-md-4">
                        <label for="unit_name" class="form-label">Unit Name <span class="text-danger">*</span></label>
                        <select class="form-select" id="unit_name" name="unit_type_id">
                            <option value="">All</option>
                            @foreach($unitTypes ?? [] as $ut)
                                <option value="{{ $ut->pk }}" {{ ($ut->unit_type ?? '') == 'Residential' ? 'selected' : '' }}>{{ $ut->unit_type }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> Select Unit
                        </small>
                    </div>
                    <div class="col-md-4">
                        <label for="unit_sub_type" class="form-label">Unit Sub Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="unit_sub_type" name="unit_sub_type">
                            <option value="">All</option>
                            @foreach($unitSubTypes ?? [] as $ust)
                                <option value="{{ $ust->pk }}">{{ $ust->unit_sub_type }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> Select Unit Sub Type
                        </small>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="estate_name" class="form-label">Estate Name <span class="text-danger">*</span></label>
                        <select class="form-select" id="estate_name" name="estate_name">
                            <option value="">Select</option>
                            @foreach($campuses ?? [] as $c)
                                <option value="{{ $c->pk }}">{{ $c->campus_name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> Select Estate Name
                        </small>
                    </div>
                    <div class="col-md-4">
                        <label for="building" class="form-label">Building <span class="text-danger">*</span></label>
                        <select class="form-select" id="building" name="building">
                            <option value="">Select</option>
                        </select>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> Select Building
                        </small>
                    </div>
                    <div class="col-md-4">
                        <label for="meter_reading_date" class="form-label">Meter Update Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="meter_reading_date" name="meter_reading_date" placeholder="Select date">
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> Select date
                        </small>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <button type="button" class="btn btn-primary" id="loadMeterReadingsBtn">
                            <i class="bi bi-search me-2"></i>Load Data
                        </button>
                    </div>
                </div>
            </form>

            <form id="meterReadingSaveForm" method="POST" action="{{ route('admin.estate.update-meter-reading.store') }}" style="display:none;">
                @csrf
                <div class="table-responsive mt-4">
                    <table class="table table-bordered table-hover" id="updateMeterReadingTable">
                        <thead class="table-primary">
                            <tr>
                                <th><input type="checkbox" class="form-check-input" id="select_all"></th>
                                <th>House No.</th>
                                <th>Name</th>
                                <th>Old Meter No.</th>
                                <th>Electric Meter Reading</th>
                                <th>New Meter No.</th>
                                <th>New Meter Reading <span class="text-danger">*</span></th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>

                <div class="alert alert-danger mb-4">
                    <small>*Required Fields: All marked fields are mandatory for registration</small>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Save
                    </button>
                    <a href="{{ route('admin.estate.update-meter-no') }}" class="btn btn-outline-primary">Cancel</a>
                </div>
            </form>

            <div id="noDataMessage" class="alert alert-warning mt-4" style="display:none;">
                No meter reading records found for the selected filters.
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    const listUrl = "{{ route('admin.estate.update-meter-reading.list') }}";
    const blocksUrl = "{{ route('admin.estate.update-meter-reading.blocks') }}";
    const unitSubTypesUrl = "{{ route('admin.estate.update-meter-reading.unit-sub-types') }}";

    let dataTable = null;

    $('#estate_name').on('change', function() {
        const campusId = $(this).val();
        $('#building').html('<option value="">All</option>');
        $('#unit_sub_type').html('<option value="">All</option>');
        if (!campusId) return;
        $.get(blocksUrl, { campus_id: campusId }, function(res) {
            if (res.status && res.data) {
                $.each(res.data, function(i, b) {
                    $('#building').append('<option value="'+b.pk+'">'+b.block_name+'</option>');
                });
            }
        });
    });

    $('#building').on('change', function() {
        const campusId = $('#estate_name').val();
        const blockId = $(this).val();
        $('#unit_sub_type').html('<option value="">All</option>');
        if (!campusId || !blockId) return;
        $.get(unitSubTypesUrl, { campus_id: campusId, block_id: blockId }, function(res) {
            if (res.status && res.data) {
                $.each(res.data, function(i, u) {
                    $('#unit_sub_type').append('<option value="'+u.pk+'">'+u.unit_sub_type+'</option>');
                });
            }
        });
    });

    $('#loadMeterReadingsBtn').on('click', function() {
        if (dataTable) {
            dataTable.destroy();
            dataTable = null;
        }
        const billMonthVal = $('#bill_month').val();
        if (!billMonthVal) {
            alert('Please select Meter Change Month.');
            return;
        }
        var monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];
        var parts = billMonthVal.split('-');
        var billYear = parts[0];
        var billMonth = monthNames[parseInt(parts[1], 10) - 1];
        const params = {
            bill_month: billMonth,
            bill_year: billYear,
            meter_reading_date: $('#meter_reading_date').val() || '',
            campus_id: $('#estate_name').val() || '',
            block_id: $('#building').val() || '',
            unit_type_id: $('#unit_name').val() || '',
            unit_sub_type_id: $('#unit_sub_type').val() || ''
        };
        $.get(listUrl, params, function(res) {
            if (!res.status || !res.data || res.data.length === 0) {
                $('#meterReadingSaveForm').hide();
                $('#noDataMessage').show();
                if (dataTable) {
                    dataTable.destroy();
                    dataTable = null;
                }
                $('#updateMeterReadingTable tbody').html('');
                return;
            }
            $('#noDataMessage').hide();
            const tbody = $('#updateMeterReadingTable tbody');
            tbody.html('');
            res.data.forEach(function(row, idx) {
                var newMeterNo = row.new_meter_no != null ? row.new_meter_no : '';
                var newMeterReading = row.new_meter_reading != null ? row.new_meter_reading : '';
                const tr = '<tr>' +
                    '<td><input type="checkbox" class="form-check-input row-check"></td>' +
                    '<td>'+ (row.house_no || 'N/A') +'</td>' +
                    '<td>'+ (row.name || 'N/A') +'</td>' +
                    '<td>'+ (row.old_meter_no || 'N/A') +'</td>' +
                    '<td>'+ (row.electric_meter_reading ?? 'N/A') +'</td>' +
                    '<td><input type="text" class="form-control form-control-sm new-meter-no" name="readings['+idx+'][new_meter_no]" value="'+ newMeterNo +'" placeholder="Enter new meter no."></td>' +
                    '<td><input type="number" class="form-control form-control-sm new-meter-reading" name="readings['+idx+'][curr_month_elec_red]" value="'+ newMeterReading +'" min="0" placeholder="Enter">' +
                    '<input type="hidden" name="readings['+idx+'][pk]" value="'+row.pk+'">' +
                    '<input type="hidden" name="readings['+idx+'][meter_slot]" value="'+(row.meter_slot || 1)+'"></td>' +
                    '</tr>';
                tbody.append(tr);
            });
            $('#meterReadingSaveForm').show();
            initDataTable();
        }).fail(function() {
            alert('Failed to load data.');
        });
    });

    function initDataTable() {
        if (dataTable) dataTable.destroy();
        dataTable = $('#updateMeterReadingTable').DataTable({
            order: [[1, 'asc']],
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            paging: true,
            language: {
                search: "Search:",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                infoEmpty: "Showing 0 to 0 of 0 entries",
                infoFiltered: "(filtered from _MAX_ total entries)",
                paginate: { first: "First", last: "Last", next: "Next", previous: "Previous" }
            },
            responsive: true,
            autoWidth: false,
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>'
        });
    }

    $('#select_all').on('change', function() {
        $('.row-check').prop('checked', $(this).prop('checked'));
    });

    $('#meterReadingSaveForm').on('submit', function(e) {
        if (dataTable && dataTable.page.len() !== -1) {
            e.preventDefault();
            dataTable.page.len(-1).draw('page');
            const $form = $(this);
            setTimeout(function() {
                $form.off('submit').submit();
            }, 150);
        }
    });
});
</script>
@endpush
