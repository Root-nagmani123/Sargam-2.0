@extends('admin.layouts.master')

@section('title', 'Examination Drive Configuration')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
@endpush

@section('setup_content')
<div class="container-fluid examination-drive-page">
    <x-breadcrum title="Examination Drive Configuration">
        <button type="button"
                class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm"
                id="edAddBtn" data-bs-toggle="modal" data-bs-target="#edFormModal">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Create Examination Drive</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <ul class="nav nav-pills gap-2 p-1 rounded-1 programme-status-tabs bg-white" role="group" aria-label="Filter examination drives by status">
            <li class="nav-item" role="presentation">
                <button type="button"
                    class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill active"
                    id="edFilterActive"
                    aria-pressed="true"
                    aria-current="true">
                    Active
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button type="button"
                    class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill"
                    id="edFilterArchive"
                    aria-pressed="false">
                    Archived
                </button>
            </li>
        </ul>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar programme-choices-bootstrap">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Filters</span>
                    <div class="programme-dt-filter-select">
                        <select id="edCourseFilter" class="form-select js-programme-choice">
                            <option value="">Course</option>
                            @foreach($courses ?? [] as $pk => $name)
                            <option value="{{ $pk }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="button" class="btn programme-dt-btn-reset" id="edResetFilters">
                        Reset Filters
                    </button>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="edPrintBtn" title="Print">
                        <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
                    </button>
                    <button type="button" class="btn programme-dt-btn-columns" id="edPdfBtn" title="Download PDF">
                        <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i><span>PDF</span>
                    </button>
                    <button type="button" class="btn programme-dt-btn-columns" id="edExcelBtn" title="Download Excel">
                        <i class="bi bi-file-earmark-excel" aria-hidden="true"></i><span>Excel</span>
                    </button>
                    <button type="button" class="btn programme-dt-btn-columns" id="edBtnColumns"
                        data-bs-toggle="modal" data-bs-target="#edColumnVisibilityModal"
                        title="Show / hide columns">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="edDtSearch" class="programme-dt-search" data-dt-search-for="examinationdrive-table"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table']) !!}
                </div>
                <div id="edDtFooter" class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                     data-dt-footer-for="examinationdrive-table"></div>
            </div>

        </div>
    </div>
</div>

<!-- Create / Edit Examination Drive Modal -->
<div class="modal fade" id="edFormModal" tabindex="-1" aria-labelledby="edFormModalLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <form id="edForm" action="{{ route('master.examination.drive.store') }}" method="POST" novalidate>
                @csrf
                <input type="hidden" name="id" id="edId" value="">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold mb-0" id="edFormModalLabel">Create Examination Drive</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="edFormAlert" class="alert d-none mb-3" role="alert"></div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="edExaminationType" class="form-label fw-semibold">Examination Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="edExaminationType" name="examination_type_master_pk" required>
                                <option value="">Select Examination Type</option>
                                @foreach($examinationTypes ?? [] as $pk => $name)
                                <option value="{{ $pk }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" data-field="examination_type_master_pk"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="edTerm" class="form-label fw-semibold">Term <span class="text-danger">*</span></label>
                            <select class="form-select" id="edTerm" name="term_master_pk" required>
                                <option value="">Select Term</option>
                                @foreach($terms ?? [] as $pk => $name)
                                <option value="{{ $pk }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" data-field="term_master_pk"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="edCourse" class="form-label fw-semibold">Course <span class="text-danger">*</span></label>
                            <select class="form-select" id="edCourse" name="course_master_pk" required>
                                <option value="">Select Course</option>
                                @foreach($courses ?? [] as $pk => $name)
                                <option value="{{ $pk }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" data-field="course_master_pk"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="edPhase" class="form-label fw-semibold">Phase <span class="text-danger">*</span></label>
                            <select class="form-select" id="edPhase" name="phase" required>
                                <option value="">Select Phase</option>
                                @foreach($phases ?? [] as $phase)
                                <option value="{{ $phase }}">{{ $phase }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" data-field="phase"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="edAcademicSession" class="form-label fw-semibold">Academic Session <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="edAcademicSession" name="academic_session"
                                   placeholder="eg. 2026" min="2000" max="2100" required>
                            <div class="invalid-feedback" data-field="academic_session"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="edStatus" class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="edStatus" name="status" required>
                                @foreach($statuses ?? [] as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" data-field="status"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="edStartDate" class="form-label fw-semibold">Start Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="edStartDate" name="start_date" required>
                            <div class="invalid-feedback" data-field="start_date"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="edEndDate" class="form-label fw-semibold">End Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="edEndDate" name="end_date" required>
                            <div class="invalid-feedback" data-field="end_date"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 gap-2 justify-content-end">
                    <button type="button" class="btn btn-outline-primary rounded-1 px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-1 px-4" id="edSubmitBtn">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="edColumnVisibilityModal" tabindex="-1" aria-labelledby="edColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="edColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="edColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{!! $dataTable->scripts() !!}
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script>
    $(document).ready(function () {
        var TABLE_ID = '#examinationdrive-table';
        var table;
        var currentFilter = 'active'; // Active tab is selected by default
        var edCourseChoices = null;

        var edChoiceOpts = {
            searchEnabled: true,
            shouldSort: false,
            itemSelectText: '',
            allowHTML: false,
            classNames: {
                containerOuter: ['choices', 'w-100', 'programme-dt-filter-select'],
                containerInner: ['choices__inner'],
                input: ['choices__input', 'form-control', 'form-control-sm', 'border-0', 'shadow-none', 'my-1'],
                inputCloned: ['choices__input--cloned'],
                list: ['choices__list'],
                listItems: ['choices__list--multiple'],
                listSingle: ['choices__list--single'],
                listDropdown: ['choices__list--dropdown', 'dropdown-menu', 'mt-1', 'p-0', 'shadow-sm', 'w-100'],
                item: ['choices__item', 'dropdown-item', 'rounded-0'],
                itemSelectable: ['choices__item--selectable'],
                itemDisabled: ['choices__item--disabled', 'disabled'],
                itemChoice: ['choices__item--choice'],
                description: ['choices__description', 'small', 'text-muted'],
                placeholder: ['choices__placeholder', 'text-muted', 'opacity-75'],
                group: ['choices__group'],
                groupHeading: ['choices__heading', 'dropdown-header', 'text-uppercase', 'small'],
                button: ['choices__button'],
                activeState: ['is-active'],
                focusState: ['is-focused'],
                openState: ['is-open'],
                disabledState: ['is-disabled'],
                highlightedState: ['is-highlighted', 'active'],
                flippedState: ['is-flipped'],
                loadingState: ['is-loading'],
                invalidState: ['is-invalid'],
                notice: ['choices__notice', 'dropdown-item-text', 'text-muted', 'small', 'py-2'],
                addChoice: ['choices__item--selectable', 'add-choice'],
                noResults: ['has-no-results'],
                noChoices: ['has-no-choices'],
            }
        };

        function edInitCourseFilterChoices() {
            if (typeof Choices === 'undefined') {
                return;
            }
            var el = document.getElementById('edCourseFilter');
            if (!el || el.dataset.choicesInitialized === 'true') {
                return;
            }
            edCourseChoices = new Choices(el, edChoiceOpts);
            el.dataset.choicesInitialized = 'true';
        }

        /* Search box, pagination and the "Showing N of M items" count are relocated
           into #edDtSearch / #edDtFooter by the global enhancer
           (public/js/datatable-global-ui.js) via the data-dt-search-for /
           data-dt-footer-for hooks on those slots. Do NOT rebuild them here — a
           second enhancer duplicates the global one and can race it. */

        /* ---- Column show / hide (DataTables API) ---- */
        var edColStorageKey = 'edGrid:hiddenColumns:v1';

        function edGetHiddenCols() {
            try {
                var raw = localStorage.getItem(edColStorageKey);
                var arr = raw ? JSON.parse(raw) : [];
                return Array.isArray(arr) ? arr : [];
            } catch (e) {
                return [];
            }
        }

        function edPersistHiddenCols(arr) {
            try { localStorage.setItem(edColStorageKey, JSON.stringify(arr)); } catch (e) {}
        }

        function setupEdColumns(dt) {
            if (!dt) {
                return;
            }
            var hidden = edGetHiddenCols();

            dt.columns().every(function () {
                var idx = this.index();
                this.visible(hidden.indexOf(idx) === -1, false);
            });
            dt.columns.adjust();

            var $grid = $('#edColumnToggleGrid');
            if (!$grid.length) {
                return;
            }
            $grid.empty();

            dt.columns().every(function () {
                var idx = this.index();
                var title = $(this.header()).text().replace(/\s+/g, ' ').trim();
                if (!title) {
                    return;
                }

                var inputId = 'edcolvis_' + idx;
                var $cell = $('<div class="col-12 col-sm-6 col-md-4"></div>');
                var $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                    .attr('for', inputId);
                var $cb = $('<input type="checkbox" class="form-check-input m-0">')
                    .attr('id', inputId)
                    .prop('checked', hidden.indexOf(idx) === -1);

                $cb.on('change', function () {
                    var h = edGetHiddenCols();
                    var pos = h.indexOf(idx);
                    if (this.checked) {
                        if (pos !== -1) h.splice(pos, 1);
                    } else {
                        if (pos === -1) h.push(idx);
                    }
                    edPersistHiddenCols(h);
                    dt.column(idx).visible(this.checked, false);
                    dt.columns.adjust();
                });

                $label.append($cb).append($('<span></span>').text(title));
                $cell.append($label);
                $grid.append($cell);
            });
        }

        /* ---- Wait for Yajra DataTable init ---- */
        setTimeout(function () {
            if (!$.fn.DataTable.isDataTable(TABLE_ID)) {
                return;
            }
            table = $(TABLE_ID).DataTable();

            setupEdColumns(table);

            edInitCourseFilterChoices();

            // Pass the Active/Archived filter and Course filter to the server on every (re)draw.
            $(TABLE_ID).on('preXhr.dt', function (e, settings, data) {
                data.status_filter = currentFilter;
                var courseFilter = $('#edCourseFilter').val();
                if (courseFilter) {
                    data.course_filter = courseFilter;
                }
            });
        }, 150);

        $(document).on('change', '#edCourseFilter', function () {
            if (table) {
                table.ajax.reload();
            }
        });

        $('#edResetFilters').on('click', function () {
            $('#edCourseFilter').val('');
            if (edCourseChoices) {
                edCourseChoices.setChoiceByValue('');
            }
            if (table) {
                table.ajax.reload();
            }
        });

        function edSetActiveTab($activeBtn) {
            $('#edFilterActive, #edFilterArchive')
                .removeClass('active')
                .attr('aria-pressed', 'false')
                .removeAttr('aria-current');

            $activeBtn
                .addClass('active')
                .attr('aria-pressed', 'true')
                .attr('aria-current', 'true');
        }

        // Course filter list depends on the tab: Active tab -> current/upcoming
        // courses, Archived tab -> courses whose end_date has passed.
        function edLoadCoursesByStatus(status) {
            $.ajax({
                url: '{{ route("master.examination.drive.get.courses.by.status") }}',
                type: 'GET',
                data: { status: status },
                success: function (response) {
                    if (!response.success) {
                        return;
                    }
                    var $courseFilter = $('#edCourseFilter');
                    $courseFilter.find('option:not(:first)').remove();
                    $.each(response.courses, function (pk, name) {
                        $courseFilter.append($('<option>', { value: pk, text: name }));
                    });
                    $courseFilter.val('');

                    if (edCourseChoices) {
                        edCourseChoices.destroy();
                        $courseFilter[0].dataset.choicesInitialized = 'false';
                        edCourseChoices = null;
                    }
                    edInitCourseFilterChoices();

                    if (table) {
                        table.ajax.reload();
                    }
                }
            });
        }

        $('#edFilterActive').on('click', function () {
            edSetActiveTab($(this));
            currentFilter = 'active';
            edLoadCoursesByStatus('active');
        });

        $('#edFilterArchive').on('click', function () {
            edSetActiveTab($(this));
            currentFilter = 'archive';
            edLoadCoursesByStatus('archive');
        });

        /* ---- Print / PDF / Excel export (current tab + course filter applied) ---- */
        function edExportUrl(format) {
            var params = new URLSearchParams({ format: format, status_filter: currentFilter });
            var courseFilter = $('#edCourseFilter').val();
            if (courseFilter) {
                params.set('course_filter', courseFilter);
            }
            return '{{ route("master.examination.drive.export") }}?' + params.toString();
        }

        $('#edPrintBtn').on('click', function () {
            window.open(edExportUrl('print'), '_blank');
        });

        $('#edPdfBtn').on('click', function () {
            window.location.href = edExportUrl('pdf');
        });

        $('#edExcelBtn').on('click', function () {
            window.location.href = edExportUrl('excel');
        });

        /* ---- Delete confirmation ---- */
        $(document).on('submit', '#examinationdrive-table .ed-delete-form', function (e) {
            if (!confirm('Are you sure you want to delete this examination drive?')) {
                e.preventDefault();
            }
        });

        /* ---- Create / Edit modal ---- */
        var $form = $('#edForm');
        var $alert = $('#edFormAlert');

        function edClearErrors() {
            $form.find('.is-invalid').removeClass('is-invalid');
            $form.find('.invalid-feedback').text('');
            $alert.addClass('d-none').removeClass('alert-danger alert-success').empty();
        }

        function edResetForm() {
            $form[0].reset();
            $('#edId').val('');
            $('#edCourse option[data-injected="true"]').remove();
            edClearErrors();
        }

        // Open for "Create"
        $('#edAddBtn').on('click', function () {
            edResetForm();
            $('#edFormModalLabel').text('Create Examination Drive');
            $('#edSubmitBtn').text('Save');
        });

        // Open for "Edit"
        $(document).on('click', '#examinationdrive-table .ed-edit-btn', function () {
            var $btn = $(this);
            edResetForm();
            $('#edFormModalLabel').text('Edit Examination Drive');
            $('#edSubmitBtn').text('Update');

            $('#edId').val($btn.data('id'));
            $('#edExaminationType').val(String($btn.data('examination_type_master_pk')));
            $('#edTerm').val(String($btn.data('term_master_pk')));

            // The modal's Course list only has current/upcoming courses; an
            // archived record's course won't be in it, so add it back so the
            // record (and its original course) can still be edited/saved.
            var coursePk = String($btn.data('course_master_pk'));
            var $courseSelect = $('#edCourse');
            if (!$courseSelect.find('option[value="' + coursePk + '"]').length) {
                $courseSelect.append($('<option>', { value: coursePk, text: $btn.data('course_name'), 'data-injected': 'true' }));
            }
            $courseSelect.val(coursePk);

            $('#edPhase').val($btn.data('phase'));
            $('#edAcademicSession').val($btn.data('academic_session'));
            $('#edStartDate').val($btn.data('start_date'));
            $('#edEndDate').val($btn.data('end_date'));
            $('#edStatus').val(String($btn.data('status')));

            bootstrap.Modal.getOrCreateInstance(document.getElementById('edFormModal')).show();
        });

        // AJAX submit (create + update share the store route)
        $form.on('submit', function (e) {
            e.preventDefault();
            edClearErrors();

            var $submit = $('#edSubmitBtn');
            var originalText = $submit.text();
            $submit.prop('disabled', true)
                   .html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Saving...');

            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: $form.serialize(),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                success: function (response) {
                    $alert.removeClass('d-none alert-danger').addClass('alert-success')
                          .html('<i class="bi bi-check-circle me-1"></i>' + (response.message || 'Saved successfully.'));

                    if ($.fn.DataTable.isDataTable(TABLE_ID)) {
                        $(TABLE_ID).DataTable().ajax.reload(null, false);
                    }

                    setTimeout(function () {
                        bootstrap.Modal.getInstance(document.getElementById('edFormModal'))?.hide();
                        edResetForm();
                    }, 1000);
                },
                error: function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        var errors = xhr.responseJSON.errors;
                        Object.keys(errors).forEach(function (field) {
                            $form.find('[name="' + field + '"]').addClass('is-invalid');
                            $form.find('.invalid-feedback[data-field="' + field + '"]').text(errors[field][0]);
                        });
                    } else {
                        var msg = (xhr.responseJSON && xhr.responseJSON.message)
                            ? xhr.responseJSON.message
                            : 'An error occurred while saving. Please try again.';
                        $alert.removeClass('d-none alert-success').addClass('alert-danger')
                              .html('<i class="bi bi-exclamation-circle me-1"></i>' + msg);
                    }
                },
                complete: function () {
                    $submit.prop('disabled', false).text(originalText);
                }
            });
        });

        // Reset on close so a stale edit can't leak into Create
        document.getElementById('edFormModal').addEventListener('hidden.bs.modal', function () {
            edResetForm();
            $('#edFormModalLabel').text('Create Examination Drive');
            $('#edSubmitBtn').text('Save');
        });
    });
</script>
@endpush
