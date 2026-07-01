@extends('admin.layouts.master')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />

@section('title', 'Discipline Memo - Sargam | Lal Bahadur Shastri National Academy of Administration')

@section('setup_content')
<link rel="stylesheet" href="{{ asset('css/notice-memo-discipline.css') }}?v={{ @filemtime(public_path('css/notice-memo-discipline.css')) ?: time() }}">
<div class="container-fluid disc-page">
    <x-breadcrum title="Send Discipline Memo">
        @if(hasRole('Internal Faculty') || hasRole('Guest Faculty') || hasRole('Super Admin') ||
        hasRole('Training Induction Admin') || hasRole('Training-Induction'))
        <button type="button" data-bs-toggle="modal" data-bs-target="#genMemoModal"
            class="btn btn-primary d-inline-flex align-items-center gap-1 px-3 shadow-sm">
            <i class="material-icons material-symbols-rounded" style="font-size:20px;">add</i>
            Generate Discipline Memo
        </button>
        @endif
    </x-breadcrum>
    <x-session_message />

    {{-- Generate Discipline Memo modal --}}
    <div class="modal fade gen-memo-modal" id="genMemoModal" tabindex="-1" aria-labelledby="genMemoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="genMemoModalLabel">Generate Discipline Memo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('memo.discipline.discipline_generate_memo_store') }}" method="POST" id="genMemoForm">
                    @csrf
                    <input type="hidden" name="submission_type" value="1">
                    <div class="modal-body">
                        <div class="gm-note">
                            <i class="bi bi-info-circle"></i>
                            As you submit this form the Notice will be automatically sent to the concerned person.
                        </div>

                        <div class="row g-3 mb-2">
                            <div class="col-md-6">
                                <label class="form-label">Course Name <span class="text-danger">*</span></label>
                                <select class="form-select" name="course_master_pk" id="gmCourse" required>
                                    <option value="">Select Course Name</option>
                                    @foreach($courses as $course)
                                        <option value="{{ $course->pk }}">{{ $course->course_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Start Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="date_of_memo" id="gmDate" max="{{ date('Y-m-d') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Session</label>
                                <select class="form-select" name="session_name" id="gmSession">
                                    <option value="">Select Session</option>
                                    @foreach($sessions as $s)
                                        <option value="{{ $s->pk }}">{{ $s->shift_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Venue</label>
                                <select class="form-select" name="venue_name" id="gmVenue">
                                    <option value="">Select Venue</option>
                                    @foreach($venues as $v)
                                        <option value="{{ $v->pk }}">{{ $v->venue_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Discipline <span class="text-danger">*</span></label>
                                <select class="form-select" name="discipline_master_pk" id="gmDiscipline" required>
                                    <option value="">Select Discipline</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Discipline Marks <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0" class="form-control" name="discipline_marks" id="gmMarks" placeholder="eg. 24.50" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Select Students <span class="text-danger">*</span></label>
                                <button type="button" class="gm-picker-trigger" id="gmSelectStudents">
                                    <span id="gmSelectedSummary" class="text-muted">Select Students</span>
                                    <i class="bi bi-chevron-down"></i>
                                </button>
                                <div id="gmSelectedHidden"></div>
                            </div>
                        </div>

                        <h6 class="gm-section-title mt-3">Notice Preview</h6>
                        <div class="gm-preview" id="gmPreview">
                            <h5 class="text-center fw-bold mb-1" id="gmPvCourse">Course Name</h5>
                            <p class="text-center mb-0 small">Lal Bahadur Shastri National Academy of Administration, Mussoorie</p>
                            <hr>
                            <p class="mb-1 fw-semibold">SHOW CAUSE NOTICE</p>
                            <p class="mb-2"><strong>Date:</strong> <span id="gmPvDate">—</span></p>
                            <p class="mb-2 small">It is brought to the notice of the undersigned that you were found in breach of discipline
                                (<span id="gmPvDiscipline">—</span>) with a mark deduction of <strong id="gmPvMarks">—</strong>.</p>
                            <div class="table-responsive">
                                <table class="table table-sm mb-2">
                                    <thead><tr><th>#</th><th>OT</th></tr></thead>
                                    <tbody id="gmPvStudents"><tr><td colspan="2" class="text-muted">No students selected.</td></tr></tbody>
                                </table>
                            </div>
                            <p class="text-end mb-0 small"><strong>Director</strong><br>LBSNAA, Mussoorie</p>
                        </div>

                        <div class="col-12">
                            <label class="form-label mt-3">Message (If Any)</label>
                            <textarea class="form-control" name="Remark" id="gmRemark" rows="2" placeholder="Enter remarks..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4">Send Discipline Memo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Student List picker (dual-listbox) — opens from "Select Students" --}}
    <div class="modal fade student-picker-modal" id="studentPickerModal" tabindex="-1" aria-labelledby="studentPickerLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="studentPickerLabel">Student List</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="an-dual">
                        <div class="an-panel">
                            <div class="an-panel-title">Defaulter Students</div>
                            <div class="an-search"><i class="bi bi-search"></i><input type="text" class="an-filter" data-target="spAvailable" placeholder="Search"></div>
                            <label class="an-selectall"><input type="checkbox" class="form-check-input an-select-all" data-panel="spAvailable"> Select All</label>
                            <div class="an-list" id="spAvailable"></div>
                        </div>
                        <div class="an-moves">
                            <button type="button" class="an-move-btn" data-move="all-right">Move all right</button>
                            <button type="button" class="an-move-btn" data-move="right">Move right</button>
                            <button type="button" class="an-move-btn" data-move="left">Move left</button>
                            <button type="button" class="an-move-btn" data-move="all-left">Move all left</button>
                        </div>
                        <div class="an-panel">
                            <div class="an-panel-title">Selected Students</div>
                            <div class="an-search"><i class="bi bi-search"></i><input type="text" class="an-filter" data-target="spSelected" placeholder="Search"></div>
                            <label class="an-selectall"><input type="checkbox" class="form-check-input an-select-all" data-panel="spSelected"> Select All</label>
                            <div class="an-list" id="spSelected"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary px-4" id="spSaveBtn">Save</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Column Visibility modal --}}
    <div class="modal fade sn-colvis-modal" id="discColumnModal" tabindex="-1" aria-labelledby="discColumnModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="discColumnModalLabel">Column Visibility</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="sn-colvis-grid" id="discColumnGrid"></div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn-close-colvis" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabs + Download --}}
    <div class="card-body py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div class="disc-tabs">
            <a href="{{ route('send.notice.management.index') }}" class="disc-tab js-nav-tab">Send Direct Notice</a>
            <a href="{{ route('memo.notice.management.index') }}" class="disc-tab js-nav-tab">Send Memo / Notice</a>
            <a href="{{ route('memo.discipline.index') }}" class="disc-tab js-nav-tab active">Send Discipline Memo</a>
        </div>
        <a href="{{ route('memo.discipline.export_csv', request()->query()) }}" class="disc-download">
            <i class="bi bi-download"></i> Download
        </a>
    </div>

    <!-- start Zero Configuration -->
    <div class="card shadow-sm border-0 overflow-hidden">
        <div class="card-body">
            @php
            $today = \Carbon\Carbon::today()->toDateString();
            $isToday = $fromDateFilter === $today && $toDateFilter === $today;
            $hasRange = ($fromDateFilter || $toDateFilter) && !$isToday;
            @endphp
            <form method="GET" action="{{ route('memo.discipline.index') }}" id="filterForm">
                <div class="disc-filter-bar mb-3">
                    <span class="disc-filter-label">Filters</span>

                    <select class="form-select" id="program_name" name="program_name" aria-label="Program Name">
                        <option value="">Program Name</option>
                        @foreach($courses as $course)
                        <option value="{{ $course->pk }}"
                            {{ (string)$programNameFilter == (string)$course->pk ? 'selected' : '' }}>
                            {{ $course->course_name }}</option>
                        @endforeach
                    </select>

                    <select class="form-select" id="status" name="status" aria-label="Status">
                        <option value="">Status</option>
                        <option value="1" {{ $statusFilter == '1' ? 'selected' : '' }}>Pending</option>
                        <option value="2" {{ $statusFilter == '2' ? 'selected' : '' }}>Recorded</option>
                        <option value="3" {{ $statusFilter == '3' ? 'selected' : '' }}>Closed</option>
                    </select>

                    <select class="form-select" id="discTimePeriod" aria-label="Time Period">
                        <option value="today" {{ $isToday ? 'selected' : '' }}>Today</option>
                        <option value="week">This Week</option>
                        <option value="month">This Month</option>
                        <option value="all">All</option>
                        <option value="custom" {{ $hasRange ? 'selected' : '' }}>Custom Range</option>
                    </select>
                    <input type="date" class="form-control {{ $hasRange ? '' : 'd-none' }}" id="from_date"
                        name="from_date" value="{{ $fromDateFilter }}" max="{{ $today }}" style="max-width:160px;">
                    <input type="date" class="form-control {{ $hasRange ? '' : 'd-none' }}" id="to_date" name="to_date"
                        value="{{ $toDateFilter }}" max="{{ $today }}" style="max-width:160px;">

                    <a href="{{ route('memo.discipline.index') }}" class="disc-reset">Reset Filters</a>

                    <div class="ms-auto d-flex align-items-center gap-2">
                        <button type="button" class="disc-icon-btn" data-bs-toggle="modal" data-bs-target="#discColumnModal">
                            <i class="bi bi-layout-three-columns"></i> Columns
                        </button>
                        <button type="button" class="disc-icon-btn" id="discSearchToggle" aria-label="Search"><i
                                class="bi bi-search"></i></button>
                        <input type="text" class="disc-search-input {{ $searchFilter ? '' : 'd-none' }}" id="search"
                            name="search" placeholder="Search..." value="{{ $searchFilter }}">
                    </div>
                </div>
            </form>

            <!-- Add this JavaScript for enhanced UX -->
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Update active filter count badge
                function updateFilterCount() {
                    const form = document.getElementById('filterForm');
                    if (!form) return;
                    const inputs = form.querySelectorAll('select, input[type="text"], input[type="date"]');
                    let activeCount = 0;
                    inputs.forEach(function(input) {
                        if ((input.tagName === 'SELECT' && input.value !== '') ||
                            (input.type === 'text' && input.value.trim() !== '') ||
                            (input.type === 'date' && input.value !== '')) {
                            activeCount++;
                        }
                    });
                    const badge = document.getElementById('activeFilterCount');
                    if (badge) badge.textContent = activeCount;
                }

                // Apply filters via AJAX
                function applyFiltersAjax() {
                    const form = document.getElementById('filterForm');
                    const listContainer = document.getElementById('memoDisciplineListContainer');
                    if (!form || !listContainer) return;
                    const formData = new FormData(form);
                    const params = new URLSearchParams(formData).toString();
                    const url = "{{ route('memo.discipline.index') }}" + (params ? '?' + params : '');
                    listContainer.style.opacity = '0.5';
                    fetch(url, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(function(r) {
                            return r.text();
                        })
                        .then(function(html) {
                            const parser = new DOMParser();
                            const doc = parser.parseFromString(html, 'text/html');
                            const newSummary = doc.querySelector('#filterSummary');
                            const currentSummary = document.getElementById('filterSummary');
                            if (newSummary) {
                                if (currentSummary) currentSummary.replaceWith(newSummary.cloneNode(true));
                                else form.querySelector('.row').appendChild(newSummary.cloneNode(true));
                            } else {
                                if (currentSummary) currentSummary.remove();
                            }
                            const newList = doc.querySelector('#memoDisciplineListContainer');
                            if (newList) listContainer.innerHTML = newList.innerHTML;
                            window.history.replaceState({}, '', url);
                            updateFilterCount();
                        })
                        .catch(function() {
                            alert('Failed to apply filters');
                        })
                        .finally(function() {
                            listContainer.style.opacity = '1';
                        });
                }
                window.applyFiltersAjax = applyFiltersAjax;

                // Submit form via AJAX instead of full page reload
                const filterForm = document.getElementById('filterForm');
                if (filterForm) {
                    filterForm.addEventListener('submit', function(e) {
                        e.preventDefault();
                        applyFiltersAjax();
                    });
                }

                // Initialize filter count
                updateFilterCount();

                // Update count on input change
                document.querySelectorAll('#filterForm select, #filterForm input').forEach(function(input) {
                    input.addEventListener('change', updateFilterCount);
                    input.addEventListener('input', updateFilterCount);
                });

                // Clear Filters button
                const clearFiltersBtn = document.getElementById('clearFiltersBtn');
                if (clearFiltersBtn && filterForm) {
                    clearFiltersBtn.addEventListener('click', function() {
                        filterForm.querySelectorAll('select').forEach(function(s) {
                            s.value = '';
                        });
                        filterForm.querySelectorAll('input[type="text"]').forEach(function(i) {
                            i.value = '';
                        });
                        filterForm.querySelectorAll('input[type="date"]').forEach(function(i) {
                            i.value = '';
                        });
                        applyFiltersAjax();
                    });
                }

                // Toggle Active Filters alert visibility
                document.addEventListener('click', function(e) {
                    const summary = document.getElementById('filterSummary');
                    if (!summary) return;
                    const alertEl = summary.querySelector('.filter-summary-alert');
                    const linkEl = summary.querySelector('.show-filter-details-link');
                    if (!alertEl || !linkEl) return;
                    if (e.target.closest('.filter-summary-close')) {
                        e.preventDefault();
                        alertEl.classList.add('d-none');
                        linkEl.classList.remove('d-none');
                    } else if (e.target.closest('.show-filter-details-link')) {
                        e.preventDefault();
                        alertEl.classList.remove('d-none');
                        linkEl.classList.add('d-none');
                    }
                });
            });

            // Remove specific filter and resubmit
            function removeFilter(filterName) {
                const input = document.querySelector('[name="' + filterName + '"]');
                if (input) input.value = '';
                if (typeof window.applyFiltersAjax === 'function') {
                    window.applyFiltersAjax();
                } else {
                    document.getElementById('filterForm').submit();
                }
            }

            // Remove date filters and resubmit
            function removeDateFilters() {
                document.getElementById('from_date').value = '';
                document.getElementById('to_date').value = '';
                if (typeof window.applyFiltersAjax === 'function') {
                    window.applyFiltersAjax();
                } else {
                    document.getElementById('filterForm').submit();
                }
            }
            </script>

            <hr class="my-3">
            <div id="memoDisciplineListContainer">
                <div class="table-responsive">
                    <table id="discTable" class="table align-middle mb-0 text-nowrap">
                        <thead>
                            <tr>
                                <th>S. No.</th>
                                <th>Program Name</th>
                                <th>Participant Name</th>
                                <th>Date</th>
                                <th>Discipline</th>
                                <th class="text-center">Submitted</th>
                                <th class="text-center">Final</th>
                                <th>Status</th>
                                @if(! hasRole('Officer Trainee'))
                                <th class="text-end">Action</th>
                                @endif
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($memos as $index => $memo)
                            <tr>
                                <td class="fw-semibold text-muted">{{ $memos->firstItem() + $index }}</td>
                                <td class="fw-semibold">{{ $memo->course->course_name ?? 'N/A' }}</td>
                                <td class="fw-semibold">
                                    {{ trim(($memo->student->generated_OT_code ? $memo->student->generated_OT_code . '- ' : '') . ($memo->student->display_name ?? 'N/A')) }}
                                </td>
                                <td class="text-muted">
                                    {{ $memo->date ? \Carbon\Carbon::parse($memo->date)->format('d M Y') : 'N/A' }}</td>
                                <td><span
                                        class="badge bg-info-subtle text-info">{{ $memo->discipline->discipline_name ?? 'N/A' }}</span>
                                </td>
                                <td class="text-center fw-semibold text-warning">{{ $memo->mark_deduction_submit }}</td>
                                <td class="text-center fw-semibold text-danger">{{ $memo->final_mark_deduction }}</td>

                                <!-- Status -->
                                <td>
                                    @if ($memo->status == 1)
                                    <span class="badge bg-success-subtle text-success">
                                        <i class="bi bi-check-circle me-1"></i> Recorded
                                    </span>
                                    @elseif ($memo->status == 2)
                                    <span class="badge bg-warning-subtle text-warning">
                                        <i class="bi bi-envelope me-1"></i> Memo Sent
                                    </span>
                                    <div class="mt-1 d-flex gap-2">
                                        <a href="{{ route('memo.discipline.memo.show', encrypt($memo->pk)) }}"
                                            class="link-primary small fw-medium">
                                            View Memo
                                        </a>
                                        <a class="text-success view-conversation" data-bs-toggle="offcanvas"
                                            data-bs-target="#chatOffcanvas" data-id="{{ $memo->pk }}"
                                            data-type="{{ (hasRole('Internal Faculty') || hasRole('Guest Faculty') || hasRole('Super Admin') || hasRole('Training Induction Admin')) ? 'admin' : 'OT' }}">
                                            <i class="material-icons material-symbols-rounded fs-5">chat</i>
                                        </a>
                                    </div>
                                    @else
                                    <span class="badge bg-secondary-subtle text-secondary">
                                        <i class="bi bi-lock me-1"></i> Closed
                                    </span>
                                    <div class="mt-1 d-flex gap-2">
                                        <a href="{{ route('memo.discipline.memo.show', encrypt($memo->pk)) }}"
                                            class="link-primary small fw-medium">
                                            View Memo
                                        </a>
                                        <a class="text-success view-conversation" data-bs-toggle="offcanvas"
                                            data-bs-target="#chatOffcanvas" data-id="{{ $memo->pk }}"
                                            data-type="{{ (hasRole('Internal Faculty') || hasRole('Guest Faculty') || hasRole('Super Admin') || hasRole('Training Induction Admin')) ? 'admin' : 'OT' }}">
                                            <i class="material-icons material-symbols-rounded fs-5">chat</i>
                                        </a>
                                    </div>
                                    @endif
                                </td>

                                <!-- Action -->
                                @if(! hasRole('Officer Trainee'))
                                <td class="text-end">
                                    @if(hasRole('Internal Faculty') || hasRole('Guest Faculty') || hasRole('Super
                                    Admin')
                                    || hasRole('Training Induction Admin'))
                                    @if($memo->status == 1)
                                    <button class="btn btn-sm btn-outline-primary" data-discipline="{{ $memo->pk }}"
                                        id="sendMemoBtn">
                                        <i class="bi bi-envelope-paper me-1"></i> Send
                                    </button>
                                    @elseif($memo->status == 2)
                                    <a href="{{ route('memo.discipline.memo.show', encrypt($memo->pk)) }}"
                                        class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-x-circle me-1"></i> Close
                                    </a>
                                    @else
                                    <span class="text-muted small">—</span>
                                    @endif
                                    @else
                                    <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                @endif
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                    <span class="fw-medium">No memo records available</span>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <!-- Pagination -->
                <div class="card-footer bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="text-muted small">
                        Showing {{ $memos->firstItem() ?? 0 }} to {{ $memos->lastItem() ?? 0 }}
                        of {{ $memos->total() }} records
                    </div>

                    <div>
                        {{ $memos->links('vendor.pagination.custom') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- end Zero Configuration -->

    <!-- Enhanced Offcanvas with GIGW Guidelines -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="chatOffcanvas" aria-labelledby="conversationTopic"
        role="dialog">
        <div class="offcanvas-header">
            <div class="d-flex flex-column w-100">
                <h4 class="offcanvas-title mb-2" id="conversationTopic">
                    <i class="material-symbols-rounded me-2" style="vertical-align: middle; font-size: 24px;">forum</i>
                    Conversation
                </h4>
                <h5 id="type_side_menu">Loading...</h5>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close conversation panel"
                title="Close">
            </button>
        </div>
        <input type="hidden" id="userType" value="" aria-hidden="true">

        <div class="offcanvas-body d-flex flex-column">
            <!-- Chat Body with Enhanced Styling -->
            <div class="chat-body flex-grow-1" id="chatBody" role="log" aria-live="polite"
                aria-label="Conversation messages">
                <div class="d-flex align-items-center justify-content-center h-100">
                    <div class="text-center">
                        <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                            <span class="visually-hidden">Loading conversation...</span>
                        </div>
                        <p class="text-muted">Loading conversation...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

@push('scripts')
<script>
$(document).ready(function() {
    const disciplineChoicesIds = ['program_name', 'status', 'discTimePeriod'];

    function initDisciplineChoices() {
        if (typeof window.Choices === 'undefined') return;
        disciplineChoicesIds.forEach(function(id) {
            const el = document.getElementById(id);
            if (!el || el.dataset.choicesInitialized === 'true') return;

            new Choices(el, {
                shouldSort: false,
                searchEnabled: true,
                searchResultLimit: 50,
                itemSelectText: '',
                allowHTML: false,
                classNames: {
                    containerInner: ['choices__inner', 'form-select', 'shadow-sm'],
                    input: ['choices__input', 'form-control', 'form-control-sm', 'border-0',
                        'shadow-none', 'my-1'
                    ],
                    inputCloned: ['choices__input--cloned'],
                    listDropdown: ['choices__list--dropdown', 'dropdown-menu', 'mt-1', 'p-0',
                        'shadow-sm', 'w-100'
                    ],
                    item: ['choices__item', 'dropdown-item', 'rounded-0'],
                    itemSelectable: ['choices__item--selectable'],
                    itemDisabled: ['choices__item--disabled', 'disabled'],
                    itemChoice: ['choices__item--choice'],
                    placeholder: ['choices__placeholder', 'text-muted', 'opacity-75'],
                    highlightedState: ['is-highlighted', 'active'],
                    notice: ['choices__notice', 'dropdown-item-text', 'text-muted', 'small',
                        'py-2'
                    ]
                }
            });

            el.dataset.choicesInitialized = 'true';
        });
    }

    initDisciplineChoices();

    /* ===============================
       FILTER (AJAX - no page refresh)
    =============================== */
    function discRunFilter() {
        if (typeof window.applyFiltersAjax === 'function') {
            window.applyFiltersAjax();
        } else {
            $('#filterForm')[0].submit();
        }
    }

    $('#program_name, #status, #from_date, #to_date').on('change', discRunFilter);

    /* ── Time Period presets → from/to dates ── */
    function discFmt(d) {
        return d.toISOString().split('T')[0];
    }
    $('#discTimePeriod').on('change', function() {
        var v = $(this).val();
        var today = new Date();
        $('#from_date, #to_date').addClass('d-none');
        if (v === 'custom') {
            $('#from_date, #to_date').removeClass('d-none');
            return;
        }
        var from = '',
            to = '';
        if (v === 'today') {
            from = to = discFmt(today);
        } else if (v === 'week') {
            var ws = new Date(today);
            ws.setDate(today.getDate() - today.getDay());
            from = discFmt(ws);
            to = discFmt(today);
        } else if (v === 'month') {
            from = discFmt(new Date(today.getFullYear(), today.getMonth(), 1));
            to = discFmt(today);
        }
        // v === 'all' → leave both empty (controller returns all when params present but empty)
        $('#from_date').val(from);
        $('#to_date').val(to);
        discRunFilter();
    });

    /* ── Search toggle + submit on Enter ── */
    $('#discSearchToggle').on('click', function() {
        $('#search').toggleClass('d-none');
        if (!$('#search').hasClass('d-none')) {
            $('#search').trigger('focus');
        }
    });
    $('#search').on('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            discRunFilter();
        }
    });

    /* ── Column Visibility modal (built from the actual header cells) ── */
    var $discGrid = $('#discColumnGrid');
    $('#discTable thead th').each(function(i) {
        var label = $(this).text().trim() || ('Column ' + (i + 1));
        var id = 'discCol' + i;
        $discGrid.append(
            '<label class="sn-colvis-chip" for="' + id + '" title="' + label + '">' +
            '<input type="checkbox" class="form-check-input disc-col-toggle" id="' + id +
            '" data-col="' + i + '" checked> ' +
            '<span>' + label + '</span></label>'
        );
    });
    $discGrid.on('change', '.disc-col-toggle', function() {
        var nth = parseInt($(this).data('col'), 10) + 1;
        var show = this.checked;
        $('#discTable tr').each(function() {
            $(this).children(':nth-child(' + nth + ')').toggle(show);
        });
    });

    /* ── Guarantee a full page reload when switching tabs ── */
    $(document).on('click', '.js-nav-tab', function(e) {
        if ($(this).hasClass('active')) {
            return;
        }
        var href = this.getAttribute('href');
        if (href) {
            e.preventDefault();
            window.location.assign(href);
        }
    });

    /* ===============================
       SEND MEMO
    =============================== */
    $(document).on('click', '#sendMemoBtn', function() {

        let discipline = $(this).data('discipline');

        Swal.fire({
            title: 'Are you sure?',
            text: "Do you want to send the memo?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, send it!'
        }).then((result) => {
            if (result.isConfirmed) {

                $.ajax({
                    url: "{{ route('memo.discipline.sendMemo') }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        discipline_pk: discipline
                    },
                    success: function(response) {
                        Swal.fire(
                            'Sent!',
                            'The memo has been sent.',
                            'success'
                        ).then(() => {
                            location.reload(); // refresh list
                        });
                    },
                    error: function() {
                        Swal.fire('Error!', 'Something went wrong.', 'error');
                    }
                });

            }
        });
    });
    $(document).on('click', '.view-conversation', function() {
        let memoId = $(this).data('id');
        let type = $(this).data('type');

        $('#conversationTopic').text("Topic: Discipline Conversation");
        $('#type_side_menu').text(type);
        $('#chatBody').html('<p class="text-muted text-center">Loading conversation...</p>');

        $.ajax({
            url: '/memo/discipline/get_conversation_model/' + memoId + '/' + type,
            type: 'GET',
            success: function(res) {
                $('#chatBody').html(res);
            },
            error: function() {
                $('#chatBody').html(
                    '<p class="text-danger text-center">Failed to load conversation.</p>'
                );
            }
        });

        // Show offcanvas
        let chatOffcanvas = new bootstrap.Offcanvas(document.getElementById('chatOffcanvas'));
        chatOffcanvas.show();
    });
});
</script>

@endpush

@push('scripts')
<script>
/* ── Generate Discipline Memo modal + Student picker ── */
$(function () {
    var routeStudents = "{{ route('memo.discipline.getStudentByCourse') }}";
    var routeMark     = "{{ route('memo.discipline.getMarkDeduction') }}";
    var todayStr      = "{{ date('Y-m-d') }}";

    var gmDefaulters = [];   // [{pk, name}] for the current course
    var gmSelected   = [];   // array of selected pks (strings)

    var genModalEl = document.getElementById('genMemoModal');
    var pickerModalEl = document.getElementById('studentPickerModal');
    var genModal = bootstrap.Modal.getOrCreateInstance(genModalEl);
    var pickerModal = bootstrap.Modal.getOrCreateInstance(pickerModalEl);
    var pendingOpenPicker = false;

    // One-at-a-time flow: Generate → (hide) → Picker → (hide/Save) → Generate.
    $('#gmSelectStudents').on('click', function () {
        pendingOpenPicker = true;
        genModal.hide();
    });
    $(genModalEl).on('hidden.bs.modal', function () {
        if (pendingOpenPicker) { pendingOpenPicker = false; pickerModal.show(); }
    });
    $(pickerModalEl).on('hidden.bs.modal', function () {
        // Closing the picker (Save or dismiss) always returns to the Generate modal.
        genModal.show();
    });

    function nameByPk(pk) {
        var f = gmDefaulters.filter(function (d) { return String(d.pk) === String(pk); })[0];
        return f ? f.name : '';
    }

    // ── Main form: summary, hidden inputs, preview ──
    function syncSelection() {
        // Summary text
        $('#gmSelectedSummary')
            .toggleClass('text-muted', gmSelected.length === 0)
            .text(gmSelected.length ? (gmSelected.length + ' student' + (gmSelected.length > 1 ? 's' : '') + ' selected') : 'Select Students');

        // Hidden inputs for submit
        var $h = $('#gmSelectedHidden').empty();
        gmSelected.forEach(function (pk) {
            $h.append($('<input type="hidden" name="selected_student_list[]">').val(pk));
        });

        // Preview student rows
        var $rows = $('#gmPvStudents').empty();
        if (!gmSelected.length) {
            $rows.append('<tr><td colspan="2" class="text-muted">No students selected.</td></tr>');
        } else {
            gmSelected.forEach(function (pk, i) {
                $rows.append($('<tr>').append($('<td>').text(i + 1)).append($('<td>').text(nameByPk(pk))));
            });
        }
    }

    function updatePreview() {
        $('#gmPvCourse').text($('#gmCourse option:selected').text() || 'Course Name');
        var d = $('#gmDate').val();
        $('#gmPvDate').text(d ? d.split('-').reverse().join('/') : '—');
        $('#gmPvDiscipline').text($('#gmDiscipline option:selected').text() && $('#gmDiscipline').val() ? $('#gmDiscipline option:selected').text() : '—');
        $('#gmPvMarks').text($('#gmMarks').val() || '—');
    }

    // ── Course change → load defaulters + disciplines ──
    $('#gmCourse').on('change', function () {
        var courseId = $(this).val();
        gmDefaulters = [];
        gmSelected = [];
        $('#gmDiscipline').html('<option value="">Select Discipline</option>');
        $('#gmMarks').val('');
        syncSelection();
        updatePreview();
        if (!courseId) return;

        $.get(routeStudents, { course_id: courseId }).done(function (res) {
            if (res && res.status) {
                gmDefaulters = (res.students || []).map(function (s) {
                    return { pk: String(s.pk), name: s.display_name + (s.generated_OT_code ? ' (' + s.generated_OT_code + ')' : '') };
                });
                (res.discipline_master_data || []).forEach(function (d) {
                    $('#gmDiscipline').append($('<option>').val(d.pk).text(d.discipline_name));
                });
            }
        });
    });

    $('#gmDiscipline').on('change', function () {
        var disc = $(this).val();
        var course = $('#gmCourse').val();
        if (disc && course) {
            $.get(routeMark, { discipline_master_pk: disc, course_id: course }).done(function (res) {
                $('#gmMarks').val(typeof res === 'number' ? res : (res || ''));
                updatePreview();
            });
        } else {
            $('#gmMarks').val('');
            updatePreview();
        }
    });
    $('#gmMarks, #gmDate').on('input change', updatePreview);

    $('#genMemoModal').on('show.bs.modal', function () {
        if (!$('#gmDate').val()) $('#gmDate').val(todayStr);
        updatePreview();
    });

    // ── Student picker ──
    function pickerItem(pk, name) {
        return $('<label class="an-item">')
            .attr('data-pk', pk)
            .attr('data-search', name.toLowerCase())
            .append($('<input type="checkbox" class="form-check-input an-check">'))
            .append($('<span>').text(name));
    }
    function renderPicker() {
        var $av = $('#spAvailable').empty();
        var $sel = $('#spSelected').empty();
        gmDefaulters.forEach(function (d) {
            (gmSelected.indexOf(d.pk) > -1 ? $sel : $av).append(pickerItem(d.pk, d.name));
        });
        if (!$av.children('.an-item').length) $av.append('<div class="an-empty text-muted">No students.</div>');
        if (!$sel.children('.an-item').length) $sel.append('<div class="an-empty text-muted">No students selected.</div>');
        $('.student-picker-modal .an-select-all').prop('checked', false);
        $('.student-picker-modal .an-filter').val('');
    }
    $('#studentPickerModal').on('show.bs.modal', renderPicker);

    function refreshPickerEmpties() {
        $('#spAvailable, #spSelected').each(function () {
            var $l = $(this);
            $l.children('.an-empty').remove();
            if (!$l.children('.an-item').length) {
                $l.append($('<div class="an-empty text-muted">').text(this.id === 'spSelected' ? 'No students selected.' : 'No students.'));
            }
        });
    }
    function moveItems(from, to, all) {
        var $items = $(from).children('.an-item');
        if (!all) $items = $items.filter(function () { return $(this).find('.an-check').prop('checked'); });
        $items.each(function () { $(this).find('.an-check').prop('checked', false); $(this).show(); $(to).append(this); });
        $('.student-picker-modal .an-select-all').prop('checked', false);
        refreshPickerEmpties();
    }
    $('#studentPickerModal').on('click', '.an-move-btn', function () {
        var m = $(this).data('move');
        if (m === 'all-right') moveItems('#spAvailable', '#spSelected', true);
        else if (m === 'right') moveItems('#spAvailable', '#spSelected', false);
        else if (m === 'left') moveItems('#spSelected', '#spAvailable', false);
        else if (m === 'all-left') moveItems('#spSelected', '#spAvailable', true);
    });
    $('#studentPickerModal').on('change', '.an-select-all', function () {
        $('#' + $(this).data('panel')).children('.an-item:visible').find('.an-check').prop('checked', this.checked);
    });
    $('#studentPickerModal').on('input', '.an-filter', function () {
        var q = this.value.toLowerCase();
        $('#' + $(this).data('target')).children('.an-item').each(function () {
            $(this).toggle(($(this).attr('data-search') || '').indexOf(q) > -1);
        });
    });

    // Save → commit picker's Selected panel, then return to the Generate modal
    $('#spSaveBtn').on('click', function () {
        gmSelected = $('#spSelected').children('.an-item').map(function () { return String($(this).data('pk')); }).get();
        syncSelection();
        updatePreview();
        pickerModal.hide(); // 'hidden.bs.modal' re-opens the Generate modal
    });

    // Guard submit
    $('#genMemoForm').on('submit', function (e) {
        if (!gmSelected.length) {
            e.preventDefault();
            alert('Please select at least one student.');
        }
    });
});
</script>
@endpush

@endsection
