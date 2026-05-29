@extends('admin.layouts.master')

@section('title', 'Faculty Expertise')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/faculty-expertise-master-admin.css') }}?v={{ @filemtime(public_path('css/faculty-expertise-master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid fem-master-page">
    <x-breadcrum title="Faculty Expertise">
        <button type="button"
            class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 rounded-2 fw-semibold text-nowrap shadow-sm fem-open-add-btn"
            aria-controls="femExpertiseModal">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Add Faculty Expertise</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    <div class="card fem-dt-card shadow-sm rounded-3 overflow-hidden">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center justify-content-end gap-3 mb-4">
                <div id="femDtSearch" class="programme-dt-search ms-lg-auto">
                    <div class="dataTables_filter">
                        <label>
                            <input type="search"
                                id="femListSearch"
                                class="form-control"
                                placeholder="Search"
                                aria-label="Search faculty expertise">
                        </label>
                    </div>
                </div>
            </div>

            <div class="programme-dt-panel fem-dt-panel">
                <div class="table-responsive fem-dt-scroll">
                    <table id="faculty-expertise-master-table"
                        class="table table-hover align-middle mb-0 w-100 programme-dt-table border-0">
                        <thead>
                            <tr>
                                <th>S. No.</th>
                                <th>Faculty Expertise</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($faculties as $index => $faculty)
                            <tr class="odd" data-fem-name="{{ $faculty->expertise_name ?? '' }}">
                                <td>{{ $faculties->firstItem() + $index }}</td>
                                <td>{{ $faculty->expertise_name ?? 'N/A' }}</td>
                                <td>
                                    <div class="form-check form-switch d-inline-block fem-status-toggle-source">
                                        <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                            data-table="faculty_expertise_master" data-column="active_inactive"
                                            data-id="{{ $faculty->pk }}"
                                            {{ $faculty->active_inactive == 1 ? 'checked' : '' }}>
                                    </div>
                                </td>
                                <td>
                                    <div class="fem-expertise-actions-source d-inline-flex align-items-center gap-2" role="group"
                                        aria-label="Faculty expertise actions">
                                        <a href="{{ route('master.faculty.expertise.edit', ['id' => encrypt($faculty->pk)]) }}"
                                            class="fem-edit-link"
                                            aria-label="Edit faculty expertise">
                                            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">edit</i>
                                        </a>

                                        @if($faculty->active_inactive == 1)
                                        <button type="button"
                                            class="fem-delete-btn"
                                            disabled
                                            aria-disabled="true"
                                            title="Cannot delete active record">
                                            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">delete</i>
                                        </button>
                                        @else
                                        <form
                                            action="{{ route('master.faculty.expertise.delete', ['id' => encrypt($faculty->pk)]) }}"
                                            method="POST"
                                            class="d-inline fem-delete-form"
                                            onsubmit="return confirm('Are you sure you want to delete this record?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="fem-delete-btn"
                                                aria-label="Delete faculty expertise">
                                                <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">delete</i>
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr class="fem-empty-row">
                                <td colspan="4" class="text-center text-muted py-4">No records found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($faculties->hasPages() || $faculties->total() > 0)
                <div class="programme-dt-footer fem-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3">
                    <div class="fem-pagination">
                        {{ $faculties->withQueryString()->links() }}
                    </div>
                    <div class="fem-dt-info">
                        @if($faculties->total() > 0)
                            Showing {{ $faculties->firstItem() }}–{{ $faculties->lastItem() }} of {{ $faculties->total() }} items
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

<div class="modal fade fem-expertise-modal" id="femExpertiseModal" tabindex="-1" aria-labelledby="femExpertiseModalLabel" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered fem-expertise-modal-dialog">
        <div class="modal-content cgt-form-modal border-0 shadow-lg">
            <div class="modal-header">
                <h5 class="modal-title mb-0" id="femExpertiseModalLabel">Add Faculty Expertise</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="femExpertiseForm" class="fem-expertise-modal-form" novalidate>
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="id" id="fem_id" value="">

                    <label for="fem_expertise_name" class="form-label cgt-field-label mb-2">
                        Expertise Name <span class="text-danger">*</span>
                    </label>
                    <input type="text"
                        name="expertise_name"
                        id="fem_expertise_name"
                        class="form-control rounded-2"
                        placeholder="eg. AI"
                        autocomplete="off">
                    <small class="text-danger d-none mt-1" id="fem_expertise_name_error">
                        Expertise Name is required
                    </small>
                </form>
            </div>
            <div class="modal-footer gap-2 justify-content-end">
                <button type="button" class="btn btn-outline-primary rounded-2 px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary rounded-2 px-4" id="femFormSubmit">Create Faculty Expertise</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var tableSelector = '#faculty-expertise-master-table';
    var storeUrl = "{{ route('master.faculty.expertise.store') }}";
    var csrfToken = "{{ csrf_token() }}";
    var femModalMode = 'add';

    var femModalEl = document.getElementById('femExpertiseModal');
    if (femModalEl && femModalEl.parentElement && femModalEl.parentElement !== document.body) {
        document.body.appendChild(femModalEl);
    }

    function showFemModal() {
        if (!femModalEl) {
            return;
        }
        if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(femModalEl).show();
        } else if (window.jQuery) {
            jQuery(femModalEl).modal('show');
        }
    }

    function hideFemModal() {
        if (!femModalEl) {
            return;
        }
        if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(femModalEl).hide();
        } else if (window.jQuery) {
            jQuery(femModalEl).modal('hide');
        }
    }

    function clearFemFieldErrors() {
        jQuery('#fem_expertise_name_error').addClass('d-none').text('Expertise Name is required');
        jQuery('#fem_expertise_name').removeClass('is-invalid');
    }

    function showFemFieldError(message) {
        jQuery('#fem_expertise_name_error').text(message || 'Expertise Name is required').removeClass('d-none');
        jQuery('#fem_expertise_name').addClass('is-invalid');
    }

    function openFemModal(mode, data) {
        femModalMode = mode;
        var isAdd = mode === 'add';

        jQuery('#femExpertiseModalLabel').text(isAdd ? 'Add Faculty Expertise' : 'Edit Faculty Expertise');
        jQuery('#femFormSubmit').text(isAdd ? 'Create Faculty Expertise' : 'Update Faculty Expertise');
        jQuery('#fem_id').val(isAdd ? '' : (data.id || ''));
        jQuery('#fem_expertise_name').val(isAdd ? '' : (data.name || ''));
        clearFemFieldErrors();
        showFemModal();

        window.setTimeout(function () {
            jQuery('#fem_expertise_name').trigger('focus');
        }, 200);
    }

    function extractEncryptedIdFromUrl(url) {
        if (!url) {
            return '';
        }
        var parts = String(url).replace(/\/+$/, '').split('/');
        return parts[parts.length - 1] || '';
    }

    function styleFemEditLink($link) {
        $link.removeClass('btn btn-sm btn-outline-primary');
        $link.addClass('fem-action-btn fem-action-edit');
        $link.empty().append('<i class="bi bi-pencil" aria-hidden="true"></i>');
    }

    function styleFemDeleteBtn($btn) {
        $btn.removeClass('btn btn-sm btn-outline-danger btn-outline-secondary');
        $btn.addClass('fem-action-btn fem-action-delete');
        $btn.empty().append('<i class="bi bi-trash" aria-hidden="true"></i>');
    }

    function updateFemStatusBadge($toggle, isActive) {
        var $badge = $toggle.closest('tr').find('.fem-status-badge');
        if (!$badge.length) {
            return;
        }
        $badge
            .removeClass('programme-status-badge--active programme-status-badge--inactive')
            .addClass(isActive ? 'programme-status-badge--active' : 'programme-status-badge--inactive')
            .text(isActive ? 'Active' : 'Inactive');
    }

    function decorateFemRows() {
        jQuery(tableSelector + ' tbody tr').not('.fem-empty-row').each(function () {
            var $row = jQuery(this);

            if ($row.hasClass('fem-row-decorated')) {
                return;
            }

            var $cells = $row.find('td');
            if ($cells.length < 4) {
                return;
            }

            var $statusCell = $cells.eq(2);
            var $actionCell = $cells.eq(3);
            var $toggleWrap = $statusCell.find('.fem-status-toggle-source').first();
            var $toggle = $toggleWrap.find('.status-toggle').first();
            var $sourceActions = $actionCell.find('.fem-expertise-actions-source').first();

            if ($toggle.length) {
                var isActive = $toggle.is(':checked');
                var badgeClass = isActive ? 'programme-status-badge--active' : 'programme-status-badge--inactive';
                var label = isActive ? 'Active' : 'Inactive';

                $toggleWrap.detach();
                $statusCell.empty().append(
                    jQuery('<span>', {
                        class: 'badge rounded-pill programme-status-badge fem-status-badge ' + badgeClass,
                        text: label
                    })
                );

                var $group = jQuery('<div>', {
                    class: 'fem-expertise-actions',
                    role: 'group',
                    'aria-label': 'Faculty expertise actions'
                });

                var $editLink = $sourceActions.find('.fem-edit-link').first();
                if ($editLink.length) {
                    styleFemEditLink($editLink);
                    $group.append($editLink);
                }

                $toggleWrap.addClass('fem-action-switch-wrap mb-0');
                $group.append($toggleWrap);

                var $deleteBtn = $sourceActions.find('.fem-delete-btn').first();
                if ($deleteBtn.length) {
                    styleFemDeleteBtn($deleteBtn);
                    var $form = $deleteBtn.closest('.fem-delete-form');
                    if ($form.length) {
                        $group.append($form);
                    } else {
                        $group.append($deleteBtn);
                    }
                }

                $actionCell.empty().append($group);
            }

            $row.addClass('fem-row-decorated');
        });
    }

    function bindFemListSearch() {
        var $input = jQuery('#femListSearch');
        if (!$input.length) {
            return;
        }
        $input.on('input', function () {
            var query = jQuery(this).val().toLowerCase().trim();
            jQuery(tableSelector + ' tbody tr').not('.fem-empty-row').each(function () {
                var $row = jQuery(this);
                var text = $row.find('td').eq(1).text().toLowerCase();
                $row.toggle(!query || text.indexOf(query) !== -1);
            });
        });
    }

    function submitFemForm() {
        var name = jQuery('#fem_expertise_name').val().trim();
        clearFemFieldErrors();

        if (!name) {
            showFemFieldError('Expertise Name is required');
            jQuery('#fem_expertise_name').trigger('focus');
            return;
        }

        var payload = {
            _token: csrfToken,
            expertise_name: name
        };

        if (femModalMode === 'edit') {
            payload.id = jQuery('#fem_id').val();
        }

        jQuery.ajax({
            url: storeUrl,
            method: 'POST',
            data: payload,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            success: function (response) {
                hideFemModal();
                var message = (response && response.message) ? response.message : 'Expertise saved successfully.';
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: femModalMode === 'edit' ? 'Updated!' : 'Created!',
                        text: message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(function () {
                        window.location.reload();
                    });
                } else {
                    window.location.reload();
                }
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    var errors = xhr.responseJSON.errors;
                    if (errors.expertise_name && errors.expertise_name[0]) {
                        showFemFieldError(errors.expertise_name[0]);
                    }
                    return;
                }

                var message = (xhr.responseJSON && xhr.responseJSON.message)
                    ? xhr.responseJSON.message
                    : 'Something went wrong. Please try again.';

                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', title: 'Error', text: message });
                } else if (typeof toastr !== 'undefined') {
                    toastr.error(message);
                }
            }
        });
    }

    function initFemPage() {
        if (typeof jQuery === 'undefined') {
            return;
        }

        decorateFemRows();
        bindFemListSearch();

        jQuery(document).on('click', '.fem-open-add-btn', function (e) {
            e.preventDefault();
            openFemModal('add');
        });

        jQuery(document).on('click', tableSelector + ' tbody a[href*="faculty-expertise/edit"]', function (e) {
            e.preventDefault();
            var $row = jQuery(this).closest('tr');
            openFemModal('edit', {
                id: extractEncryptedIdFromUrl(jQuery(this).attr('href')),
                name: $row.data('fem-name') || $row.find('td').eq(1).text().trim()
            });
        });

        if (femModalEl) {
            femModalEl.addEventListener('hidden.bs.modal', function () {
                clearFemFieldErrors();
                jQuery('#fem_id').val('');
                jQuery('#fem_expertise_name').val('');
            });
        }

        jQuery('#femFormSubmit').on('click', submitFemForm);
        jQuery('#femExpertiseForm').on('submit', function (e) {
            e.preventDefault();
            submitFemForm();
        });

        jQuery(document).on('change', tableSelector + ' .status-toggle', function () {
            var $toggle = jQuery(this);
            window.setTimeout(function () {
                updateFemStatusBadge($toggle, $toggle.is(':checked'));
            }, 0);
        });

        var params = new URLSearchParams(window.location.search);
        if (params.get('open_fem_modal') === 'add') {
            openFemModal('add');
            if (window.history && window.history.replaceState) {
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        } else if (params.get('open_fem_modal') === 'edit') {
            var femName = params.get('fem_name') || '';
            try {
                femName = decodeURIComponent(femName.replace(/\+/g, ' '));
            } catch (e) { /* keep raw */ }
            openFemModal('edit', {
                id: params.get('fem_id') || '',
                name: femName
            });
            if (window.history && window.history.replaceState) {
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFemPage);
    } else {
        initFemPage();
    }
})();
</script>
@endpush
