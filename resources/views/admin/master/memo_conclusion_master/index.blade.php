@extends('admin.layouts.master')

@section('title', 'Memo Conclusion Master')

@section('setup_content')
<div class="container-fluid memo-conclusion-index">
<x-breadcrum title="Memo Conclusion Master" variant="glass" />
    <div class="card border-start border-4 border-primary">
        <div class="card-body">
            <div class="row">
                <div class="col-6">
                    <h4>Memo Conclusion Master</h4>
                </div>
                <div class="col-6">
                    <div class="d-flex justify-content-end align-items-center gap-2">
                        <!-- Add Group Mapping -->
                        <a href="javascript:void(0)" id="showConclusionAlert"
                            class="btn btn-primary d-flex align-items-center">
                            <i class="material-icons menu-icon material-symbols-rounded"
                                style="font-size: 24px;">add</i>
                            Add Memo Conclusion
                        </a>
                    </div>
                </div>
            </div>
            <hr>

            {!! $dataTable->table(['class' => 'table']) !!}
        </div>
    </div>
</div>
@endsection

@push('scripts')
    {!! $dataTable->scripts() !!}
<script>
document.getElementById('showConclusionAlert').addEventListener('click', function () {

    Swal.fire({
        title: '<strong>Add Discussion</strong>',
        html: `
            <form id="conclusionForm">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">

                <!-- Conclusion name -->
                <div class="row mb-2">
                    <label class="col-auto fw-semibold">
                        Conclusion name <span class="text-danger">*</span>
                    </label>
                    <div class="col">
                        <input type="text" name="discussion_name" id="discussion_name" class="form-control">
                        <small class="text-danger d-none" id="discussion_name_error">Required</small>
                    </div>
                </div>

                <!-- PT Discussion -->
                <div class="row mb-2">
                    <label class="col-auto fw-semibold">
                        PT Discussion
                    </label>
                    <div class="col">
                        <input type="text" name="pt_discusion" id="pt_discusion" class="form-control">
                    </div>
                </div>

                <!-- Status -->
                <div class="row mb-2">
                    <label class="col-auto fw-semibold">
                        Status <span class="text-danger">*</span>
                    </label>
                    <div class="col">
                        <select name="active_inactive" id="active_inactive" class="form-control">
                            <option value="">Select</option>
                            <option value="1">Active</option>
                            <option value="2">Inactive</option>
                        </select>
                        <small class="text-danger d-none" id="active_inactive_error">Required</small>
                    </div>
                </div>
            </form>
        `,
        showCancelButton: true,
        confirmButtonText: 'Submit',
        cancelButtonText: 'Cancel',
        focusConfirm: false,
        allowOutsideClick: () => !Swal.isLoading(),

        preConfirm: () => {

            const popup = Swal.getPopup();

            const discussion = popup.querySelector('#discussion_name');
            const status = popup.querySelector('#active_inactive');

            const discussionError = popup.querySelector('#discussion_name_error');
            const statusError = popup.querySelector('#active_inactive_error');

            // reset errors
            [discussionError, statusError].forEach(e => e.classList.add('d-none'));

            let isValid = true;

            if (!discussion.value.trim()) {
                discussionError.classList.remove('d-none');
                isValid = false;
            }

            if (!status.value) {
                statusError.classList.remove('d-none');
                isValid = false;
            }

            if (!isValid) {
                return false;
            }

            const formData = new FormData(popup.querySelector('#conclusionForm'));

            Swal.showLoading();

            return fetch("{{ route('master.memo.conclusion.master.store') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': "{{ csrf_token() }}",
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => Promise.reject(err));
                }
                return response.json();
            })
            .catch(error => {

                if (error.errors) {

                    if (error.errors.discussion_name) {
                        discussionError.textContent = error.errors.discussion_name[0];
                        discussionError.classList.remove('d-none');
                    }

                    if (error.errors.active_inactive) {
                        statusError.textContent = error.errors.active_inactive[0];
                        statusError.classList.remove('d-none');
                    }

                } else {
                    Swal.showValidationMessage('Server error or session expired');
                }
            });
        }
    }).then(result => {

        if (result.isConfirmed && result.value?.status) {

            Swal.fire('Success', result.value.message, 'success');

            // Reload DataTable if exists
            if ($.fn.DataTable.isDataTable('#memoconclusionmaster-table')) {
                $('#memoconclusionmaster-table').DataTable().ajax.reload(null, false);
            }
        }
    });
});


$(document).on('click', '.editshowConclusionAlert', function() {
        const pk      = $(this).data('pk');
        const discussion_name    = $(this).data('discussion_name');
        const pt_discusion  = $(this).data('pt_discusion');
        const active_inactive = $(this).data('active_inactive');

    Swal.fire({
        title: '<strong>Edit Discussion</strong>',
        html: `
            <form id="conclusionForm">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <input type="hidden" name="id" value="${pk}">

                <!-- Conclusion name -->
                <div class="row mb-2">
                    <label class="col-auto fw-semibold">
                        Conclusion name <span class="text-danger">*</span>
                    </label>
                    <div class="col">
                        <input type="text" name="discussion_name" id="discussion_name" class="form-control" value="${discussion_name}">
                        <small class="text-danger d-none" id="discussion_name_error">Required</small>
                    </div>
                </div>

                <!-- PT Discussion -->
                <div class="row mb-2">
                    <label class="col-auto fw-semibold">
                        PT Discussion
                    </label>
                    <div class="col">
                        <input type="text" name="pt_discusion" id="pt_discusion" class="form-control" value="${pt_discusion}">
                    </div>
                </div>

                <!-- Status -->
                <div class="row mb-2">
                    <label class="col-auto fw-semibold">
                        Status <span class="text-danger">*</span>
                    </label>
                    <div class="col">
                        <select name="active_inactive" id="active_inactive" class="form-control">
                            <option value="">Select</option>
                            <option value="1" ${active_inactive == 1 ? 'selected' : ''}>Active</option>
                            <option value="2" ${active_inactive == 0 ? 'selected' : ''}>Inactive</option>
                        </select>
                        <small class="text-danger d-none" id="active_inactive_error">Required</small>
                    </div>
                </div>
            </form>
        `,
        showCancelButton: true,
        confirmButtonText: 'Update',
        cancelButtonText: 'Cancel',
        focusConfirm: false,
        allowOutsideClick: () => !Swal.isLoading(),

        preConfirm: () => {

            const popup = Swal.getPopup();

            const discussion = popup.querySelector('#discussion_name');
            const status = popup.querySelector('#active_inactive');

            const discussionError = popup.querySelector('#discussion_name_error');
            const statusError = popup.querySelector('#active_inactive_error');

            // reset errors
            [discussionError, statusError].forEach(e => e.classList.add('d-none'));

            let isValid = true;

            if (!discussion.value.trim()) {
                discussionError.classList.remove('d-none');
                isValid = false;
            }

            if (!status.value) {
                statusError.classList.remove('d-none');
                isValid = false;
            }

            if (!isValid) {
                return false;
            }

            const formData = new FormData(popup.querySelector('#conclusionForm'));

            Swal.showLoading();

            // Use the same route or update route if different
            return fetch("{{ route('master.memo.conclusion.master.store') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': "{{ csrf_token() }}",
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => Promise.reject(err));
                }
                return response.json();
            })
            .catch(error => {

                if (error.errors) {

                    if (error.errors.discussion_name) {
                        discussionError.textContent = error.errors.discussion_name[0];
                        discussionError.classList.remove('d-none');
                    }

                    if (error.errors.active_inactive) {
                        statusError.textContent = error.errors.active_inactive[0];
                        statusError.classList.remove('d-none');
                    }

                } else {
                    Swal.showValidationMessage('Server error or session expired');
                }
            });
        }
    }).then(result => {

        if (result.isConfirmed && result.value?.status) {

            Swal.fire('Success', result.value.message, 'success');

            // Reload DataTable if exists
            if ($.fn.DataTable.isDataTable('#memoconclusionmaster-table')) {
                $('#memoconclusionmaster-table').DataTable().ajax.reload(null, false);
            }
        }
    });
});




$(document).on('click', '.deleteBtn', function () {

 //   const pk  = $(this).data('pk');
    const url = $(this).data('url');

    // Debug (optional)
    // alert(pk);
    alert(url);

    Swal.fire({
        title: 'Are you sure?',
        text: 'This action cannot be undone!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {

            $.ajax({
                url: url,
                type: 'POST',
                data: {
                    _method: 'DELETE',
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function (res) {
                    if (res.status) {
                        Swal.fire('Deleted!', res.message, 'success');

                        // ✅ Reload DataTable only
                        $('#memoconclusionmaster-table')
                            .DataTable()
                            .ajax.reload(null, false);
                    } else {
                        Swal.fire('Error!', res.message, 'error');
                    }
                },
                error: function () {
                    Swal.fire('Error!', 'Something went wrong.', 'error');
                }
            });

        }
    });
});




</script>
@endpush