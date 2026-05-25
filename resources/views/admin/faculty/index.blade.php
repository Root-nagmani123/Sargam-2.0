@extends('admin.layouts.master')

@section('title', 'Faculty')

@section('setup_content')
<style>
    .faculty-page-shell .fac-card {
        border: none;
        border-radius: 1rem;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
        overflow: hidden;
    }
    .faculty-page-shell .fac-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #e9ecef;
        background: transparent;
    }
    .faculty-page-shell .fac-title {
        font-size: 1.2rem;
        font-weight: 700;
        color: #004a93;
        margin: 0;
    }
    .faculty-page-shell .fac-actions .btn {
        font-size: 0.8125rem;
        font-weight: 500;
        border-radius: 0.5rem;
        transition: all 0.2s ease;
    }
    .faculty-page-shell .fac-actions .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
    }
    .faculty-page-shell .table thead th {
        background: #f8f9fa;
        font-weight: 600;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        color: #495057;
        border-bottom: 2px solid #dee2e6;
        padding: 0.65rem 1rem;
        white-space: nowrap;
    }
    .faculty-page-shell .table tbody td {
        padding: 0.65rem 1rem;
        vertical-align: middle;
        border-color: #f0f0f0;
        font-size: 0.875rem;
    }
    .faculty-page-shell .table tbody tr {
        transition: background-color 0.15s ease;
    }
    .faculty-page-shell .table tbody tr:hover {
        background-color: rgba(0, 74, 147, 0.05);
    }
    /* Compact DataTable pagination */
    .faculty-page-shell .dataTables_paginate .paginate_button {
        font-size: 0.8rem !important;
        padding: 0.25rem 0.65rem !important;
        min-width: 1.85rem;
        border-radius: 0.45rem !important;
        margin: 0 2px !important;
        border: 1px solid #dee2e6 !important;
        background: #f8f9fa !important;
        color: #495057 !important;
        font-weight: 500;
    }
    .faculty-page-shell .dataTables_paginate .paginate_button.current {
        background: #004a93 !important;
        border-color: #004a93 !important;
        color: #fff !important;
        font-weight: 600;
        box-shadow: 0 2px 8px rgba(0, 74, 147, 0.3);
    }
    .faculty-page-shell .dataTables_paginate .paginate_button:hover:not(.current) {
        background: rgba(0, 74, 147, 0.08) !important;
        border-color: #004a93 !important;
        color: #004a93 !important;
    }
    .faculty-page-shell .dataTables_paginate .paginate_button.disabled {
        opacity: 0.4;
        pointer-events: none;
    }
    .faculty-page-shell .dataTables_info,
    .faculty-page-shell .dataTables_length label,
    .faculty-page-shell .dataTables_filter label {
        font-size: 0.8125rem;
        color: #6c757d;
    }
    .faculty-page-shell .dataTables_length select,
    .faculty-page-shell .dataTables_filter input {
        font-size: 0.8125rem;
        border-radius: 0.4rem;
    }
</style>
<div class="container-fluid faculty-page-shell">
    <x-breadcrum title="Faculty"></x-breadcrum>
    <!--<x-session_message />-->
    <div id="status-msg"></div>

    <div class="datatables">
        <div class="card fac-card">
            <div class="fac-header d-flex flex-wrap align-items-center justify-content-between gap-3">
                <h4 class="fac-title d-flex align-items-center gap-1">
                    <span class="material-icons material-symbols-rounded" style="font-size:1.4rem;">school</span>
                    Faculty
                </h4>
                <div class="d-flex flex-wrap align-items-center gap-2 fac-actions">
                    <!-- Add Faculty -->
                    <a href="{{ route('faculty.create') }}"
                        class="btn btn-primary d-inline-flex align-items-center gap-1 shadow-sm btn-sm"
                        style="background:#004a93; border-color:#004a93;"
                        aria-label="Add New Faculty">
                        <span class="material-icons material-symbols-rounded" style="font-size:18px;">add</span>
                        Add Faculty
                    </a>

                    <!-- Export Excel -->
                    <a href="{{ route('faculty.excel.export') }}"
                        class="btn btn-outline-primary d-inline-flex align-items-center gap-1 btn-sm"
                        style="border-color:#004a93; color:#004a93;"
                        aria-label="Export Faculty Excel">
                        <span class="material-icons material-symbols-rounded" style="font-size:18px;">download</span>
                        Export Excel
                    </a>

                    <!-- Print Blank Form -->
                    <a href="{{ route('faculty.printBlank') }}"
                        class="btn btn-outline-success d-inline-flex align-items-center gap-1 btn-sm"
                        aria-label="Print Blank Form">
                        <span class="material-icons material-symbols-rounded" style="font-size:18px;">print</span>
                        Print Blank Form
                    </a>
                </div>
            </div>
            <div class="card-body pt-3 px-4 pb-4">
                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table align-middle mb-0']) !!}
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
