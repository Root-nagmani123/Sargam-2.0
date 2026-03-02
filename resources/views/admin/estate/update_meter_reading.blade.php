@extends('admin.layouts.master')

@section('title', 'Update Meter Reading - Sargam')

@section('setup_content')
<div class="container-fluid">
    <!-- Breadcrumb -->
<x-breadcrum :title="'Update Meter Reading'" :items="['Home', 'Estate Management', 'Update Meter Reading']" />  

    <!-- Page Title -->
    <div class="card shadow-sm">
        <div class="card-header bg-body-secondary bg-opacity-10 border-0 py-3 px-4 d-flex flex-wrap align-items-center justify-content-between gap-2">
            <h5 class="card-title">Please Update Meter Reading</h5>
    
        </div>
        <div class="card-body p-4">
            <form>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="meter_change_month" class="form-label">Meter Change Month <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="date" class="form-control" id="meter_change_month" name="meter_change_month" value="January 2026" required>
                            <span class="input-group-text">
                                <i class="bi bi-calendar"></i>
                            </span>
                        </div>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> Select Bill Month
                        </small>
                    </div>
                    <div class="col-md-4">
                        <label for="estate_name" class="form-label">Estate Name <span class="text-danger">*</span></label>
                        <select class="form-select" id="estate_name" name="estate_name" required>
                            <option value="administrative_officer" selected>Administrative Officer</option>
                        </select>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> Select Estate Name
                        </small>
                    </div>
                    <div class="col-md-4">
                        <label for="building" class="form-label">Building <span class="text-danger">*</span></label>
                        <select class="form-select" id="building" name="building" required>
                            <option value="bhagirathi_avas" selected>Bhagirathi Avas</option>
                        </select>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> Select Building
                        </small>
                    </div>
                    <div class="col-md-4">
                        <label for="unit_name" class="form-label">Unit Name <span class="text-danger">*</span></label>
                        <select class="form-select" id="unit_name" name="unit_name" required>
                            <option value="residential" selected>Residential</option>
                        </select>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> Select Unit
                        </small>
                    </div>
                    <div class="col-md-4">
                        <label for="unit_sub_type" class="form-label">Unit Sub Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="unit_sub_type" name="unit_sub_type" required>
                            <option value="type_i" selected>Type-I</option>
                        </select>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> Select Unit Sub Type
                        </small>
                    </div>
                </div>

                <div class="row mb-3">
                    
                    
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
                        <label for="meter_reading_date" class="form-label">Master Update Date (Meter Reading Date) <span class="text-danger">*</span></label>
                        <select class="form-select" id="meter_reading_date" name="meter_reading_date">
                            <option value="">Select</option>
                        </select>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> Meter Reading Date
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
                                <th>New Meter Reading</th>
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
    const meterReadingDatesUrl = "{{ route('admin.estate.update-meter-reading.meter-reading-dates') }}";

    let dataTable = null;

    $('#bill_month').on('change', function() {
        const billMonth = $(this).val();
        const billYear = $(this).find('option:selected').data('year');
        $('#meter_reading_date').html('<option value="">Select</option>');
        if (!billMonth || !billYear) return;
        $.get(meterReadingDatesUrl, { bill_month: billMonth, bill_year: billYear }, function(res) {
            if (res.status && res.data && res.data.length) {
                res.data.forEach(function(d) {
                    $('#meter_reading_date').append('<option value="'+d.value+'">'+d.label+'</option>');
                });
                if (res.data.length === 1) $('#meter_reading_date').val(res.data[0].value);
            }
        });
    });

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
        const billMonth = $('#bill_month').val();
        const billYear = $('#bill_month option:selected').data('year');
        if (!billMonth || !billYear) {
            alert('Please select Bill Month.');
            return;
        }
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
                const tr = '<tr>' +
                    '<td><input type="checkbox" class="form-check-input row-check"></td>' +
                    '<td>'+ (row.house_no || 'N/A') +'</td>' +
                    '<td>'+ (row.name || 'N/A') +'</td>' +
                    '<td>'+ (row.last_reading_date || 'N/A') +'</td>' +
                    '<td>'+ (row.meter_no || 'N/A') +'</td>' +
                    '<td>'+ (row.last_month_reading || 'N/A') +'</td>' +
                    '<td><input type="number" class="form-control form-control-sm curr-reading" name="readings['+idx+'][curr_month_elec_red]" value="'+ (row.curr_month_reading || '') +'" min="0" placeholder="Enter">' +
                    '<input type="hidden" name="readings['+idx+'][pk]" value="'+row.pk+'"></td>' +
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
