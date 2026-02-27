@extends('admin.layouts.master')

@section('title', 'State - Sargam | Lal Bahadur')

@section('setup_content')
<div class="container-fluid state-index">
    <x-breadcrum title="State" />
    <x-session_message />
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>State</h4>
                        </div>
                        <div class="col-6">
                            <div class="d-flex justify-content-end align-items-end mb-3">
                                <div class="d-flex align-items-center gap-2">

                                    <!-- Add New Button -->
                                    <button type="button"
                                        class="btn btn-primary px-3 py-2 rounded-1 shadow-sm d-flex align-items-center gap-2"
                                        data-bs-toggle="modal" data-bs-target="#stateFormModal" data-mode="create">
                                        <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 20px; vertical-align: middle;">add</i>
                                        Add New State
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
</div>

{{-- State Create / Edit Modal --}}
<div class="modal fade" id="stateFormModal" tabindex="-1" aria-labelledby="stateFormModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="stateFormModalLabel">Add New State</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="stateForm" method="POST" action="{{ route('master.state.store') }}">
                    @csrf
                    <input type="hidden" name="_method" id="stateFormMethod" value="">
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
                                <label for="modal_state_name" class="form-label">State Name <span style="color:red;">*</span></label>
                                <input type="text" class="form-control" id="modal_state_name" name="state_name" value="{{ old('state_name') }}" required>
                                @error('state_name')
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
                <button type="submit" form="stateForm" class="btn btn-primary btn-sm" id="stateFormSubmitBtn">Save</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{!! $dataTable->scripts() !!}
<script>
document.addEventListener('DOMContentLoaded', function() {
    var stateFormModal = document.getElementById('stateFormModal');
    var stateForm = document.getElementById('stateForm');
    var stateFormMethod = document.getElementById('stateFormMethod');
    var modalTitle = document.getElementById('stateFormModalLabel');
    var submitBtn = document.getElementById('stateFormSubmitBtn');
    var storeUrl = "{{ route('master.state.store') }}";

    function openCreateModal() {
        modalTitle.textContent = 'Add New State';
        submitBtn.textContent = 'Save';
        stateForm.action = storeUrl;
        stateFormMethod.value = '';
        stateFormMethod.removeAttribute('name');
        document.getElementById('modal_country_master_pk').value = '{{ old("country_master_pk", "") }}';
        document.getElementById('modal_state_name').value = '{{ old("state_name", "") }}';
        document.getElementById('modal_active_inactive').value = '{{ old("active_inactive", "1") }}';
    }

    function openEditModal(btn) {
        var pk = btn.getAttribute('data-pk');
        var stateName = btn.getAttribute('data-state-name') || '';
        var countryPk = btn.getAttribute('data-country-pk') || '';
        var activeInactive = btn.getAttribute('data-active-inactive') || '1';
        var updateUrl = btn.getAttribute('data-update-url') || '';
        if (!updateUrl) return;
        modalTitle.textContent = 'Edit State';
        submitBtn.textContent = 'Update';
        stateForm.action = updateUrl;
        stateFormMethod.value = 'PUT';
        stateFormMethod.setAttribute('name', '_method');
        document.getElementById('modal_country_master_pk').value = countryPk;
        document.getElementById('modal_state_name').value = stateName;
        document.getElementById('modal_active_inactive').value = activeInactive;
    }

    stateFormModal.addEventListener('show.bs.modal', function(event) {
        var trigger = event.relatedTarget;
        if (trigger) {
            if (trigger.getAttribute('data-mode') === 'create') {
                openCreateModal();
            } else if (trigger.getAttribute('data-mode') === 'edit' || trigger.classList.contains('open-state-edit-modal')) {
                openEditModal(trigger);
            }
        }
    });

    @if($errors->has('state_name') || $errors->has('country_master_pk') || $errors->has('active_inactive'))
    (function() {
        var modal = new bootstrap.Modal(stateFormModal);
        modal.show();
        var editId = "{{ session('state_modal_edit_id', '') }}";
        if (editId) {
            var updateUrlTemplate = "{{ route('master.state.update', ['id' => 0]) }}";
            stateForm.action = updateUrlTemplate.replace(/\/0$/, '/' + editId);
            modalTitle.textContent = 'Edit State';
            submitBtn.textContent = 'Update';
            stateFormMethod.value = 'PUT';
            stateFormMethod.setAttribute('name', '_method');
        } else {
            openCreateModal();
        }
        document.getElementById('modal_country_master_pk').value = '{{ old("country_master_pk", "") }}';
        document.getElementById('modal_state_name').value = '{{ old("state_name", "") }}';
        document.getElementById('modal_active_inactive').value = '{{ old("active_inactive", "1") }}';
    })();
    @endif
});
</script>
@endpush