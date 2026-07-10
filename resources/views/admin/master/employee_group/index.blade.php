@extends('admin.layouts.master')

@section('title', 'Employee Group Master')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/employee-group-master-admin.css') }}?v={{ @filemtime(public_path('css/employee-group-master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid egm-master-page">
    <x-breadcrum title="Employee Group Master">
        <button type="button"
            class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 rounded-2 fw-semibold text-nowrap shadow-sm egm-open-add-btn"
            aria-controls="egmGroupModal">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Add Employee Group</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    <div class="card egm-dt-card shadow-sm rounded-3 overflow-hidden">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center justify-content-end gap-3 mb-4">
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="btnEgmColumns"
                        data-bs-toggle="modal" data-bs-target="#egmColumnVisibilityModal"
                        title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="egmDtSearch" class="programme-dt-search" data-dt-search-for="employeegroupmaster-table"></div>
                </div>
            </div>

            <div class="programme-dt-panel egm-dt-panel">
                <div class="table-responsive egm-dt-scroll">
                    {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table border-0']) !!}
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

<!-- Column Visibility Modal -->
<div class="modal fade" id="egmColumnVisibilityModal" tabindex="-1" aria-labelledby="egmColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="egmColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="egmColumnToggleGrid"></div>
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

            // Locate cells by content (not fixed position) so the decoration
            // survives column-visibility toggles that change the cell count.
            var $toggle = $row.find('.status-toggle').first();
            var $toggleWrap = $toggle.closest('.form-check');
            var $editLink = $row.find('td:last-child a').first();
            if (!$editLink.length) {
                $editLink = $row.find('a[href*="edit"]').first();
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

<script>
/* ---- Per-page relocation of search / pagination / "Showing N of M" ----
   DataTables renders these inside a hidden default-dom row; this moves them out
   of the table wrapper into the visible #egmDtSearch / #egmDtFooter slots (the
   page CSS only reveals them once relocated). Poll-based so it runs regardless
   of init.dt handler timing. */
(function () {
    function updateEgmDtCount() {
        var $ = window.jQuery;
        if (!$ || !$.fn.DataTable || !$.fn.DataTable.isDataTable('#employeegroupmaster-table')) {
            return;
        }
        var info = $('#employeegroupmaster-table').DataTable().page.info();
        var $info = $('#egmDtFooter .dataTables_info');
        if ($info.length && info && info.recordsDisplay !== undefined) {
            $info.text('of ' + info.recordsDisplay.toLocaleString() + ' items');
        }
    }

    function enhanceEgmDtControls() {
        var $ = window.jQuery;
        var $wrapper = $('#employeegroupmaster-table_wrapper');
        if (!$wrapper.length) {
            return;
        }

        var $searchSlot = $('#egmDtSearch');
        var $footer = $('#egmDtFooter');

        if ($searchSlot.length && !$searchSlot.find('.dataTables_filter').length) {
            var $filter = $wrapper.find('.dataTables_filter').first();
            if ($filter.length) {
                $filter.find('input')
                    .addClass('form-control shadow-none')
                    .attr('placeholder', 'Search')
                    .attr('aria-label', 'Search employee groups');
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
            updateEgmDtCount();
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
        updateEgmDtCount();
    }

    (function whenReady(tries) {
        var $ = window.jQuery;
        tries = tries || 0;
        if ($ && $.fn.DataTable && $.fn.DataTable.isDataTable('#employeegroupmaster-table')) {
            enhanceEgmDtControls();
            $('#employeegroupmaster-table').on('draw.dt', function () {
                if (!$('#egmDtFooter .dataTables_paginate').length) {
                    $('#egmDtFooter').data('dtReady', false);
                }
                enhanceEgmDtControls();
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
    var egmColStorageKey = 'employeeGroupMaster:hiddenColumns:v1';

    function egmGetHiddenCols() {
        try {
            var raw = localStorage.getItem(egmColStorageKey);
            var arr = raw ? JSON.parse(raw) : [];
            return Array.isArray(arr) ? arr : [];
        } catch (e) { return []; }
    }
    function egmPersistHiddenCols(arr) {
        try { localStorage.setItem(egmColStorageKey, JSON.stringify(arr)); } catch (e) {}
    }

    function setupEgmColumns(dt) {
        if (!dt) { return; }
        var hidden = egmGetHiddenCols();

        dt.columns().every(function () {
            var idx = this.index();
            this.visible(hidden.indexOf(idx) === -1, false);
        });
        dt.columns.adjust();

        var $grid = $('#egmColumnToggleGrid');
        if (!$grid.length) { return; }
        $grid.empty();

        dt.columns().every(function () {
            var idx = this.index();
            var title = $(this.header()).text().replace(/\s+/g, ' ').trim();
            if (!title) { return; }

            var inputId = 'egmcolvis_' + idx;
            var $cell = $('<div class="col-12 col-sm-6 col-md-4"></div>');
            var $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                .attr('for', inputId);
            var $cb = $('<input type="checkbox" class="form-check-input m-0">')
                .attr('id', inputId)
                .prop('checked', hidden.indexOf(idx) === -1);

            $cb.on('change', function () {
                var h = egmGetHiddenCols();
                var pos = h.indexOf(idx);
                if (this.checked) {
                    if (pos !== -1) h.splice(pos, 1);
                } else {
                    if (pos === -1) h.push(idx);
                }
                egmPersistHiddenCols(h);
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
        if ($.fn.DataTable && $.fn.DataTable.isDataTable('#employeegroupmaster-table')) {
            setupEgmColumns($('#employeegroupmaster-table').DataTable());
        } else if (tries < 100) {
            setTimeout(function () { whenReady(tries + 1); }, 100);
        }
    })();
});
</script>
@endpush
