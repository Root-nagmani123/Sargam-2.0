@extends('admin.layouts.master')

@section('title', 'State List')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<style>
.master-filter-select {
    height: 40px; width: 150px; padding: 0 2rem 0 0.875rem; font-size: 0.9375rem;
    color: #344054; background-color: #fff; border: 1px solid #d0d5dd; border-radius: 8px;
}
.master-filter-select:focus { border-color: #004a93; box-shadow: 0 0 0 3px rgba(0, 74, 147, 0.12); }
</style>
@endpush

@section('content')
<div class="container-fluid state-index-page py-3">
    <x-breadcrum title="State List">
        <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm"
                data-bs-toggle="modal" data-bs-target="#stateCreateModal">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Add State</span>
        </button>
    </x-breadcrum>
    <x-session_message />

    <div class="d-flex flex-wrap justify-content-end align-items-center gap-3 mb-3">
        <button type="button" class="btn programme-dt-btn-columns border-0 text-primary" id="masterPrintBtn" title="Print">
            <i class="bi bi-printer" aria-hidden="true"></i> <span>Print</span>
        </button>
    </div>

    <div class="card overflow-hidden rounded-1">
        <div class="card-body p-3 p-md-4">

            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Filters</span>
                    <select id="masterStatusFilter" class="form-select master-filter-select" aria-label="Status">
                        <option value="all">All Status</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                    <button type="button" class="btn programme-dt-btn-reset" id="masterResetFilters">Reset Filters</button>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="masterBtnColumns"
                        data-bs-toggle="modal" data-bs-target="#masterColumnVisibilityModal" title="Show / hide columns">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div class="programme-dt-search" data-dt-search-for="masterTable"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table class="table table-hover text-nowrap align-middle programme-dt-table" id="masterTable">
                        <thead>
                            <tr>
                                <th>S. No.</th>
                                <th>State Name</th>
                                <th>Country</th>
                                <th>Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($states as $state)
                                @php $isActive = (int) $state->active_inactive === 1; @endphp
                                <tr data-status="{{ $isActive ? 1 : 0 }}">
                                    <td class="fw-medium ps-3">{{ $loop->iteration }}</td>
                                    <td>{{ $state->state_name }}</td>
                                    <td>{{ optional($state->country)->country_name ?? '--' }}</td>
                                    <td>
                                        <span class="badge rounded-1 master-status-badge bg-{{ $isActive ? 'success' : 'secondary' }}">
                                            {{ $isActive ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-inline-flex align-items-center justify-content-center programme-action-group" role="group" aria-label="State actions">
                                            <button type="button" class="programme-action-btn master-edit-btn" title="Edit"
                                                    data-pk="{{ $state->pk }}"
                                                    data-name="{{ $state->state_name }}"
                                                    data-country="{{ $state->country_master_pk }}"
                                                    data-status="{{ $isActive ? 1 : 0 }}">
                                                <i class="material-icons material-symbols-rounded" aria-hidden="true">edit</i>
                                            </button>

                                            <span class="master-action-toggle form-check form-switch mb-0" title="Toggle status">
                                                <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                    data-table="state_master" data-column="active_inactive"
                                                    data-id="{{ $state->pk }}"
                                                    {{ $isActive ? 'checked' : '' }}>
                                            </span>

                                            @if($isActive)
                                                <button type="button" class="programme-action-btn" disabled aria-disabled="true"
                                                        title="Cannot delete an active state">
                                                    <i class="material-icons material-symbols-rounded" aria-hidden="true">delete</i>
                                                </button>
                                            @else
                                                <form action="{{ route('master.state.delete', $state->pk) }}" method="POST"
                                                      class="d-inline"
                                                      onsubmit="return confirm('Are you sure you want to delete this?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="programme-action-btn programme-action-btn--danger" title="Delete">
                                                        <i class="material-icons material-symbols-rounded" aria-hidden="true">delete</i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-5 table-empty-state">
                                        <div class="d-inline-flex flex-column align-items-center p-5 bg-body-tertiary rounded-4 border border-body-secondary">
                                            <i class="material-icons material-symbols-rounded mb-3 text-body-tertiary" style="font-size:56px;">map</i>
                                            <p class="mb-1 fw-semibold text-body-emphasis">No states found.</p>
                                            <button type="button" class="btn btn-primary rounded-1 px-4 py-2 mt-2"
                                                    data-bs-toggle="modal" data-bs-target="#stateCreateModal">Add State</button>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3" data-dt-footer-for="masterTable"></div>
            </div>
        </div>
    </div>
</div>

{{-- ============ Create modal ============ --}}
<div class="modal fade" id="stateCreateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="{{ route('master.state.store') }}" method="POST">
                @csrf
                <input type="hidden" name="_form" value="create">
                <div class="modal-header border-0 pb-2">
                    <h5 class="modal-title fw-bold">Add State</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-0">
                    <div class="mb-3">
                        <label class="form-label">Country <span class="text-danger">*</span></label>
                        <select name="country_master_pk" class="form-select" required>
                            <option value="">-- Select Country --</option>
                            @foreach($countries as $c)
                                <option value="{{ $c->pk }}" {{ old('_form') === 'create' && (string) old('country_master_pk') === (string) $c->pk ? 'selected' : '' }}>{{ $c->country_name }}</option>
                            @endforeach
                        </select>
                        @error('country_master_pk')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">State Name <span class="text-danger">*</span></label>
                        <input type="text" name="state_name" class="form-control" placeholder="Enter state name"
                               value="{{ old('_form') === 'create' ? old('state_name') : '' }}" required>
                        @error('state_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="active_inactive" class="form-select" required>
                            <option value="1" {{ old('active_inactive', '1') == '1' ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ old('active_inactive') === '0' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ============ Edit modal ============ --}}
<div class="modal fade" id="stateEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form id="masterEditForm" action="" method="POST">
                @csrf
                <input type="hidden" name="_form" value="edit">
                <input type="hidden" name="_pk" id="masterEditPk" value="{{ old('_pk') }}">
                <div class="modal-header border-0 pb-2">
                    <h5 class="modal-title fw-bold">Edit State</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-0">
                    <div class="mb-3">
                        <label class="form-label">Country <span class="text-danger">*</span></label>
                        <select name="country_master_pk" id="masterEditCountry" class="form-select" required>
                            <option value="">-- Select Country --</option>
                            @foreach($countries as $c)
                                <option value="{{ $c->pk }}" {{ old('_form') === 'edit' && (string) old('country_master_pk') === (string) $c->pk ? 'selected' : '' }}>{{ $c->country_name }}</option>
                            @endforeach
                        </select>
                        @error('country_master_pk')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">State Name <span class="text-danger">*</span></label>
                        <input type="text" name="state_name" id="masterEditName" class="form-control"
                               value="{{ old('_form') === 'edit' ? old('state_name') : '' }}" required>
                        @error('state_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="active_inactive" id="masterEditStatus" class="form-select" required>
                            <option value="1" {{ old('_form') === 'edit' && old('active_inactive') == '1' ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ old('_form') === 'edit' && old('active_inactive') === '0' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

@include('admin.partials._master_columns_modal')
@endsection

@push('scripts')
@include('admin.partials._master_form_scripts', [
    'updateUrl'   => route('master.state.update', ['id' => '__ID__']),
    'createModal' => 'stateCreateModal',
    'editModal'   => 'stateEditModal',
    'fields'      => [
        'name'    => '#masterEditName',
        'country' => '#masterEditCountry',
        'status'  => '#masterEditStatus',
    ],
])

@include('admin.partials._master_list_scripts', [
    'reportTitle'  => 'State List',
    'storageKey'   => 'stateGrid:hiddenColumns:v1',
    'statusColumn' => 3,
    'actionColumn' => 4,
    'printColumns' => [
        ['label' => 'State Name', 'index' => 1],
        ['label' => 'Country', 'index' => 2],
    ],
])
@endpush
