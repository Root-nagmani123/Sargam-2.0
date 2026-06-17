@extends('admin.layouts.master')

@section('title', 'Class Session Master')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/class-session-master-admin.css') }}?v={{ @filemtime(public_path('css/class-session-master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Class Session Master" />
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" >
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>Class Session Master</h4>
                        </div>
                        <div class="col-6">
                            <div class="d-flex justify-content-end align-items-center gap-2">

    <x-session_message />

    <div class="card csm-dt-card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center justify-content-end gap-3 mb-4">
                <div id="csmDtSearch" class="programme-dt-search ms-lg-auto">
                    <div class="dataTables_filter">
                        <label>
                            <input type="search"
                                id="csmListSearch"
                                class="form-control"
                                placeholder="Search"
                                aria-label="Search class sessions">
                        </label>
                    </div>
                </div>
            </div>

            <div class="programme-dt-panel csm-dt-panel">
                <div class="table-responsive csm-dt-scroll">
                    <table id="class-session-master-table"
                        class="table table-hover align-middle mb-0 w-100 programme-dt-table border-0">
                        <thead>
                            <tr>
                                <th scope="col">S. No.</th>
                                <th scope="col">Shift Name</th>
                                <th scope="col">Start Time</th>
                                <th scope="col">End Time</th>
                                <th scope="col">Status</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($classSessionMaster as $index => $classSession)
                            <tr class="{{ $loop->odd ? 'odd' : 'even' }}"
                                data-csm-shift="{{ $classSession->shift_name ?? '' }}"
                                data-csm-start="{{ $classSession->start_time ?? '' }}"
                                data-csm-end="{{ $classSession->end_time ?? '' }}">
                                <td>{{ $classSessionMaster->firstItem() + $index }}</td>
                                <td>{{ $classSession->shift_name ?? 'N/A' }}</td>
                                <td>{{ $classSession->start_time ?? 'N/A' }}</td>
                                <td>{{ $classSession->end_time ?? 'N/A' }}</td>
                                <td>
                                    <div class="form-check form-switch d-inline-block csm-status-toggle-source">
                                        <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                            data-table="class_session_master" data-column="active_inactive"
                                            data-id="{{ $classSession->pk }}"
                                            {{ $classSession->active_inactive == 1 ? 'checked' : '' }}>
                                    </div>
                                </td>
                                <td>
                                    <div class="csm-session-actions-source d-inline-flex align-items-center gap-2" role="group"
                                        aria-label="Class session actions">
                                        <a href="{{ route('master.class.session.edit', ['id' => encrypt($classSession->pk)]) }}"
                                            class="csm-edit-link"
                                            aria-label="Edit class session">
                                            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">edit</i>
                                        </a>

                                        @if($classSession->active_inactive == 1)
                                        <button type="button"
                                            class="csm-delete-btn"
                                            disabled
                                            aria-disabled="true"
                                            title="Cannot delete active class session">
                                            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">delete</i>
                                        </button>
                                        @else
                                        <form
                                            action="{{ route('master.class.session.delete', ['id' => encrypt($classSession->pk)]) }}"
                                            method="POST"
                                            class="d-inline csm-delete-form"
                                            onsubmit="return confirm('Are you sure you want to delete this record?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="csm-delete-btn"
                                                aria-label="Delete class session"
                                                title="Delete class session">
                                                <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">delete</i>
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr class="csm-empty-row">
                                <td colspan="6" class="csm-empty-state text-center">
                                    <i class="bi bi-clock-history display-4 text-secondary opacity-50 d-block mb-3" aria-hidden="true"></i>
                                    <h5 class="fw-semibold text-dark mb-1">No Class Sessions Found</h5>
                                    <p class="text-secondary mb-0">Add a class session to get started.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($classSessionMaster->hasPages() || $classSessionMaster->total() > 0)
                <div class="programme-dt-footer csm-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3 pt-3">
                    <nav aria-label="Class session pagination" class="csm-pagination">
                        {{ $classSessionMaster->withQueryString()->links('vendor.pagination.custom') }}
                    </nav>
                    <div class="csm-dt-info">
                        @if($classSessionMaster->total() > 0)
                            Showing {{ $classSessionMaster->firstItem() }}–{{ $classSessionMaster->lastItem() }} of {{ $classSessionMaster->total() }} items
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

<div class="modal fade" id="classSessionModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-md modal-dialog-centered csm-session-modal-dialog">
        <div class="modal-content cgt-form-modal border-0 shadow-lg rounded-4">
            <div class="modal-header">
                <h5 class="modal-title mb-0 fw-bold">Add Session</h5>
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
    var tableSelector = '#class-session-master-table';
    var modalEl = document.getElementById('classSessionModal');
    var modalBody = modalEl ? modalEl.querySelector('.modal-body') : null;
    var modalTitle = modalEl ? modalEl.querySelector('.modal-title') : null;
    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    if (modalEl && modalEl.parentElement && modalEl.parentElement !== document.body) {
        document.body.appendChild(modalEl);
    }

    function loadClassSessionForm(url, title) {
        if (!modalEl || !modalBody || !modalTitle) {
            return;
        }

        modalTitle.textContent = title || 'Add Session';
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

    function clearCsmFormErrors(form) {
        if (!form) {
            return;
        }
        form.querySelectorAll('.is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });
        form.querySelectorAll('.csm-field-error').forEach(function (el) {
            el.textContent = '';
            el.classList.add('d-none');
        });
    }

    function showCsmFormErrors(form, errors) {
        if (!form || !errors) {
            return;
        }
        Object.entries(errors).forEach(function (entry) {
            var field = entry[0];
            var messages = entry[1];
            var input = form.querySelector('[name="' + field + '"]');
            var errorEl = form.querySelector('.csm-field-error[data-field="' + field + '"]');
            if (input) {
                input.classList.add('is-invalid');
            }
            if (errorEl && messages && messages[0]) {
                errorEl.textContent = messages[0];
                errorEl.classList.remove('d-none');
            }
        });
    }

    function bindClassSessionModalHandlers() {
        document.addEventListener('click', function (e) {
            var createBtn = e.target.closest('#openCreateClassSession');
            if (createBtn) {
                e.preventDefault();
                loadClassSessionForm(createBtn.getAttribute('href'), 'Add Session');
                return;
            }

            var editLink = e.target.closest('.csm-edit-link');
            if (editLink) {
                e.preventDefault();
                loadClassSessionForm(editLink.getAttribute('href'), 'Edit Session');
            }
        });

        document.addEventListener('submit', function (e) {
            var form = e.target.closest('#classSessionForm');
            if (!form) {
                return;
            }

            e.preventDefault();
            clearCsmFormErrors(form);

            var submitBtn = form.querySelector('#saveClassSessionForm');
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
                            showCsmFormErrors(form, data?.errors || {});
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

        @if (request('open_csm_modal') === 'add')
            loadClassSessionForm("{{ route('master.class.session.create') }}", 'Add Session');
        @elseif (request('open_csm_modal') === 'edit' && request('csm_id'))
            loadClassSessionForm("{{ route('master.class.session.edit', ['id' => request('csm_id')]) }}", 'Edit Session');
        @endif
    }

    function styleCsmEditLink($link) {
        $link.removeClass('btn btn-sm btn-outline-primary d-flex align-items-center gap-1');
        $link.addClass('csm-action-btn csm-action-edit');
        $link.empty().append('<i class="bi bi-pencil" aria-hidden="true"></i>');
    }

    function styleCsmDeleteBtn($btn) {
        $btn.removeClass('btn btn-sm btn-outline-danger btn-outline-secondary d-flex align-items-center gap-1');
        $btn.addClass('csm-action-btn csm-action-delete');
        $btn.empty().append('<i class="bi bi-trash" aria-hidden="true"></i>');
    }

    function updateCsmStatusBadge($toggle, isActive) {
        var $badge = $toggle.closest('tr').find('.csm-status-badge');
        if (!$badge.length) {
            return;
        }
        $badge
            .removeClass('programme-status-badge--active programme-status-badge--inactive')
            .addClass(isActive ? 'programme-status-badge--active' : 'programme-status-badge--inactive')
            .text(isActive ? 'Active' : 'Inactive');
    }

    function decorateCsmRows() {
        jQuery(tableSelector + ' tbody tr').not('.csm-empty-row').each(function () {
            var $row = jQuery(this);

            if ($row.hasClass('csm-row-decorated')) {
                return;
            }

            var $cells = $row.find('td');
            if ($cells.length < 6) {
                return;
            }

            var $statusCell = $cells.eq(4);
            var $actionCell = $cells.eq(5);
            var $toggleWrap = $statusCell.find('.csm-status-toggle-source').first();
            var $toggle = $toggleWrap.find('.status-toggle').first();
            var $sourceActions = $actionCell.find('.csm-session-actions-source').first();

            if ($toggle.length) {
                var isActive = $toggle.is(':checked');
                var badgeClass = isActive ? 'programme-status-badge--active' : 'programme-status-badge--inactive';
                var label = isActive ? 'Active' : 'Inactive';

                $toggleWrap.detach();
                $statusCell.empty().append(
                    jQuery('<span>', {
                        class: 'badge rounded-pill programme-status-badge csm-status-badge ' + badgeClass,
                        text: label
                    })
                );

                var $group = jQuery('<div>', {
                    class: 'csm-session-actions',
                    role: 'group',
                    'aria-label': 'Class session actions'
                });

                var $editLink = $sourceActions.find('.csm-edit-link').first();
                if ($editLink.length) {
                    styleCsmEditLink($editLink);
                    $group.append($editLink);
                }

                $toggleWrap.addClass('csm-action-switch-wrap mb-0');
                $group.append($toggleWrap);

                var $deleteBtn = $sourceActions.find('.csm-delete-btn').first();
                if ($deleteBtn.length) {
                    styleCsmDeleteBtn($deleteBtn);
                    var $form = $deleteBtn.closest('.csm-delete-form');
                    $group.append($form.length ? $form : $deleteBtn);
                }

                $actionCell.empty().append($group);
            }

            $row.addClass('csm-row-decorated');
        });
    }

    function bindCsmListSearch() {
        jQuery('#csmListSearch').on('input', function () {
            var query = jQuery(this).val().toLowerCase().trim();
            jQuery(tableSelector + ' tbody tr').not('.csm-empty-row').each(function () {
                var $row = jQuery(this);
                var text = (
                    ($row.data('csm-shift') || '') + ' ' +
                    ($row.data('csm-start') || '') + ' ' +
                    ($row.data('csm-end') || '')
                ).toLowerCase();
                $row.toggle(!query || text.indexOf(query) !== -1);
            });
        });
    }

    function initCsmPage() {
        bindClassSessionModalHandlers();

        if (typeof jQuery === 'undefined') {
            return;
        }

        decorateCsmRows();
        bindCsmListSearch();

        jQuery(document).on('change', tableSelector + ' .status-toggle', function () {
            var $toggle = jQuery(this);
            window.setTimeout(function () {
                updateCsmStatusBadge($toggle, $toggle.is(':checked'));
            }, 0);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCsmPage);
    } else {
        initCsmPage();
    }
})();
</script>
@endpush
