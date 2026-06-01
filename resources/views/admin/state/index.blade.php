@extends('admin.layouts.master')

@section('title', 'State - Sargam | Lal Bahadur')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/state-master-admin.css') }}?v={{ @filemtime(public_path('css/state-master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid stt-master-page py-4">
    <x-breadcrum title="State List">
        <a href="{{ route('master.state.create') }}" id="openCreateState"
            class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 rounded-2 fw-semibold text-nowrap shadow-sm">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Add State</span>
        </a>
    </x-breadcrum>

    <x-session_message />

    <div class="card stt-dt-card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center justify-content-end gap-3 mb-4">
                <div id="sttDtSearch" class="programme-dt-search ms-lg-auto">
                    <div class="dataTables_filter">
                        <label>
                            <input type="search"
                                id="sttListSearch"
                                class="form-control"
                                placeholder="Search"
                                aria-label="Search states">
                        </label>
                    </div>
                </div>
            </div>

            <div class="programme-dt-panel stt-dt-panel">
                <div class="table-responsive stt-dt-scroll">
                    <table id="state-master-table"
                        class="table table-hover align-middle mb-0 w-100 programme-dt-table border-0">
                        <thead>
                            <tr>
                                <th scope="col">S. No.</th>
                                <th scope="col">State Name</th>
                                <th scope="col">Status</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($states as $key => $state)
                            <tr class="{{ $loop->odd ? 'odd' : 'even' }}"
                                data-stt-name="{{ $state->state_name ?? '' }}">
                                <td>{{ $states->firstItem() + $key }}</td>
                                <td>{{ $state->state_name }}</td>
                                <td>
                                    <div class="form-check form-switch d-inline-block stt-status-toggle-source">
                                        <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                            data-table="state_master" data-column="active_inactive"
                                            data-id="{{ $state->pk }}"
                                            {{ $state->active_inactive == 1 ? 'checked' : '' }}>
                                    </div>
                                </td>
                                <td>
                                    <div class="stt-state-actions-source d-inline-flex align-items-center gap-2" role="group"
                                        aria-label="State actions">
                                        <a href="{{ route('master.state.edit', $state->pk) }}"
                                            class="stt-edit-link"
                                            aria-label="Edit state">
                                            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">edit</i>
                                        </a>

                                        @if($state->active_inactive == 1)
                                        <button type="button"
                                            class="stt-delete-btn"
                                            disabled
                                            aria-disabled="true"
                                            title="Cannot delete active state">
                                            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">delete</i>
                                        </button>
                                        @else
                                        <form action="{{ route('master.state.delete', $state->pk) }}" method="POST"
                                            class="d-inline stt-delete-form"
                                            onsubmit="return confirm('Are you sure you want to delete this?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="stt-delete-btn"
                                                aria-label="Delete state"
                                                title="Delete state">
                                                <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">delete</i>
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr class="stt-empty-row">
                                <td colspan="4" class="stt-empty-state text-center">
                                    <i class="bi bi-map display-4 text-secondary opacity-50 d-block mb-3" aria-hidden="true"></i>
                                    <h5 class="fw-semibold text-dark mb-1">No States Found</h5>
                                    <p class="text-secondary mb-0">Add a state to get started.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($states->hasPages() || $states->total() > 0)
                <div class="programme-dt-footer stt-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3 pt-3">
                    <nav aria-label="State pagination" class="stt-pagination">
                        {{ $states->withQueryString()->links('vendor.pagination.custom') }}
                    </nav>
                    <div class="stt-dt-info">
                        @if($states->total() > 0)
                            Showing {{ $states->firstItem() }}–{{ $states->lastItem() }} of {{ $states->total() }} items
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

<div class="modal fade" id="stateModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-md modal-dialog-centered stt-state-modal-dialog">
        <div class="modal-content cgt-form-modal border-0 shadow-lg rounded-4">
            <div class="modal-header">
                <h5 class="modal-title mb-0 fw-bold">Add State</h5>
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
(function () {
    var tableSelector = '#state-master-table';
    var modalEl = document.getElementById('stateModal');
    var modalBody = modalEl ? modalEl.querySelector('.modal-body') : null;
    var modalTitle = modalEl ? modalEl.querySelector('.modal-title') : null;
    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    if (modalEl && modalEl.parentElement && modalEl.parentElement !== document.body) {
        document.body.appendChild(modalEl);
    }

    function loadStateForm(url, title) {
        if (!modalEl || !modalBody || !modalTitle) {
            return;
        }

        modalTitle.textContent = title || 'Add State';
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

    function clearSttFormErrors(form) {
        if (!form) {
            return;
        }
        form.querySelectorAll('.is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });
        form.querySelectorAll('.stt-field-error').forEach(function (el) {
            el.textContent = '';
            el.classList.add('d-none');
        });
    }

    function showSttFormErrors(form, errors) {
        if (!form || !errors) {
            return;
        }
        Object.entries(errors).forEach(function (entry) {
            var field = entry[0];
            var messages = entry[1];
            var input = form.querySelector('[name="' + field + '"]');
            var errorEl = form.querySelector('.stt-field-error[data-field="' + field + '"]');
            if (input) {
                input.classList.add('is-invalid');
            }
            if (errorEl && messages && messages[0]) {
                errorEl.textContent = messages[0];
                errorEl.classList.remove('d-none');
            }
        });
    }

    function bindStateModalHandlers() {
        document.addEventListener('click', function (e) {
            var createBtn = e.target.closest('#openCreateState');
            if (createBtn) {
                e.preventDefault();
                loadStateForm(createBtn.getAttribute('href'), 'Add State');
                return;
            }

            var editLink = e.target.closest('.stt-edit-link');
            if (editLink) {
                e.preventDefault();
                loadStateForm(editLink.getAttribute('href'), 'Edit State');
            }
        });

        document.addEventListener('submit', function (e) {
            var form = e.target.closest('#stateForm');
            if (!form) {
                return;
            }

            e.preventDefault();
            clearSttFormErrors(form);

            var submitBtn = form.querySelector('#saveStateForm');
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
                            showSttFormErrors(form, data?.errors || {});
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

        @if (request('open_stt_modal') === 'add')
            loadStateForm("{{ route('master.state.create') }}", 'Add State');
        @elseif (request('open_stt_modal') === 'edit' && request('stt_id'))
            loadStateForm("{{ route('master.state.edit', request('stt_id')) }}", 'Edit State');
        @endif
    }

    function styleSttEditLink($link) {
        $link.removeClass('btn btn-sm btn-outline-primary d-flex align-items-center gap-1');
        $link.addClass('stt-action-btn stt-action-edit');
        $link.empty().append('<i class="bi bi-pencil" aria-hidden="true"></i>');
    }

    function styleSttDeleteBtn($btn) {
        $btn.removeClass('btn btn-sm btn-outline-danger btn-outline-secondary d-flex align-items-center gap-1');
        $btn.addClass('stt-action-btn stt-action-delete');
        $btn.empty().append('<i class="bi bi-trash" aria-hidden="true"></i>');
    }

    function updateSttStatusBadge($toggle, isActive) {
        var $badge = $toggle.closest('tr').find('.stt-status-badge');
        if (!$badge.length) {
            return;
        }
        $badge
            .removeClass('programme-status-badge--active programme-status-badge--inactive')
            .addClass(isActive ? 'programme-status-badge--active' : 'programme-status-badge--inactive')
            .text(isActive ? 'Active' : 'Inactive');
    }

    function decorateSttRows() {
        jQuery(tableSelector + ' tbody tr').not('.stt-empty-row').each(function () {
            var $row = jQuery(this);

            if ($row.hasClass('stt-row-decorated')) {
                return;
            }

            var $cells = $row.find('td');
            if ($cells.length < 4) {
                return;
            }

            var $statusCell = $cells.eq(2);
            var $actionCell = $cells.eq(3);
            var $toggleWrap = $statusCell.find('.stt-status-toggle-source').first();
            var $toggle = $toggleWrap.find('.status-toggle').first();
            var $sourceActions = $actionCell.find('.stt-state-actions-source').first();

            if ($toggle.length) {
                var isActive = $toggle.is(':checked');
                var badgeClass = isActive ? 'programme-status-badge--active' : 'programme-status-badge--inactive';
                var label = isActive ? 'Active' : 'Inactive';

                $toggleWrap.detach();
                $statusCell.empty().append(
                    jQuery('<span>', {
                        class: 'badge rounded-pill programme-status-badge stt-status-badge ' + badgeClass,
                        text: label
                    })
                );

                var $group = jQuery('<div>', {
                    class: 'stt-state-actions',
                    role: 'group',
                    'aria-label': 'State actions'
                });

                var $editLink = $sourceActions.find('.stt-edit-link').first();
                if ($editLink.length) {
                    styleSttEditLink($editLink);
                    $group.append($editLink);
                }

                $toggleWrap.addClass('stt-action-switch-wrap mb-0');
                $group.append($toggleWrap);

                var $deleteBtn = $sourceActions.find('.stt-delete-btn').first();
                if ($deleteBtn.length) {
                    styleSttDeleteBtn($deleteBtn);
                    var $form = $deleteBtn.closest('.stt-delete-form');
                    $group.append($form.length ? $form : $deleteBtn);
                }

                $actionCell.empty().append($group);
            }

            $row.addClass('stt-row-decorated');
        });
    }

    function bindSttListSearch() {
        jQuery('#sttListSearch').on('input', function () {
            var query = jQuery(this).val().toLowerCase().trim();
            jQuery(tableSelector + ' tbody tr').not('.stt-empty-row').each(function () {
                var $row = jQuery(this);
                var text = ($row.data('stt-name') || '').toLowerCase();
                $row.toggle(!query || text.indexOf(query) !== -1);
            });
        });
    }

    function initSttPage() {
        bindStateModalHandlers();

        if (typeof jQuery === 'undefined') {
            return;
        }

        decorateSttRows();
        bindSttListSearch();

        jQuery(document).on('change', tableSelector + ' .status-toggle', function () {
            var $toggle = jQuery(this);
            window.setTimeout(function () {
                updateSttStatusBadge($toggle, $toggle.is(':checked'));
            }, 0);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSttPage);
    } else {
        initSttPage();
    }
})();
</script>
@endpush
