@extends('admin.layouts.master')

@section('title', 'Employee Type Master')

@section('setup_content')
<div class="container-fluid employee-type-index">
    <x-breadcrum title="Employee Type Master" />
    <x-session_message />
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                    <div class="flex-grow-1 min-w-0">
                        <h4 class="fw-semibold text-dark mb-1">Employee Type Master</h4>
                        <p class="text-muted small mb-0">Manage and organize employee type categories</p>
                    </div>
                    <div class="d-flex align-items-center gap-2 w-100 w-md-auto justify-content-start">
                        <button type="button"
                                class="btn btn-primary px-4 py-2 rounded-1"
                                data-bs-toggle="modal"
                                data-bs-target="#employeeTypeModal"
                                onclick="openEmployeeTypeModal('{{ route('master.employee.type.create') }}', 'Create Employee Type')">
                            <i class="material-icons menu-icon material-symbols-rounded"
                               style="font-size: 20px; vertical-align: middle;">add</i>
                            <span>Add Employee Type</span>
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
    .employee-type-index .card {
        transition: box-shadow 0.3s ease;
    }

    .employee-type-index .card:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    }

    .employee-type-index .btn-primary {
        transition: all 0.3s ease;
        font-weight: 500;
    }

    .employee-type-index .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.5rem 1rem rgba(0, 74, 147, 0.3) !important;
    }

    /* Table: full row visible on all screens, horizontal scroll on small */
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

    .employee-type-index .table {
        border-collapse: separate;
        border-spacing: 0;
    }

    .employee-type-index .table thead th {
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
        font-weight: 600;
        color: #495057;
        padding: 1rem;
        text-transform: uppercase;
        font-size: 0.875rem;
        letter-spacing: 0.5px;
    }

    .employee-type-index .table tbody td {
        padding: 1rem;
        vertical-align: middle;
        border-bottom: 1px solid #f0f0f0;
    }

    .employee-type-index .table tbody tr:hover {
        background-color: #f8f9fa;
        transition: background-color 0.2s ease;
    }

    .employee-type-index .table tbody tr:last-child td {
        border-bottom: none;
    }

    /* Action buttons styling */
    .employee-type-index .table tbody td .btn {
        transition: all 0.2s ease;
    }

    .employee-type-index .table tbody td .btn:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .employee-type-index .table tbody td .btn-outline-primary:hover {
        background-color: #0d6efd;
        border-color: #0d6efd;
        color: #fff;
    }

    .employee-type-index .table tbody td .btn-outline-danger:hover {
        background-color: #dc3545;
        border-color: #dc3545;
        color: #fff;
    }

    /* Hide DataTables responsive control column if it ever appears */
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
        .employee-type-index .d-flex.justify-content-between {
            flex-direction: column;
            align-items: flex-start !important;
        }

        .employee-type-index .btn-primary {
            width: 100%;
            justify-content: center;
        }

        .employee-type-index .card-body {
            padding: 1rem !important;
        }

        .employee-type-index #employeetypemaster-table th,
        .employee-type-index #employeetypemaster-table td {
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
        }
    }

    @media (max-width: 576px) {
        .employee-type-index .table thead th {
            font-size: 0.75rem;
            padding: 0.5rem 0.5rem;
        }

        .employee-type-index .table tbody td {
            padding: 0.5rem 0.5rem;
            font-size: 0.8125rem;
        }
    }

    /* Modal styling */
    .employee-type-index .modal-header {
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
    }

    .employee-type-index .modal-footer {
        border-top: 1px solid #dee2e6;
    }
</style>

<!-- Employee Type Modal -->
<div class="modal fade" id="employeeTypeModal" tabindex="-1" aria-labelledby="employeeTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-semibold" id="employeeTypeModalLabel">Create Employee Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="employeeTypeModalBody">
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
                <button type="submit" form="employeeTypeForm" class="btn btn-primary btn-sm
                " id="saveEmployeeTypeBtn"
                style="font-size: 14px;">
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
                    const alertHtml = '<div class="alert alert-success alert-dismissible fade show" role="alert">' +
                        response.message +
                        '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                        '</div>';

                    // Insert alert at top of page
                    $('.employee-type-index').prepend(alertHtml);

                    // Close modal
                  /*  const modal = bootstrap.Modal.getInstance(document.getElementById('employeeTypeModal'));
                    if (modal) modal.hide();*/

                    const modalEl = document.getElementById('employeeTypeModal');
const modalInstance = bootstrap.Modal.getInstance(modalEl);

                if (modalInstance) {
                    modalInstance.hide();
                }

                    // 🔥 Force cleanup (very important)
                    setTimeout(function () {
                        document.body.classList.remove('modal-open');
                        document.querySelectorAll('.modal-backdrop').forEach(function (el) {
                            el.remove();
                        });
                    }, 300);

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
                    let errorHtml = '<div class="alert alert-danger"><ul class="mb-0">';

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
        $('#employeeTypeModalBody').html('<div class="text-center p-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
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
