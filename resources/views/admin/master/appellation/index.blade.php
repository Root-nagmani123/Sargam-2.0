@extends('admin.layouts.master')

@section('title', 'Appellation Master')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/appellation-master-admin.css') }}?v={{ @filemtime(public_path('css/appellation-master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid apm-master-page">
    <x-breadcrum title="Appellation Master">
        <button type="button"
            class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 rounded-2 fw-semibold text-nowrap shadow-sm apm-open-add-btn"
            aria-controls="apmAppellationModal">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Add Appellation</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    <div class="card apm-dt-card shadow-sm rounded-3 overflow-hidden">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center justify-content-end gap-3 mb-4">
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="btnApmColumns"
                        data-bs-toggle="modal" data-bs-target="#apmColumnVisibilityModal"
                        title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="apmDtSearch" class="programme-dt-search" data-dt-search-for="appellation-master-table"></div>
                </div>
            </div>

            <div class="programme-dt-panel apm-dt-panel">
                <div class="table-responsive apm-dt-scroll">
                    {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table border-0']) !!}
                </div>
                <div id="apmDtFooter"
                    class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                    data-dt-footer-for="appellation-master-table"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade apm-appellation-modal" id="apmAppellationModal" tabindex="-1" aria-labelledby="apmAppellationModalLabel" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered apm-appellation-modal-dialog">
        <div class="modal-content cgt-form-modal border-0 shadow-lg">
            <div class="modal-header">
                <h5 class="modal-title mb-0" id="apmAppellationModalLabel">Add Appellation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="apmAppellationForm" class="apm-appellation-modal-form" novalidate>
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="id" id="apm_id" value="">
                    <input type="hidden" name="active_inactive" id="apm_active_inactive" value="1">

                    <label for="apm_appettation_name" class="form-label cgt-field-label mb-2">
                        Appellation Name <span class="text-danger">*</span>
                    </label>
                    <input type="text"
                        name="appettation_name"
                        id="apm_appettation_name"
                        class="form-control rounded-2"
                        placeholder="eg. Brigadier"
                        maxlength="50"
                        autocomplete="off">
                    <small class="text-danger d-none mt-1" id="apm_appettation_name_error">
                        Appellation Name is required
                    </small>
                </form>
            </div>
            <div class="modal-footer gap-2 justify-content-end">
                <button type="button" class="btn btn-outline-primary rounded-2 px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary rounded-2 px-4" id="apmFormSubmit">Create Appellation</button>
            </div>
        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="apmColumnVisibilityModal" tabindex="-1" aria-labelledby="apmColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="apmColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="apmColumnToggleGrid"></div>
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
    var tableSelector = '#appellation-master-table';
    var storeUrl = "{{ route('master.appellation.store') }}";
    var csrfToken = "{{ csrf_token() }}";
    var apmModalMode = 'add';
    var apmModalBound = false;
    var apmTableBound = false;
    var apmInitAttempts = 0;

    var apmModalEl = document.getElementById('apmAppellationModal');
    if (apmModalEl && apmModalEl.parentElement && apmModalEl.parentElement !== document.body) {
        document.body.appendChild(apmModalEl);
    }

    function showApmModal() {
        if (!apmModalEl) {
            return;
        }
        if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(apmModalEl).show();
        } else if (window.jQuery) {
            jQuery(apmModalEl).modal('show');
        }
    }

    function hideApmModal() {
        if (!apmModalEl) {
            return;
        }
        if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(apmModalEl).hide();
        } else if (window.jQuery) {
            jQuery(apmModalEl).modal('hide');
        }
    }

    function clearApmFieldErrors() {
        jQuery('#apm_appettation_name_error').addClass('d-none').text('Appellation Name is required');
        jQuery('#apm_appettation_name').removeClass('is-invalid');
    }

    function showApmFieldError(message) {
        jQuery('#apm_appettation_name_error').text(message || 'Appellation Name is required').removeClass('d-none');
        jQuery('#apm_appettation_name').addClass('is-invalid');
    }

    function openApmModal(mode, data) {
        apmModalMode = mode;
        var isAdd = mode === 'add';

        jQuery('#apmAppellationModalLabel').text(isAdd ? 'Add Appellation' : 'Edit Appellation');
        jQuery('#apmFormSubmit').text(isAdd ? 'Create Appellation' : 'Update Appellation');
        jQuery('#apm_id').val(isAdd ? '' : (data.id || ''));
        jQuery('#apm_appettation_name').val(isAdd ? '' : (data.name || ''));
        jQuery('#apm_active_inactive').val(isAdd ? '1' : String(data.status || '1'));
        clearApmFieldErrors();
        showApmModal();

        window.setTimeout(function () {
            jQuery('#apm_appettation_name').trigger('focus');
        }, 200);
    }

    function extractEncryptedIdFromUrl(url) {
        if (!url) {
            return '';
        }
        var parts = String(url).replace(/\/+$/, '').split('/');
        return parts[parts.length - 1] || '';
    }

    function reloadApmTable() {
        if (typeof jQuery !== 'undefined' && jQuery.fn.DataTable && jQuery.fn.DataTable.isDataTable(tableSelector)) {
            jQuery(tableSelector).DataTable().ajax.reload(null, false);
        }
    }

    function styleApmEditLink($link) {
        $link.addClass('apm-action-btn apm-action-edit');
        $link.attr('aria-label', 'Edit appellation');
        $link.empty().append('<i class="bi bi-pencil" aria-hidden="true"></i>');
    }

    function styleApmDeleteControl($btn) {
        $btn.addClass('apm-action-btn apm-action-delete');
        $btn.attr('aria-label', $btn.is(':disabled') ? 'Cannot delete active record' : 'Delete appellation');
        $btn.empty().append('<i class="bi bi-trash" aria-hidden="true"></i>');
    }

    function normalizeApmHeaders() {
        var $firstTh = jQuery(tableSelector).find('thead tr th').first();
        if ($firstTh.length) {
            var text = $firstTh.text().trim().replace(/\s+/g, '');
            if (text === 'S.No' || text === 'S.No.') {
                $firstTh.text('S. No.');
            }
        }
        jQuery(tableSelector).find('thead tr th').each(function () {
            if (jQuery(this).text().trim() === 'Actions') {
                jQuery(this).text('Action');
            }
        });
    }

    function updateApmStatusBadge($toggle, isActive) {
        var $badge = $toggle.closest('tr').find('.apm-status-badge');
        if (!$badge.length) {
            return;
        }
        $badge
            .removeClass('programme-status-badge--active programme-status-badge--inactive')
            .addClass(isActive ? 'programme-status-badge--active' : 'programme-status-badge--inactive')
            .text(isActive ? 'Active' : 'Inactive');
    }

    function openApmEditFromRow($link) {
        var $row = $link.closest('tr');
        var isActive = $row.find('.status-toggle').is(':checked');
        openApmModal('edit', {
            id: extractEncryptedIdFromUrl($link.attr('href')),
            name: $row.data('apm-name') || $row.find('td').eq(1).text().trim(),
            status: isActive ? '1' : '2'
        });
    }

    function decorateApmRows() {
        normalizeApmHeaders();

        jQuery(tableSelector + ' tbody tr').each(function () {
            var $row = jQuery(this);

            if ($row.hasClass('apm-row-decorated')) {
                return;
            }

            var $cells = $row.find('td');

            if (!$row.data('apm-name')) {
                $row.data('apm-name', $cells.eq(1).text().trim());
            }

            // Locate cells by content (not fixed position) so the decoration
            // survives column-visibility toggles that change the cell count.
            var $toggle = $row.find('.status-toggle').first();
            var $toggleWrap = $toggle.closest('.form-check');
            var $statusCell = $toggleWrap.closest('td');
            var $editLink = $row.find('a[href*="edit"]').first();
            var $deleteBtn = $row.find('button').first();
            var $actionCell = $editLink.length ? $editLink.closest('td')
                : ($deleteBtn.length ? $deleteBtn.closest('td') : $());

            if ($toggle.length && $toggleWrap.length && $statusCell.length) {
                var isActive = $toggle.is(':checked');
                var badgeClass = isActive ? 'programme-status-badge--active' : 'programme-status-badge--inactive';
                var label = isActive ? 'Active' : 'Inactive';

                $toggleWrap.detach();
                $statusCell.empty().append(
                    jQuery('<span>', {
                        class: 'badge rounded-pill programme-status-badge apm-status-badge ' + badgeClass,
                        text: label
                    })
                );

                var $group = jQuery('<div>', {
                    class: 'apm-appellation-actions',
                    role: 'group',
                    'aria-label': 'Appellation actions'
                });

                var $editLink = $actionCell.find('a[href*="edit"]').first();
                if ($editLink.length) {
                    styleApmEditLink($editLink);
                    $group.append($editLink);
                }

                $toggleWrap.addClass('apm-action-switch-wrap mb-0');
                $group.append($toggleWrap);

                var $deleteBtn = $actionCell.find('button').first();
                if ($deleteBtn.length) {
                    styleApmDeleteControl($deleteBtn);
                    var $form = $deleteBtn.closest('form');
                    if ($form.length) {
                        $group.append($form);
                    } else {
                        $group.append($deleteBtn);
                    }
                }

                $actionCell.empty().append($group);
            }

            $row.addClass('apm-row-decorated');
        });
    }

    function submitApmForm() {
        var name = jQuery('#apm_appettation_name').val().trim();
        var status = jQuery('#apm_active_inactive').val() || '1';
        clearApmFieldErrors();

        if (!name) {
            showApmFieldError('Appellation Name is required');
            jQuery('#apm_appettation_name').trigger('focus');
            return;
        }
        if (!/^[a-zA-Z\s\.]+$/.test(name)) {
            showApmFieldError('Appellation name must contain only letters and spaces.');
            jQuery('#apm_appettation_name').trigger('focus');
            return;
        }

        var payload = {
            _token: csrfToken,
            appettation_name: name,
            active_inactive: status
        };

        if (apmModalMode === 'edit') {
            payload.id = jQuery('#apm_id').val();
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
                hideApmModal();
                reloadApmTable();

                var message = response.message || 'Appellation saved successfully.';

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: apmModalMode === 'edit' ? 'Updated!' : 'Created!',
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
                    if (errors.appettation_name && errors.appettation_name[0]) {
                        showApmFieldError(errors.appettation_name[0]);
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

    function bindApmNameInputFilter() {
        var input = document.getElementById('apm_appettation_name');
        if (!input || input.dataset.apmFiltered) {
            return;
        }
        input.dataset.apmFiltered = '1';
        input.addEventListener('keypress', function (e) {
            var char = String.fromCharCode(e.which);
            if (!/^[a-zA-Z\s\.]$/.test(char)) {
                e.preventDefault();
            }
        });
        input.addEventListener('paste', function (e) {
            var pasted = (e.clipboardData || window.clipboardData).getData('text');
            if (!/^[a-zA-Z\s\.]+$/.test(pasted)) {
                e.preventDefault();
            }
        });
    }

    function bindApmModalHandlers() {
        if (apmModalBound || typeof jQuery === 'undefined') {
            return;
        }
        apmModalBound = true;

        bindApmNameInputFilter();

        jQuery(document).on('click', '.apm-open-add-btn', function (e) {
            e.preventDefault();
            openApmModal('add');
        });

        jQuery(document).on('click', tableSelector + ' tbody a[href*="appellation/edit"]', function (e) {
            e.preventDefault();
            openApmEditFromRow(jQuery(this));
        });

        if (apmModalEl) {
            apmModalEl.addEventListener('hidden.bs.modal', function () {
                clearApmFieldErrors();
                jQuery('#apm_id').val('');
                jQuery('#apm_appettation_name').val('');
                jQuery('#apm_active_inactive').val('1');
            });
        }

        jQuery('#apmFormSubmit').on('click', submitApmForm);
        jQuery('#apmAppellationForm').on('submit', function (e) {
            e.preventDefault();
            submitApmForm();
        });

        var params = new URLSearchParams(window.location.search);
        if (params.get('open_apm_modal') === 'add') {
            openApmModal('add');
            if (window.history && window.history.replaceState) {
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        } else if (params.get('open_apm_modal') === 'edit') {
            var apmName = params.get('apm_name') || '';
            try {
                apmName = decodeURIComponent(apmName.replace(/\+/g, ' '));
            } catch (e) { /* keep raw */ }
            openApmModal('edit', {
                id: params.get('apm_id') || '',
                name: apmName,
                status: params.get('apm_status') || '1'
            });
            if (window.history && window.history.replaceState) {
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        }
    }

    function bindApmTableHandlers() {
        if (apmTableBound || typeof jQuery === 'undefined' || !jQuery.fn.dataTable) {
            return;
        }
        apmTableBound = true;

        jQuery(tableSelector).on('draw.dt init.dt', function () {
            jQuery(tableSelector + ' tbody tr').removeClass('apm-row-decorated');
            decorateApmRows();
        });

        jQuery(document).on('change', tableSelector + ' .status-toggle', function () {
            var $toggle = jQuery(this);
            window.setTimeout(function () {
                updateApmStatusBadge($toggle, $toggle.is(':checked'));
            }, 0);
        });

        if (jQuery.fn.DataTable.isDataTable(tableSelector)) {
            decorateApmRows();
        }
    }

    function initApmPage() {
        bindApmModalHandlers();
        bindApmTableHandlers();

        if (!apmTableBound && typeof jQuery !== 'undefined' && apmInitAttempts < 50) {
            apmInitAttempts += 1;
            window.setTimeout(initApmPage, 100);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initApmPage);
    } else {
        initApmPage();
    }
})();
</script>

<script>
/* ---- Per-page relocation of search / pagination / "Showing N of M" ----
   DataTables renders these inside a hidden default-dom row; this moves them into
   the visible #apmDtSearch / #apmDtFooter slots (the page CSS only reveals them
   once relocated). Poll-based so it runs regardless of init.dt timing. */
(function () {
    function updateApmDtCount() {
        var $ = window.jQuery;
        if (!$ || !$.fn.DataTable || !$.fn.DataTable.isDataTable('#appellation-master-table')) {
            return;
        }
        var info = $('#appellation-master-table').DataTable().page.info();
        var $info = $('#apmDtFooter .dataTables_info');
        if ($info.length && info && info.recordsDisplay !== undefined) {
            $info.text('of ' + info.recordsDisplay.toLocaleString() + ' items');
        }
    }

    function enhanceApmDtControls() {
        var $ = window.jQuery;
        var $wrapper = $('#appellation-master-table_wrapper');
        if (!$wrapper.length) {
            return;
        }

        var $searchSlot = $('#apmDtSearch');
        var $footer = $('#apmDtFooter');

        if ($searchSlot.length && !$searchSlot.find('.dataTables_filter').length) {
            var $filter = $wrapper.find('.dataTables_filter').first();
            if ($filter.length) {
                $filter.find('input')
                    .addClass('form-control shadow-none')
                    .attr('placeholder', 'Search')
                    .attr('aria-label', 'Search appellations');
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
            updateApmDtCount();
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
        updateApmDtCount();
    }

    (function whenReady(tries) {
        var $ = window.jQuery;
        tries = tries || 0;
        if ($ && $.fn.DataTable && $.fn.DataTable.isDataTable('#appellation-master-table')) {
            enhanceApmDtControls();
            $('#appellation-master-table').on('draw.dt', function () {
                if (!$('#apmDtFooter .dataTables_paginate').length) {
                    $('#apmDtFooter').data('dtReady', false);
                }
                enhanceApmDtControls();
            });
        } else if (tries < 100) {
            setTimeout(function () { whenReady(tries + 1); }, 100);
        }
    })(0);
})();
</script>

<script>
/* ---- Column Visibility (drives the live Yajra DataTable via its API) ----
   This page keeps DataTables' column order (no merge/swap), so the standard
   .column().visible() API is safe. Persisted to localStorage. */
$(function () {
    var TABLE = '#appellation-master-table';
    var apmColStorageKey = 'appellationMaster:hiddenColumns:v1';

    function apmGetHiddenCols() {
        try {
            var raw = localStorage.getItem(apmColStorageKey);
            var arr = raw ? JSON.parse(raw) : [];
            return Array.isArray(arr) ? arr : [];
        } catch (e) { return []; }
    }
    function apmPersistHiddenCols(arr) {
        try { localStorage.setItem(apmColStorageKey, JSON.stringify(arr)); } catch (e) {}
    }

    function setupApmColumns(dt) {
        if (!dt) { return; }
        var hidden = apmGetHiddenCols();

        dt.columns().every(function () {
            var idx = this.index();
            this.visible(hidden.indexOf(idx) === -1, false);
        });
        dt.columns.adjust();

        var $grid = $('#apmColumnToggleGrid');
        if (!$grid.length) { return; }
        $grid.empty();

        dt.columns().every(function () {
            var idx = this.index();
            var title = $(this.header()).text().replace(/\s+/g, ' ').trim();
            if (!title) { return; }

            var inputId = 'apmcolvis_' + idx;
            var $cell = $('<div class="col-12 col-sm-6 col-md-4"></div>');
            var $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                .attr('for', inputId);
            var $cb = $('<input type="checkbox" class="form-check-input m-0">')
                .attr('id', inputId)
                .prop('checked', hidden.indexOf(idx) === -1);

            $cb.on('change', function () {
                var h = apmGetHiddenCols();
                var pos = h.indexOf(idx);
                if (this.checked) {
                    if (pos !== -1) h.splice(pos, 1);
                } else {
                    if (pos === -1) h.push(idx);
                }
                apmPersistHiddenCols(h);
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
        if ($.fn.DataTable && $.fn.DataTable.isDataTable(TABLE)) {
            setupApmColumns($(TABLE).DataTable());
        } else if (tries < 100) {
            setTimeout(function () { whenReady(tries + 1); }, 100);
        }
    })();
});
</script>
@endpush
