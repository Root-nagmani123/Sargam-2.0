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
                <div>
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

                    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-end gap-3 mb-4">
                        <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                            <button type="button" class="btn programme-dt-btn-columns" id="facultyBtnColumns"
                                data-bs-toggle="modal" data-bs-target="#facultyColumnVisibilityModal"
                                title="Show / hide columns">
                                <span>Columns</span>
                                <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                            </button>
                            <div id="facultyDtSearch" class="programme-dt-search" data-dt-search-for="faculty-table"></div>
                        </div>
                    </div>

                    <div class="programme-dt-panel">
                        <div class="table-responsive">
                            {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table']) !!}
                        </div>
                        <div id="facultyDtFooter" class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                            data-dt-footer-for="faculty-table"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Column Visibility Modal -->
    <div class="modal fade" id="facultyColumnVisibilityModal" tabindex="-1"
        aria-labelledby="facultyColumnVisibilityLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-2">
                    <h5 class="modal-title fw-bold" id="facultyColumnVisibilityLabel">Column Visibility</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-0">
                    <hr class="mt-0">
                    <div class="row g-3" id="facultyColumnToggleGrid"></div>
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

{{-- Search box, pagination and the "Showing N of M items" count are relocated into
     #facultyDtSearch / #facultyDtFooter by the global enhancer
     (public/js/datatable-global-ui.js) via the data-dt-search-for /
     data-dt-footer-for hooks above. Do NOT add a page-local copy of that logic. --}}

<script>
/* ---- Column show / hide (DataTables API), matching the other listing pages ---- */
$(function () {
    var TABLE_ID = '#faculty-table';
    var facultyColStorageKey = 'facultyGrid:hiddenColumns:v1';

    function facultyGetHiddenCols() {
        try {
            var raw = localStorage.getItem(facultyColStorageKey);
            var arr = raw ? JSON.parse(raw) : [];
            return Array.isArray(arr) ? arr : [];
        } catch (e) {
            return [];
        }
    }

    function facultyPersistHiddenCols(arr) {
        try { localStorage.setItem(facultyColStorageKey, JSON.stringify(arr)); } catch (e) {}
    }

    function setupFacultyColumns(dt) {
        if (!dt) {
            return;
        }
        var hidden = facultyGetHiddenCols();

        // Apply saved visibility — DataTables keeps this across redraws / ajax reloads.
        dt.columns().every(function () {
            var idx = this.index();
            this.visible(hidden.indexOf(idx) === -1, false);
        });
        dt.columns.adjust();

        var $grid = $('#facultyColumnToggleGrid');
        if (!$grid.length) {
            return;
        }
        $grid.empty();

        dt.columns().every(function () {
            var idx = this.index();
            var title = $(this.header()).text().replace(/\s+/g, ' ').trim();
            if (!title) {
                return;
            }

            var inputId = 'facultycolvis_' + idx;
            var $cell = $('<div class="col-12 col-sm-6 col-md-4"></div>');
            var $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-1 px-3 py-2 mb-0 w-100"></label>')
                .attr('for', inputId);
            var $cb = $('<input type="checkbox" class="form-check-input m-0">')
                .attr('id', inputId)
                .prop('checked', hidden.indexOf(idx) === -1);

            $cb.on('change', function () {
                var h = facultyGetHiddenCols();
                var pos = h.indexOf(idx);
                if (this.checked) {
                    if (pos !== -1) h.splice(pos, 1);
                } else {
                    if (pos === -1) h.push(idx);
                }
                facultyPersistHiddenCols(h);
                dt.column(idx).visible(this.checked, false);
                dt.columns.adjust();
            });

            $label.append($cb).append($('<span></span>').text(title));
            $cell.append($label);
            $grid.append($cell);
        });
    }

    // Yajra initialises the table itself. Handle both orders: if it is already up
    // we build now, otherwise init.dt fires for us. Both paths are idempotent.
    $(document).on('init.dt', function (e, settings) {
        if (settings.nTable && settings.nTable.id === 'faculty-table') {
            setupFacultyColumns(new $.fn.dataTable.Api(settings));
        }
    });

    if ($.fn.DataTable.isDataTable(TABLE_ID)) {
        setupFacultyColumns($(TABLE_ID).DataTable());
    }
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
