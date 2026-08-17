@extends('admin.layouts.master')

@section('title', 'Class Session Master')

@push('styles')
{{-- Shared with Venue Master and Stream: same shape, one module stylesheet
     (docs/new-design-index-page.md §7). --}}
<link rel="stylesheet"
    href="{{ asset('css/basic-masters-admin.css') }}?v={{ @filemtime(public_path('css/basic-masters-admin.css')) }}">
@endpush

@section('setup_content')
<div class="container-fluid csm-master-page">
    <x-breadcrum title="Class Session Master" :showBack="false">
        {{-- Add and Edit are modals, not pages (§3c). --}}
        <button type="button"
            class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 rounded-1 fw-semibold shadow-sm"
            data-bs-toggle="modal" data-bs-target="#classSessionModal" data-mode="add">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Add Class Session</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    <div class="datatables">
        <div class="card border-0 shadow-sm rounded-1 overflow-hidden">
            <div class="card-body p-3 p-md-4">

                <div class="programme-dt-panel">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                            <thead>
                                <tr>
                                    <th scope="col" class="text-nowrap">S. No.</th>
                                    <th scope="col">Shift Name</th>
                                    <th scope="col" class="text-nowrap">Start Time</th>
                                    <th scope="col" class="text-nowrap">End Time</th>
                                    <th scope="col" class="text-nowrap text-center">Status</th>
                                    <th scope="col" class="text-nowrap text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($classSessionMaster as $index => $classSession)
                                    @php $csmIsActive = $classSession->active_inactive == 1; @endphp
                                    <tr>
                                        <td>{{ $classSessionMaster->firstItem() + $index }}</td>
                                        <td>{{ $classSession->shift_name ?? 'N/A' }}</td>
                                        <td>{{ $classSession->start_time ?? 'N/A' }}</td>
                                        <td>{{ $classSession->end_time ?? 'N/A' }}</td>

                                        {{-- Status: soft badge, display only (§3b) --}}
                                        <td class="text-center">
                                            <span class="status-pill badge rounded-1 {{ $csmIsActive ? 'bg-success-subtle' : 'bg-danger-subtle' }}"
                                                data-order="{{ (int) $csmIsActive }}">
                                                {{ $csmIsActive ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>

                                        {{-- Action: Edit · the status switch · Delete — visible in the cell as
                                             equal-width icon-over-label stacks (§3b). Edit opens the modal. --}}
                                        <td>
                                            <div class="bm-act-group" role="group" aria-label="Class session actions">
                                                <button type="button" class="bm-act bm-act--edit"
                                                    title="Edit" aria-label="Edit class session"
                                                    data-bs-toggle="modal" data-bs-target="#classSessionModal"
                                                    data-mode="edit"
                                                    data-session-id="{{ encrypt($classSession->pk) }}"
                                                    data-shift-name="{{ $classSession->shift_name }}"
                                                    data-start-time="{{ \Illuminate\Support\Str::substr($classSession->start_time, 0, 5) }}"
                                                    data-end-time="{{ \Illuminate\Support\Str::substr($classSession->end_time, 0, 5) }}">
                                                    <span class="bm-act__icon"><i class="bi bi-pencil" aria-hidden="true"></i></span>
                                                    <span class="bm-act__label">Edit</span>
                                                </button>

                                                {{-- No .form-check/.form-switch wrapper (§3b trap 1):
                                                     custom.css pulls a .form-check-input inside one left
                                                     by -2.375rem. custom.js binds .status-toggle globally
                                                     off these data-* attributes. --}}
                                                <label class="bm-act bm-act--toggle"
                                                    title="{{ $csmIsActive ? 'Deactivate' : 'Activate' }}">
                                                    <span class="bm-act__icon">
                                                        <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                            data-table="class_session_master" data-column="active_inactive"
                                                            data-id="{{ $classSession->pk }}"
                                                            {{ $csmIsActive ? 'checked' : '' }}
                                                            aria-label="{{ $csmIsActive ? 'Deactivate' : 'Activate' }} class session">
                                                    </span>
                                                    <span class="bm-act__label">{{ $csmIsActive ? 'Deactivate' : 'Activate' }}</span>
                                                </label>

                                                @if ($csmIsActive)
                                                    {{-- An active session cannot be deleted — mirror that guard
                                                         rather than offering a control that fails. --}}
                                                    <span class="bm-act bm-act--del is-disabled" aria-disabled="true"
                                                        title="Deactivate this class session before deleting">
                                                        <span class="bm-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>
                                                        <span class="bm-act__label">Delete</span>
                                                    </span>
                                                @else
                                                    <form action="{{ route('master.class.session.delete', ['id' => encrypt($classSession->pk)]) }}"
                                                        method="POST" class="bm-act bm-act--del"
                                                        onsubmit="return confirm('Are you sure you want to delete this record?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="bm-act__btn"
                                                            title="Delete" aria-label="Delete class session">
                                                            <span class="bm-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>
                                                            <span class="bm-act__label">Delete</span>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <i class="bi bi-inbox fs-2 d-block mb-2 opacity-50" aria-hidden="true"></i>
                                            <span class="fw-medium">No class sessions found.</span>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- §4 variant B: a Laravel paginator wearing the shared footer chrome. --}}
                    @if ($classSessionMaster->total() > 0)
                        <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div class="programme-dt-pagination">
                                {{ $classSessionMaster->links('vendor.pagination.custom') }}
                            </div>
                            {{-- "Showing [10 v] of N items" — the rows-per-page select the
                                 DataTables pages get from the enhancer, hand-written here
                                 because this grid is a Laravel paginator (§4B). --}}
                            <div class="programme-dt-count d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                                <div class="dataTables_length">
                                    <label class="mb-0">Showing
                                        <select id="csmPerPage" class="form-select form-select-sm"
                                            aria-label="Rows per page">
                                            @foreach ($perPageOptions as $option)
                                                <option value="{{ $option }}" @selected($perPage === $option)>{{ $option }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                </div>
                                <div class="dataTables_info">of {{ number_format($classSessionMaster->total()) }} items</div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Add / Edit modal (§3c). store() already handles both — it updates when
         an encrypted id is posted — so one form serves the two modes. --}}
    <div class="modal fade bm-modal" id="classSessionModal" tabindex="-1"
        aria-labelledby="classSessionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg">
                <form method="POST" action="{{ route('master.class.session.store') }}" id="classSessionForm">
                    @csrf
                    <input type="hidden" name="id" id="csm_id" value="{{ old('id') }}">
                    <input type="hidden" name="form_mode" id="csmFormMode" value="{{ old('form_mode', 'add') }}">

                    <div class="bm-modal-header">
                        <h5 class="bm-modal-title" id="classSessionModalLabel">Add Class Session</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="bm-modal-body">
                        <div class="bm-field-card">
                            <div class="mb-3">
                                <label for="csm_shift_name" class="bm-form-label">
                                    Shift Name <span class="bm-req">*</span>
                                </label>
                                <input type="text" id="csm_shift_name" name="shift_name"
                                    class="bm-control @error('shift_name') is-invalid @enderror"
                                    value="{{ old('shift_name') }}" placeholder="Enter shift name" required>
                                @error('shift_name')
                                    <span class="bm-field-error">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <label for="csm_start_time" class="bm-form-label">
                                        Start Time <span class="bm-req">*</span>
                                    </label>
                                    <input type="time" id="csm_start_time" name="start_time"
                                        class="bm-control @error('start_time') is-invalid @enderror"
                                        value="{{ old('start_time') }}" required>
                                    @error('start_time')
                                        <span class="bm-field-error">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-sm-6">
                                    <label for="csm_end_time" class="bm-form-label">
                                        End Time <span class="bm-req">*</span>
                                    </label>
                                    <input type="time" id="csm_end_time" name="end_time"
                                        class="bm-control @error('end_time') is-invalid @enderror"
                                        value="{{ old('end_time') }}" required>
                                    @error('end_time')
                                        <span class="bm-field-error">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bm-modal-footer">
                        <button type="button" class="btn bm-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn bm-btn-submit" id="csmSubmit">Save Session</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
window.statusToggleUrl = "{{ route('admin.toggleStatus') }}";

// On DOMContentLoaded, not inline: this section renders BEFORE the footer's
// script tags, so `bootstrap` does not exist yet at parse time.
document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('classSessionModal');
    var form = document.getElementById('classSessionForm');
    if (!modal || !form) { return; }

    var elId = document.getElementById('csm_id');
    var elMode = document.getElementById('csmFormMode');
    var elTitle = document.getElementById('classSessionModalLabel');
    var elSubmit = document.getElementById('csmSubmit');

    function applyMode(mode) {
        var editing = mode === 'edit';
        elMode.value = editing ? 'edit' : 'add';
        elTitle.textContent = editing ? 'Edit Class Session' : 'Add Class Session';
        elSubmit.textContent = editing ? 'Update Session' : 'Save Session';
    }

    modal.addEventListener('show.bs.modal', function (event) {
        var trigger = event.relatedTarget;
        if (!trigger) { return; }

        var data = trigger.dataset;
        applyMode(data.mode);
        elId.value = data.sessionId || '';
        document.getElementById('csm_shift_name').value = data.shiftName || '';
        document.getElementById('csm_start_time').value = data.startTime || '';
        document.getElementById('csm_end_time').value = data.endTime || '';

        // A failed submit leaves its messages behind; a fresh open must not
        // show the previous attempt's errors.
        form.querySelectorAll('.bm-field-error').forEach(function (node) { node.remove(); });
        form.querySelectorAll('.is-invalid').forEach(function (node) { node.classList.remove('is-invalid'); });
    });

    modal.addEventListener('hidden.bs.modal', function () {
        form.reset();
        elId.value = '';
        applyMode('add');
    });

    @if ($errors->any())
        // Validation bounced the save: reopen the dialog in the mode it was in,
        // with the values old() has already put back into the fields.
        applyMode(elMode.value);
        new bootstrap.Modal(modal).show();
    @endif

    var perPage = document.getElementById('csmPerPage');
    if (perPage) {
        perPage.addEventListener('change', function () {
            var url = new URL(window.location.href);
            url.searchParams.set('per_page', this.value);
            url.searchParams.set('page', '1');   // page 1, or the user lands past the end
            window.location.href = url.toString();
        });
    }
});
</script>
@endsection
