@extends('admin.layouts.master')

@section('title', 'Employee Group Master')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/employee-group-master-admin.css') }}?v={{ @filemtime(public_path('css/employee-group-master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid">
<x-breadcrum title="Employee Group Master"></x-breadcrum>
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" >
            <div class="card-body">
                
                <div class="row">
                    <div class="col-6">
                        <h4>Employee Group Master</h4>
                    </div>
                    <div class="col-6">
                        <div class="float-end gap-2">
                            <a href="{{route('master.employee.group.create')}}" class="btn btn-primary">+ Add Employee Group</a>
                        </div>
                    </div>
                </div>
                <div id="egmDtFooter"
                    class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                    data-dt-footer-for="employeegroupmaster-table"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade egm-group-modal" id="egmGroupModal" tabindex="-1" aria-labelledby="egmGroupModalLabel" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered egm-group-modal-dialog">
        <div class="modal-content cgt-form-modal egm-group-modal-content border-0 shadow-lg">
            <div class="modal-header">
                <h5 class="modal-title mb-0" id="egmGroupModalLabel">Add Employee Group</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="egmGroupForm" class="egm-group-modal-form" novalidate>
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="pk" id="egm_pk" value="">

                    <label for="egm_group_name" class="form-label cgt-field-label mb-2">
                        Employee Group Name <span class="text-danger">*</span>
                    </label>
                    <input type="text"
                        name="group_name"
                        id="egm_group_name"
                        class="form-control rounded-3"
                        placeholder="eg. General Medicine"
                        autocomplete="off">
                    <small class="text-danger d-none mt-1" id="egm_group_name_error">
                        Employee Group Name is required
                    </small>
                </form>
            </div>
            <div class="modal-footer gap-2 justify-content-end">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary rounded-3 px-4" id="egmFormSubmit">Create Employee Group</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{!! $dataTable->scripts() !!}
<script>
(function () {
    var tableSelector = '#employeegroupmaster-table';
    var storeUrl = "{{ route('master.employee.group.store') }}";
    var csrfToken = "{{ csrf_token() }}";
    var egmModalMode = 'add';

    var egmModalEl = document.getElementById('egmGroupModal');
    if (egmModalEl && egmModalEl.parentElement && egmModalEl.parentElement !== document.body) {
        document.body.appendChild(egmModalEl);
    }

    function showEgmModal() {
        if (!egmModalEl) {
            return;
        }
        if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(egmModalEl).show();
        } else if (window.jQuery) {
            jQuery(egmModalEl).modal('show');
        }
    }

    function hideEgmModal() {
        if (!egmModalEl) {
            return;
        }
        if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(egmModalEl).hide();
        } else if (window.jQuery) {
            jQuery(egmModalEl).modal('hide');
        }
    }

    function clearEgmFieldErrors() {
        jQuery('#egm_group_name_error').addClass('d-none').text('Employee Group Name is required');
        jQuery('#egm_group_name').removeClass('is-invalid');
    }

    function showEgmFieldError(message) {
        jQuery('#egm_group_name_error').text(message || 'Employee Group Name is required').removeClass('d-none');
        jQuery('#egm_group_name').addClass('is-invalid');
    }

    function openEgmModal(mode, data) {
        egmModalMode = mode;
        var isAdd = mode === 'add';

        jQuery('#egmGroupModalLabel').text(isAdd ? 'Add Employee Group' : 'Edit Employee Group');
        jQuery('#egmFormSubmit').text(isAdd ? 'Create Employee Group' : 'Update Employee Group');
        jQuery('#egm_pk').val(isAdd ? '' : (data.pk || ''));
        jQuery('#egm_group_name').val(isAdd ? '' : (data.name || ''));
        clearEgmFieldErrors();
        showEgmModal();

        window.setTimeout(function () {
            jQuery('#egm_group_name').trigger('focus');
        }, 200);
    }

    function extractEncryptedPkFromUrl(url) {
        if (!url) {
            return '';
        }
        var parts = String(url).replace(/\/+$/, '').split('/');
        return parts[parts.length - 1] || '';
    }

    function reloadEgmTable() {
        if (typeof jQuery !== 'undefined' && jQuery.fn.DataTable && jQuery.fn.DataTable.isDataTable(tableSelector)) {
            jQuery(tableSelector).DataTable().ajax.reload(null, false);
        }
    }

    function iconOnlyLink($link, iconClass, extraClass, label) {
        $link.removeClass('btn btn-sm btn-primary btn-success btn-danger');
        $link.addClass('egm-action-btn ' + (extraClass || ''));
        $link.attr('aria-label', label || $link.text().trim());
        $link.empty().append('<i class="bi ' + iconClass + '" aria-hidden="true"></i>');
    }

    function swapEgmHeaders() {
        var $wrapper = jQuery('#employeegroupmaster-table_wrapper');
        if ($wrapper.data('egm-headers-swapped')) {
            return;
        }

        var $ths = $wrapper.find('.dataTables_scrollHead thead tr th');
        if ($ths.length < 4) {
            $ths = jQuery(tableSelector).find('thead tr th');
        }
        if ($ths.length < 4) {
            return;
        }

        $ths.eq(3).insertBefore($ths.eq(2));

        var $firstTh = $wrapper.find('.dataTables_scrollHead thead tr th').first();
        if (!$firstTh.length) {
            $firstTh = jQuery(tableSelector).find('thead tr th').first();
        }
        if ($firstTh.length && $firstTh.text().trim().replace(/\s+/g, '') === 'S.No.') {
            $firstTh.text('S. No.');
        }

        $wrapper.data('egm-headers-swapped', true);
    }

    function swapEgmRowColumns($row) {
        if ($row.hasClass('egm-cols-swapped')) {
            return;
        }
        var $cells = $row.find('td');
        if ($cells.length < 4) {
            return;
        }
        $cells.eq(3).insertBefore($cells.eq(2));
        $row.addClass('egm-cols-swapped');
    }

    function updateEgmStatusBadge($toggle, isActive) {
        var $badge = $toggle.closest('tr').find('.egm-status-badge');
        if (!$badge.length) {
            return;
        }
        $badge
            .removeClass('programme-status-badge--active programme-status-badge--inactive')
            .addClass(isActive ? 'programme-status-badge--active' : 'programme-status-badge--inactive')
            .text(isActive ? 'Active' : 'Inactive');
    }

    function bindEgmEditClicks($editLink, $row) {
        $editLink.off('click.egmEdit').on('click.egmEdit', function (e) {
            e.preventDefault();
            var $cells = $row.find('td');
            openEgmModal('edit', {
                pk: extractEncryptedPkFromUrl(jQuery(this).attr('href')),
                name: $cells.eq(1).text().trim()
            });
        });
    }

    function decorateEgmRows() {
        swapEgmHeaders();

        jQuery(tableSelector + ' tbody tr').each(function () {
            var $row = jQuery(this);
            swapEgmRowColumns($row);

            if ($row.hasClass('egm-row-decorated')) {
                return;
            }

            var $cells = $row.find('td');
            if ($cells.length < 4) {
                return;
            }

            var $statusCell = $cells.eq(2);
            var $actionCell = $cells.eq(3);
            var $toggleWrap = $statusCell.find('.form-check').first();
            var $toggle = $toggleWrap.find('.status-toggle').first();

            if ($toggle.length) {
                var isActive = $toggle.is(':checked');
                var badgeClass = isActive ? 'programme-status-badge--active' : 'programme-status-badge--inactive';
                var label = isActive ? 'Active' : 'Inactive';

                $toggleWrap.detach();
                $statusCell.empty().append(
                    jQuery('<span>', {
                        class: 'badge rounded-pill programme-status-badge egm-status-badge ' + badgeClass,
                        text: label
                    })
                );

                var $editLink = $actionCell.find('a').first().detach();
                var $group = jQuery('<div>', {
                    class: 'egm-group-actions',
                    role: 'group',
                    'aria-label': 'Employee group actions'
                });

                if ($editLink.length) {
                    iconOnlyLink($editLink, 'bi-pencil', 'egm-action-edit', 'Edit employee group');
                    bindEgmEditClicks($editLink, $row);
                    $group.append($editLink);
                }

                $toggleWrap.addClass('egm-action-switch-wrap mb-0');
                $group.append($toggleWrap);
                $actionCell.empty().append($group);
            } else {
                var $editOnly = $actionCell.find('a').first();
                if ($editOnly.length) {
                    iconOnlyLink($editOnly, 'bi-pencil', 'egm-action-edit', 'Edit employee group');
                    bindEgmEditClicks($editOnly, $row);
                }
            }

            $row.addClass('egm-row-decorated');
        });
    }

    function submitEgmForm() {
        var name = jQuery('#egm_group_name').val().trim();
        clearEgmFieldErrors();

        if (!name) {
            showEgmFieldError('Employee Group Name is required');
            jQuery('#egm_group_name').trigger('focus');
            return;
        }

        var payload = {
            _token: csrfToken,
            group_name: name
        };

        if (egmModalMode === 'edit') {
            payload.pk = jQuery('#egm_pk').val();
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
                hideEgmModal();
                reloadEgmTable();

                var message = response.message || (egmModalMode === 'edit'
                    ? 'Employee Group updated successfully.'
                    : 'Employee Group created successfully.');

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: egmModalMode === 'edit' ? 'Updated!' : 'Created!',
                        text: message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                } else if (typeof toastr !== 'undefined') {
                    toastr.success(message);
                }
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    var errors = xhr.responseJSON.errors;
                    if (errors.group_name && errors.group_name[0]) {
                        showEgmFieldError(errors.group_name[0]);
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

    function initEgmPage() {
        if (typeof jQuery === 'undefined' || !jQuery.fn.dataTable) {
            return;
        }

        jQuery(document).on('click', '.egm-open-add-btn', function (e) {
            e.preventDefault();
            openEgmModal('add');
        });

        if (egmModalEl) {
            egmModalEl.addEventListener('hidden.bs.modal', function () {
                clearEgmFieldErrors();
                jQuery('#egm_pk').val('');
                jQuery('#egm_group_name').val('');
            });
        }

        jQuery('#egmFormSubmit').on('click', submitEgmForm);

        jQuery('#egmGroupForm').on('submit', function (e) {
            e.preventDefault();
            submitEgmForm();
        });

        jQuery(tableSelector).on('draw.dt init.dt', function () {
            jQuery('#employeegroupmaster-table_wrapper').data('egm-headers-swapped', false);
            jQuery(tableSelector + ' tbody tr').removeClass('egm-cols-swapped egm-row-decorated');
            decorateEgmRows();
        });

        jQuery(document).on('change', tableSelector + ' .status-toggle', function () {
            var $toggle = jQuery(this);
            window.setTimeout(function () {
                updateEgmStatusBadge($toggle, $toggle.is(':checked'));
            }, 0);
        });

        if (jQuery.fn.DataTable.isDataTable(tableSelector)) {
            decorateEgmRows();
        }

        var params = new URLSearchParams(window.location.search);
        if (params.get('open_egm_modal') === 'add') {
            openEgmModal('add');
            if (window.history && window.history.replaceState) {
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        } else if (params.get('open_egm_modal') === 'edit') {
            var egmName = params.get('egm_name') || '';
            try {
                egmName = decodeURIComponent(egmName.replace(/\+/g, ' '));
            } catch (e) { /* keep raw */ }
            openEgmModal('edit', {
                pk: params.get('egm_pk') || '',
                name: egmName
            });
            if (window.history && window.history.replaceState) {
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initEgmPage);
    } else {
        initEgmPage();
    }
})();
</script>
@endpush
