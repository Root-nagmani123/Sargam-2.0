@extends('admin.layouts.master')

@section('title', 'Discipline Memo - Sargam | Lal Bahadur Shastri National Academy of Administration')

@section('setup_content')
<style>
/* GIGW Color Palette */
:root {
    --gigw-primary: #004a93;
    --gigw-primary-dark: #003366;
    --gigw-secondary: #0066cc;
    --gigw-light-bg: #f8f9fa;
    --gigw-border: #dee2e6;
    --gigw-text-muted: #6c757d;
    --gigw-success: #198754;
    --gigw-white: #ffffff;
}

/* Enhanced Offcanvas */
.offcanvas {
    width: 480px !important;
    max-width: 90vw;
    box-shadow: -4px 0 20px rgba(0, 74, 147, 0.15);
}

.offcanvas-header {
    background: linear-gradient(135deg, var(--gigw-primary), var(--gigw-secondary));
    color: var(--gigw-white);
    padding: 1.5rem;
    border-bottom: 3px solid var(--gigw-primary-dark);
    min-height: 80px;
}

.offcanvas-title {
    font-weight: 600;
    font-size: 1.25rem;
    letter-spacing: 0.3px;
    margin-bottom: 0.25rem;
    color: var(--gigw-white);
}

#type_side_menu {
    font-size: 0.875rem;
    font-weight: 500;
    opacity: 0.95;
    margin: 0;
    color: var(--gigw-white);
    background-color: rgba(255, 255, 255, 0.2);
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    display: inline-block;
}

.offcanvas .btn-close {
    background-color: rgba(255, 255, 255, 0.3);
    opacity: 1;
    border-radius: 50%;
    width: 36px;
    height: 36px;
    padding: 0;
    transition: all 0.2s ease;
}

.offcanvas .btn-close:hover {
    background-color: rgba(255, 255, 255, 0.5);
    transform: scale(1.1);
}

.offcanvas .btn-close:focus {
    outline: 3px solid var(--gigw-white);
    outline-offset: 2px;
    box-shadow: none;
}

.offcanvas-body {
    padding: 1.5rem;
    background-color: #fafbfc;
}

/* Enhanced Chat Body */
.chat-body {
    height: 480px;
    overflow-y: auto;
    background: linear-gradient(to bottom, #f8f9fa 0%, #ffffff 100%);
    padding: 1.25rem;
    border-radius: 0.75rem;
    border: 1px solid var(--gigw-border);
    box-shadow: inset 0 2px 8px rgba(0, 74, 147, 0.05);
    scroll-behavior: smooth;
}

.chat-body::-webkit-scrollbar {
    width: 8px;
}

.chat-body::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.chat-body::-webkit-scrollbar-thumb {
    background: var(--gigw-primary);
    border-radius: 10px;
    transition: background 0.3s ease;
}

.chat-body::-webkit-scrollbar-thumb:hover {
    background: var(--gigw-primary-dark);
}

/* Enhanced Chat Messages */
.chat-message {
    margin-bottom: 1rem;
    animation: slideIn 0.3s ease;
    clear: both;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.chat-message.user {
    text-align: right;
}

.chat-message .message {
    display: inline-block;
    padding: 0.75rem 1rem;
    border-radius: 1.25rem;
    max-width: 80%;
    word-wrap: break-word;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    transition: transform 0.2s ease;
    font-size: 0.95rem;
    line-height: 1.5;
}

.chat-message .message:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.chat-message.bot .message {
    background: linear-gradient(135deg, #e9ecef 0%, #f8f9fa 100%);
    color: #212529;
    border: 1px solid #dee2e6;
    border-left: 4px solid var(--gigw-primary);
}

.chat-message.user .message {
    background: linear-gradient(135deg, var(--gigw-primary), var(--gigw-secondary));
    color: var(--gigw-white);
    border: none;
}

/* Loading State */
.chat-body .text-muted {
    color: var(--gigw-text-muted) !important;
    font-style: italic;
    padding: 2rem;
    text-align: center;
}

/* Accessibility Enhancements */
.offcanvas:focus-visible {
    outline: 3px solid var(--gigw-primary);
    outline-offset: 2px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .offcanvas {
        width: 100% !important;
    }

    .offcanvas-header {
        padding: 1rem;
        min-height: 70px;
    }

    .offcanvas-title {
        font-size: 1.1rem;
    }

    .chat-body {
        height: calc(100vh - 250px);
        padding: 1rem;
    }

    .chat-message .message {
        max-width: 85%;
        font-size: 0.9rem;
    }
}

/* Sticky Table Status */
.table .sticky-status {
    position: sticky;
    right: 0;
    background: #fff;
    z-index: 10;
    box-shadow: -4px 0 6px rgba(0, 0, 0, 0.08);
}

/* WCAG 2.1 AA Compliance */
.offcanvas * {
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}

/* Chat Row Layout */
.chat-row {
    display: flex;
    margin-bottom: 15px;
}

.chat-row.right {
    justify-content: flex-end;
}

.chat-row.left {
    justify-content: flex-start;
}

/* Message Bubble */
.chat-bubble {
    max-width: 80%;
    background: #f4f5f7;
    padding: 12px 15px;
    border-radius: 12px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
    border: 1px solid #e2e2e2;
}

.chat-row.right .chat-bubble {
    background: #e7f1ff;
    border-color: #c9ddff;
}

/* Header */
.chat-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 4px;
}

.chat-sender {
    color: #003e7e;
    font-weight: 600;
}

.chat-time {
    font-size: 11px;
    color: #6c757d;
}

/* Message Text */
.chat-text {
    margin: 0;
    font-size: 14px;
    color: #222;
    line-height: 1.4;
}

/* Attachments */
.chat-attachment {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    margin-top: 8px;
    font-size: 14px;
    color: #004a93;
    text-decoration: none;
}

.chat-attachment:hover {
    text-decoration: underline;
}

/* Footer Input */
.chat-footer {
    background: #fff;
}

.chat-input-wrapper {
    display: flex;
    align-items: center;
    gap: 10px;
}

.attachment-btn {
    cursor: pointer;
    color: #004a93;
    font-size: 22px;
}

.chat-textarea {
    resize: none;
    height: 40px;
    font-size: 14px;
}

.chat-send-btn {
    height: 40px;
    padding: 0 20px;
}

/* Scrollable message area */
#chatBody {
    padding-bottom: 20px;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: #b3b3b3 #efefef;
}

#chatBody::-webkit-scrollbar {
    width: 8px;
}

#chatBody::-webkit-scrollbar-thumb {
    background: #b3b3b3;
    border-radius: 4px;
}

/* Accessibility: Focus outline */
*:focus-visible {
    outline: 3px solid #004a93 !important;
    border-radius: 4px;
}
</style>
<div class="container-fluid memo-discipline-index">
    <x-breadcrum title="Discipline Memo" />
    <x-session_message />

    <!-- start Zero Configuration -->
    <div class="card" style="border-left:4px solid #004a93;">
        <div class="card-body">
            <div class="row">
                <div class="col-6">
                    <h4 class="card-title">Discipline Memo</h4>
                </div>
                <div class="col-6">
                    <div class="d-flex justify-content-end align-items-center gap-2">

                        <!-- Add Group Mapping -->
                        @if(hasRole('Internal Faculty') || hasRole('Guest Faculty') || hasRole('Admin') ||
                        hasRole('Training-Induction'))
                        <a href="{{ route('memo.discipline.create') }}"
                            class="btn btn-primary d-flex align-items-center">
                            <i class="material-icons menu-icon material-symbols-rounded"
                                style="font-size: 24px;">add</i>
                            Discipline Memo
                        </a>
                        @endif


                    </div>
                </div>
            </div>
            <hr class="my-2">
            <form method="GET" action="{{ route('memo.discipline.index') }}" id="filterForm" class="mb-4">
                <div class="row g-3">
                    <!-- Program Filter -->
                    <div class="col-md-6 col-lg-3">
                        <div class="form-group">
                            <label for="program_name" class="form-label fw-semibold">
                                <i class="bi bi-book me-1"></i>Program Name
                            </label>
                            <select class="form-select form-select-md" id="program_name" name="program_name"
                                aria-label="Select program">
                                <option value="">All Programs</option>
                                @foreach($courses as $course)
                                <option value="{{ $course->pk }}"
                                    {{ (string)$programNameFilter == (string)$course->pk ? 'selected' : '' }}
                                    data-course-code="{{ $course->course_code ?? '' }}">
                                    {{ $course->course_name }}
                                    @if(isset($course->course_code) && $course->course_code)
                                    ({{ $course->course_code }})
                                    @endif
                                </option>
                                @endforeach
                            </select>
                            <div class="form-text">Filter by academic program</div>
                        </div>
                    </div>

                    <!-- Status Filter -->
                    <div class="col-md-6 col-lg-2">
                        <div class="form-group">
                            <label for="status" class="form-label fw-semibold">
                                <i class="bi bi-flag me-1"></i>Status
                            </label>
                            <select class="form-select form-select-md" id="status" name="status"
                                aria-label="Select status">
                                <option value="">All Status</option>
                                <option value="1" {{ $statusFilter == '1' ? 'selected' : '' }}>Recorded</option>
                                <option value="2" {{ $statusFilter == '2' ? 'selected' : '' }}>Memo Sent</option>
                                <option value="3" {{ $statusFilter == '3' ? 'selected' : '' }}>Closed</option>
                            </select>
                            <div class="form-text">Filter by memo status</div>
                        </div>
                    </div>

                    <!-- Search Filter -->
                    <div class="col-md-6 col-lg-3">
                        <div class="form-group">
                            <label for="search" class="form-label fw-semibold">
                                <i class="bi bi-search me-1"></i>Search
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="bi bi-search text-muted"></i>
                                </span>
                                <input type="text" class="form-control border-start-0" id="search" name="search"
                                    placeholder="Student name, ID, or memo details..." value="{{ $searchFilter }}"
                                    aria-label="Search memos">
                            </div>
                            <div class="form-text">Search across multiple fields</div>
                        </div>
                    </div>

                    <!-- Date Range Filters -->
                    <div class="col-md-6 col-lg-4">
                        <div class="row g-2">
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="from_date" class="form-label fw-semibold">
                                        <i class="bi bi-calendar-event me-1"></i>From Date
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0">
                                            <i class="bi bi-calendar-minus text-muted"></i>
                                        </span>
                                        <input type="date" class="form-control" id="from_date" name="from_date"
                                            value="{{ $fromDateFilter ?: \Carbon\Carbon::today()->toDateString() }}"
                                            max="{{ \Carbon\Carbon::today()->toDateString() }}" aria-label="Start date">
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="to_date" class="form-label fw-semibold">
                                        <i class="bi bi-calendar-event me-1"></i>To Date
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0">
                                            <i class="bi bi-calendar-plus text-muted"></i>
                                        </span>
                                        <input type="date" class="form-control" id="to_date" name="to_date"
                                            value="{{ $toDateFilter ?: \Carbon\Carbon::today()->toDateString() }}"
                                            max="{{ \Carbon\Carbon::today()->toDateString() }}" aria-label="End date">
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-text">Select date range for memos</div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="col-12 mt-3">
                        <div
                            class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 p-3 bg-light rounded border">
                            <div class="d-flex align-items-center">
                                <span class="me-2 text-muted">
                                    <i class="bi bi-filter-circle me-1"></i>
                                </span>
                                <small class="text-muted">
                                    <span id="activeFilterCount" class="badge bg-primary me-2">0</span>
                                    Active filters
                                </small>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-secondary d-flex align-items-center"
                                    onclick="clearFilters()" aria-label="Clear all filters">
                                    <i class="bi bi-arrow-clockwise me-2"></i>
                                    Reset All
                                </button>

                                <button type="button" class="btn btn-outline-danger d-flex align-items-center"
                                    id="clearFiltersBtn" aria-label="Remove filters and show all">
                                    <i class="bi bi-x-circle me-2"></i>
                                    Clear Filters
                                </button>

                                <button type="submit" class="btn btn-primary d-flex align-items-center"
                                    aria-label="Apply selected filters">
                                    <i class="bi bi-funnel me-2"></i>
                                    Apply Filters
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Filter Summary (Optional, shows when filters are active) -->
                    @if($programNameFilter || $statusFilter || $searchFilter || ($fromDateFilter && $toDateFilter))
                    <div class="col-12" id="filterSummary">
                        <div class="alert alert-info alert-dismissible fade show mt-2" role="alert">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-info-circle me-2 fs-5"></i>
                                <div>
                                    <strong>Active Filters:</strong>
                                    <div class="d-flex flex-wrap gap-2 mt-1">
                                        @if($programNameFilter)
                                        @php
                                        $selectedCourse = $courses->where('pk', $programNameFilter)->first();
                                        @endphp
                                        <span class="badge bg-primary d-flex align-items-center">
                                            Program: {{ $selectedCourse->course_name ?? 'Selected' }}
                                            <a href="#" class="text-white ms-2" onclick="removeFilter('program_name')">
                                                <i class="bi bi-x"></i>
                                            </a>
                                        </span>
                                        @endif

                                        @if($statusFilter)
                                        @php
                                        $statusLabels = ['1' => 'Recorded', '2' => 'Memo Sent', '3' => 'Closed'];
                                        @endphp
                                        <span class="badge bg-success d-flex align-items-center">
                                            Status: {{ $statusLabels[$statusFilter] ?? 'Selected' }}
                                            <a href="#" class="text-white ms-2" onclick="removeFilter('status')">
                                                <i class="bi bi-x"></i>
                                            </a>
                                        </span>
                                        @endif

                                        @if($searchFilter)
                                        <span class="badge bg-warning text-dark d-flex align-items-center">
                                            Search:
                                            "{{ substr($searchFilter, 0, 20) }}{{ strlen($searchFilter) > 20 ? '...' : '' }}"
                                            <a href="#" class="text-dark ms-2" onclick="removeFilter('search')">
                                                <i class="bi bi-x"></i>
                                            </a>
                                        </span>
                                        @endif

                                        @if($fromDateFilter && $toDateFilter)
                                        <span class="badge bg-info d-flex align-items-center">
                                            Date: {{ \Carbon\Carbon::parse($fromDateFilter)->format('M d') }} -
                                            {{ \Carbon\Carbon::parse($toDateFilter)->format('M d, Y') }}
                                            <a href="#" class="text-white ms-2" onclick="removeDateFilters()">
                                                <i class="bi bi-x"></i>
                                            </a>
                                        </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    </div>
                    @endif
                </div>
            </form>

            <!-- Add this JavaScript for enhanced UX (AJAX filter - no full page refresh) -->
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Update active filter count
                function updateFilterCount() {
                    const form = document.getElementById('filterForm');
                    if (!form) return;
                    const inputs = form.querySelectorAll('select, input[type="text"], input[type="date"]');
                    let activeCount = 0;
                    inputs.forEach(input => {
                        if ((input.tagName === 'SELECT' && input.value !== '') ||
                            (input.type === 'text' && input.value.trim() !== '') ||
                            (input.type === 'date' && input.value !== '')) {
                            activeCount++;
                        }
                    });
                    const el = document.getElementById('activeFilterCount');
                    if (el) el.textContent = activeCount;
                }

                // Apply filters via AJAX (no full page refresh)
                function applyFiltersAjax() {
                    const form = document.getElementById('filterForm');
                    const tableWrap = document.querySelector('.table-responsive');
                    if (!form || !tableWrap) return;
                    const formData = new FormData(form);
                    const params = new URLSearchParams(formData).toString();
                    const url = "{{ route('memo.discipline.index') }}" + (params ? '?' + params : '');
                    tableWrap.style.opacity = '0.5';
                    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(function(r) { return r.text(); })
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
                            const newTable = doc.querySelector('.table-responsive');
                            if (newTable) tableWrap.innerHTML = newTable.innerHTML;
                            window.history.replaceState({}, '', url);
                            updateFilterCount();
                        })
                        .catch(function() { alert('Failed to apply filters'); })
                        .finally(function() { tableWrap.style.opacity = '1'; });
                }
                window.applyFiltersAjax = applyFiltersAjax;

                // Prevent form full-page submit; use AJAX
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
                document.querySelectorAll('#filterForm select, #filterForm input').forEach(input => {
                    input.addEventListener('change', updateFilterCount);
                    input.addEventListener('input', updateFilterCount);
                });

                // Date validation
                const fromDate = document.getElementById('from_date');
                const toDate = document.getElementById('to_date');
                if (fromDate && toDate) {
                    fromDate.addEventListener('change', function() { toDate.min = this.value; });
                    toDate.addEventListener('change', function() { fromDate.max = this.value; });
                }

                // Clear Filters button (no full page reload)
                const clearFiltersBtn = document.getElementById('clearFiltersBtn');
                if (clearFiltersBtn && filterForm) {
                    clearFiltersBtn.addEventListener('click', function() {
                        filterForm.querySelectorAll('select').forEach(s => s.value = '');
                        filterForm.querySelectorAll('input[type="text"]').forEach(i => i.value = '');
                        const today = new Date().toISOString().split('T')[0];
                        filterForm.querySelectorAll('input[type="date"]').forEach(i => i.value = today);
                        applyFiltersAjax();
                    });
                }
            });

            // Clear all filters (Reset All button) - AJAX
            function clearFilters() {
                const form = document.getElementById('filterForm');
                if (!form) return;
                form.querySelectorAll('select').forEach(select => select.value = '');
                form.querySelectorAll('input[type="text"]').forEach(input => input.value = '');
                const today = new Date().toISOString().split('T')[0];
                form.querySelectorAll('input[type="date"]').forEach(input => input.value = today);
                if (typeof window.applyFiltersAjax === 'function') window.applyFiltersAjax();
                else form.submit();
            }

            // Remove specific filter - AJAX
            function removeFilter(filterName) {
                const input = document.querySelector('[name="' + filterName + '"]');
                if (input) input.value = '';
                if (typeof window.applyFiltersAjax === 'function') window.applyFiltersAjax();
                else document.getElementById('filterForm').submit();
            }

            // Remove date filters - AJAX
            function removeDateFilters() {
                const fd = document.getElementById('from_date');
                const td = document.getElementById('to_date');
                if (fd) fd.value = '';
                if (td) td.value = '';
                if (typeof window.applyFiltersAjax === 'function') window.applyFiltersAjax();
                else document.getElementById('filterForm').submit();
            }
            </script>

            <style>
            .form-select:focus,
            .form-control:focus {
                border-color: #86b7fe;
                box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
            }

            .input-group-text {
                transition: all 0.2s ease;
            }

            .badge a {
                text-decoration: none;
                opacity: 0.8;
                transition: opacity 0.2s;
            }

            .badge a:hover {
                opacity: 1;
            }

            .alert {
                border-left: 4px solid #0d6efd;
            }

            .btn {
                transition: all 0.2s ease;
            }

            .btn-primary {
                background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
                border: none;
            }

            .btn-primary:hover {
                background: linear-gradient(135deg, #0b5ed7 0%, #0a58ca 100%);
                transform: translateY(-1px);
                box-shadow: 0 4px 8px rgba(13, 110, 253, 0.2);
            }

            .form-label {
                color: #495057;
                font-size: 0.875rem;
                margin-bottom: 0.5rem;
            }

            .form-text {
                font-size: 0.75rem;
                color: #6c757d;
                margin-top: 0.25rem;
            }

            .bg-light {
                background-color: #f8f9fa !important;
            }

            @media (max-width: 768px) {
                .btn {
                    width: 100%;
                    justify-content: center;
                }

                .d-flex.gap-2 {
                    width: 100%;
                    flex-direction: column;
                }
            }
            </style>
            <hr>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-nowrap">
                    <thead class="sticky-top">
                        <tr>
                            <th width="60">#</th>
                            <th>Program</th>
                            <th>Participant</th>
                            <th>Date</th>
                            <th>Discipline</th>
                            <th class="text-center">Submitted</th>
                            <th class="text-center">Final</th>
                            <th>Remarks</th>
                            <th>Status</th>
                            @if(! hasRole('Student-OT'))
                            <th class="text-end">Action</th>
                            @endif
                        </tr>
                    </thead>

                    <tbody>
                        @if ($memos->isEmpty())
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                <span class="fw-medium">No memo records available</span>
                            </td>
                        </tr>
                        @else
                        @foreach ($memos as $index => $memo)
                        <tr>
                            <!-- Serial -->
                            <td class="fw-semibold text-muted">
                                {{ $memos->firstItem() + $index }}
                            </td>

                            <!-- Program -->
                            <td>
                                <div class="fw-semibold">
                                    {{ $memo->course->course_name ?? 'N/A' }}
                                </div>
                            </td>

                            <!-- Participant -->
                            <td>
                                <div class="fw-semibold">
                                    {{ $memo->student->display_name ?? 'N/A' }}
                                </div>
                            </td>

                            <!-- Date -->
                            <td class="text-muted">
                                {{ \Carbon\Carbon::parse($memo->date)->format('d M Y') }}
                            </td>

                            <!-- Discipline -->
                            <td>
                                <span class="badge bg-info-subtle text-info">
                                    {{ $memo->discipline->discipline_name ?? 'N/A' }}
                                </span>
                            </td>

                            <!-- Marks -->
                            <td class="text-center fw-semibold text-warning">
                                {{ $memo->mark_deduction_submit }}
                            </td>

                            <td class="text-center fw-semibold text-danger">
                                {{ $memo->final_mark_deduction }}
                            </td>

                            <!-- Remarks -->
                            <td class="text-muted small">
                                {{ $memo->remarks ?? '-' }}
                            </td>

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
                                        data-type="{{ (hasRole('Internal Faculty') || hasRole('Guest Faculty') || hasRole('Admin') || hasRole('Training-Induction')) ? 'admin' : 'OT' }}">
                                        <i class="material-icons material-symbols-rounded fs-5">chat</i>
                                    </a>
                                </div>
                                @else
                                <a class="text-success view-conversation" data-bs-toggle="offcanvas"
                                    data-bs-target="#chatOffcanvas" data-id="{{ $memo->pk }}"
                                    data-type="{{ (hasRole('Internal Faculty') || hasRole('Guest Faculty') || hasRole('Admin') || hasRole('Training-Induction')) ? 'admin' : 'OT' }}">
                                    <i class="material-icons material-symbols-rounded fs-5">chat</i>
                                </a>
                                <span class="badge bg-secondary-subtle text-secondary">
                                    <i class="bi bi-lock me-1"></i> Closed
                                </span>
                                @endif
                            </td>

                            <!-- Action -->
                            @if(! hasRole('Student-OT'))
                            <td class="text-end">
                                @if(hasRole('Internal Faculty') || hasRole('Guest Faculty') || hasRole('Admin')
                                || hasRole('Training-Induction'))
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
                        @endforeach
                        @endif
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
@include('components.jquery-3-6')

@push('scripts')
<script>
$(document).ready(function() {

    /* ===============================
       FILTER AUTO SUBMIT
    =============================== */
    $('#program_name, #status').on('change', function() {
        $('#filterForm').submit();
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
    $('.view-conversation').on('click', function() {
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

@endsection