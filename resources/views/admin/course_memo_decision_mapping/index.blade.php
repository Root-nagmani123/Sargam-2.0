@extends('admin.layouts.master')

@section('title', 'Course Memo Decision Mapping')

@push('styles')
@include('admin.layouts.partials.select2-assets')
<link rel="stylesheet" href="{{ asset('css/master-admin.css') }}?v={{ @filemtime(public_path('css/master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
{{-- Root is `mst-page cmdm-page`, deliberately NOT `cmdm-master-page`: the
     `.cmdm-master-page #memoDecisionTable …` block in public/css/custom.css
     styles the retired icon-only action row and its ID-level rules would
     out-rank the shared .mst-act stack (min-width, padding, colours). --}}
<div class="container-fluid mst-page cmdm-page">
    <x-breadcrum title="Course Memo Decision Mapping" :showBack="false">
        {{-- #showConclusionAlert opens the Add modal (#conclusionModal). --}}
        <button type="button" id="showConclusionAlert"
                class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Add Mapping</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    {{-- Scope tabs above the card (§1). They split the mappings by the COURSE's
         lifecycle (status_filter=active|archive), not by the mapping's own
         Active/Inactive status — the hint says so, because the Status column
         below means something else. --}}
    <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
        <ul class="nav nav-pills gap-2 p-1 rounded-1 programme-status-tabs bg-white mb-0"
            role="group" aria-label="Show mappings by course status" aria-describedby="cmdmScopeHint">
            <li class="nav-item" role="presentation">
                <button type="button"
                        class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill active"
                        id="cmdmFilterActive" aria-pressed="true" aria-current="true">
                    Active
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button type="button"
                        class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill"
                        id="cmdmFilterArchive" aria-pressed="false">
                    Archived
                </button>
            </li>
        </ul>
        <p class="text-muted small mb-0" id="cmdmScopeHint">
            <span class="fw-semibold">Active</span> lists mappings of running or upcoming courses;
            <span class="fw-semibold">Archived</span> lists mappings of courses that have ended.
        </p>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            {{-- Two filters + Reset and Columns + search do not fit one row
                 beside the sidebar, so the toolbar WRAPS as two whole groups
                 (filters left, Columns + search right-aligned below) instead
                 of squeezing each group into a ragged second line. --}}
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 programme-dt-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Filters</span>

                    {{-- Course options follow the selected tab: they are rebuilt from
                         get-courses-by-status whenever Active / Archived changes. --}}
                    <div class="programme-dt-filter-select">
                        <select id="cmdmCourseFilter" class="form-select mst-control mst-searchable"
                                data-placeholder="All courses" aria-label="Filter by course">
                            <option value="">All courses</option>
                            @foreach($filterCourses ?? [] as $pk => $name)
                                <option value="{{ $pk }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="programme-dt-filter-select">
                        <select id="memoConclusionFilter" class="form-select mst-control mst-searchable"
                                data-placeholder="All memo conclusions" aria-label="Filter by memo conclusion">
                            <option value="">All memo conclusions</option>
                            @foreach($MemoConclusionMaster as $memo)
                                <option value="{{ $memo->pk }}">{{ $memo->discussion_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="button" class="btn programme-dt-btn-reset" id="cmdmResetFilters">
                        Reset Filters
                    </button>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 ms-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="btnCmdmColumns"
                            data-bs-toggle="modal" data-bs-target="#cmdmColumnVisibilityModal"
                            title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="cmdmDtSearch" class="programme-dt-search" data-dt-search-for="memoDecisionTable"></div>
                </div>
            </div>

            {{-- Search, pager and "Showing N of M items" are relocated into the
                 slots by public/js/datatable-global-ui.js. --}}
            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 w-100 programme-dt-table" id="memoDecisionTable">
                        <caption class="visually-hidden">Course memo decision mappings: each course with the memo decision and conclusion mapped to it</caption>
                        <thead>
                            <tr>
                                <th scope="col">S. No.</th>
                                <th scope="col">Course</th>
                                <th scope="col">Memo Decision</th>
                                <th scope="col">Memo Conclusion</th>
                                <th scope="col">Status</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
                <div id="cmdmDtFooter" class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                     data-dt-footer-for="memoDecisionTable"></div>
            </div>

        </div>
    </div>

    {{-- Row markup. The feed (CourseMemoDecisionMappController@index) still
         returns its legacy switch / Edit / Delete HTML; the grid renders these
         shared partials instead and fills the __CMDM_*__ slots from each row
         (see cmdmRenderActions). Active rows keep a disabled Delete, exactly as
         the feed did. --}}
    <template id="cmdmTplStatusOn">@include('admin.master.partials.grid-status', ['active' => true])</template>
    <template id="cmdmTplStatusOff">@include('admin.master.partials.grid-status', ['active' => false])</template>
    @php
        $cmdmEditTpl = [
            'class' => 'editConclusion',
            'attrs' => [
                'data-id'              => '__CMDM_ID__',
                'data-course'          => '__CMDM_COURSE__',
                'data-course-name'     => '__CMDM_COURSE_NAME__',
                'data-memo'            => '__CMDM_MEMO__',
                'data-memo-name'       => '__CMDM_MEMO_NAME__',
                'data-conclusion'      => '__CMDM_CONCLUSION__',
                'data-conclusion-name' => '__CMDM_CONCLUSION_NAME__',
                'data-status'          => '__CMDM_STATUS__',
            ],
        ];
        $cmdmToggleTpl = [
            'table'  => 'course_memo_decision_mapp',
            'column' => 'active_inactive',
            'id'     => '__CMDM_ID__',
        ];
    @endphp
    <template id="cmdmTplActionsOn">@include('admin.master.partials.grid-actions', [
        'name'   => '__CMDM_NAME__',
        'edit'   => $cmdmEditTpl,
        'toggle' => $cmdmToggleTpl + ['active' => true],
        'delete' => ['disabled' => true, 'reason' => 'Active mappings cannot be deleted. Deactivate it first.'],
    ])</template>
    <template id="cmdmTplActionsOff">@include('admin.master.partials.grid-actions', [
        'name'   => '__CMDM_NAME__',
        'edit'   => $cmdmEditTpl,
        'toggle' => $cmdmToggleTpl + ['active' => false],
        'delete' => ['action' => '__CMDM_DEL__'],
    ])</template>
</div>

<!-- Add mapping modal -->
<div class="modal fade mst-modal" id="conclusionModal" tabindex="-1" aria-labelledby="conclusionModalLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <div>
                    <h5 class="modal-title fw-bold mb-0" id="conclusionModalLabel">Add Memo Decision Mapping</h5>
                    <p class="text-muted small mb-0 mt-1" id="conclusionModalHint">Pick the course, then the memo decision and the conclusion it maps to.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="conclusionForm" novalidate aria-describedby="conclusionModalHint">
                    <div class="mst-field-card">
                        <label for="course_master_pk" class="mst-form-label d-block">
                            Course <span class="mst-req" aria-hidden="true">*</span>
                        </label>
                        <select name="course_master_pk" id="course_master_pk"
                                class="form-select mst-control mst-searchable"
                                data-placeholder="Select Course" required aria-required="true">
                            <option value="">Select Course</option>
                            @foreach($CourseMaster as $course)
                                <option value="{{ $course->pk }}">{{ $course->course_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mst-field-card">
                        <div class="mb-3">
                            <label for="memo_type_master_pk" class="mst-form-label d-block">
                                Memo Decision <span class="mst-req" aria-hidden="true">*</span>
                            </label>
                            <select name="memo_type_master_pk" id="memo_type_master_pk"
                                    class="form-select mst-control mst-searchable"
                                    data-placeholder="Select Memo Decision" required aria-required="true">
                                <option value="">Select Memo Decision</option>
                                @foreach($MemoTypeMaster as $memo)
                                    <option value="{{ $memo->pk }}">{{ $memo->memo_type_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="memo_conclusion_master_pk" class="mst-form-label d-block">
                                Memo Conclusion <span class="mst-req" aria-hidden="true">*</span>
                            </label>
                            <select name="memo_conclusion_master_pk" id="memo_conclusion_master_pk"
                                    class="form-select mst-control mst-searchable"
                                    data-placeholder="Select Memo Conclusion" required aria-required="true">
                                <option value="">Select Memo Conclusion</option>
                                @foreach($MemoConclusionMaster as $memo)
                                    <option value="{{ $memo->pk }}">{{ $memo->discussion_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-0">
                            <label for="active_inactive" class="mst-form-label d-block">
                                Status <span class="mst-req" aria-hidden="true">*</span>
                            </label>
                            <select name="active_inactive" id="active_inactive"
                                    class="form-select mst-control mst-searchable"
                                    data-placeholder="Select Status" required aria-required="true">
                                <option value="">Select Status</option>
                                <option value="1">Active</option>
                                <option value="2">Inactive</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 gap-2 justify-content-end">
                <button type="button" class="btn mst-btn-cancel px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="submitConclusionForm" class="btn mst-btn-submit px-4">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit mapping modal -->
<div class="modal fade mst-modal" id="editconclusionModal" tabindex="-1" aria-labelledby="editConclusionLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <div>
                    <h5 class="modal-title fw-bold mb-0" id="editConclusionLabel">Edit Memo Decision Mapping</h5>
                    <p class="text-muted small mb-0 mt-1" id="editConclusionHint">Change the course, the memo decision or the conclusion it maps to.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="edit_conclusionForm" novalidate aria-describedby="editConclusionHint">
                    <input type="hidden" id="edit_id" name="edit_id">

                    <div class="mst-field-card">
                        <label for="edit_course_master_pk" class="mst-form-label d-block">
                            Course <span class="mst-req" aria-hidden="true">*</span>
                        </label>
                        <select id="edit_course_master_pk" class="form-select mst-control mst-searchable"
                                data-placeholder="Select Course" aria-required="true">
                            <option value="">Select Course</option>
                            @foreach($CourseMaster as $course)
                                <option value="{{ $course->pk }}">{{ $course->course_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mst-field-card">
                        <div class="mb-3">
                            <label for="edit_memo_type_master_pk" class="mst-form-label d-block">
                                Memo Decision <span class="mst-req" aria-hidden="true">*</span>
                            </label>
                            <select id="edit_memo_type_master_pk" class="form-select mst-control mst-searchable"
                                    data-placeholder="Select Memo Decision" aria-required="true">
                                <option value="">Select Memo Decision</option>
                                @foreach($MemoTypeMaster as $memo)
                                    <option value="{{ $memo->pk }}">{{ $memo->memo_type_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="edit_memo_conclusion_master_pk" class="mst-form-label d-block">
                                Memo Conclusion <span class="mst-req" aria-hidden="true">*</span>
                            </label>
                            <select id="edit_memo_conclusion_master_pk" class="form-select mst-control mst-searchable"
                                    data-placeholder="Select Memo Conclusion" aria-required="true">
                                <option value="">Select Memo Conclusion</option>
                                @foreach($MemoConclusionMaster as $memo)
                                    <option value="{{ $memo->pk }}">{{ $memo->discussion_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-0">
                            <label for="edit_active_inactive" class="mst-form-label d-block">
                                Status <span class="mst-req" aria-hidden="true">*</span>
                            </label>
                            <select id="edit_active_inactive" class="form-select mst-control mst-searchable"
                                    data-placeholder="Select Status" aria-required="true">
                                <option value="">Select Status</option>
                                <option value="1">Active</option>
                                <option value="2">Inactive</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 gap-2 justify-content-end">
                <button type="button" class="btn mst-btn-cancel px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn mst-btn-submit px-4" id="edit_submitConclusionForm">Update</button>
            </div>
        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="cmdmColumnVisibilityModal" tabindex="-1" aria-labelledby="cmdmColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="cmdmColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3 mst-colvis-grid" id="cmdmColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-1 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/master-admin.js') }}?v={{ @filemtime(public_path('js/master-admin.js')) ?: time() }}"></script>
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

        /* ---------------- Row markup from the shared partials ---------------- */
        const cmdmTpl = {
            statusOn: $('#cmdmTplStatusOn').html(),
            statusOff: $('#cmdmTplStatusOff').html(),
            actionsOn: $('#cmdmTplActionsOn').html(),
            actionsOff: $('#cmdmTplActionsOff').html()
        };

        function cmdmEsc(value) {
            return String(value === undefined || value === null ? '' : value).replace(/[&<>"']/g, function(ch) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
            });
        }

        function cmdmFill(tpl, values) {
            return tpl.replace(/__CMDM_([A-Z]+(?:_[A-Z]+)*)__/g, function(match, key) {
                return Object.prototype.hasOwnProperty.call(values, key) ? cmdmEsc(values[key]) : '';
            });
        }

        function cmdmIsActive(row) {
            return String(row.active_inactive) === '1';
        }

        function cmdmRenderStatus(data, type, row) {
            if (type !== 'display') {
                return data;
            }
            return cmdmIsActive(row) ? cmdmTpl.statusOn : cmdmTpl.statusOff;
        }

        // The feed's legacy action HTML still carries the edit hooks (with the
        // saved names for archived / disabled values) and the encrypted delete
        // URL; read them from it inertly (DOMParser runs no handlers).
        function cmdmRenderActions(data, type, row) {
            if (type !== 'display') {
                return data;
            }
            const doc = new DOMParser().parseFromString('<div>' + (data || '') + '</div>', 'text/html');
            const edit = doc.querySelector('.editConclusion');
            const form = doc.querySelector('form[action]');
            const attr = function(name, fallback) {
                return edit && edit.hasAttribute(name) ? edit.getAttribute(name) : (fallback === undefined ? '' : fallback);
            };
            const active = cmdmIsActive(row);
            const courseName = attr('data-course-name');
            const memoName = attr('data-memo-name');
            const values = {
                ID: attr('data-id', row.pk),
                COURSE: attr('data-course', row.course_master_pk),
                COURSE_NAME: courseName,
                MEMO: attr('data-memo', row.memo_type_master_pk),
                MEMO_NAME: memoName,
                CONCLUSION: attr('data-conclusion', row.memo_conclusion_master_pk),
                CONCLUSION_NAME: attr('data-conclusion-name'),
                STATUS: attr('data-status', row.active_inactive),
                NAME: [courseName, memoName].filter(Boolean).join(' – '),
                DEL: form ? form.getAttribute('action') : ''
            };
            if (!active && !values.DEL) {
                return data; // unexpected feed shape: keep the server markup
            }
            return cmdmFill(active ? cmdmTpl.actionsOn : cmdmTpl.actionsOff, values);
        }

        // Course is the record's anchor (bold); the memo decision reads as a
        // neutral category tag (not a status colour); the conclusion is plain
        // text that may wrap.
        function cmdmRenderCourse(data, type) {
            if (type !== 'display' || !data || data === '-') {
                return data;
            }
            return '<span class="fw-semibold">' + data + '</span>';
        }

        function cmdmRenderDecision(data, type) {
            if (type !== 'display' || !data || data === '-') {
                return data;
            }
            return '<span class="badge rounded-1 bg-light text-dark border fw-semibold text-wrap text-start lh-sm">' + data + '</span>';
        }

        // ── Active / Archived tab + filter state ──
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
                ajax: {
                    url: "{{ route('course.memo.decision.index') }}",
                    data: function(d) {
                        d.course_filter = $('#cmdmCourseFilter').val();
                        d.memo_conclusion_filter = $('#memoConclusionFilter').val();
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                        className: 'text-nowrap'
                    },
                    {
                        data: 'course_name',
                        name: 'course.course_name',
                        render: cmdmRenderCourse
                    },
                    {
                        data: 'memo_decision',
                        name: 'memo.memo_type_name',
                        render: cmdmRenderDecision
                    },
                    {
                        data: 'memo_conclusion',
                        name: 'memoConclusion.discussion_name',
                        // Wrap the cell only — on the <th> it split "CONCLUSION".
                        createdCell: function(td) {
                            td.classList.add('mst-col-wrap');
                        }
                    },
                    {
                        data: 'status',
                        name: 'status',
                        orderable: false,
                        searchable: false,
                        className: 'text-nowrap',
                        render: cmdmRenderStatus
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        className: 'text-nowrap',
                        width: '13rem',
                        render: cmdmRenderActions
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
                var $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-1 px-3 py-2 mb-0 w-100"></label>')
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

        /* ---------------- Active / Archived tabs + filters ---------------- */
        // A filter or tab change starts again from page 1.
        function cmdmReloadTable() {
            if ($.fn.DataTable.isDataTable(tableSelector)) {
                $(tableSelector).DataTable().ajax.reload();
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

        // Rebuild the Course filter for the selected tab. Select2 re-reads the
        // <option>s live; change.select2 repaints the cleared selection without
        // firing the jQuery change handler below.
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
                        $sel.val('').trigger('change.select2');
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

        // jQuery handlers: Select2 signals a pick with a jQuery `change`.
        $('#cmdmCourseFilter, #memoConclusionFilter').on('change', function() {
            cmdmReloadTable();
        });

        $('#cmdmResetFilters').on('click', function() {
            $('#memoConclusionFilter').val('').trigger('change.select2');
            cmdmCurrentFilter = 'active';
            cmdmSetActiveTab($('#cmdmFilterActive'));
            cmdmLoadCoursesByStatus('active');
        });

        /* ---------------- Add ---------------- */
        function cmdmResetAddForm() {
            document.getElementById('conclusionForm').reset();
            $('#conclusionForm select').trigger('change.select2');
        }

        document.getElementById('showConclusionAlert').addEventListener('click', function() {
            cmdmResetAddForm();
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
                            cmdmResetAddForm();
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

        /* ---------------- Edit ---------------- */
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
            // A row switched off from the grid is stored as 0 — it is Inactive (2) here.
            $('#edit_active_inactive').val(
                String(status) === '1' ? '1' : (status === undefined || status === '' ? '' : '2')
            ).trigger('change');

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
                    } else {
                        // A 422 (empty field) or "Record not found" used to fail silently.
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: res.message || 'Please fill all required fields!'
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
    });
</script>
@endsection
