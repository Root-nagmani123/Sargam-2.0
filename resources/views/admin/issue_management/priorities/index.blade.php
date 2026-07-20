@extends('admin.layouts.master')

@section('title', 'Issue Priorities')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<style>
.master-filter-select {
    height: 40px; padding: 0 2rem 0 0.875rem; font-size: 0.9375rem;
    color: #344054; background-color: #fff; border: 1px solid #d0d5dd; border-radius: 8px;
}
.master-filter-select:focus { border-color: #004a93; box-shadow: 0 0 0 3px rgba(0, 74, 147, 0.12); }
.master-filter-select--status { width: 140px; }

/* Keep the whole toolbar on one line; scroll horizontally rather than wrap. */
.master-toolbar { flex-wrap: nowrap; overflow-x: auto; }
.master-toolbar > * { flex: 0 0 auto; }
.master-toolbar::-webkit-scrollbar { height: 6px; }
.master-toolbar::-webkit-scrollbar-thumb { background: #d0d5dd; border-radius: 3px; }

.master-action-toggle { display: inline-flex; align-items: center; margin: 0 0.25rem 0 0.15rem; }
.master-action-toggle .form-check-input { margin: 0; cursor: pointer; }
.programme-action-group .material-symbols-rounded { font-size: 18px; line-height: 1; }
/* Description can be long — let it wrap instead of stretching the row. */
#masterTable td.priority-desc { white-space: normal; min-width: 240px; max-width: 460px; }
</style>
@endpush

@section('content')
<div class="container-fluid issue-priority-index-page py-3">
    <x-breadcrum title="Issue Priorities">
        <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm"
                data-bs-toggle="modal" data-bs-target="#addPriorityModal">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Add Priority</span>
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

            <div class="d-flex align-items-center gap-2 mb-4 programme-dt-toolbar master-toolbar">
                <span class="programme-dt-filters-label">Filters</span>
                <select id="masterStatusFilter" class="form-select master-filter-select master-filter-select--status" aria-label="Status">
                    <option value="all">All Status</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
                <button type="button" class="btn programme-dt-btn-reset" id="masterResetFilters">Reset Filters</button>

                <button type="button" class="btn programme-dt-btn-columns ms-auto" id="masterBtnColumns"
                    data-bs-toggle="modal" data-bs-target="#masterColumnVisibilityModal" title="Show / hide columns">
                    <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                </button>
                <div class="programme-dt-search" data-dt-search-for="masterTable"></div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table class="table table-hover text-nowrap align-middle programme-dt-table" id="masterTable">
                        <thead>
                            <tr>
                                <th>S. No.</th>
                                <th>Priority Name</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($priorities as $priority)
                                @php $isActive = (int) $priority->status === 1; @endphp
                                <tr data-status="{{ $isActive ? 1 : 0 }}">
                                    <td class="fw-medium ps-3">{{ $loop->iteration }}</td>
                                    <td>{{ $priority->priority }}</td>
                                    <td class="priority-desc">{{ $priority->description ?: '--' }}</td>
                                    <td>
                                        <span class="badge rounded-1 master-status-badge bg-{{ $isActive ? 'success' : 'secondary' }}">
                                            {{ $isActive ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-inline-flex align-items-center justify-content-center programme-action-group" role="group" aria-label="Priority actions">
                                            <button type="button" class="programme-action-btn master-edit-btn" title="Edit"
                                                    data-pk="{{ $priority->pk }}"
                                                    data-name="{{ $priority->priority }}"
                                                    data-description="{{ $priority->description }}"
                                                    data-status="{{ $isActive ? 1 : 0 }}">
                                                <i class="material-icons material-symbols-rounded" aria-hidden="true">edit</i>
                                            </button>

                                            <span class="master-action-toggle form-check form-switch mb-0" title="Toggle status">
                                                <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                    data-table="issue_priority_master" data-column="status"
                                                    data-id="{{ $priority->pk }}"
                                                    {{ $isActive ? 'checked' : '' }}>
                                            </span>

                                            {{-- destroy() only blocks priorities that have associated issues,
                                                 so Delete stays available regardless of status. --}}
                                            <form action="{{ route('admin.issue-priorities.destroy', $priority->pk) }}"
                                                  method="POST" class="d-inline"
                                                  onsubmit="return confirm('Are you sure you want to delete this priority?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="programme-action-btn programme-action-btn--danger" title="Delete">
                                                    <i class="material-icons material-symbols-rounded" aria-hidden="true">delete</i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-5 table-empty-state">
                                        <div class="d-inline-flex flex-column align-items-center p-5 bg-body-tertiary rounded-4 border border-body-secondary">
                                            <i class="material-icons material-symbols-rounded mb-3 text-body-tertiary" style="font-size:56px;">flag</i>
                                            <p class="mb-1 fw-semibold text-body-emphasis">No priorities found.</p>
                                            <button type="button" class="btn btn-primary rounded-1 px-4 py-2 mt-2"
                                                    data-bs-toggle="modal" data-bs-target="#addPriorityModal">Add Priority</button>
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

{{-- ============ Add modal ============ --}}
<div class="modal fade" id="addPriorityModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="{{ route('admin.issue-priorities.store') }}" method="POST">
                @csrf
                <input type="hidden" name="_form" value="create">
                <div class="modal-header border-0 pb-2">
                    <h5 class="modal-title fw-bold">Add New Priority</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-0">
                    <div class="mb-3">
                        <label class="form-label">Priority Name <span class="text-danger">*</span></label>
                        <input type="text" name="priority" class="form-control" placeholder="e.g. High, Medium, Low"
                               value="{{ old('_form') === 'create' ? old('priority') : '' }}" required>
                        @error('priority')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"
                                  placeholder="Optional description">{{ old('_form') === 'create' ? old('description') : '' }}</textarea>
                        @error('description')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
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
<div class="modal fade" id="editPriorityModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form id="masterEditForm" action="" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="_form" value="edit">
                <input type="hidden" name="_pk" id="masterEditPk" value="{{ old('_pk') }}">
                <div class="modal-header border-0 pb-2">
                    <h5 class="modal-title fw-bold">Edit Priority</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-0">
                    <div class="mb-3">
                        <label class="form-label">Priority Name <span class="text-danger">*</span></label>
                        <input type="text" name="priority" id="masterEditName" class="form-control"
                               value="{{ old('_form') === 'edit' ? old('priority') : '' }}" required>
                        @error('priority')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="masterEditDescription" class="form-control" rows="3">{{ old('_form') === 'edit' ? old('description') : '' }}</textarea>
                        @error('description')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="status" id="masterEditStatus" class="form-select" required>
                            <option value="1" {{ old('_form') === 'edit' && old('status') == '1' ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ old('_form') === 'edit' && old('status') === '0' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        <div class="form-text">You can also flip this with the switch in the Actions column.</div>
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
    'updateUrl'   => route('admin.issue-priorities.update', ['id' => '__ID__']),
    'createModal' => 'addPriorityModal',
    'editModal'   => 'editPriorityModal',
    'fields'      => [
        'name'        => '#masterEditName',
        'description' => '#masterEditDescription',
        'status'      => '#masterEditStatus',
    ],
])

@include('admin.partials._master_list_scripts', [
    'reportTitle'  => 'Issue Priorities',
    'storageKey'   => 'issuePriorityGrid:hiddenColumns:v1',
    'statusColumn' => 3,
    'actionColumn' => 4,
    'printColumns' => [
        ['label' => 'Priority Name', 'index' => 1],
        ['label' => 'Description', 'index' => 2],
    ],
])
@endpush
