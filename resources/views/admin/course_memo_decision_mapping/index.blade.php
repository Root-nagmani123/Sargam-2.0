@extends('admin.layouts.master')

@section('title', 'Course Memo Decision Mapping')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
@endpush

@section('setup_content')
<div class="container-fluid cmdm-master-page">
    <x-breadcrum title="Course Memo Decision Mapping" :showBack="false">
        <button type="button"
            id="showConclusionAlert"
            class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-2 fw-semibold shadow-sm"
            aria-controls="conclusionModal">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Add New Mapping</span>
        </button>
    </x-breadcrum>

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <ul class="nav nav-pills gap-2 p-1 rounded-1 programme-status-tabs bg-white" role="group" aria-label="Filter mappings by status">
            <li class="nav-item" role="presentation">
                <button type="button"
                    class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill active"
                    id="cmdmFilterActive"
                    aria-pressed="true"
                    aria-current="true">
                    Active
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button type="button"
                    class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill"
                    id="cmdmFilterArchive"
                    aria-pressed="false">
                    Archived
                </button>
            </li>
        </ul>
    </div>

    <div class="card cmdm-dt-card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar programme-choices-bootstrap">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Filters</span>
                    <div class="programme-dt-filter-select">
                        <select id="cmdmCourseFilter" class="form-select js-programme-choice">
                            <option value="">Course Name</option>
                            @foreach($filterCourses ?? [] as $pk => $name)
                            <option value="{{ $pk }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="button" class="btn programme-dt-btn-reset" id="cmdmResetFilters">
                        Reset Filters
                    </button>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="btnCmdmColumns"
                        data-bs-toggle="modal" data-bs-target="#cmdmColumnVisibilityModal"
                        title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="cmdmDtSearch" class="programme-dt-search" data-dt-search-for="memoDecisionTable"></div>
                </div>
            </div>

            <div class="programme-dt-panel cmdm-dt-scroll">
                <table class="table table-hover align-middle mb-0 w-100 programme-dt-table" id="memoDecisionTable">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">S. No.</th>
                            <th scope="col">Course Name</th>
                            <th scope="col">Memo Decision</th>
                            <th scope="col">Memo Conclusion</th>
                            <th scope="col">Status</th>
                            <th scope="col">Action</th>
                        </tr>
                    </thead>
                </table>
                <div id="cmdmDtFooter" class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3" data-dt-footer-for="memoDecisionTable"></div>
            </div>
        </div>
    </div>
</div>

<!-- Add mapping modal -->
<div class="modal fade cmdm-form-modal" id="conclusionModal" tabindex="-1" aria-labelledby="conclusionModalLabel" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered cmdm-modal-dialog">
        <div class="modal-content cgt-form-modal border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom">
                <h5 class="modal-title mb-0 fw-bold" id="conclusionModalLabel">Add Memo Conclusion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-4 pb-2">
                <form id="conclusionForm" novalidate>
                    <div class="mb-4">
                        <label for="course_master_pk" class="form-label cgt-field-label mb-2">
                            Select Course <span class="text-danger">*</span>
                        </label>
                        <select name="course_master_pk" id="course_master_pk" class="form-select rounded-3" required>
                            <option value="">Select Course</option>
                            @foreach($CourseMaster as $course)
                            <option value="{{ $course->pk }}">{{ $course->course_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="memo_type_master_pk" class="form-label cgt-field-label mb-2">
                            Select Memo <span class="text-danger">*</span>
                        </label>
                        <select name="memo_type_master_pk" id="memo_type_master_pk" class="form-select rounded-3" required>
                            <option value="">Select Memo</option>
                            @foreach($MemoTypeMaster as $memo)
                            <option value="{{ $memo->pk }}">{{ $memo->memo_type_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="memo_conclusion_master_pk" class="form-label cgt-field-label mb-2">
                            Select Memo Conclusion <span class="text-danger">*</span>
                        </label>
                        <select name="memo_conclusion_master_pk" id="memo_conclusion_master_pk" class="form-select rounded-3" required>
                            <option value="">Select Memo Conclusion</option>
                            @foreach($MemoConclusionMaster as $memo)
                            <option value="{{ $memo->pk }}">{{ $memo->discussion_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-0">
                        <label for="active_inactive" class="form-label cgt-field-label mb-2">
                            Status <span class="text-danger">*</span>
                        </label>
                        <select name="active_inactive" id="active_inactive" class="form-select rounded-3" required>
                            <option value="">Select Status</option>
                            <option value="1">Active</option>
                            <option value="2">Inactive</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top border-0 gap-2 justify-content-end pt-3 pb-4 px-4">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4 py-2" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="submitConclusionForm" class="btn btn-primary rounded-3 px-4 py-2">Create Memo Mapping</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit mapping modal -->
<div class="modal fade cmdm-form-modal" id="editconclusionModal" tabindex="-1" aria-labelledby="editConclusionLabel" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered cmdm-modal-dialog">
        <div class="modal-content cgt-form-modal border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom">
                <h5 class="modal-title mb-0 fw-bold" id="editConclusionLabel">Edit Memo Conclusion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-4 pb-2">
                <form id="edit_conclusionForm" novalidate>
                    <input type="hidden" id="edit_id" name="edit_id">

                    <div class="mb-4">
                        <label for="edit_course_master_pk" class="form-label cgt-field-label mb-2">
                            Select Course <span class="text-danger">*</span>
                        </label>
                        <select id="edit_course_master_pk" class="form-select rounded-3">
                            <option value="">Select Course</option>
                            @foreach($CourseMaster as $course)
                            <option value="{{ $course->pk }}">{{ $course->course_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="edit_memo_type_master_pk" class="form-label cgt-field-label mb-2">
                            Select Memo <span class="text-danger">*</span>
                        </label>
                        <select id="edit_memo_type_master_pk" class="form-select rounded-3">
                            <option value="">Select Memo</option>
                            @foreach($MemoTypeMaster as $memo)
                            <option value="{{ $memo->pk }}">{{ $memo->memo_type_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="edit_memo_conclusion_master_pk" class="form-label cgt-field-label mb-2">
                            Select Memo Conclusion <span class="text-danger">*</span>
                        </label>
                        <select id="edit_memo_conclusion_master_pk" class="form-select rounded-3">
                            <option value="">Select Memo Conclusion</option>
                            @foreach($MemoConclusionMaster as $memo)
                            <option value="{{ $memo->pk }}">{{ $memo->discussion_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-0">
                        <label for="edit_active_inactive" class="form-label cgt-field-label mb-2">
                            Status <span class="text-danger">*</span>
                        </label>
                        <select id="edit_active_inactive" class="form-select rounded-3">
                            <option value="">Select Status</option>
                            <option value="1">Active</option>
                            <option value="2">Inactive</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top border-0 gap-2 justify-content-end pt-3 pb-4 px-4">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4 py-2" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary rounded-3 px-4 py-2" id="edit_submitConclusionForm">Update</button>
            </div>
        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="cmdmColumnVisibilityModal" tabindex="-1" aria-labelledby="cmdmColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="cmdmColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="cmdmColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script>
    $(function() {
        const tableSelector = '#memoDecisionTable';

        const addModalEl = document.getElementById('conclusionModal');
        const editModalEl = document.getElementById('editconclusionModal');

        [addModalEl, editModalEl].forEach(function(modalEl) {
            if (modalEl && modalEl.parentElement && modalEl.parentElement !== document.body) {
                document.body.appendChild(modalEl);
            }
        });

        // ── Active / Archived tab + course filter state ──
        // Bound before init so the first ajax request carries the filters too.
        let cmdmCurrentFilter = 'active';

        $(tableSelector).on('preXhr.dt', function(e, settings, data) {
            data.status_filter = cmdmCurrentFilter;
            const courseVal = $('#cmdmCourseFilter').val();
            if (courseVal) {
                data.course_filter = courseVal;
            }
        });

        if (!$.fn.DataTable.isDataTable(tableSelector)) {
            $(tableSelector).DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('course.memo.decision.index') }}",
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'course_name',
                        name: 'course.course_name'
                    },
                    {
                        data: 'memo_decision',
                        name: 'memo.memo_type_name'
                    },
                    {
                        data: 'memo_conclusion',
                        name: 'memoConclusion.discussion_name'
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
                ],
                order: [
                    [1, 'asc']
                ]
            });
        }

        /* ---------------- Column show / hide (DataTables API) ---------------- */
        var cmdmColStorageKey = 'cmdmGrid:hiddenColumns:v1';

        function cmdmGetHiddenCols() {
            try {
                var raw = localStorage.getItem(cmdmColStorageKey);
                var arr = raw ? JSON.parse(raw) : [];
                return Array.isArray(arr) ? arr : [];
            } catch (e) {
                return [];
            }
        }

        function cmdmPersistHiddenCols(arr) {
            try { localStorage.setItem(cmdmColStorageKey, JSON.stringify(arr)); } catch (e) {}
        }

        function setupCmdmColumns(dt) {
            if (!dt) {
                return;
            }
            var hidden = cmdmGetHiddenCols();

            // Apply saved visibility — DataTables keeps this across redraws / ajax reloads.
            dt.columns().every(function() {
                var idx = this.index();
                this.visible(hidden.indexOf(idx) === -1, false);
            });
            dt.columns.adjust();

            // Build the modal checkboxes once from the live table headers.
            var $grid = $('#cmdmColumnToggleGrid');
            if (!$grid.length) {
                return;
            }
            $grid.empty();

            dt.columns().every(function() {
                var idx = this.index();
                var title = $(this.header()).text().replace(/\s+/g, ' ').trim();
                if (!title) {
                    return;
                }

                var inputId = 'cmdmcolvis_' + idx;
                var $cell = $('<div class="col-12 col-sm-6 col-md-4"></div>');
                var $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                    .attr('for', inputId);
                var $cb = $('<input type="checkbox" class="form-check-input m-0">')
                    .attr('id', inputId)
                    .prop('checked', hidden.indexOf(idx) === -1);

                $cb.on('change', function() {
                    var h = cmdmGetHiddenCols();
                    var pos = h.indexOf(idx);
                    if (this.checked) {
                        if (pos !== -1) h.splice(pos, 1);
                    } else {
                        if (pos === -1) h.push(idx);
                    }
                    cmdmPersistHiddenCols(h);
                    dt.column(idx).visible(this.checked, false);
                    dt.columns.adjust();
                });

                $label.append($cb).append($('<span></span>').text(title));
                $cell.append($label);
                $grid.append($cell);
            });
        }

        if ($.fn.DataTable.isDataTable(tableSelector)) {
            setupCmdmColumns($(tableSelector).DataTable());
        }

        /* ---------------- Active / Archived tabs + course filter ---------------- */
        var cmdmChoiceOpts = {
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
        var cmdmCourseChoices = null;

        function cmdmInitCourseChoices() {
            if (typeof Choices === 'undefined') {
                return;
            }
            var el = document.getElementById('cmdmCourseFilter');
            if (!el || el.dataset.choicesInitialized === 'true') {
                return;
            }
            cmdmCourseChoices = new Choices(el, cmdmChoiceOpts);
            el._choicesInstance = cmdmCourseChoices;
            el.dataset.choicesInitialized = 'true';
        }

        function cmdmRebuildCourseChoices() {
            var el = document.getElementById('cmdmCourseFilter');
            if (!el) {
                return;
            }
            if (el._choicesInstance) {
                el._choicesInstance.destroy();
                el.dataset.choicesInitialized = 'false';
                el._choicesInstance = null;
                cmdmCourseChoices = null;
            }
            cmdmInitCourseChoices();
        }

        cmdmInitCourseChoices();

        function cmdmReloadTable() {
            if ($.fn.DataTable.isDataTable(tableSelector)) {
                $(tableSelector).DataTable().ajax.reload(null, false);
            }
        }

        function cmdmSetActiveTab($btn) {
            $('#cmdmFilterActive, #cmdmFilterArchive')
                .removeClass('active')
                .attr('aria-pressed', 'false')
                .removeAttr('aria-current');
            $btn.addClass('active')
                .attr('aria-pressed', 'true')
                .attr('aria-current', 'true');
        }

        function cmdmLoadCoursesByStatus(status) {
            $.ajax({
                url: "{{ route('course.memo.decision.get.courses.by.status') }}",
                type: 'GET',
                data: {
                    status: status
                },
                success: function(res) {
                    if (res && res.success) {
                        var $sel = $('#cmdmCourseFilter');
                        $sel.find('option:not(:first)').remove();
                        $.each(res.courses, function(pk, name) {
                            $sel.append($('<option>', {
                                value: pk,
                                text: name
                            }));
                        });
                        $sel.val('');
                        cmdmRebuildCourseChoices();
                    }
                    cmdmReloadTable();
                },
                error: function() {
                    cmdmReloadTable();
                }
            });
        }

        $('#cmdmFilterActive').on('click', function() {
            cmdmSetActiveTab($(this));
            cmdmCurrentFilter = 'active';
            cmdmLoadCoursesByStatus('active');
        });

        $('#cmdmFilterArchive').on('click', function() {
            cmdmSetActiveTab($(this));
            cmdmCurrentFilter = 'archive';
            cmdmLoadCoursesByStatus('archive');
        });

        $('#cmdmCourseFilter').on('change', function() {
            cmdmReloadTable();
        });

        $('#cmdmResetFilters').on('click', function() {
            cmdmCurrentFilter = 'active';
            cmdmSetActiveTab($('#cmdmFilterActive'));
            cmdmLoadCoursesByStatus('active');
        });

        function iconOnlyBtn($btn, iconClass, extraClass) {
            $btn.removeClass('btn btn-sm btn-outline-warning btn-outline-danger btn-outline-secondary d-flex align-items-center gap-1');
            $btn.addClass('programme-action-btn ' + (extraClass || ''));
            $btn.find('.material-icons').remove();
            $btn.find('span').remove();
            if (!$btn.find('.bi').length) {
                $btn.append('<i class="bi ' + iconClass + '" aria-hidden="true"></i>');
            }
        }

        function buildToggleControl($toggle) {
            const $label = $('<label>', {
                class: 'programme-action-toggle-icon cmdm-action-toggle mb-0',
                'aria-label': 'Toggle mapping status'
            });

            $toggle.detach().addClass('cmdm-status-toggle-input').appendTo($label);
            $label.append('<i class="bi bi-toggle-off cmdm-toggle-icon cmdm-toggle-icon--off" aria-hidden="true"></i>');
            $label.append('<i class="bi bi-toggle-on cmdm-toggle-icon cmdm-toggle-icon--on" aria-hidden="true"></i>');

            return $label;
        }

        function decorateCmdmRows() {
            $(tableSelector + ' tbody tr').each(function() {
                const $row = $(this);
                if ($row.hasClass('cmdm-row-decorated')) {
                    return;
                }

                const $cells = $row.find('td');
                if ($cells.length < 6) {
                    return;
                }

                const $courseCell = $cells.eq(1);
                const $memoCell = $cells.eq(2);
                const $conclusionCell = $cells.eq(3);
                const $statusCell = $cells.eq(4);
                const $actionCell = $cells.eq(5);

                $courseCell.addClass('cmdm-col-course');
                $memoCell.addClass('cmdm-col-memo-decision');
                $conclusionCell.addClass('cmdm-col-memo-conclusion');

                const $toggle = $statusCell.find('.status-toggle').first();
                const isActive = $toggle.length ? $toggle.is(':checked') : false;

                $statusCell.empty().append(
                    $('<span>', {
                        class: 'badge rounded-1 programme-status-badge cmdm-status-badge ' +
                            (isActive ? 'programme-status-badge--active' : 'programme-status-badge--inactive'),
                        text: isActive ? 'Active' : 'Inactive'
                    })
                );

                const $editBtn = $actionCell.find('.editConclusion').first().detach();
                const $deleteForm = $actionCell.find('form').first().detach();
                const $disabledDelete = $actionCell.find('button[disabled]').not('.editConclusion').first().detach();

                const $group = $('<div>', {
                    class: 'd-inline-flex align-items-center programme-action-group',
                    role: 'group',
                    'aria-label': 'Course memo mapping actions'
                });

                if ($editBtn.length) {
                    iconOnlyBtn($editBtn, 'bi-pencil');
                    $group.append($editBtn);
                }

                if ($toggle.length) {
                    $group.append(buildToggleControl($toggle));
                }

                if ($deleteForm.length) {
                    const $deleteBtn = $deleteForm.find('button[type="submit"]').first();
                    if ($deleteBtn.length) {
                        iconOnlyBtn($deleteBtn, 'bi-trash3', 'programme-action-btn--danger');
                        $deleteForm.addClass('d-inline m-0');
                        $group.append($deleteForm);
                    }
                } else if ($disabledDelete.length) {
                    iconOnlyBtn($disabledDelete, 'bi-trash3', 'programme-action-btn--danger is-disabled');
                    $disabledDelete.prop('disabled', true).attr('aria-disabled', 'true');
                    $group.append($disabledDelete);
                }

                $actionCell.empty().append($group);
                $row.addClass('cmdm-row-decorated');
            });
        }

        function updateCmdmRowBadge($checkbox, isActive) {
            const $badge = $checkbox.closest('tr').find('.cmdm-status-badge');
            if ($badge.length) {
                $badge
                    .removeClass('programme-status-badge--active programme-status-badge--inactive')
                    .addClass(isActive ? 'programme-status-badge--active' : 'programme-status-badge--inactive')
                    .text(isActive ? 'Active' : 'Inactive');
            }
        }

        $(tableSelector).on('draw.dt', function() {
            $(tableSelector + ' tbody tr').removeClass('cmdm-row-decorated');
            decorateCmdmRows();
        });

        $(tableSelector).on('init.dt', function() {
            decorateCmdmRows();
        });

        if ($.fn.DataTable.isDataTable(tableSelector)) {
            decorateCmdmRows();
        }

        $(document).on('click', '.swal2-cancel, .swal2-deny', function() {
            setTimeout(function() {
                $(tableSelector + ' tbody .status-toggle').each(function() {
                    updateCmdmRowBadge($(this), $(this).is(':checked'));
                });
            }, 0);
        });

        document.getElementById('showConclusionAlert').addEventListener('click', function() {
            document.getElementById('conclusionForm').reset();
            if (window.bootstrap && bootstrap.Modal) {
                bootstrap.Modal.getOrCreateInstance(addModalEl).show();
            } else if (window.jQuery) {
                $('#conclusionModal').modal('show');
            }
        });

        document.getElementById('submitConclusionForm').addEventListener('click', function(e) {
            e.preventDefault();

            const course = document.getElementById('course_master_pk').value;
            const memo = document.getElementById('memo_type_master_pk').value;
            const conclusion = document.getElementById('memo_conclusion_master_pk').value;
            const status = document.getElementById('active_inactive').value;

            if (!course || !memo || !conclusion || !status) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Required',
                    text: 'Please fill all required fields!'
                });
                return;
            }

            const data = {
                course_master_pk: course,
                memo_type_master_pk: memo,
                memo_conclusion_master_pk: conclusion,
                active_inactive: status
            };

            fetch("{{ route('course.memo.decision.store') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(data)
                })
                .then(response => response.json())
                .then(res => {
                    if (res.status === true) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: res.message || 'Course Memo Decision Mapping saved successfully!',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            $('#memoDecisionTable').DataTable().ajax.reload(null, false);
                            document.getElementById('conclusionForm').reset();
                            if (window.bootstrap && bootstrap.Modal) {
                                bootstrap.Modal.getOrCreateInstance(addModalEl).hide();
                            } else {
                                $('#conclusionModal').modal('hide');
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: res.message || 'Something went wrong!'
                        });
                    }
                })
                .catch(function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Server Error',
                        text: 'Please try again later.'
                    });
                });
        });

        // These dropdowns are rendered with running courses / active memo types only.
        // A row from the Archived tab (or one whose memo type was later disabled) has
        // no matching <option>, so .val() silently left the field blank — the course
        // "disappeared", and Update then failed its required validation or forced the
        // user to reassign the mapping to a different course. Put the saved value back
        // as a labelled option so the row edits exactly as it was saved.
        function cmdmRestoreSavedOption($sel, value, label, suffix) {
            $sel.find('option[data-cmdm-restored]').remove();
            if (value === undefined || value === null || value === '') return;

            const exists = $sel.find('option').filter(function() {
                return String(this.value) === String(value);
            }).length > 0;
            if (exists) return;

            $sel.append($('<option>', {
                value: value,
                text: (label && label.length ? label : '#' + value) + ' ' + suffix,
                'data-cmdm-restored': '1'
            }));
        }

        $(document).on('click', '.editConclusion', function() {
            const id = $(this).data('id');
            const course = $(this).data('course');
            const memo = $(this).data('memo');
            const conclusion = $(this).data('conclusion');
            const status = $(this).data('status');

            cmdmRestoreSavedOption($('#edit_course_master_pk'), course,
                $(this).attr('data-course-name'), '(Archived)');
            cmdmRestoreSavedOption($('#edit_memo_type_master_pk'), memo,
                $(this).attr('data-memo-name'), '(Inactive)');
            cmdmRestoreSavedOption($('#edit_memo_conclusion_master_pk'), conclusion,
                $(this).attr('data-conclusion-name'), '(Inactive)');

            $('#edit_id').val(id);
            $('#edit_course_master_pk').val(course).trigger('change');
            $('#edit_memo_type_master_pk').val(memo).trigger('change');
            $('#edit_memo_conclusion_master_pk').val(conclusion).trigger('change');
            $('#edit_active_inactive').val(status).trigger('change');

            if (window.bootstrap && bootstrap.Modal) {
                bootstrap.Modal.getOrCreateInstance(editModalEl).show();
            } else if (window.jQuery) {
                $('#editconclusionModal').modal('show');
            }
        });

        $('#edit_submitConclusionForm').on('click', function(e) {
            e.preventDefault();

            const data = {
                id: $('#edit_id').val(),
                course_master_pk: $('#edit_course_master_pk').val(),
                memo_type_master_pk: $('#edit_memo_type_master_pk').val(),
                memo_conclusion_master_pk: $('#edit_memo_conclusion_master_pk').val(),
                active_inactive: $('#edit_active_inactive').val()
            };

            fetch("{{ route('course.memo.decision.update') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(data)
                })
                .then(res => res.json())
                .then(res => {
                    if (res.status) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Updated',
                            text: res.message,
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            $('#memoDecisionTable').DataTable().ajax.reload(null, false);
                            if (window.bootstrap && bootstrap.Modal) {
                                bootstrap.Modal.getOrCreateInstance(editModalEl).hide();
                            } else {
                                $('#editconclusionModal').modal('hide');
                            }
                        });
                    }
                });
        });
    });
</script>
@endsection
