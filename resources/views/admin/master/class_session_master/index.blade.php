@extends('admin.layouts.master')

@section('title', 'Class Session Master')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<style>
.master-filter-select {
    height: 40px; width: 150px; padding: 0 2rem 0 0.875rem; font-size: 0.9375rem;
    color: #344054; background-color: #fff; border: 1px solid #d0d5dd; border-radius: 8px;
}
.master-filter-select:focus { border-color: #004a93; box-shadow: 0 0 0 3px rgba(0, 74, 147, 0.12); }
.master-action-toggle { display: inline-flex; align-items: center; margin: 0 0.25rem 0 0.15rem; }
.master-action-toggle .form-check-input { margin: 0; cursor: pointer; }
.programme-action-group .material-symbols-rounded { font-size: 18px; line-height: 1; }
</style>
@endpush

@section('content')
@php
    // Times are stored as H:i:s; <input type="time"> expects H:i.
    $timeValue = function ($value) {
        if (empty($value)) {
            return '';
        }
        try {
            return \Carbon\Carbon::parse($value)->format('H:i');
        } catch (\Exception $e) {
            return (string) $value;
        }
    };
@endphp

<div class="container-fluid class-session-index-page py-3">
    <x-breadcrum title="Class Session Master">
        <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm"
                data-bs-toggle="modal" data-bs-target="#sessionCreateModal">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Add Class Session</span>
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
                                <th>Shift Name</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th>Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($classSessionMaster as $classSession)
                                @php
                                    $isActive = (int) $classSession->active_inactive === 1;
                                    $encId = encrypt($classSession->pk);
                                @endphp
                                <tr data-status="{{ $isActive ? 1 : 0 }}">
                                    <td class="fw-medium ps-3">{{ $loop->iteration }}</td>
                                    <td>{{ $classSession->shift_name ?? 'N/A' }}</td>
                                    <td>{{ $classSession->start_time ?? 'N/A' }}</td>
                                    <td>{{ $classSession->end_time ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge rounded-1 master-status-badge bg-{{ $isActive ? 'success' : 'secondary' }}">
                                            {{ $isActive ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-inline-flex align-items-center justify-content-center programme-action-group" role="group" aria-label="Class session actions">
                                            {{-- data-pk carries the ENCRYPTED id: store() decrypts it to decide insert vs update. --}}
                                            <button type="button" class="programme-action-btn master-edit-btn" title="Edit"
                                                    data-pk="{{ $encId }}"
                                                    data-name="{{ $classSession->shift_name }}"
                                                    data-start="{{ $timeValue($classSession->start_time) }}"
                                                    data-end="{{ $timeValue($classSession->end_time) }}"
                                                    data-status="{{ $isActive ? 1 : 0 }}">
                                                <i class="material-icons material-symbols-rounded" aria-hidden="true">edit</i>
                                            </button>

                                            <span class="master-action-toggle form-check form-switch mb-0" title="Toggle status">
                                                <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                    data-table="class_session_master" data-column="active_inactive"
                                                    data-id="{{ $classSession->pk }}"
                                                    {{ $isActive ? 'checked' : '' }}>
                                            </span>

                                            @if($isActive)
                                                <button type="button" class="programme-action-btn" disabled aria-disabled="true"
                                                        title="Cannot delete an active class session">
                                                    <i class="material-icons material-symbols-rounded" aria-hidden="true">delete</i>
                                                </button>
                                            @else
                                                <form action="{{ route('master.class.session.delete', ['id' => $encId]) }}"
                                                      method="POST" class="d-inline"
                                                      onsubmit="return confirm('Are you sure you want to delete this record?');">
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
                                    <td colspan="6" class="text-center py-5 table-empty-state">
                                        <div class="d-inline-flex flex-column align-items-center p-5 bg-body-tertiary rounded-4 border border-body-secondary">
                                            <i class="material-icons material-symbols-rounded mb-3 text-body-tertiary" style="font-size:56px;">schedule</i>
                                            <p class="mb-1 fw-semibold text-body-emphasis">No class sessions found.</p>
                                            <button type="button" class="btn btn-primary rounded-1 px-4 py-2 mt-2"
                                                    data-bs-toggle="modal" data-bs-target="#sessionCreateModal">Add Class Session</button>
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
<div class="modal fade" id="sessionCreateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="{{ route('master.class.session.store') }}" method="POST">
                @csrf
                <input type="hidden" name="_form" value="create">
                <div class="modal-header border-0 pb-2">
                    <h5 class="modal-title fw-bold">Add Class Session</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-0">
                    <div class="mb-3">
                        <label class="form-label">Shift Name <span class="text-danger">*</span></label>
                        <input type="text" name="shift_name" class="form-control" placeholder="Enter shift name"
                               value="{{ old('_form') === 'create' ? old('shift_name') : '' }}" required>
                        @error('shift_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Start Time <span class="text-danger">*</span></label>
                            <input type="time" name="start_time" class="form-control"
                                   value="{{ old('_form') === 'create' ? old('start_time') : '' }}" required>
                            @error('start_time')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">End Time <span class="text-danger">*</span></label>
                            <input type="time" name="end_time" class="form-control"
                                   value="{{ old('_form') === 'create' ? old('end_time') : '' }}" required>
                            @error('end_time')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
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
{{-- There is no separate update route: store() handles both, switching on the
     hidden (encrypted) `id`. The action is therefore constant. --}}
<div class="modal fade" id="sessionEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form id="masterEditForm" action="{{ route('master.class.session.store') }}" method="POST">
                @csrf
                <input type="hidden" name="_form" value="edit">
                <input type="hidden" name="id" id="masterEditPk" value="{{ old('_form') === 'edit' ? old('id') : '' }}">
                <div class="modal-header border-0 pb-2">
                    <h5 class="modal-title fw-bold">Edit Class Session</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-0">
                    <div class="mb-3">
                        <label class="form-label">Shift Name <span class="text-danger">*</span></label>
                        <input type="text" name="shift_name" id="masterEditName" class="form-control"
                               value="{{ old('_form') === 'edit' ? old('shift_name') : '' }}" required>
                        @error('shift_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Start Time <span class="text-danger">*</span></label>
                            <input type="time" name="start_time" id="masterEditStart" class="form-control"
                                   value="{{ old('_form') === 'edit' ? old('start_time') : '' }}" required>
                            @error('start_time')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">End Time <span class="text-danger">*</span></label>
                            <input type="time" name="end_time" id="masterEditEnd" class="form-control"
                                   value="{{ old('_form') === 'edit' ? old('end_time') : '' }}" required>
                            @error('end_time')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <p class="text-muted small mb-0 mt-3">
                        Status is changed with the switch in the Actions column.
                    </p>
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
    {{-- Constant action (no __ID__ placeholder) — store() switches on the hidden id. --}}
    'updateUrl'   => route('master.class.session.store'),
    'createModal' => 'sessionCreateModal',
    'editModal'   => 'sessionEditModal',
    'fields'      => [
        'name'  => '#masterEditName',
        'start' => '#masterEditStart',
        'end'   => '#masterEditEnd',
    ],
])

@include('admin.partials._master_list_scripts', [
    'reportTitle'  => 'Class Session Master',
    'storageKey'   => 'classSessionGrid:hiddenColumns:v1',
    'statusColumn' => 4,
    'actionColumn' => 5,
    'printColumns' => [
        ['label' => 'Shift Name', 'index' => 1],
        ['label' => 'Start Time', 'index' => 2],
        ['label' => 'End Time', 'index' => 3],
    ],
])
@endpush
