@extends('admin.layouts.master')

@section('title', 'Faculty')

@push('styles')
    <style>
        .faculty-index-page .card-faculty-accent {
            border-left: 4px solid #004a93;
        }
        @media (min-width: 768px) {
            .faculty-index-page .faculty-actions .btn {
                white-space: nowrap;
            }
        }
        .btn-faculty-export {
            border-color: #004a93;
            color: #004a93;
        }
        .btn-faculty-export:hover,
        .btn-faculty-export:focus,
        .btn-faculty-export:active {
            background-color: #004a93;
            border-color: #004a93;
            color: #fff !important;
        }
    </style>
@endpush

@section('setup_content')
<div class="container-fluid">
<x-breadcrum title="Faculty"></x-breadcrum>
    <!--<x-session_message />-->
    <div id="status-msg"></div>

    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" style="border-left:4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4 class="fw-semibold text-primary mb-0" style="color:#004a93 !important;">
                                Faculty
                            </h4>
                        </div>

                        <div class="col-6">
                            <div class="d-flex justify-content-end align-items-center gap-3">

                                <!-- Add Faculty -->
                                <a href="{{ route('faculty.create') }}"
                                    class="btn btn-primary d-flex align-items-center gap-1 shadow-sm"
                                    style="background-color:#004a93; border-color:#004a93;"
                                    aria-label="Add New Faculty">
                                    <span class="material-symbols-rounded fs-5">add</span>
                                    Add Faculty
                                </a>

                                <!-- Export Excel -->
                                <a href="{{ route('faculty.excel.export') }}"
                                    class="btn btn-outline-primary btn-faculty-export d-flex align-items-center gap-1 shadow-sm"
                                    aria-label="Export Faculty Excel">
                                    <span class="material-symbols-rounded fs-5">export_notes</span>
                                    Export Excel
                                </a>
                                <a href="{{ route('faculty.printBlank') }}"  class="btn btn-outline-success">
									<i class="material-icons">print</i> Print Blank Form
								</a>

                            </div>
                        </div>
                    </div>

                    <hr>
                    {!! $dataTable->table(['class' => 'table', 'data-sargam-dt-ui' => 'false']) !!}
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {!! $dataTable->scripts() !!}

<script>
// Move the Yajra DataTable's search / pagination / length / count into the
// programme-dt-* slots so they render (the table's DOM routes them into hidden
// rows by default). Mirrors the programme / course-group-type index pattern.
$(function () {
    function enhanceFacultyDtControls() {
        var $wrapper = $('#faculty-table_wrapper');
        if (!$wrapper.length) { return; }

        var $searchSlot = $('#facultyDtSearch');
        var $footer = $('#facultyDtFooter');

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

        if ($footer.data('dtReady')) { updateFacultyDtCount(); return; }
        if (!$footer.length) { return; }
        // If the global DataTable UI already populated this footer, don't duplicate it.
        if ($footer.find('.dataTables_info, .dataTables_paginate, .dataTables_length').length) {
            $footer.data('dtReady', true);
            updateFacultyDtCount();
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
            // Detach (not empty) the select so DataTables' change.DT handler survives.
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
    }

    function updateFacultyDtCount() {
        if (!$.fn.DataTable.isDataTable('#faculty-table')) { return; }
        var info = $('#faculty-table').DataTable().page.info();
        var $info = $('#facultyDtFooter .dataTables_info');
        if ($info.length && info && info.recordsDisplay !== undefined) {
            $info.text('of ' + info.recordsDisplay.toLocaleString() + ' items');
        }
    }

    // Yajra initialises the table itself; wait for it, then wire up the slots.
    var facultyInitTimer = setInterval(function () {
        if (!$.fn.DataTable.isDataTable('#faculty-table')) { return; }
        clearInterval(facultyInitTimer);

        var $wrapper = $('#faculty-table_wrapper');
        enhanceFacultyDtControls();
        updateFacultyDtCount();

        $('#faculty-table').on('draw.dt', function () {
            if ($wrapper.find('.dataTables_paginate').length && !$('#facultyDtFooter .dataTables_paginate').length) {
                $('#facultyDtFooter').empty().data('dtReady', false);
                enhanceFacultyDtControls();
            }
            updateFacultyDtCount();
        });

        setTimeout(function () { enhanceFacultyDtControls(); updateFacultyDtCount(); }, 300);
    }, 50);
});
</script>

<script>
// Delete Faculty with SweetAlert Confirmation
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
        confirmButtonText: '<i class="material-icons" style="font-size:14px;vertical-align:middle;">delete</i> Yes, delete it!',
        cancelButtonText: '<i class="material-icons" style="font-size:14px;vertical-align:middle;">close</i> Cancel',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading state
            Swal.fire({
                title: 'Deleting...',
                text: 'Please wait while we delete the faculty record.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Send AJAX delete request
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
                        }).then(() => {
                            // Reload the DataTable
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
$(document).ready(function () {
    var toastMsg = sessionStorage.getItem('facultyToast');
    if (toastMsg) {
        sessionStorage.removeItem('facultyToast');
        toastr.options = {
            "timeOut": "4000",
            "extendedTimeOut": "1000",
            "positionClass": "toast-top-right",
            "closeButton": true,
            "progressBar": true
        };
        toastr.success(toastMsg);
        $('#toast-container').css('top', '80px');
    }
});
</script>
@endpush
