@extends('admin.layouts.master')

@section('title', 'Designation Master')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/designation-master-admin.css') }}?v={{ @filemtime(public_path('css/designation-master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid dsn-master-page">
    <x-breadcrum title="Designation Master">
        <button type="button"
            class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 rounded-2 fw-semibold text-nowrap shadow-sm dsn-open-add-btn"
            aria-controls="dsnDesignationModal">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Add Designation</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    <div class="card dsn-dt-card shadow-sm rounded-3 overflow-hidden">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center justify-content-end gap-3 mb-4">
                <div id="dsnDtSearch" class="programme-dt-search ms-lg-auto" data-dt-search-for="departmentmaster-table"></div>
            </div>

            <div class="programme-dt-panel dsn-dt-panel">
                <div class="table-responsive dsn-dt-scroll">
                    {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table border-0']) !!}
                </div>
                <div id="dsnDtFooter"
                    class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                    data-dt-footer-for="departmentmaster-table"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade dsn-designation-modal" id="dsnDesignationModal" tabindex="-1" aria-labelledby="dsnDesignationModalLabel" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered dsn-designation-modal-dialog">
        <div class="modal-content cgt-form-modal border-0 shadow-lg">
            <div class="modal-header">
                <h5 class="modal-title mb-0" id="dsnDesignationModalLabel">Add Designation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="dsnDesignationForm" class="dsn-designation-modal-form" novalidate>
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="pk" id="dsn_pk" value="">

                    <label for="dsn_designation_name" class="form-label cgt-field-label mb-2">
                        Designation Name <span class="text-danger">*</span>
                    </label>
                    <input type="text"
                        name="designation_name"
                        id="dsn_designation_name"
                        class="form-control rounded-2"
                        placeholder="eg. General Manager"
                        autocomplete="off">
                    <small class="text-danger d-none mt-1" id="dsn_designation_name_error">
                        Designation Name is required
                    </small>
                </form>
            </div>
            <div class="modal-footer gap-2 justify-content-end">
                <button type="button" class="btn btn-outline-primary rounded-2 px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary rounded-2 px-4" id="dsnFormSubmit">Create Designation</button>
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
    var storeUrl = "{{ route('master.designation.store') }}";
    var csrfToken = "{{ csrf_token() }}";
    var dsnModalMode = 'add';

    var dsnModalEl = document.getElementById('dsnDesignationModal');
    if (dsnModalEl && dsnModalEl.parentElement && dsnModalEl.parentElement !== document.body) {
        document.body.appendChild(dsnModalEl);
    }

    function showDsnModal() {
        if (!dsnModalEl) {
            return;
        }
        if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(dsnModalEl).show();
        } else if (window.jQuery) {
            jQuery(dsnModalEl).modal('show');
        }
    }

    function hideDsnModal() {
        if (!dsnModalEl) {
            return;
        }
        if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(dsnModalEl).hide();
        } else if (window.jQuery) {
            jQuery(dsnModalEl).modal('hide');
        }
    }

    function clearDsnFieldErrors() {
        jQuery('#dsn_designation_name_error').addClass('d-none').text('Designation Name is required');
        jQuery('#dsn_designation_name').removeClass('is-invalid');
    }

    function showDsnFieldError(message) {
        jQuery('#dsn_designation_name_error').text(message || 'Designation Name is required').removeClass('d-none');
        jQuery('#dsn_designation_name').addClass('is-invalid');
    }

    function openDsnModal(mode, data) {
        dsnModalMode = mode;
        var isAdd = mode === 'add';

        jQuery('#dsnDesignationModalLabel').text(isAdd ? 'Add Designation' : 'Edit Designation');
        jQuery('#dsnFormSubmit').text(isAdd ? 'Create Designation' : 'Update Designation');
        jQuery('#dsn_pk').val(isAdd ? '' : (data.pk || ''));
        jQuery('#dsn_designation_name').val(isAdd ? '' : (data.name || ''));
        clearDsnFieldErrors();
        showDsnModal();

        window.setTimeout(function () {
            jQuery('#dsn_designation_name').trigger('focus');
        }, 200);
    }

    function extractEncryptedPkFromUrl(url) {
        if (!url) {
            return '';
        }
        var parts = String(url).replace(/\/+$/, '').split('/');
        return parts[parts.length - 1] || '';
    }

    function reloadDsnTable() {
        if (typeof jQuery !== 'undefined' && jQuery.fn.DataTable && jQuery.fn.DataTable.isDataTable(tableSelector)) {
            jQuery(tableSelector).DataTable().ajax.reload(null, false);
        }
    }

    function iconOnlyLink($link, iconClass, extraClass, label) {
        $link.removeClass('btn btn-sm btn-primary btn-success btn-danger');
        $link.addClass('dsn-action-btn ' + (extraClass || ''));
        $link.attr('aria-label', label || $link.text().trim());
        $link.empty().append('<i class="bi ' + iconClass + '" aria-hidden="true"></i>');
    }

    function swapDsnHeaders() {
        var $wrapper = jQuery('#departmentmaster-table_wrapper');
        if ($wrapper.data('dsn-headers-swapped')) {
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

        $wrapper.data('dsn-headers-swapped', true);
    }

    function swapDsnRowColumns($row) {
        if ($row.hasClass('dsn-cols-swapped')) {
            return;
        }
        var $cells = $row.find('td');
        if ($cells.length < 4) {
            return;
        }
        $cells.eq(3).insertBefore($cells.eq(2));
        $row.addClass('dsn-cols-swapped');
    }

    function updateDsnStatusBadge($toggle, isActive) {
        var $badge = $toggle.closest('tr').find('.dsn-status-badge');
        if (!$badge.length) {
            return;
        }
        $badge
            .removeClass('programme-status-badge--active programme-status-badge--inactive')
            .addClass(isActive ? 'programme-status-badge--active' : 'programme-status-badge--inactive')
            .text(isActive ? 'Active' : 'Inactive');
    }

    function openDsnEditFromRow($link) {
        var $row = $link.closest('tr');
        var name = $row.find('td').eq(1).text().trim();
        openDsnModal('edit', {
            pk: extractEncryptedPkFromUrl($link.attr('href')),
            name: name
        });
    }

    function decorateDsnRows() {
        swapDsnHeaders();

        jQuery(tableSelector + ' tbody tr').each(function () {
            var $row = jQuery(this);
            swapDsnRowColumns($row);

            if ($row.hasClass('dsn-row-decorated')) {
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
                        class: 'badge rounded-pill programme-status-badge dsn-status-badge ' + badgeClass,
                        text: label
                    })
                );

                var $editLink = $actionCell.find('a').first().detach();
                var $group = jQuery('<div>', {
                    class: 'dsn-designation-actions',
                    role: 'group',
                    'aria-label': 'Designation actions'
                });

                if ($editLink.length) {
                    iconOnlyLink($editLink, 'bi-pencil', 'dsn-action-edit', 'Edit designation');
                    $group.append($editLink);
                }

                $toggleWrap.addClass('dsn-action-switch-wrap mb-0');
                $group.append($toggleWrap);
                $actionCell.empty().append($group);
            } else {
                var $editOnly = $actionCell.find('a').first();
                if ($editOnly.length) {
                    iconOnlyLink($editOnly, 'bi-pencil', 'dsn-action-edit', 'Edit designation');
                }
            }

            $row.addClass('dsn-row-decorated');
        });
    }

    function submitDsnForm() {
        var name = jQuery('#dsn_designation_name').val().trim();
        clearDsnFieldErrors();

        if (!name) {
            showDsnFieldError('Designation Name is required');
            jQuery('#dsn_designation_name').trigger('focus');
            return;
        }

        var payload = {
            _token: csrfToken,
            designation_name: name
        };

        if (dsnModalMode === 'edit') {
            payload.pk = jQuery('#dsn_pk').val();
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
                hideDsnModal();
                reloadDsnTable();

                var message = response.message || (dsnModalMode === 'edit'
                    ? 'Designation updated successfully.'
                    : 'Designation created successfully.');

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: dsnModalMode === 'edit' ? 'Updated!' : 'Created!',
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
                    if (errors.designation_name && errors.designation_name[0]) {
                        showDsnFieldError(errors.designation_name[0]);
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

    var dsnModalBound = false;
    var dsnTableBound = false;
    var dsnInitAttempts = 0;

    function bindDsnModalHandlers() {
        if (dsnModalBound || typeof jQuery === 'undefined') {
            return;
        }
        dsnModalBound = true;

        jQuery(document).on('click', '.dsn-open-add-btn', function (e) {
            e.preventDefault();
            openDsnModal('add');
        });

        jQuery(document).on('click', tableSelector + ' tbody a[href*="designation/edit"]', function (e) {
            e.preventDefault();
            openDsnEditFromRow(jQuery(this));
        });

        if (dsnModalEl) {
            dsnModalEl.addEventListener('hidden.bs.modal', function () {
                clearDsnFieldErrors();
                jQuery('#dsn_pk').val('');
                jQuery('#dsn_designation_name').val('');
            });
        }

        jQuery('#dsnFormSubmit').on('click', submitDsnForm);
        jQuery('#dsnDesignationForm').on('submit', function (e) {
            e.preventDefault();
            submitDsnForm();
        });

        var params = new URLSearchParams(window.location.search);
        if (params.get('open_dsn_modal') === 'add') {
            openDsnModal('add');
            if (window.history && window.history.replaceState) {
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        } else if (params.get('open_dsn_modal') === 'edit') {
            var dsnName = params.get('dsn_name') || '';
            try {
                dsnName = decodeURIComponent(dsnName.replace(/\+/g, ' '));
            } catch (e) { /* keep raw */ }
            openDsnModal('edit', {
                pk: params.get('dsn_pk') || '',
                name: dsnName
            });
            if (window.history && window.history.replaceState) {
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        }
    }

    function bindDsnTableHandlers() {
        if (dsnTableBound || typeof jQuery === 'undefined' || !jQuery.fn.dataTable) {
            return;
        }
        dsnTableBound = true;

        jQuery(tableSelector).on('draw.dt init.dt', function () {
            jQuery('#departmentmaster-table_wrapper').data('dsn-headers-swapped', false);
            jQuery(tableSelector + ' tbody tr').removeClass('dsn-cols-swapped dsn-row-decorated');
            decorateDsnRows();
        });

        jQuery(document).on('change', tableSelector + ' .status-toggle', function () {
            var $toggle = jQuery(this);
            window.setTimeout(function () {
                updateDsnStatusBadge($toggle, $toggle.is(':checked'));
            }, 0);
        });

        if (jQuery.fn.DataTable.isDataTable(tableSelector)) {
            decorateDsnRows();
        }
    }

    function initDsnPage() {
        bindDsnModalHandlers();
        bindDsnTableHandlers();

        if (!dsnTableBound && typeof jQuery !== 'undefined' && dsnInitAttempts < 50) {
            dsnInitAttempts += 1;
            window.setTimeout(initDsnPage, 100);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDsnPage);
    } else {
        initDsnPage();
    }
})();
</script>
@endpush
