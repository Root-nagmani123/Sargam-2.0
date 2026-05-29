@extends('admin.layouts.master')

@section('title', 'Employee Type Master')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/employee-type-master-admin.css') }}?v={{ @filemtime(public_path('css/employee-type-master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid etm-master-page">
    <x-breadcrum title="Employee Type Master">
        <button type="button"
            class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 rounded-2 fw-semibold text-nowrap shadow-sm etm-open-add-btn"
            aria-controls="etmTypeModal">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Add Employee Type</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    <div class="card etm-dt-card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center justify-content-end gap-3 mb-4">
                <div id="etmDtSearch" class="programme-dt-search ms-lg-auto" data-dt-search-for="employeetypemaster-table"></div>
            </div>

            <div class="programme-dt-panel etm-dt-panel">
                <div class="table-responsive etm-dt-scroll">
                    {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table']) !!}
                </div>
                <div id="etmDtFooter"
                    class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                    data-dt-footer-for="employeetypemaster-table"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade etm-type-modal" id="etmTypeModal" tabindex="-1" aria-labelledby="etmTypeModalLabel" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered etm-type-modal-dialog">
        <div class="modal-content cgt-form-modal etm-type-modal-content border-0 shadow-lg">
            <div class="modal-header">
                <h5 class="modal-title mb-0" id="etmTypeModalLabel">Add Employee Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="etmTypeForm" class="etm-type-modal-form" novalidate>
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="pk" id="etm_pk" value="">

                    <label for="etm_employee_type_name" class="form-label cgt-field-label mb-2">
                        Employee Type Name <span class="text-danger">*</span>
                    </label>
                    <input type="text"
                        name="employee_type_name"
                        id="etm_employee_type_name"
                        class="form-control rounded-3"
                        placeholder="eg. General Medicine"
                        autocomplete="off">
                    <small class="text-danger d-none mt-1" id="etm_employee_type_name_error">
                        Employee Type Name is required
                    </small>
                </form>
            </div>
            <div class="modal-footer gap-2 justify-content-end">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary rounded-3 px-4" id="etmFormSubmit">Create Employee Type</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{!! $dataTable->scripts() !!}
<script>
(function () {
    var tableSelector = '#employeetypemaster-table';
    var storeUrl = "{{ route('master.employee.type.store') }}";
    var csrfToken = "{{ csrf_token() }}";
    var etmModalMode = 'add';

    var etmModalEl = document.getElementById('etmTypeModal');
    if (etmModalEl && etmModalEl.parentElement && etmModalEl.parentElement !== document.body) {
        document.body.appendChild(etmModalEl);
    }

    function showEtmModal() {
        if (!etmModalEl) {
            return;
        }
        if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(etmModalEl).show();
        } else if (window.jQuery) {
            jQuery(etmModalEl).modal('show');
        }
    }

    function hideEtmModal() {
        if (!etmModalEl) {
            return;
        }
        if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(etmModalEl).hide();
        } else if (window.jQuery) {
            jQuery(etmModalEl).modal('hide');
        }
    }

    function clearEtmFieldErrors() {
        jQuery('#etm_employee_type_name_error').addClass('d-none').text('Employee Type Name is required');
        jQuery('#etm_employee_type_name').removeClass('is-invalid');
    }

    function showEtmFieldError(message) {
        jQuery('#etm_employee_type_name_error').text(message || 'Employee Type Name is required').removeClass('d-none');
        jQuery('#etm_employee_type_name').addClass('is-invalid');
    }

    function openEtmModal(mode, data) {
        etmModalMode = mode;
        var isAdd = mode === 'add';

        jQuery('#etmTypeModalLabel').text(isAdd ? 'Add Employee Type' : 'Edit Employee Type');
        jQuery('#etmFormSubmit').text(isAdd ? 'Create Employee Type' : 'Update Employee Type');
        jQuery('#etm_pk').val(isAdd ? '' : (data.pk || ''));
        jQuery('#etm_employee_type_name').val(isAdd ? '' : (data.name || ''));
        clearEtmFieldErrors();
        showEtmModal();

        window.setTimeout(function () {
            jQuery('#etm_employee_type_name').trigger('focus');
        }, 200);
    }

    function extractEncryptedPkFromUrl(url) {
        if (!url) {
            return '';
        }
        var parts = String(url).replace(/\/+$/, '').split('/');
        return parts[parts.length - 1] || '';
    }

    function reloadEtmTable() {
        if (typeof jQuery !== 'undefined' && jQuery.fn.DataTable && jQuery.fn.DataTable.isDataTable(tableSelector)) {
            jQuery(tableSelector).DataTable().ajax.reload(null, false);
        }
    }

    function iconOnlyLink($link, iconClass, extraClass, label) {
        $link.removeClass('btn btn-sm btn-primary btn-success btn-danger');
        $link.addClass('etm-action-btn ' + (extraClass || ''));
        $link.attr('aria-label', label || $link.text().trim());
        $link.empty().append('<i class="bi ' + iconClass + '" aria-hidden="true"></i>');
    }

    function swapEtmHeaders() {
        var $wrapper = jQuery('#employeetypemaster-table_wrapper');
        if ($wrapper.data('etm-headers-swapped')) {
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
        $wrapper.data('etm-headers-swapped', true);
    }

    function swapEtmRowColumns($row) {
        if ($row.hasClass('etm-cols-swapped')) {
            return;
        }
        var $cells = $row.find('td');
        if ($cells.length < 4) {
            return;
        }
        $cells.eq(3).insertBefore($cells.eq(2));
        $row.addClass('etm-cols-swapped');
    }

    function updateEtmStatusBadge($toggle, isActive) {
        var $badge = $toggle.closest('tr').find('.etm-status-badge');
        if (!$badge.length) {
            return;
        }
        $badge
            .removeClass('programme-status-badge--active programme-status-badge--inactive')
            .addClass(isActive ? 'programme-status-badge--active' : 'programme-status-badge--inactive')
            .text(isActive ? 'Active' : 'Inactive');
    }

    function bindEtmEditClicks($editLink, $row) {
        $editLink.off('click.etmEdit').on('click.etmEdit', function (e) {
            e.preventDefault();
            var $cells = $row.find('td');
            openEtmModal('edit', {
                pk: extractEncryptedPkFromUrl(jQuery(this).attr('href')),
                name: $cells.eq(1).text().trim()
            });
        });
    }

    function decorateEtmRows() {
        swapEtmHeaders();

        jQuery(tableSelector + ' tbody tr').each(function () {
            var $row = jQuery(this);
            swapEtmRowColumns($row);

            if ($row.hasClass('etm-row-decorated')) {
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
                        class: 'badge rounded-pill programme-status-badge etm-status-badge ' + badgeClass,
                        text: label
                    })
                );

                var $editLink = $actionCell.find('a').first().detach();
                var $group = jQuery('<div>', {
                    class: 'etm-type-actions',
                    role: 'group',
                    'aria-label': 'Employee type actions'
                });

                if ($editLink.length) {
                    iconOnlyLink($editLink, 'bi-pencil', 'etm-action-edit', 'Edit employee type');
                    bindEtmEditClicks($editLink, $row);
                    $group.append($editLink);
                }

                $toggleWrap.addClass('etm-action-switch-wrap mb-0');
                $group.append($toggleWrap);
                $actionCell.empty().append($group);
            } else {
                var $editOnly = $actionCell.find('a').first();
                if ($editOnly.length) {
                    iconOnlyLink($editOnly, 'bi-pencil', 'etm-action-edit', 'Edit employee type');
                    bindEtmEditClicks($editOnly, $row);
                }
            }

            $row.addClass('etm-row-decorated');
        });
    }

    function submitEtmForm() {
        var name = jQuery('#etm_employee_type_name').val().trim();
        clearEtmFieldErrors();

        if (!name) {
            showEtmFieldError('Employee Type Name is required');
            jQuery('#etm_employee_type_name').trigger('focus');
            return;
        }

        var payload = {
            _token: csrfToken,
            employee_type_name: name
        };

        if (etmModalMode === 'edit') {
            payload.pk = jQuery('#etm_pk').val();
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
                hideEtmModal();
                reloadEtmTable();

                var message = response.message || (etmModalMode === 'edit'
                    ? 'Employee Type updated successfully.'
                    : 'Employee Type created successfully.');

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: etmModalMode === 'edit' ? 'Updated!' : 'Created!',
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
                    if (errors.employee_type_name && errors.employee_type_name[0]) {
                        showEtmFieldError(errors.employee_type_name[0]);
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

    function initEtmPage() {
        if (typeof jQuery === 'undefined' || !jQuery.fn.dataTable) {
            return;
        }

        jQuery(document).on('click', '.etm-open-add-btn', function (e) {
            e.preventDefault();
            openEtmModal('add');
        });

        if (etmModalEl) {
            etmModalEl.addEventListener('hidden.bs.modal', function () {
                clearEtmFieldErrors();
                jQuery('#etm_pk').val('');
                jQuery('#etm_employee_type_name').val('');
            });
        }

        jQuery('#etmFormSubmit').on('click', submitEtmForm);

        jQuery('#etmTypeForm').on('submit', function (e) {
            e.preventDefault();
            submitEtmForm();
        });

        jQuery(tableSelector).on('draw.dt init.dt', function () {
            jQuery('#employeetypemaster-table_wrapper').data('etm-headers-swapped', false);
            jQuery(tableSelector + ' tbody tr').removeClass('etm-cols-swapped etm-row-decorated');
            decorateEtmRows();
        });

        jQuery(document).on('change', tableSelector + ' .status-toggle', function () {
            var $toggle = jQuery(this);
            window.setTimeout(function () {
                updateEtmStatusBadge($toggle, $toggle.is(':checked'));
            }, 0);
        });

        if (jQuery.fn.DataTable.isDataTable(tableSelector)) {
            decorateEtmRows();
        }

        var params = new URLSearchParams(window.location.search);
        if (params.get('open_etm_modal') === 'add') {
            openEtmModal('add');
            if (window.history && window.history.replaceState) {
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        } else if (params.get('open_etm_modal') === 'edit') {
            var etmName = params.get('etm_name') || '';
            try {
                etmName = decodeURIComponent(etmName.replace(/\+/g, ' '));
            } catch (e) { /* keep raw */ }
            openEtmModal('edit', {
                pk: params.get('etm_pk') || '',
                name: etmName
            });
            if (window.history && window.history.replaceState) {
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initEtmPage);
    } else {
        initEtmPage();
    }
})();
</script>
@endpush
