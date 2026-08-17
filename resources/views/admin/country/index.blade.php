@extends('admin.layouts.master')

@section('title', 'Country Master')

@push('styles')
{{-- Shared with State, District and City: one module stylesheet for the whole
     address module (docs/new-design-index-page.md §7). --}}
<link rel="stylesheet"
    href="{{ asset('css/address-masters-admin.css') }}?v={{ @filemtime(public_path('css/address-masters-admin.css')) }}">
@endpush

@section('setup_content')
@php
    // Both forms post a field called country_name — Add as an array, Edit as a
    // single value — so old() has to be read through the mode that produced it,
    // or a failed Edit would be fed to the Add form's card loop.
    $cyMode = old('form_mode', 'add');
    $cyOldNames = ($cyMode === 'add' && is_array(old('country_name'))) ? old('country_name') : [];
@endphp
<div class="container-fluid country-master-page">
    <x-breadcrum title="Country Master" :showBack="false">
        {{-- Add and Edit are modals, not pages (§3c). --}}
        <button type="button"
            class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 rounded-1 fw-semibold shadow-sm"
            data-bs-toggle="modal" data-bs-target="#countryAddModal">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Add Country</span>
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
                                    <th scope="col">Country Name</th>
                                    <th scope="col" class="text-nowrap text-center">Status</th>
                                    <th scope="col" class="text-nowrap text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($countries as $key => $country)
                                    @php $cyIsActive = $country->active_inactive == 1; @endphp
                                    <tr>
                                        {{-- firstItem() + $key, not $key + 1: the old numbering
                                             restarted at 1 on every page. --}}
                                        <td>{{ $countries->firstItem() + $key }}</td>
                                        <td>{{ $country->country_name }}</td>

                                        {{-- Status: soft badge, display only (§3b) --}}
                                        <td class="text-center">
                                            <span class="status-pill badge rounded-1 {{ $cyIsActive ? 'bg-success-subtle' : 'bg-danger-subtle' }}"
                                                data-order="{{ (int) $cyIsActive }}">
                                                {{ $cyIsActive ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>

                                        {{-- Action: Edit · the status switch · Delete — visible in the cell as
                                             equal-width icon-over-label stacks (§3b). Edit opens the modal. --}}
                                        <td>
                                            <div class="adr-act-group" role="group" aria-label="Country actions">
                                                <button type="button" class="adr-act adr-act--edit"
                                                    title="Edit" aria-label="Edit country"
                                                    data-bs-toggle="modal" data-bs-target="#countryEditModal"
                                                    data-action="{{ route('master.country.update', $country->pk) }}"
                                                    data-country-name="{{ $country->country_name }}"
                                                    data-status="{{ $cyIsActive ? 1 : 2 }}">
                                                    <span class="adr-act__icon"><i class="bi bi-pencil" aria-hidden="true"></i></span>
                                                    <span class="adr-act__label">Edit</span>
                                                </button>

                                                {{-- No .form-check/.form-switch wrapper (§3b trap 1):
                                                     custom.css pulls a .form-check-input inside one left
                                                     by -2.375rem. custom.js binds .status-toggle globally
                                                     off these data-* attributes. --}}
                                                <label class="adr-act adr-act--toggle"
                                                    title="{{ $cyIsActive ? 'Deactivate' : 'Activate' }}">
                                                    <span class="adr-act__icon">
                                                        <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                            data-table="country_master" data-column="active_inactive"
                                                            data-id="{{ $country->pk }}"
                                                            {{ $cyIsActive ? 'checked' : '' }}
                                                            aria-label="{{ $cyIsActive ? 'Deactivate' : 'Activate' }} country">
                                                    </span>
                                                    <span class="adr-act__label">{{ $cyIsActive ? 'Deactivate' : 'Activate' }}</span>
                                                </label>

                                                @if ($cyIsActive)
                                                    {{-- An active country cannot be deleted — mirror that guard
                                                         rather than offering a control that fails. --}}
                                                    <span class="adr-act adr-act--del is-disabled" aria-disabled="true"
                                                        title="Deactivate this country before deleting">
                                                        <span class="adr-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>
                                                        <span class="adr-act__label">Delete</span>
                                                    </span>
                                                @else
                                                    <form action="{{ route('master.country.delete', $country->pk) }}"
                                                        method="POST" class="adr-act adr-act--del"
                                                        onsubmit="return confirm('Are you sure you want to delete this country?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="adr-act__btn"
                                                            title="Delete" aria-label="Delete country">
                                                            <span class="adr-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>
                                                            <span class="adr-act__label">Delete</span>
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
                                            <span class="fw-medium">No countries found.</span>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- §4 variant B: a Laravel paginator wearing the shared footer chrome. --}}
                    @if ($countries->total() > 0)
                        <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div class="programme-dt-pagination">
                                {{ $countries->links('vendor.pagination.custom') }}
                            </div>
                            <div class="programme-dt-count d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                                <div class="dataTables_length">
                                    <label class="mb-0">Showing
                                        <select id="cyPerPage" class="form-select form-select-sm"
                                            aria-label="Rows per page">
                                            @foreach ($perPageOptions as $option)
                                                <option value="{{ $option }}" @selected($perPage === $option)>{{ $option }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                </div>
                                <div class="dataTables_info">of {{ number_format($countries->total()) }} items</div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Add modal (§3c). countryStore() loops over country_name, so Add takes
         several countries at once — one repeatable field card each. novalidate:
         an empty extra card must reach the submit handler that drops it, not be
         blocked by native validation. --}}
    <div class="modal fade adr-modal" id="countryAddModal" tabindex="-1"
        aria-labelledby="countryAddModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg">
                <form method="POST" action="{{ route('master.country.store') }}" id="countryAddForm" novalidate>
                    @csrf
                    <input type="hidden" name="form_mode" value="add">

                    <div class="adr-modal-header">
                        <h5 class="adr-modal-title" id="countryAddModalLabel">Add Country</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="adr-modal-body">
                        <div id="countryFieldsContainer">
                            @forelse ($cyOldNames as $index => $oldName)
                                <div class="adr-field-card country-field-group" data-index="{{ $index }}">
                                    <div class="adr-field">
                                        <label class="adr-field-label">Country Name <span class="adr-req">*</span></label>
                                        <input type="text" name="country_name[{{ $index }}]"
                                            class="adr-control country-field @error('country_name.' . $index) is-invalid @enderror"
                                            value="{{ $oldName }}" placeholder="Enter country name" required>
                                        @error('country_name.' . $index)
                                            <span class="adr-error">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="adr-field-actions">
                                        <button type="button" class="adr-field-btn adr-field-btn--remove remove-field-btn"
                                            aria-label="Remove this country">
                                            <i class="bi bi-dash-lg" aria-hidden="true"></i>
                                        </button>
                                        <button type="button" class="adr-field-btn adr-field-btn--add add-field-btn"
                                            aria-label="Add another country">
                                            <i class="bi bi-plus-lg" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </div>
                            @empty
                                <div class="adr-field-card country-field-group" data-index="0">
                                    <div class="adr-field">
                                        <label class="adr-field-label">Country Name <span class="adr-req">*</span></label>
                                        <input type="text" name="country_name[0]" class="adr-control country-field"
                                            placeholder="Enter country name" required>
                                    </div>
                                    <div class="adr-field-actions">
                                        <button type="button" class="adr-field-btn adr-field-btn--remove remove-field-btn"
                                            aria-label="Remove this country">
                                            <i class="bi bi-dash-lg" aria-hidden="true"></i>
                                        </button>
                                        <button type="button" class="adr-field-btn adr-field-btn--add add-field-btn"
                                            aria-label="Add another country">
                                            <i class="bi bi-plus-lg" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </div>
                            @endforelse

                            {{-- One status for the whole batch: countryStore() writes the same
                                 active_inactive to every row it creates. --}}
                            <div class="adr-field-card mt-3">
                                <div class="adr-field">
                                    <label for="cy_add_status" class="adr-field-label">
                                        Status <span class="adr-req">*</span>
                                    </label>
                                    <select name="active_inactive" id="cy_add_status"
                                        class="adr-control @error('active_inactive') is-invalid @enderror" required>
                                        <option value="1" @selected($cyMode === 'add' && old('active_inactive', 1) == 1)>Active</option>
                                        <option value="2" @selected($cyMode === 'add' && old('active_inactive') == 2)>Inactive</option>
                                    </select>
                                    @error('active_inactive')
                                        <span class="adr-error">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="adr-modal-footer">
                        <button type="button" class="btn adr-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn adr-btn-submit">Save Country</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Edit modal (§3c) — same chrome, one country, and a PUT to countryUpdate(). --}}
    <div class="modal fade adr-modal" id="countryEditModal" tabindex="-1"
        aria-labelledby="countryEditModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg">
                <form method="POST" action="{{ $cyMode === 'edit' ? old('form_action') : '' }}" id="countryEditForm">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="form_mode" value="edit">
                    <input type="hidden" name="form_action" id="cyFormAction"
                        value="{{ $cyMode === 'edit' ? old('form_action') : '' }}">

                    <div class="adr-modal-header">
                        <h5 class="adr-modal-title" id="countryEditModalLabel">Edit Country</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="adr-modal-body">
                        <div class="adr-field-card">
                            <div class="adr-field">
                                <label for="cy_country_name" class="adr-field-label">
                                    Country Name <span class="adr-req">*</span>
                                </label>
                                <input type="text" id="cy_country_name" name="country_name"
                                    class="adr-control @error('country_name') is-invalid @enderror"
                                    value="{{ $cyMode === 'edit' ? old('country_name') : '' }}"
                                    placeholder="Enter country name" required>
                                @error('country_name')
                                    <span class="adr-error">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="adr-field">
                                <label for="cy_edit_status" class="adr-field-label">
                                    Status <span class="adr-req">*</span>
                                </label>
                                <select name="active_inactive" id="cy_edit_status" class="adr-control" required>
                                    <option value="1">Active</option>
                                    <option value="2">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="adr-modal-footer">
                        <button type="button" class="btn adr-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn adr-btn-submit">Update Country</button>
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

    function clearErrors(form) {
        form.querySelectorAll('.adr-error').forEach(function (n) { n.remove(); });
        form.querySelectorAll('.is-invalid').forEach(function (n) { n.classList.remove('is-invalid'); });
    }

    // ── Edit ──────────────────────────────────────────────────────────────
    var editModal = document.getElementById('countryEditModal');
    var editForm = document.getElementById('countryEditForm');
    var editAction = document.getElementById('cyFormAction');

    editModal.addEventListener('show.bs.modal', function (event) {
        var trigger = event.relatedTarget;
        if (!trigger) { return; }

        clearErrors(editForm);
        editForm.setAttribute('action', trigger.dataset.action || '');
        editAction.value = trigger.dataset.action || '';
        document.getElementById('cy_country_name').value = trigger.dataset.countryName || '';
        // Anything other than 1 is inactive — the column holds 2 from the forms
        // and 0 from the inline toggle.
        document.getElementById('cy_edit_status').value = trigger.dataset.status === '1' ? '1' : '2';
    });

    // ── Add: repeatable field cards ───────────────────────────────────────
    var addModal = document.getElementById('countryAddModal');
    var addForm = document.getElementById('countryAddForm');
    var container = document.getElementById('countryFieldsContainer');

    // Every card's index, name and button visibility is derived from the DOM
    // after each change — never by nudging the neighbouring card (§3c).
    function syncFieldCards() {
        var groups = container.querySelectorAll('.country-field-group');
        var last = groups.length - 1;

        groups.forEach(function (group, index) {
            group.setAttribute('data-index', index);
            group.querySelector('.country-field').setAttribute('name', 'country_name[' + index + ']');
            group.querySelector('.remove-field-btn').style.display = groups.length > 1 ? '' : 'none';
            group.querySelector('.add-field-btn').style.display = index === last ? '' : 'none';
        });
    }

    // The first card is the template; a clone is blanked, and visibility is
    // derived afterwards so it cannot inherit a hidden state.
    var template = container.querySelector('.country-field-group').cloneNode(true);

    container.addEventListener('click', function (event) {
        if (event.target.closest('.add-field-btn')) {
            var card = template.cloneNode(true);
            card.querySelector('.country-field').value = '';
            card.querySelectorAll('.adr-error').forEach(function (n) { n.remove(); });
            card.querySelector('.country-field').classList.remove('is-invalid');
            // After the last card, but before the shared Status card.
            var groups = container.querySelectorAll('.country-field-group');
            groups[groups.length - 1].insertAdjacentElement('afterend', card);
            syncFieldCards();
            card.querySelector('.country-field').focus();
            return;
        }

        if (event.target.closest('.remove-field-btn')) {
            if (container.querySelectorAll('.country-field-group').length > 1) {
                event.target.closest('.country-field-group').remove();
                syncFieldCards();
            }
        }
    });

    addForm.addEventListener('submit', function (event) {
        clearErrors(addForm);

        var fields = Array.prototype.slice.call(container.querySelectorAll('.country-field'));
        var filled = fields.filter(function (field) { return field.value.trim() !== ''; });

        if (!filled.length) {
            event.preventDefault();
            fields[0].classList.add('is-invalid');
            fields[0].insertAdjacentHTML('afterend',
                '<span class="adr-error">Enter at least one country name.</span>');
            fields[0].focus();
            return;
        }

        // Blank extra cards are dropped rather than posted as empty rows.
        fields.forEach(function (field) {
            field.disabled = field.value.trim() === '';
        });
    });

    addModal.addEventListener('hidden.bs.modal', function () {
        container.querySelectorAll('.country-field-group').forEach(function (group, index) {
            if (index === 0) {
                var field = group.querySelector('.country-field');
                field.value = '';
                field.disabled = false;
                field.classList.remove('is-invalid');
                group.querySelectorAll('.adr-error').forEach(function (n) { n.remove(); });
            } else {
                group.remove();
            }
        });
        syncFieldCards();
    });

    syncFieldCards();

    @if ($errors->any())
        // Validation bounced the save: reopen the dialog it came from, with the
        // values old() has already put back into the fields.
        @if ($cyMode === 'edit')
            editForm.setAttribute('action', editAction.value);
            document.getElementById('cy_edit_status').value =
                @json((string) old('active_inactive', 1)) === '1' ? '1' : '2';
            new bootstrap.Modal(editModal).show();
        @else
            new bootstrap.Modal(addModal).show();
        @endif
    @endif

    var perPage = document.getElementById('cyPerPage');
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
