@extends('admin.layouts.master')

@section('title', 'Estate Request for Others - Sargam')

@section('setup_content')
<div class="container-fluid py-4">
    <x-breadcrum title="Estate Request for Others"></x-breadcrum>

    <!-- Page Card -->
    <div class="card">
        <div class="card-body p-4 p-lg-5" id="estateRequestCardBody">
            <!-- Header -->
            <div class="d-flex flex-column flex-md-row flex-wrap align-items-start align-items-md-center justify-content-between gap-3 mb-4">
                <div>
                    <h1 class="h4 fw-semibold mb-1">Estate Request for Others</h1>
                    <p class="text-muted small mb-0">View and manage estate requests submitted on behalf of others.</p>
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
                {!! $dataTable->table(['class' => 'table align-middle mb-0', 'aria-describedby' => 'estate-request-caption']) !!}
            </div>
            <div id="estate-request-caption" class="visually-hidden">Estate Request for Others list</div>
        </div>
    </div>
</div>

<!-- Add / Edit Other Estate Request modal -->
<div class="modal fade" id="addEditOtherRequestModal" tabindex="-1" aria-labelledby="addEditOtherRequestModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-3 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-semibold" id="addEditOtherRequestModalLabel">Add Other Estate Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2">
                <div id="addEditOtherRequestFormErrors" class="alert alert-danger d-none" role="alert">
                    <ul class="mb-0 ps-3"></ul>
                </div>
                <form id="formAddEditOtherRequest" method="POST" action="{{ route('admin.estate.add-other-estate-request.store') }}">
                    @csrf
                    <input type="hidden" name="id" id="other_request_id" value="">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="modal_employee_name" class="form-label">Employee Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="modal_employee_name" name="employee_name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="modal_father_name" class="form-label">Father Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="modal_father_name" name="father_name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="modal_section" class="form-label">Section <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="modal_section" name="section" required>
                        </div>
                        <div class="col-md-6">
                            <label for="modal_doj_academy" class="form-label">DOJ in Academy <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="date" class="form-control" id="modal_doj_academy" name="doj_academy" required>
                                <span class="input-group-text"><i class="bi bi-calendar"></i></span>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-success" id="btnSubmitOtherRequest">
                            <i class="bi bi-save me-2"></i>Save
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle me-2"></i>Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete confirmation modal -->
<div class="modal fade" id="deleteOtherRequestModal" tabindex="-1" aria-labelledby="deleteOtherRequestModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-3 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-semibold" id="deleteOtherRequestModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2">
                Are you sure you want to delete this estate request? This action cannot be undone.
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteOtherRequestBtn">Delete</button>
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
