@extends('admin.layouts.master')

@section('title', 'Department Master')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/department-master-admin.css') }}?v={{ @filemtime(public_path('css/department-master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid">
<x-breadcrum title="Department Master"></x-breadcrum>
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" >
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>Department Master</h4>
                        </div>
                        <div class="col-6">
                            <div class="float-end gap-2">
                                <a href="{{route('master.department.master.create')}}" class="btn btn-primary">+ Add Department</a>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="table-responsive">
                        {{ $dataTable->table(['class' => 'table']) }}
                    </div>
                </div>
                <div id="dpmDtFooter"
                    class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                    data-dt-footer-for="departmentmaster-table"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade dpm-dept-modal" id="dpmDeptModal" tabindex="-1" aria-labelledby="dpmDeptModalLabel" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered dpm-dept-modal-dialog">
        <div class="modal-content cgt-form-modal dpm-dept-modal-content border-0 shadow-lg">
            <div class="modal-header">
                <h5 class="modal-title mb-0" id="dpmDeptModalLabel">Add Department</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="dpmDeptForm" class="dpm-dept-modal-form" novalidate>
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="pk" id="dpm_pk" value="">

                    <label for="dpm_department_name" class="form-label cgt-field-label mb-2">
                        Department Name <span class="text-danger">*</span>
                    </label>
                    <input type="text"
                        name="department_name"
                        id="dpm_department_name"
                        class="form-control rounded-2"
                        placeholder="eg. General Medicine"
                        autocomplete="off">
                    <small class="text-danger d-none mt-1" id="dpm_department_name_error">
                        Department Name is required
                    </small>
                </form>
            </div>
            <div class="modal-footer gap-2 justify-content-end">
                <button type="button" class="btn btn-outline-primary rounded-2 px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary rounded-2 px-4" id="dpmFormSubmit">Create Department</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{!! $dataTable->scripts() !!}
<script>
(function () {
    var tableSelector = '#departmentmaster-table';
    var storeUrl = "{{ route('master.department.master.store') }}";
    var csrfToken = "{{ csrf_token() }}";
    var dpmModalMode = 'add';

    var dpmModalEl = document.getElementById('dpmDeptModal');
    if (dpmModalEl && dpmModalEl.parentElement && dpmModalEl.parentElement !== document.body) {
        document.body.appendChild(dpmModalEl);
    }

    function showDpmModal() {
        if (!dpmModalEl) {
            return;
        }
        if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(dpmModalEl).show();
        } else if (window.jQuery) {
            jQuery(dpmModalEl).modal('show');
        }
    }

    function hideDpmModal() {
        if (!dpmModalEl) {
            return;
        }
        if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(dpmModalEl).hide();
        } else if (window.jQuery) {
            jQuery(dpmModalEl).modal('hide');
        }
    }

    function clearDpmFieldErrors() {
        jQuery('#dpm_department_name_error').addClass('d-none').text('Department Name is required');
        jQuery('#dpm_department_name').removeClass('is-invalid');
    }

    function showDpmFieldError(message) {
        jQuery('#dpm_department_name_error').text(message || 'Department Name is required').removeClass('d-none');
        jQuery('#dpm_department_name').addClass('is-invalid');
    }

    function openDpmModal(mode, data) {
        dpmModalMode = mode;
        var isAdd = mode === 'add';

        jQuery('#dpmDeptModalLabel').text(isAdd ? 'Add Department' : 'Edit Department');
        jQuery('#dpmFormSubmit').text(isAdd ? 'Create Department' : 'Update Department');
        jQuery('#dpm_pk').val(isAdd ? '' : (data.pk || ''));
        jQuery('#dpm_department_name').val(isAdd ? '' : (data.name || ''));
        clearDpmFieldErrors();
        showDpmModal();

        window.setTimeout(function () {
            jQuery('#dpm_department_name').trigger('focus');
        }, 200);
    }

    function extractEncryptedPkFromUrl(url) {
        if (!url) {
            return '';
        }
        var parts = String(url).replace(/\/+$/, '').split('/');
        return parts[parts.length - 1] || '';
    }

    function reloadDpmTable() {
        if (typeof jQuery !== 'undefined' && jQuery.fn.DataTable && jQuery.fn.DataTable.isDataTable(tableSelector)) {
            jQuery(tableSelector).DataTable().ajax.reload(null, false);
        }
    }

    function iconOnlyLink($link, iconClass, extraClass, label) {
        $link.removeClass('btn btn-sm btn-primary btn-success btn-danger');
        $link.addClass('dpm-action-btn ' + (extraClass || ''));
        $link.attr('aria-label', label || $link.text().trim());
        $link.empty().append('<i class="bi ' + iconClass + '" aria-hidden="true"></i>');
    }

    function swapDpmHeaders() {
        var $wrapper = jQuery('#departmentmaster-table_wrapper');
        if ($wrapper.data('dpm-headers-swapped')) {
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

        $wrapper.data('dpm-headers-swapped', true);
    }

    function swapDpmRowColumns($row) {
        if ($row.hasClass('dpm-cols-swapped')) {
            return;
        }
        var $cells = $row.find('td');
        if ($cells.length < 4) {
            return;
        }
        $cells.eq(3).insertBefore($cells.eq(2));
        $row.addClass('dpm-cols-swapped');
    }

    function updateDpmStatusBadge($toggle, isActive) {
        var $badge = $toggle.closest('tr').find('.dpm-status-badge');
        if (!$badge.length) {
            return;
        }
        $badge
            .removeClass('programme-status-badge--active programme-status-badge--inactive')
            .addClass(isActive ? 'programme-status-badge--active' : 'programme-status-badge--inactive')
            .text(isActive ? 'Active' : 'Inactive');
    }

    function openDpmEditFromRow($link) {
        var $row = $link.closest('tr');
        var name = $row.find('td').eq(1).text().trim();
        openDpmModal('edit', {
            pk: extractEncryptedPkFromUrl($link.attr('href')),
            name: name
        });
    }

    function decorateDpmRows() {
        swapDpmHeaders();

        jQuery(tableSelector + ' tbody tr').each(function () {
            var $row = jQuery(this);
            swapDpmRowColumns($row);

            if ($row.hasClass('dpm-row-decorated')) {
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
                        class: 'badge rounded-pill programme-status-badge dpm-status-badge ' + badgeClass,
                        text: label
                    })
                );

                var $editLink = $actionCell.find('a').first().detach();
                var $group = jQuery('<div>', {
                    class: 'dpm-dept-actions',
                    role: 'group',
                    'aria-label': 'Department actions'
                });

                if ($editLink.length) {
                    iconOnlyLink($editLink, 'bi-pencil', 'dpm-action-edit', 'Edit department');
                    $group.append($editLink);
                }

                $toggleWrap.addClass('dpm-action-switch-wrap mb-0');
                $group.append($toggleWrap);
                $actionCell.empty().append($group);
            } else {
                var $editOnly = $actionCell.find('a').first();
                if ($editOnly.length) {
                    iconOnlyLink($editOnly, 'bi-pencil', 'dpm-action-edit', 'Edit department');
                }
            }

            $row.addClass('dpm-row-decorated');
        });
    }

    function submitDpmForm() {
        var name = jQuery('#dpm_department_name').val().trim();
        clearDpmFieldErrors();

        if (!name) {
            showDpmFieldError('Department Name is required');
            jQuery('#dpm_department_name').trigger('focus');
            return;
        }

        var payload = {
            _token: csrfToken,
            department_name: name
        };

        if (dpmModalMode === 'edit') {
            payload.pk = jQuery('#dpm_pk').val();
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
                hideDpmModal();
                reloadDpmTable();

                var message = response.message || (dpmModalMode === 'edit'
                    ? 'Department updated successfully.'
                    : 'Department created successfully.');

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: dpmModalMode === 'edit' ? 'Updated!' : 'Created!',
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
                    if (errors.department_name && errors.department_name[0]) {
                        showDpmFieldError(errors.department_name[0]);
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

    function initDpmPage() {
        if (typeof jQuery === 'undefined' || !jQuery.fn.dataTable) {
            return;
        }

        jQuery(document).on('click', '.dpm-open-add-btn', function (e) {
            e.preventDefault();
            openDpmModal('add');
        });

        jQuery(document).on('click', tableSelector + ' tbody a[href*="department-master/edit"]', function (e) {
            e.preventDefault();
            openDpmEditFromRow(jQuery(this));
        });

        if (dpmModalEl) {
            dpmModalEl.addEventListener('hidden.bs.modal', function () {
                clearDpmFieldErrors();
                jQuery('#dpm_pk').val('');
                jQuery('#dpm_department_name').val('');
            });
        }

        jQuery('#dpmFormSubmit').on('click', submitDpmForm);

        jQuery('#dpmDeptForm').on('submit', function (e) {
            e.preventDefault();
            submitDpmForm();
        });

        jQuery(tableSelector).on('draw.dt init.dt', function () {
            jQuery('#departmentmaster-table_wrapper').data('dpm-headers-swapped', false);
            jQuery(tableSelector + ' tbody tr').removeClass('dpm-cols-swapped dpm-row-decorated');
            decorateDpmRows();
        });

        jQuery(document).on('change', tableSelector + ' .status-toggle', function () {
            var $toggle = jQuery(this);
            window.setTimeout(function () {
                updateDpmStatusBadge($toggle, $toggle.is(':checked'));
            }, 0);
        });

        if (jQuery.fn.DataTable.isDataTable(tableSelector)) {
            decorateDpmRows();
        }

        var params = new URLSearchParams(window.location.search);
        if (params.get('open_dpm_modal') === 'add') {
            openDpmModal('add');
            if (window.history && window.history.replaceState) {
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        } else if (params.get('open_dpm_modal') === 'edit') {
            var dpmName = params.get('dpm_name') || '';
            try {
                dpmName = decodeURIComponent(dpmName.replace(/\+/g, ' '));
            } catch (e) { /* keep raw */ }
            openDpmModal('edit', {
                pk: params.get('dpm_pk') || '',
                name: dpmName
            });
            if (window.history && window.history.replaceState) {
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDpmPage);
    } else {
        initDpmPage();
    }
})();
</script>
@endpush
