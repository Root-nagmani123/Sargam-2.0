@extends('admin.layouts.master')

@section('title', 'Stream')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/stream-master-admin.css') }}?v={{ @filemtime(public_path('css/stream-master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid stm-master-page py-4">
    <x-breadcrum title="Stream">
        <a href="{{ route('stream.create') }}" id="openCreateStream"
            class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 rounded-2 fw-semibold text-nowrap shadow-sm">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Add Stream</span>
        </a>
    </x-breadcrum>

    <x-session_message />

    <div class="card stm-dt-card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center justify-content-end gap-3 mb-4">
                <div id="stmDtSearch" class="programme-dt-search ms-lg-auto">
                    <div class="dataTables_filter">
                        <label>
                            <input type="search"
                                id="stmListSearch"
                                class="form-control"
                                placeholder="Search"
                                aria-label="Search streams">
                        </label>
                    </div>
                </div>
            </div>

            <div class="programme-dt-panel stm-dt-panel">
                <div class="table-responsive stm-dt-scroll">
                    <table id="stream-master-table"
                        class="table table-hover align-middle mb-0 w-100 programme-dt-table border-0">
                        <thead>
                            <tr>
                                <th scope="col">S. No.</th>
                                <th scope="col">Stream Name</th>
                                <th scope="col">Status</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($streams as $index => $stream)
                            <tr class="{{ $loop->odd ? 'odd' : 'even' }}"
                                data-stm-name="{{ $stream->stream_name ?? '' }}">
                                <td>{{ $streams->firstItem() + $index }}</td>
                                <td>{{ $stream->stream_name }}</td>
                                <td>
                                    <div class="form-check form-switch d-inline-block stm-status-toggle-source">
                                        <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                            data-table="stream_master" data-column="status"
                                            data-id="{{ $stream->pk }}" {{ $stream->status == 1 ? 'checked' : '' }}>
                                    </div>
                                </td>
                                <td>
                                    <div class="stm-stream-actions-source d-inline-flex align-items-center gap-2" role="group"
                                        aria-label="Stream actions">
                                        <a href="{{ route('stream.edit', $stream->pk) }}"
                                            class="stm-edit-link"
                                            aria-label="Edit stream">
                                            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">edit</i>
                                        </a>

                                        @if($stream->status == 1)
                                        <button type="button"
                                            class="stm-delete-btn"
                                            disabled
                                            aria-disabled="true"
                                            title="Cannot delete active stream">
                                            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">delete</i>
                                        </button>
                                        @else
                                        <form action="{{ route('stream.destroy', $stream->pk) }}" method="POST"
                                            class="d-inline stm-delete-form"
                                            onsubmit="return confirm('Are you sure you want to delete this stream?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="stm-delete-btn"
                                                aria-label="Delete stream"
                                                title="Delete stream">
                                                <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">delete</i>
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr class="stm-empty-row">
                                <td colspan="4" class="stm-empty-state text-center">
                                    <i class="bi bi-diagram-3 display-4 text-secondary opacity-50 d-block mb-3" aria-hidden="true"></i>
                                    <h5 class="fw-semibold text-dark mb-1">No Streams Found</h5>
                                    <p class="text-secondary mb-0">Add a stream to get started.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($streams->hasPages() || $streams->total() > 0)
                <div class="programme-dt-footer stm-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3 pt-3">
                    <nav aria-label="Stream pagination" class="stm-pagination">
                        {{ $streams->withQueryString()->links('vendor.pagination.custom') }}
                    </nav>
                    <div class="stm-dt-info">
                        @if($streams->total() > 0)
                            Showing {{ $streams->firstItem() }}–{{ $streams->lastItem() }} of {{ $streams->total() }} items
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

<div class="modal fade" id="streamModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-md modal-dialog-centered stm-stream-modal-dialog">
        <div class="modal-content cgt-form-modal border-0 shadow-lg rounded-4">
            <div class="modal-header">
                <h5 class="modal-title mb-0 fw-bold">Add Stream</h5>
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
    var tableSelector = '#stream-master-table';
    var modalEl = document.getElementById('streamModal');
    var modalBody = modalEl ? modalEl.querySelector('.modal-body') : null;
    var modalTitle = modalEl ? modalEl.querySelector('.modal-title') : null;
    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    if (modalEl && modalEl.parentElement && modalEl.parentElement !== document.body) {
        document.body.appendChild(modalEl);
    }

    function loadStreamForm(url, title) {
        if (!modalEl || !modalBody || !modalTitle) {
            return;
        }

        modalTitle.textContent = title || 'Add Stream';
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

    function clearStmFormErrors(form) {
        if (!form) {
            return;
        }
        form.querySelectorAll('.is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });
        form.querySelectorAll('.stm-field-error').forEach(function (el) {
            el.textContent = '';
            el.classList.add('d-none');
        });
    }

    function showStmFormErrors(form, errors) {
        if (!form || !errors) {
            return;
        }
        Object.entries(errors).forEach(function (entry) {
            var field = entry[0];
            var messages = entry[1];
            var input = form.querySelector('[name="' + field + '"]');
            var errorEl = form.querySelector('.stm-field-error[data-field="' + field + '"]');
            if (input) {
                input.classList.add('is-invalid');
            }
            if (errorEl && messages && messages[0]) {
                errorEl.textContent = messages[0];
                errorEl.classList.remove('d-none');
            }
        });
    }

    function bindStreamModalHandlers() {
        document.addEventListener('click', function (e) {
            var createBtn = e.target.closest('#openCreateStream');
            if (createBtn) {
                e.preventDefault();
                loadStreamForm(createBtn.getAttribute('href'), 'Add Stream');
                return;
            }

            var editLink = e.target.closest('.stm-edit-link');
            if (editLink) {
                e.preventDefault();
                loadStreamForm(editLink.getAttribute('href'), 'Edit Stream');
            }
        });

        document.addEventListener('submit', function (e) {
            var form = e.target.closest('#streamForm');
            if (!form) {
                return;
            }

            e.preventDefault();
            clearStmFormErrors(form);

            var submitBtn = form.querySelector('#saveStreamForm');
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
                            showStmFormErrors(form, data?.errors || {});
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

        @if (request('open_stm_modal') === 'add')
            loadStreamForm("{{ route('stream.create') }}", 'Add Stream');
        @elseif (request('open_stm_modal') === 'edit' && request('stm_id'))
            loadStreamForm("{{ route('stream.edit', request('stm_id')) }}", 'Edit Stream');
        @endif
    }

    function styleStmEditLink($link) {
        $link.removeClass('btn btn-sm btn-outline-primary d-flex align-items-center gap-1');
        $link.addClass('stm-action-btn stm-action-edit');
        $link.empty().append('<i class="bi bi-pencil" aria-hidden="true"></i>');
    }

    function styleStmDeleteBtn($btn) {
        $btn.removeClass('btn btn-sm btn-outline-danger btn-outline-secondary d-flex align-items-center gap-1');
        $btn.addClass('stm-action-btn stm-action-delete');
        $btn.empty().append('<i class="bi bi-trash" aria-hidden="true"></i>');
    }

    function updateStmStatusBadge($toggle, isActive) {
        var $badge = $toggle.closest('tr').find('.stm-status-badge');
        if (!$badge.length) {
            return;
        }
        $badge
            .removeClass('programme-status-badge--active programme-status-badge--inactive')
            .addClass(isActive ? 'programme-status-badge--active' : 'programme-status-badge--inactive')
            .text(isActive ? 'Active' : 'Inactive');
    }

    function decorateStmRows() {
        jQuery(tableSelector + ' tbody tr').not('.stm-empty-row').each(function () {
            var $row = jQuery(this);

            if ($row.hasClass('stm-row-decorated')) {
                return;
            }

            var $cells = $row.find('td');
            if ($cells.length < 4) {
                return;
            }

            var $statusCell = $cells.eq(2);
            var $actionCell = $cells.eq(3);
            var $toggleWrap = $statusCell.find('.stm-status-toggle-source').first();
            var $toggle = $toggleWrap.find('.status-toggle').first();
            var $sourceActions = $actionCell.find('.stm-stream-actions-source').first();

            if ($toggle.length) {
                var isActive = $toggle.is(':checked');
                var badgeClass = isActive ? 'programme-status-badge--active' : 'programme-status-badge--inactive';
                var label = isActive ? 'Active' : 'Inactive';

                $toggleWrap.detach();
                $statusCell.empty().append(
                    jQuery('<span>', {
                        class: 'badge rounded-pill programme-status-badge stm-status-badge ' + badgeClass,
                        text: label
                    })
                );

                var $group = jQuery('<div>', {
                    class: 'stm-stream-actions',
                    role: 'group',
                    'aria-label': 'Stream actions'
                });

                var $editLink = $sourceActions.find('.stm-edit-link').first();
                if ($editLink.length) {
                    styleStmEditLink($editLink);
                    $group.append($editLink);
                }

                $toggleWrap.addClass('stm-action-switch-wrap mb-0');
                $group.append($toggleWrap);

                var $deleteBtn = $sourceActions.find('.stm-delete-btn').first();
                if ($deleteBtn.length) {
                    styleStmDeleteBtn($deleteBtn);
                    var $form = $deleteBtn.closest('.stm-delete-form');
                    $group.append($form.length ? $form : $deleteBtn);
                }

                $actionCell.empty().append($group);
            }

            $row.addClass('stm-row-decorated');
        });
    }

    function bindStmListSearch() {
        jQuery('#stmListSearch').on('input', function () {
            var query = jQuery(this).val().toLowerCase().trim();
            jQuery(tableSelector + ' tbody tr').not('.stm-empty-row').each(function () {
                var $row = jQuery(this);
                var text = ($row.data('stm-name') || '').toLowerCase();
                $row.toggle(!query || text.indexOf(query) !== -1);
            });
        });
    }

    function initStmPage() {
        bindStreamModalHandlers();

        if (typeof jQuery === 'undefined') {
            return;
        }

        decorateStmRows();
        bindStmListSearch();

        jQuery(document).on('change', tableSelector + ' .status-toggle', function () {
            var $toggle = jQuery(this);
            window.setTimeout(function () {
                updateStmStatusBadge($toggle, $toggle.is(':checked'));
            }, 0);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initStmPage);
    } else {
        initStmPage();
    }
})();
</script>
@endpush
