@extends('admin.layouts.master')

@section('title', 'Venue Master')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/venue-master-admin.css') }}?v={{ @filemtime(public_path('css/venue-master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Venue Master" />
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" >
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>Venue Master</h4>
                        </div>
                        <div class="col-6">
                            <div class="d-flex justify-content-end align-items-end mb-3">
                                <div class="d-flex align-items-center gap-2">

    <x-session_message />

    <div class="card vm-dt-card shadow-sm rounded-3 overflow-hidden border-0">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center justify-content-end gap-3 mb-4">
                <div id="vmDtSearch" class="programme-dt-search ms-lg-auto">
                    <div class="dataTables_filter">
                        <label>
                            <input type="search"
                                id="vmListSearch"
                                class="form-control"
                                placeholder="Search"
                                aria-label="Search venues">
                        </label>
                    </div>
                </div>
            </div>

            <div class="programme-dt-panel vm-dt-panel">
                <div class="table-responsive vm-dt-scroll">
                    <table id="venue-master-table"
                        class="table table-hover align-middle mb-0 w-100 programme-dt-table border-0">
                        <thead>
                            <tr>
                                <th scope="col">S. No.</th>
                                <th scope="col">Venue Name</th>
                                <th scope="col">Short Name</th>
                                <th scope="col">Description</th>
                                <th scope="col">Status</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($venues as $key => $venue)
                            <tr class="{{ $loop->odd ? 'odd' : 'even' }}"
                                data-vm-name="{{ $venue->venue_name ?? '' }}"
                                data-vm-short="{{ $venue->venue_short_name ?? '' }}"
                                data-vm-desc="{{ $venue->description ?? '' }}">
                                <td>{{ $venues->firstItem() + $key }}</td>
                                <td>{{ $venue->venue_name }}</td>
                                <td>{{ $venue->venue_short_name }}</td>
                                <td class="vm-desc-cell" title="{{ $venue->description }}">
                                    {{ $venue->description ?: '—' }}
                                </td>
                                <td>
                                    <div class="form-check form-switch d-inline-block vm-status-toggle-source">
                                        <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                            data-table="venue_master" data-column="active_inactive"
                                            data-id="{{ $venue->venue_id }}" data-id_column="venue_id"
                                            {{ $venue->active_inactive == 1 ? 'checked' : '' }}>
                                    </div>
                                </td>
                                <td>
                                    <div class="vm-venue-actions-source d-inline-flex align-items-center gap-2" role="group"
                                        aria-label="Venue actions">
                                        <a href="{{ route('Venue-Master.edit', $venue->venue_id) }}"
                                            class="vm-edit-link"
                                            aria-label="Edit {{ $venue->venue_name }}">
                                            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">edit</i>
                                        </a>

                                        @if($venue->active_inactive == 1)
                                        <button type="button"
                                            class="vm-delete-btn"
                                            disabled
                                            aria-disabled="true"
                                            title="Cannot delete active venue">
                                            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">delete</i>
                                        </button>
                                        @else
                                        <form action="{{ route('Venue-Master.destroy', $venue->venue_id) }}"
                                            method="POST"
                                            class="d-inline delete-form"
                                            onsubmit="return confirm('Are you sure you want to delete this venue?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="vm-delete-btn"
                                                aria-label="Delete {{ $venue->venue_name }}"
                                                title="Delete venue">
                                                <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">delete</i>
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr class="vm-empty-row">
                                <td colspan="6" class="vm-empty-state text-center">
                                    <i class="bi bi-building display-4 text-secondary opacity-50 d-block mb-3" aria-hidden="true"></i>
                                    <h5 class="fw-semibold text-dark mb-1">No Venues Found</h5>
                                    <p class="text-secondary mb-0">Add a venue to get started.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($venues->hasPages() || $venues->total() > 0)
                <div class="programme-dt-footer vm-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3 pt-3">
                    <nav aria-label="Venue pagination" class="vm-pagination">
                        {{ $venues->withQueryString()->links('vendor.pagination.custom') }}
                    </nav>
                    <div class="vm-dt-info">
                        @if($venues->total() > 0)
                            Showing {{ $venues->firstItem() }}–{{ $venues->lastItem() }} of {{ $venues->total() }} items
                        @else
                            Showing 0 of 0 items
                        @endif
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="venueMasterModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-md modal-dialog-centered vm-venue-modal-dialog">
        <div class="modal-content cgt-form-modal border-0 shadow-lg">
            <div class="modal-header">
                <h5 class="modal-title mb-0 fw-bold">Add Venue</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center py-4 placeholder-loading">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
window.statusToggleUrl = "{{ route('admin.toggleStatus') }}";

(function () {
    var tableSelector = '#venue-master-table';
    var modalEl = document.getElementById('venueMasterModal');
    var modalBody = modalEl ? modalEl.querySelector('.modal-body') : null;
    var modalTitle = modalEl ? modalEl.querySelector('.modal-title') : null;
    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    if (modalEl && modalEl.parentElement && modalEl.parentElement !== document.body) {
        document.body.appendChild(modalEl);
    }

    function loadVenueForm(url, title) {
        if (!modalEl || !modalBody || !modalTitle) {
            return;
        }

        modalTitle.textContent = title || 'Add Venue';
        modalBody.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (response) { return response.text(); })
            .then(function (html) { modalBody.innerHTML = html; })
            .catch(function () {
                modalBody.innerHTML = '<div class="alert alert-danger mb-0 rounded-3">Failed to load form.</div>';
            });

        if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
    }

    function clearVenueFormErrors(form) {
        if (!form) {
            return;
        }
        form.querySelectorAll('.is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });
        form.querySelectorAll('.vm-field-error').forEach(function (el) {
            el.textContent = '';
            el.classList.add('d-none');
        });
    }

    function showVenueFormErrors(form, errors) {
        if (!form || !errors) {
            return;
        }
        Object.entries(errors).forEach(function (entry) {
            var field = entry[0];
            var messages = entry[1];
            var input = form.querySelector('[name="' + field + '"]');
            var errorEl = form.querySelector('.vm-field-error[data-field="' + field + '"]');
            if (input) {
                input.classList.add('is-invalid');
            }
            if (errorEl && messages && messages[0]) {
                errorEl.textContent = messages[0];
                errorEl.classList.remove('d-none');
            }
        });
    }

    function bindVenueModalHandlers() {
        document.addEventListener('click', function (e) {
            var createBtn = e.target.closest('#openCreateVenue');
            if (createBtn) {
                e.preventDefault();
                loadVenueForm(createBtn.getAttribute('href'), 'Add Venue');
                return;
            }

            var editLink = e.target.closest('.vm-edit-link');
            if (editLink) {
                e.preventDefault();
                loadVenueForm(editLink.getAttribute('href'), 'Edit Venue');
            }
        });

        document.addEventListener('submit', function (e) {
            var form = e.target.closest('#venueMasterForm');
            if (!form) {
                return;
            }

            e.preventDefault();
            clearVenueFormErrors(form);

            var submitBtn = form.querySelector('button[type="submit"]');
            var originalSubmitText = submitBtn ? submitBtn.textContent : '';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Saving...';
            }

            fetch(form.getAttribute('action'), {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                }
            })
                .then(function (res) {
                    if (res.ok) {
                        if (window.bootstrap && bootstrap.Modal && modalEl) {
                            var modalInstance = bootstrap.Modal.getInstance(modalEl);
                            if (modalInstance) {
                                modalInstance.hide();
                            }
                        }
                        window.location.reload();
                        return null;
                    }

                    if (res.status === 422) {
                        return res.json().then(function (data) {
                            showVenueFormErrors(form, data?.errors || {});
                        });
                    }

                    throw new Error('Save failed. Please try again.');
                })
                .catch(function (err) {
                    alert(err.message || 'Save failed. Please try again.');
                })
                .finally(function () {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalSubmitText;
                    }
                });
        });

        @if (request('open_venue_modal') === 'add')
            loadVenueForm("{{ route('Venue-Master.create') }}", 'Add Venue');
        @elseif (request('open_venue_modal') === 'edit' && request('venue_id'))
            loadVenueForm("{{ route('Venue-Master.edit', request('venue_id')) }}", 'Edit Venue');
        @endif
    }

    function styleVmEditLink($link) {
        $link.removeClass('btn btn-sm btn-outline-primary d-flex align-items-center gap-1');
        $link.addClass('vm-action-btn vm-action-edit');
        $link.empty().append('<i class="bi bi-pencil" aria-hidden="true"></i>');
    }

    function styleVmDeleteBtn($btn) {
        $btn.removeClass('btn btn-sm btn-outline-danger btn-outline-secondary d-flex align-items-center gap-1');
        $btn.addClass('vm-action-btn vm-action-delete');
        $btn.empty().append('<i class="bi bi-trash" aria-hidden="true"></i>');
    }

    function updateVmStatusBadge($toggle, isActive) {
        var $badge = $toggle.closest('tr').find('.vm-status-badge');
        if (!$badge.length) {
            return;
        }
        $badge
            .removeClass('programme-status-badge--active programme-status-badge--inactive')
            .addClass(isActive ? 'programme-status-badge--active' : 'programme-status-badge--inactive')
            .text(isActive ? 'Active' : 'Inactive');
    }

    function decorateVmRows() {
        jQuery(tableSelector + ' tbody tr').not('.vm-empty-row').each(function () {
            var $row = jQuery(this);

            if ($row.hasClass('vm-row-decorated')) {
                return;
            }

            var $cells = $row.find('td');
            if ($cells.length < 6) {
                return;
            }

            var $statusCell = $cells.eq(4);
            var $actionCell = $cells.eq(5);
            var $toggleWrap = $statusCell.find('.vm-status-toggle-source').first();
            var $toggle = $toggleWrap.find('.status-toggle').first();
            var $sourceActions = $actionCell.find('.vm-venue-actions-source').first();

            if ($toggle.length) {
                var isActive = $toggle.is(':checked');
                var badgeClass = isActive ? 'programme-status-badge--active' : 'programme-status-badge--inactive';
                var label = isActive ? 'Active' : 'Inactive';

                $toggleWrap.detach();
                $statusCell.empty().append(
                    jQuery('<span>', {
                        class: 'badge rounded-pill programme-status-badge vm-status-badge ' + badgeClass,
                        text: label
                    })
                );

                var $group = jQuery('<div>', {
                    class: 'vm-venue-actions',
                    role: 'group',
                    'aria-label': 'Venue actions'
                });

                var $editLink = $sourceActions.find('.vm-edit-link').first();
                if ($editLink.length) {
                    styleVmEditLink($editLink);
                    $group.append($editLink);
                }

                $toggleWrap.addClass('vm-action-switch-wrap mb-0');
                $group.append($toggleWrap);

                var $deleteBtn = $sourceActions.find('.vm-delete-btn').first();
                if ($deleteBtn.length) {
                    styleVmDeleteBtn($deleteBtn);
                    var $form = $deleteBtn.closest('.delete-form');
                    $group.append($form.length ? $form : $deleteBtn);
                }

                $actionCell.empty().append($group);
            }

            $row.addClass('vm-row-decorated');
        });
    }

    function bindVmListSearch() {
        jQuery('#vmListSearch').on('input', function () {
            var query = jQuery(this).val().toLowerCase().trim();
            jQuery(tableSelector + ' tbody tr').not('.vm-empty-row').each(function () {
                var $row = jQuery(this);
                var text = (
                    ($row.data('vm-name') || '') + ' ' +
                    ($row.data('vm-short') || '') + ' ' +
                    ($row.data('vm-desc') || '')
                ).toLowerCase();
                $row.toggle(!query || text.indexOf(query) !== -1);
            });
        });
    }

    function initVmPage() {
        bindVenueModalHandlers();

        if (typeof jQuery === 'undefined') {
            return;
        }

        decorateVmRows();
        bindVmListSearch();

        jQuery(document).on('change', tableSelector + ' .status-toggle', function () {
            var $toggle = jQuery(this);
            window.setTimeout(function () {
                updateVmStatusBadge($toggle, $toggle.is(':checked'));
            }, 0);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initVmPage);
    } else {
        initVmPage();
    }
})();
</script>
@endpush
