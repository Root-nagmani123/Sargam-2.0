@extends('admin.layouts.master')

@section('title', 'Member')

@section('setup_content')
<div class="container-fluid">
<x-breadcrum title="Member"></x-breadcrum>
 <div id="status-msg"></div>
    <div class="datatables">
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-body p-3 p-md-4">
                <div class="modal fade" id="vertical-center-scroll-modal" tabindex="-1"
                    aria-labelledby="vertical-center-modal" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                        <div class="modal-content rounded-3 border-0 shadow">
                            <div class="modal-header d-flex align-items-center border-0 pb-0">
                                <h4 class="modal-title fs-6 fw-bold text-primary-emphasis" id="myLargeModalLabel">
                                    Bulk Upload for member
                                </h4>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body pt-2">
                                <form action="" method="POST">
                                    <label for="file" class="form-label small fw-medium">Upload CSV</label>
                                    <input type="file" name="file" id="file" class="form-control rounded-2">
                                </form>
                            </div>
                            <div class="modal-footer border-0 pt-0">
                                <button type="submit"
                                    class="btn btn-success btn-sm px-4 fw-semibold">
                                    Submit
                                </button>
                                <button type="button"
                                    class="btn btn-outline-secondary btn-sm px-4"
                                    data-bs-dismiss="modal">
                                    Close
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end mb-3">
                    <div class="mem-search-wrap">
                        <i class="material-icons material-symbols-rounded mem-search-icon" aria-hidden="true">search</i>
                        <input type="search" id="memSearch" class="form-control" placeholder="Search"
                            aria-controls="member-table" autocomplete="off">
                    </div>
                </div>

                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100', 'id' => 'member-table']) !!}
                </div>
            </div>
        </div>
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

    var table = $tableEl.DataTable();

    var $paginate = $('#member-table_wrapper .dataTables_paginate');
    if ($paginate.length) {
        $paginate.appendTo('#memPaginationCell');
    }

    $tableEl.on('draw.dt', function () {
        var info = table.page.info();
        $('#memTotalInfo').text('of ' + info.recordsTotal + ' items');
        $('#memPerPage').val(table.page.len());
    }).trigger('draw.dt');

    $('#memSearch').on('input', function () {
        clearTimeout(window._memSearchTimer);
        var q = $(this).val();
        window._memSearchTimer = setTimeout(function () {
            table.search(q).draw();
        }, 350);
    });

    $('#memPerPage').on('change', function () {
        table.page.len(parseInt($(this).val(), 10)).draw();
    });
});
</script>
@endpush
