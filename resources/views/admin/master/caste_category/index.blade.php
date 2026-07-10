@extends('admin.layouts.master')

@section('title', 'Caste Category Master')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/caste-category-master-admin.css') }}?v={{ @filemtime(public_path('css/caste-category-master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid ccm-master-page">
    <x-breadcrum title="Caste Category Master">
        <button type="button"
            class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 rounded-2 fw-semibold text-nowrap shadow-sm ccm-open-add-btn"
            aria-controls="ccmCasteModal">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Add Caste Category</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    <div class="card ccm-dt-card shadow-sm rounded-3 overflow-hidden">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center justify-content-end gap-3 mb-4">
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="btnCcmColumns"
                        data-bs-toggle="modal" data-bs-target="#ccmColumnVisibilityModal"
                        title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="ccmDtSearch" class="programme-dt-search" data-dt-search-for="castecategorymaster-table"></div>
                </div>
            </div>

            <div class="programme-dt-panel ccm-dt-panel">
                <div class="table-responsive ccm-dt-scroll">
                    {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table border-0']) !!}
                </div>
                <div id="ccmDtFooter"
                    class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                    data-dt-footer-for="castecategorymaster-table"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade ccm-caste-modal" id="ccmCasteModal" tabindex="-1" aria-labelledby="ccmCasteModalLabel" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered ccm-caste-modal-dialog">
        <div class="modal-content cgt-form-modal border-0 shadow-lg">
            <div class="modal-header">
                <h5 class="modal-title mb-0" id="ccmCasteModalLabel">Add Caste Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="ccmCasteForm" class="ccm-caste-modal-form" novalidate>
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="pk" id="ccm_pk" value="">

                    <label for="ccm_Seat_name" class="form-label cgt-field-label mb-2">
                        Caste Name in English <span class="text-danger">*</span>
                    </label>
                    <input type="text"
                        name="Seat_name"
                        id="ccm_Seat_name"
                        class="form-control rounded-2 mb-4"
                        placeholder="eg. SC"
                        autocomplete="off">
                    <small class="text-danger d-none mb-3 d-block" id="ccm_Seat_name_error">
                        Caste Name in English is required
                    </small>

                    <label for="ccm_Seat_name_hindi" class="form-label cgt-field-label mb-2">
                        Caste Name in Hindi <span class="text-danger">*</span>
                    </label>
                    <input type="text"
                        name="Seat_name_hindi"
                        id="ccm_Seat_name_hindi"
                        class="form-control rounded-2"
                        placeholder="eg. अनुसूचित जाति"
                        autocomplete="off">
                    <small class="text-danger d-none mt-1" id="ccm_Seat_name_hindi_error">
                        Caste Name in Hindi is required
                    </small>
                </form>
            </div>
            <div class="modal-footer gap-2 justify-content-end">
                <button type="button" class="btn btn-outline-primary rounded-2 px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary rounded-2 px-4" id="ccmFormSubmit">Create Caste</button>
            </div>
        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="ccmColumnVisibilityModal" tabindex="-1" aria-labelledby="ccmColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="ccmColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="ccmColumnToggleGrid"></div>
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
    var tableSelector = '#castecategorymaster-table';
    var storeUrl = "{{ route('master.caste.category.store') }}";
    var csrfToken = "{{ csrf_token() }}";
    var ccmModalMode = 'add';
    var ccmModalBound = false;
    var ccmTableBound = false;
    var ccmInitAttempts = 0;

    var ccmModalEl = document.getElementById('ccmCasteModal');
    if (ccmModalEl && ccmModalEl.parentElement && ccmModalEl.parentElement !== document.body) {
        document.body.appendChild(ccmModalEl);
    }

    function showCcmModal() {
        if (!ccmModalEl) {
            return;
        }
        if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(ccmModalEl).show();
        } else if (window.jQuery) {
            jQuery(ccmModalEl).modal('show');
        }
    }

    function hideCcmModal() {
        if (!ccmModalEl) {
            return;
        }
        if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(ccmModalEl).hide();
        } else if (window.jQuery) {
            jQuery(ccmModalEl).modal('hide');
        }
    }

    function clearCcmFieldErrors() {
        jQuery('#ccm_Seat_name_error, #ccm_Seat_name_hindi_error').addClass('d-none');
        jQuery('#ccm_Seat_name, #ccm_Seat_name_hindi').removeClass('is-invalid');
    }

    function showCcmFieldError(field, message) {
        var map = {
            Seat_name: '#ccm_Seat_name_error',
            Seat_name_hindi: '#ccm_Seat_name_hindi_error'
        };
        var inputMap = {
            Seat_name: '#ccm_Seat_name',
            Seat_name_hindi: '#ccm_Seat_name_hindi'
        };
        if (map[field]) {
            jQuery(map[field]).text(message).removeClass('d-none');
            jQuery(inputMap[field]).addClass('is-invalid');
        }
    }

    function openCcmModal(mode, data) {
        ccmModalMode = mode;
        var isAdd = mode === 'add';

        jQuery('#ccmCasteModalLabel').text(isAdd ? 'Add Caste Category' : 'Edit Caste Category');
        jQuery('#ccmFormSubmit').text(isAdd ? 'Create Caste' : 'Update Caste');
        jQuery('#ccm_pk').val(isAdd ? '' : (data.pk || ''));
        jQuery('#ccm_Seat_name').val(isAdd ? '' : (data.seatName || ''));
        jQuery('#ccm_Seat_name_hindi').val(isAdd ? '' : (data.seatNameHindi || ''));
        clearCcmFieldErrors();
        showCcmModal();

        window.setTimeout(function () {
            jQuery('#ccm_Seat_name').trigger('focus');
        }, 200);
    }

    function extractEncryptedPkFromUrl(url) {
        if (!url) {
            return '';
        }
        var parts = String(url).replace(/\/+$/, '').split('/');
        return parts[parts.length - 1] || '';
    }

    function reloadCcmTable() {
        if (typeof jQuery !== 'undefined' && jQuery.fn.DataTable && jQuery.fn.DataTable.isDataTable(tableSelector)) {
            jQuery(tableSelector).DataTable().ajax.reload(null, false);
        }
    }

    function iconOnlyLink($link, iconClass, extraClass, label) {
        $link.removeClass('btn btn-sm btn-primary btn-success btn-danger');
        $link.addClass('ccm-action-btn ' + (extraClass || ''));
        $link.attr('aria-label', label || $link.text().trim());
        $link.empty().append('<i class="bi ' + iconClass + '" aria-hidden="true"></i>');
    }

    function swapCcmStatusAction($cells) {
        if ($cells.length < 5) {
            return $cells;
        }
        $cells.eq(4).insertBefore($cells.eq(3));
        return $cells;
    }

    function mergeCcmNameCells($cells) {
        if ($cells.length < 5 || $cells.eq(0).closest('tr').hasClass('ccm-name-merged')) {
            return $cells;
        }
        var english = $cells.eq(1).text().trim();
        var hindi = $cells.eq(2).text().trim();
        var combined = hindi ? (english + ' - ' + hindi) : english;
        $cells.eq(1).text(combined);
        $cells.eq(2).remove();
        $cells.eq(0).closest('tr').addClass('ccm-name-merged');
        return $cells;
    }

    function swapCcmHeaders() {
        var $wrapper = jQuery('#castecategorymaster-table_wrapper');
        if ($wrapper.data('ccm-headers-ready')) {
            return;
        }

        var $ths = $wrapper.find('.dataTables_scrollHead thead tr th');
        if ($ths.length < 5) {
            $ths = jQuery(tableSelector).find('thead tr th');
        }
        if ($ths.length < 5) {
            return;
        }

        $ths.eq(4).insertBefore($ths.eq(3));
        $ths.eq(1).text('Caste Name');
        $ths.eq(2).remove();

        var $firstTh = jQuery(tableSelector).find('thead tr th').first();
        if ($firstTh.length && $firstTh.text().trim().replace(/\s+/g, '') === 'S.No.') {
            $firstTh.text('S. No.');
        }

        $wrapper.data('ccm-headers-ready', true);
    }

    function updateCcmStatusBadge($toggle, isActive) {
        var $badge = $toggle.closest('tr').find('.ccm-status-badge');
        if (!$badge.length) {
            return;
        }
        $badge
            .removeClass('programme-status-badge--active programme-status-badge--inactive')
            .addClass(isActive ? 'programme-status-badge--active' : 'programme-status-badge--inactive')
            .text(isActive ? 'Active' : 'Inactive');
    }

    function openCcmEditFromRow($link) {
        var $row = $link.closest('tr');
        var seatName = $row.data('ccm-seat-name') || '';
        var seatNameHindi = $row.data('ccm-seat-name-hindi') || '';

        if (!seatName && $row.hasClass('ccm-name-merged')) {
            var combined = $row.find('td').eq(1).text().trim();
            var parts = combined.split(' - ');
            seatName = parts[0] ? parts[0].trim() : combined;
            seatNameHindi = parts.length > 1 ? parts.slice(1).join(' - ').trim() : '';
        }

        openCcmModal('edit', {
            pk: extractEncryptedPkFromUrl($link.attr('href')),
            seatName: seatName,
            seatNameHindi: seatNameHindi
        });
    }

    function decorateCcmRows() {
        swapCcmHeaders();

        jQuery(tableSelector + ' tbody tr').each(function () {
            var $row = jQuery(this);
            var $cells = $row.find('td');

            if ($cells.length < 5) {
                return;
            }

            if (!$row.data('ccm-seat-name')) {
                $row.data('ccm-seat-name', $cells.eq(1).text().trim());
                $row.data('ccm-seat-name-hindi', $cells.eq(2).text().trim());
            }

            if (!$row.hasClass('ccm-cols-swapped')) {
                swapCcmStatusAction($cells);
                $row.addClass('ccm-cols-swapped');
                $cells = $row.find('td');
            }

            if (!$row.hasClass('ccm-name-merged')) {
                mergeCcmNameCells($row.find('td'));
                $cells = $row.find('td');
            }

            if ($row.hasClass('ccm-row-decorated')) {
                return;
            }

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
                        class: 'badge rounded-pill programme-status-badge ccm-status-badge ' + badgeClass,
                        text: label
                    })
                );

                var $editLink = $actionCell.find('a').first().detach();
                var $group = jQuery('<div>', {
                    class: 'ccm-caste-actions',
                    role: 'group',
                    'aria-label': 'Caste category actions'
                });

                if ($editLink.length) {
                    iconOnlyLink($editLink, 'bi-pencil', 'ccm-action-edit', 'Edit caste category');
                    $group.append($editLink);
                }

                $toggleWrap.addClass('ccm-action-switch-wrap mb-0');
                $group.append($toggleWrap);
                $actionCell.empty().append($group);
            } else {
                var $editOnly = $actionCell.find('a').first();
                if ($editOnly.length) {
                    iconOnlyLink($editOnly, 'bi-pencil', 'ccm-action-edit', 'Edit caste category');
                }
            }

            $row.addClass('ccm-row-decorated');
        });
    }

    function submitCcmForm() {
        var seatName = jQuery('#ccm_Seat_name').val().trim();
        var seatNameHindi = jQuery('#ccm_Seat_name_hindi').val().trim();
        clearCcmFieldErrors();

        var hasError = false;
        if (!seatName) {
            showCcmFieldError('Seat_name', 'Caste Name in English is required');
            hasError = true;
        }
        if (!seatNameHindi) {
            showCcmFieldError('Seat_name_hindi', 'Caste Name in Hindi is required');
            hasError = true;
        }
        if (hasError) {
            if (!seatName) {
                jQuery('#ccm_Seat_name').trigger('focus');
            } else {
                jQuery('#ccm_Seat_name_hindi').trigger('focus');
            }
            return;
        }

        var payload = {
            _token: csrfToken,
            Seat_name: seatName,
            Seat_name_hindi: seatNameHindi
        };

        if (ccmModalMode === 'edit') {
            payload.pk = jQuery('#ccm_pk').val();
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
                hideCcmModal();
                reloadCcmTable();

                var message = response.message || (ccmModalMode === 'edit'
                    ? 'Caste Category updated successfully.'
                    : 'Caste Category created successfully.');

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: ccmModalMode === 'edit' ? 'Updated!' : 'Created!',
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
                    if (errors.Seat_name && errors.Seat_name[0]) {
                        showCcmFieldError('Seat_name', errors.Seat_name[0]);
                    }
                    if (errors.Seat_name_hindi && errors.Seat_name_hindi[0]) {
                        showCcmFieldError('Seat_name_hindi', errors.Seat_name_hindi[0]);
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

    function bindCcmModalHandlers() {
        if (ccmModalBound || typeof jQuery === 'undefined') {
            return;
        }
        ccmModalBound = true;

        jQuery(document).on('click', '.ccm-open-add-btn', function (e) {
            e.preventDefault();
            openCcmModal('add');
        });

        jQuery(document).on('click', tableSelector + ' tbody a[href*="caste-category/edit"]', function (e) {
            e.preventDefault();
            openCcmEditFromRow(jQuery(this));
        });

        if (ccmModalEl) {
            ccmModalEl.addEventListener('hidden.bs.modal', function () {
                clearCcmFieldErrors();
                jQuery('#ccm_pk').val('');
                jQuery('#ccm_Seat_name, #ccm_Seat_name_hindi').val('');
            });
        }

        jQuery('#ccmFormSubmit').on('click', submitCcmForm);
        jQuery('#ccmCasteForm').on('submit', function (e) {
            e.preventDefault();
            submitCcmForm();
        });

        var params = new URLSearchParams(window.location.search);
        if (params.get('open_ccm_modal') === 'add') {
            openCcmModal('add');
            if (window.history && window.history.replaceState) {
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        } else if (params.get('open_ccm_modal') === 'edit') {
            var ccmName = params.get('ccm_seat_name') || '';
            var ccmNameHi = params.get('ccm_seat_name_hindi') || '';
            try {
                ccmName = decodeURIComponent(ccmName.replace(/\+/g, ' '));
                ccmNameHi = decodeURIComponent(ccmNameHi.replace(/\+/g, ' '));
            } catch (e) { /* keep raw */ }
            openCcmModal('edit', {
                pk: params.get('ccm_pk') || '',
                seatName: ccmName,
                seatNameHindi: ccmNameHi
            });
            if (window.history && window.history.replaceState) {
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        }
    }

    function bindCcmTableHandlers() {
        if (ccmTableBound || typeof jQuery === 'undefined' || !jQuery.fn.dataTable) {
            return;
        }
        ccmTableBound = true;

        jQuery(tableSelector).on('draw.dt init.dt', function () {
            jQuery('#castecategorymaster-table_wrapper').data('ccm-headers-ready', false);
            jQuery(tableSelector + ' tbody tr').removeClass(
                'ccm-cols-swapped ccm-name-merged ccm-row-decorated'
            );
            decorateCcmRows();
        });

        jQuery(document).on('change', tableSelector + ' .status-toggle', function () {
            var $toggle = jQuery(this);
            window.setTimeout(function () {
                updateCcmStatusBadge($toggle, $toggle.is(':checked'));
            }, 0);
        });

        if (jQuery.fn.DataTable.isDataTable(tableSelector)) {
            decorateCcmRows();
        }
    }

    function initCcmPage() {
        bindCcmModalHandlers();
        bindCcmTableHandlers();

        if (!ccmTableBound && typeof jQuery !== 'undefined' && ccmInitAttempts < 50) {
            ccmInitAttempts += 1;
            window.setTimeout(initCcmPage, 100);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCcmPage);
    } else {
        initCcmPage();
    }
})();
</script>

<script>
/* ---- Per-page relocation of search / pagination / "Showing N of M" ----
   DataTables renders these inside a hidden default-dom row; this moves them into
   the visible #ccmDtSearch / #ccmDtFooter slots (the page CSS only reveals them
   once relocated). Poll-based so it runs regardless of init.dt timing. */
(function () {
    function updateCcmDtCount() {
        var $ = window.jQuery;
        if (!$ || !$.fn.DataTable || !$.fn.DataTable.isDataTable('#castecategorymaster-table')) {
            return;
        }
        var info = $('#castecategorymaster-table').DataTable().page.info();
        var $info = $('#ccmDtFooter .dataTables_info');
        if ($info.length && info && info.recordsDisplay !== undefined) {
            $info.text('of ' + info.recordsDisplay.toLocaleString() + ' items');
        }
    }

    function enhanceCcmDtControls() {
        var $ = window.jQuery;
        var $wrapper = $('#castecategorymaster-table_wrapper');
        if (!$wrapper.length) {
            return;
        }

        var $searchSlot = $('#ccmDtSearch');
        var $footer = $('#ccmDtFooter');

        if ($searchSlot.length && !$searchSlot.find('.dataTables_filter').length) {
            var $filter = $wrapper.find('.dataTables_filter').first();
            if ($filter.length) {
                $filter.find('input')
                    .addClass('form-control shadow-none')
                    .attr('placeholder', 'Search')
                    .attr('aria-label', 'Search caste categories');
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
            updateCcmDtCount();
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
        updateCcmDtCount();
    }

    (function whenReady(tries) {
        var $ = window.jQuery;
        tries = tries || 0;
        if ($ && $.fn.DataTable && $.fn.DataTable.isDataTable('#castecategorymaster-table')) {
            enhanceCcmDtControls();
            $('#castecategorymaster-table').on('draw.dt', function () {
                if (!$('#ccmDtFooter .dataTables_paginate').length) {
                    $('#ccmDtFooter').data('dtReady', false);
                }
                enhanceCcmDtControls();
            });
        } else if (tries < 100) {
            setTimeout(function () { whenReady(tries + 1); }, 100);
        }
    })(0);
})();
</script>

<script>
/* ---- Column Visibility (CSS-based) ----
   This page's row decoration MERGES the English + Hindi name columns into one
   and REMOVES a column, so DataTables' 5-column model no longer matches the 4
   visible cells — the usual .column().visible() API would hide the wrong column.
   Instead we toggle the stable *visual* columns (post-decoration nth-child) via
   an injected <style> rule, which is decoupled from DataTables' model and auto-
   applies to redrawn rows. Persisted to localStorage. */
$(function () {
    var TABLE = '#castecategorymaster-table';
    var storageKey = 'casteCategoryMaster:hiddenColumns:v1';
    // Visual columns after decoration (1-based nth-child position + label).
    var COLS = [
        { n: 1, title: 'S. No.' },
        { n: 2, title: 'Caste Name' },
        { n: 3, title: 'Status' },
        { n: 4, title: 'Action' }
    ];
    var styleEl = null;

    function getHidden() {
        try {
            var raw = localStorage.getItem(storageKey);
            var arr = raw ? JSON.parse(raw) : [];
            return Array.isArray(arr) ? arr : [];
        } catch (e) { return []; }
    }
    function persist(arr) {
        try { localStorage.setItem(storageKey, JSON.stringify(arr)); } catch (e) {}
    }

    function applyHidden(hidden) {
        if (!styleEl) {
            styleEl = document.createElement('style');
            document.head.appendChild(styleEl);
        }
        styleEl.textContent = hidden.map(function (n) {
            return TABLE + ' thead th:nth-child(' + n + '),' +
                   TABLE + ' tbody td:nth-child(' + n + '){display:none !important;}';
        }).join('');
    }

    function buildGrid() {
        var $grid = $('#ccmColumnToggleGrid');
        if (!$grid.length) { return; }
        var hidden = getHidden();
        $grid.empty();

        COLS.forEach(function (col) {
            var inputId = 'ccmcolvis_' + col.n;
            var $cell = $('<div class="col-12 col-sm-6 col-md-4"></div>');
            var $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                .attr('for', inputId);
            var $cb = $('<input type="checkbox" class="form-check-input m-0">')
                .attr('id', inputId)
                .prop('checked', hidden.indexOf(col.n) === -1);

            $cb.on('change', function () {
                var h = getHidden();
                var pos = h.indexOf(col.n);
                if (this.checked) {
                    if (pos !== -1) h.splice(pos, 1);
                } else {
                    if (pos === -1) h.push(col.n);
                }
                persist(h);
                applyHidden(h);
            });

            $label.append($cb).append($('<span></span>').text(col.title));
            $cell.append($label);
            $grid.append($cell);
        });
    }

    applyHidden(getHidden());
    buildGrid();
});
</script>
@endpush
