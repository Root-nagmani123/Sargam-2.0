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
    </style>
@endpush

@section('setup_content')
    <div class="container-fluid px-2 px-sm-3 px-md-4 pb-4 pb-lg-5 faculty-index-page">
        <x-breadcrum title="Faculty"></x-breadcrum>
        <!--<x-session_message />-->
        <div id="status-msg" class="mb-3"></div>

        <div class="datatables">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden card-faculty-accent">
                <div class="card-header bg-body-secondary bg-opacity-50 border-bottom border-secondary border-opacity-25 py-3 px-3 px-md-4">
                    <div class="row g-3 align-items-center">
                        <div class="col min-w-0">
                            <div class="d-flex align-items-start gap-3 min-w-0">
                                <div class="flex-shrink-0 rounded-3 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center d-none d-sm-flex"
                                    style="width: 2.75rem; height: 2.75rem;" aria-hidden="true">
                                    <span class="material-symbols-rounded fs-4">groups</span>
                                </div>
                                <div class="min-w-0">
                                    <h1 class="h4 fw-bold mb-1" style="color: #004a93;">Faculty</h1>
                                    <p class="small text-body-secondary mb-0 text-truncate">View, add, export, and manage faculty records</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-auto">
                            <div class="faculty-actions d-flex flex-wrap gap-2 justify-content-lg-end">
                                <a href="{{ route('faculty.create') }}"
                                    class="btn btn-primary d-inline-flex align-items-center justify-content-center gap-2 rounded-3 shadow-sm px-3 py-2"
                                    style="background-color: #004a93; border-color: #004a93;"
                                    aria-label="Add new faculty">
                                    <span class="material-symbols-rounded fs-5" aria-hidden="true">add</span>
                                    <span>Add faculty</span>
                                </a>
                                <a href="{{ route('faculty.excel.export') }}"
                                    class="btn btn-outline-primary d-inline-flex align-items-center justify-content-center gap-2 rounded-3 shadow-sm px-3 py-2"
                                    style="border-color: #004a93; color: #004a93;"
                                    aria-label="Export faculty to Excel">
                                    <span class="material-symbols-rounded fs-5" aria-hidden="true">export_notes</span>
                                    <span>Export Excel</span>
                                </a>
                                <a href="{{ route('faculty.printBlank') }}"
                                    class="btn btn-outline-success d-inline-flex align-items-center justify-content-center gap-2 rounded-3 px-3 py-2 shadow-sm"
                                    aria-label="Print blank form">
                                    <span class="material-symbols-rounded fs-5" aria-hidden="true">print</span>
                                    <span>Print blank form</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body p-3 p-md-4 bg-body-tertiary bg-opacity-25">
                    <div class="table-responsive">
                        {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100']) !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {!! $dataTable->scripts() !!}

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
                    Swal.fire({
                        title: 'Deleting...',
                        text: 'Please wait while we delete the faculty record.',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        didOpen: () => {
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
                                }).then(() => {
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
@endpush
