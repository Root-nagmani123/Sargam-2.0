@extends('admin.layouts.master')

@section('title', 'Faculty')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/faculty-index-admin.css') }}?v={{ @filemtime(public_path('css/faculty-index-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid fc-index-page">
    <x-breadcrum title="Faculty">
        <a href="{{ route('faculty.create') }}"
            class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 rounded-2 fw-semibold text-nowrap shadow-sm">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Add Faculty</span>
        </a>
    </x-breadcrum>

    <div id="status-msg"></div>

    <div class="d-flex flex-wrap align-items-center justify-content-end gap-2 fc-toolbar-actions">
        <a href="{{ route('faculty.printBlank') }}"
            class="fc-btn-outline"
            aria-label="Print blank faculty form">
            <i class="bi bi-printer" aria-hidden="true"></i>
            <span>Print Blank Form</span>
        </a>
        <a href="{{ route('faculty.excel.export') }}"
            class="fc-btn-outline"
            aria-label="Download faculty data">
            <i class="bi bi-download" aria-hidden="true"></i>
            <span>Download</span>
        </a>
    </div>

    <div class="card fc-dt-card shadow-sm rounded-3 overflow-hidden">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center justify-content-end gap-3 mb-4">
                <div id="fcDtSearch" class="programme-dt-search ms-lg-auto" data-dt-search-for="faculty-table"></div>
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

            var $cells = $row.find('td');
            if ($cells.length < 9) {
                return;
            }

            var $statusCell = $cells.eq(7);
            var $actionCell = $cells.eq(8);
            var $toggleWrap = $statusCell.find('.form-check').first();
            var $toggle = $toggleWrap.find('.status-toggle').first();

            if ($toggle.length) {
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

                var $editLink = $actionCell.find('a[href*="edit"]').first().detach();
                var $viewLink = $actionCell.find('a[href*="show"]').first().detach();
                var $deleteBtn = $actionCell.find('.delete-faculty-btn').first().detach();

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
@endpush
