@extends('admin.layouts.master')

@section('title', 'District - Sargam | Lal Bahadur')

@section('setup_content')
<div class="container-fluid district-index">
<x-breadcrum title="District" />
    <x-session_message />
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" style="border-left:4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row district-header-row">
                        <div class="col-12 col-md-6">
                            <h1 class="h4 mb-0">District</h1>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="d-flex justify-content-md-end justify-content-start align-items-center gap-2 mt-2 mt-md-0">
                                <button type="button"
                                    class="btn btn-primary d-flex align-items-center w-md-100 justify-content-center justify-content-md-start"
                                    data-bs-toggle="modal" data-bs-target="#districtFormModal" data-mode="create">
                                    <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 20px; vertical-align: middle;">add</i>
                                    Add New District
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table w-100 text-nowrap align-middle mb-0']) !!}
                </div>
            </div>
        </div>
        <!-- end Zero Configuration -->
    </div>
</div>

{{-- District Create / Edit Modal --}}
<div class="modal fade" id="districtFormModal" tabindex="-1" aria-labelledby="districtFormModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="districtFormModalLabel">Add New District</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="districtForm" method="POST" action="{{ route('master.district.store') }}">
                    @csrf
                    <input type="hidden" name="_method" id="districtFormMethod" value="">
                    <div class="row">
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="modal_country_master_pk" class="form-label">Select Country <span style="color:red;">*</span></label>
                                <select class="form-select" id="modal_country_master_pk" name="country_master_pk" required>
                                    <option value="">-- Select Country --</option>
                                    @foreach($countries as $country)
                                    <option value="{{ $country->pk }}">{{ $country->country_name }}</option>
                                    @endforeach
                                </select>
                                @error('country_master_pk')
                                <p class="text-danger">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="modal_state_master_pk" class="form-label">Select State <span style="color:red;">*</span></label>
                                <select class="form-select" id="modal_state_master_pk" name="state_master_pk" required>
                                    <option value="">-- Select State --</option>
                                    @foreach($states as $state)
                                    <option value="{{ $state->pk }}">{{ $state->state_name }}</option>
                                    @endforeach
                                </select>
                                @error('state_master_pk')
                                <p class="text-danger">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="modal_district_name" class="form-label">District Name <span style="color:red;">*</span></label>
                                <input type="text" class="form-control" id="modal_district_name" name="district_name" value="{{ old('district_name') }}" required>
                                @error('district_name')
                                <p class="text-danger">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="modal_active_inactive" class="form-label">Status <span style="color:red;">*</span></label>
                                <select name="active_inactive" id="modal_active_inactive" class="form-select" required>
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
                <button type="submit" form="districtForm" class="btn btn-primary btn-sm" id="districtFormSubmitBtn">Save</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
{!! $dataTable->scripts() !!}
<script>
$(document).ready(function() {
    var districtFormModal = $('#districtFormModal');
    var districtForm = $('#districtForm');
    var districtFormMethod = $('#districtFormMethod');
    var modalTitle = $('#districtFormModalLabel');
    var submitBtn = $('#districtFormSubmitBtn');
    var storeUrl = "{{ route('master.district.store') }}";
    var getStatesUrl = "{{ route('master.country.get.state.by.country') }}";

    // Handle country change - filter states
    $(document).on('change', '#modal_country_master_pk', function() {
        var countryId = $(this).val();
        var $stateSelect = $('#modal_state_master_pk');
        
        if (countryId !== '') {
            $.ajax({
                url: getStatesUrl,
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    country_id: countryId
                },
                success: function(response) {
                    if (response.status) {
                        $stateSelect.empty().append('<option value="">-- Select State --</option>');
                        response.states.forEach(function(state) {
                            $stateSelect.append('<option value="' + state.pk + '">' + state.state_name + '</option>');
                        });
                    } else {
                        console.error('Failed to load states');
                    }
                },
                error: function() {
                    console.error('Error fetching states');
                }
            });
        } else {
            $stateSelect.empty().append('<option value="">-- Select State --</option>');
            @foreach($states as $state)
            $stateSelect.append('<option value="{{ $state->pk }}">{{ $state->state_name }}</option>');
            @endforeach
        }
    });

    function openCreateModal() {
        modalTitle.text('Add New District');
        submitBtn.text('Save');
        districtForm.attr('action', storeUrl);
        districtFormMethod.val('').removeAttr('name');
        $('#modal_country_master_pk').val('{{ old("country_master_pk", "") }}');
        $('#modal_district_name').val('{{ old("district_name", "") }}');
        $('#modal_active_inactive').val('{{ old("active_inactive", "1") }}');
        
        // Reset state dropdown
        var $stateSelect = $('#modal_state_master_pk');
        var countryId = $('#modal_country_master_pk').val();
        if (countryId) {
            // Trigger change to load states
            $('#modal_country_master_pk').trigger('change');
            // Set the old value after states are loaded
            setTimeout(function() {
                $stateSelect.val('{{ old("state_master_pk", "") }}');
            }, 500);
        } else {
            $stateSelect.empty().append('<option value="">-- Select State --</option>');
            @foreach($states as $state)
            $stateSelect.append('<option value="{{ $state->pk }}">{{ $state->state_name }}</option>');
            @endforeach
            $stateSelect.val('{{ old("state_master_pk", "") }}');
        }
    }

    function openEditModal(btn) {
        var pk = $(btn).data('pk');
        var districtName = $(btn).data('district-name') || '';
        var countryPk = $(btn).data('country-pk') || '';
        var statePk = $(btn).data('state-pk') || '';
        var activeInactive = $(btn).data('active-inactive') || '1';
        var updateUrl = $(btn).data('update-url') || '';
        
        if (!updateUrl) return;
        
        modalTitle.text('Edit District');
        submitBtn.text('Update');
        districtForm.attr('action', updateUrl);
        districtFormMethod.val('PUT').attr('name', '_method');
        
        $('#modal_country_master_pk').val(countryPk);
        $('#modal_district_name').val(districtName);
        $('#modal_active_inactive').val(activeInactive);
        
        // Load states for selected country
        if (countryPk) {
            $.ajax({
                url: getStatesUrl,
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    country_id: countryPk
                },
                success: function(response) {
                    if (response.status) {
                        var $stateSelect = $('#modal_state_master_pk');
                        $stateSelect.empty().append('<option value="">-- Select State --</option>');
                        response.states.forEach(function(state) {
                            var option = '<option value="' + state.pk + '"';
                            if (state.pk == statePk) {
                                option += ' selected';
                            }
                            option += '>' + state.state_name + '</option>';
                            $stateSelect.append(option);
                        });
                    }
                },
                error: function() {
                    console.error('Error fetching states');
                }
            });
        }
    }

    districtFormModal.on('show.bs.modal', function(event) {
        var trigger = $(event.relatedTarget);
        if (trigger.length) {
            if (trigger.data('mode') === 'create') {
                openCreateModal();
            } else if (trigger.data('mode') === 'edit' || trigger.hasClass('open-district-edit-modal')) {
                openEditModal(trigger[0]);
            }
        }
    });

    @if($errors->has('district_name') || $errors->has('country_master_pk') || $errors->has('state_master_pk') || $errors->has('active_inactive'))
    (function() {
        var modal = new bootstrap.Modal(districtFormModal[0]);
        modal.show();
        var editId = "{{ session('district_modal_edit_id', '') }}";
        if (editId) {
            var updateUrlTemplate = "{{ route('master.district.update', ['id' => 0]) }}";
            districtForm.attr('action', updateUrlTemplate.replace(/\/0$/, '/' + editId));
            modalTitle.text('Edit District');
            submitBtn.text('Update');
            districtFormMethod.val('PUT').attr('name', '_method');
        } else {
            openCreateModal();
        }
        $('#modal_country_master_pk').val('{{ old("country_master_pk", "") }}');
        $('#modal_district_name').val('{{ old("district_name", "") }}');
        $('#modal_active_inactive').val('{{ old("active_inactive", "1") }}');
        
        // Load states if country is selected
        var countryId = $('#modal_country_master_pk').val();
        if (countryId) {
            $('#modal_country_master_pk').trigger('change');
            setTimeout(function() {
                $('#modal_state_master_pk').val('{{ old("state_master_pk", "") }}');
            }, 500);
        } else {
            $('#modal_state_master_pk').val('{{ old("state_master_pk", "") }}');
        }
    })();
    @endif
});
</script>
@endpush

@endsection