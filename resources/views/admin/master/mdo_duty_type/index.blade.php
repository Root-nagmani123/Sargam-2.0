@extends('admin.layouts.master')

@section('title', 'MDO Duty Type')

@section('setup_content')
<div class="container-fluid mdo-duty-type-index">
    <x-breadcrum title="MDO Duty Type"></x-breadcrum>
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row mdo-duty-type-header-row">
                        <div class="col-6">
                            <!-- left column empty or header title above -->
                            <h4>MDO Duty Type</h4>
                        </div>
                        <div class="col-6">
                            <div class="d-flex justify-content-end align-items-center gap-2">
                                <!-- Add Group Mapping -->
                                <a href="{{ route('master.mdo_duty_type.create') }}" 
                                    class="btn btn-primary add-btn d-flex align-items-center">
                                    <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 24px;">add</i>
                                    Add MDO Duty Type
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
    document.addEventListener('DOMContentLoaded', function() {
        // Handle Add button click
        $(document).on('click', '.add-btn', function(e) {
            e.preventDefault();
            const url = $(this).attr('href');
            openModalWithUrl(url, 'Create MDO Duty Type');
        });

        // Handle Edit links
        $(document).on('click', '.openEditDutyType', function(e) {
            e.preventDefault();
            const url = $(this).attr('href');
            openModalWithUrl(url, 'Edit MDO Duty Type');
        });

        function openModalWithUrl(url, title) {
            const modalEl = document.getElementById('dutyTypeModal');
            const modalTitle = modalEl.querySelector('.modal-title');
            const modalBody = modalEl.querySelector('.modal-body');
            modalTitle.textContent = title || 'MDO Duty Type';
            modalBody.innerHTML = '<div class="text-center p-4">Loading...</div>';

            fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.text())
                .then(html => {
                    modalBody.innerHTML = html;
                })
                .catch(() => {
                    modalBody.innerHTML = '<div class="text-danger">Failed to load form.</div>';
                });

            const bsModal = new bootstrap.Modal(modalEl);
            bsModal.show();
        }

        // Handle AJAX form submit inside modal
        $(document).on('submit', '#dutyTypeForm', function(e) {
            e.preventDefault();
            const form = $(this);
            const submitBtn = form.find('button[type="submit"]');
            const originalText = submitBtn.html();
            
            submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Saving...');

            $.ajax({
                url: form.attr('action'),
                method: form.attr('method') || 'POST',
                data: form.serialize(),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    if (response.success) {
                        updateTableAfterSave(response);
                        bootstrap.Modal.getInstance(document.getElementById('dutyTypeModal'))?.hide();
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message || 'Record saved successfully',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message || 'Something went wrong'
                        });
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        // Validation errors: re-render returned HTML into modal
                        const modalBody = document.querySelector('#dutyTypeModal .modal-body');
                        modalBody.innerHTML = xhr.responseText;
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: xhr.responseJSON?.message || 'Something went wrong'
                        });
                    }
                },
                complete: function() {
                    submitBtn.prop('disabled', false).html(originalText);
                }
            });
        });

        function updateTableAfterSave(payload) {
            // Reload DataTable after create/update
            if (typeof $.fn.DataTable !== 'undefined') {
                const table = $('#mdodutytypemaster-table').DataTable();
                if (table) {
                    table.ajax.reload(null, false); // false = don't reset pagination
                }
            }
        }
    });


    $(document).on('change', '.plain-status-toggle', function() {
        let checkbox = $(this);
        let pk = checkbox.data('id');
        let active_inactive = checkbox.is(':checked') ? 1 : 0;
            var actionText = active_inactive ? 'activate' : 'deactivate';
            var confirmBtnText = active_inactive ? 'Yes, activate' : 'Yes, deactivate';
            var confirmBtnColor = active_inactive ? '#28a745' : '#d33';

            Swal.fire({
                title: 'Are you sure?',
                text: `Are you sure you want to ${actionText} this item?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: confirmBtnColor,
                cancelButtonColor: '#3085d6',
                confirmButtonText: confirmBtnText,
                cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed){
                $.ajax({
                    url: "{{ route('master.mdo_duty_type.status') }}", // route
                    type: "POST",
                    data: {
                        pk: pk,
                        active_inactive: active_inactive,
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(response) {

                        $('#mdodutytypemaster-table').DataTable().ajax.reload(null, false);

                        Swal.fire({
                            icon: 'success',
                            title: 'Updated!',
                            text: response.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                    }
                });

            } else {
                // revert checkbox
                checkbox.prop('checked', !active_inactive);
            }
        });
    });

    $(document).on('click', '.delete-btn', function(e) {
        e.preventDefault();
        let pk = $(this).data('id');
        Swal.fire({
            title: 'Are you sure?',
            text: "This record will be permanently deleted!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed){
                $.ajax({
                    url: "{{ route('master.mdo_duty_type.delete') }}", // route
                    type: "POST",
                    data: {
                        id: pk,
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        $('#mdodutytypemaster-table').DataTable().ajax.reload(null, false);
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: response.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                    }
                });

            } else {
                // revert checkbox
                checkbox.prop('checked', !active_inactive);
            }
        });
    });
</script>

<script>
    
    $(document).on('change', '.plain-status-toggle', function() {
        let checkbox = $(this);
        let pk = checkbox.data('id');
        let active_inactive = checkbox.is(':checked') ? 1 : 0;
        var actionText = active_inactive ? 'activate' : 'deactivate';
        var confirmBtnText = active_inactive ? 'Yes, activate' : 'Yes, deactivate';
        var confirmBtnColor = active_inactive ? '#28a745' : '#d33';

        Swal.fire({
            title: 'Are you sure?',
            text: `Are you sure you want to ${actionText} this item?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: confirmBtnColor,
            cancelButtonColor: '#3085d6',
            confirmButtonText: confirmBtnText,
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed){
                $.ajax({
                    url: "{{ route('master.mdo_duty_type.status') }}",
                    type: "POST",
                    data: {
                        pk: pk,
                        active_inactive: active_inactive,
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        $('#mdodutytypemaster-table').DataTable().ajax.reload(null, false);
                        Swal.fire({
                            icon: 'success',
                            title: 'Updated!',
                            text: response.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                    }
                });
            } else {
                // revert checkbox
                checkbox.prop('checked', !active_inactive);
            }
        });
    });
</script>
@if(session('success'))
<script>
    Swal.fire({
        icon: 'success',
        title: 'Success',
        text: "{{ session('success') }}"
    });
</script>
@endif

@if(session('error'))
<script>
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: "{{ session('error') }}"
    });
</script>
@endif
<!-- Modal -->
<div class="modal fade" id="dutyTypeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">MDO Duty Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Form content will be loaded here via fetch -->
            </div>
        </div>
    </div>
</div>
@endpush