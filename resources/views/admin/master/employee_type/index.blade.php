@extends('admin.layouts.master')

@section('title', 'Employee Type Master')

@section('setup_content')
<div class="container-fluid employee-type-index py-2 py-md-3">
    <x-breadcrum title="Employee Type Master" />
    <x-session_message />
    <div class="datatables">
        <div class="card border-0 shadow-sm rounded-3 overflow-hidden border-start border-primary border-4">
            <div class="card-body p-4 p-md-5">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-stretch align-items-md-center gap-3 mb-4">
                    <div class="flex-grow-1 min-w-0">
                        <h1 class="h4 fw-bold text-body mb-1 lh-sm">Employee Type Master</h1>
                        <p class="text-body-secondary small mb-0 opacity-90">Manage and organize employee type categories</p>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                        <button type="button"
                                class="btn btn-primary rounded-1 px-4 py-2 d-inline-flex align-items-center gap-2"
                                data-bs-toggle="modal"
                                data-bs-target="#employeeTypeModal"
                                onclick="openEmployeeTypeModal('{{ route('master.employee.type.create') }}', 'Create Employee Type')">
                            <i class="material-icons material-symbols-rounded fs-6" aria-hidden="true">add</i>
                            <span>Add Employee Type</span>
                        </button>
                    </div>
                </div>
                <hr class="my-4 opacity-25">
                <div class="table-responsive rounded-3 overflow-hidden border">
                    {{ $dataTable->table(['class' => 'table text-nowrap mb-0 align-middle employee-type-table']) }}
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .employee-type-index .card {
        transition: box-shadow 0.25s ease, transform 0.25s ease;
    }
    .employee-type-index .card:hover {
        box-shadow: var(--bs-box-shadow) !important;
    }
    
    .employee-type-index .btn-primary {
        font-weight: 500;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .employee-type-index .btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 0.5rem 1rem rgba(var(--bs-primary-rgb), 0.35);
    }
    .employee-type-index .btn-primary:focus-visible {
        box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb), 0.5);
    }

    .employee-type-index .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .employee-type-index #employeetypemaster-table {
        width: 100% !important;
        min-width: 320px;
    }
    .employee-type-index #employeetypemaster-table th,
    .employee-type-index #employeetypemaster-table td {
        white-space: nowrap;
        min-width: 0;
    }
    .employee-type-index .employee-type-table {
        --bs-table-bg: transparent;
        --bs-table-striped-bg: rgba(0, 0, 0, 0.02);
        --bs-table-hover-bg: rgba(var(--bs-primary-rgb), 0.06);
        --bs-table-hover-color: inherit;
    }
    .employee-type-index .employee-type-table thead th {
        background: var(--bs-tertiary-bg);
        border-bottom: 2px solid var(--bs-border-color);
        font-weight: 600;
        color: var(--bs-body-color);
        padding: 1rem 1.25rem;
        font-size: 0.8125rem;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }
    .employee-type-index .employee-type-table tbody td {
        padding: 1rem 1.25rem;
        vertical-align: middle;
        border-bottom: 1px solid var(--bs-border-color-translucent);
    }
    .employee-type-index .employee-type-table tbody tr:last-child td {
        border-bottom: none;
    }
    .employee-type-index .employee-type-table tbody tr:hover td {
        background: var(--bs-table-hover-bg);
    }

    .employee-type-index .table tbody td .btn {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .employee-type-index .table tbody td .btn:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
    }
    .employee-type-index .table tbody td a.text-primary {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.25rem;
        height: 2.25rem;
        border-radius: 0.5rem;
        transition: background-color 0.2s ease, color 0.2s ease;
    }
    .employee-type-index .table tbody td a.text-primary:hover {
        background-color: rgba(var(--bs-primary-rgb), 0.12);
        color: var(--bs-primary) !important;
    }

    .employee-type-index #employeetypemaster-table .dtr-control,
    .employee-type-index #employeetypemaster-table th.dtr-control,
    .employee-type-index #employeetypemaster-table td.dtr-control {
        display: none !important;
        width: 0 !important;
        padding: 0 !important;
        margin: 0 !important;
        visibility: hidden !important;
        position: absolute !important;
        left: -9999px !important;
    }
    
    @media (max-width: 768px) {
        .employee-type-index .btn-primary {
            width: 100%;
            justify-content: center;
        }
        .employee-type-index .card-body {
            padding: 1rem !important;
        }
        .employee-type-index #employeetypemaster-table th,
        .employee-type-index #employeetypemaster-table td {
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
        }
    }
    @media (max-width: 576px) {
        .employee-type-index .employee-type-table thead th {
            font-size: 0.75rem;
            padding: 0.625rem 0.75rem;
        }
        .employee-type-index .employee-type-table tbody td {
            padding: 0.625rem 0.75rem;
            font-size: 0.8125rem;
        }
    }

    .employee-type-index .modal-content {
        border: none;
        border-radius: 1rem;
        box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.175);
    }
    .employee-type-index .modal-header {
        background: var(--bs-tertiary-bg);
        border-bottom: 1px solid var(--bs-border-color);
        padding: 1.25rem 1.5rem;
        border-radius: 1rem 1rem 0 0;
    }
    .employee-type-index .modal-body {
        padding: 1.5rem;
    }
    .employee-type-index .modal-footer {
        border-top: 1px solid var(--bs-border-color);
        padding: 1rem 1.5rem 1.25rem;
        gap: 0.5rem;
    }
</style>

<!-- Employee Type Modal -->
<div class="modal fade" id="employeeTypeModal" tabindex="-1" aria-labelledby="employeeTypeModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-semibold" id="employeeTypeModalLabel">Create Employee Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 pt-2" id="employeeTypeModalBody">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="text-body-secondary small mt-2 mb-0">Loading form...</p>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 flex-wrap gap-2">
                <button type="button" class="btn btn-outline-secondary rounded-3" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="employeeTypeForm" class="btn btn-primary rounded-3" id="saveEmployeeTypeBtn">
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
    function removeResponsiveControl() {
        var sel = '.employee-type-index #employeetypemaster-table';
        $(sel + ' .dtr-control, ' + sel + ' th.dtr-control, ' + sel + ' td.dtr-control').remove();
    }
    
    // Open modal with form
    window.openEmployeeTypeModal = function(url, title) {
        const modalEl = document.getElementById('employeeTypeModal');
        const modalTitle = modalEl.querySelector('#employeeTypeModalLabel');
        const modalBody = modalEl.querySelector('#employeeTypeModalBody');
        
        modalTitle.textContent = title || 'Employee Type';
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
                const form = document.getElementById('employeeTypeForm');
                if (form && form.querySelector('input[name="pk"]')) {
                    const btnText = document.querySelector('#saveEmployeeTypeBtn .btn-text');
                    if (btnText) btnText.textContent = 'Update';
                } else {
                    const btnText = document.querySelector('#saveEmployeeTypeBtn .btn-text');
                    if (btnText) btnText.textContent = 'Save';
                }
            }, 100);
        })
        .catch(() => {
            modalBody.innerHTML = '<div class="alert alert-danger">Failed to load form.</div>';
        });
    };
    
    // Handle form submission
    $(document).on('submit', '#employeeTypeForm', function(e) {
        e.preventDefault();
        const form = $(this);
        const submitBtn = $('#saveEmployeeTypeBtn');
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
                    const alertHtml = '<div class="alert alert-success alert-dismissible fade show d-flex align-items-center rounded-3 shadow-sm" role="alert">' +
                        response.message +
                        '<button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>' +
                        '</div>';
                    
                    // Insert alert at top of page
                    $('.employee-type-index').prepend(alertHtml);
                    
                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('employeeTypeModal'));
                    if (modal) modal.hide();
                    
                    // Reload DataTable
                    if ($.fn.DataTable.isDataTable('#employeetypemaster-table')) {
                        $('#employeetypemaster-table').DataTable().ajax.reload(null, false);
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
                    let errorHtml = '<div class="alert alert-danger rounded-3"><ul class="mb-0 ps-3">';

                    $.each(errors, function(key, value) {
                        errorHtml += '<li>' + value[0] + '</li>';
                    });
                    errorHtml += '</ul></div>';
                    
                    $('#employeeTypeModalBody').prepend(errorHtml);
                } else {
                    const message = xhr.responseJSON?.message || 'An error occurred. Please try again.';
                    $('#employeeTypeModalBody').prepend('<div class="alert alert-danger">' + message + '</div>');
                }
            },
            complete: function() {
                submitBtn.prop('disabled', false);
                submitBtn.find('.btn-text').text(originalText);
            }
        });
    });
    
    // Handle edit button clicks in DataTable
    $(document).on('click', '.edit-employee-type', function(e) {
        e.preventDefault();
        const url = $(this).attr('href');
        openEmployeeTypeModal(url, 'Edit Employee Type');
    });
    
    // Clear modal on close and update button text
    $('#employeeTypeModal').on('hidden.bs.modal', function() {
        $('#employeeTypeModalBody').html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="text-body-secondary small mt-2 mb-0">Loading form...</p></div>');
        $('#saveEmployeeTypeBtn').find('.btn-text').text('Save');
        $('#employeeTypeModalLabel').text('Create Employee Type');
    });
    
    $(document).ready(function() {
        setTimeout(removeResponsiveControl, 100);
        $(document).on('preInit.dt', function(e, settings) {
            if (settings.nTable.id === 'employeetypemaster-table') {
                $(settings.nTable).on('draw.dt', removeResponsiveControl);
            }
        });
        if ($.fn.DataTable.isDataTable('#employeetypemaster-table')) {
            $('#employeetypemaster-table').on('draw.dt', removeResponsiveControl);
        }
    });
    $(window).on('load', function() { setTimeout(removeResponsiveControl, 200); });
})();
</script>
@endpush