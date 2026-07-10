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
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="btnEtmColumns"
                        data-bs-toggle="modal" data-bs-target="#etmColumnVisibilityModal"
                        title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="etmDtSearch" class="programme-dt-search" data-dt-search-for="employeetypemaster-table"></div>
                </div>
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

<!-- Column Visibility Modal -->
<div class="modal fade" id="etmColumnVisibilityModal" tabindex="-1" aria-labelledby="etmColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="etmColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="etmColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
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

            // Locate cells by content (not fixed position) so the decoration
            // survives column-visibility toggles that change the cell count.
            var $toggle = $row.find('.status-toggle').first();
            var $toggleWrap = $toggle.closest('.form-check');
            var $editLink = $row.find('a[href*="employee-type/edit"], a[href*="employee_type/edit"]').first();
            if (!$editLink.length) {
                $editLink = $row.find('td:last-child a').first();
            }
            var $statusCell = $toggleWrap.closest('td');
            var $actionCell = $editLink.closest('td');

            if (!$toggle.length && !$editLink.length) {
                return;
            }

            if ($toggle.length && $toggleWrap.length && $statusCell.length) {
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

<script>
/* ---- Per-page relocation of search / pagination / "Showing N of M" ----
   DataTables renders these inside a hidden default-dom row; this moves them out
   of the table wrapper into the visible #etmDtSearch / #etmDtFooter slots (the
   page CSS only reveals them once relocated). Poll-based so it runs regardless
   of init.dt handler timing. */
(function () {
    function updateEtmDtCount() {
        var $ = window.jQuery;
        if (!$ || !$.fn.DataTable || !$.fn.DataTable.isDataTable('#employeetypemaster-table')) {
            return;
        }
        var info = $('#employeetypemaster-table').DataTable().page.info();
        var $info = $('#etmDtFooter .dataTables_info');
        if ($info.length && info && info.recordsDisplay !== undefined) {
            $info.text('of ' + info.recordsDisplay.toLocaleString() + ' items');
        }
    }

    function enhanceEtmDtControls() {
        var $ = window.jQuery;
        var $wrapper = $('#employeetypemaster-table_wrapper');
        if (!$wrapper.length) {
            return;
        }

        var $searchSlot = $('#etmDtSearch');
        var $footer = $('#etmDtFooter');

        if ($searchSlot.length && !$searchSlot.find('.dataTables_filter').length) {
            var $filter = $wrapper.find('.dataTables_filter').first();
            if ($filter.length) {
                $filter.find('input')
                    .addClass('form-control shadow-none')
                    .attr('placeholder', 'Search')
                    .attr('aria-label', 'Search employee types');
                $filter.find('label').contents().filter(function () {
                    return this.nodeType === 3;
                }).remove();
                $searchSlot.append($filter);
            }
        }

        if (!$footer.length) {
            return;
        }
        if ($footer.find('.dataTables_paginate').length || $footer.data('dtReady')) {
            updateEtmDtCount();
            return;
        }

        var $paginate = $wrapper.find('.dataTables_paginate').first();
        var $length = $wrapper.find('.dataTables_length').first();
        var $info = $wrapper.find('.dataTables_info').first();

        var $pagCol = $('<div class="programme-dt-pagination"></div>');
        var $countCol = $('<div class="programme-dt-count d-flex flex-wrap align-items-center gap-2 ms-lg-auto"></div>');

        if ($paginate.length) {
            $paginate.find('.pagination').addClass('mb-0');
            $pagCol.append($paginate);
        }
        if ($length.length) {
            var $select = $length.find('select').addClass('form-select form-select-sm').detach();
            $length.find('label')
                .empty()
                .append(document.createTextNode('Showing '))
                .append($select)
                .append(document.createTextNode(' '));
            $countCol.append($length);
        }
        if ($info.length) {
            $info.addClass('mb-0');
            $countCol.append($info);
        }

        $footer.append($pagCol).append($countCol);
        $footer.data('dtReady', true);
        updateEtmDtCount();
    }

    (function whenReady(tries) {
        var $ = window.jQuery;
        tries = tries || 0;
        if ($ && $.fn.DataTable && $.fn.DataTable.isDataTable('#employeetypemaster-table')) {
            enhanceEtmDtControls();
            $('#employeetypemaster-table').on('draw.dt', function () {
                if (!$('#etmDtFooter .dataTables_paginate').length) {
                    $('#etmDtFooter').data('dtReady', false);
                }
                enhanceEtmDtControls();
            });
        } else if (tries < 100) {
            setTimeout(function () { whenReady(tries + 1); }, 100);
        }
    })(0);
})();
</script>

<script>
/* ---- Column Visibility (drives the live Yajra DataTable via its API) ----
   A Bootstrap modal of checkboxes built from the live table headers, persisted
   to localStorage. We never touch the table's dom/init. */
$(function () {
    var etmColStorageKey = 'employeeTypeMaster:hiddenColumns:v1';

    function etmGetHiddenCols() {
        try {
            var raw = localStorage.getItem(etmColStorageKey);
            var arr = raw ? JSON.parse(raw) : [];
            return Array.isArray(arr) ? arr : [];
        } catch (e) { return []; }
    }
    function etmPersistHiddenCols(arr) {
        try { localStorage.setItem(etmColStorageKey, JSON.stringify(arr)); } catch (e) {}
    }

    function setupEtmColumns(dt) {
        if (!dt) { return; }
        var hidden = etmGetHiddenCols();

        dt.columns().every(function () {
            var idx = this.index();
            this.visible(hidden.indexOf(idx) === -1, false);
        });
        dt.columns.adjust();

        var $grid = $('#etmColumnToggleGrid');
        if (!$grid.length) { return; }
        $grid.empty();

        dt.columns().every(function () {
            var idx = this.index();
            var title = $(this.header()).text().replace(/\s+/g, ' ').trim();
            if (!title) { return; }

            var inputId = 'etmcolvis_' + idx;
            var $cell = $('<div class="col-12 col-sm-6 col-md-4"></div>');
            var $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                .attr('for', inputId);
            var $cb = $('<input type="checkbox" class="form-check-input m-0">')
                .attr('id', inputId)
                .prop('checked', hidden.indexOf(idx) === -1);

            $cb.on('change', function () {
                var h = etmGetHiddenCols();
                var pos = h.indexOf(idx);
                if (this.checked) {
                    if (pos !== -1) h.splice(pos, 1);
                } else {
                    if (pos === -1) h.push(idx);
                }
                etmPersistHiddenCols(h);
                dt.column(idx).visible(this.checked, false);
                dt.columns.adjust();
            });

            $label.append($cb).append($('<span></span>').text(title));
            $cell.append($label);
            $grid.append($cell);
        });
    }

    (function whenReady(tries) {
        tries = tries || 0;
        if ($.fn.DataTable && $.fn.DataTable.isDataTable('#employeetypemaster-table')) {
            setupEtmColumns($('#employeetypemaster-table').DataTable());
        } else if (tries < 100) {
            setTimeout(function () { whenReady(tries + 1); }, 100);
        }
    })();
});
</script>
@endpush
