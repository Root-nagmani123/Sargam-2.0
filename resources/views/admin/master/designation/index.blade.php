@extends('admin.layouts.master')

@section('title', 'Designation Master')

@section('setup_content')
<div class="container-fluid designation-index">
<x-breadcrum title="Designation Master"></x-breadcrum>
<x-session_message />
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card shadow-sm" style="border-left: 4px solid #004a93;">
            <div class="card-body p-4">
                
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                    <div>
                        <h4 class="fw-semibold text-dark mb-1">Designation Master</h4>
                        <p class="text-muted small mb-0">Manage and organize designations</p>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" 
                                class="btn btn-primary px-4 py-2 rounded-1 shadow-sm d-flex align-items-center gap-2 transition-all"
                                data-bs-toggle="modal" 
                                data-bs-target="#designationModal"
                                onclick="openDesignationModal('{{ route('master.designation.create') }}', 'Create Designation')">
                            <i class="material-icons menu-icon material-symbols-rounded" 
                               style="font-size: 20px; vertical-align: middle;">add</i>
                            <span>Add Designation</span>
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
    .designation-index .card {
        transition: box-shadow 0.3s ease;
        border-radius: 0.5rem;
    }
    
    .designation-index .card:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    }
    
    .designation-index .btn-primary {
        transition: all 0.3s ease;
        font-weight: 500;
    }
    
    .designation-index .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.5rem 1rem rgba(0, 74, 147, 0.3) !important;
    }
    
    /* Table: full row visible on all screens, horizontal scroll on small */
    .designation-index .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    .designation-index #departmentmaster-table {
        width: 100% !important;
        min-width: 320px;
    }
    
    .designation-index #departmentmaster-table th,
    .designation-index #departmentmaster-table td {
        white-space: nowrap;
        min-width: 0;
    }
    
    .designation-index .table {
        border-collapse: separate;
        border-spacing: 0;
    }
    
    .designation-index .table thead th {
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
        font-weight: 600;
        color: #495057;
        padding: 1rem;
        text-transform: uppercase;
        font-size: 0.875rem;
        letter-spacing: 0.5px;
    }
    
    .designation-index .table tbody td {
        padding: 1rem;
        vertical-align: middle;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .designation-index .table tbody tr:hover {
        background-color: #f8f9fa;
        transition: background-color 0.2s ease;
    }
    
    .designation-index .table tbody tr:last-child td {
        border-bottom: none;
    }
    
    /* Action buttons styling */
    .designation-index .table tbody td .btn,
    .designation-index .table tbody td a {
        transition: all 0.2s ease;
        margin: 0 2px;
    }
    
    .designation-index .table tbody td a:hover:not(:disabled) {
        transform: translateY(-1px);
        opacity: 0.8;
    }
    
    .designation-index .table tbody td .btn-outline-primary:hover {
        background-color: #0d6efd;
        border-color: #0d6efd;
        color: #fff;
    }
    
    .designation-index .table tbody td .btn-outline-danger:hover {
        background-color: #dc3545;
        border-color: #dc3545;
        color: #fff;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .designation-index .d-flex.justify-content-between {
            flex-direction: column;
            align-items: flex-start !important;
        }
        
        .designation-index .btn-primary {
            width: 100%;
            justify-content: center;
        }
        
        .designation-index .card-body {
            padding: 1rem !important;
        }
        
        .designation-index #departmentmaster-table th,
        .designation-index #departmentmaster-table td {
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
        }
        
        /* Stack DataTables controls on mobile */
        .designation-index #departmentmaster-table_wrapper .row:first-child,
        .designation-index #departmentmaster-table_wrapper .dt-row:first-child {
            flex-direction: column !important;
            align-items: stretch !important;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
        }
        
        .designation-index #departmentmaster-table_wrapper .dataTables_length,
        .designation-index #departmentmaster-table_wrapper .dataTables_filter {
            text-align: left !important;
            margin-bottom: 0;
            display: block;
            width: 100%;
        }
        
        .designation-index #departmentmaster-table_wrapper .dataTables_length label,
        .designation-index #departmentmaster-table_wrapper .dataTables_filter label {
            width: 100%;
            margin-bottom: 0.5rem;
        }
        
        .designation-index #departmentmaster-table_wrapper .dataTables_filter input {
            width: 100% !important;
            min-width: 100% !important;
        }
    }
    
    @media (max-width: 576px) {
        .designation-index .table thead th {
            font-size: 0.75rem;
            padding: 0.5rem 0.5rem;
        }
        
        .designation-index .table tbody td {
            padding: 0.5rem 0.5rem;
            font-size: 0.8125rem;
        }
        
        .designation-index .table tbody td .btn,
        .designation-index .table tbody td a {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }
    }
    
    /* Modal styling */
    .designation-index .modal-header {
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
    }
    
    .designation-index .modal-footer {
        border-top: 1px solid #dee2e6;
    }
</style>

<!-- Designation Modal -->
<div class="modal fade" id="designationModal" tabindex="-1" aria-labelledby="designationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-semibold" id="designationModalLabel">Create Designation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="designationModalBody">
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
                <button type="submit" form="designationForm" class="btn btn-primary btn-sm" id="saveDesignationBtn" style="font-size: 14px;">
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
    window.openDesignationModal = function(url, title) {
        const modalEl = document.getElementById('designationModal');
        const modalTitle = modalEl.querySelector('#designationModalLabel');
        const modalBody = modalEl.querySelector('#designationModalBody');
        
        modalTitle.textContent = title || 'Designation';
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
                const form = document.getElementById('designationForm');
                if (form && form.querySelector('input[name="pk"]')) {
                    const btnText = document.querySelector('#saveDesignationBtn .btn-text');
                    if (btnText) btnText.textContent = 'Update';
                } else {
                    const btnText = document.querySelector('#saveDesignationBtn .btn-text');
                    if (btnText) btnText.textContent = 'Save';
                }
            }, 100);
        })
        .catch(() => {
            modalBody.innerHTML = '<div class="alert alert-danger">Failed to load form.</div>';
        });
    };
    
    // Handle form submission
    $(document).on('submit', '#designationForm', function(e) {
        e.preventDefault();
        const form = $(this);
        const submitBtn = $('#saveDesignationBtn');
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
                    
                    // Insert alert at top of page
                    $('.designation-index').prepend(alertHtml);
                    
                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('designationModal'));
                    if (modal) modal.hide();
                    
                    // Reload DataTable
                    if ($.fn.DataTable.isDataTable('#departmentmaster-table')) {
                        $('#departmentmaster-table').DataTable().ajax.reload(null, false);
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
                    // Validation errors
                    const errors = xhr.responseJSON?.errors || {};
                    let errorHtml = '<div class="alert alert-danger"><ul class="mb-0">';
                    
                    $.each(errors, function(key, value) {
                        errorHtml += '<li>' + value[0] + '</li>';
                    });
                    errorHtml += '</ul></div>';
                    
                    $('#designationModalBody').prepend(errorHtml);
                } else {
                    const message = xhr.responseJSON?.message || 'An error occurred. Please try again.';
                    $('#designationModalBody').prepend('<div class="alert alert-danger">' + message + '</div>');
                }
            },
            complete: function() {
                submitBtn.prop('disabled', false);
                submitBtn.find('.btn-text').text(originalText);
            }
        });
    });
    
    // Handle edit button clicks in DataTable
    $(document).on('click', '.edit-designation', function(e) {
        e.preventDefault();
        const url = $(this).attr('href');
        openDesignationModal(url, 'Edit Designation');
    });
    
    // Clear modal on close and update button text
    $('#designationModal').on('hidden.bs.modal', function() {
        $('#designationModalBody').html('<div class="text-center p-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
        $('#saveDesignationBtn').find('.btn-text').text('Save');
        $('#designationModalLabel').text('Create Designation');
    });
    
    // Handle delete button clicks
    $(document).on('click', '.delete-designation', function(e) {
        e.preventDefault();
        const form = $(this).closest('form');
        const designationName = $(this).closest('tr').find('td:eq(1)').text().trim();
        
        if (confirm('Are you sure you want to delete "' + designationName + '"?')) {
            $.ajax({
                url: form.attr('action'),
                method: 'POST',
                data: form.serialize(),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    if (response.success || response.deleted) {
                        // Show success message
                        const alertHtml = '<div class="alert alert-success alert-dismissible fade show" role="alert">' +
                            (response.message || 'Designation deleted successfully.') +
                            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                            '</div>';
                        
                        $('.designation-index').prepend(alertHtml);
                        
                        // Reload DataTable
                        if ($.fn.DataTable.isDataTable('#departmentmaster-table')) {
                            $('#departmentmaster-table').DataTable().ajax.reload(null, false);
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
                    const message = xhr.responseJSON?.message || 'An error occurred while deleting the designation.';
                    alert(message);
                }
            });
        }
    });
})();
</script>
@endpush