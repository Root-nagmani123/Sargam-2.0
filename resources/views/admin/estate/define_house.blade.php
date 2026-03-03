@extends('admin.layouts.master')

@section('title', 'Define House - Sargam')

@section('setup_content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.estate.request-for-others') }}">Estate Management</a></li>
            <li class="breadcrumb-item active" aria-current="page">Define House</li>
        </ol>
    </nav>

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Define House</h2>
        <div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEstateHouseModal">
                <i class="bi bi-plus-circle me-2"></i>Add Estate House
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Data Table Card -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0">Estate House List</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="defineHouseTable">
                    <thead class="table-primary">
                        <tr>
                            <th>S.No.</th>
                            <th>Estate Name</th>
                            <th>Unit Type</th>
                            <th>Building Name</th>
                            <th>Unit Sub Type</th>
                            <th>House No.</th>
                            <th>Water Charge</th>
                            <th>Electric Charge</th>
                            <th>Licence Fee</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Estate House Modal -->
<div class="modal fade" id="addEstateHouseModal" tabindex="-1" aria-labelledby="addEstateHouseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addEstateHouseModalLabel">Add Estate House</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">Please Add Estate House</p>
                <form id="addEstateHouseForm">
                    @csrf
                    <input type="hidden" id="edit_house_pk" name="edit_house_pk" value="">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="estate_campus_master_pk" class="form-label">Estate Name <span class="text-danger">*</span></label>
                            <select class="form-select" id="estate_campus_master_pk" name="estate_campus_master_pk" required>
                                <option value="">--Select--</option>
                                @foreach($campuses ?? [] as $c)
                                    <option value="{{ $c->pk }}">{{ $c->campus_name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Select Estate Name</small>
                        </div>
                        <div class="col-md-6">
                            <label for="estate_unit_type_master_pk" class="form-label">Unit Name <span class="text-danger">*</span></label>
                            <select class="form-select" id="estate_unit_type_master_pk" name="estate_unit_type_master_pk" required>
                                <option value="">--Select--</option>
                                @foreach($unitTypes ?? [] as $ut)
                                    <option value="{{ $ut->pk }}">{{ $ut->unit_type }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Select Unit</small>
                        </div>
                        <div class="col-md-6">
                            <label for="estate_block_master_pk" class="form-label">Building <span class="text-danger">*</span></label>
                            <select class="form-select" id="estate_block_master_pk" name="estate_block_master_pk" required>
                                <option value="">--Select--</option>
                            </select>
                            <small class="text-muted">Select Building</small>
                        </div>
                        <div class="col-md-6">
                            <label for="estate_unit_sub_type_master_pk" class="form-label">Unit Sub Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="estate_unit_sub_type_master_pk" name="estate_unit_sub_type_master_pk" required>
                                <option value="">--Select--</option>
                                @foreach($unitSubTypes ?? [] as $ust)
                                    <option value="{{ $ust->pk }}">{{ $ust->unit_sub_type }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Select Unit Sub Type</small>
                        </div>
                        <div class="col-md-6">
                            <label for="water_charge" class="form-label">Water Charges <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="water_charge" name="water_charge" value="0.00" step="0.01" min="0" required>
                            <small class="text-muted">Water Charges</small>
                        </div>
                        <div class="col-md-6">
                            <label for="electric_charge" class="form-label">Fixed Electricity Charges <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="electric_charge" name="electric_charge" value="0.00" step="0.01" min="0" required>
                            <small class="text-muted">Fix Electricity Charges</small>
                        </div>
                    </div>

                    <!-- Repeatable section: House No. to Under Renovation -->
                    <div class="border rounded p-3 mt-3 mb-3 bg-light">
                        <div id="houseRowsContainer">
                            <div class="house-row row g-3 mb-3 align-items-end" data-row="0">
                                <div class="col-md-6">
                                    <label class="form-label">House No. <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control house_no" name="house_no[]" placeholder="Enter House No." required>
                                    <small class="text-muted">Enter House No.</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Meter No. 1</label>
                                    <input type="text" class="form-control meter_one" name="meter_one[]" placeholder="Enter Meter No. 1">
                                    <small class="text-muted">Enter Meter No. 1</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Meter No. 2</label>
                                    <input type="text" class="form-control meter_two" name="meter_two[]" placeholder="Enter Meter No. 2">
                                    <small class="text-muted">Enter Meter No. 2</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Licence Fee</label>
                                    <input type="number" class="form-control licence_fee" name="licence_fee[]" value="0.00" step="0.01" min="0" placeholder="Enter Licence Fee">
                                    <small class="text-muted">Enter Licence Fee</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Status <span class="text-danger">*</span></label>
                                    <input type="hidden" class="vacant_renovation_status" name="vacant_renovation_status[]" value="1">
                                    <div class="d-flex gap-3 pt-2">
                                        <div class="form-check">
                                            <input class="form-check-input status-radio" type="radio" name="vacant_renovation_radio_0" value="1" checked>
                                            <label class="form-check-label">Vacant</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input status-radio" type="radio" name="vacant_renovation_radio_0" value="0">
                                            <label class="form-check-label">Under Renovation</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex gap-2 mt-2">
                            <button type="button" class="btn btn-success btn-sm" id="addHouseRowBtn" title="Add House"><i class="bi bi-plus-lg"></i></button>
                            <button type="button" class="btn btn-danger btn-sm" id="removeHouseRowBtn" title="Remove last house row"><i class="bi bi-dash-lg"></i></button>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-12">
                            <label for="remarks" class="form-label">Remarks</label>
                            <textarea class="form-control" id="remarks" name="remarks" rows="2" placeholder="Remarks (optional)" maxlength="200"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="saveEstateHouseBtn">
                    <i class="bi bi-save me-1"></i>Save
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    const blocksUrl = "{{ route('admin.estate.define-house.blocks') }}";
    const storeUrl = "{{ route('admin.estate.define-house.store') }}";
    const dataUrl = "{{ route('admin.estate.define-house.data') }}";
    const editUrlBase = "{{ route('admin.estate.define-house.show', ['id' => '__ID__']) }}".replace('__ID__', '');
    const updateUrlBase = "{{ route('admin.estate.define-house.update', ['id' => '__ID__']) }}".replace('__ID__', '');
    const deleteUrlBase = "{{ route('admin.estate.define-house.destroy', ['id' => '__ID__']) }}".replace('__ID__', '');

    // Load buildings when estate (campus) changes
    $('#estate_campus_master_pk').on('change', function() {
        var campusId = $(this).val();
        var $building = $('#estate_block_master_pk');
        $building.html('<option value="">--Select--</option>');
        if (!campusId) return;
        $.get(blocksUrl, { campus_id: campusId }, function(res) {
            if (res.status && res.data) {
                $.each(res.data, function(i, b) {
                    $building.append('<option value="'+b.pk+'">'+b.block_name+'</option>');
                });
            }
        });
    });

    // DataTable server-side
    var table = $('#defineHouseTable').DataTable({
        processing: true,
        serverSide: true,
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
            { data: 'water_charge', name: 'water_charge', render: function(v) { return v != null ? parseFloat(v).toFixed(2) : '0.00'; } },
            { data: 'electric_charge', name: 'electric_charge', render: function(v) { return v != null ? parseFloat(v).toFixed(2) : '0.00'; } },
            { data: 'licence_fee', name: 'licence_fee', render: function(v) { return v != null ? parseFloat(v).toFixed(2) : '0.00'; } },
            { data: 'vacant_renovation_status', name: 'vacant_renovation_status', render: function(v) { return v == 1 ? 'Vacant' : 'Under Renovation'; } },
            { data: 'pk', orderable: false, searchable: false, render: function(pk) {
                return '<div class="d-flex flex-nowrap gap-1 justify-content-center">' +
                       '<button type="button" class="btn btn-sm btn-warning btn-edit-house" title="Edit" data-pk="'+pk+'"><i class="bi bi-pencil"></i></button>' +
                       '<button type="button" class="btn btn-sm btn-danger btn-delete-house" title="Delete" data-pk="'+pk+'"><i class="bi bi-trash"></i></button></div>';
            }}
        ],
        order: [[0, 'asc']],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        language: {
            search: "Search:",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            infoEmpty: "Showing 0 to 0 of 0 entries",
            infoFiltered: "(filtered from _MAX_ total entries)",
            paginate: { first: "First", last: "Last", next: "Next", previous: "Previous" }
        },
        responsive: true,
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>'
    });

    // Save Estate House (AJAX) - add multiple or edit single
    $('#saveEstateHouseBtn').on('click', function() {
        syncStatusHidden();
        reindexHouseRowNames();
        var $form = $('#addEstateHouseForm');
        var editPk = $('#edit_house_pk').val();
        var $rows = $('#houseRowsContainer .house-row');
        var valid = true;
        $rows.each(function() {
            if (!$(this).find('.house_no').val().trim()) valid = false;
        });
        if (!valid || !$form[0].checkValidity()) {
            $form[0].reportValidity();
            if (!valid) alert('Please enter House No. for all rows.');
            return;
        }
        var btn = $(this);
        btn.prop('disabled', true);
        var url = editPk ? (updateUrlBase.slice(-1) === '/' ? updateUrlBase : updateUrlBase + '/') + editPk : storeUrl;
        var method = editPk ? 'PUT' : 'POST';
        var data = $form.serialize();
        if (editPk) data += '&_method=PUT';
        $.ajax({
            url: url,
            type: 'POST',
            data: data,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        }).done(function(res) {
            if (res.success) {
                $('#addEstateHouseModal').modal('hide');
                $('#edit_house_pk').val('');
                $form[0].reset();
                $('#water_charge, #electric_charge').val('0.00');
                $('#houseRowsContainer .house-row:gt(0)').remove();
                $('#houseRowsContainer .house-row').first().find('.licence_fee').val('0.00');
                $('#houseRowsContainer .house-row').first().find('.vacant_renovation_status').val('1');
                $('#houseRowsContainer .house-row').first().find('.status-radio[value="1"]').prop('checked', true);
                $('#addHouseRowBtn, #removeHouseRowBtn').show();
                table.ajax.reload(null, false);
                alert(res.message || (editPk ? 'Estate house updated.' : 'Estate house(s) added successfully.'));
            }
        }).fail(function(xhr) {
            var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : (xhr.responseJSON && xhr.responseJSON.errors ? JSON.stringify(xhr.responseJSON.errors) : 'Failed to save.');
            alert(msg);
        }).always(function() {
            btn.prop('disabled', false);
        });
    });

    // Reset form when modal is closed
    $('#addEstateHouseModal').on('hidden.bs.modal', function() {
        $('#edit_house_pk').val('');
        $('#addEstateHouseModalLabel').text('Add Estate House');
        $('#addHouseRowBtn, #removeHouseRowBtn').show();
        $('#addEstateHouseForm')[0].reset();
        $('#water_charge, #electric_charge').val('0.00');
        $('#estate_block_master_pk').html('<option value="">--Select--</option>');
        $('#houseRowsContainer .house-row:gt(0)').remove();
        $('#houseRowsContainer .house-row').first().find('.licence_fee').val('0.00');
        $('#houseRowsContainer .house-row').first().find('.vacant_renovation_status').val('1');
        $('#houseRowsContainer .house-row').first().find('.status-radio').attr('name', 'vacant_renovation_radio_0');
        $('#houseRowsContainer .house-row').first().find('.status-radio[value="1"]').prop('checked', true);
        updateGlobalRemoveButton();
    });

    // Add House row (House No. to Under Renovation section)
    var houseRowIndex = 1;
    $('#addHouseRowBtn').on('click', function() {
        var $container = $('#houseRowsContainer');
        var $first = $container.find('.house-row').first();
        var $clone = $first.clone();
        var radioName = 'vacant_renovation_radio_' + houseRowIndex;
        $clone.removeAttr('data-row').attr('data-row', houseRowIndex);
        $clone.find('input').val('');
        $clone.find('.house_no').attr('placeholder', 'Enter House No.');
        $clone.find('.meter_one').attr('placeholder', 'Enter Meter No. 1');
        $clone.find('.meter_two').attr('placeholder', 'Enter Meter No. 2');
        $clone.find('.licence_fee').val('0.00');
        $clone.find('.vacant_renovation_status').val('1');
        $clone.find('.status-radio').attr('name', radioName);
        $clone.find('.status-radio[value="1"]').prop('checked', true);
        $clone.find('.status-radio[value="0"]').prop('checked', false);
        $container.append($clone);
        houseRowIndex++;
        updateGlobalRemoveButton();
    });

    // Remove last House row (only global - button, no per-row minus)
    $('#removeHouseRowBtn').on('click', function() {
        var $rows = $('#houseRowsContainer .house-row');
        if ($rows.length > 1) {
            $rows.last().remove();
            updateGlobalRemoveButton();
        }
    });

    // Status radio: update hidden in same row; use unique name per row so only one selected per row
    $(document).on('change', '.status-radio', function() {
        $(this).closest('.house-row').find('.vacant_renovation_status').val($(this).val());
    });

    function updateGlobalRemoveButton() {
        var $rows = $('#houseRowsContainer .house-row');
        $('#removeHouseRowBtn').prop('disabled', $rows.length <= 1);
    }

    updateGlobalRemoveButton();

    // Before save: sync each row's hidden from checked radio; ensure every row has 0 or 1
    function syncStatusHidden() {
        $('#houseRowsContainer .house-row').each(function() {
            var $row = $(this);
            var checked = $row.find('.status-radio:checked').val();
            var $hidden = $row.find('.vacant_renovation_status');
            if (checked !== undefined && checked !== '') {
                $hidden.val(checked);
            } else {
                $hidden.val('1');
            }
        });
    }

    // Before submit: set explicit array indices [0], [1], ... so backend receives all rows (fixes vacant_renovation_status.1 required)
    function reindexHouseRowNames() {
        $('#houseRowsContainer .house-row').each(function(idx) {
            var $row = $(this);
            $row.find('.house_no').attr('name', 'house_no[' + idx + ']');
            $row.find('.meter_one').attr('name', 'meter_one[' + idx + ']');
            $row.find('.meter_two').attr('name', 'meter_two[' + idx + ']');
            $row.find('.licence_fee').attr('name', 'licence_fee[' + idx + ']');
            $row.find('.vacant_renovation_status').attr('name', 'vacant_renovation_status[' + idx + ']');
        });
    }

    // Edit Estate House
    $(document).on('click', '.btn-edit-house', function() {
        var pk = $(this).data('pk');
        var url = (editUrlBase.slice(-1) === '/' ? editUrlBase : editUrlBase + '/') + pk;
        $.get(url, function(res) {
            if (!res || !res.pk) { alert('Could not load house.'); return; }
            $('#edit_house_pk').val(res.pk);
            $('#addEstateHouseModalLabel').text('Edit Estate House');
            $('#estate_campus_master_pk').val(res.estate_campus_master_pk);
            $('#estate_unit_type_master_pk').val(res.estate_unit_master_pk);
            $('#estate_block_master_pk').html('<option value="'+res.estate_block_master_pk+'" selected>'+res.building_name+'</option>');
            $('#estate_unit_sub_type_master_pk').val(res.estate_unit_sub_type_master_pk);
            $('#water_charge').val(res.water_charge);
            $('#electric_charge').val(res.electric_charge);
            $('#remarks').val(res.remarks || '');
            $('#houseRowsContainer .house-row:gt(0)').remove();
            var $row = $('#houseRowsContainer .house-row').first();
            $row.find('.house_no').val(res.house_no);
            $row.find('.meter_one').val(res.meter_one || '');
            $row.find('.meter_two').val(res.meter_two || '');
            $row.find('.licence_fee').val(res.licence_fee);
            $row.find('.vacant_renovation_status').val(res.vacant_renovation_status);
            $row.find('.status-radio').attr('name', 'vacant_renovation_radio_0');
            $row.find('.status-radio[value="1"]').prop('checked', res.vacant_renovation_status == 1);
            $row.find('.status-radio[value="0"]').prop('checked', res.vacant_renovation_status == 0);
            $('#addHouseRowBtn').hide();
            $('#removeHouseRowBtn').hide();
            $('#addEstateHouseModal').modal('show');
        }).fail(function() { alert('Could not load house.'); });
    });

    // Delete Estate House
    $(document).on('click', '.btn-delete-house', function() {
        var pk = $(this).data('pk');
        if (!confirm('Are you sure you want to delete this estate house?')) return;
        var url = (deleteUrlBase.slice(-1) === '/' ? deleteUrlBase : deleteUrlBase + '/') + pk;
        $.ajax({
            url: url,
            type: 'DELETE',
            headers: { 'X-CSRF-TOKEN': $('input[name="_token"]').val() || $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' }
        }).done(function(res) {
            if (res.success) {
                table.ajax.reload(null, false);
                alert(res.message || 'Estate house deleted.');
            } else alert(res.message || 'Delete failed.');
        }).fail(function(xhr) {
            var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Delete failed.';
            alert(msg);
        });
    });

    // When opening Add modal (not edit), reset title and show + -
    $('#addEstateHouseModal').on('show.bs.modal', function(e) {
        if ($('#edit_house_pk').val() === '') {
            $('#addEstateHouseModalLabel').text('Add Estate House');
            $('#addHouseRowBtn').show();
            $('#removeHouseRowBtn').show();
        }
    });
});
</script>
@endpush