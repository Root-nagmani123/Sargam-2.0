@extends('admin.layouts.master')

@section('title', 'Faculty')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/faculty-index-admin.css') }}?v={{ @filemtime(public_path('css/faculty-index-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid fc-index-page">
    <x-breadcrum title="Faculty" :showBack="false">
        <a href="{{ route('faculty.create') }}"
            class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 rounded-2 fw-semibold text-nowrap shadow-sm">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Add Faculty</span>
        </a>
    </x-breadcrum>

    <div id="status-msg"></div>

    <div class="d-flex flex-wrap align-items-center justify-content-end gap-2 fc-toolbar-actions mb-3">
        <a href="{{ route('faculty.printBlank') }}"
            class="fc-btn-outline border-0 "
            aria-label="Print blank faculty form">
            <i class="bi bi-printer" aria-hidden="true"></i>
            <span>Print Blank Form</span>
        </a>
        <a href="{{ route('faculty.excel.export') }}"
            class="fc-btn-outline border-0"
            aria-label="Download faculty data">
            <i class="bi bi-download" aria-hidden="true"></i>
            <span>Download</span>
        </a>
    </div>

    <div class="card fc-dt-card shadow-sm rounded-3 overflow-hidden">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center justify-content-end gap-3 mb-4">
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="btnFcColumns"
                        data-bs-toggle="modal" data-bs-target="#fcColumnVisibilityModal"
                        title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="fcDtSearch" class="programme-dt-search" data-dt-search-for="faculty-table"></div>
                </div>
            </div>

            <div class="programme-dt-panel fc-dt-panel">
                <div class="table-responsive fc-dt-scroll">
                    {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table border-0']) !!}
                </div>
                <div id="fcDtFooter"
                    class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                    data-dt-footer-for="faculty-table"></div>
            </div>
        </div>
    </div>

    <!-- Column Visibility Modal -->
    <div class="modal fade" id="fcColumnVisibilityModal" tabindex="-1" aria-labelledby="fcColumnVisibilityLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-2">
                    <h5 class="modal-title fw-bold" id="fcColumnVisibilityLabel">Column Visibility</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-0">
                    <hr class="mt-0">
                    <div class="row g-3" id="fcColumnToggleGrid"></div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{!! $dataTable->scripts() !!}

<script>
// Delete Faculty with SweetAlert Confirmation (preserves .delete-faculty-btn hooks)
$(document).on('click', '.delete-faculty-btn', function(e) {
    e.preventDefault();

    var deleteUrl = $(this).data('url');
    var facultyName = $(this).data('name');
    var csrfToken = $(this).data('token');

    Swal.fire({
        title: 'Are you sure?',
        html: 'You are about to delete faculty: <strong>' + facultyName + '</strong><br><br>This action cannot be undone!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Deleting...',
                text: 'Please wait while we delete the faculty record.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: function () {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: deleteUrl,
                type: 'DELETE',
                data: {
                    _token: csrfToken
                },
                success: function(response) {
                    if (response.status) {
                        Swal.fire({
                            title: 'Deleted!',
                            text: response.message || 'Faculty has been deleted successfully.',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(function () {
                            $('#faculty-table').DataTable().ajax.reload(null, false);
                        });
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            text: response.message || 'Failed to delete faculty.',
                            icon: 'error'
                        });
                    }
                },
                error: function(xhr) {
                    var errorMessage = 'Something went wrong. Please try again.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    Swal.fire({
                        title: 'Error!',
                        text: errorMessage,
                        icon: 'error'
                    });
                }
            });
        }
    });
});
</script>

<script>
(function () {
    var tableSelector = '#faculty-table';

    function normalizeFcHeaders() {
        var $firstTh = jQuery(tableSelector).find('thead tr th').first();
        if ($firstTh.length) {
            var text = $firstTh.text().trim().replace(/\s+/g, '');
            if (text === 'S.No.' || text === 'S.No') {
                $firstTh.text('S. No.');
            }
        }
    }

    function iconOnlyLink($link, iconClass, extraClass, label) {
        $link.removeClass('btn bg-transparent border-0 p-0 text-primary text-info');
        $link.addClass('fc-action-btn ' + (extraClass || ''));
        $link.attr('aria-label', label || $link.attr('title') || '');
        $link.empty().append('<i class="bi ' + iconClass + '" aria-hidden="true"></i>');
    }

    function iconOnlyDeleteBtn($btn, label) {
        $btn.removeClass('btn bg-transparent border-0 p-0 text-danger');
        $btn.addClass('fc-action-btn fc-action-delete delete-faculty-btn');
        if ($btn.is(':disabled')) {
            $btn.attr('aria-disabled', 'true');
        }
        $btn.attr('aria-label', label || $btn.attr('title') || 'Delete faculty');
        $btn.empty().append('<i class="bi bi-trash" aria-hidden="true"></i>');
    }

    function updateFcStatusBadge($toggle, isActive) {
        var $badge = $toggle.closest('tr').find('.fc-status-badge');
        if (!$badge.length) {
            return;
        }
        $badge
            .removeClass('programme-status-badge--active programme-status-badge--inactive')
            .addClass(isActive ? 'programme-status-badge--active' : 'programme-status-badge--inactive')
            .text(isActive ? 'Active' : 'Inactive');
    }

    function decorateFacultyRows() {
        normalizeFcHeaders();

        jQuery(tableSelector + ' tbody tr').each(function () {
            var $row = jQuery(this);
            if ($row.hasClass('fc-row-decorated')) {
                return;
            }

            // Locate cells by content (not fixed position) so the decoration
            // survives column-visibility toggles that change the cell count.
            var $toggle = $row.find('.status-toggle').first();
            var $toggleWrap = $toggle.closest('.form-check');
            var $statusCell = $toggleWrap.closest('td');
            var $editLink = $row.find('a[href*="edit"]').first();
            var $viewLink = $row.find('a[href*="show"]').first();
            var $deleteBtn = $row.find('.delete-faculty-btn').first();
            var $actionCell = $editLink.closest('td');
            if (!$actionCell.length) { $actionCell = $viewLink.closest('td'); }
            if (!$actionCell.length) { $actionCell = $deleteBtn.closest('td'); }

            if ($toggle.length && $statusCell.length && $actionCell.length) {
                var isActive = $toggle.is(':checked');
                var badgeClass = isActive ? 'programme-status-badge--active' : 'programme-status-badge--inactive';
                var label = isActive ? 'Active' : 'Inactive';

                $toggleWrap.detach();
                $statusCell.empty().append(
                    jQuery('<span>', {
                        class: 'badge rounded-pill programme-status-badge fc-status-badge ' + badgeClass,
                        text: label
                    })
                );

                $editLink = $editLink.detach();
                $viewLink = $viewLink.detach();
                $deleteBtn = $deleteBtn.detach();

                var $group = jQuery('<div>', {
                    class: 'fc-faculty-actions',
                    role: 'group',
                    'aria-label': 'Faculty actions'
                });

                if ($viewLink.length) {
                    iconOnlyLink($viewLink, 'bi-eye', 'fc-action-view', 'View faculty');
                    $group.append($viewLink);
                }

                if ($editLink.length) {
                    iconOnlyLink($editLink, 'bi-pencil', 'fc-action-edit', 'Edit faculty');
                    $group.append($editLink);
                }

                $toggleWrap.addClass('fc-action-switch-wrap mb-0');
                $group.append($toggleWrap);

                if ($deleteBtn.length) {
                    iconOnlyDeleteBtn($deleteBtn, $deleteBtn.attr('title') || 'Delete faculty');
                    $group.append($deleteBtn);
                }

                $actionCell.empty().append($group);
            }

            $row.addClass('fc-row-decorated');
        });
    }

    function bindFacultyTableUi() {
        if (typeof jQuery === 'undefined' || !jQuery.fn.dataTable) {
            return;
        }

        jQuery(tableSelector).on('draw.dt init.dt', function () {
            jQuery(tableSelector + ' tbody tr').removeClass('fc-row-decorated');
            decorateFacultyRows();
        });

        jQuery(document).on('change', tableSelector + ' .status-toggle', function () {
            var $toggle = jQuery(this);
            window.setTimeout(function () {
                updateFcStatusBadge($toggle, $toggle.is(':checked'));
            }, 0);
        });

        if (jQuery.fn.DataTable.isDataTable(tableSelector)) {
            decorateFacultyRows();
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindFacultyTableUi);
    } else {
        bindFacultyTableUi();
    }
})();
</script>

<script>
jQuery(document).ready(function () {
    var toastMsg = sessionStorage.getItem('facultyToast');
    if (toastMsg) {
        sessionStorage.removeItem('facultyToast');
        if (typeof toastr !== 'undefined') {
            toastr.options = {
                timeOut: 4000,
                extendedTimeOut: 1000,
                positionClass: 'toast-top-right',
                closeButton: true,
                progressBar: true
            };
            toastr.success(toastMsg);
            jQuery('#toast-container').css('top', '80px');
        }
    }
});
</script>

<script>
/* ---- Per-page relocation of search / pagination / "Showing N of M" ----
   DataTables renders these inside the wrapper (the page CSS hides the native
   search + length there until relocated); this moves them into the visible
   #fcDtSearch / #fcDtFooter slots. Poll-based so it runs regardless of timing
   and cooperates with the global enhancer. */
(function () {
    function updateFcDtCount() {
        var $ = window.jQuery;
        if (!$ || !$.fn.DataTable || !$.fn.DataTable.isDataTable('#faculty-table')) {
            return;
        }
        var info = $('#faculty-table').DataTable().page.info();
        var $info = $('#fcDtFooter .dataTables_info');
        if ($info.length && info && info.recordsDisplay !== undefined) {
            $info.text('of ' + info.recordsDisplay.toLocaleString() + ' items');
        }
    }

    function enhanceFcDtControls() {
        var $ = window.jQuery;
        var $wrapper = $('#faculty-table_wrapper');
        if (!$wrapper.length) {
            return;
        }

        var $searchSlot = $('#fcDtSearch');
        var $footer = $('#fcDtFooter');

        if ($searchSlot.length && !$searchSlot.find('.dataTables_filter').length) {
            var $filter = $wrapper.find('.dataTables_filter').first();
            if ($filter.length) {
                $filter.find('input')
                    .addClass('form-control shadow-none')
                    .attr('placeholder', 'Search')
                    .attr('aria-label', 'Search faculty');
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
            updateFcDtCount();
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
        updateFcDtCount();
    }

    (function whenReady(tries) {
        var $ = window.jQuery;
        tries = tries || 0;
        if ($ && $.fn.DataTable && $.fn.DataTable.isDataTable('#faculty-table')) {
            enhanceFcDtControls();
            $('#faculty-table').on('draw.dt', function () {
                if (!$('#fcDtFooter .dataTables_paginate').length) {
                    $('#fcDtFooter').data('dtReady', false);
                }
                enhanceFcDtControls();
            });
        } else if (tries < 100) {
            setTimeout(function () { whenReady(tries + 1); }, 100);
        }
    })(0);
})();
</script>

<script>
/* ---- Column Visibility (drives the live Yajra DataTable via its API) ----
   No column merge/swap on this page, so the standard .column().visible() API is
   safe. Persisted to localStorage. */
$(function () {
    var TABLE = '#faculty-table';
    var fcColStorageKey = 'facultyMaster:hiddenColumns:v1';

    function fcGetHiddenCols() {
        try {
            var raw = localStorage.getItem(fcColStorageKey);
            var arr = raw ? JSON.parse(raw) : [];
            return Array.isArray(arr) ? arr : [];
        } catch (e) { return []; }
    }
    function fcPersistHiddenCols(arr) {
        try { localStorage.setItem(fcColStorageKey, JSON.stringify(arr)); } catch (e) {}
    }

    function setupFcColumns(dt) {
        if (!dt) { return; }
        var hidden = fcGetHiddenCols();

        dt.columns().every(function () {
            var idx = this.index();
            this.visible(hidden.indexOf(idx) === -1, false);
        });
        dt.columns.adjust();

        var $grid = $('#fcColumnToggleGrid');
        if (!$grid.length) { return; }
        $grid.empty();

        dt.columns().every(function () {
            var idx = this.index();
            var title = $(this.header()).text().replace(/\s+/g, ' ').trim();
            if (!title) { return; }

            var inputId = 'fccolvis_' + idx;
            var $cell = $('<div class="col-12 col-sm-6 col-md-4"></div>');
            var $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                .attr('for', inputId);
            var $cb = $('<input type="checkbox" class="form-check-input m-0">')
                .attr('id', inputId)
                .prop('checked', hidden.indexOf(idx) === -1);

            $cb.on('change', function () {
                var h = fcGetHiddenCols();
                var pos = h.indexOf(idx);
                if (this.checked) {
                    if (pos !== -1) h.splice(pos, 1);
                } else {
                    if (pos === -1) h.push(idx);
                }
                fcPersistHiddenCols(h);
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
            setupFcColumns($(TABLE).DataTable());
        } else if (tries < 100) {
            setTimeout(function () { whenReady(tries + 1); }, 100);
        }
    })();
});
</script>
@endpush
