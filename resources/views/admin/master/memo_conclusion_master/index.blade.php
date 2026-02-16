@extends('admin.layouts.master')

@section('title', 'Memo Conclusion Master')

@section('setup_content')
<div class="container-fluid memo-conclusion-index">
<x-breadcrum title="Memo Conclusion Master" variant="glass" />
    <div class="card" style="border-left: 4px solid #004a93;">
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

            {!! $dataTable->table(['class' => 'table w-100 text-nowrap align-middle mb-0']) !!}
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* SweetAlert popup styling with Bootstrap integration */
    .swal2-popup {
        border-radius: 0.5rem !important;
        padding: 0 !important;
        max-width: 550px !important;
        width: 90vw !important;
        box-sizing: border-box !important;
    }
    
    .swal2-popup *,
    .swal2-popup *::before,
    .swal2-popup *::after {
        box-sizing: border-box !important;
    }
    
    .swal2-title {
        padding: 1.25rem 1.5rem 1rem !important;
        margin: 0 !important;
        font-size: 1.25rem !important;
        font-weight: 600 !important;
        color: #004a93 !important;
        border-bottom: 1px solid #dee2e6 !important;
        line-height: 1.5 !important;
    }
    
    .swal2-html-container {
        padding: 1.5rem !important;
        margin: 0 !important;
        max-width: 100% !important;
        overflow: hidden !important;
    }
    
    .swal2-html-container form {
        max-width: 100%;
        min-width: 0;
    }
    
    /* Ensure Bootstrap form controls work properly */
    .swal2-html-container .form-control,
    .swal2-html-container .form-select {
        display: block;
        width: 100%;
        padding: 0.5rem 0.75rem;
        font-size: 0.9375rem;
        font-weight: 400;
        line-height: 1.5;
        color: #212529;
        background-color: #fff;
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }
    
    .swal2-html-container .form-control:focus,
    .swal2-html-container .form-select:focus {
        color: #212529;
        background-color: #fff;
        border-color: #004a93;
        outline: 0;
        box-shadow: 0 0 0 0.2rem rgba(0, 74, 147, 0.25);
    }
    
    .swal2-html-container .form-control.is-invalid,
    .swal2-html-container .form-select.is-invalid {
        border-color: #dc3545;
        padding-right: calc(1.5em + 0.75rem);
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath d='m5.8 3.6 .4.4.4-.4'/%3e%3cpath d='m6 8.5v-4'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right calc(0.375em + 0.1875rem) center;
        background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
    }
    
    .swal2-html-container .form-control.is-invalid:focus,
    .swal2-html-container .form-select.is-invalid:focus {
        border-color: #dc3545;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
    }
    
    .swal2-actions {
        padding: 1rem 1.5rem 1.25rem !important;
        margin: 0 !important;
        gap: 0.5rem !important;
        flex-wrap: nowrap !important;
    }
    
    .swal2-actions .swal2-confirm {
        background-color: #004a93 !important;
        border-color: #004a93 !important;
    }
    
    .swal2-actions .swal2-confirm:hover {
        background-color: #003d7a !important;
        border-color: #003d7a !important;
    }
</style>
@endpush

@push('scripts')
    {!! $dataTable->scripts() !!}
<script>
document.getElementById('showConclusionAlert').addEventListener('click', function () {

    Swal.fire({
        title: 'Add Discussion',
        html: `
            <form id="conclusionForm" class="needs-validation" novalidate>
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                
                <div class="mb-3">
                    <label for="discussion_name" class="form-label fw-semibold">
                        Conclusion Name <span class="text-danger">*</span>
                    </label>
                    <input type="text" 
                           class="form-control" 
                           id="discussion_name" 
                           name="discussion_name" 
                           placeholder="Enter conclusion name" 
                           autocomplete="off"
                           required>
                    <div class="invalid-feedback" id="discussion_name_error">
                        Please provide a conclusion name.
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="pt_discusion" class="form-label fw-semibold">
                        PT Discussion
                    </label>
                    <input type="text" 
                           class="form-control" 
                           id="pt_discusion" 
                           name="pt_discusion" 
                           placeholder="Enter PT discussion (optional)" 
                           autocomplete="off">
                </div>
                
                <div class="mb-0">
                    <label for="active_inactive" class="form-label fw-semibold">
                        Status <span class="text-danger">*</span>
                    </label>
                    <select class="form-select" 
                            id="active_inactive" 
                            name="active_inactive" 
                            required>
                        <option value="">Select Status</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                    <div class="invalid-feedback" id="active_inactive_error">
                        Please select a status.
                    </div>
                </div>
            </form>
        `,
        showCancelButton: true,
        confirmButtonText: 'Submit',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#004a93',
        focusConfirm: false,
        allowOutsideClick: function () { return !Swal.isLoading(); },
        didOpen: function () {
            var firstInput = Swal.getPopup().querySelector('#discussion_name');
            if (firstInput) {
                setTimeout(function () { firstInput.focus(); }, 100);
            }
        },

        preConfirm: function () {
            var popup = Swal.getPopup();
            var form = popup.querySelector('#conclusionForm');
            var discussion = popup.querySelector('#discussion_name');
            var status = popup.querySelector('#active_inactive');
            var discussionError = popup.querySelector('#discussion_name_error');
            var statusError = popup.querySelector('#active_inactive_error');

            // Reset validation state
            discussion.classList.remove('is-invalid');
            status.classList.remove('is-invalid');
            discussionError.style.display = 'none';
            statusError.style.display = 'none';

            var isValid = true;

            // Validate discussion name
            if (!discussion.value.trim()) {
                discussion.classList.add('is-invalid');
                discussionError.textContent = 'Conclusion name is required.';
                discussionError.style.display = 'block';
                isValid = false;
            }

            // Validate status
            if (!status.value) {
                status.classList.add('is-invalid');
                statusError.textContent = 'Status is required.';
                statusError.style.display = 'block';
                isValid = false;
            }

            if (!isValid) {
                return false;
            }

            Swal.showLoading();
            var formData = new FormData(form);
            
            return fetch("{{ route('master.memo.conclusion.master.store') }}", {
                method: 'POST',
                headers: { 
                    'X-CSRF-TOKEN': "{{ csrf_token() }}", 
                    'Accept': 'application/json' 
                },
                body: formData
            })
            .then(function (response) {
                if (!response.ok) {
                    return response.json().then(function (err) { 
                        return Promise.reject(err); 
                    });
                }
                return response.json();
            })
            .catch(function (error) {
                // Reset all validation states
                discussion.classList.remove('is-invalid');
                status.classList.remove('is-invalid');
                discussionError.style.display = 'none';
                statusError.style.display = 'none';

                if (error.errors) {
                    if (error.errors.discussion_name) {
                        discussion.classList.add('is-invalid');
                        discussionError.textContent = error.errors.discussion_name[0];
                        discussionError.style.display = 'block';
                    }
                    if (error.errors.active_inactive) {
                        status.classList.add('is-invalid');
                        statusError.textContent = error.errors.active_inactive[0];
                        statusError.style.display = 'block';
                    }
                } else {
                    Swal.showValidationMessage('Server error or session expired');
                }
            });
        }
    }).then(function (result) {
        if (result.isConfirmed && result.value && result.value.status) {
            Swal.fire({ 
                icon: 'success', 
                title: 'Success!', 
                text: result.value.message, 
                timer: 2000, 
                showConfirmButton: false, 
                timerProgressBar: true 
            });
            if (typeof $ !== 'undefined' && $.fn.DataTable && $.fn.DataTable.isDataTable('#memoconclusionmaster-table')) {
                $('#memoconclusionmaster-table').DataTable().ajax.reload(null, false);
            }
        }
    });
});


// Helper function to escape HTML
function escapeHtml(text) {
    if (!text) return '';
    var map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
}

$(document).on('click', '.editshowConclusionAlert', function() {
    var pk = $(this).data('pk');
    var discussion_name = $(this).data('discussion_name') || '';
    var pt_discusion = $(this).data('pt_discusion') || '';
    var active_inactive = $(this).data('active_inactive');

    Swal.fire({
        title: 'Edit Discussion',
        html: `
            <form id="conclusionForm" class="needs-validation" novalidate>
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <input type="hidden" name="id" value="${pk}">
                
                <div class="mb-3">
                    <label for="discussion_name" class="form-label fw-semibold">
                        Conclusion Name <span class="text-danger">*</span>
                    </label>
                    <input type="text" 
                           class="form-control" 
                           id="discussion_name" 
                           name="discussion_name" 
                           value="${escapeHtml(discussion_name)}"
                           placeholder="Enter conclusion name" 
                           autocomplete="off"
                           required>
                    <div class="invalid-feedback" id="discussion_name_error">
                        Please provide a conclusion name.
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="pt_discusion" class="form-label fw-semibold">
                        PT Discussion
                    </label>
                    <input type="text" 
                           class="form-control" 
                           id="pt_discusion" 
                           name="pt_discusion" 
                           value="${escapeHtml(pt_discusion)}"
                           placeholder="Enter PT discussion (optional)" 
                           autocomplete="off">
                </div>
                
                <div class="mb-0">
                    <label for="active_inactive" class="form-label fw-semibold">
                        Status <span class="text-danger">*</span>
                    </label>
                    <select class="form-select" 
                            id="active_inactive" 
                            name="active_inactive" 
                            required>
                        <option value="">Select Status</option>
                        <option value="1" ${active_inactive == 1 ? 'selected' : ''}>Active</option>
                        <option value="0" ${active_inactive == 0 ? 'selected' : ''}>Inactive</option>
                    </select>
                    <div class="invalid-feedback" id="active_inactive_error">
                        Please select a status.
                    </div>
                </div>
            </form>
        `,
        showCancelButton: true,
        confirmButtonText: 'Update',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#004a93',
        focusConfirm: false,
        allowOutsideClick: function () { return !Swal.isLoading(); },
        didOpen: function () {
            var firstInput = Swal.getPopup().querySelector('#discussion_name');
            if (firstInput) {
                setTimeout(function () { 
                    firstInput.focus(); 
                    firstInput.select(); 
                }, 100);
            }
        },

        preConfirm: function () {
            var popup = Swal.getPopup();
            var form = popup.querySelector('#conclusionForm');
            var discussion = popup.querySelector('#discussion_name');
            var status = popup.querySelector('#active_inactive');
            var discussionError = popup.querySelector('#discussion_name_error');
            var statusError = popup.querySelector('#active_inactive_error');

            // Reset validation state
            discussion.classList.remove('is-invalid');
            status.classList.remove('is-invalid');
            discussionError.style.display = 'none';
            statusError.style.display = 'none';

            var isValid = true;

            // Validate discussion name
            if (!discussion.value.trim()) {
                discussion.classList.add('is-invalid');
                discussionError.textContent = 'Conclusion name is required.';
                discussionError.style.display = 'block';
                isValid = false;
            }

            // Validate status
            if (!status.value) {
                status.classList.add('is-invalid');
                statusError.textContent = 'Status is required.';
                statusError.style.display = 'block';
                isValid = false;
            }

            if (!isValid) {
                return false;
            }

            Swal.showLoading();
            var formData = new FormData(form);
            
            return fetch("{{ route('master.memo.conclusion.master.store') }}", {
                method: 'POST',
                headers: { 
                    'X-CSRF-TOKEN': "{{ csrf_token() }}", 
                    'Accept': 'application/json' 
                },
                body: formData
            })
            .then(function (response) {
                if (!response.ok) {
                    return response.json().then(function (err) { 
                        return Promise.reject(err); 
                    });
                }
                return response.json();
            })
            .catch(function (error) {
                // Reset all validation states
                discussion.classList.remove('is-invalid');
                status.classList.remove('is-invalid');
                discussionError.style.display = 'none';
                statusError.style.display = 'none';

                if (error.errors) {
                    if (error.errors.discussion_name) {
                        discussion.classList.add('is-invalid');
                        discussionError.textContent = error.errors.discussion_name[0];
                        discussionError.style.display = 'block';
                    }
                    if (error.errors.active_inactive) {
                        status.classList.add('is-invalid');
                        statusError.textContent = error.errors.active_inactive[0];
                        statusError.style.display = 'block';
                    }
                } else {
                    Swal.showValidationMessage('Server error or session expired');
                }
            });
        }
    }).then(function (result) {
        if (result.isConfirmed && result.value && result.value.status) {
            Swal.fire({ 
                icon: 'success', 
                title: 'Success!', 
                text: result.value.message, 
                timer: 2000, 
                showConfirmButton: false, 
                timerProgressBar: true 
            });
            if (typeof $ !== 'undefined' && $.fn.DataTable && $.fn.DataTable.isDataTable('#memoconclusionmaster-table')) {
                $('#memoconclusionmaster-table').DataTable().ajax.reload(null, false);
            }
        }
    });
});




$(document).on('click', '.deleteBtn', function () {
    const url = $(this).data('url');

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