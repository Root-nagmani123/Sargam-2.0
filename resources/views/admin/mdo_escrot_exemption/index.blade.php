@extends('admin.layouts.master')

@section('title', 'MDO Escrot Exemption')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
@endpush

@section('setup_content')
<div class="container-fluid mee-master-page">
    <x-breadcrum title="Escort/ Moderator Duty">
        <button type="button"
            id="meeAddExemptionBtn"
            class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 rounded-2 fw-semibold shadow-sm text-nowrap">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Add New MDO/ Escort Exemption</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    @php
        $activeParams = ['filter' => 'active'];
        $archiveParams = ['filter' => 'archive'];
        foreach (['course_filter', 'year_filter', 'duty_type_filter', 'time_from_filter', 'time_to_filter', 'from_date_filter', 'to_date_filter'] as $param) {
            if (request($param)) {
                $activeParams[$param] = request($param);
                $archiveParams[$param] = request($param);
            }
        }
        $timePeriodLabel = '';
        if (request('from_date_filter') && request('to_date_filter')) {
            try {
                $timePeriodLabel = \Carbon\Carbon::parse(request('from_date_filter'))->format('d/m/Y')
                    . ' - '
                    . \Carbon\Carbon::parse(request('to_date_filter'))->format('d/m/Y');
            } catch (\Exception $e) {
                $timePeriodLabel = '';
            }
        }
    @endphp

    <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center justify-content-between gap-3 mb-4">
        <ul class="nav nav-pills gap-2 p-1 rounded-2 programme-status-tabs bg-white shadow-sm mb-0" role="group"
            aria-label="Course Status Filter">
            <li class="nav-item" role="presentation">
                <a href="{{ route('mdo-escrot-exemption.index', $activeParams) }}"
                    class="nav-link rounded-2 px-4 py-2 fw-semibold programme-status-pill {{ ($filter ?? 'active') === 'active' ? 'active' : '' }}"
                    id="filterActive"
                    aria-pressed="{{ ($filter ?? 'active') === 'active' ? 'true' : 'false' }}"
                    {{ ($filter ?? 'active') === 'active' ? 'aria-current=true' : '' }}>
                    Active
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a href="{{ route('mdo-escrot-exemption.index', $archiveParams) }}"
                    class="nav-link rounded-2 px-4 py-2 fw-semibold programme-status-pill {{ ($filter ?? 'active') === 'archive' ? 'active' : '' }}"
                    id="filterArchive"
                    aria-pressed="{{ ($filter ?? 'active') === 'archive' ? 'true' : 'false' }}"
                    {{ ($filter ?? 'active') === 'archive' ? 'aria-current=true' : '' }}>
                    Archived
                </a>
            </li>
        </ul>

        <div class="d-flex flex-wrap align-items-center justify-content-lg-end gap-2">
            <button type="button" id="printDownloadBtn"
                class="btn btn-outline-primary d-inline-flex align-items-center gap-2 px-3 py-2 rounded-2 fw-semibold shadow-sm">
                <i class="bi bi-printer" aria-hidden="true"></i>
                <span>Print</span>
            </button>
            <button type="button" id="downloadBtn"
                class="btn btn-outline-primary d-inline-flex align-items-center gap-2 px-3 py-2 rounded-2 fw-semibold shadow-sm">
                <i class="bi bi-download" aria-hidden="true"></i>
                <span>Download</span>
            </button>
        </div>
    </div>

    <div class="datatables">
        <div class="card mee-dt-card border-0 shadow-sm rounded-1 overflow-hidden">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex flex-column flex-xl-row align-items-xl-center justify-content-between gap-3 mb-4 programme-dt-toolbar">
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <span class="programme-dt-filters-label">Filters</span>

                        <div class="programme-dt-filter-select">
                            <label for="course_filter" class="visually-hidden">Course Name</label>
                            <select id="course_filter" class="form-select " aria-label="Filter by course name">
                                <option value="">Course Name</option>
                                @foreach ($courseMaster as $id => $name)
                                <option value="{{ $id }}" {{ (string) request('course_filter') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="programme-dt-filter-select">
                            <label for="year_filter" class="visually-hidden">Year</label>
                            <select id="year_filter" class="form-select " aria-label="Filter by year">
                                <option value="">Year</option>
                                @foreach ($years as $year => $yearValue)
                                <option value="{{ $year }}" {{ (string) request('year_filter') === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="programme-dt-filter-select">
                            <label for="duty_type_filter" class="visually-hidden">Duty Type</label>
                            <select id="duty_type_filter" class="form-select " aria-label="Filter by duty type">
                                <option value="">Duty Type</option>
                                @foreach ($dutyTypes as $id => $name)
                                <option value="{{ $id }}" {{ (string) request('duty_type_filter') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="programme-dt-filter-select mee-time-period-filter position-relative">
                            <input type="hidden" id="from_date_filter" value="{{ request('from_date_filter') }}">
                            <input type="hidden" id="to_date_filter" value="{{ request('to_date_filter') }}">
                            <label for="mee_time_period_picker" class="visually-hidden">Time Period</label>
                            <input type="text"
                                id="mee_time_period_picker"
                                class="form-control form-control-sm mee-time-period-input"
                                placeholder="Time Period"
                                value="{{ $timePeriodLabel }}"
                                readonly
                                autocomplete="off"
                                aria-label="Filter by time period">
                            <i class="bi bi-calendar3 mee-time-period-icon" aria-hidden="true"></i>
                        </div>

                        <div class="dropdown mee-extra-filters-dropdown flex-shrink-0">
                            <button type="button"
                                class="btn btn-link p-0 text-decoration-none fw-semibold mee-more-filters-toggle dropdown-toggle"
                                id="meeExtraFiltersToggle"
                                data-bs-toggle="dropdown"
                                data-bs-auto-close="outside"
                                aria-expanded="false"
                                aria-haspopup="true"
                                aria-controls="meeExtraFiltersMenu">
                                <span class="mee-more-filters-count">+2</span> Filters
                            </button>
                            <div class="dropdown-menu dropdown-menu-start p-0 border-0 shadow-sm rounded-1 mee-extra-filters-menu"
                                id="meeExtraFiltersMenu"
                                aria-labelledby="meeExtraFiltersToggle">
                                <div class="mee-extra-filters-card p-3 p-md-4">
                                    <h6 class="fw-semibold text-secondary mb-0">Filters</h6>
                                    <hr class="my-3 opacity-50">
                                    <div class="d-flex flex-column gap-3">
                                        <div>
                                            <label for="time_from_filter" class="form-label small text-secondary mb-1">Time From</label>
                                            <div class="mee-extra-filter-field position-relative">
                                                <input type="time" id="time_from_filter"
                                                    class="form-control form-control-sm rounded-1"
                                                    value="{{ request('time_from_filter') }}"
                                                    aria-label="Filter by time from">
                                                <i class="bi bi-chevron-down mee-extra-filter-chevron" aria-hidden="true"></i>
                                            </div>
                                        </div>
                                        <div>
                                            <label for="time_to_filter" class="form-label small text-secondary mb-1">Time To</label>
                                            <div class="mee-extra-filter-field position-relative">
                                                <input type="time" id="time_to_filter"
                                                    class="form-control form-control-sm rounded-1"
                                                    value="{{ request('time_to_filter') }}"
                                                    aria-label="Filter by time to">
                                                <i class="bi bi-chevron-down mee-extra-filter-chevron" aria-hidden="true"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="button" class="btn programme-dt-btn-reset flex-shrink-0" id="resetFilters">
                            Reset Filters
                        </button>
                    </div>

                    <div id="meeDtSearch" class="programme-dt-search ms-xl-auto" data-dt-search-for="mdoescot-table"></div>
                </div>

                <input type="hidden" id="filter_status" value="{{ $filter ?? 'active' }}">

                <div class="programme-dt-panel mee-dt-panel">
                    <div class="table-responsive mee-dt-scroll">
                        {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table']) !!}
                    </div>
                    <div id="meeDtFooter"
                        class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                        data-dt-footer-for="mdoescot-table"></div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('admin.mdo_escrot_exemption.partials.add_modal')
@include('admin.mdo_escrot_exemption.partials.student_list_modal')

<!-- Delete confirmation -->
<div class="modal fade programme-confirm-modal-root" id="meeDeleteConfirmModal" tabindex="-1"
    aria-labelledby="meeDeleteConfirmTitle" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered programme-confirm-dialog">
        <div class="modal-content programme-confirm-modal border-0 shadow-lg rounded-5 overflow-hidden">
            <div class="modal-body text-center px-4 px-md-5 py-5">
                <div class="programme-confirm-icon programme-confirm-icon--danger mb-4" role="img" aria-hidden="true">
                    <i class="bi bi-exclamation-lg"></i>
                </div>
                <h2 class="h4 fw-bold text-dark mb-3" id="meeDeleteConfirmTitle">Delete This Record?</h2>
                <p class="programme-confirm-message programme-confirm-message--danger mb-4 mb-md-5">
                    Are you sure you want to delete this Escort Exemption?
                </p>
                <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center align-items-stretch programme-confirm-actions">
                    <button type="button"
                        class="btn btn-lg rounded-1 programme-confirm-btn programme-confirm-cancel--danger"
                        id="meeDeleteConfirmCancel"
                        data-bs-dismiss="modal">
                        <span class="programme-confirm-btn-line">Cancel, Keep it</span>
                    </button>
                    <button type="button"
                        class="btn btn-lg rounded-1 programme-confirm-btn programme-confirm-ok--danger"
                        id="meeDeleteConfirmOk">
                        <span class="programme-confirm-btn-line">Yes, Delete</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
{!! $dataTable->scripts() !!}
<script>
$(document).ready(function() {
    var table = $('#mdoescot-table').DataTable();
    var meeTimePeriodPicker = null;
    var pendingDeleteForm = null;
    var meeDeleteModalEl = document.getElementById('meeDeleteConfirmModal');
    var meeDeleteModal = meeDeleteModalEl ? bootstrap.Modal.getOrCreateInstance(meeDeleteModalEl) : null;

    initMeeAddModal(table);

    if (typeof flatpickr !== 'undefined') {
        var fpDefaults = [];
        @if(request('from_date_filter') && request('to_date_filter'))
        fpDefaults = ['{{ request('from_date_filter') }}', '{{ request('to_date_filter') }}'];
        @endif

        meeTimePeriodPicker = flatpickr('#mee_time_period_picker', {
            mode: 'range',
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd/m/Y',
            showMonths: 2,
            defaultDate: fpDefaults.length ? fpDefaults : null,
            static: false,
            locale: { rangeSeparator: ' - ' },
            onReady: function (_selectedDates, _dateStr, instance) {
                instance.calendarContainer.classList.add('mee-flatpickr-theme');
            },
            onChange: function (selectedDates) {
                if (selectedDates.length === 2) {
                    $('#from_date_filter').val(meeTimePeriodPicker.formatDate(selectedDates[0], 'Y-m-d'));
                    $('#to_date_filter').val(meeTimePeriodPicker.formatDate(selectedDates[1], 'Y-m-d'));
                    table.ajax.reload();
                } else if (selectedDates.length === 0) {
                    $('#from_date_filter').val('');
                    $('#to_date_filter').val('');
                }
            },
            onClose: function (selectedDates) {
                if (selectedDates.length === 1) {
                    meeTimePeriodPicker.clear();
                    $('#from_date_filter').val('');
                    $('#to_date_filter').val('');
                }
            }
        });
    }

    function meeBindDeleteActions() {
        $('#mdoescot-table form[id^="delete-form-"] button[type="submit"]').removeAttr('onclick');
    }

    table.on('draw.dt', function() {
        var info = table.page.info();
        $('#total-records-count').text(info.recordsFiltered || info.recordsTotal || 0);
        meeBindDeleteActions();
    });

    meeBindDeleteActions();

    $('#course_filter, #year_filter, #duty_type_filter').on('change', function() {
        table.ajax.reload();
    });

    $('#time_from_filter, #time_to_filter').on('change input', function() {
        meeUpdateExtraFiltersIndicator();
    });

    $('#time_from_filter, #time_to_filter').on('change', function() {
        table.ajax.reload();
    });

    $('#resetFilters').on('click', function() {
        window.location.href = '{{ route("mdo-escrot-exemption.index", ["filter" => "active"]) }}';
    });

    $('#mdoescot-table').on('preXhr.dt', function(e, settings, data) {
        data.filter = $('#filter_status').val() || 'active';
        data.course_filter = $('#course_filter').val();
        data.year_filter = $('#year_filter').val();
        data.duty_type_filter = $('#duty_type_filter').val();
        data.time_from_filter = $('#time_from_filter').val();
        data.time_to_filter = $('#time_to_filter').val();
        data.from_date_filter = $('#from_date_filter').val();
        data.to_date_filter = $('#to_date_filter').val();
    });

    document.addEventListener('click', function(e) {
        var btn = e.target.closest('#mdoescot-table form[id^="delete-form-"] button[type="submit"]');
        if (!btn) {
            return;
        }
        e.preventDefault();
        e.stopPropagation();
        pendingDeleteForm = btn.closest('form');
        if (meeDeleteModal) {
            meeDeleteModal.show();
        } else if (window.confirm('Are you sure you want to delete this record?')) {
            pendingDeleteForm.submit();
        }
    }, true);

    $('#meeDeleteConfirmOk').on('click', function() {
        if (pendingDeleteForm) {
            pendingDeleteForm.off('submit');
            pendingDeleteForm[0].submit();
            pendingDeleteForm = null;
        }
        if (meeDeleteModal) {
            meeDeleteModal.hide();
        }
    });

    $('#meeDeleteConfirmCancel, #meeDeleteConfirmModal').on('hidden.bs.modal', function() {
        pendingDeleteForm = null;
    });

    function meeGetExportTableClone() {
        var tableClone = $('#mdoescot-table').clone();
        tableClone.find('th:last-child, td:last-child').remove();
        var html = tableClone[0].outerHTML;
        html = html.replace(/<th[^>]*>Actions<\/th>/gi, '');
        html = html.replace(/<td[^>]*>[\s\S]*?(edit|delete|Actions)[\s\S]*?<\/td>/gi, '');
        return html;
    }

    function meeUpdateExtraFiltersIndicator() {
        var hasExtra = $('#time_from_filter').val() || $('#time_to_filter').val();
        $('#meeExtraFiltersToggle').toggleClass('mee-more-filters-active', !!hasExtra);
    }

    meeUpdateExtraFiltersIndicator();

    $('#printDownloadBtn').on('click', function() {
        var printWindow = window.open('', '_blank');
        var tableHtml = '<!DOCTYPE html><html><head><title>MDO/Escort Exemption</title>';
        tableHtml += '<style>';
        tableHtml += 'body { font-family: Arial, sans-serif; margin: 20px; }';
        tableHtml += 'table { border-collapse: collapse; width: 100%; margin-top: 20px; }';
        tableHtml += 'th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }';
        tableHtml += 'th { background-color: #b72a2a; color: white; font-weight: bold; }';
        tableHtml += 'tr:nth-child(even) { background-color: #f2f2f2; }';
        tableHtml += 'h2 { color: #004a93; margin-bottom: 20px; }';
        tableHtml += '@media print { body { margin: 0; } @page { margin: 1cm; } }';
        tableHtml += '</style></head><body>';
        tableHtml += '<h2>MDO/Escort Exemption</h2>';
        tableHtml += meeGetExportTableClone();
        tableHtml += '</body></html>';

        printWindow.document.write(tableHtml);
        printWindow.document.close();

        setTimeout(function() {
            printWindow.print();
        }, 250);
    });

    function initMeeAddModal(table) {
        var addModalEl = document.getElementById('meeAddModal');
        var studentModalEl = document.getElementById('meeStudentListModal');
        if (!addModalEl || !studentModalEl) {
            return;
        }

        var meeAddModal = bootstrap.Modal.getOrCreateInstance(addModalEl);
        var meeStudentModal = bootstrap.Modal.getOrCreateInstance(studentModalEl);
        var studentsUrl = @json(route('mdo-escrot-exemption.get.student.list.according.to.course'));
        var storeUrl = @json(route('mdo-escrot-exemption.store'));
        var updateUrl = @json(route('mdo-escrot-exemption.update'));
        var editDataBaseUrl = @json(url('mdo-escrot-exemption/edit-data'));
        var csrfToken = $('meta[name="csrf-token"]').attr('content');

        var meeModalMode = 'add';
        var meeAllStudents = [];
        var meeAssignedStudents = [];
        var meePickerSelectedIds = new Set();
        var meePickerStudentsMap = {};
        var meeStudentsRequest = null;
        var meeEscortDutyTypeId = null;

        $('#mdo_duty_type_master_pk option').each(function() {
            if ($(this).text().trim().toLowerCase() === 'escort') {
                meeEscortDutyTypeId = $(this).val();
            }
        });

        function escapeHtml(text) {
            return String(text || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/"/g, '&quot;');
        }

        function clearMeeFormErrors() {
            $('#meeAddFormAlert').addClass('d-none').removeClass('alert-success alert-danger').empty();
            $('#mdoDutyTypeForm .text-danger[id^="meeError"]').addClass('d-none');
            $('#mdoDutyTypeForm .form-select, #mdoDutyTypeForm .form-control').removeClass('is-invalid');
            $('#meeAssignStudentsTrigger').removeClass('is-invalid');
        }

        function showMeeFormError(message, errors) {
            var $alert = $('#meeAddFormAlert');
            $alert.removeClass('d-none alert-success').addClass('alert-danger').html(message);

            if (errors) {
                var map = {
                    course_master_pk: '#meeCourseDropdown',
                    mdo_duty_type_master_pk: '#mdo_duty_type_master_pk',
                    mdo_date: '#mdo_date',
                    Time_from: '#Time_from',
                    Time_to: '#Time_to',
                    faculty_master_pk: '#faculty_master_pk',
                    selected_student_list: '#meeAssignStudentsTrigger'
                };
                Object.keys(errors).forEach(function(key) {
                    var baseKey = key.split('.')[0];
                    var selector = map[baseKey] || map[key];
                    if (selector) {
                        $(selector).addClass('is-invalid');
                    }
                });
            }
        }

        function toggleFacultyField() {
            var dutyType = $('#mdo_duty_type_master_pk').val();
            if (meeEscortDutyTypeId && dutyType === meeEscortDutyTypeId) {
                $('#faculty_field_container').removeClass('d-none');
                $('#faculty_master_pk').prop('required', true);
            } else {
                $('#faculty_field_container').addClass('d-none');
                $('#faculty_master_pk').val('').prop('required', false);
            }
        }

        function syncHiddenStudentSelect() {
            var $hidden = $('#hiddenStudentSelect');
            $hidden.empty();
            meeAssignedStudents.forEach(function(student) {
                $hidden.append($('<option>', { value: student.pk, selected: true }));
            });
        }

        function renderAssignStudentTags() {
            var $tags = $('#meeAssignStudentsTags');
            var $label = $('#meeAssignStudentsLabel');
            $tags.empty();

            if (!meeAssignedStudents.length) {
                $label.text('Select Students').addClass('text-muted');
                $tags.addClass('d-none');
                syncHiddenStudentSelect();
                return;
            }

            $label.text('');
            $tags.removeClass('d-none');
            meeAssignedStudents.forEach(function(student) {
                $tags.append(
                    '<span class="badge rounded-1 mee-student-tag" data-student-id="' + student.pk + '">' +
                    escapeHtml(student.display_name) +
                    '<button type="button" class="btn-close btn-close-sm ms-1" aria-label="Remove ' + escapeHtml(student.display_name) + '"></button>' +
                    '</span>'
                );
            });
            syncHiddenStudentSelect();
        }

        function renderPickerTags() {
            var ids = Array.from(meePickerSelectedIds);
            $('#meeStudentSelectedCount').text(ids.length + ' Selected');
            var $tags = $('#meeStudentTags');
            $tags.empty();

            ids.forEach(function(id) {
                var student = meePickerStudentsMap[id];
                if (!student) {
                    return;
                }
                $tags.append(
                    '<span class="badge rounded-1 mee-student-tag" data-student-id="' + id + '">' +
                    escapeHtml(student.display_name) +
                    '<button type="button" class="btn-close btn-close-sm ms-1" aria-label="Remove"></button>' +
                    '</span>'
                );
            });
        }

        function renderStudentList() {
            var query = ($('#meeStudentListSearch').val() || '').trim().toLowerCase();
            var $list = $('#meeStudentList');
            var $empty = $('#meeStudentListEmpty');
            $list.empty();

            var filtered = meeAllStudents.filter(function(student) {
                if (!query) {
                    return true;
                }
                var name = (student.display_name || '').toLowerCase();
                var ot = (student.ot_code || '').toLowerCase();
                return name.indexOf(query) !== -1 || ot.indexOf(query) !== -1;
            });

            if (!filtered.length) {
                $list.addClass('d-none');
                $empty.removeClass('d-none').text(
                    meeAllStudents.length ? 'No students match your search.' : 'No students available for this course and date.'
                );
                return;
            }

            $empty.addClass('d-none');
            $list.removeClass('d-none');

            filtered.forEach(function(student) {
                var id = String(student.pk);
                var checked = meePickerSelectedIds.has(id) ? ' checked' : '';
                var otLabel = student.ot_code ? ' <span class="text-muted small">(' + escapeHtml(student.ot_code) + ')</span>' : '';
                $list.append(
                    '<li class="list-group-item mee-student-list-item">' +
                    '<div class="form-check d-flex align-items-center gap-2 mb-0">' +
                    '<input class="form-check-input mee-student-pick" type="checkbox" value="' + id + '" id="meeStudentPick_' + id + '"' + checked + '>' +
                    '<label class="form-check-label flex-grow-1" for="meeStudentPick_' + id + '">' + escapeHtml(student.display_name) + otLabel + '</label>' +
                    '</div></li>'
                );
            });
        }

        function setPickerFromAssigned() {
            meePickerSelectedIds.clear();
            meeAssignedStudents.forEach(function(student) {
                meePickerSelectedIds.add(String(student.pk));
            });
            renderPickerTags();
            renderStudentList();
        }

        function loadStudentsForPicker() {
            var courseId = $('#meeCourseDropdown').val();
            var selectedDate = $('#mdo_date').val();

            if (!courseId || !selectedDate) {
                return $.Deferred().reject().promise();
            }

            if (meeStudentsRequest && typeof meeStudentsRequest.abort === 'function') {
                meeStudentsRequest.abort();
            }

            $('#meeStudentListEmpty').removeClass('d-none').text('Loading students...');
            $('#meeStudentList').addClass('d-none');

            meeStudentsRequest = $.ajax({
                url: studentsUrl,
                type: 'POST',
                data: {
                    _token: csrfToken,
                    selectedCourses: courseId,
                    selectedDate: selectedDate
                }
            });

            return meeStudentsRequest.then(function(response) {
                if (!response.status) {
                    meeAllStudents = [];
                    $('#meeStudentListEmpty').removeClass('d-none').text(response.message || 'Unable to load students.');
                    $('#meeStudentList').addClass('d-none');
                    return;
                }

                meeAllStudents = response.students || [];
                meePickerStudentsMap = {};
                meeAllStudents.forEach(function(s) {
                    meePickerStudentsMap[String(s.pk)] = s;
                });
                setPickerFromAssigned();
            }).always(function() {
                meeStudentsRequest = null;
            });
        }

        function prepareAddMode() {
            meeModalMode = 'add';
            $('#meeAddModalLabel').text('Add MDO/ Escort Exemption');
            $('#meeAddSubmitBtn').text('Add MDO/ Escort Exemption');
            $('#mdoDutyTypeForm').attr('action', storeUrl);
            $('#meeRecordPk').val('');
            $('.mee-add-only-field').removeClass('d-none');
            $('#meeEditStudentInfo').addClass('d-none').removeClass('d-flex');
            $('#meeCourseDropdown').prop('required', true).prop('disabled', false);
            $('#meeAssignStudentsTrigger').prop('disabled', false);
        }

        function prepareEditMode() {
            meeModalMode = 'edit';
            $('#meeAddModalLabel').text('Edit MDO/ Escort Exemption');
            $('#meeAddSubmitBtn').text('Update MDO/ Escort Exemption');
            $('#mdoDutyTypeForm').attr('action', updateUrl);
            $('.mee-add-only-field').addClass('d-none');
            $('#meeEditStudentInfo').removeClass('d-none').addClass('d-flex');
            $('#meeCourseDropdown').prop('required', false).prop('disabled', true);
            $('#meeAssignStudentsTrigger').prop('disabled', true);
        }

        function resetMeeAddForm() {
            var form = document.getElementById('mdoDutyTypeForm');
            if (form) {
                form.reset();
            }
            meeAllStudents = [];
            meeAssignedStudents = [];
            meePickerSelectedIds.clear();
            meePickerStudentsMap = {};
            $('#meeStudentListSearch').val('');
            $('#hiddenStudentSelect').empty();
            $('#meeRecordPk').val('');
            $('#meeEditStudentName, #meeEditCourseName').text('—');
            toggleFacultyField();
            renderAssignStudentTags();
            renderPickerTags();
            $('#meeStudentListEmpty').removeClass('d-none').text('Select course and start date to load students.');
            $('#meeStudentList').addClass('d-none').empty();
            clearMeeFormErrors();
            prepareAddMode();
        }

        function validateMeeForm() {
            clearMeeFormErrors();
            var valid = true;
            var isEdit = meeModalMode === 'edit';

            if (!isEdit && !$('#meeCourseDropdown').val()) {
                $('#meeErrorCourse').removeClass('d-none');
                $('#meeCourseDropdown').addClass('is-invalid');
                valid = false;
            }
            if (!$('#mdo_duty_type_master_pk').val()) {
                $('#meeErrorDutyType').removeClass('d-none');
                $('#mdo_duty_type_master_pk').addClass('is-invalid');
                valid = false;
            }
            if (!$('#mdo_date').val()) {
                $('#meeErrorDate').removeClass('d-none');
                $('#mdo_date').addClass('is-invalid');
                valid = false;
            }
            if (!$('#Time_from').val()) {
                $('#meeErrorTimeFrom').removeClass('d-none');
                $('#Time_from').addClass('is-invalid');
                valid = false;
            }
            if (!$('#Time_to').val()) {
                $('#meeErrorTimeTo').removeClass('d-none');
                $('#Time_to').addClass('is-invalid');
                valid = false;
            }
            if ($('#Time_from').val() && $('#Time_to').val() && $('#Time_to').val() <= $('#Time_from').val()) {
                $('#meeErrorTimeTo').removeClass('d-none').text('End time must be after start time.');
                $('#Time_to').addClass('is-invalid');
                valid = false;
            }
            if (meeEscortDutyTypeId && $('#mdo_duty_type_master_pk').val() === meeEscortDutyTypeId && !$('#faculty_master_pk').val()) {
                $('#meeErrorFaculty').removeClass('d-none');
                $('#faculty_master_pk').addClass('is-invalid');
                valid = false;
            }
            if (!isEdit && !meeAssignedStudents.length) {
                $('#meeErrorStudents').removeClass('d-none');
                $('#meeAssignStudentsTrigger').addClass('is-invalid');
                valid = false;
            }

            return valid;
        }

        $('#meeAddExemptionBtn').on('click', function() {
            resetMeeAddForm();
            meeAddModal.show();
        });

        $(document).on('click', '.mee-edit-btn', function(e) {
            e.preventDefault();

            var editId = $(this).data('edit-id');
            if (!editId) {
                return;
            }

            clearMeeFormErrors();
            resetMeeAddForm();
            prepareEditMode();

            var $editBtn = $(this);
            $editBtn.prop('disabled', true);

            $.ajax({
                url: editDataBaseUrl + '/' + editId,
                type: 'GET',
                headers: { 'Accept': 'application/json' },
                success: function(res) {
                    var record = res.record;

                    $('#meeRecordPk').val(record.pk);
                    $('#mdo_duty_type_master_pk').val(record.mdo_duty_type_master_pk);
                    $('#mdo_date').val(record.mdo_date || '');
                    $('#Time_from').val(record.Time_from || '');
                    $('#Time_to').val(record.Time_to || '');
                    $('#faculty_master_pk').val(record.faculty_master_pk || '');
                    $('#meeEditStudentName').text(record.student_name || '—');
                    $('#meeEditCourseName').text(record.course_name || '—');

                    toggleFacultyField();
                    meeAddModal.show();
                },
                error: function() {
                    Swal.fire('Error', 'Unable to load record for editing.', 'error');
                },
                complete: function() {
                    $editBtn.prop('disabled', false);
                }
            });
        });

        addModalEl.addEventListener('hidden.bs.modal', function() {
            resetMeeAddForm();
        });

        $('#mdo_duty_type_master_pk').on('change', toggleFacultyField);

        $('#meeCourseDropdown, #mdo_date').on('change', function() {
            meeAssignedStudents = [];
            meeAllStudents = [];
            renderAssignStudentTags();
        });

        $('#meeAssignStudentsTrigger').on('click', function() {
            if (meeModalMode === 'edit') {
                return;
            }
            if (!$('#meeCourseDropdown').val()) {
                $('#meeErrorCourse').removeClass('d-none');
                $('#meeCourseDropdown').addClass('is-invalid').focus();
                return;
            }
            if (!$('#mdo_date').val()) {
                $('#meeErrorDate').removeClass('d-none');
                $('#mdo_date').addClass('is-invalid').focus();
                return;
            }

            setPickerFromAssigned();
            loadStudentsForPicker().always(function() {
                meeStudentModal.show();
            });
        });

        $('#meeStudentListSearch').on('input', renderStudentList);

        $(document).on('change', '#meeStudentList .mee-student-pick', function() {
            var id = String($(this).val());
            if (this.checked) {
                meePickerSelectedIds.add(id);
            } else {
                meePickerSelectedIds.delete(id);
            }
            renderPickerTags();
        });

        $('#meeStudentTags').on('click', '.btn-close', function() {
            var id = String($(this).closest('[data-student-id]').data('student-id'));
            meePickerSelectedIds.delete(id);
            $('.mee-student-pick[value="' + id + '"]').prop('checked', false);
            renderPickerTags();
        });

        $('#meeAssignStudentsTags').on('click', '.btn-close', function() {
            var id = String($(this).closest('[data-student-id]').data('student-id'));
            meeAssignedStudents = meeAssignedStudents.filter(function(s) {
                return String(s.pk) !== id;
            });
            renderAssignStudentTags();
        });

        $('#meeStudentClearAll').on('click', function() {
            meePickerSelectedIds.clear();
            $('.mee-student-pick').prop('checked', false);
            renderPickerTags();
        });

        $('#meeStudentSelectAll').on('click', function() {
            $('#meeStudentList .mee-student-pick:visible').each(function() {
                meePickerSelectedIds.add(String($(this).val()));
                $(this).prop('checked', true);
            });
            renderPickerTags();
        });

        $('#meeStudentSave').on('click', function() {
            var ids = Array.from(meePickerSelectedIds);
            if (!ids.length) {
                Swal.fire('Required', 'Please select at least one student.', 'warning');
                return;
            }
            meeAssignedStudents = ids.map(function(id) {
                return meePickerStudentsMap[id];
            }).filter(Boolean);
            renderAssignStudentTags();
            $('#meeErrorStudents').addClass('d-none');
            $('#meeAssignStudentsTrigger').removeClass('is-invalid');
            meeStudentModal.hide();
        });

        $('#mdoDutyTypeForm').on('submit', function(e) {
            e.preventDefault();

            if (!validateMeeForm()) {
                return;
            }

            var $submit = $('#meeAddSubmitBtn');
            var defaultText = $submit.text();
            var formData = new FormData(this);

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
                success: function(response) {
                    meeAddModal.hide();
                    table.ajax.reload(null, false);
                    var defaultMsg = meeModalMode === 'edit'
                        ? 'MDO/Escort Exemption updated successfully.'
                        : 'MDO/Escort Exemption created successfully.';
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: (response && response.message) ? response.message : defaultMsg,
                        timer: 2200,
                        showConfirmButton: false
                    });
                },
                error: function(xhr) {
                    var message = 'Something went wrong. Please try again.';
                    var errors = xhr.responseJSON && xhr.responseJSON.errors ? xhr.responseJSON.errors : null;

                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    } else if (errors) {
                        message = Object.values(errors).flat().join('<br>');
                    }

                    showMeeFormError(message, errors);
                },
                complete: function() {
                    $submit.prop('disabled', false).text(defaultText);
                }
            });
        });
    }

    $('#downloadBtn').on('click', function() {
        var $table = $('<div>').html(meeGetExportTableClone()).find('table');
        if (!$table.length) {
            return;
        }

        var rows = [];
        $table.find('tr').each(function() {
            var cells = [];
            $(this).find('th, td').each(function() {
                var text = $(this).text().replace(/\s+/g, ' ').trim().replace(/"/g, '""');
                cells.push('"' + text + '"');
            });
            if (cells.length) {
                rows.push(cells.join(','));
            }
        });

        if (!rows.length) {
            return;
        }

        var csv = rows.join('\r\n');
        var blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' });
        var url = URL.createObjectURL(blob);
        var link = document.createElement('a');
        link.href = url;
        link.download = 'MDO_Escort_Exemption_' + new Date().toISOString().slice(0, 10) + '.csv';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    });
});
</script>
@endpush
