@extends('admin.layouts.master')

@section('title', 'Department Master')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/department-master-admin.css') }}?v={{ @filemtime(public_path('css/department-master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid dpm-master-page">
    <x-breadcrum title="Department Master">
        <button type="button"
            class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 rounded-2 fw-semibold text-nowrap shadow-sm dpm-open-add-btn"
            aria-controls="dpmDeptModal">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Add Department</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    <div class="card dpm-dt-card shadow-sm rounded-3 overflow-hidden">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center justify-content-end gap-3 mb-4">
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="btnDpmColumns"
                        data-bs-toggle="modal" data-bs-target="#dpmColumnVisibilityModal"
                        title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="dpmDtSearch" class="programme-dt-search" data-dt-search-for="departmentmaster-table"></div>
                </div>
            </div>

            <div class="programme-dt-panel dpm-dt-panel">
                <div class="table-responsive dpm-dt-scroll">
                    {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table border-0']) !!}
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

<!-- Column Visibility Modal -->
<div class="modal fade" id="dpmColumnVisibilityModal" tabindex="-1" aria-labelledby="dpmColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="dpmColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="dpmColumnToggleGrid"></div>
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

            // Locate cells by content (not fixed position) so the decoration
            // survives column-visibility toggles that change the cell count.
            var $toggle = $row.find('.status-toggle').first();
            var $toggleWrap = $toggle.closest('.form-check');
            var $editLink = $row.find('a[href*="department-master/edit"]').first();
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

<script>
/* ---- Per-page relocation of search / pagination / "Showing N of M" ----
   Belt-and-suspenders copy of the global enhancer (same pattern as the
   programme index). DataTables renders these controls inside a hidden
   default-dom row; this moves them out of the table wrapper into the visible
   #dpmDtSearch / #dpmDtFooter slots. Poll-based so it runs regardless of
   init.dt handler timing. */
(function () {
    function updateDpmDtCount() {
        var $ = window.jQuery;
        if (!$ || !$.fn.DataTable || !$.fn.DataTable.isDataTable('#departmentmaster-table')) {
            return;
        }
        var info = $('#departmentmaster-table').DataTable().page.info();
        var $info = $('#dpmDtFooter .dataTables_info');
        if ($info.length && info && info.recordsDisplay !== undefined) {
            $info.text('of ' + info.recordsDisplay.toLocaleString() + ' items');
        }
    }

    function enhanceDpmDtControls() {
        var $ = window.jQuery;
        var $wrapper = $('#departmentmaster-table_wrapper');
        if (!$wrapper.length) {
            return;
        }

        var $searchSlot = $('#dpmDtSearch');
        var $footer = $('#dpmDtFooter');

        /* Search → toolbar right */
        if ($searchSlot.length && !$searchSlot.find('.dataTables_filter').length) {
            var $filter = $wrapper.find('.dataTables_filter').first();
            if ($filter.length) {
                $filter.find('input')
                    .addClass('form-control shadow-none')
                    .attr('placeholder', 'Search')
                    .attr('aria-label', 'Search departments');
                $filter.find('label').contents().filter(function () {
                    return this.nodeType === 3;
                }).remove();
                $searchSlot.append($filter);
            }
        }

        if (!$footer.length) {
            return;
        }

        /* Footer already populated (by this or the global enhancer) → just refresh count */
        if ($footer.find('.dataTables_paginate').length || $footer.data('dtReady')) {
            updateDpmDtCount();
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
        updateDpmDtCount();
    }

    (function whenReady(tries) {
        var $ = window.jQuery;
        tries = tries || 0;
        if ($ && $.fn.DataTable && $.fn.DataTable.isDataTable('#departmentmaster-table')) {
            enhanceDpmDtControls();
            $('#departmentmaster-table').on('draw.dt', function () {
                // On ajax reload the pagination may be re-rendered inside the wrapper.
                if (!$('#dpmDtFooter .dataTables_paginate').length) {
                    $('#dpmDtFooter').data('dtReady', false);
                }
                enhanceDpmDtControls();
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
    var dpmColStorageKey = 'departmentMaster:hiddenColumns:v1';

    function dpmGetHiddenCols() {
        try {
            var raw = localStorage.getItem(dpmColStorageKey);
            var arr = raw ? JSON.parse(raw) : [];
            return Array.isArray(arr) ? arr : [];
        } catch (e) { return []; }
    }
    function dpmPersistHiddenCols(arr) {
        try { localStorage.setItem(dpmColStorageKey, JSON.stringify(arr)); } catch (e) {}
    }

    function setupDpmColumns(dt) {
        if (!dt) { return; }
        var hidden = dpmGetHiddenCols();

        // Apply saved visibility (persists across ajax reloads / redraws).
        dt.columns().every(function () {
            var idx = this.index();
            this.visible(hidden.indexOf(idx) === -1, false);
        });
        dt.columns.adjust();

        var $grid = $('#dpmColumnToggleGrid');
        if (!$grid.length) { return; }
        $grid.empty();

        dt.columns().every(function () {
            var idx = this.index();
            var title = $(this.header()).text().replace(/\s+/g, ' ').trim();
            if (!title) { return; }

            var inputId = 'dpmcolvis_' + idx;
            var $cell = $('<div class="col-12 col-sm-6 col-md-4"></div>');
            var $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                .attr('for', inputId);
            var $cb = $('<input type="checkbox" class="form-check-input m-0">')
                .attr('id', inputId)
                .prop('checked', hidden.indexOf(idx) === -1);

            $cb.on('change', function () {
                var h = dpmGetHiddenCols();
                var pos = h.indexOf(idx);
                if (this.checked) {
                    if (pos !== -1) h.splice(pos, 1);
                } else {
                    if (pos === -1) h.push(idx);
                }
                dpmPersistHiddenCols(h);
                dt.column(idx).visible(this.checked, false);
                dt.columns.adjust();
            });

            $label.append($cb).append($('<span></span>').text(title));
            $cell.append($label);
            $grid.append($cell);
        });
    }

    // Wait for Yajra to finish initializing the table, then wire columns.
    (function whenReady(tries) {
        tries = tries || 0;
        if ($.fn.DataTable && $.fn.DataTable.isDataTable('#departmentmaster-table')) {
            setupDpmColumns($('#departmentmaster-table').DataTable());
        } else if (tries < 100) {
            setTimeout(function () { whenReady(tries + 1); }, 100);
        }
    })();
});
</script>
@endpush
