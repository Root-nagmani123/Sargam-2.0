@extends('admin.layouts.master')

@section('title', 'Member')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/roles-admin.css') }}?v={{ @filemtime(public_path('css/roles-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid roles-page">
    <x-breadcrum title="User Management Roles">
        <button type="button"
            class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 rounded-2 fw-semibold text-nowrap shadow-sm roles-open-add-btn"
            aria-controls="rolesRoleModal">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Add Roles</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    <div class="card roles-dt-card shadow-sm rounded-3 overflow-hidden">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center justify-content-end gap-3 mb-4">
                <div id="rolesDtSearch" class="programme-dt-search ms-lg-auto" data-dt-search-for="role-table"></div>
            </div>

            <div class="programme-dt-panel roles-dt-panel">
                <div class="table-responsive roles-dt-scroll">
                    {{ $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table border-0']) }}
                </div>
                <div id="rolesDtFooter"
                    class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                    data-dt-footer-for="role-table"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade roles-role-modal" id="rolesRoleModal" tabindex="-1" aria-labelledby="rolesRoleModalLabel" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered roles-role-modal-dialog">
        <div class="modal-content cgt-form-modal border-0 shadow-lg">
            <div class="modal-header">
                <h5 class="modal-title mb-0" id="rolesRoleModalLabel">Add Role</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="rolesRoleForm" class="roles-role-modal-form" novalidate>
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="role_id" id="roles_role_id" value="">

                    <label for="roles_name" class="form-label cgt-field-label mb-2">
                        Role Name <span class="text-danger">*</span>
                    </label>
                    <input type="text"
                        name="name"
                        id="roles_name"
                        class="form-control rounded-2"
                        placeholder="eg. Personal Assistant"
                        autocomplete="off">
                    <small class="text-danger d-none mt-1" id="roles_name_error">Role Name is required</small>

                    <label for="roles_display_name" class="form-label cgt-field-label mb-2 mt-3">
                        Role Display Name <span class="text-danger">*</span>
                    </label>
                    <input type="text"
                        name="display_name"
                        id="roles_display_name"
                        class="form-control rounded-2"
                        placeholder="eg. PA"
                        autocomplete="off">
                    <small class="text-danger d-none mt-1" id="roles_display_name_error">Role Display Name is required</small>
                </form>
            </div>
            <div class="modal-footer gap-2 justify-content-end">
                <button type="button" class="btn btn-outline-primary rounded-2 px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary rounded-2 px-4" id="rolesFormSubmit">Create Role</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    {{ $dataTable->scripts() }}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
(function () {
    var tableSelector = '#role-table';
    var storeUrl = "{{ route('admin.roles.store') }}";
    var updateBaseUrl = "{{ url('admin/roles') }}";
    var csrfToken = "{{ csrf_token() }}";
    var roleModalMode = 'add';
    var roleTableBound = false;
    var roleInitAttempts = 0;
    var roleModalBound = false;

    var roleModalEl = document.getElementById('rolesRoleModal');
    if (roleModalEl && roleModalEl.parentElement && roleModalEl.parentElement !== document.body) {
        document.body.appendChild(roleModalEl);
    }

    function showRoleModal() {
        if (!roleModalEl) {
            return;
        }
        if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(roleModalEl).show();
        } else if (window.jQuery) {
            jQuery(roleModalEl).modal('show');
        }
    }

    function hideRoleModal() {
        if (!roleModalEl) {
            return;
        }
        if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(roleModalEl).hide();
        } else if (window.jQuery) {
            jQuery(roleModalEl).modal('hide');
        }
    }

    function clearRoleFieldErrors() {
        jQuery('#roles_name_error, #roles_display_name_error').addClass('d-none');
        jQuery('#roles_name, #roles_display_name').removeClass('is-invalid');
    }

    function showRoleFieldError(fieldId, message) {
        jQuery('#' + fieldId + '_error').text(message).removeClass('d-none');
        jQuery('#' + fieldId).addClass('is-invalid');
    }

    function openRoleModal(mode, data) {
        roleModalMode = mode;
        var isAdd = mode === 'add';

        jQuery('#rolesRoleModalLabel').text(isAdd ? 'Add Role' : 'Edit Role');
        jQuery('#rolesFormSubmit').text(isAdd ? 'Create Role' : 'Update Role');
        jQuery('#roles_role_id').val(isAdd ? '' : (data.id || ''));
        jQuery('#roles_name').val(isAdd ? '' : (data.name || ''));
        jQuery('#roles_display_name').val(isAdd ? '' : (data.display_name || ''));
        clearRoleFieldErrors();
        showRoleModal();

        window.setTimeout(function () {
            jQuery('#roles_name').trigger('focus');
        }, 200);
    }

    function extractEncryptedIdFromUrl(url) {
        if (!url) {
            return '';
        }
        var parts = String(url).replace(/\/+$/, '').split('/');
        return parts[parts.length - 1] || '';
    }

    function reloadRoleTable() {
        if (typeof jQuery !== 'undefined' && jQuery.fn.DataTable && jQuery.fn.DataTable.isDataTable(tableSelector)) {
            jQuery(tableSelector).DataTable().ajax.reload(null, false);
        }
    }

    function styleRoleEditLink($link) {
        $link.removeClass('text-primary me-2');
        $link.addClass('roles-action-btn roles-action-edit');
        $link.attr('aria-label', 'Edit role');
        $link.empty().append('<i class="bi bi-pencil" aria-hidden="true"></i>');
    }

    function styleRoleDeleteLink($link) {
        $link.removeClass('text-danger');
        $link.addClass('roles-action-btn roles-action-delete delete-role-link');
        $link.attr('aria-label', 'Delete role');
        $link.empty().append('<i class="bi bi-trash" aria-hidden="true"></i>');
    }

    function normalizeRoleHeaders() {
        var $firstTh = jQuery(tableSelector).find('thead tr th').first();
        if ($firstTh.length) {
            var text = $firstTh.text().trim().replace(/\s+/g, '');
            if (text === '#' || text === 'S.No' || text === 'S.No.') {
                $firstTh.text('S. No.');
            }
        }
    }

    function updateRoleStatusBadge($toggle, isActive) {
        var $badge = $toggle.closest('tr').find('.roles-status-badge');
        if (!$badge.length) {
            return;
        }
        $badge
            .removeClass('programme-status-badge--active programme-status-badge--inactive')
            .addClass(isActive ? 'programme-status-badge--active' : 'programme-status-badge--inactive')
            .text(isActive ? 'Active' : 'Inactive');
    }

    function decorateRoleRows() {
        normalizeRoleHeaders();

        jQuery(tableSelector + ' tbody tr').each(function () {
            var $row = jQuery(this);

            if ($row.hasClass('roles-row-decorated')) {
                return;
            }

            var $cells = $row.find('td');
            if ($cells.length < 5) {
                return;
            }

            if (!$row.data('roles-name')) {
                $row.data('roles-name', $cells.eq(1).text().trim());
            }
            if (!$row.data('roles-display-name')) {
                $row.data('roles-display-name', $cells.eq(2).text().trim());
            }

            var $statusCell = $cells.eq(3);
            var $actionCell = $cells.eq(4);
            var $toggleWrap = $statusCell.find('.form-check').first();
            var $toggle = $toggleWrap.find('.status-toggle').first();

            if (!$toggle.length) {
                return;
            }

            var isActive = $toggle.is(':checked');
            var badgeClass = isActive ? 'programme-status-badge--active' : 'programme-status-badge--inactive';
            var label = isActive ? 'Active' : 'Inactive';

            $toggleWrap.detach();
            $statusCell.empty().append(
                jQuery('<span>', {
                    class: 'badge rounded-pill programme-status-badge roles-status-badge ' + badgeClass,
                    text: label
                })
            );

            var $group = jQuery('<div>', {
                class: 'roles-role-actions',
                role: 'group',
                'aria-label': 'Role actions'
            });

            var $editLink = $actionCell.find('a[href*="edit"]').first();
            if ($editLink.length) {
                styleRoleEditLink($editLink);
                $group.append($editLink);
            }

            $toggleWrap.addClass('roles-action-switch-wrap mb-0');
            $group.append($toggleWrap);

            var $deleteLink = $actionCell.find('.delete-role-link').first();
            var $deleteForm = $actionCell.find('form.delete-role-form').first();
            if ($deleteLink.length) {
                styleRoleDeleteLink($deleteLink);
                if ($deleteForm.length) {
                    $group.append($deleteForm);
                } else {
                    $group.append($deleteLink);
                }
            }

            $actionCell.empty().append($group);
            $row.addClass('roles-row-decorated');
        });
    }

    function initRoleTableUi() {
        if (typeof jQuery === 'undefined' || !jQuery.fn.DataTable || !jQuery.fn.DataTable.isDataTable(tableSelector)) {
            if (roleInitAttempts++ < 50) {
                window.setTimeout(initRoleTableUi, 100);
            }
            return;
        }

        if (roleTableBound) {
            decorateRoleRows();
            return;
        }

        roleTableBound = true;
        var table = jQuery(tableSelector).DataTable();

        decorateRoleRows();
        table.on('draw.dt', function () {
            jQuery(tableSelector + ' tbody tr').removeClass('roles-row-decorated');
            decorateRoleRows();
        });
    }

    function submitRoleForm() {
        var name = jQuery('#roles_name').val().trim();
        var displayName = jQuery('#roles_display_name').val().trim();
        clearRoleFieldErrors();

        if (!name) {
            showRoleFieldError('roles_name', 'Role Name is required');
            jQuery('#roles_name').trigger('focus');
            return;
        }
        if (!displayName) {
            showRoleFieldError('roles_display_name', 'Role Display Name is required');
            jQuery('#roles_display_name').trigger('focus');
            return;
        }

        var isEdit = roleModalMode === 'edit';
        var roleId = jQuery('#roles_role_id').val();
        var payload = {
            _token: csrfToken,
            name: name,
            display_name: displayName
        };
        var requestUrl = storeUrl;
        var requestMethod = 'POST';

        if (isEdit && roleId) {
            requestUrl = updateBaseUrl + '/' + encodeURIComponent(roleId);
            payload._method = 'PUT';
        }

        jQuery.ajax({
            url: requestUrl,
            method: requestMethod,
            data: payload,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            success: function (response) {
                hideRoleModal();
                reloadRoleTable();

                var message = response.message || (isEdit ? 'Role updated successfully' : 'Role created successfully');

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: isEdit ? 'Updated!' : 'Created!',
                        text: message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                }
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    var errors = xhr.responseJSON.errors;
                    if (errors.name && errors.name[0]) {
                        showRoleFieldError('roles_name', errors.name[0]);
                    }
                    if (errors.display_name && errors.display_name[0]) {
                        showRoleFieldError('roles_display_name', errors.display_name[0]);
                    }
                    return;
                }

                var message = (xhr.responseJSON && xhr.responseJSON.message)
                    ? xhr.responseJSON.message
                    : 'Something went wrong. Please try again.';

                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', title: 'Error', text: message });
                }
            }
        });
    }

    function bindRoleModalHandlers() {
        if (roleModalBound || typeof jQuery === 'undefined') {
            return;
        }
        roleModalBound = true;

        jQuery(document).on('click', '.roles-open-add-btn', function (e) {
            e.preventDefault();
            openRoleModal('add');
        });

        jQuery(document).on('click', tableSelector + ' .roles-action-edit', function (e) {
            e.preventDefault();
            var $row = jQuery(this).closest('tr');
            openRoleModal('edit', {
                id: extractEncryptedIdFromUrl(jQuery(this).attr('href')),
                name: $row.data('roles-name') || $row.find('td').eq(1).text().trim(),
                display_name: $row.data('roles-display-name') || $row.find('td').eq(2).text().trim()
            });
        });

        jQuery('#rolesFormSubmit').on('click', submitRoleForm);

        jQuery('#rolesRoleForm').on('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                submitRoleForm();
            }
        });
    }

    function openRoleModalFromQueryParams() {
        var params = new URLSearchParams(window.location.search);
        var modalMode = params.get('open_roles_modal');

        if (!modalMode) {
            return;
        }

        if (modalMode === 'add') {
            openRoleModal('add');
        } else if (modalMode === 'edit') {
            openRoleModal('edit', {
                id: params.get('roles_id') || '',
                name: params.get('roles_name') || '',
                display_name: params.get('roles_display_name') || ''
            });
        }

        if (window.history && window.history.replaceState) {
            var cleanUrl = window.location.pathname;
            window.history.replaceState({}, document.title, cleanUrl);
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        bindRoleModalHandlers();
        initRoleTableUi();
        openRoleModalFromQueryParams();

        jQuery(document).on('click', '.delete-role-link', function (e) {
            e.preventDefault();
            jQuery(this).closest('form.delete-role-form').trigger('submit');
        });

        jQuery(document).on('change', tableSelector + ' .status-toggle', function () {
            updateRoleStatusBadge(jQuery(this), jQuery(this).is(':checked'));
        });

        jQuery(document).on('submit', 'form.delete-role-form', function (e) {
            e.preventDefault();

            var form = this;
            var row = jQuery(this).closest('tr');
            var toggle = row.find('.status-toggle');
            var isActive = toggle.is(':checked');

            if (isActive) {
                Swal.fire({
                    icon: 'error',
                    title: 'Cannot Delete!',
                    text: 'Active role cannot be deleted. Please deactivate the role first.',
                    confirmButtonColor: '#d33',
                });
                return false;
            }

            Swal.fire({
                title: 'Are you sure?',
                text: 'This role will be permanently deleted!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then(function (result) {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
})();
    </script>
@endpush
