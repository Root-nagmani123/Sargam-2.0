@extends('admin.layouts.master')

@section('title', 'Stream')

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
<div class="container-fluid stream-index-page py-3">
    <x-breadcrum title="Stream">
        <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm"
                data-bs-toggle="modal" data-bs-target="#streamCreateModal">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Add Stream</span>
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
                                <th>Stream Name</th>
                                <th>Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($streams as $stream)
                                {{-- stream_master stores the flag in `active_inactive` (there is no `status` column). --}}
                                @php $isActive = (int) $stream->active_inactive === 1; @endphp
                                <tr data-status="{{ $isActive ? 1 : 0 }}">
                                    <td class="fw-medium ps-3">{{ $loop->iteration }}</td>
                                    <td>{{ $stream->stream_name }}</td>
                                    <td>
                                        <span class="badge rounded-1 master-status-badge bg-{{ $isActive ? 'success' : 'secondary' }}">
                                            {{ $isActive ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-inline-flex align-items-center justify-content-center programme-action-group" role="group" aria-label="Stream actions">
                                            <button type="button" class="programme-action-btn master-edit-btn" title="Edit"
                                                    data-pk="{{ $stream->pk }}"
                                                    data-name="{{ $stream->stream_name }}"
                                                    data-status="{{ $isActive ? 1 : 0 }}">
                                                <i class="material-icons material-symbols-rounded" aria-hidden="true">edit</i>
                                            </button>

                                            <span class="master-action-toggle form-check form-switch mb-0" title="Toggle status">
                                                <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                    data-table="stream_master" data-column="active_inactive"
                                                    data-id="{{ $stream->pk }}"
                                                    {{ $isActive ? 'checked' : '' }}>
                                            </span>

                                            @if($isActive)
                                                <button type="button" class="programme-action-btn" disabled aria-disabled="true"
                                                        title="Cannot delete an active stream">
                                                    <i class="material-icons material-symbols-rounded" aria-hidden="true">delete</i>
                                                </button>
                                            @else
                                                <form action="{{ route('stream.destroy', $stream->pk) }}" method="POST"
                                                      class="d-inline"
                                                      onsubmit="return confirm('Are you sure you want to delete this stream?');">
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
                                    <td colspan="4" class="text-center py-5 table-empty-state">
                                        <div class="d-inline-flex flex-column align-items-center p-5 bg-body-tertiary rounded-4 border border-body-secondary">
                                            <i class="material-icons material-symbols-rounded mb-3 text-body-tertiary" style="font-size:56px;">account_tree</i>
                                            <p class="mb-1 fw-semibold text-body-emphasis">No streams found.</p>
                                            <button type="button" class="btn btn-primary rounded-1 px-4 py-2 mt-2"
                                                    data-bs-toggle="modal" data-bs-target="#streamCreateModal">Add Stream</button>
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
<div class="modal fade" id="streamCreateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="{{ route('stream.store') }}" method="POST">
                @csrf
                <input type="hidden" name="_form" value="create">
                <div class="modal-header border-0 pb-2">
                    <h5 class="modal-title fw-bold">Add Stream</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-0">
                    <label class="form-label">Stream Name <span class="text-danger">*</span></label>
                    <div id="streamNameRows">
                        @php $oldNames = old('_form') === 'create' ? (array) old('stream_name', ['']) : ['']; @endphp
                        @foreach($oldNames as $oldName)
                            <div class="input-group mb-2 stream-name-row">
                                <input type="text" name="stream_name[]" class="form-control" value="{{ $oldName }}"
                                       placeholder="Enter stream name" required>
                                <button type="button" class="btn btn-outline-secondary stream-name-remove" title="Remove">
                                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                                </button>
                            </div>
                        @endforeach
                    </div>
                    @error('stream_name.*')<div class="invalid-feedback d-block mb-2">{{ $message }}</div>@enderror
                    <button type="button" class="btn btn-sm btn-outline-primary rounded-1" id="streamAddRow">
                        <i class="bi bi-plus-lg" aria-hidden="true"></i> Add another
                    </button>
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
<div class="modal fade" id="streamEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            {{-- Resource route: update expects PUT. --}}
            <form id="masterEditForm" action="" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="_form" value="edit">
                <input type="hidden" name="_pk" id="masterEditPk" value="{{ old('_pk') }}">
                <div class="modal-header border-0 pb-2">
                    <h5 class="modal-title fw-bold">Edit Stream</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-0">
                    <div>
                        <label class="form-label">Stream Name <span class="text-danger">*</span></label>
                        <input type="text" name="stream_name" id="masterEditName" class="form-control"
                               value="{{ old('_form') === 'edit' ? old('stream_name') : '' }}" required>
                        @error('stream_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
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
<script>
    window.statusToggleUrl = "{{ route('admin.toggleStatus') }}";
</script>

{{-- Create modal: repeatable "Stream Name" rows (store() accepts an array). --}}
<script>
$(function () {
    $('#streamAddRow').on('click', function () {
        var $row = $('#streamNameRows .stream-name-row').first().clone();
        $row.find('input').val('');
        $('#streamNameRows').append($row);
    });
    $('#streamNameRows').on('click', '.stream-name-remove', function () {
        if ($('#streamNameRows .stream-name-row').length > 1) {
            $(this).closest('.stream-name-row').remove();
        } else {
            $(this).closest('.stream-name-row').find('input').val('');
        }
    });
});
</script>

@include('admin.partials._master_form_scripts', [
    'updateUrl'   => route('stream.update', ['stream' => '__ID__']),
    'createModal' => 'streamCreateModal',
    'editModal'   => 'streamEditModal',
    'fields'      => [
        'name' => '#masterEditName',
    ],
])

@include('admin.partials._master_list_scripts', [
    'reportTitle'  => 'Stream List',
    'storageKey'   => 'streamGrid:hiddenColumns:v1',
    'statusColumn' => 2,
    'actionColumn' => 3,
    'printColumns' => [
        ['label' => 'Stream Name', 'index' => 1],
    ],
])
@endpush
