@extends('admin.layouts.master')

@section('title', 'Course Group Type')

@section('setup_content')
<style>
/* ── Course Group Type – flat design ── */
.cgt-page #coursegrouptype_wrapper { overflow: visible !important; }
.cgt-page .table td { overflow: visible !important; vertical-align: middle; }

/* ── Card ── */
.cgt-page .cgt-card {
    border: 1px solid #e9ecef; border-radius: .75rem;
    box-shadow: 0 1px 3px rgba(0,0,0,.04), 0 1px 2px rgba(0,0,0,.06);
    overflow: hidden;
}

/* ── Search bar area ── */
.cgt-page .cgt-search-wrap {
    background: linear-gradient(180deg, #fafbfc 0%, #f8f9fa 100%);
    padding: 1rem 1.25rem; border-bottom: 1px solid #e9ecef;
}
.cgt-page .cgt-search-input {
    max-width: 360px; margin-left: auto;
}
.cgt-page .cgt-search-input .input-group {
    border: 1px solid #dee2e6; border-radius: .5rem; overflow: hidden;
    background: #fff; transition: border-color .2s, box-shadow .2s;
}
.cgt-page .cgt-search-input .input-group:focus-within {
    border-color: #86b7fe; box-shadow: 0 0 0 .2rem rgba(13,110,253,.15);
}
.cgt-page .cgt-search-input .input-group-text {
    background: transparent; border: none; padding: .5rem .75rem;
    color: #9ca3af;
}
.cgt-page .cgt-search-input .form-control {
    border: none; padding: .5rem .75rem .5rem 0; font-size: .875rem;
    box-shadow: none !important; background: transparent;
}
.cgt-page .cgt-search-input .form-control::placeholder { color: #adb5bd; }

/* ── Table ── */
.cgt-page .cgt-table { font-size: .875rem; margin-bottom: 0; }
.cgt-page .cgt-table thead th {
    background: #f8f9fa; color: #6c757d; font-weight: 600; font-size: .8125rem;
    text-transform: none; border-bottom: 1px solid #e9ecef; border-top: none;
    white-space: nowrap; padding: .875rem 1.25rem; letter-spacing: .01em;
}
.cgt-page .cgt-table tbody td {
    padding: .875rem 1.25rem; border-bottom: 1px solid #f2f3f5; color: #212529;
    transition: background-color .15s ease;
}
.cgt-page .cgt-table tbody tr:hover { background: #f5f7fa; }
.cgt-page .cgt-table tbody tr:last-child td { border-bottom: none; }

/* ── Status badges ── */
.cgt-badge-active {
    display: inline-flex; align-items: center; gap: .3em;
    padding: .35em .9em; font-size: .75rem; font-weight: 600;
    border-radius: 10px; color: #0f5132; background: #d1e7dd; border: 1px solid #badbcc;
    letter-spacing: .02em;
}
.cgt-badge-inactive {
    display: inline-flex; align-items: center; gap: .3em;
    padding: .35em .9em; font-size: .75rem; font-weight: 600;
    border-radius: 10px; color: #842029; background: #f8d7da; border: 1px solid #f5c2c7;
    letter-spacing: .02em;
}

/* ── Action icon buttons ── */
.cgt-action-btn {
    width: 32px; height: 32px; display: inline-flex; align-items: center;
    justify-content: center; border-radius: .5rem; text-decoration: none;
    cursor: pointer; transition: all .2s ease; border: none;
}
.cgt-action-btn:hover { transform: translateY(-1px); box-shadow: 0 2px 4px rgba(0,0,0,.1); }
.cgt-action-btn .material-icons,
.cgt-action-btn .material-symbols-rounded { font-size: 18px; line-height: 1; }
.cgt-action-edit { background: #e8f0fe; }
.cgt-action-edit:hover { background: #d3e3fd; }
.cgt-action-edit .material-icons,
.cgt-action-edit .material-symbols-rounded { color: #1a73e8; }
.cgt-action-toggle { background: #e6f4ea; }
.cgt-action-toggle:hover { background: #c8e6c9; }
.cgt-action-toggle .material-icons,
.cgt-action-toggle .material-symbols-rounded { color: #198754; }
.cgt-action-toggle-off { background: #f0f0f0; }
.cgt-action-toggle-off:hover { background: #e0e0e0; }
.cgt-action-toggle-off .material-icons,
.cgt-action-toggle-off .material-symbols-rounded { color: #6c757d; }
.cgt-action-delete { background: #fce8e6; }
.cgt-action-delete:hover { background: #f8c9c5; }
.cgt-action-delete .material-icons,
.cgt-action-delete .material-symbols-rounded { color: #dc3545; }

/* ── DataTables overrides ── */
.cgt-page #coursegrouptype_wrapper .cgt-dt-meta .dataTables_length,
.cgt-page #coursegrouptype_wrapper .cgt-dt-meta .dataTables_info,
.cgt-page #coursegrouptype_wrapper .cgt-dt-paginate-wrap .dataTables_paginate {
    display: block !important; visibility: visible !important;
}
.cgt-page #coursegrouptype_wrapper .cgt-dt-meta .dataTables_length label,
.cgt-page #coursegrouptype_wrapper .cgt-dt-meta .dataTables_info {
    margin-bottom: 0; font-size: .8125rem; color: #6c757d; font-weight: 500;
}
.cgt-page #coursegrouptype_wrapper .cgt-dt-meta .dataTables_length select {
    border-radius: .375rem; border: 1px solid #dee2e6; padding: .3rem .5rem;
    font-size: .8125rem; color: #495057; background-color: #fff;
    cursor: pointer; transition: border-color .2s;
}
.cgt-page #coursegrouptype_wrapper .cgt-dt-meta .dataTables_length select:focus {
    border-color: #86b7fe; outline: none; box-shadow: 0 0 0 .15rem rgba(13,110,253,.15);
}

/* Footer row */
.cgt-page .cgt-dt-footer {
    border-top: 1px solid #e9ecef; background: #fafbfc;
}

/* Pagination */
.cgt-page #coursegrouptype_wrapper .dataTables_paginate .page-link {
    border: none !important; background: none !important; margin: 0 !important;
    padding: .3rem .6rem !important; font-size: .8125rem !important;
    min-width: 30px !important; height: 30px !important; text-align: center;
    color: #495057 !important; border-radius: .375rem !important;
    line-height: 1.5 !important; box-shadow: none !important; font-weight: 500 !important;
    transition: all .15s ease !important;
}
.cgt-page #coursegrouptype_wrapper .dataTables_paginate .page-link:hover {
    color: #212529 !important; background-color: #e9ecef !important;
}
.cgt-page #coursegrouptype_wrapper .dataTables_paginate .page-item.active .page-link {
    border: 1.5px solid #495057 !important; color: #212529 !important;
    background: transparent !important; font-weight: 600 !important;
}
.cgt-page #coursegrouptype_wrapper .dataTables_paginate .page-item.disabled .page-link {
    color: #adb5bd !important; background: none !important;
}
.cgt-page #coursegrouptype_wrapper .dataTables_paginate .pagination {
    gap: .2rem; align-items: center; margin-bottom: 0;
}

/* Processing overlay */
.cgt-page #coursegrouptype_wrapper .dataTables_processing {
    background: rgba(255,255,255,.85) !important; border: none !important;
    box-shadow: none !important; font-size: .875rem; color: #6c757d;
    backdrop-filter: blur(2px); z-index: 10;
}

/* Hide default DT search */
.cgt-page #coursegrouptype_wrapper .dataTables_filter { display: none !important; }

/* ── Empty state ── */
.cgt-empty-state { text-align: center; padding: 4rem 1rem 3rem; }
.cgt-empty-state p { color: #6c757d; font-size: .95rem; margin: 1rem 0 1.5rem; font-weight: 500; }
.cgt-empty-state .btn { border-radius: .5rem; font-weight: 600; padding: .5rem 1.25rem; }

/* ── SweetAlert deactivate border ── */
.swal2-popup.cgt-swal-deactivate {
    border: 2px solid #0d6efd; border-radius: .75rem;
}

/* ── SweetAlert form layout ── */
.cgt-swal-form { border-radius: .75rem !important; }
.cgt-swal-form .swal2-html-container { text-align: left !important; padding: 1rem 1.5rem !important; }
.cgt-swal-form .swal2-title { font-size: 1.125rem !important; font-weight: 700 !important; }
.cgt-swal-form .swal2-close { font-size: 1.25rem; color: #6c757d; }
.cgt-swal-form .swal2-close:hover { color: #212529; }
.cgt-swal-form .form-control {
    border-radius: .5rem; padding: .625rem .875rem; font-size: .875rem;
    border: 1px solid #dee2e6; transition: border-color .2s, box-shadow .2s;
}
.cgt-swal-form .form-control:focus {
    border-color: #86b7fe; box-shadow: 0 0 0 .2rem rgba(13,110,253,.15);
}
.cgt-swal-form .form-label { font-size: .8125rem; color: #495057; }
</style>

<div class="container-fluid cgt-page">

    {{-- Breadcrumb --}}
    <x-breadcrum title="Course Group Type">
        <a href="javascript:void(0)" id="showAlert"
            class="btn btn-sm btn-primary d-inline-flex align-items-center justify-content-center gap-1 rounded-1 shadow-sm px-3 fw-semibold text-nowrap">
            <i class="material-icons material-symbols-rounded fs-6 lh-1" aria-hidden="true">add</i>
            <span>Add Course Group Type</span>
        </a>
    </x-breadcrum>

    {{-- Card with search + table --}}
    <div class="bg-white cgt-card">

        {{-- Search bar --}}
        <div class="cgt-search-wrap">
            <div class="cgt-search-input text-end">
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="material-icons material-symbols-rounded" style="font-size:18px" aria-hidden="true">search</i>
                    </span>
                    <input type="search" id="cgtTableSearch"
                        class="form-control"
                        placeholder="Search" autocomplete="off">
                </div>
            </div>
        </div>

        {{-- Data table --}}
        <div class="mb-0">
            <table class="table align-middle mb-0 cgt-table" id="coursegrouptype">
                <thead>
                    <tr>
                        <th style="width:80px">S. No.</th>
                        <th>Course Name</th>
                        <th style="width:120px" class="text-center">Status</th>
                        <th style="width:140px" class="text-center">Action</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<input type="hidden" id="pk" value="">
<input type="hidden" id="active_inactive" value="">
@endsection

@section('scripts')
<script>
$(function() {
    const tableSelector = '#coursegrouptype';
    let table;

    if ($.fn.DataTable.isDataTable(tableSelector)) {
        table = $(tableSelector).DataTable();
    } else {
        table = $(tableSelector).DataTable({
            processing: true,
            serverSide: true,
            searching: true,
            ajax: {
                url: "{{ route('master.course.group.type.grouptypeview') }}",
                data: function(d) {
                    d.pk = $('#pk').val();
                    d.active_inactive = $('#active_inactive').val();
                    //  console.log(d.pk);

                }
            },
            columns: [{
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'type_name',
                    name: 'type_name'
                },
                {
                    data: 'status',
                    name: 'status',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false
                }
            ]

        });
    }

    $(document).on('change', '.plain-status-toggle', function() {
        var checkbox = $(this);
        var previousState = !checkbox.is(':checked'); // save previous state
        var pk = checkbox.data('id');
        var active_inactive = checkbox.is(':checked') ? 1 : 0;

        var actionText = active_inactive ? 'activate' : 'deactivate';
        var confirmBtnText = active_inactive ? 'Yes, activate' : 'Yes, deactivate';
        var confirmBtnColor = active_inactive ? '#28a745' : '#d33';

        if (newStatus == 1) {
            // ACTIVATE
            Swal.fire({
                icon: 'info',
                iconColor: '#198754',
                title: 'Activate Course Group?',
                text: 'Are you sure you want to activate this course group',
                showCancelButton: true,
                confirmButtonText: 'Yes, Activate',
                cancelButtonText: 'Cancel, Keep it deactive',
                buttonsStyling: false,
                customClass: {
                    confirmButton: 'btn btn-success me-2',
                    cancelButton: 'btn btn-outline-secondary'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#pk').val(pk);
                    $('#active_inactive').val(1);
                    table.ajax.reload(null, false);
                    Swal.fire({ icon: 'success', title: 'Activated!', text: 'Course group has been activated.', timer: 1500, showConfirmButton: false });
                }
            });
        } else {
            // DEACTIVATE
            Swal.fire({
                icon: 'warning',
                title: 'Deactivate Course Group?',
                text: 'Are you sure you want to deactivate this course group?',
                showCancelButton: true,
                confirmButtonText: 'Yes, Deactivate',
                cancelButtonText: 'Cancel, Keep it active',
                buttonsStyling: false,
                customClass: {
                    popup: 'cgt-swal-deactivate',
                    confirmButton: 'btn btn-primary me-2',
                    cancelButton: 'btn btn-outline-secondary'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#pk').val(pk);
                    $('#active_inactive').val(0);
                    table.ajax.reload(null, false);
                    Swal.fire({ icon: 'success', title: 'Deactivated!', text: 'Course group has been deactivated.', timer: 1500, showConfirmButton: false });
                }
            });
        }
    });

    // ── Delete ──
    $(document).on('click', '.delete-btn', function(e) {
        e.preventDefault();
        let pk = $(this).data('id');
        Swal.fire({
            icon: 'warning',
            iconColor: '#dc3545',
            title: 'Delete Course Group?',
            text: 'Are you sure you want to delete this course group?',
            showCancelButton: true,
            confirmButtonText: 'Yes, Delete',
            cancelButtonText: 'Cancel, Keep it',
            buttonsStyling: false,
            customClass: {
                confirmButton: 'btn btn-danger me-2',
                cancelButton: 'btn btn-outline-secondary'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $('#pk').val(pk);
                $('#active_inactive').val(2);
                table.ajax.reload(null, false);
                Swal.fire({ icon: 'success', title: 'Deleted!', text: 'Course group has been deleted.', timer: 1500, showConfirmButton: false });
            }
        });
    });

}); //endclose
</script>

{{-- ── ADD FORM ── --}}
<script>
document.getElementById('showAlert').addEventListener('click', function() {
    Swal.fire({
        title: 'Add Course Group',
        html: `
        <form id="courseGroupTypeForm">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <div class="mb-3 text-start">
                <label class="form-label fw-semibold mb-1">Course Group Name</label>
                <input type="text" name="type_name" id="type_name"
                       class="form-control" placeholder="eg. MCTP Group" autocomplete="off">
                <small class="text-danger d-none" id="type_name_error">Course Group Name is required</small>
            </div>
        </form>
        `,
        showCancelButton: true,
        showCloseButton: true,
        confirmButtonText: 'Create Course Group',
        cancelButtonText: 'Cancel',
        focusConfirm: false,
        buttonsStyling: false,
        customClass: {
            popup: 'cgt-swal-form',
            confirmButton: 'btn btn-primary me-2',
            cancelButton: 'btn btn-outline-secondary'
        },
        preConfirm: () => {
            const typeNameInput = Swal.getPopup().querySelector('#type_name');
            const errorMsg = Swal.getPopup().querySelector('#type_name_error');
            typeNameInput.classList.remove('is-invalid');
            errorMsg.classList.add('d-none');
            if (!typeNameInput.value.trim()) {
                typeNameInput.classList.add('is-invalid');
                errorMsg.classList.remove('d-none');
                return false;
            }
            return { type_name: typeNameInput.value.trim() };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`{{ route('master.course.group.type.store') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(result.value)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === true) {
                        Swal.fire({ icon: 'success', title: 'Success', text: data.message });
                        window.cgtTable.ajax.reload();
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                    }
                })
                .catch(error => {
                    Swal.fire({ icon: 'error', title: 'Server Error', text: 'Something went wrong!' });
                });
        }
    });
});
</script>

{{-- ── EDIT FORM ── --}}
<script>
$(document).on('click', '.edit-btn', function() {
    let pk = $(this).data('id');
    let typeName = $(this).data('type-name');
    let url = "{{ route('master.course.group.type.store') }}";

    Swal.fire({
        title: 'Edit Course Group',
        html: `
        <form id="courseGroupTypeEditForm">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <input type="hidden" name="id" value="${pk}">
            <div class="mb-3 text-start">
                <label class="form-label fw-semibold mb-1">Course Group Name</label>
                <input type="text" name="type_name" id="type_name"
                       class="form-control" value="${typeName}" autocomplete="off">
                <small class="text-danger d-none" id="type_name_error">Course Group Name is required</small>
            </div>
        </form>
        `,
        showCancelButton: true,
        showCloseButton: true,
        confirmButtonText: 'Update Course Group',
        cancelButtonText: 'Cancel',
        focusConfirm: false,
        buttonsStyling: false,
        customClass: {
            popup: 'cgt-swal-form',
            confirmButton: 'btn btn-primary me-2',
            cancelButton: 'btn btn-outline-secondary'
        },
        preConfirm: () => {
            const typeNameInput = Swal.getPopup().querySelector('#type_name');
            const errorMsg = Swal.getPopup().querySelector('#type_name_error');
            typeNameInput.classList.remove('is-invalid');
            errorMsg.classList.add('d-none');
            if (!typeNameInput.value.trim()) {
                typeNameInput.classList.add('is-invalid');
                errorMsg.classList.remove('d-none');
                return false;
            }
            return { id: pk, type_name: typeNameInput.value.trim() };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: url,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    id: result.value.id,
                    type_name: result.value.type_name
                },
                success: function(response) {
                    if (response.status === true) {
                        Swal.fire({ icon: 'success', title: 'Updated', text: response.message });
                        window.cgtTable.ajax.reload();
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;
                        Swal.fire('Validation Error', errors.type_name[0], 'error');
                    } else {
                        Swal.fire('Error', 'Something went wrong!', 'error');
                    }
                }
            });
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
@endsection