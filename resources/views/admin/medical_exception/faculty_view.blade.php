@extends('admin.layouts.master')

@section('title', 'Medical Exception Faculty View - Sargam | Lal Bahadur')

@section('setup_content')

<style>
    .filter-select {
        border: 1px solid #dee2e6;
        border-radius: 6px;
        padding: 6px 32px 6px 14px;
        font-size: 14px;
        color: #495057;
        background-color: #fff;
        min-width: 160px;
        cursor: pointer;
    }
    .filter-date {
        border: 1px solid #dee2e6;
        border-radius: 6px;
        padding: 6px 14px;
        font-size: 14px;
        color: #495057;
        background-color: #fff;
        min-width: 160px;
    }
    .btn-reset-filters {
        border: 1px solid #dc3545;
        color: #dc3545;
        background: transparent;
        border-radius: 6px;
        padding: 6px 16px;
        font-size: 14px;
        font-weight: 500;
        text-decoration: none;
        white-space: nowrap;
    }
    .btn-reset-filters:hover {
        background: #dc3545;
        color: #fff;
    }
    .btn-search-icon {
        border: 1px solid #dee2e6;
        border-radius: 6px;
        padding: 6px 10px;
        background: #fff;
        color: #495057;
        cursor: pointer;
    }
    .btn-search-icon:hover {
        background: #f8f9fa;
    }
    /* Search input wrapper */
    .search-wrapper {
        position: relative;
        display: flex;
        align-items: center;
    }
    .search-input-field {
        border: 1px solid #dee2e6;
        border-radius: 6px;
        padding: 6px 34px 6px 12px;
        font-size: 14px;
        color: #495057;
        background-color: #fff;
        width: 220px;
        outline: none;
        transition: width 0.3s ease, opacity 0.3s ease;
    }
    .search-input-field:focus {
        border-color: #86b7fe;
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
    }
    .search-input-field::placeholder {
        color: #adb5bd;
    }
    .search-icon-inside {
        position: absolute;
        right: 8px;
        top: 50%;
        transform: translateY(-50%);
        color: #6c757d;
        font-size: 20px;
        pointer-events: none;
    }
    /* Small screens: hide input by default, show on toggle */
    @media (max-width: 767.98px) {
        .search-full {
            display: none;
        }
        .search-toggle-btn {
            display: inline-flex;
        }
        .search-collapsed-input {
            display: none;
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            z-index: 10;
        }
        .search-collapsed-input.show {
            display: flex;
        }
    }
    /* Large screens: show full input, hide toggle button */
    @media (min-width: 768px) {
        .search-full {
            display: flex;
        }
        .search-toggle-btn {
            display: none;
        }
        .search-collapsed-input {
            display: none;
        }
    }
    .badge-submitted {
        background-color: #d1f5d3;
        color: #198754;
        font-weight: 500;
        padding: 5px 14px;
        border-radius: 20px;
        font-size: 13px;
    }
    .badge-not-submitted {
        background-color: #fde2d8;
        color: #dc3545;
        font-weight: 500;
        padding: 5px 14px;
        border-radius: 20px;
        font-size: 13px;
    }
    .action-icon {
        font-size: 22px;
        cursor: pointer;
    }
    .action-icon.active {
        color: #0d6efd;
    }
    .action-icon.disabled {
        color: #c0c0c0;
    }
    .table > thead > tr > th {
        font-weight: 600;
        font-size: 14px;
        color: #495057;
        white-space: nowrap;
        border-bottom: 2px solid #dee2e6;
    }
    .table > tbody > tr > td {
        font-size: 14px;
        color: #212529;
        vertical-align: middle;
        padding: 14px 12px;
    }
    .table > tbody > tr {
        border-bottom: 1px solid #f0f0f0;
    }
    .pagination-info {
        font-size: 14px;
        color: #6c757d;
    }
</style>

<div class="container-fluid">
    <x-breadcrum title="Medical Exception Faculty View"></x-breadcrum>
    <div class="d-flex justify-content-end mb-3">
                <button type="button" class="btn btn-outline-primary d-flex align-items-center gap-1" onclick="window.print()">
                    <i class="material-icons" style="font-size: 20px;">download</i>
                    Download
                </button>
            </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            {{-- Download Button --}}

            {{-- Filters Row --}}
            <form class="d-flex align-items-center gap-3 mb-4 flex-wrap" role="search" aria-label="Medical exception filters" method="GET" action="{{ route('medical.exception.faculty.view') }}">
                <span class="fw-semibold text-muted" style="font-size: 14px;">Filters</span>

                <select name="course" id="filter_course" class="form-select filter-select" style="width: 220px;" title="Course Name">
                    <option value="">Course Name</option>
                    @foreach($courses ?? [] as $course)
                        <option value="{{ $course->pk }}" {{ (isset($courseFilter) && $courseFilter == $course->pk) ? 'selected' : '' }}>
                            {{ $course->course_name }}
                        </option>
                    @endforeach
                </select>

                <input type="date" name="date_from" id="filter_date_from" class="filter-date" value="{{ $dateFromFilter ?? '' }}" title="Time Period">

                <a href="{{ route('medical.exception.faculty.view') }}" class="btn-reset-filters">Reset Filters</a>

                <div class="ms-auto position-relative d-flex align-items-center">
                    {{-- Full search input visible on larger screens --}}
                    <div class="search-wrapper search-full">
                        <input type="text" id="tableSearchInput" class="search-input-field" placeholder="Search..." autocomplete="off">
                        <i class="material-icons search-icon-inside">search</i>
                    </div>
                    {{-- Icon-only toggle for small screens --}}
                    <button type="button" class="btn-search-icon search-toggle-btn" title="Search" id="searchToggleBtn">
                        <i class="material-icons" style="font-size: 20px;">search</i>
                    </button>
                    {{-- Expandable input for small screens --}}
                    <div class="search-collapsed-input" id="searchCollapsed">
                        <div class="search-wrapper">
                            <input type="text" id="tableSearchInputSm" class="search-input-field" placeholder="Search..." autocomplete="off">
                            <i class="material-icons search-icon-inside">search</i>
                        </div>
                    </div>
                </div>

                {{-- Hidden submit for form filter --}}
                <button type="submit" class="d-none" id="filterSubmitBtn"></button>
            </form>

            {{-- Data Table --}}
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-nowrap" role="table">
                    <thead>
                        <tr>
                            <th scope="col">S. No.</th>
                            <th scope="col">Course Name</th>
                            <th scope="col">Faculty Name</th>
                            <th scope="col">Topics</th>
                            <th scope="col">Student Name</th>
                            <th scope="col">Medical Exemption Date</th>
                            <th scope="col">OT Code</th>
                            <th scope="col">Application Type</th>
                            <th scope="col">Exemption Count</th>
                            <th scope="col">Submitted On</th>
                            <th scope="col">Medical Document</th>
                            <th scope="col">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data ?? [] as $index => $record)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $record->course_name ?? 'N/A' }}</td>
                                <td>{{ $record->faculty_name ?? 'N/A' }}</td>
                                <td>{{ $record->topics ?? 'N/A' }}</td>
                                <td>{{ $record->student_name ?? 'N/A' }}</td>
                                <td>
                                    @if($record->from_date)
                                        {{ \Carbon\Carbon::parse($record->from_date)->format('d/m/Y H:i') }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td>{{ $record->ot_code ?? 'N/A' }}</td>
                                <td>{{ $record->application_type ?? 'N/A' }}</td>
                                <td>{{ $record->exemption_count ?? 0 }}</td>
                                <td>
                                    @if($record->submitted_on)
                                        {{ \Carbon\Carbon::parse($record->submitted_on)->format('d/m/Y H:i') }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td>
                                    @if($record->medical_document)
                                        <span class="badge-submitted">Submitted</span>
                                    @else
                                        <span class="badge-not-submitted">Not Submit</span>
                                    @endif
                                </td>
                                <td>
                                    @if($record->medical_document)
                                        <a href="{{ asset('storage/' . $record->medical_document) }}" target="_blank">
                                            <i class="material-icons action-icon active">visibility</i>
                                        </a>
                                    @else
                                        <span>
                                            <i class="material-icons action-icon disabled">visibility</i>
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="text-center py-4 text-muted">No medical exception records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination / Count --}}
            @if(isset($data) && count($data) > 0)
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div></div>
                    <span class="pagination-info">Showing <strong>{{ count($data) }}</strong> of <strong>{{ count($data) }}</strong> items</span>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var searchInput = document.getElementById('tableSearchInput');
        var searchInputSm = document.getElementById('tableSearchInputSm');
        var toggleBtn = document.getElementById('searchToggleBtn');
        var collapsedWrap = document.getElementById('searchCollapsed');
        var tableBody = document.querySelector('.table tbody');

        // Toggle collapsed search on small screens
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function () {
                collapsedWrap.classList.toggle('show');
                if (collapsedWrap.classList.contains('show')) {
                    searchInputSm.focus();
                }
            });
        }

        // Close collapsed search when clicking outside
        document.addEventListener('click', function (e) {
            if (collapsedWrap && collapsedWrap.classList.contains('show')) {
                if (!collapsedWrap.contains(e.target) && e.target !== toggleBtn && !toggleBtn.contains(e.target)) {
                    collapsedWrap.classList.remove('show');
                }
            }
        });

        // Client-side table search filter
        function filterTable(query) {
            var rows = tableBody.querySelectorAll('tr');
            var lowerQuery = query.toLowerCase().trim();
            rows.forEach(function (row) {
                if (row.querySelector('td[colspan]')) return; // skip empty-state row
                var text = row.textContent.toLowerCase();
                row.style.display = lowerQuery === '' || text.indexOf(lowerQuery) > -1 ? '' : 'none';
            });
        }

        // Sync both inputs and filter
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                filterTable(this.value);
                if (searchInputSm) searchInputSm.value = this.value;
            });
        }
        if (searchInputSm) {
            searchInputSm.addEventListener('input', function () {
                filterTable(this.value);
                if (searchInput) searchInput.value = this.value;
            });
        }
    });
</script>
@endpush

@endsection