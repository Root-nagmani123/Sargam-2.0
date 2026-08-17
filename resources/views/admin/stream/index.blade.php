@extends('admin.layouts.master')

@section('title', 'Stream Master')

@push('styles')
{{-- Shared with Venue Master and Class Session Master: same shape, one module
     stylesheet (docs/new-design-index-page.md §7). --}}
<link rel="stylesheet"
    href="{{ asset('css/basic-masters-admin.css') }}?v={{ @filemtime(public_path('css/basic-masters-admin.css')) }}">
@endpush

@section('setup_content')
<div class="container-fluid stm-master-page">
    <x-breadcrum title="Stream Master" :showBack="false">
        {{-- Add and Edit are modals, not pages (§3c). --}}
        <button type="button"
            class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 rounded-1 fw-semibold shadow-sm"
            data-bs-toggle="modal" data-bs-target="#streamAddModal">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Add Stream</span>
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
                                    <th scope="col">Stream Name</th>
                                    <th scope="col" class="text-nowrap text-center">Status</th>
                                    <th scope="col" class="text-nowrap text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($streams as $key => $stream)
                                    @php $stmIsActive = $stream->status == 1; @endphp
                                    <tr>
                                        {{-- firstItem() + $key, not $key + 1: the old numbering
                                             restarted at 1 on every page. --}}
                                        <td>{{ $streams->firstItem() + $key }}</td>
                                        <td>{{ $stream->stream_name }}</td>

                                        {{-- Status: soft badge, display only (§3b) --}}
                                        <td class="text-center">
                                            <span class="status-pill badge rounded-1 {{ $stmIsActive ? 'bg-success-subtle' : 'bg-danger-subtle' }}"
                                                data-order="{{ (int) $stmIsActive }}">
                                                {{ $stmIsActive ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>

                                        {{-- Action: Edit · the status switch · Delete — visible in the cell as
                                             equal-width icon-over-label stacks (§3b). Edit opens the modal. --}}
                                        <td>
                                            <div class="bm-act-group" role="group" aria-label="Stream actions">
                                                <button type="button" class="bm-act bm-act--edit"
                                                    title="Edit" aria-label="Edit stream"
                                                    data-bs-toggle="modal" data-bs-target="#streamEditModal"
                                                    data-action="{{ route('stream.update', $stream->pk) }}"
                                                    data-stream-name="{{ $stream->stream_name }}">
                                                    <span class="bm-act__icon"><i class="bi bi-pencil" aria-hidden="true"></i></span>
                                                    <span class="bm-act__label">Edit</span>
                                                </button>

                                                {{-- No .form-check/.form-switch wrapper (§3b trap 1):
                                                     custom.css pulls a .form-check-input inside one left
                                                     by -2.375rem. custom.js binds .status-toggle globally
                                                     off these data-* attributes. --}}
                                                <label class="bm-act bm-act--toggle"
                                                    title="{{ $stmIsActive ? 'Deactivate' : 'Activate' }}">
                                                    <span class="bm-act__icon">
                                                        <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                            data-table="stream_master" data-column="status"
                                                            data-id="{{ $stream->pk }}"
                                                            {{ $stmIsActive ? 'checked' : '' }}
                                                            aria-label="{{ $stmIsActive ? 'Deactivate' : 'Activate' }} stream">
                                                    </span>
                                                    <span class="bm-act__label">{{ $stmIsActive ? 'Deactivate' : 'Activate' }}</span>
                                                </label>

                                                @if ($stmIsActive)
                                                    {{-- An active stream cannot be deleted — mirror that guard
                                                         rather than offering a control that fails. --}}
                                                    <span class="bm-act bm-act--del is-disabled" aria-disabled="true"
                                                        title="Deactivate this stream before deleting">
                                                        <span class="bm-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>
                                                        <span class="bm-act__label">Delete</span>
                                                    </span>
                                                @else
                                                    <form action="{{ route('stream.destroy', $stream->pk) }}" method="POST"
                                                        class="bm-act bm-act--del"
                                                        onsubmit="return confirm('Are you sure you want to delete this stream?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="bm-act__btn"
                                                            title="Delete" aria-label="Delete stream">
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
                                        <td colspan="4" class="text-center py-5 text-muted">
                                            <i class="bi bi-inbox fs-2 d-block mb-2 opacity-50" aria-hidden="true"></i>
                                            <span class="fw-medium">No streams found.</span>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- §4 variant B: a Laravel paginator wearing the shared footer chrome. --}}
                    @if ($streams->total() > 0)
                        <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div class="programme-dt-pagination">
                                {{ $streams->links('vendor.pagination.custom') }}
                            </div>
                            {{-- "Showing [10 v] of N items" — the rows-per-page select the
                                 DataTables pages get from the enhancer, hand-written here
                                 because this grid is a Laravel paginator (§4B). --}}
                            <div class="programme-dt-count d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                                <div class="dataTables_length">
                                    <label class="mb-0">Showing
                                        <select id="stmPerPage" class="form-select form-select-sm"
                                            aria-label="Rows per page">
                                            @foreach ($perPageOptions as $option)
                                                <option value="{{ $option }}" @selected($perPage === $option)>{{ $option }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                </div>
                                <div class="dataTables_info">of {{ number_format($streams->total()) }} items</div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @php
        // Both forms post a field called stream_name — the Add form as an array,
        // Edit as a single value — so old() has to be read through the mode that
        // produced it, or a failed Edit would be fed to the Add form's loop.
        $stmMode = old('form_mode', 'add');
        $stmOldNames = ($stmMode === 'add' && is_array(old('stream_name'))) ? old('stream_name') : [];
    @endphp

    {{-- Add modal (§3c). store() accepts several names at once, so each is one
         repeatable field card. novalidate: an empty extra card must reach the
         submit handler that drops it, not be blocked by native validation. --}}
    <div class="modal fade bm-modal" id="streamAddModal" tabindex="-1"
        aria-labelledby="streamAddModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg">
                <form method="POST" action="{{ route('stream.store') }}" id="streamAddForm" novalidate>
                    @csrf
                    <input type="hidden" name="form_mode" value="add">

                    <div class="bm-modal-header">
                        <h5 class="bm-modal-title" id="streamAddModalLabel">Add Stream</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="bm-modal-body">
                        <div id="streamFieldsContainer">
                            @forelse ($stmOldNames as $index => $oldName)
                                <div class="bm-field-card stream-field-group" data-index="{{ $index }}">
                                    <label class="bm-form-label">Stream Name <span class="bm-req">*</span></label>
                                    <input type="text" name="stream_name[{{ $index }}]"
                                        class="bm-control stream-field @error('stream_name.' . $index) is-invalid @enderror"
                                        value="{{ $oldName }}" placeholder="Enter stream name" required>
                                    @error('stream_name.' . $index)
                                        <span class="bm-field-error">{{ $message }}</span>
                                    @enderror
                                    <div class="bm-field-actions">
                                        <button type="button" class="bm-field-btn bm-field-btn--remove remove-field-btn"
                                            aria-label="Remove this stream">
                                            <i class="bi bi-dash-lg" aria-hidden="true"></i>
                                        </button>
                                        <button type="button" class="bm-field-btn bm-field-btn--add add-field-btn"
                                            aria-label="Add another stream">
                                            <i class="bi bi-plus-lg" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </div>
                            @empty
                                <div class="bm-field-card stream-field-group" data-index="0">
                                    <label class="bm-form-label">Stream Name <span class="bm-req">*</span></label>
                                    <input type="text" name="stream_name[0]" class="bm-control stream-field"
                                        placeholder="Enter stream name" required>
                                    <div class="bm-field-actions">
                                        <button type="button" class="bm-field-btn bm-field-btn--remove remove-field-btn"
                                            aria-label="Remove this stream">
                                            <i class="bi bi-dash-lg" aria-hidden="true"></i>
                                        </button>
                                        <button type="button" class="bm-field-btn bm-field-btn--add add-field-btn"
                                            aria-label="Add another stream">
                                            <i class="bi bi-plus-lg" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="bm-modal-footer">
                        <button type="button" class="btn bm-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn bm-btn-submit">Save Stream</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Edit modal (§3c) — same chrome, one name, and a PUT to update(). --}}
    <div class="modal fade bm-modal" id="streamEditModal" tabindex="-1"
        aria-labelledby="streamEditModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg">
                <form method="POST" action="{{ $stmMode === 'edit' ? old('form_action') : '' }}" id="streamEditForm">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="form_mode" value="edit">
                    <input type="hidden" name="form_action" id="stmFormAction"
                        value="{{ $stmMode === 'edit' ? old('form_action') : '' }}">

                    <div class="bm-modal-header">
                        <h5 class="bm-modal-title" id="streamEditModalLabel">Edit Stream</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="bm-modal-body">
                        <div class="bm-field-card">
                            <label for="stm_stream_name" class="bm-form-label">
                                Stream Name <span class="bm-req">*</span>
                            </label>
                            <input type="text" id="stm_stream_name" name="stream_name"
                                class="bm-control @error('stream_name') is-invalid @enderror"
                                value="{{ $stmMode === 'edit' ? old('stream_name') : '' }}"
                                placeholder="Enter stream name" required>
                            @error('stream_name')
                                <span class="bm-field-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="bm-modal-footer">
                        <button type="button" class="btn bm-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn bm-btn-submit">Update Stream</button>
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

    // ── Edit ──────────────────────────────────────────────────────────────
    var editModal = document.getElementById('streamEditModal');
    var editForm = document.getElementById('streamEditForm');
    var editAction = document.getElementById('stmFormAction');
    var editInput = document.getElementById('stm_stream_name');

    if (editModal && editForm) {
        editModal.addEventListener('show.bs.modal', function (event) {
            var trigger = event.relatedTarget;
            if (!trigger) { return; }

            editForm.setAttribute('action', trigger.dataset.action || '');
            editAction.value = trigger.dataset.action || '';
            editInput.value = trigger.dataset.streamName || '';

            // A failed submit leaves its messages behind; a fresh open must not
            // show the previous attempt's errors.
            editForm.querySelectorAll('.bm-field-error').forEach(function (n) { n.remove(); });
            editForm.querySelectorAll('.is-invalid').forEach(function (n) { n.classList.remove('is-invalid'); });
        });
    }

    // ── Add: repeatable field cards ───────────────────────────────────────
    var addModal = document.getElementById('streamAddModal');
    var addForm = document.getElementById('streamAddForm');
    var container = document.getElementById('streamFieldsContainer');

    // Every card's index, name and button visibility is derived from the DOM
    // after each change — never by nudging the neighbouring card (§3c).
    function syncFieldCards() {
        var groups = container.querySelectorAll('.stream-field-group');
        var last = groups.length - 1;

        groups.forEach(function (group, index) {
            group.setAttribute('data-index', index);
            group.querySelector('.stream-field').setAttribute('name', 'stream_name[' + index + ']');
            group.querySelector('.remove-field-btn').style.display = groups.length > 1 ? '' : 'none';
            group.querySelector('.add-field-btn').style.display = index === last ? '' : 'none';
        });
    }

    if (addForm && container) {
        // The first card is the template; a clone is blanked, and visibility is
        // derived afterwards so it cannot inherit a hidden state.
        var template = container.querySelector('.stream-field-group').cloneNode(true);

        container.addEventListener('click', function (event) {
            if (event.target.closest('.add-field-btn')) {
                var card = template.cloneNode(true);
                card.querySelector('.stream-field').value = '';
                card.querySelectorAll('.bm-field-error').forEach(function (n) { n.remove(); });
                card.querySelector('.stream-field').classList.remove('is-invalid');
                container.appendChild(card);
                syncFieldCards();
                card.querySelector('.stream-field').focus();
                return;
            }

            if (event.target.closest('.remove-field-btn')) {
                if (container.querySelectorAll('.stream-field-group').length > 1) {
                    event.target.closest('.stream-field-group').remove();
                    syncFieldCards();
                }
            }
        });

        addForm.addEventListener('submit', function (event) {
            var fields = Array.prototype.slice.call(container.querySelectorAll('.stream-field'));
            var filled = fields.filter(function (field) { return field.value.trim() !== ''; });

            if (!filled.length) {
                event.preventDefault();
                fields[0].classList.add('is-invalid');
                fields[0].focus();
                return;
            }

            // Blank extra cards are dropped rather than posted as empty rows.
            fields.forEach(function (field) {
                if (field.value.trim() === '') { field.disabled = true; }
            });
        });

        addModal.addEventListener('hidden.bs.modal', function () {
            container.querySelectorAll('.stream-field-group').forEach(function (group, index) {
                if (index === 0) {
                    group.querySelector('.stream-field').value = '';
                    group.querySelector('.stream-field').classList.remove('is-invalid');
                    group.querySelectorAll('.bm-field-error').forEach(function (n) { n.remove(); });
                } else {
                    group.remove();
                }
            });
            syncFieldCards();
        });

        syncFieldCards();
    }

    @if ($errors->any())
        // Validation bounced the save: reopen the dialog it came from, with the
        // values old() has already put back into the fields.
        new bootstrap.Modal(document.getElementById(
            @json($stmMode) === 'edit' ? 'streamEditModal' : 'streamAddModal'
        )).show();
    @endif

    var perPage = document.getElementById('stmPerPage');
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
