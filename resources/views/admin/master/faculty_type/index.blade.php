@extends('admin.layouts.master')

@section('title', 'Faculty Type')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/faculty-type-master-admin.css') }}?v={{ @filemtime(public_path('css/faculty-type-master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid ftm-master-page">
    <x-breadcrum title="Faculty Type">
        <button type="button"
            class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 rounded-2 fw-semibold text-nowrap shadow-sm ftm-open-add-btn"
            aria-controls="ftmTypeModal">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Add Faculty Type</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    <div class="card ftm-dt-card shadow-sm rounded-3 overflow-hidden">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center justify-content-end gap-3 mb-4">
                <div id="ftmDtSearch" class="programme-dt-search ms-lg-auto">
                    <div class="dataTables_filter">
                        <label>
                            <input type="search"
                                id="ftmListSearch"
                                class="form-control"
                                placeholder="Search"
                                aria-label="Search faculty type">
                        </label>
                    </div>
                </div>
            </div>

            <div class="programme-dt-panel ftm-dt-panel">
                <div class="table-responsive ftm-dt-scroll">
                    <table id="faculty-type-master-table"
                        class="table table-hover align-middle mb-0 w-100 programme-dt-table border-0">
                        <thead>
                            <tr>
                                <th>S. No.</th>
                                <th>Short Name</th>
                                <th>Faculty Name</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($facultyTypes as $index => $facultyType)
                            <tr class="odd"
                                data-ftm-short="{{ $facultyType->shot_faculty_type_name ?? '' }}"
                                data-ftm-name="{{ $facultyType->faculty_type_name ?? '' }}">
                                <td>{{ $facultyTypes->firstItem() + $index }}</td>
                                <td>{{ $facultyType->shot_faculty_type_name ?? 'N/A' }}</td>
                                <td>{{ $facultyType->faculty_type_name ?? 'N/A' }}</td>
                                <td>
                                    <div class="form-check form-switch d-inline-block ftm-status-toggle-source">
                                        <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                            data-table="faculty_type_master" data-column="active_inactive"
                                            data-id="{{ $facultyType->pk }}"
                                            {{ $facultyType->active_inactive == 1 ? 'checked' : '' }}>
                                    </div>
                                </td>
                                <td>
                                    <div class="ftm-type-actions-source d-inline-flex align-items-center gap-2" role="group"
                                        aria-label="Faculty type actions">
                                        <a href="{{ route('master.faculty.type.master.edit', ['id' => encrypt($facultyType->pk)]) }}"
                                            class="ftm-edit-link"
                                            aria-label="Edit faculty type">
                                            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">edit</i>
                                        </a>

                                        @if($facultyType->active_inactive == 1)
                                        <button type="button"
                                            class="ftm-delete-btn"
                                            disabled
                                            aria-disabled="true"
                                            title="Cannot delete active Faculty Type">
                                            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">delete</i>
                                        </button>
                                        @else
                                        <form
                                            action="{{ route('master.faculty.type.master.delete', ['id' => encrypt($facultyType->pk)]) }}"
                                            method="POST"
                                            class="d-inline ftm-delete-form"
                                            onsubmit="return confirm('Are you sure you want to delete this record?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="ftm-delete-btn"
                                                aria-label="Delete faculty type"
                                                title="Delete Faculty Type">
                                                <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">delete</i>
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr class="ftm-empty-row">
                                <td colspan="5" class="text-center text-muted py-4">No records found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($facultyTypes->hasPages() || $facultyTypes->total() > 0)
                <div class="programme-dt-footer ftm-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3">
                    <div class="ftm-pagination">
                        {{ $facultyTypes->withQueryString()->links() }}
                    </div>
                    <div class="ftm-dt-info">
                        @if($facultyTypes->total() > 0)
                            Showing {{ $facultyTypes->firstItem() }}–{{ $facultyTypes->lastItem() }} of {{ $facultyTypes->total() }} items
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

<div class="modal fade ftm-type-modal" id="ftmTypeModal" tabindex="-1" aria-labelledby="ftmTypeModalLabel" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered ftm-type-modal-dialog">
        <div class="modal-content cgt-form-modal border-0 shadow-lg">
            <div class="modal-header">
                <h5 class="modal-title mb-0" id="ftmTypeModalLabel">Add Faculty Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="ftmTypeForm" class="ftm-type-modal-form" novalidate>
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="pk" id="ftm_pk" value="">

                    <label for="ftm_shot_faculty_type_name" class="form-label cgt-field-label mb-2">
                        Faculty Short Name <span class="text-danger">*</span>
                    </label>
                    <input type="text"
                        name="shot_faculty_type_name"
                        id="ftm_shot_faculty_type_name"
                        class="form-control rounded-2 mb-4"
                        placeholder="eg. INT"
                        maxlength="255"
                        autocomplete="off">
                    <small class="text-danger d-none mb-3 d-block" id="ftm_shot_faculty_type_name_error">
                        Faculty Short Name is required
                    </small>

                    <label for="ftm_faculty_type_name" class="form-label cgt-field-label mb-2">
                        Faculty Name <span class="text-danger">*</span>
                    </label>
                    <input type="text"
                        name="faculty_type_name"
                        id="ftm_faculty_type_name"
                        class="form-control rounded-2"
                        placeholder="eg. Internal"
                        maxlength="255"
                        autocomplete="off">
                    <small class="text-danger d-none mt-1" id="ftm_faculty_type_name_error">
                        Faculty Name is required
                    </small>
                </form>
            </div>
            <div class="modal-footer gap-2 justify-content-end">
                <button type="button" class="btn btn-outline-primary rounded-2 px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary rounded-2 px-4" id="ftmFormSubmit">Create Faculty Type</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var tableSelector = '#faculty-type-master-table';
    var storeUrl = "{{ route('master.faculty.type.master.store') }}";
    var csrfToken = "{{ csrf_token() }}";
    var ftmModalMode = 'add';

    var ftmModalEl = document.getElementById('ftmTypeModal');
    if (ftmModalEl && ftmModalEl.parentElement && ftmModalEl.parentElement !== document.body) {
        document.body.appendChild(ftmModalEl);
    }

    function showFtmModal() {
        if (!ftmModalEl) {
            return;
        }
        if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(ftmModalEl).show();
        } else if (window.jQuery) {
            jQuery(ftmModalEl).modal('show');
        }
    }

    function hideFtmModal() {
        if (!ftmModalEl) {
            return;
        }
        if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(ftmModalEl).hide();
        } else if (window.jQuery) {
            jQuery(ftmModalEl).modal('hide');
        }
    }

    function clearFtmFieldErrors() {
        jQuery('#ftm_shot_faculty_type_name_error, #ftm_faculty_type_name_error').addClass('d-none');
        jQuery('#ftm_shot_faculty_type_name, #ftm_faculty_type_name').removeClass('is-invalid');
    }

    function showFtmFieldError(field, message) {
        var map = {
            shot_faculty_type_name: '#ftm_shot_faculty_type_name_error',
            faculty_type_name: '#ftm_faculty_type_name_error'
        };
        var inputMap = {
            shot_faculty_type_name: '#ftm_shot_faculty_type_name',
            faculty_type_name: '#ftm_faculty_type_name'
        };
        if (map[field]) {
            jQuery(map[field]).text(message).removeClass('d-none');
            jQuery(inputMap[field]).addClass('is-invalid');
        }
    }

    function openFtmModal(mode, data) {
        ftmModalMode = mode;
        var isAdd = mode === 'add';

        jQuery('#ftmTypeModalLabel').text(isAdd ? 'Add Faculty Type' : 'Edit Faculty Type');
        jQuery('#ftmFormSubmit').text(isAdd ? 'Create Faculty Type' : 'Update Faculty Type');
        jQuery('#ftm_pk').val(isAdd ? '' : (data.pk || ''));
        jQuery('#ftm_shot_faculty_type_name').val(isAdd ? '' : (data.shortName || ''));
        jQuery('#ftm_faculty_type_name').val(isAdd ? '' : (data.facultyName || ''));
        clearFtmFieldErrors();
        showFtmModal();

        window.setTimeout(function () {
            jQuery('#ftm_shot_faculty_type_name').trigger('focus');
        }, 200);
    }

    function extractEncryptedPkFromUrl(url) {
        if (!url) {
            return '';
        }
        var parts = String(url).replace(/\/+$/, '').split('/');
        return parts[parts.length - 1] || '';
    }

    function styleFtmEditLink($link) {
        $link.removeClass('btn btn-sm btn-outline-primary');
        $link.addClass('ftm-action-btn ftm-action-edit');
        $link.empty().append('<i class="bi bi-pencil" aria-hidden="true"></i>');
    }

    function styleFtmDeleteBtn($btn) {
        $btn.removeClass('btn btn-sm btn-outline-danger btn-outline-secondary');
        $btn.addClass('ftm-action-btn ftm-action-delete');
        $btn.empty().append('<i class="bi bi-trash" aria-hidden="true"></i>');
    }

    function updateFtmStatusBadge($toggle, isActive) {
        var $badge = $toggle.closest('tr').find('.ftm-status-badge');
        if (!$badge.length) {
            return;
        }
        $badge
            .removeClass('programme-status-badge--active programme-status-badge--inactive')
            .addClass(isActive ? 'programme-status-badge--active' : 'programme-status-badge--inactive')
            .text(isActive ? 'Active' : 'Inactive');
    }

    function decorateFtmRows() {
        jQuery(tableSelector + ' tbody tr').not('.ftm-empty-row').each(function () {
            var $row = jQuery(this);

            if ($row.hasClass('ftm-row-decorated')) {
                return;
            }

            var $cells = $row.find('td');
            if ($cells.length < 5) {
                return;
            }

            var $statusCell = $cells.eq(3);
            var $actionCell = $cells.eq(4);
            var $toggleWrap = $statusCell.find('.ftm-status-toggle-source').first();
            var $toggle = $toggleWrap.find('.status-toggle').first();
            var $sourceActions = $actionCell.find('.ftm-type-actions-source').first();

            if ($toggle.length) {
                var isActive = $toggle.is(':checked');
                var badgeClass = isActive ? 'programme-status-badge--active' : 'programme-status-badge--inactive';
                var label = isActive ? 'Active' : 'Inactive';

                $toggleWrap.detach();
                $statusCell.empty().append(
                    jQuery('<span>', {
                        class: 'badge rounded-pill programme-status-badge ftm-status-badge ' + badgeClass,
                        text: label
                    })
                );

                var $group = jQuery('<div>', {
                    class: 'ftm-type-actions',
                    role: 'group',
                    'aria-label': 'Faculty type actions'
                });

                var $editLink = $sourceActions.find('.ftm-edit-link').first();
                if ($editLink.length) {
                    styleFtmEditLink($editLink);
                    $group.append($editLink);
                }

                $toggleWrap.addClass('ftm-action-switch-wrap mb-0');
                $group.append($toggleWrap);

                var $deleteBtn = $sourceActions.find('.ftm-delete-btn').first();
                if ($deleteBtn.length) {
                    styleFtmDeleteBtn($deleteBtn);
                    var $form = $deleteBtn.closest('.ftm-delete-form');
                    $group.append($form.length ? $form : $deleteBtn);
                }

                $actionCell.empty().append($group);
            }

            $row.addClass('ftm-row-decorated');
        });
    }

    function bindFtmListSearch() {
        jQuery('#ftmListSearch').on('input', function () {
            var query = jQuery(this).val().toLowerCase().trim();
            jQuery(tableSelector + ' tbody tr').not('.ftm-empty-row').each(function () {
                var $row = jQuery(this);
                var text = ($row.data('ftm-short') + ' ' + $row.data('ftm-name')).toLowerCase();
                $row.toggle(!query || text.indexOf(query) !== -1);
            });
        });
    }

    function submitFtmForm() {
        var shortName = jQuery('#ftm_shot_faculty_type_name').val().trim();
        var facultyName = jQuery('#ftm_faculty_type_name').val().trim();
        clearFtmFieldErrors();

        var hasError = false;
        if (!shortName) {
            showFtmFieldError('shot_faculty_type_name', 'Faculty Short Name is required');
            hasError = true;
        }
        if (!facultyName) {
            showFtmFieldError('faculty_type_name', 'Faculty Name is required');
            hasError = true;
        }
        if (hasError) {
            jQuery(!shortName ? '#ftm_shot_faculty_type_name' : '#ftm_faculty_type_name').trigger('focus');
            return;
        }

        var payload = {
            _token: csrfToken,
            shot_faculty_type_name: shortName,
            faculty_type_name: facultyName
        };

        if (ftmModalMode === 'edit') {
            payload.pk = jQuery('#ftm_pk').val();
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
                hideFtmModal();
                var message = (response && response.message) ? response.message : 'Faculty Type saved successfully.';
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: ftmModalMode === 'edit' ? 'Updated!' : 'Created!',
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
                    if (errors.shot_faculty_type_name && errors.shot_faculty_type_name[0]) {
                        showFtmFieldError('shot_faculty_type_name', errors.shot_faculty_type_name[0]);
                    }
                    if (errors.faculty_type_name && errors.faculty_type_name[0]) {
                        showFtmFieldError('faculty_type_name', errors.faculty_type_name[0]);
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

    function initFtmPage() {
        if (typeof jQuery === 'undefined') {
            return;
        }

        decorateFtmRows();
        bindFtmListSearch();

        jQuery(document).on('click', '.ftm-open-add-btn', function (e) {
            e.preventDefault();
            openFtmModal('add');
        });

        jQuery(document).on('click', tableSelector + ' tbody a[href*="faculty-type-master/edit"]', function (e) {
            e.preventDefault();
            var $row = jQuery(this).closest('tr');
            openFtmModal('edit', {
                pk: extractEncryptedPkFromUrl(jQuery(this).attr('href')),
                shortName: $row.data('ftm-short') || $row.find('td').eq(1).text().trim(),
                facultyName: $row.data('ftm-name') || $row.find('td').eq(2).text().trim()
            });
        });

        if (ftmModalEl) {
            ftmModalEl.addEventListener('hidden.bs.modal', function () {
                clearFtmFieldErrors();
                jQuery('#ftm_pk').val('');
                jQuery('#ftm_shot_faculty_type_name, #ftm_faculty_type_name').val('');
            });
        }

        jQuery('#ftmFormSubmit').on('click', submitFtmForm);
        jQuery('#ftmTypeForm').on('submit', function (e) {
            e.preventDefault();
            submitFtmForm();
        });

        jQuery(document).on('change', tableSelector + ' .status-toggle', function () {
            var $toggle = jQuery(this);
            window.setTimeout(function () {
                updateFtmStatusBadge($toggle, $toggle.is(':checked'));
            }, 0);
        });

        var params = new URLSearchParams(window.location.search);
        if (params.get('open_ftm_modal') === 'add') {
            openFtmModal('add');
            if (window.history && window.history.replaceState) {
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        } else if (params.get('open_ftm_modal') === 'edit') {
            var shortName = params.get('ftm_short') || '';
            var facultyName = params.get('ftm_name') || '';
            try {
                shortName = decodeURIComponent(shortName.replace(/\+/g, ' '));
                facultyName = decodeURIComponent(facultyName.replace(/\+/g, ' '));
            } catch (e) { /* keep raw */ }
            openFtmModal('edit', {
                pk: params.get('ftm_pk') || '',
                shortName: shortName,
                facultyName: facultyName
            });
            if (window.history && window.history.replaceState) {
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFtmPage);
    } else {
        initFtmPage();
    }
})();
</script>
@endpush
