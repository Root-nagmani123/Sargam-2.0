@extends('admin.layouts.master')

@section('title', 'Employee Group Master')

@section('setup_content')
<div class="container-fluid employee-group-index py-3">
    <x-breadcrum title="Employee Group Master"></x-breadcrum>
    <x-session_message />
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card shadow border-0 border-start border-4 border-primary rounded-3 overflow-hidden">
            <div class="card-body p-4 p-lg-5">
                
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
                    <div class="order-2 order-md-1">
                        <h4 class="fw-bold text-body-emphasis mb-1 lh-sm">Employee Group Master</h4>
                        <p class="text-body-secondary small mb-0 opacity-75">Manage and organize employee group categories</p>
                    </div>
                    <div class="order-1 order-md-2 w-100 w-md-auto">
                        <button type="button" 
                                class="btn btn-primary px-4 py-2 rounded-2 shadow-sm d-inline-flex align-items-center gap-2"
                                data-bs-toggle="modal" 
                                data-bs-target="#employeeGroupModal"
                                onclick="openEmployeeGroupModal('{{ route('master.employee.group.create') }}', 'Create Employee Group')">
                            <i class="material-icons menu-icon material-symbols-rounded fs-6">add</i>
                            <span>Add Employee Group</span>
                        </button>
                    </div>
                </div>
                
                <hr class="my-4 border-secondary border-opacity-25">
                
                <div class="table-responsive rounded-3 overflow-hidden border border-secondary border-opacity-25">
                    {{ $dataTable->table(['class' => 'table table-hover table-striped table-borderless text-nowrap mb-0 align-middle']) }}
                </div>
                
            </div>
        </div>
        <!-- end Zero Configuration -->
    </div>
</div>

{{-- Minimal CSS: DataTables responsive column hide + table min-width (Bootstrap cannot target these) --}}
<style>
    .employee-group-index #employeegroupmaster-table { width: 100% !important; min-width: 320px; }
    .employee-group-index #employeegroupmaster-table .dtr-control,
    .employee-group-index #employeegroupmaster-table th.dtr-control,
    .employee-group-index #employeegroupmaster-table td.dtr-control {
        display: none !important; width: 0 !important; padding: 0 !important; margin: 0 !important;
        visibility: hidden !important; position: absolute !important; left: -9999px !important;
    }
</style>

<!-- Employee Group Modal -->
<div class="modal fade" id="employeeGroupModal" tabindex="-1" aria-labelledby="employeeGroupModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-3 shadow-lg border-0 overflow-hidden">
            <div class="modal-header border-bottom py-3 px-4">
                <h5 class="modal-title fw-semibold text-body-emphasis" id="employeeGroupModalLabel">Create Employee Group</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="employeeGroupModalBody">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="text-body-secondary small mt-2 mb-0">Loading form...</p>
                </div>
            </div>
            <div class="modal-footer border-top py-3 px-4 gap-2">
                <button type="button" class="btn btn-outline-secondary rounded-2" data-bs-dismiss="modal">
                    Cancel
                </button>
                <button type="submit" form="employeeGroupForm" class="btn btn-primary rounded-2 px-3" id="saveEmployeeGroupBtn">
                    <span class="btn-text">Save</span>
                </button>
            </div>
        </div>
    </div>
</div>

@endsection
@push('scripts')
{{ $dataTable->scripts() }}
<script>
(function() {
    // Open modal with form
    window.openEmployeeGroupModal = function(url, title) {
        const modalEl = document.getElementById('employeeGroupModal');
        const modalTitle = modalEl.querySelector('#employeeGroupModalLabel');
        const modalBody = modalEl.querySelector('#employeeGroupModalBody');
        
        modalTitle.textContent = title || 'Employee Group';
        modalBody.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="text-body-secondary small mt-2 mb-0">Loading form...</p></div>';
        
        const bsModal = new bootstrap.Modal(modalEl);
        bsModal.show();
        
        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.text())
        .then(html => {
            modalBody.innerHTML = html;
            // Update button text based on form state
            setTimeout(function() {
                const form = document.getElementById('employeeGroupForm');
                if (form && form.querySelector('input[name="pk"]')) {
                    const btnText = document.querySelector('#saveEmployeeGroupBtn .btn-text');
                    if (btnText) btnText.textContent = 'Update';
                } else {
                    const btnText = document.querySelector('#saveEmployeeGroupBtn .btn-text');
                    if (btnText) btnText.textContent = 'Save';
                }
            }, 100);
        })
        .catch(() => {
            modalBody.innerHTML = '<div class="alert alert-danger">Failed to load form.</div>';
        });
    };
    
    // Handle form submission
    $(document).on('submit', '#employeeGroupForm', function(e) {
        e.preventDefault();
        const form = $(this);
        const submitBtn = $('#saveEmployeeGroupBtn');
        const btnText = submitBtn.find('.btn-text');
        const originalText = btnText.text();
        
        submitBtn.prop('disabled', true);
        btnText.html('<span class="spinner-border spinner-border-sm me-2"></span>Saving...');
        
        $.ajax({
            url: form.attr('action'),
            method: form.attr('method') || 'POST',
            data: form.serialize(),
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    const alertHtml = '<div class="alert alert-success alert-dismissible fade show" role="alert">' +
                        response.message +
                        '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                        '</div>';
                    
                    $('.employee-group-index').prepend(alertHtml);
                    
                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('employeeGroupModal'));
                    if (modal) modal.hide();
                    
                    // Reload DataTable
                    if ($.fn.DataTable.isDataTable('#employeegroupmaster-table')) {
                        $('#employeegroupmaster-table').DataTable().ajax.reload(null, false);
                    }
                    
                    // Auto-hide alert after 3 seconds
                    setTimeout(function() {
                        $('.alert-success').fadeOut(function() {
                            $(this).remove();
                        });
                    }, 3000);
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON?.errors || {};
                    let errorHtml = '<div class="alert alert-danger"><ul class="mb-0">';
                    
                    $.each(errors, function(key, value) {
                        errorHtml += '<li>' + value[0] + '</li>';
                    });
                    errorHtml += '</ul></div>';
                    
                    $('#employeeGroupModalBody').prepend(errorHtml);
                } else {
                    const message = xhr.responseJSON?.message || 'An error occurred. Please try again.';
                    $('#employeeGroupModalBody').prepend('<div class="alert alert-danger">' + message + '</div>');
                }
            },
            complete: function() {
                submitBtn.prop('disabled', false);
                submitBtn.find('.btn-text').text(originalText);
            }
        });
    });
    
    // Handle edit button clicks in DataTable
    $(document).on('click', '.edit-employee-group', function(e) {
        e.preventDefault();
        const url = $(this).attr('href');
        openEmployeeGroupModal(url, 'Edit Employee Group');
    });
    
    // Clear modal on close and update button text
    $('#employeeGroupModal').on('hidden.bs.modal', function() {
        $('#employeeGroupModalBody').html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="text-body-secondary small mt-2 mb-0">Loading form...</p></div>');
        $('#saveEmployeeGroupBtn').find('.btn-text').text('Save');
        $('#employeeGroupModalLabel').text('Create Employee Group');
    });
    
    function removeResponsiveControl() {
        var sel = '.employee-group-index #employeegroupmaster-table';
        $(sel + ' .dtr-control, ' + sel + ' th.dtr-control, ' + sel + ' td.dtr-control').remove();
    }
    
    // Handle delete button clicks
    $(document).on('click', '.delete-employee-group', function(e) {
        e.preventDefault();
        const url = $(this).attr('href');
        const groupName = $(this).data('name') || 'this employee group';
        
        if (confirm('Are you sure you want to delete ' + groupName + '? This action cannot be undone.')) {
            $.ajax({
                url: url,
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        const alertHtml = '<div class="alert alert-success alert-dismissible fade show" role="alert">' +
                            'Employee Group deleted successfully.' +
                            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                            '</div>';
                        
                        // Insert alert at top of page
                        $('.employee-group-index').prepend(alertHtml);
                        
                        // Reload DataTable
                        if ($.fn.DataTable.isDataTable('#employeegroupmaster-table')) {
                            $('#employeegroupmaster-table').DataTable().ajax.reload(null, false);
                        }
                        
                        // Auto-hide alert after 3 seconds
                        setTimeout(function() {
                            $('.alert-success').fadeOut(function() {
                                $(this).remove();
                            });
                        }, 3000);
                    }
                },
                error: function(xhr) {
                    const message = xhr.responseJSON?.message || 'An error occurred while deleting. Please try again.';
                    const alertHtml = '<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
                        message +
                        '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                        '</div>';
                    $('.employee-group-index').prepend(alertHtml);
                    
                    setTimeout(function() {
                        $('.alert-danger').fadeOut(function() {
                            $(this).remove();
                        });
                    }, 5000);
                }
            });
        }
    });
    
    $(document).ready(function() {
        setTimeout(removeResponsiveControl, 100);
        $(document).on('preInit.dt', function(e, settings) {
            if (settings.nTable.id === 'employeegroupmaster-table') {
                $(settings.nTable).on('draw.dt', removeResponsiveControl);
            }
        });
        if ($.fn.DataTable.isDataTable('#employeegroupmaster-table')) {
            $('#employeegroupmaster-table').on('draw.dt', removeResponsiveControl);
        }
    });
    $(window).on('load', function() { setTimeout(removeResponsiveControl, 200); });
})();
</script>
@endpush