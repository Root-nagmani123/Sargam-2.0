@extends('admin.layouts.master')

@section('title', 'City - Sargam | Lal Bahadur')

@section('setup_content')
<div class="container-fluid city-index">
    <x-breadcrum title="City" />
    <x-session_message />
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row align-items-center mb-0 g-2">
                        <div class="col-12 col-md-6">
                            <h4 class="mb-0 fw-bold">City</h4>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="d-flex justify-content-start justify-content-md-end align-items-center gap-2">
                                <button type="button" class="btn btn-primary px-3 py-2 rounded-1 shadow-sm d-flex align-items-center gap-2"
                                    data-bs-toggle="modal" data-bs-target="#createCityModal">
                                    <i class="material-icons material-symbols-rounded fs-5 align-middle">add</i>
                                    Add New City
                                </button>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="table-responsive">
                        {!! $dataTable->table(['class' => 'table w-100 text-nowrap align-middle mb-0']) !!}
                    </div>
                </div>
            </div>
        </div>
        <!-- end Zero Configuration -->
    </div>
</div>

{{-- Create City Modal --}}
<div class="modal fade" id="createCityModal" tabindex="-1" aria-labelledby="createCityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createCityModalLabel">Add City</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('master.city.store') }}" method="POST" id="createCityForm">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="create_country_master_pk" class="form-label">Select Country</label>
                                <select class="form-select" id="create_country_master_pk" name="country_master_pk" required>
                                    <option value="">-- Select Country --</option>
                                    @foreach($countries ?? [] as $country)
                                    <option value="{{ $country->pk }}"
                                        {{ old('country_master_pk') == $country->pk ? 'selected' : '' }}>
                                        {{ $country->country_name }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('country_master_pk')
                                <p class="text-danger">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="create_state_master_pk" class="form-label">State</label>
                                <select name="state_master_pk" id="create_state_master_pk" class="form-select" required>
                                    <option value="">Select State</option>
                                </select>
                                @error('state_master_pk')
                                <p class="text-danger">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="create_district_master_pk" class="form-label">District</label>
                                <select name="district_master_pk" id="create_district_master_pk" class="form-select" required>
                                    <option value="">Select District</option>
                                </select>
                                @error('district_master_pk')
                                <p class="text-danger">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="create_city_name" class="form-label">City Name</label>
                                <input type="text" name="city_name" id="create_city_name" class="form-control" value="{{ old('city_name') }}" required>
                                @error('city_name')
                                <p class="text-danger">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="create_active_inactive" class="form-label">Status <span style="color:red;">*</span></label>
                                <select name="active_inactive" id="create_active_inactive" class="form-select" required>
                                    <option value="1" {{ (old('active_inactive', 1) == 1) ? 'selected' : '' }}>Active</option>
                                    <option value="2" {{ (old('active_inactive') == 2) ? 'selected' : '' }}>Inactive</option>
                                </select>
                                @error('active_inactive')
                                <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="createCityForm" class="btn btn-primary btn-sm">Submit</button>
            </div>
        </div>
    </div>
</div>

{{-- Edit City Modal --}}
<div class="modal fade" id="editCityModal" tabindex="-1" aria-labelledby="editCityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editCityModalLabel">Edit City</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="editCityForm" action="">
                    @csrf
                    @method('PUT')
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_country_master_pk" class="form-label">Select Country</label>
                                <select class="form-select" id="edit_country_master_pk" name="country_master_pk" required>
                                    <option value="">-- Select Country --</option>
                                    @foreach($countries ?? [] as $country)
                                    <option value="{{ $country->pk }}">
                                        {{ $country->country_name }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('country_master_pk')
                                <p class="text-danger">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_state_master_pk" class="form-label">State</label>
                                <select name="state_master_pk" id="edit_state_master_pk" class="form-select" required>
                                    <option value="">Select State</option>
                                </select>
                                @error('state_master_pk')
                                <p class="text-danger">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_district_master_pk" class="form-label">District</label>
                                <select name="district_master_pk" id="edit_district_master_pk" class="form-select" required>
                                    <option value="">Select District</option>
                                </select>
                                @error('district_master_pk')
                                <p class="text-danger">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_city_name" class="form-label">City Name</label>
                                <input type="text" name="city_name" id="edit_city_name" class="form-control" required>
                                @error('city_name')
                                <p class="text-danger">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_active_inactive" class="form-label">Status <span style="color:red;">*</span></label>
                                <select name="active_inactive" id="edit_active_inactive" class="form-select" required>
                                    <option value="1">Active</option>
                                    <option value="2">Inactive</option>
                                </select>
                                @error('active_inactive')
                                <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="editCityForm" class="btn btn-primary btn-sm">Update</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
{!! $dataTable->scripts() !!}
<script>
$(document).ready(function() {
    // Open create modal when there are validation errors
    @if($errors->has('city_name') || $errors->has('country_master_pk') || $errors->has('state_master_pk') || $errors->has('district_master_pk') || $errors->has('active_inactive'))
        var createModal = new bootstrap.Modal(document.getElementById('createCityModal'));
        createModal.show();
    @endif

    // Handle country change for create modal
    $('#create_country_master_pk').on('change', function() {
        let countryId = $(this).val();
        $('#create_state_master_pk').html('<option value="">Loading...</option>');
        $('#create_district_master_pk').html('<option value="">Select District</option>');

        if (countryId) {
            $.ajax({
                url: "{{ route('master.city.getStates') }}",
                type: "POST",
                data: {
                    country_id: countryId,
                    _token: "{{ csrf_token() }}"
                },
                success: function(states) {
                    $('#create_state_master_pk').empty().append('<option value="">Select State</option>');
                    $.each(states, function(key, state) {
                        $('#create_state_master_pk').append('<option value="' + state.pk + '">' + state.state_name + '</option>');
                    });
                }
            });
        } else {
            $('#create_state_master_pk').html('<option value="">Select State</option>');
        }
    });

    // Handle state change for create modal
    $('#create_state_master_pk').on('change', function() {
        let stateId = $(this).val();
        $('#create_district_master_pk').html('<option value="">Loading...</option>');

        if (stateId) {
            $.ajax({
                url: "{{ route('master.city.getDistricts') }}",
                type: "POST",
                data: {
                    state_id: stateId,
                    _token: "{{ csrf_token() }}"
                },
                success: function(districts) {
                    $('#create_district_master_pk').empty().append('<option value="">Select District</option>');
                    $.each(districts, function(key, district) {
                        $('#create_district_master_pk').append('<option value="' + district.pk + '">' + district.district_name + '</option>');
                    });
                }
            });
        } else {
            $('#create_district_master_pk').html('<option value="">Select District</option>');
        }
    });

    // Handle country change for edit modal
    $('#edit_country_master_pk').on('change', function() {
        let countryId = $(this).val();
        $('#edit_state_master_pk').html('<option value="">Loading...</option>');
        $('#edit_district_master_pk').html('<option value="">Select District</option>');

        if (countryId) {
            $.ajax({
                url: "{{ route('master.city.getStates') }}",
                type: "POST",
                data: {
                    country_id: countryId,
                    _token: "{{ csrf_token() }}"
                },
                success: function(states) {
                    $('#edit_state_master_pk').empty().append('<option value="">Select State</option>');
                    $.each(states, function(key, state) {
                        $('#edit_state_master_pk').append('<option value="' + state.pk + '">' + state.state_name + '</option>');
                    });
                    // Trigger state change to load districts if state was already selected
                    var selectedState = $('#editCityModal').data('selected-state');
                    if (selectedState) {
                        $('#edit_state_master_pk').val(selectedState).trigger('change');
                    }
                }
            });
        } else {
            $('#edit_state_master_pk').html('<option value="">Select State</option>');
        }
    });

    // Handle state change for edit modal
    $('#edit_state_master_pk').on('change', function() {
        let stateId = $(this).val();
        $('#edit_district_master_pk').html('<option value="">Loading...</option>');

        if (stateId) {
            $.ajax({
                url: "{{ route('master.city.getDistricts') }}",
                type: "POST",
                data: {
                    state_id: stateId,
                    _token: "{{ csrf_token() }}"
                },
                success: function(districts) {
                    $('#edit_district_master_pk').empty().append('<option value="">Select District</option>');
                    $.each(districts, function(key, district) {
                        $('#edit_district_master_pk').append('<option value="' + district.pk + '">' + district.district_name + '</option>');
                    });
                    // Set selected district if it was already selected
                    var selectedDistrict = $('#editCityModal').data('selected-district');
                    if (selectedDistrict) {
                        $('#edit_district_master_pk').val(selectedDistrict);
                    }
                }
            });
        } else {
            $('#edit_district_master_pk').html('<option value="">Select District</option>');
        }
    });

    // Populate edit modal when opened
    $('#editCityModal').on('show.bs.modal', function(event) {
        var button = $(event.relatedTarget);
        var cityId = button.data('id');
        var cityName = button.data('city-name');
        var countryPk = button.data('country-pk');
        var statePk = button.data('state-pk');
        var districtPk = button.data('district-pk');
        var status = button.data('status');
        var updateUrl = button.data('update-url');

        // Set form action
        $('#editCityForm').attr('action', updateUrl);
        
        // Set basic fields
        $('#edit_city_name').val(cityName || '');
        $('#edit_active_inactive').val(status || '1');
        $('#edit_country_master_pk').val(countryPk || '');

        // Store selected state and district for later use
        $('#editCityModal').data('selected-state', statePk);
        $('#editCityModal').data('selected-district', districtPk);

        // Reset state and district dropdowns
        $('#edit_state_master_pk').html('<option value="">Loading...</option>');
        $('#edit_district_master_pk').html('<option value="">Loading...</option>');

        // Load states if country is selected
        if (countryPk) {
            $.ajax({
                url: "{{ route('master.city.getStates') }}",
                type: "POST",
                data: {
                    country_id: countryPk,
                    _token: "{{ csrf_token() }}"
                },
                success: function(states) {
                    $('#edit_state_master_pk').empty().append('<option value="">Select State</option>');
                    $.each(states, function(key, state) {
                        var selected = (state.pk == statePk) ? 'selected' : '';
                        $('#edit_state_master_pk').append('<option value="' + state.pk + '" ' + selected + '>' + state.state_name + '</option>');
                    });
                    
                    // Load districts if state is selected
                    if (statePk) {
                        $.ajax({
                            url: "{{ route('master.city.getDistricts') }}",
                            type: "POST",
                            data: {
                                state_id: statePk,
                                _token: "{{ csrf_token() }}"
                            },
                            success: function(districts) {
                                $('#edit_district_master_pk').empty().append('<option value="">Select District</option>');
                                $.each(districts, function(key, district) {
                                    var selected = (district.pk == districtPk) ? 'selected' : '';
                                    $('#edit_district_master_pk').append('<option value="' + district.pk + '" ' + selected + '>' + district.district_name + '</option>');
                                });
                            }
                        });
                    } else {
                        $('#edit_district_master_pk').html('<option value="">Select District</option>');
                    }
                }
            });
        } else {
            $('#edit_state_master_pk').html('<option value="">Select State</option>');
            $('#edit_district_master_pk').html('<option value="">Select District</option>');
        }
    });

    // Reset create modal when closed
    $('#createCityModal').on('hidden.bs.modal', function() {
        $('#createCityForm')[0].reset();
        $('#create_state_master_pk').html('<option value="">Select State</option>');
        $('#create_district_master_pk').html('<option value="">Select District</option>');
    });

    // Reset edit modal when closed
    $('#editCityModal').on('hidden.bs.modal', function() {
        $('#editCityForm')[0].reset();
        $('#edit_state_master_pk').html('<option value="">Select State</option>');
        $('#edit_district_master_pk').html('<option value="">Select District</option>');
        $('#editCityModal').removeData('selected-state');
        $('#editCityModal').removeData('selected-district');
    });

    // Handle status toggle for city table specifically - override global handler
    $(document).off('change', '#city-table .status-toggle').on('change', '#city-table .status-toggle', function(e) {
        let $checkbox = $(this);
        let table = $checkbox.data('table');
        let column = $checkbox.data('column');
        let id = $checkbox.data('id');
        let status = $checkbox.is(':checked') ? 1 : 0;
        let actionText = status === 1 ? 'activate' : 'deactivate';

        Swal.fire({
            title: 'Are you sure?',
            text: `Are you sure? You want to ${actionText} this item?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: `Yes, ${actionText}`,
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Proceed with AJAX call
                $.ajax({
                    url: "{{ route('admin.toggleStatus') }}",
                    type: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        table: table,
                        column: column,
                        id: id,
                        id_column: 'pk',
                        status: status
                    },
                    success: function (response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: response.message || 'Status updated successfully',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        // Reload the specific DataTable
                        if ($.fn.DataTable.isDataTable('#city-table')) {
                            $('#city-table').DataTable().ajax.reload(null, false);
                        }
                    },
                    error: function (xhr) {
                        Swal.fire('Error', xhr.responseJSON?.message || 'Status update failed', 'error');
                        // Revert checkbox if error
                        $checkbox.prop('checked', !status);
                    }
                });
            } else {
                // Revert checkbox back if cancelled
                $checkbox.prop('checked', !status);
            }
        });
    });
});
</script>
@endpush