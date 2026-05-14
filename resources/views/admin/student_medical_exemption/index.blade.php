@extends('admin.layouts.master')

@section('title', 'Student Medical Exemption - Sargam | Lal Bahadur')

@section('setup_content')
<style>
/* ─── Toggle Group ─── */
.sme-toggle-group {
    display: inline-flex;
    border-radius: 6px;
    overflow: hidden;
    border: 1px solid #dee2e6;
}

.sme-toggle-group .btn {
    border-radius: 0;
    border: none;
    padding: 8px;
    font-weight: 600;
    font-size: 0.875rem;
    transition: all 0.2s ease;
}

.sme-toggle-group .btn.active-toggle {
    background-color: #1b3a5c;
    color: #fff;
}

.sme-toggle-group .btn.inactive-toggle {
    background-color: #f8f9fa;
    color: #6c757d;
}

.sme-toggle-group .btn.inactive-toggle:hover {
    background-color: #e9ecef;
    color: #495057;
}

/* ─── Action Buttons (Print / Download) ─── */
.sme-action-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px;
    border-radius: 6px;
    font-weight: 600;
    font-size: 0.875rem;
    border: 1px solid #dee2e6;
    background: #fff;
    color: #212529;
    cursor: pointer;
    transition: all 0.2s ease;
}

.sme-action-btn:hover {
    background-color: #f8f9fa;
    border-color: #adb5bd;
}

.sme-action-btn .material-icons,
.sme-action-btn .material-symbols-rounded {
    font-size: 18px;
}

/* ─── Filter Bar ─── */
.sme-filter-bar {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.sme-filter-bar .filter-label {
    font-weight: 600;
    color: #6c757d;
    font-size: 0.875rem;
    white-space: nowrap;
}

.sme-filter-bar .form-select {
    border-radius: 6px;
    border: 1px solid #dee2e6;
    font-size: 0.875rem;
    padding: 7px 32px 7px 12px;
    min-width: 160px;
    width: auto;
}

.sme-filter-bar .btn-reset {
    border: 2px solid #dc3545;
    color: #dc3545;
    font-weight: 600;
    border-radius: 6px;
    padding: 6px;
    font-size: 0.875rem;
    background: transparent;
    white-space: nowrap;
    cursor: pointer;
    transition: all 0.2s ease;
}

.sme-filter-bar .btn-reset:hover {
    background-color: #dc3545;
    color: #fff;
}

.sme-search-box {
    position: relative;
}

.sme-search-box .form-control {
    padding-left: 36px;
    border-radius: 6px;
    border: 1px solid #dee2e6;
    font-size: 0.875rem;
    min-width: 200px;
}

.sme-search-box .search-icon {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: #adb5bd;
    font-size: 18px;
    pointer-events: none;
}

/* ─── Date Range Picker ─── */
.sme-date-range {
    position: relative;
}

.sme-date-range .form-control {
    border-radius: 6px;
    border: 1px solid #dee2e6;
    font-size: 0.875rem;
    padding: 7px 12px;
    min-width: 180px;
    width: auto;
    cursor: pointer;
    background: #fff;
}

.flatpickr-calendar {
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12) !important;
    border-radius: 10px !important;
    border: none !important;
}

.table-responsive {
    max-height: 70vh;
    overflow: auto !important;
    -webkit-overflow-scrolling: touch;
}

/* ─── Choices.js overrides inside modal ─── */
#smeModal .choices {
    margin-bottom: 0;
}
#smeModal .choices__inner {
    border-radius: 6px;
    border: 1px solid #dee2e6;
    min-height: 38px;
    padding: 4px 8px;
    font-size: 0.875rem;
    background-color: #fff;
}
#smeModal .choices__list--dropdown {
    z-index: 9999;
    border-radius: 6px;
    border: 1px solid #dee2e6;
    box-shadow: 0 4px 16px rgba(0,0,0,0.1);
}
#smeModal .choices__input {
    font-size: 0.875rem;
}
#smeModal .is-invalid + .choices .choices__inner,
#smeModal .choices.is-invalid .choices__inner {
    border-color: #dc3545;
}

/* ─── Accessibility ─── */
.btn:focus-visible {
    outline: 3px solid #0d6efd;
    outline-offset: 2px;
}

/* ─── Print ─── */
@media print {
    body * {
        visibility: hidden;
    }

    #medicalExemptionTable,
    #medicalExemptionTable * {
        visibility: visible;
    }

    #medicalExemptionTable {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }

    .table thead {
        background-color: #1b3a5c !important;
        color: white !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .table th,
    .table td {
        border: 1px solid #000 !important;
        padding: 8px !important;
    }

    .table {
        border-collapse: collapse !important;
        font-size: 12px !important;
    }

    .table th:nth-child(11),
    .table td:nth-child(11),
    .table th:nth-child(12),
    .table td:nth-child(12) {
        display: none;
    }

    @page {
        margin: 1cm;
    }
}
</style>
<div class="container-fluid">
    <x-breadcrum title="Student Medical Exemption">
        <button type="button" onclick="openCreateModal()"
            class="btn btn-sm btn-primary d-inline-flex align-items-center justify-content-center gap-1 rounded-1 shadow-sm px-3 fw-semibold text-nowrap">
            <i class="material-icons material-symbols-rounded fs-6 lh-1" aria-hidden="true">add</i>
            <span>Add Student Medical Exemption</span>
        </button>
    </x-breadcrum>
    <!-- ─── Top Row: Status Toggle + Action Buttons ─── -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div class="sme-toggle-group" role="group" aria-label="Status filter">
            <button type="button" id="filterActive" class="btn active-toggle">Active</button>
            <button type="button" id="filterArchive" class="btn inactive-toggle">Archived</button>
        </div>
        <div class="d-flex align-items-center gap-1">
            <button type="button" class="sme-action-btn" onclick="printTable()">
                <i class="material-icons material-symbols-rounded">print</i>
                <span>Print</span>
            </button>
            <button type="button" class="sme-action-btn" onclick="downloadCSV()">
                <i class="material-icons material-symbols-rounded">download</i>
                <span>Download</span>
            </button>
        </div>
    </div>
    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body p-4">



            <!-- ─── Filter Bar ─── -->
            <div class="sme-filter-bar mb-2">
                <span class="filter-label">Filters</span>

                <!-- Course Filter -->
                <select name="course_filter" id="course_filter" class="form-select">
                    <option value="">Course Name</option>
                    @foreach($courses as $course)
                    <option value="{{ $course->pk }}">{{ $course->couse_short_name ?? $course->course_name }}</option>
                    @endforeach
                </select>

                <!-- Time Period (Flatpickr Date Range) -->
                <div class="sme-date-range">
                    <input type="text" id="dateRangePicker" class="form-control" placeholder="Time Period" readonly>
                    <input type="hidden" id="from_date_filter" value="">
                    <input type="hidden" id="to_date_filter" value="">
                </div>

                <!-- Reset Filters -->
                <button type="button" id="resetFilters" class="btn-reset">Reset Filters</button>

                <!-- Search -->
                <div class="sme-search-box ms-auto">
                    <i class="material-icons material-symbols-rounded search-icon">search</i>
                    <input type="text" id="search" class="form-control" placeholder="Search" value="">
                </div>
            </div>

            <!-- ─── Data Table ─── -->
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="medicalExemptionTable">
                    <thead>
                        <tr>
                            <th>S. No.</th>
                            <th>OT Code</th>
                            <th>Student Name</th>
                            <th>Course</th>
                            <th>Assigned By</th>
                            <th>Category</th>
                            <th>Medical Speciality</th>
                            <th>Duration</th>
                            <th>OPD Type</th>
                            <th>Document</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                </table>
            </div>

        </div>
    </div>
</div>

{{-- ══════════════════ TWO-STEP MODAL ══════════════════ --}}
<div class="modal fade" id="smeModal" tabindex="-1" aria-labelledby="smeModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width:480px;">
        <div class="modal-content border-0 rounded-4" style="box-shadow:0 12px 40px rgba(0,0,0,0.15);">

            <div class="modal-header border-0 px-4 pt-4 pb-0">
                <h5 class="modal-title fw-bold" id="smeModalLabel">Add Student Medical Exemption</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body px-4 pt-3 pb-4">
                <!-- Progress bar -->
                <div class="d-flex align-items-center gap-1 mb-4">
                    <div class="progress flex-grow-1" style="height:6px;border-radius:3px;">
                        <div class="progress-bar" id="smeProgressBar" role="progressbar"
                            style="width:50%;background-color:#1b3a5c;transition:width .3s ease;"></div>
                    </div>
                    <span class="text-muted small fw-semibold" id="smeProgressText" style="min-width:36px;">50%</span>
                </div>

                <form id="smeForm" enctype="multipart/form-data" novalidate>
                    @csrf
                    <input type="hidden" id="smeUpdateUrl" value="">
                    <input type="hidden" name="employee_master_pk" id="smeEmployeePk" value="{{ Auth::user()?->user_id }}">
                    <input type="hidden" name="active_inactive" id="smeStatusHidden" value="1">

                    {{-- ─── STEP 1 ─── --}}
                    <div id="smeStep1">
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Doctor Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="smeDoctorName"
                                value="{{ Auth::user() ? Auth::user()->first_name . ' ' . Auth::user()->last_name : '' }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Course Name <span class="text-danger">*</span></label>
                            <select name="course_master_pk" id="smeCourse" class="form-select" required>
                                <option value="">Select Course Name</option>
                                @foreach($courses as $course)
                                <option value="{{ $course->pk }}">{{ $course->course_name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="smeCourseError"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">OT Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="smeOtCode" placeholder="eg. A72" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Student Name <span class="text-danger">*</span></label>
                            <select name="student_master_pk" id="smeStudent" class="form-select" required>
                                <option value="">Search Student</option>
                            </select>
                            <div class="invalid-feedback" id="smeStudentError"></div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                            <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary px-4 fw-semibold" id="smeNextBtn"
                                style="background-color:#1b3a5c;border-color:#1b3a5c;">Next</button>
                        </div>
                    </div>

                    {{-- ─── STEP 2 ─── --}}
                    <div id="smeStep2" style="display:none;">
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Exemption Category <span class="text-danger">*</span></label>
                            <select name="exemption_category_master_pk" id="smeCategory" class="form-select" required>
                                <option value="">Select Category</option>
                                @foreach($categories as $cat)
                                <option value="{{ $cat->pk }}">{{ $cat->exemp_category_name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="smeCategoryError"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">OPD Category <span class="text-danger">*</span></label>
                            <select name="opd_category" id="smeOpd" class="form-select">
                                <option value="">Select Category</option>
                                <option value="OPD">OPD</option>
                                <option value="Referred">Referred</option>
                                <option value="IPD">IPD</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Medical Speciality <span class="text-danger">*</span></label>
                            <select name="exemption_medical_speciality_pk" id="smeSpeciality" class="form-select" required>
                                <option value="">Select Category</option>
                                @foreach($specialities as $spec)
                                <option value="{{ $spec->pk }}">{{ $spec->speciality_name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="smeSpecialityError"></div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold small">Start Date</label>
                                <input type="datetime-local" name="from_date" id="smeFromDate" class="form-control" required>
                                <div class="invalid-feedback" id="smeFromDateError"></div>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold small">End Date</label>
                                <input type="datetime-local" name="to_date" id="smeToDate" class="form-control">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Description</label>
                            <textarea name="Description" id="smeDescription" class="form-control" rows="3"
                                placeholder="eg. Lorem ipsum dolor"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Attachment</label>
                            <input type="file" name="Doc_upload" id="smeDocument" class="form-control">
                            <div id="smeExistingDoc" class="mt-1" style="display:none;">
                                <a href="#" id="smeDocLink" target="_blank" class="small text-primary">View existing file</a>
                            </div>
                        </div>
                        <div class="mb-3 d-none" id="smeStatusRow">
                            <label class="form-label fw-semibold small">Status</label>
                            <select id="smeStatusSelect" class="form-select">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                            <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary px-4 fw-semibold" id="smeSubmitBtn"
                                style="background-color:#1b3a5c;border-color:#1b3a5c;">
                                <span id="smeSubmitText">Create Medical Exemption</span>
                                <span id="smeSubmitSpinner" class="spinner-border spinner-border-sm ms-1 d-none"></span>
                            </button>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script>
$(document).ready(function() {

    // ✅ IMPORTANT: global variable (DataTable से पहले)
    let courseStatus = 'active';

    let table = $('#medicalExemptionTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: false,

        scrollX: false,
        scrollCollapse: false,
        autoWidth: false,

        lengthChange: false,
        searching: false,
        paging: false,
        info: false,

        ajax: {
            url: "{{ route('student.medical.exemption.index') }}",
            data: function(d) {
                d.course_id = $('#course_filter').val();
                d.custom_search = $('#search').val();
                d.from_date = $('#from_date_filter').val();
                d.to_date = $('#to_date_filter').val();

                // ✅ status now properly passed
                d.status = courseStatus;
            }
        },

        columns: [{
                data: 'DT_RowIndex',
                orderable: false,
                searchable: false
            },
            {
                data: 'ot_code',
                name: 'student.generated_OT_code'
            },
            {
                data: 'student',
                name: 'student.display_name'
            },
            {
                data: 'course',
                name: 'course.course_name'
            },
            {
                data: 'assigned_by',
                name: 'employee.first_name'
            },
            {
                data: 'category',
                name: 'category.exemp_category_name'
            },
            {
                data: 'speciality',
                name: 'speciality.speciality_name'
            },
            {
                data: 'from_to',
                orderable: false
            },
            {
                data: 'opd_type',
                name: 'opd_category'
            },
            {
                data: 'document',
                orderable: false,
                searchable: false
            },
            {
                data: 'status',
                orderable: false,
                searchable: false
            },
            {
                data: 'action',
                orderable: false,
                searchable: false
            }
        ]
    });

    // Reload table when course filter changes
    $('#course_filter').on('change', function() {
        table.ajax.reload(null, false);
    });

    // Flatpickr date range picker (dual-month calendar)
    var fp = flatpickr('#dateRangePicker', {
        mode: 'range',
        showMonths: 2,
        dateFormat: 'Y-m-d',
        altInput: true,
        altFormat: 'd M Y',
        allowInput: false,
        onChange: function(selectedDates) {
            if (selectedDates.length === 2) {
                var fmt = function(d) {
                    return d.toISOString().slice(0, 10);
                };
                $('#from_date_filter').val(fmt(selectedDates[0]));
                $('#to_date_filter').val(fmt(selectedDates[1]));
                table.ajax.reload(null, false);
            } else if (selectedDates.length === 0) {
                $('#from_date_filter').val('');
                $('#to_date_filter').val('');
                table.ajax.reload(null, false);
            }
        },
        onClose: function(selectedDates) {
            if (selectedDates.length === 1) {
                var fmt = function(d) {
                    return d.toISOString().slice(0, 10);
                };
                $('#from_date_filter').val(fmt(selectedDates[0]));
                $('#to_date_filter').val(fmt(selectedDates[0]));
                table.ajax.reload(null, false);
            }
        }
    });

    // 🔍 Search with debounce
    let delayTimer;
    $('#search').on('keyup', function() {
        clearTimeout(delayTimer);
        delayTimer = setTimeout(function() {
            table.ajax.reload(null, false);
        }, 400);
    });

    // Reset filters
    $('#resetFilters').on('click', function() {
        $('#search').val('');
        $('#course_filter').val('');
        $('#from_date_filter').val('');
        $('#to_date_filter').val('');
        if (fp) fp.clear();
        table.ajax.reload(null, false);
    });

    // Active filter
    $('#filterActive').on('click', function() {
        courseStatus = 'active';
        $(this).addClass('active-toggle').removeClass('inactive-toggle');
        $('#filterArchive').removeClass('active-toggle').addClass('inactive-toggle');
        table.ajax.reload(null, false);
    });

    // Archive filter
    $('#filterArchive').on('click', function() {
        courseStatus = 'archive';
        $(this).addClass('active-toggle').removeClass('inactive-toggle');
        $('#filterActive').removeClass('active-toggle').addClass('inactive-toggle');
        table.ajax.reload(null, false);
    });


    $(document).on('click', '.delete-btn', function () {

        let deleteUrl = $(this).data('url');

        Swal.fire({
            title: 'Delete Medical Exemption?',
            text: 'This record will be permanently deleted!',
            icon: 'warning',
            iconColor: '#dc3545',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Delete',
            cancelButtonText: 'Cancel, Keep it'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: deleteUrl,
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function (response) {
                        Swal.fire('Deleted!', response.message ?? 'Record deleted successfully.', 'success');
                        table.ajax.reload(null, false);
                    },
                    error: function () {
                        Swal.fire('Error!', 'Something went wrong.', 'error');
                    }
                });
            }
        });
    });

    // ─── Edit button → open modal ───
    $(document).on('click', '.edit-sme-btn', function () {
        openEditModal($(this).data('get-url'), $(this).data('update-url'));
    });

});
</script>

<script>
// ══════════════════════════════════════════════════════
// SME MODAL LOGIC
// ══════════════════════════════════════════════════════
let smeMode = 'create';

function openCreateModal() {
    smeMode = 'create';
    resetSmeModal();
    $('#smeModalLabel').text('Add Student Medical Exemption');
    $('#smeSubmitText').text('Create Medical Exemption');
    smeGoToStep(1);
    new bootstrap.Modal(document.getElementById('smeModal')).show();
}

function openEditModal(getUrl, updateUrl) {
    smeMode = 'edit';
    resetSmeModal();
    $('#smeModalLabel').text('Edit Student Medical Exemption');
    $('#smeSubmitText').text('Update Medical Exemption');
    $('#smeUpdateUrl').val(updateUrl);

    $.ajax({
        url: getUrl, type: 'GET',
        success: function (data) {
            $('#smeEmployeePk').val(data.employee_master_pk);
            $('#smeDoctorName').val(data.doctor_name);
            $('#smeCourse').val(data.course_master_pk);
            smeLoadStudents(data.course_master_pk, data.student_master_pk, data.ot_code);
            $('#smeCategory').val(data.exemption_category_master_pk);
            $('#smeOpd').val(data.opd_category);
            $('#smeSpeciality').val(data.exemption_medical_speciality_pk);
            $('#smeFromDate').val(data.from_date);
            $('#smeToDate').val(data.to_date);
            $('#smeDescription').val(data.Description);
            $('#smeStatusHidden').val(data.active_inactive);
            $('#smeStatusSelect').val(data.active_inactive);
            $('#smeStatusRow').removeClass('d-none');
            if (data.doc_url) { $('#smeDocLink').attr('href', data.doc_url); $('#smeExistingDoc').show(); }
            smeGoToStep(1);
            new bootstrap.Modal(document.getElementById('smeModal')).show();
        },
        error: function () { Swal.fire('Error', 'Failed to load record data.', 'error'); }
    });
}

// Choices.js instance for student dropdown
let smeStudentChoices = null;

function smeLoadStudents(courseId, selectedPk, otCode) {
    if (!courseId) return;

    // Destroy existing Choices instance before manipulating the select
    if (smeStudentChoices) {
        smeStudentChoices.destroy();
        smeStudentChoices = null;
    }

    var $sel = $('#smeStudent');
    $sel.html('<option value="">Loading...</option>');

    $.ajax({
        url: '{{ route("student.medical.exemption.getStudentsByCourse") }}',
        type: 'GET',
        data: { course_id: courseId },
        success: function (response) {
            var opts = '<option value="">Search Student</option>';
            $.each(response.students, function (i, s) {
                var sel = (selectedPk && s.pk == selectedPk) ? ' selected' : '';
                opts += '<option value="' + s.pk + '" data-ot_code="' + s.generated_OT_code + '"' + sel + '>' + s.display_name + '</option>';
            });
            $sel.html(opts);

            // Init Choices.js
            smeStudentChoices = new Choices(document.getElementById('smeStudent'), {
                searchEnabled: true,
                searchPlaceholderValue: 'Search student...',
                itemSelectText: '',
                allowHTML: false,
                shouldSort: false,
                placeholder: true,
                placeholderValue: 'Search Student'
            });

            if (selectedPk) {
                smeStudentChoices.setChoiceByValue(String(selectedPk));
                $('#smeOtCode').val(otCode || '');
            }
        }
    });
}

function resetSmeModal() {
    document.getElementById('smeForm').reset();
    $('#smeUpdateUrl').val('');
    $('#smeStatusHidden').val('1');
    $('#smeOtCode').val('');
    $('#smeExistingDoc').hide();
    $('#smeStatusRow').addClass('d-none');
    $('#smeForm .is-invalid').removeClass('is-invalid');
    $('#smeForm .invalid-feedback').text('');

    // Destroy Choices and reset student dropdown to blank
    if (smeStudentChoices) {
        smeStudentChoices.destroy();
        smeStudentChoices = null;
    }
    $('#smeStudent').html('<option value="">Search Student</option>');
}

function smeGoToStep(step) {
    if (step === 1) {
        $('#smeStep1').show(); $('#smeStep2').hide();
        $('#smeProgressBar').css('width', '50%'); $('#smeProgressText').text('50%');
    } else {
        $('#smeStep1').hide(); $('#smeStep2').show();
        $('#smeProgressBar').css('width', '100%'); $('#smeProgressText').text('100%');
    }
}

$('#smeCourse').on('change', function () {
    smeLoadStudents($(this).val(), null, null);
    $('#smeOtCode').val('');
});

// Student change → fill OT code (Choices.js fires native 'change' on the underlying select)
$(document).on('change', '#smeStudent', function () {
    var selected = $(this).find('option:selected');
    $('#smeOtCode').val(selected.data('ot_code') || '');
});

$('#smeStatusSelect').on('change', function () {
    $('#smeStatusHidden').val($(this).val());
});

$('#smeNextBtn').on('click', function () {
    var valid = true;
    if (!$('#smeCourse').val()) {
        $('#smeCourse').addClass('is-invalid'); $('#smeCourseError').text('Please select a course.'); valid = false;
    } else { $('#smeCourse').removeClass('is-invalid'); }

    var studentVal = $('#smeStudent').val();
    if (!studentVal) {
        $('#smeStudentError').text('Please select a student.').show();
        $('#smeStudent').closest('.choices').find('.choices__inner').css('border-color','#dc3545');
        valid = false;
    } else {
        $('#smeStudentError').text('').hide();
        $('#smeStudent').closest('.choices').find('.choices__inner').css('border-color','');
    }
    if (valid) smeGoToStep(2);
});

$('#smeSubmitBtn').on('click', function () {
    var valid = true;
    if (!$('#smeCategory').val()) {
        $('#smeCategory').addClass('is-invalid'); $('#smeCategoryError').text('Please select a category.'); valid = false;
    } else { $('#smeCategory').removeClass('is-invalid'); }
    if (!$('#smeSpeciality').val()) {
        $('#smeSpeciality').addClass('is-invalid'); $('#smeSpecialityError').text('Please select a speciality.'); valid = false;
    } else { $('#smeSpeciality').removeClass('is-invalid'); }
    if (!$('#smeFromDate').val()) {
        $('#smeFromDate').addClass('is-invalid'); $('#smeFromDateError').text('Please select a start date.'); valid = false;
    } else { $('#smeFromDate').removeClass('is-invalid'); }
    if (!valid) return;

    var formData = new FormData(document.getElementById('smeForm'));
    var url = smeMode === 'edit' ? $('#smeUpdateUrl').val() : '{{ route("student.medical.exemption.store") }}';

    $('#smeSubmitSpinner').removeClass('d-none');
    $('#smeSubmitBtn').prop('disabled', true);

    $.ajax({
        url: url, type: 'POST', data: formData, processData: false, contentType: false,
        success: function (response) {
            $('#smeSubmitSpinner').addClass('d-none');
            $('#smeSubmitBtn').prop('disabled', false);
            bootstrap.Modal.getInstance(document.getElementById('smeModal')).hide();
            Swal.fire({ icon: 'success', title: 'Success!', text: response.message || 'Record saved successfully.', timer: 2000, showConfirmButton: false });
            table.ajax.reload(null, false);
        },
        error: function (xhr) {
            $('#smeSubmitSpinner').addClass('d-none');
            $('#smeSubmitBtn').prop('disabled', false);
            if (xhr.status === 422) {
                var errors = xhr.responseJSON?.errors || {};
                if (errors.from_date)        { $('#smeFromDate').addClass('is-invalid'); $('#smeFromDateError').text(errors.from_date[0]); }
                if (errors.course_master_pk) { smeGoToStep(1); $('#smeCourse').addClass('is-invalid'); $('#smeCourseError').text(errors.course_master_pk[0]); }
                if (errors.student_master_pk){ smeGoToStep(1); $('#smeStudent').addClass('is-invalid'); $('#smeStudentError').text(errors.student_master_pk[0]); }
                Swal.fire('Validation Error', 'Please fix the highlighted fields.', 'warning');
            } else {
                Swal.fire('Error', 'Something went wrong. Please try again.', 'error');
            }
        }
    });
});

$('#smeModal').on('hidden.bs.modal', function () {
    resetSmeModal(); smeGoToStep(1);
});

// Print function - defined globally so it can be called from onclick
function printTable() {
    // Create a new window for printing
    var printWindow = window.open('', '_blank');
    var table = document.getElementById('medicalExemptionTable');

    if (!table) {
        alert('Table not found!');
        return;
    }

    // Clone the table to avoid modifying the original
    var tableClone = table.cloneNode(true);

    // Remove Action and Status columns (11th and 12th columns)
    var rows = tableClone.querySelectorAll('tr');
    rows.forEach(function(row) {
        var cells = row.querySelectorAll('th, td');
        if (cells.length >= 12) {
            // Remove Action column (11th) and Status column (12th)
            if (cells[10]) cells[10].remove(); // Action
            if (cells[10]) cells[10].remove(); // Status (now at index 10 after first removal)
        }
    });

    var tableHTML = tableClone.outerHTML;

    // Get current date for header
    var today = new Date();
    var dateStr = today.toLocaleDateString('en-GB', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });

    // Build print content
    var printContent = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Medical Exemption Form - Print</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    margin: 20px;
                }
                .print-header {
                    text-align: center;
                    margin-bottom: 20px;
                    border-bottom: 2px solid #333;
                    padding-bottom: 10px;
                }
                .print-header h2 {
                    margin: 0;
                    color: #004a93;
                }
                .print-header p {
                    margin: 5px 0;
                    color: #666;
                }
                .print-info {
                    margin-bottom: 15px;
                    font-size: 12px;
                    color: #666;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 10px;
                }
                table thead {
                    background-color: #af2910 !important;
                    color: white !important;
                }
                table th,
                table td {
                    border: 1px solid #000;
                    padding: 8px;
                    text-align: left;
                    font-size: 11px;
                }
                table th {
                    font-weight: bold;
                    background-color: #af2910;
                    color: white;
                }
                table tbody tr:nth-child(even) {
                    background-color: #f9f9f9;
                }
                .print-footer {
                    margin-top: 20px;
                    text-align: center;
                    font-size: 10px;
                    color: #666;
                    border-top: 1px solid #ccc;
                    padding-top: 10px;
                }
                @media print {
                    @page {
                        margin: 1cm;
                    }
                    body {
                        margin: 0;
                    }
                }
            </style>
        </head>
        <body>
            <div class="print-header">
                <h2>Medical Exemption Form</h2>
                <p>Lal Bahadur Shastri National Academy of Administration</p>
                <p>Print Date: ${dateStr}</p>
            </div>
            <div class="print-info">
                ${getFilterInfo()}
            </div>
            ${tableHTML}
            <div class="print-footer">
                <p>Generated on ${new Date().toLocaleString()}</p>
            </div>
        </body>
        </html>
    `;

    printWindow.document.write(printContent);
    printWindow.document.close();

    // Wait for content to load, then print
    printWindow.onload = function() {
        printWindow.print();
        printWindow.close();
    };
}

// Download CSV function
function downloadCSV() {
    var table = document.getElementById('medicalExemptionTable');
    if (!table) {
        alert('Table not found!');
        return;
    }

    var csv = [];
    var rows = table.querySelectorAll('tr');
    rows.forEach(function(row) {
        var cols = row.querySelectorAll('th, td');
        var rowData = [];
        cols.forEach(function(col, index) {
            if (index < 10) {
                var text = col.innerText.replace(/"/g, '""').trim();
                rowData.push('"' + text + '"');
            }
        });
        if (rowData.length) csv.push(rowData.join(','));
    });

    var csvContent = csv.join('\n');
    var blob = new Blob(['\ufeff' + csvContent], {
        type: 'text/csv;charset=utf-8;'
    });
    var url = URL.createObjectURL(blob);
    var link = document.createElement('a');
    link.setAttribute('href', url);
    link.setAttribute('download', 'medical_exemption_' + new Date().toISOString().slice(0, 10) + '.csv');
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
}
</script>

@endpush