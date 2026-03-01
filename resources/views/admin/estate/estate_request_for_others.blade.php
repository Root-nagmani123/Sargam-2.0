@extends('admin.layouts.master')

@section('title', 'Estate Request for Others - Sargam')

@section('setup_content')
<div class="container-fluid py-4">
    <x-breadcrum title="Estate Request for Others"></x-breadcrum>

    <div class="card shadow-sm border-0 rounded-3 overflow-hidden">
        <div class="card-header bg-body border-0 py-3 px-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-stretch align-items-md-center gap-3">
                <h5 class="mb-0 fw-semibold">Estate Request for Others</h5>
                <a href="{{ route('admin.estate.add-other-estate-request') }}" class="btn btn-primary d-inline-flex align-items-center gap-2 flex-shrink-0">
                    <i class="material-symbols-rounded">add</i>
                    <span>Add Other Estate</span>
                </a>
            </div>
        </div>
        <div class="card-body p-0 p-md-4">
            <div class="table-responsive">
                <table class="table align-middle mb-0" id="estateRequestTable">
                    <thead>
                        <tr>
                            <th class="w-auto pe-2">
                                <input type="checkbox" class="form-check-input" id="select_all" aria-label="Select all">
                            </th>
                            <th>S.No.</th>
                            <th>Request ID</th>
                            <th>Employee Name</th>
                            <th>Father's Name</th>
                            <th>Section</th>
                            <th>Date of Joining in Academy</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="pe-2">
                                <input type="checkbox" class="form-check-input" aria-label="Select row">
                            </td>
                            <td>1</td>
                            <td><span class="fw-medium">Oth-req-1</span></td>
                            <td>Karan Pillee</td>
                            <td>Karan Pillee</td>
                            <td>Karan Pillee</td>
                            <td>Karan Pillee</td>
                            <td>
                                <div class="d-inline-flex gap-1">
                                    <a href="javascript:void(0)" class="text-primary p-1" title="Edit" aria-label="Edit">
                                        <i class="material-symbols-rounded">edit</i>
                                    </a>
                                    <a href="javascript:void(0)" class="text-primary p-1" title="Delete" aria-label="Delete">
                                        <i class="material-symbols-rounded">delete</i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                </div>
                <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2 flex-shrink-0" id="btn-open-add-other-request">
                    <i class="en material-symbols-rounded" style="font-size: 1.25rem;">add</i>
                    <span>Add Other Estate</span>
                </button>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show d-flex align-items-center rounded-3 shadow-sm" role="alert">
                    <i class="bi bi-check-circle-fill me-2 flex-shrink-0" aria-hidden="true"></i>
                    <span class="flex-grow-1">{{ session('success') }}</span>
                    <button type="button" class="btn-close flex-shrink-0" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
<hr class="my-2">
            <!-- Table Section -->
            <div class="table-responsive">
                {!! $dataTable->table(['class' => 'table table-bordered table-hover align-middle', 'aria-describedby' => 'estate-request-caption']) !!}
                <div id="estate-request-caption" class="visually-hidden">Estate Request for Others list</div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    {!! $dataTable->scripts() !!}
    <script>
    $(document).ready(function() {
        var deleteOtherRequestUrl = '';
        var addEditModalEl = document.getElementById('addEditOtherRequestModal');
        var addEditModal = addEditModalEl ? new bootstrap.Modal(addEditModalEl) : null;

        // ---- Add: open modal with empty form ----
        $('#btn-open-add-other-request').on('click', function() {
            $('#addEditOtherRequestModalLabel').text('Add Other Estate Request');
            $('#other_request_id').val('');
            $('#modal_employee_name, #modal_father_name, #modal_section, #modal_doj_academy').val('');
            $('#addEditOtherRequestFormErrors').addClass('d-none').find('ul').empty();
            if (addEditModal) addEditModal.show();
        });

        // ---- Edit: open modal with row data ----
        $(document).on('click', '.btn-edit-other-request', function() {
            var $btn = $(this);
            $('#addEditOtherRequestModalLabel').text('Edit Other Estate Request');
            $('#other_request_id').val($btn.data('id') || '');
            $('#modal_employee_name').val($btn.data('employee_name') || '');
            $('#modal_father_name').val($btn.data('father_name') || '');
            $('#modal_section').val($btn.data('section') || '');
            $('#modal_doj_academy').val($btn.data('doj_academy') || '');
            $('#addEditOtherRequestFormErrors').addClass('d-none').find('ul').empty();
            if (addEditModal) addEditModal.show();
        });

        // ---- Form submit via AJAX ----
        $('#formAddEditOtherRequest').on('submit', function(e) {
            e.preventDefault();
            var $form = $(this);
            var $errors = $('#addEditOtherRequestFormErrors');
            var $btn = $('#btnSubmitOtherRequest');
            $errors.addClass('d-none').find('ul').empty();
            $btn.prop('disabled', true);

            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: $form.serialize(),
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                success: function(response) {
                    if (addEditModal) addEditModal.hide();
                    if (response.success && response.message) {
                        $('#estateRequestTable').DataTable().ajax.reload(null, false);
                        var alertHtml = '<div class="alert alert-success alert-dismissible fade show d-flex align-items-center rounded-3 shadow-sm" role="alert"><i class="bi bi-check-circle-fill me-2 flex-shrink-0"></i><span class="flex-grow-1">' + response.message + '</span><button type="button" class="btn-close flex-shrink-0" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                        $('#estateRequestCardBody').find('.alert-success').remove();
                        $('#estateRequestCardBody').prepend(alertHtml);
                        setTimeout(function() { $('#estateRequestCardBody .alert-success').fadeOut(); }, 4000);
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        var $ul = $errors.removeClass('d-none').find('ul');
                        $.each(xhr.responseJSON.errors, function(_, msgs) {
                            $.each(msgs, function(__, m) { $ul.append('<li>' + m + '</li>'); });
                        });
                    } else {
                        var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Something went wrong. Please try again.';
                        $errors.removeClass('d-none').find('ul').html('<li>' + msg + '</li>');
                    }
                },
                complete: function() {
                    $btn.prop('disabled', false);
                }
            });
        });

        // ---- Delete ----
        $(document).on('click', '.btn-delete-other-request', function(e) {
            e.preventDefault();
            deleteOtherRequestUrl = $(this).data('url');
            var modal = new bootstrap.Modal(document.getElementById('deleteOtherRequestModal'));
            modal.show();
        });

        $('#confirmDeleteOtherRequestBtn').on('click', function() {
            if (!deleteOtherRequestUrl) return;
            $.ajax({
                url: deleteOtherRequestUrl,
                type: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}',
                },
                success: function(response) {
                    bootstrap.Modal.getInstance(document.getElementById('deleteOtherRequestModal')).hide();
                    if (response.success) {
                        $('#estateRequestTable').DataTable().ajax.reload(null, false);
                        var alertHtml = '<div class="alert alert-success alert-dismissible fade show d-flex align-items-center rounded-3 shadow-sm" role="alert"><i class="bi bi-check-circle-fill me-2 flex-shrink-0"></i><span class="flex-grow-1">' + response.message + '</span><button type="button" class="btn-close flex-shrink-0" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                        $('#estateRequestCardBody').find('.alert-success').remove();
                        $('#estateRequestCardBody').prepend(alertHtml);
                        setTimeout(function() { $('#estateRequestCardBody .alert-success').fadeOut(); }, 4000);
                    }
                },
                error: function(xhr) {
                    bootstrap.Modal.getInstance(document.getElementById('deleteOtherRequestModal')).hide();
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to delete.';
                    var alertHtml = '<div class="alert alert-danger alert-dismissible fade show" role="alert">' + msg + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                    $('#estateRequestCardBody').find('.alert-danger').remove();
                    $('#estateRequestCardBody').prepend(alertHtml);
                }
            });
            deleteOtherRequestUrl = '';
        });
    });
    </script>
@endpush
