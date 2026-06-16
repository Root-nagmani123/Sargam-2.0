@extends('admin.layouts.master')

@section('title', 'Member')

@section('setup_content')
<div class="container-fluid">
<x-breadcrum title="Member"></x-breadcrum>
 <div id="status-msg"></div>
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" >
            <div class="card-body">
                <div class="row">
                        <div class="col-6">
                            <h4>Member</h4>
                        </div>
                        <div class="col-6">
                            <div class="float-end gap-2">
                                <a href="{{ route('member.create') }}" class="btn btn-primary">+ Add Member</a>
                                {{-- <a href="#" class="btn btn-success" data-bs-toggle="modal"
                                    data-bs-target="#vertical-center-scroll-modal">Bulk Upload</a> --}}
                                <a href="{{ route('member.excel.export') }}" class="btn btn-secondary">Export</a>
                            </div>
                        </div>
                    </div>
                    <!-- Vertically centered modal -->
                    <div class="modal fade" id="vertical-center-scroll-modal" tabindex="-1"
                        aria-labelledby="vertical-center-modal" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header d-flex align-items-center">
                                    <h4 class="modal-title" id="myLargeModalLabel">
                                        Bulk Upload for member
                                    </h4>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form action="" method="POST">
                                        <label for="" class="form-label">Upload CSV</label>
                                        <input type="file" name="file" id="file" class="form-control">
                                    </form>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit"
                                        class="btn bg-success-subtle text-success  waves-effect text-start">
                                        Submit
                                    </button>
                                    <button type="button"
                                        class="btn bg-danger-subtle text-danger  waves-effect text-start"
                                        data-bs-dismiss="modal">
                                        Close
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="table-responsive">
                        {!! $dataTable->table(['class' => 'table']) !!}
                    </div>
            </div>
        </div>
        <!-- end Zero Configuration -->
    </div>
</div>


@endsection

@push('scripts')
{{ $dataTable->scripts() }}

<script>
$(document).ready(function() {
    // Handle member status toggle
    $(document).on('change', '.member-status-toggle', function(e) {
        const $checkbox = $(this);
        const memberId = $checkbox.data('id');
        const isChecked = $checkbox.is(':checked');
        const newStatus = isChecked ? 1 : 2; // checked=Active(1), unchecked=Inactive(2)
        const actionText = isChecked ? 'activate' : 'deactivate';

        // Show confirmation dialog
        Swal.fire({
            title: 'Confirm Action',
            text: `Are you sure you want to ${actionText} this member?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: `Yes, ${actionText}!`
        }).then((result) => {
            if (result.isConfirmed) {
                // Make AJAX request to toggle status
                $.ajax({
                    url: `/member/${memberId}/toggle-status`,
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Content-Type': 'application/json'
                    },
                    data: JSON.stringify({ status: newStatus }),
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Reload the DataTable silently
                            $('#member-table').DataTable().ajax.reload();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message || 'Error updating status'
                            });
                            // Revert checkbox state
                            $checkbox.prop('checked', !isChecked);
                        }
                    },
                    error: function(xhr) {
                        const response = xhr.responseJSON || {};
                        const message = response.message || 'Error toggling status';

                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: message
                        });
                        // Revert checkbox state
                        $checkbox.prop('checked', !isChecked);
                    }
                });
            } else {
                // User cancelled, revert checkbox
                $checkbox.prop('checked', !isChecked);
            }
        });
    });

    $(document).on('click', '.member-delete-btn', function(e) {
        e.preventDefault();

        if ($(this).is(':disabled')) {
            return false;
        }

        const deleteUrl = $(this).data('delete-url');
        const $btn = $(this);
        const $row = $btn.closest('tr');

        if (!confirm('Are you sure you want to delete this member?')) {
            return false;
        }

        $.ajax({
            url: deleteUrl,
            type: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            dataType: 'json',
            success: function(response) {
                // Show success message
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: response.message || 'Member deleted successfully',
                    timer: 1500
                }).then(function() {
                    // Reload the DataTable without refreshing the page
                    $('#member-table').DataTable().ajax.reload();
                });
            },
            error: function(xhr) {
                const response = xhr.responseJSON || {};
                const message = response.message || 'Error deleting member';

                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: message
                });
            }
        });
    });
});
</script>
@endpush
