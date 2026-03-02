@extends('admin.layouts.master')

@section('title', 'Faculty')

@section('setup_content')
<div class="container-fluid faculty-index">
<x-breadcrum title="Faculty"></x-breadcrum>
    <!--<x-session_message />-->
    <div id="status-msg"></div>

    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" style="border-left:4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row faculty-header-row">
                        <div class="col-12 col-md-6">
                            <h4 class="fw-semibold text-primary mb-0" style="color:#004a93 !important;">
                                Faculty
                            </h4>
                        </div>

                        <div class="col-12 col-md-6">
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
                                    class="btn btn-outline-primary d-flex align-items-center gap-1 shadow-sm"
                                    style="border-color:#004a93; color:#004a93;" aria-label="Export Faculty Excel">
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
                    {!! $dataTable->table(['class' => 'table']) !!}
                </div>
            </div>
        </div>
        <!-- end Zero Configuration -->
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
@endpush
