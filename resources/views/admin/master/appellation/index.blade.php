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
                <div id="apmDtSearch" class="programme-dt-search ms-lg-auto" data-dt-search-for="appellation-master-table"></div>
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
            if ($cells.length < 4) {
                return;
            }

            if (!$row.data('apm-name')) {
                $row.data('apm-name', $cells.eq(1).text().trim());
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
@endpush
