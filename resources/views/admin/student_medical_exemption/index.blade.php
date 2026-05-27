@extends('admin.layouts.master')

@section('title', 'Student Medical Exemption')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/styles/choices.min.css">
@endpush

@section('setup_content')
<div class="container-fluid sme-master-page">
    <x-breadcrum title="Student Medical Exemption">
        <button type="button"
            class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 rounded-2 fw-semibold shadow-sm text-nowrap"
            id="smeAddExemptionBtn">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Add Student Medical Exemption</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    <div class="datatables">
    <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center justify-content-between gap-3 mb-4">
                    <ul class="nav nav-pills gap-2 p-1 rounded-2 programme-status-tabs bg-white shadow-sm mb-0" role="group"
                        aria-label="Filter exemptions by status">
                        <li class="nav-item" role="presentation">
                            <a href="javascript:void(0)"
                                class="nav-link rounded-2 px-4 py-2 fw-semibold programme-status-pill active"
                                id="filterActive"
                                aria-pressed="true"
                                aria-current="true">
                                Active
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a href="javascript:void(0)"
                                class="nav-link rounded-2 px-4 py-2 fw-semibold programme-status-pill"
                                id="filterArchive"
                                aria-pressed="false">
                                Archived
                            </a>
                        </li>
                    </ul>

                    <div class="d-flex flex-wrap align-items-center justify-content-lg-end gap-2">
                        <button type="button"
                            class="btn btn-outline-primary d-inline-flex align-items-center gap-2 px-3 py-2 rounded-2 fw-semibold shadow-sm"
                            onclick="printTable()">
                            <i class="bi bi-printer" aria-hidden="true"></i>
                            <span>Print</span>
                        </button>
                        <a href="{{ route('student.medical.exemption.export') }}"
                            class="btn btn-outline-primary d-inline-flex align-items-center gap-2 px-3 py-2 rounded-2 fw-semibold shadow-sm">
                            <i class="bi bi-download" aria-hidden="true"></i>
                            <span>Download</span>
                        </a>
                    </div>
                </div>
        <div class="card sme-dt-card border-0 shadow-sm rounded-3 overflow-hidden">
            <div class="card-body p-3 p-md-4">

                <div class="d-flex flex-column flex-xl-row align-items-xl-center justify-content-between gap-3 mb-4 programme-dt-toolbar">
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <span class="programme-dt-filters-label">Filters</span>

                        <div class="programme-dt-filter-select">
                            <select name="course_filter" id="course_filter" class="form-select form-select-sm" aria-label="Filter by course name">
                                <option value="">Course Name</option>
                                @foreach($courses as $course)
                                <option value="{{ $course->pk }}">{{ $course->course_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="programme-dt-filter-select sme-time-period-filter position-relative">
                            <input type="hidden" name="from_date_filter" id="from_date_filter" value="">
                            <input type="hidden" name="to_date_filter" id="to_date_filter" value="">
                            <label for="sme_time_period_picker" class="visually-hidden">Time Period</label>
                            <input type="text"
                                id="sme_time_period_picker"
                                class="form-control form-control-sm sme-time-period-input"
                                placeholder="Time Period"
                                value=""
                                readonly
                                autocomplete="off"
                                aria-label="Filter by time period">
                            <i class="bi bi-calendar3 sme-time-period-icon" aria-hidden="true"></i>
                        </div>

                        <button type="button" class="btn programme-dt-btn-reset flex-shrink-0" id="resetFilters">
                            Reset Filters
                        </button>
                    </div>

                    <div class="programme-dt-search sme-custom-search ms-xl-auto">
                        <label for="search" class="mb-0 w-100 position-relative">
                            <span class="visually-hidden">Search</span>
                            <input type="text"
                                name="search"
                                id="search"
                                class="form-control shadow-none"
                                placeholder="Search"
                                value=""
                                autocomplete="off">
                        </label>
                    </div>
                </div>

                <div class="programme-dt-panel">
                    <div class="table-responsive sme-dt-scroll">
                        <table class="table table-hover align-middle mb-0 w-100 programme-dt-table" id="medicalExemptionTable">
                            <thead>
                                <tr>
                                    <th scope="col" class="text-nowrap sme-col-sno">S. No.</th>
                                    <th scope="col" class="text-nowrap">OT Code</th>
                                    <th scope="col">Student Name</th>
                                    <th scope="col">Course</th>
                                    <th scope="col">Assigned By</th>
                                    <th scope="col">Category</th>
                                    <th scope="col">Medical Speciality</th>
                                    <th scope="col" class="text-nowrap">Duration</th>
                                    <th scope="col" class="text-nowrap">OPD</th>
                                    <th scope="col" class="text-nowrap sme-col-document">Document</th>
                                    <th scope="col" class="text-center text-nowrap sme-col-status">Status</th>
                                    <th scope="col" class="text-center text-nowrap sme-col-action">Action</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                    <div id="smeDtFooter"
                        class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                        data-dt-footer-for="medicalExemptionTable"></div>
                </div>
            </div>
        </div>
    </div>

    @include('admin.student_medical_exemption.partials.add_modal')
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/scripts/choices.min.js"></script>
<script>
$(document).ready(function () {
    let courseStatus = 'active';
    let smeTimePeriodPicker = null;

    if (typeof flatpickr !== 'undefined') {
        smeTimePeriodPicker = flatpickr('#sme_time_period_picker', {
            mode: 'range',
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd/m/Y',
            showMonths: 2,
            static: false,
            locale: { rangeSeparator: ' - ' },
            onReady: function (_selectedDates, _dateStr, instance) {
                instance.calendarContainer.classList.add('sme-flatpickr-theme');
            },
            onChange: function (selectedDates) {
                if (selectedDates.length === 2) {
                    $('#from_date_filter').val(smeTimePeriodPicker.formatDate(selectedDates[0], 'Y-m-d'));
                    $('#to_date_filter').val(smeTimePeriodPicker.formatDate(selectedDates[1], 'Y-m-d'));
                    table.ajax.reload(null, false);
                } else if (selectedDates.length === 0) {
                    $('#from_date_filter').val('');
                    $('#to_date_filter').val('');
                }
            },
            onClose: function (selectedDates) {
                if (selectedDates.length === 1) {
                    smeTimePeriodPicker.clear();
                    $('#from_date_filter').val('');
                    $('#to_date_filter').val('');
                }
            }
        });
    }

    let table = $('#medicalExemptionTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: false,
        searching: false,
        scrollX: false,
        scrollCollapse: false,
        autoWidth: false,

        ajax: {
            url: "{{ route('student.medical.exemption.index') }}",
            data: function (d) {
                d.course_id     = $('#course_filter').val();
                d.custom_search = $('#search').val();
                d.from_date     = $('#from_date_filter').val();
                d.to_date       = $('#to_date_filter').val();
                d.status        = courseStatus;
            }
        },

        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false, className: 'sme-col-sno text-nowrap' },
            { data: 'ot_code', name: 'student.generated_OT_code', className: 'text-nowrap' },
            { data: 'student', name: 'student.display_name' },
            { data: 'course', name: 'course.course_name', className: 'sme-col-course' },
            { data: 'assigned_by', name: 'employee.first_name' },
            { data: 'category', name: 'category.exemp_category_name' },
            { data: 'speciality', name: 'speciality.speciality_name' },
            { data: 'from_to', orderable: false, className: 'sme-col-duration text-nowrap' },
            { data: 'opd_type', name: 'opd_category', className: 'text-nowrap' },
            { data: 'document', orderable: false, searchable: false, className: 'sme-col-document text-nowrap' },
            { data: 'status', orderable: false, searchable: false, className: 'sme-col-status text-center text-nowrap' },
            { data: 'action', orderable: false, searchable: false, className: 'sme-col-action text-center text-nowrap' }
        ],

        drawCallback: function () {
            if (window.SargamDataTableUI) {
                window.SargamDataTableUI.enhance(this.api());
            }
        }
    });

    $('#course_filter').on('change', function () {
        table.ajax.reload(null, false);
    });

    let delayTimer;
    $('#search').on('keyup', function () {
        clearTimeout(delayTimer);
        delayTimer = setTimeout(function () {
            table.ajax.reload(null, false);
        }, 400);
    });

    $('#resetFilters').on('click', function (e) {
        e.preventDefault();

        $('#search').val('');
        $('#course_filter').val('');
        $('#from_date_filter').val('');
        $('#to_date_filter').val('');

        if (smeTimePeriodPicker) {
            smeTimePeriodPicker.clear();
            if (smeTimePeriodPicker.altInput) {
                smeTimePeriodPicker.altInput.value = '';
            }
        }

        table.ajax.reload(null, false);
    });

    $('#filterActive').on('click', function () {
        courseStatus = 'active';

        $(this).addClass('active')
               .attr({ 'aria-pressed': 'true', 'aria-current': 'true' });

        $('#filterArchive').removeClass('active')
                           .attr({ 'aria-pressed': 'false', 'aria-current': null });

        table.ajax.reload(null, false);
    });

    $('#filterArchive').on('click', function () {
        courseStatus = 'archive';

        $(this).addClass('active')
               .attr({ 'aria-pressed': 'true', 'aria-current': 'true' });

        $('#filterActive').removeClass('active')
                          .attr({ 'aria-pressed': 'false', 'aria-current': null });

        table.ajax.reload(null, false);
    });

    initSmeAddWizard(table);

    $(document).on('click', '.delete-btn', function () {
        if ($(this).is(':disabled') || $(this).attr('aria-disabled') === 'true') {
            return;
        }

        let deleteUrl = $(this).data('url');

        Swal.fire({
            title: 'Are you sure?',
            text: 'This record will be permanently deleted!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: deleteUrl,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function (response) {
                        Swal.fire(
                            'Deleted!',
                            response.message ?? 'Record deleted successfully.',
                            'success'
                        );
                        table.ajax.reload(null, false);
                    },
                    error: function () {
                        Swal.fire(
                            'Error!',
                            'Something went wrong.',
                            'error'
                        );
                    }
                });
            }
        });
    });
});

function initSmeAddWizard(table) {
    const modalEl = document.getElementById('smeAddModal');
    if (!modalEl) {
        return;
    }

    const smeModal = bootstrap.Modal.getOrCreateInstance(modalEl);
    const storeUrl = @json(route('student.medical.exemption.store'));
    const updateUrlTemplate = @json(route('student.medical.exemption.update', ['id' => '__ID__']));
    const editDataUrlTemplate = @json(route('student.medical.exemption.editData', ['id' => '__ID__']));
    let currentStep = 1;
    let smeModalMode = 'add';
    let pendingEditStudent = null;

    function setWizardStep(step) {
        currentStep = step;
        const pct = step === 1 ? 50 : 100;

        $('#smeWizardStep1').toggleClass('d-none', step !== 1);
        $('#smeWizardStep2').toggleClass('d-none', step !== 2);
        $('#smeWizardProgressBar').css('width', pct + '%').attr('aria-valuenow', pct);
        $('#smeWizardPct').text(pct + '%');
        $('#smeWizardBack').toggleClass('d-none', step === 1);
        $('#smeWizardNext').toggleClass('d-none', step === 2);
        $('#smeWizardSubmit').toggleClass('d-none', step !== 2);
    }

    function clearSmeFormErrors() {
        $('#smeAddFormAlert').addClass('d-none').removeClass('alert-success alert-danger').empty();
        $('#smeAddExemptionForm .text-danger[id^="smeError"]').addClass('d-none');
        $('#smeAddExemptionForm .form-select, #smeAddExemptionForm .form-control').removeClass('is-invalid');
        $('#smeStudentFieldWrap .choices__inner').removeClass('is-invalid');
    }

    function showSmeFormError(message, errors) {
        const $alert = $('#smeAddFormAlert');
        $alert.removeClass('d-none alert-success').addClass('alert-danger').html(message);

        if (errors) {
            Object.keys(errors).forEach(function (key) {
                const map = {
                    course_master_pk: '#courseDropdown',
                    student_master_pk: '#smeStudentDropdown',
                    exemption_category_master_pk: '#smeExemptionCategory',
                    exemption_medical_speciality_pk: '#smeMedicalSpeciality',
                    from_date: '#smeFromDate'
                };
                if (key === 'student_master_pk') {
                    $('#smeStudentFieldWrap .choices__inner').addClass('is-invalid');
                } else {
                    const selector = map[key];
                    if (selector) {
                        $(selector).addClass('is-invalid');
                    }
                }
            });
        }
    }

    function validateStep1() {
        clearSmeFormErrors();
        let valid = true;

        if (!$('#courseDropdown').val()) {
            $('#smeErrorCourse').removeClass('d-none');
            $('#courseDropdown').addClass('is-invalid');
            valid = false;
        }
        if (!$('#smeStudentDropdown').val()) {
            $('#smeErrorStudent').removeClass('d-none');
            const $choicesInner = $('#smeStudentFieldWrap .choices__inner');
            if ($choicesInner.length) {
                $choicesInner.addClass('is-invalid');
            } else {
                $('#smeStudentDropdown').addClass('is-invalid');
            }
            valid = false;
        }

        return valid;
    }

    function validateStep2() {
        clearSmeFormErrors();
        let valid = true;

        if (!$('#smeExemptionCategory').val()) {
            $('#smeErrorCategory').removeClass('d-none');
            $('#smeExemptionCategory').addClass('is-invalid');
            valid = false;
        }
        if (!$('#smeMedicalSpeciality').val()) {
            $('#smeErrorSpeciality').removeClass('d-none');
            $('#smeMedicalSpeciality').addClass('is-invalid');
            valid = false;
        }
        if (!$('#smeFromDate').val()) {
            $('#smeErrorFromDate').removeClass('d-none');
            $('#smeFromDate').addClass('is-invalid');
            valid = false;
        }

        return valid;
    }

    let smeStudentChoices = null;
    let smeStudentsRequest = null;

    function ensureSmeStudentSelectInWrap() {
        const wrap = document.getElementById('smeStudentFieldWrap');
        const el = document.getElementById('smeStudentDropdown');
        if (!wrap || !el) {
            return;
        }
        wrap.querySelectorAll('.choices').forEach(function (node) {
            node.remove();
        });
        if (!wrap.contains(el)) {
            wrap.appendChild(el);
        }
    }

    function cleanupSmeStudentSelect2() {
        const el = document.getElementById('smeStudentDropdown');
        if (!el) {
            return;
        }

        if (typeof $ !== 'undefined' && $.fn.select2 && $(el).hasClass('select2-hidden-accessible')) {
            try {
                $(el).select2('destroy');
            } catch (e) { /* noop */ }
        }

        $('#smeStudentFieldWrap .select2-container').remove();
        el.classList.remove('select2-hidden-accessible');
        el.removeAttribute('data-select2-id');
        el.removeAttribute('aria-hidden');
        el.removeAttribute('tabindex');
        el.style.display = '';
        el.hidden = false;
        ensureSmeStudentSelectInWrap();
    }

    const smeStudentChoiceOpts = {
        searchEnabled: true,
        shouldSort: false,
        itemSelectText: '',
        allowHTML: false,
        placeholder: true,
        placeholderValue: 'Search Student',
        searchPlaceholderValue: 'Search student...',
        noResultsText: 'No students found',
        noChoicesText: 'No students available',
        classNames: {
            containerOuter: ['choices', 'sme-student-choices', 'w-100'],
            containerInner: ['choices__inner', 'form-control', 'py-2'],
            input: ['choices__input', 'choices__input--cloned'],
            listDropdown: ['choices__list--dropdown', 'dropdown-menu', 'shadow-sm', 'w-100'],
            itemChoice: ['choices__item--choice'],
            placeholder: ['choices__placeholder']
        }
    };

    function destroyStudentChoices() {
        const el = document.getElementById('smeStudentDropdown');
        if (!el) {
            return;
        }

        if (smeStudentChoices) {
            try {
                smeStudentChoices.destroy();
            } catch (e) { /* noop */ }
            smeStudentChoices = null;
        }

        if (el._choicesInstance) {
            try {
                el._choicesInstance.destroy();
            } catch (e) { /* noop */ }
            el._choicesInstance = null;
        }

        cleanupSmeStudentSelect2();
    }

    function syncOtCodeFromStudent() {
        const el = document.getElementById('smeStudentDropdown');
        if (!el) {
            return;
        }
        const selected = el.options[el.selectedIndex];
        const otCode = selected ? (selected.getAttribute('data-ot-code') || '') : '';
        $('#otCodeField').val(otCode);
    }

    function setStudentSelectHtml(message, students, selectedStudentId) {
        const el = document.getElementById('smeStudentDropdown');
        if (!el) {
            return;
        }

        let html = `<option value="">${message}</option>`;
        (students || []).forEach(function (student) {
            const selected = selectedStudentId && String(student.pk) === String(selectedStudentId) ? ' selected' : '';
            const otCode = (student.generated_OT_code || '').replace(/"/g, '&quot;');
            const label = (student.display_name || '').replace(/</g, '&lt;');
            html += `<option value="${student.pk}" data-ot-code="${otCode}"${selected}>${label}</option>`;
        });
        el.innerHTML = html;
    }

    function initStudentChoices(students, selectedStudentId) {
        const el = document.getElementById('smeStudentDropdown');
        if (!el || typeof Choices === 'undefined') {
            return;
        }

        destroyStudentChoices();
        setStudentSelectHtml('Search Student', students, selectedStudentId);
        ensureSmeStudentSelectInWrap();

        smeStudentChoices = new Choices(el, smeStudentChoiceOpts);
        el._choicesInstance = smeStudentChoices;

        if (selectedStudentId) {
            smeStudentChoices.setChoiceByValue(String(selectedStudentId));
        }

        syncOtCodeFromStudent();
    }

    function prepareAddMode() {
        smeModalMode = 'add';
        $('#smeAddModalLabel').text('Add Student Medical Exemption');
        $('#smeWizardSubmit').text('Add Student Medical Exemption');
        $('#smeAddExemptionForm').attr('action', storeUrl);
        $('#smeFormMethod').remove();
        $('#smeStatusWrap, #smeExistingDocWrap').addClass('d-none');
    }

    function prepareEditMode(editId) {
        smeModalMode = 'edit';
        $('#smeAddModalLabel').text('Edit Student Medical Exemption');
        $('#smeWizardSubmit').text('Update Student Medical Exemption');
        $('#smeAddExemptionForm').attr('action', updateUrlTemplate.replace('__ID__', editId));
        if (!$('#smeFormMethod').length) {
            $('#smeAddExemptionForm').append('<input type="hidden" name="_method" value="PUT" id="smeFormMethod">');
        }
        $('#smeStatusWrap').removeClass('d-none');
    }

    function loadStudentsForCourse(courseId, selectedStudentId, fallbackStudent) {
        if (smeStudentsRequest && typeof smeStudentsRequest.abort === 'function') {
            smeStudentsRequest.abort();
            smeStudentsRequest = null;
        }

        if (!courseId) {
            destroyStudentChoices();
            setStudentSelectHtml('Select Course First', [], null);
            return $.Deferred().resolve().promise();
        }

        destroyStudentChoices();
        setStudentSelectHtml('Loading...', [], null);

        smeStudentsRequest = $.ajax({
            url: @json(route('student.medical.exemption.getStudentsByCourse')),
            type: 'GET',
            data: { course_id: courseId }
        });

        return smeStudentsRequest.then(function (response) {
            let students = response.students || [];
            const hasSelected = selectedStudentId && students.some(function (student) {
                return String(student.pk) === String(selectedStudentId);
            });

            if (selectedStudentId && !hasSelected && fallbackStudent) {
                students = [fallbackStudent].concat(students);
            }

            initStudentChoices(students, selectedStudentId);
        }).always(function () {
            smeStudentsRequest = null;
        });
    }

    function resetSmeAddForm() {
        destroyStudentChoices();

        const form = document.getElementById('smeAddExemptionForm');
        if (form) {
            form.reset();
        }

        setStudentSelectHtml('Search Student', [], null);
        $('#otCodeField').val('');
        $('#smeFileName').text('No file chosen');
        $('#smeExistingDocWrap').addClass('d-none');
        $('#smeExistingDoc').attr('href', '#');

        clearSmeFormErrors();
        setWizardStep(1);
        prepareAddMode();
    }

    $('#smeAddExemptionBtn').on('click', function () {
        resetSmeAddForm();
        smeModal.show();
    });

    modalEl.addEventListener('shown.bs.modal', function () {
        cleanupSmeStudentSelect2();

        if (!pendingEditStudent) {
            return;
        }

        const pending = pendingEditStudent;
        pendingEditStudent = null;

        loadStudentsForCourse(pending.courseId, pending.studentId, pending.fallback).always(function () {
            $('#otCodeField').val(pending.otCode || '');
        });
    });

    modalEl.addEventListener('hidden.bs.modal', function () {
        pendingEditStudent = null;
        resetSmeAddForm();
    });

    $(document).on('click', '.sme-edit-btn', function (e) {
        e.preventDefault();

        const editId = $(this).data('edit-id');
        if (!editId) {
            return;
        }

        clearSmeFormErrors();
        setWizardStep(1);
        prepareEditMode(editId);

        const $editBtn = $(this);
        $editBtn.prop('disabled', true);

        $.ajax({
            url: editDataUrlTemplate.replace('__ID__', editId),
            type: 'GET',
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                const record = res.record;

                $('#smeEmployeeMaster').val(record.employee_master_pk);
                $('#courseDropdown').val(record.course_master_pk);
                $('#smeExemptionCategory').val(record.exemption_category_master_pk);
                $('#smeOpdCategory').val(record.opd_category || '');
                $('#smeMedicalSpeciality').val(record.exemption_medical_speciality_pk);
                $('#smeFromDate').val(record.from_date || '');
                $('#smeToDate').val(record.to_date || '');
                $('#smeDescription').val(record.Description || '');
                $('#smeActiveInactive').val(String(record.active_inactive));

                if (record.doc_url) {
                    $('#smeExistingDoc').attr('href', record.doc_url);
                    $('#smeExistingDocWrap').removeClass('d-none');
                } else {
                    $('#smeExistingDocWrap').addClass('d-none');
                }

                destroyStudentChoices();
                setStudentSelectHtml('Loading...', [], null);

                pendingEditStudent = {
                    courseId: record.course_master_pk,
                    studentId: record.student_master_pk,
                    otCode: record.ot_code || '',
                    fallback: {
                        pk: record.student_master_pk,
                        display_name: record.student_name || 'Selected Student',
                        generated_OT_code: record.ot_code || ''
                    }
                };

                smeModal.show();
            },
            error: function () {
                Swal.fire('Error', 'Unable to load record for editing.', 'error');
            },
            complete: function () {
                $editBtn.prop('disabled', false);
            }
        });
    });

    $('#smeWizardNext').on('click', function () {
        if (!validateStep1()) {
            return;
        }
        setWizardStep(2);
    });

    $('#smeWizardBack').on('click', function () {
        setWizardStep(1);
    });

    $('#courseDropdown').on('change', function () {
        const courseId = $(this).val();
        $('#otCodeField').val('');
        loadStudentsForCourse(courseId, null);
    });

    $(window).on('load', cleanupSmeStudentSelect2);

    $('#smeAddModal').on('change', '#smeStudentDropdown', syncOtCodeFromStudent);

    $('#smeDocUpload').on('change', function () {
        const fileName = this.files && this.files[0] ? this.files[0].name : 'No file chosen';
        $('#smeFileName').text(fileName);
    });

    $('#smeAddExemptionForm').on('submit', function (e) {
        e.preventDefault();

        if (!validateStep1()) {
            setWizardStep(1);
            return;
        }
        if (!validateStep2()) {
            return;
        }

        const $submit = $('#smeWizardSubmit');
        const defaultText = $submit.text();
        const formData = new FormData(this);

        $submit.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Saving...');

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            success: function (response) {
                smeModal.hide();
                table.ajax.reload(null, false);

                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: response.message || 'Saved successfully.',
                    timer: 2200,
                    showConfirmButton: false
                });
            },
            error: function (xhr) {
                let message = 'Something went wrong. Please try again.';
                const errors = xhr.responseJSON && xhr.responseJSON.errors ? xhr.responseJSON.errors : null;

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                } else if (errors) {
                    message = Object.values(errors).flat().join('<br>');
                }

                showSmeFormError(message, errors);

                if (errors && (errors.from_date || errors.exemption_category_master_pk || errors.exemption_medical_speciality_pk)) {
                    setWizardStep(2);
                }
            },
            complete: function () {
                $submit.prop('disabled', false).text(defaultText);
            }
        });
    });
}

function printTable() {
    var printWindow = window.open('', '_blank');
    var table = document.getElementById('medicalExemptionTable');

    if (!table) {
        alert('Table not found!');
        return;
    }

    var tableClone = table.cloneNode(true);
    var rows = tableClone.querySelectorAll('tr');

    rows.forEach(function(row) {
        var cells = row.querySelectorAll('th, td');
        if (cells.length >= 12) {
            if (cells[11]) cells[11].remove();
            if (cells[10]) cells[10].remove();
        }
    });

    var tableHTML = tableClone.outerHTML;
    var today = new Date();
    var dateStr = today.toLocaleDateString('en-GB', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });

    var printContent = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Medical Exemption Form - Print</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .print-header {
                    text-align: center;
                    margin-bottom: 20px;
                    border-bottom: 2px solid #333;
                    padding-bottom: 10px;
                }
                .print-header h2 { margin: 0; color: #004a93; }
                .print-header p { margin: 5px 0; color: #666; }
                table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                table thead { background-color: #af2910 !important; color: white !important; }
                table th, table td {
                    border: 1px solid #000;
                    padding: 8px;
                    text-align: left;
                    font-size: 11px;
                }
                table th { font-weight: bold; background-color: #af2910; color: white; }
                table tbody tr:nth-child(even) { background-color: #f9f9f9; }
                .print-footer {
                    margin-top: 20px;
                    text-align: center;
                    font-size: 10px;
                    color: #666;
                    border-top: 1px solid #ccc;
                    padding-top: 10px;
                }
                @media print {
                    @page { margin: 1cm; }
                    body { margin: 0; }
                }
            </style>
        </head>
        <body>
            <div class="print-header">
                <h2>Medical Exemption Form</h2>
                <p>Lal Bahadur Shastri National Academy of Administration</p>
                <p>Print Date: ${dateStr}</p>
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

    printWindow.onload = function() {
        printWindow.print();
        printWindow.close();
    };
}
</script>
@endpush
