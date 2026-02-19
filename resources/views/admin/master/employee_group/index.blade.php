@extends('admin.layouts.master')

@section('title', 'Employee Group Master')

@section('setup_content')
<div class="container-fluid employee-group-index">
    <x-breadcrum title="Employee Group Master"></x-breadcrum>
    <x-session_message />
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card shadow-sm" style="border-left: 4px solid #004a93;">
            <div class="card-body p-4">
                
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                    <div>
                        <h4 class="fw-semibold text-dark mb-1">Employee Group Master</h4>
                        <p class="text-muted small mb-0">Manage and organize employee group categories</p>
                    </div>
                    <div class="d-flex align-items-center gap-2 w-100 w-md-auto">
                        <button type="button" 
                                class="btn btn-primary px-4 py-2 rounded-1 shadow-sm d-flex align-items-center gap-2 transition-all"
                                data-bs-toggle="modal" 
                                data-bs-target="#employeeGroupModal"
                                onclick="openEmployeeGroupModal('{{ route('master.employee.group.create') }}', 'Create Employee Group')">
                            <i class="material-icons menu-icon material-symbols-rounded" 
                               style="font-size: 20px; vertical-align: middle;">add</i>
                            <span>Add Employee Group</span>
                        </button>
                    </div>
                </div>
                
                <hr class="my-4">
                
                <div class="table-responsive rounded overflow-auto">
                    {{ $dataTable->table(['class' => 'table text-nowrap mb-0 align-middle']) }}
                </div>
                
            </div>
        </div>
        <!-- end Zero Configuration -->
    </div>
</div>

<style>
    .employee-group-index .card {
        transition: box-shadow 0.3s ease;
    }
    
    .employee-group-index .card:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    }
    
    .employee-group-index .btn-primary {
        transition: all 0.3s ease;
        font-weight: 500;
    }
    
    .employee-group-index .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.5rem 1rem rgba(0, 74, 147, 0.3) !important;
    }
    
    /* Table: full row visible on all screens, horizontal scroll on small */
    .employee-group-index .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    .employee-group-index #employeegroupmaster-table {
        width: 100% !important;
        min-width: 320px;
    }
    
    .employee-group-index #employeegroupmaster-table th,
    .employee-group-index #employeegroupmaster-table td {
        white-space: nowrap;
        min-width: 0;
    }
    
    .employee-group-index .table {
        border-collapse: separate;
        border-spacing: 0;
    }
    
    .employee-group-index .table thead th {
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
        font-weight: 600;
        color: #495057;
        padding: 1rem;
        text-transform: uppercase;
        font-size: 0.875rem;
        letter-spacing: 0.5px;
    }
    
    .employee-group-index .table tbody td {
        padding: 1rem;
        vertical-align: middle;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .employee-group-index .table tbody tr:hover {
        background-color: #f8f9fa;
        transition: background-color 0.2s ease;
    }
    
    .employee-group-index .table tbody tr:last-child td {
        border-bottom: none;
    }
    
    /* Action buttons styling */
    .employee-group-index .table tbody td .btn {
        transition: all 0.2s ease;
        margin: 0 2px;
    }
    
    .employee-group-index .table tbody td .btn:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .employee-group-index .table tbody td .btn-outline-primary:hover {
        background-color: #0d6efd;
        border-color: #0d6efd;
        color: #fff;
    }
    
    .employee-group-index .table tbody td .btn-outline-danger:hover {
        background-color: #dc3545;
        border-color: #dc3545;
        color: #fff;
    }
    
    /* Hide DataTables responsive control column if it ever appears */
    .employee-group-index #employeegroupmaster-table .dtr-control,
    .employee-group-index #employeegroupmaster-table th.dtr-control,
    .employee-group-index #employeegroupmaster-table td.dtr-control {
        display: none !important;
        width: 0 !important;
        padding: 0 !important;
        margin: 0 !important;
        visibility: hidden !important;
        position: absolute !important;
        left: -9999px !important;
    }
    
    /* Action buttons container */
    .employee-group-index .action-buttons {
        display: flex;
        gap: 0.5rem;
        justify-content: center;
        flex-wrap: wrap;
    }
    
    @media (max-width: 768px) {
        .employee-group-index .d-flex.justify-content-between {
            flex-direction: column;
            align-items: flex-start !important;
        }
        
        .employee-group-index .btn-primary {
            width: 100%;
            justify-content: center;
        }
        
        .employee-group-index .card-body {
            padding: 1rem !important;
        }
        
        .employee-group-index #employeegroupmaster-table th,
        .employee-group-index #employeegroupmaster-table td {
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
        }
        
        .employee-group-index .action-buttons {
            flex-direction: column;
            width: 100%;
        }
        
        .employee-group-index .action-buttons .btn {
            width: 100%;
            margin: 2px 0;
        }
    }
    
    @media (max-width: 576px) {
        .employee-group-index .table thead th {
            font-size: 0.75rem;
            padding: 0.5rem 0.5rem;
        }
        
        .employee-group-index .table tbody td {
            padding: 0.5rem 0.5rem;
            font-size: 0.8125rem;
        }
    }
    
    /* Modal styling */
    .employee-group-index .modal-header {
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
    }
    
    .employee-group-index .modal-footer {
        border-top: 1px solid #dee2e6;
    }
</style>

<!-- Employee Group Modal -->
<div class="modal fade" id="employeeGroupModal" tabindex="-1" aria-labelledby="employeeGroupModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-semibold" id="employeeGroupModalLabel">Create Employee Group</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="employeeGroupModalBody">
                <div class="text-center p-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
                    Cancel
                </button>
                <button type="submit" form="employeeGroupForm" class="btn btn-primary btn-sm" id="saveEmployeeGroupBtn" style="font-size: 14px;">
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
        modalBody.innerHTML = '<div class="text-center p-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
        
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
        $('#employeeGroupModalBody').html('<div class="text-center p-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
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