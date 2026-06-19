@extends('admin.layouts.master')

@section('title', 'Course Master')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
@endpush

@section('setup_content')
<div class="container-fluid programme-index-page">
    <x-breadcrum
        title="Course Master"
        buttonText="Add Course"
        :buttonUrl="route('programme.create')"
        buttonIcon="add"
        buttonClass="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm" />

    <div id="status-msg" class="mb-3"></div>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                <ul class="nav nav-pills gap-2 p-1 rounded-1 programme-status-tabs bg-white" role="group" aria-label="Filter courses by status">
                    <li class="nav-item" role="presentation">
                        <button type="button"
                            class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill active"
                            id="filterActive"
                            aria-pressed="true"
                            aria-current="true">
                            Active
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button type="button"
                            class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill"
                            id="filterArchive"
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
                        <select id="courseFilter" class="form-select js-programme-choice">
                            <option value="">Course Name</option>
                            @foreach($courses ?? [] as $pk => $name)
                            <option value="{{ $pk }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="button" class="btn programme-dt-btn-reset" id="resetFilters">
                        Reset Filters
                    </button>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="btnProgrammeColumns"
                        data-bs-toggle="modal" data-bs-target="#programmeColumnVisibilityModal"
                        title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="programmeDtSearch" class="programme-dt-search"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table']) !!}
                </div>
                <div id="programmeDtFooter" class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"></div>
            </div>

        </div>
    </div>
</div>

<!-- Activate / Deactivate / Delete confirmation -->
<div class="modal fade programme-confirm-modal-root" id="programmeConfirmModal" tabindex="-1"
    aria-labelledby="programmeConfirmTitle" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered programme-confirm-dialog">
        <div class="modal-content programme-confirm-modal border-0 shadow-lg rounded-5 overflow-hidden">
            <div class="modal-body text-center px-4 px-md-5 py-5">
                <div id="programmeConfirmIcon" class="programme-confirm-icon programme-confirm-icon--warning mb-4"
                    role="img" aria-hidden="true">
                    <i id="programmeConfirmIconBi" class="bi bi-exclamation-lg"></i>
                </div>
                <h2 class="programme-confirm-title h4 fw-bold mb-3" id="programmeConfirmTitle">Confirm</h2>
                <p class="programme-confirm-message mb-4 mb-md-5" id="programmeConfirmMessage"></p>
                <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center align-items-stretch programme-confirm-actions">
                    <button type="button" class="btn btn-lg rounded-3 programme-confirm-btn" id="programmeConfirmCancel">
                        Cancel
                    </button>
                    <button type="button" class="btn btn-lg rounded-3 programme-confirm-btn" id="programmeConfirmOk">
                        Confirm
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Course View Modal -->
<div class="modal fade" id="viewCourseModal" tabindex="-1" aria-labelledby="viewCourseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header text-white border-0 py-3 px-4">
                <h5 class="modal-title fw-semibold d-flex align-items-center gap-2 mb-0" id="viewCourseModalLabel">
                    <i class="bi bi-journal-bookmark-fill" aria-hidden="true"></i>
                    Course Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div id="courseDetailsContent">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-3 mb-0 text-body-secondary">Loading course details…</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top bg-body-tertiary px-4 py-3">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4 fw-semibold" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="programmeColumnVisibilityModal" tabindex="-1" aria-labelledby="programmeColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="programmeColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="programmeColumnToggleGrid"></div>
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
    $(document).ready(function() {
        var table;
        var currentFilter = 'active'; // Set Active as default
        var courseChoices = null;

        /* ── Confirmation modals (Course Master only) ── */
        var programmeConfirmModalEl = document.getElementById('programmeConfirmModal');
        var programmeConfirmModal = programmeConfirmModalEl ? bootstrap.Modal.getOrCreateInstance(programmeConfirmModalEl) : null;
        var programmeConfirmOnOk = null;
        var programmeConfirmOnCancel = null;

        var programmeConfirmConfigs = {
            activate: {
                iconWrap: 'programme-confirm-icon--success',
                icon: 'bi-check-lg',
                title: 'Activate Course?',
                message: 'Are you sure you want to activate this course?',
                messageClass: 'programme-confirm-message--success',
                cancelLines: ['Cancel,', 'Keep it deactive'],
                confirmLines: ['Yes,', 'Activate'],
                cancelClass: 'programme-confirm-cancel--primary',
                confirmClass: 'programme-confirm-ok--primary'
            },
            deactivate: {
                iconWrap: 'programme-confirm-icon--warning',
                icon: 'bi-exclamation-lg',
                title: 'Deactivate Course?',
                message: 'Are you sure you want to deactivate this course?',
                messageClass: 'programme-confirm-message--info',
                cancelLines: ['Cancel,', 'Keep it active'],
                confirmLines: ['Yes,', 'Deactivate'],
                cancelClass: 'programme-confirm-cancel--primary',
                confirmClass: 'programme-confirm-ok--primary'
            },
            delete: {
                iconWrap: 'programme-confirm-icon--danger',
                icon: 'bi-exclamation-lg',
                title: 'Delete Course?',
                message: 'Are you sure you want to delete this course?',
                messageClass: 'programme-confirm-message--danger',
                cancelLines: ['Cancel,', 'Keep it'],
                confirmLines: ['Yes,', 'Delete'],
                cancelClass: 'programme-confirm-cancel--danger',
                confirmClass: 'programme-confirm-ok--danger'
            }
        };

        var programmeConfirmBtnClasses = [
            'programme-confirm-cancel--primary',
            'programme-confirm-cancel--danger',
            'programme-confirm-ok--primary',
            'programme-confirm-ok--danger',
            'btn-primary',
            'btn-danger',
            'btn-outline-primary',
            'btn-outline-danger'
        ];

        function setProgrammeConfirmButtonLines($btn, lines) {
            $btn.empty();
            (lines || []).forEach(function(line) {
                $('<span>', { 'class': 'programme-confirm-btn-line', text: line }).appendTo($btn);
            });
        }

        function showProgrammeConfirm(type, onConfirm, onCancel) {
            if (!programmeConfirmModal) {
                if (onConfirm) {
                    onConfirm();
                }
                return;
            }

            var cfg = programmeConfirmConfigs[type];
            if (!cfg) {
                return;
            }

            var $icon = $('#programmeConfirmIcon');
            $icon.removeClass('programme-confirm-icon--success programme-confirm-icon--warning programme-confirm-icon--danger')
                .addClass(cfg.iconWrap);
            $('#programmeConfirmIconBi').attr('class', 'bi ' + cfg.icon);
            $('#programmeConfirmTitle').text(cfg.title);

            var $message = $('#programmeConfirmMessage');
            $message.removeClass('programme-confirm-message--info programme-confirm-message--success programme-confirm-message--danger')
                .addClass(cfg.messageClass || 'programme-confirm-message--info')
                .text(cfg.message);

            var $cancel = $('#programmeConfirmCancel');
            var $ok = $('#programmeConfirmOk');
            $cancel.removeClass(programmeConfirmBtnClasses.join(' '))
                .addClass('btn programme-confirm-btn ' + cfg.cancelClass);
            $ok.removeClass(programmeConfirmBtnClasses.join(' '))
                .addClass('btn programme-confirm-btn ' + cfg.confirmClass);
            setProgrammeConfirmButtonLines($cancel, cfg.cancelLines);
            setProgrammeConfirmButtonLines($ok, cfg.confirmLines);

            programmeConfirmOnOk = onConfirm || null;
            programmeConfirmOnCancel = onCancel || null;
            programmeConfirmModal.show();
        }

        $('#programmeConfirmOk').on('click', function() {
            var onOk = programmeConfirmOnOk;
            programmeConfirmOnOk = null;
            programmeConfirmOnCancel = null;
            programmeConfirmModal.hide();
            if (typeof onOk === 'function') {
                onOk();
            }
        });

        $('#programmeConfirmCancel').on('click', function() {
            var onCancel = programmeConfirmOnCancel;
            programmeConfirmOnOk = null;
            programmeConfirmOnCancel = null;
            programmeConfirmModal.hide();
            if (typeof onCancel === 'function') {
                onCancel();
            }
        });

        if (programmeConfirmModalEl) {
            programmeConfirmModalEl.addEventListener('hidden.bs.modal', function() {
                if (typeof programmeConfirmOnCancel === 'function') {
                    programmeConfirmOnCancel();
                }
                programmeConfirmOnOk = null;
                programmeConfirmOnCancel = null;
            });
        }

        function programmeUpdateStatus($checkbox) {
            var status = $checkbox.is(':checked') ? 1 : 0;

            $.ajax({
                url: typeof routes !== 'undefined' ? routes.toggleStatus : '',
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    table: $checkbox.data('table'),
                    column: $checkbox.data('column'),
                    id: $checkbox.data('id'),
                    id_column: $checkbox.data('id_column'),
                    status: status
                },
                success: function(response) {
                    $('#status-msg').html(
                        '<div class="alert alert-success alert-dismissible fade show" role="alert">' +
                        (response.message || 'Status updated successfully') +
                        '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>'
                    );
                    setTimeout(function() {
                        if ($.fn.DataTable.isDataTable('#coursemaster-table')) {
                            $('#coursemaster-table').DataTable().ajax.reload(null, false);
                        }
                    }, 500);
                },
                error: function() {
                    if (typeof Swal !== 'undefined' && Swal.fire) {
                        Swal.fire('Error', 'Status update failed', 'error');
                    }
                    $checkbox.prop('checked', !status);
                }
            });
        }

        document.addEventListener('change', function(e) {
            if (!e.target.matches('#coursemaster-table .status-toggle')) {
                return;
            }
            e.stopPropagation();
            e.stopImmediatePropagation();

            var checkbox = e.target;
            var $checkbox = $(checkbox);
            var status = checkbox.checked ? 1 : 0;
            var type = status === 1 ? 'activate' : 'deactivate';

            showProgrammeConfirm(type, function() {
                programmeUpdateStatus($checkbox);
            }, function() {
                $checkbox.prop('checked', !status);
            });
        }, true);

        $(document).on('submit', '#coursemaster-table .programme-delete-form', function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            var form = this;

            showProgrammeConfirm('delete', function() {
                HTMLFormElement.prototype.submit.call(form);
            });
        });

        var programmeChoiceOpts = {
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

        function initCourseFilterChoices() {
            if (typeof Choices === 'undefined') {
                return;
            }

            var courseFilterEl = document.getElementById('courseFilter');
            if (!courseFilterEl || courseFilterEl.dataset.choicesInitialized === 'true') {
                return;
            }

            courseChoices = new Choices(courseFilterEl, programmeChoiceOpts);
            courseFilterEl._choicesInstance = courseChoices;
            courseFilterEl.dataset.choicesInitialized = 'true';
        }

        function rebuildCourseFilterChoices() {
            if (typeof Choices === 'undefined') {
                return;
            }

            var courseFilterEl = document.getElementById('courseFilter');
            if (!courseFilterEl || !courseFilterEl._choicesInstance) {
                initCourseFilterChoices();
                return;
            }

            courseFilterEl._choicesInstance.destroy();
            courseFilterEl.dataset.choicesInitialized = 'false';
            courseFilterEl._choicesInstance = null;
            courseChoices = null;
            initCourseFilterChoices();
        }

        function enhanceProgrammeDtControls() {
            var $wrapper = $('#coursemaster-table_wrapper');
            if (!$wrapper.length) {
                return;
            }

            var $searchSlot = $('#programmeDtSearch');
            var $footer = $('#programmeDtFooter');

            /* ── Search → toolbar right ── */
            if (!$searchSlot.find('.dataTables_filter').length) {
                var $filter = $wrapper.find('.dataTables_filter').first();
                if ($filter.length) {
                    $filter.find('input')
                        .addClass('form-control shadow-none')
                        .attr('placeholder', 'Search')
                        .attr('aria-label', 'Search courses');
                    $filter.find('label').contents().filter(function() {
                        return this.nodeType === 3;
                    }).remove();
                    $searchSlot.append($filter);
                }
            }

            /* ── Footer: pagination + count (once) ── */
            if ($footer.data('dtReady')) {
                updateProgrammeDtCount();
                return;
            }

            var $paginate = $wrapper.find('.dataTables_paginate').first();
            var $length = $wrapper.find('.dataTables_length').first();
            var $info = $wrapper.find('.dataTables_info').first();

            if (!$footer.length) {
                return;
            }

            var $pagCol = $('<div class="programme-dt-pagination"></div>');
            var $countCol = $('<div class="programme-dt-count d-flex flex-wrap align-items-center gap-2 ms-lg-auto"></div>');

            if ($paginate.length) {
                $paginate.find('.pagination').addClass('mb-0');
                $pagCol.append($paginate);
            }

            if ($length.length) {
                var $select = $length.find('select').addClass('form-select form-select-sm');
                $length.find('label')
                    .empty()
                    .append(document.createTextNode('Showing '))
                    .append($select)
                    .append(document.createTextNode(' '));
                $countCol.append($length);
            }

            if ($info.length) {
                $info.addClass('mb-0');
                $countCol.append($info);
            }

            $footer.append($pagCol).append($countCol);
            $footer.data('dtReady', true);
        }

        function updateProgrammeDtCount() {
            if (!table) {
                return;
            }
            var info = table.page.info();
            var $info = $('#programmeDtFooter .dataTables_info');
            if ($info.length && info.recordsDisplay !== undefined) {
                $info.text('of ' + info.recordsDisplay.toLocaleString() + ' items');
            }
        }

        // Wait for Yajra DataTable init (avoid re-init / duplicate header)
        setTimeout(function() {
            if (!$.fn.DataTable.isDataTable('#coursemaster-table')) {
                return;
            }
            table = $('#coursemaster-table').DataTable();
            initCourseFilterChoices();
            enhanceProgrammeDtControls();
            updateProgrammeDtCount();
            setupProgrammeColumns(table);

            // Initialize dropdowns after table loads
            initializeDropdowns();

            // Set initial active state - Active button is already styled as active in HTML
            // No need to change styling initially

            // Function to load courses by status
            function loadCoursesByStatus(status) {
                $.ajax({
                    url: '{{ route("programme.get.courses.by.status") }}',
                    type: 'GET',
                    data: {
                        status: status
                    },
                    success: function(response) {
                        if (response.success) {
                            var courseFilter = $('#courseFilter');
                            var currentValue = courseFilter.val();

                            // Clear existing options except "All Courses"
                            courseFilter.find('option:not(:first)').remove();

                            // Add new course options
                            $.each(response.courses, function(pk, name) {
                                courseFilter.append($('<option>', {
                                    value: pk,
                                    text: name
                                }));
                            });

                            // Reset to "All Courses" when status changes
                            courseFilter.val('');
                            rebuildCourseFilterChoices();

                            // Reload table
                            table.ajax.reload();
                            initializeDropdowns();
                        }
                    },
                    error: function(xhr) {
                        console.error('Error loading courses:', xhr);
                    }
                });
            }

            // Filter button click handlers
            $('#filterActive').on('click', function() {
                setActiveButton($(this));
                currentFilter = 'active';
                loadCoursesByStatus('active');
            });

            $('#filterArchive').on('click', function() {
                setActiveButton($(this));
                currentFilter = 'archive';
                loadCoursesByStatus('archive');
            });

            // Function to initialize dropdowns
            function initializeDropdowns() {
                var dropdownElementList = document.querySelectorAll('[data-bs-toggle="dropdown"]');
                dropdownElementList.forEach(function(dropdownToggleEl) {
                    // Dispose of existing dropdown instance if any
                    try {
                        var existingDropdown = bootstrap.Dropdown.getInstance(dropdownToggleEl);
                        if (existingDropdown) {
                            existingDropdown.dispose();
                        }
                    } catch (e) {
                        // Instance doesn't exist, continue
                    }

                    // Create new dropdown instance
                    try {
                        new bootstrap.Dropdown(dropdownToggleEl);
                    } catch (e) {
                        console.error('Error initializing dropdown:', e);
                    }
                });
            }

            // Function to set active button styling
            function setActiveButton(activeBtn) {
                $('#filterActive, #filterArchive')
                    .removeClass('active')
                    .attr('aria-pressed', 'false')
                    .removeAttr('aria-current');

                activeBtn
                    .addClass('active')
                    .attr('aria-pressed', 'true')
                    .attr('aria-current', 'true');
            }

            // Pass filter parameter to server
            $('#coursemaster-table').on('preXhr.dt', function(e, settings, data) {
                data.status_filter = currentFilter;
                var courseFilter = $('#courseFilter').val();
                if (courseFilter) {
                    data.course_filter = courseFilter;
                }
            });

            var $wrapper = $('#coursemaster-table_wrapper');

            // Reinitialize dropdowns after table draw
            $('#coursemaster-table').on('draw.dt', function() {
                initializeDropdowns();
                if ($wrapper.find('.dataTables_paginate').length && !$('#programmeDtFooter .dataTables_paginate').length) {
                    $('#programmeDtFooter').empty().data('dtReady', false);
                    enhanceProgrammeDtControls();
                }
                updateProgrammeDtCount();
            });

            setTimeout(function() {
                enhanceProgrammeDtControls();
                updateProgrammeDtCount();
            }, 300);

            // Handle dropdown toggle with event delegation
            $(document).on('click', '[data-bs-toggle="dropdown"]', function(e) {
                // Bootstrap will handle the toggle, just ensure it's initialized
                var el = this;
                if (!bootstrap.Dropdown.getInstance(el)) {
                    new bootstrap.Dropdown(el);
                }
            });

            // Handle course filter change
            $('#courseFilter').on('change', function() {
                table.ajax.reload();
                initializeDropdowns();
            });

            // Handle reset filters
            $('#resetFilters').on('click', function() {
                $('#courseFilter').val('');
                rebuildCourseFilterChoices();
                currentFilter = 'active'; // Reset to active by default
                setActiveButton($('#filterActive'));
                loadCoursesByStatus('active');
            });

            // Handle view course button click
            $(document).on('click', '.view-course-btn', function() {
                var courseId = $(this).data('id');
                console.log('Course ID:', courseId); // Debug log
                loadCourseDetails(courseId);
            });
        }, 100);

        /* ---------------- Column show / hide (DataTables API) ---------------- */
        var programmeColStorageKey = 'programmeGrid:hiddenColumns:v1';

        function programmeGetHiddenCols() {
            try {
                var raw = localStorage.getItem(programmeColStorageKey);
                var arr = raw ? JSON.parse(raw) : [];
                return Array.isArray(arr) ? arr : [];
            } catch (e) {
                return [];
            }
        }

        function programmePersistHiddenCols(arr) {
            try { localStorage.setItem(programmeColStorageKey, JSON.stringify(arr)); } catch (e) {}
        }

        function setupProgrammeColumns(dt) {
            if (!dt) {
                return;
            }
            var hidden = programmeGetHiddenCols();

            // Apply saved visibility — DataTables keeps this across redraws / ajax reloads.
            dt.columns().every(function() {
                var idx = this.index();
                this.visible(hidden.indexOf(idx) === -1, false);
            });
            dt.columns.adjust();

            // Build the modal checkboxes once from the live table headers.
            var $grid = $('#programmeColumnToggleGrid');
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

                var inputId = 'programmecolvis_' + idx;
                var $cell = $('<div class="col-12 col-sm-6 col-md-4"></div>');
                var $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                    .attr('for', inputId);
                var $cb = $('<input type="checkbox" class="form-check-input m-0">')
                    .attr('id', inputId)
                    .prop('checked', hidden.indexOf(idx) === -1);

                $cb.on('change', function() {
                    var h = programmeGetHiddenCols();
                    var pos = h.indexOf(idx);
                    if (this.checked) {
                        if (pos !== -1) h.splice(pos, 1);
                    } else {
                        if (pos === -1) h.push(idx);
                    }
                    programmePersistHiddenCols(h);
                    dt.column(idx).visible(this.checked, false);
                    dt.columns.adjust();
                });

                $label.append($cb).append($('<span></span>').text(title));
                $cell.append($label);
                $grid.append($cell);
            });
        }

        // Function to load course details
        function loadCourseDetails(courseId) {
            var url = '{{ route("programme.view", ":id") }}'.replace(':id', courseId);
            console.log('Request URL:', url); // Debug log

            $.ajax({
                url: url,
                type: 'GET',
                beforeSend: function() {
                    $('#courseDetailsContent').html(`
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Loading course details...</p>
                        </div>
                    `);
                },
                success: function(response) {
                    if (response.success) {
                        var course = response.course;
                        var content = `
                            <div class="row">
                                <div class="col-12">
                                    <h4 class="text-primary mb-4">${course.course_name}</h4>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Course Short Name:</strong>
                                    <p class="text-muted">${course.course_short_name || 'Not Available'}</p>
                                </div>
                                <div class="col-md-6">
                                    <strong>Course Year:</strong>
                                    <p class="text-muted">${course.course_year || 'Not Available'}</p>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Start Date:</strong>
                                    <p class="text-muted">${course.start_date}</p>
                                </div>
                                <div class="col-md-6">
                                    <strong>End Date:</strong>
                                    <p class="text-muted">${course.end_date}</p>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Course Coordinator:</strong>
                                    <div class="d-flex align-items-center mt-2">
                                        ${course.coordinator_photo ? 
                                            `<img src="${course.coordinator_photo}" alt="Coordinator Photo" class="rounded-circle me-2" style="width: 50px; height: 50px; object-fit: cover;">` : 
                                            `<div class="bg-secondary rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                <i class="bi bi-person text-white"></i>
                                            </div>`
                                        }
                                        <span>${course.coordinator_name}</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-12">
                                    <strong>Assistant Coordinators:</strong>
                                    <div class="mt-2">
                        `;

                        if (course.assistant_coordinators && course.assistant_coordinators.length > 0) {
                            course.assistant_coordinators.forEach(function(coordinator, index) {
                                var photo = course.assistant_coordinator_photos[index] || null;
                                content += `
                                    <div class="d-flex align-items-center mb-2">
                                        ${photo ? 
                                            `<img src="${photo}" alt="Assistant Coordinator Photo" class="rounded-circle me-2" style="width: 40px; height: 40px; object-fit: cover;">` : 
                                            `<div class="bg-secondary rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                <i class="bi bi-person text-white"></i>
                                            </div>`
                                        }
                                        <span>${coordinator}</span>
                                    </div>
                                `;
                            });
                        } else {
                            content += '<p class="text-muted">No Assistant Coordinators assigned</p>';
                        }

                        content += `
                                    </div>
                                </div>
                            </div>
                        `;

                        $('#courseDetailsContent').html(content);
                    } else {
                        $('#courseDetailsContent').html(`
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle"></i>
                                ${response.message || 'Failed to load course details'}
                            </div>
                        `);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', {
                        status: xhr.status,
                        statusText: xhr.statusText,
                        responseText: xhr.responseText,
                        error: error
                    });

                    var errorMessage = 'Error loading course details. Please try again.';
                    if (xhr.status === 404) {
                        errorMessage = 'Course not found.';
                    } else if (xhr.status === 400) {
                        errorMessage = 'Invalid course ID.';
                    } else if (xhr.status === 500) {
                        errorMessage = 'Server error. Please try again later.';
                    }

                    $('#courseDetailsContent').html(`
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle"></i>
                            ${errorMessage}
                        </div>
                    `);
                }
            });
        }
    });
</script>
@endpush