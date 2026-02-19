@extends('admin.layouts.master')

@section('title', 'Discipline Memo - Sargam | Lal Bahadur Shastri National Academy of Administration')

@section('setup_content')
<div class="container-fluid py-3">
    <x-breadcrum title="Discipline Memo" />
    <x-session_message />

    <!-- start Zero Configuration -->
    <div class="card shadow-sm border-0 border-start border-4 border-primary rounded-3">
        <div class="card-body p-4">
            <div class="row align-items-center g-2 mb-0">
                <div class="col-12 col-md-6">
                    <h4 class="card-title mb-0 fw-bold text-body">Discipline Memo</h4>
                </div>
                <div class="col-12 col-md-6">
                    <div class="d-flex justify-content-md-end align-items-center gap-2 flex-wrap">

                        @if(hasRole('Internal Faculty') || hasRole('Guest Faculty') || hasRole('Admin') ||
                        hasRole('Training-Induction'))
                        <a href="{{ route('memo.discipline.create') }}"
                            class="btn btn-primary d-inline-flex align-items-center gap-2">
                            <i class="material-icons menu-icon material-symbols-rounded fs-5">add</i>
                            Discipline Memo
                        </a>
                        @endif

                    </div>
                </div>
            </div>
            <hr class="my-3">
            <form method="GET" action="{{ route('memo.discipline.index') }}" id="filterForm" class="mb-4" onsubmit="return false;">
                <div class="row g-3">
                    <div class="col-12 col-md-6 col-lg-3">
                        <label for="program_name" class="form-label fw-semibold small text-body-secondary">
                            <i class="bi bi-book me-1"></i>Program Name
                        </label>
                        <select class="form-select" id="program_name" name="program_name"
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
                        <div class="form-text small">Filter by academic program</div>
                    </div>

                    <div class="col-12 col-md-6 col-lg-2">
                        <label for="status" class="form-label fw-semibold small text-body-secondary">
                            <i class="bi bi-flag me-1"></i>Status
                        </label>
                        <select class="form-select" id="status" name="status" aria-label="Select status">
                            <option value="">All Status</option>
                            <option value="1" {{ $statusFilter == '1' ? 'selected' : '' }}>Recorded</option>
                            <option value="2" {{ $statusFilter == '2' ? 'selected' : '' }}>Memo Sent</option>
                            <option value="3" {{ $statusFilter == '3' ? 'selected' : '' }}>Closed</option>
                        </select>
                        <div class="form-text small">Filter by memo status</div>
                    </div>

                    <div class="col-12 col-md-6 col-lg-3">
                        <label for="search" class="form-label fw-semibold small text-body-secondary">
                            <i class="bi bi-search me-1"></i>Search
                        </label>
                        <div class="input-group has-validation">
                            <span class="input-group-text bg-body-secondary border-end-0">
                                <i class="bi bi-search text-muted"></i>
                            </span>
                            <input type="text" class="form-control border-start-0 ps-0" id="search" name="search"
                                placeholder="Student name, ID, or memo details..." value="{{ $searchFilter }}"
                                aria-label="Search memos">
                        </div>
                        <div class="form-text small">Search across multiple fields</div>
                    </div>

                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="row g-2">
                            <div class="col-6">
                                <label for="from_date" class="form-label fw-semibold small text-body-secondary">
                                    <i class="bi bi-calendar-event me-1"></i>From Date
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-body-secondary border-end-0">
                                        <i class="bi bi-calendar-minus text-muted"></i>
                                    </span>
                                    <input type="date" class="form-control" id="from_date" name="from_date"
                                        value="{{ $fromDateFilter ?? '' }}"
                                        max="{{ \Carbon\Carbon::today()->toDateString() }}" aria-label="Start date">
                                </div>
                            </div>
                            <div class="col-6">
                                <label for="to_date" class="form-label fw-semibold small text-body-secondary">
                                    <i class="bi bi-calendar-event me-1"></i>To Date
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-body-secondary border-end-0">
                                        <i class="bi bi-calendar-plus text-muted"></i>
                                    </span>
                                    <input type="date" class="form-control" id="to_date" name="to_date"
                                        value="{{ $toDateFilter ?? '' }}"
                                        max="{{ \Carbon\Carbon::today()->toDateString() }}" aria-label="End date">
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-text small">Select date range for memos</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 mt-2">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 p-3 bg-body-secondary rounded-3 border">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-filter-circle text-primary me-2"></i>
                                <small class="text-body-secondary">
                                    <span id="activeFilterCount" class="badge rounded-pill bg-primary me-2">0</span>
                                    Active filters
                                </small>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                              
                                <button type="button" class="btn btn-outline-danger btn-sm d-inline-flex align-items-center"
                                    id="clearFiltersBtn" aria-label="Remove filters and show all">
                                    <i class="bi bi-x-circle me-1"></i>
                                    Clear Filters
                                </button>
                                <button type="submit" class="btn btn-primary btn-sm d-inline-flex align-items-center"
                                    aria-label="Apply selected filters">
                                    <i class="bi bi-funnel me-1"></i>
                                    Apply Filters
                                </button>
                            </div>
                        </div>
                    </div>

                    @if($programNameFilter || $statusFilter || $searchFilter || ($fromDateFilter && $toDateFilter))
                    <div class="col-12" id="filterSummary">
                        <div class="alert alert-info fade show d-flex align-items-start gap-2 rounded-3 border-0 shadow-sm filter-summary-alert" id="filterSummaryAlert" role="alert">
                            <i class="bi bi-info-circle fs-5 flex-shrink-0 mt-1"></i>
                            <div class="flex-grow-1">
                                <strong class="d-block mb-2">Active Filters</strong>
                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    @if($programNameFilter)
                                    @php
                                    $selectedCourse = $courses->where('pk', $programNameFilter)->first();
                                    @endphp
                                    <span class="badge rounded-pill bg-primary d-inline-flex align-items-center gap-1 py-2">
                                        Program: {{ $selectedCourse->course_name ?? 'Selected' }}
                                        <a href="#" class="text-white text-decoration-none opacity-75 hover-opacity-100" onclick="removeFilter('program_name'); return false;" aria-label="Remove program filter"><i class="bi bi-x-lg small"></i></a>
                                    </span>
                                    @endif
                                    @if($statusFilter)
                                    @php
                                    $statusLabels = ['1' => 'Recorded', '2' => 'Memo Sent', '3' => 'Closed'];
                                    @endphp
                                    <span class="badge rounded-pill bg-success d-inline-flex align-items-center gap-1 py-2">
                                        Status: {{ $statusLabels[$statusFilter] ?? 'Selected' }}
                                        <a href="#" class="text-white text-decoration-none opacity-75" onclick="removeFilter('status'); return false;" aria-label="Remove status filter"><i class="bi bi-x-lg small"></i></a>
                                    </span>
                                    @endif
                                    @if($searchFilter)
                                    <span class="badge rounded-pill bg-warning text-dark d-inline-flex align-items-center gap-1 py-2">
                                        Search: "{{ substr($searchFilter, 0, 20) }}{{ strlen($searchFilter) > 20 ? '...' : '' }}"
                                        <a href="#" class="text-dark text-decoration-none opacity-75" onclick="removeFilter('search'); return false;" aria-label="Remove search filter"><i class="bi bi-x-lg small"></i></a>
                                    </span>
                                    @endif
                                    @if($fromDateFilter && $toDateFilter)
                                    <span class="badge rounded-pill bg-info text-dark d-inline-flex align-items-center gap-1 py-2">
                                        Date: {{ \Carbon\Carbon::parse($fromDateFilter)->format('M d') }} - {{ \Carbon\Carbon::parse($toDateFilter)->format('M d, Y') }}
                                        <a href="#" class="text-dark text-decoration-none opacity-75" onclick="removeDateFilters(); return false;" aria-label="Remove date filter"><i class="bi bi-x-lg small"></i></a>
                                    </span>
                                    @endif
                                </div>
                            </div>
                            <button type="button" class="btn-close flex-shrink-0 filter-summary-close" aria-label="Close"></button>
                        </div>
                        <a href="#" id="showFilterDetailsLink" class="show-filter-details-link d-none small text-primary text-decoration-none d-inline-flex align-items-center gap-1 mt-1" aria-label="Show active filter details">
                            <i class="bi bi-chevron-down"></i> Show active filter details
                        </a>
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
                    const listContainer = document.getElementById('memoDisciplineListContainer');
                    if (!form || !listContainer) return;
                    const formData = new FormData(form);
                    const params = new URLSearchParams(formData).toString();
                    const url = "{{ route('memo.discipline.index') }}" + (params ? '?' + params : '');
                    listContainer.style.opacity = '0.5';
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
                            const newList = doc.querySelector('#memoDisciplineListContainer');
                            if (newList) listContainer.innerHTML = newList.innerHTML;
                            window.history.replaceState({}, '', url);
                            updateFilterCount();
                        })
                        .catch(function() { alert('Failed to apply filters'); })
                        .finally(function() { listContainer.style.opacity = '1'; });
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
                        filterForm.querySelectorAll('input[type="date"]').forEach(i => i.value = '');
                        applyFiltersAjax();
                    });
                }

                // Toggle Active Filters: close hides alert and shows "Show active filter details"; link shows alert again (event delegation for AJAX)
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

            // Clear all filters (Reset All button) - AJAX
            function clearFilters() {
                const form = document.getElementById('filterForm');
                if (!form) return;
                form.querySelectorAll('select').forEach(select => select.value = '');
                form.querySelectorAll('input[type="text"]').forEach(input => input.value = '');
                form.querySelectorAll('input[type="date"]').forEach(input => input.value = '');
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

            <hr class="my-3">
            <div id="memoDisciplineListContainer">
            <div class="table-responsive rounded-3 border">
                    <table class="table align-middle mb-0 text-nowrap">
                    <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Program</th>
                            <th scope="col">Participant</th>
                            <th scope="col">Date</th>
                            <th scope="col">Discipline</th>
                            <th scope="col">Submitted</th>
                            <th scope="col">Final</th>
                            <th scope="col">Remarks</th>
                            <th scope="col">Status</th>
                            @if(! hasRole('Student-OT'))
                            <th scope="col">Action</th>
                            @endif
                        </tr>
                    </thead>

                    <tbody class="table-group-divider">
                        @if ($memos->isEmpty())
                        <tr>
                            <td colspan="10" class="text-center py-5">
                                <div class="py-4">
                                    <i class="bi bi-inbox display-4 text-body-secondary d-block mb-3"></i>
                                    <p class="fw-semibold text-body-secondary mb-0">No memo records available</p>
                                    <p class="small text-muted mt-1">Try adjusting your filters or date range.</p>
                                </div>
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
                                <span class="badge bg-info-subtle text-info rounded-pill">
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
       FILTER (AJAX - no page refresh)
    =============================== */
    $('#program_name, #status').on('change', function() {
        if (typeof window.applyFiltersAjax === 'function') {
            window.applyFiltersAjax();
        } else {
            $('#filterForm')[0].submit();
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

@endsection