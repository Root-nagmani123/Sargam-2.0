@extends('admin.layouts.master')

@section('title', 'Guest Faculty')

@section('content')
<link rel="stylesheet" href="{{ asset('css/guest_faculty.css') }}">

<div class="container-fluid px-2 px-md-3">
    <x-breadcrum title="Guest Faculty"></x-breadcrum>
    <div class="card guest-faculty-card border-0 shadow-sm rounded-1">
        <div class="card-body p-3 p-md-4">
            <div class="datatables">
                <div class="table-responsive">
                    <table class="table table-hover align-middle text-nowrap mb-0" id="guess_faculty">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">S. No.</th>
                                <th scope="col">Faculty Type</th>
                                <th scope="col">Faculty Name</th>
                                <th scope="col">Email</th>
                                <th scope="col">Mobile Number</th>
                                <th scope="col">Current Sector</th>
                                <th scope="col">Session Count</th>
                                <th scope="col">Feedback Average</th>
                                @if((hasRole('Admin')))
                                <th scope="col">Action</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($guest_faculty as $index => $faculty)
                            <tr>
                                <td class="text-body-secondary fw-medium">{{ $index + 1 }}</td>
                                <td>
                                    <span class="badge rounded-1 badge-guest bg-success-subtle text-success border border-success-subtle">Guest</span>
                                </td>
                                <td>
                                    <span class="faculty-name">{{ $faculty->full_name }}</span>
                                </td>
                                <td>
                                    @if($faculty->email_id)
                                        <a href="mailto:{{ $faculty->email_id }}" class="email-link">
                                            <span class="material-symbols-rounded align-text-bottom me-1" style="font-size: 1rem;">mail</span>
                                            {{ $faculty->email_id }}
                                        </a>
                                    @else
                                        <span class="text-muted small">N/A</span>
                                    @endif
                                </td>
                                <td class="fw-medium">{{ $faculty->mobile_no ?? 'N/A' }}</td>
                                <td>
                                    @if($faculty->faculty_sector == 1)
                                        <span class="badge rounded-1 badge-sector-gov border border-primary-subtle">Government</span>
                                    @elseif($faculty->faculty_sector == 2)
                                        <span class="badge rounded-1 badge-sector-private border border-warning-subtle">Private</span>
                                    @else
                                        <span class="badge rounded-1 badge-sector-other border border-secondary-subtle">Other</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="session-count-badge d-inline-flex align-items-center gap-1">
                                        <span class="material-symbols-rounded align-text-bottom" style="font-size: 1rem;">event</span>
                                        {{ $faculty->session_count ?? 0 }}
                                    </span>
                                </td>
                                <td>
                                    @php
                                        $avgContent = data_get($faculty, 'feedback_summary.avg_content', 0);
                                        $avgPresentation = data_get($faculty, 'feedback_summary.avg_presentation', 0);
                                        $totalFeedback = (int) data_get($faculty, 'feedback_summary.total_feedback', 0);
                                        $overallAvg = ($avgContent + $avgPresentation) / 2;
                                        
                                        $getScoreClass = function($score) {
                                            if ($score >= 80) return 'excellent';
                                            if ($score >= 60) return 'good';
                                            if ($score >= 40) return 'average';
                                            return 'poor';
                                        };
                                    @endphp
                                    @if($totalFeedback > 0)
                                        <div class="feedback-average">
                                            <div class="feedback-score">
                                                <span class="feedback-label">Content:</span>
                                                <span class="feedback-value {{ $getScoreClass($avgContent) }}">
                                                    {{ number_format($avgContent, 1) }}%
                                                </span>
                                            </div>
                                            <div class="feedback-score">
                                                <span class="feedback-label">Presentation:</span>
                                                <span class="feedback-value {{ $getScoreClass($avgPresentation) }}">
                                                    {{ number_format($avgPresentation, 1) }}%
                                                </span>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-muted small">No feedback yet</span>
                                    @endif
                                </td>
                                @if((hasRole('Admin')))
                                <td>
                                    <a href="{{ route('feedback.average', ['faculty_name' => $faculty->full_name]) }}" 
                                       class="btn btn-view-feedback btn-sm">
                                        <span class="material-symbols-rounded">visibility</span>
                                        View Feedback
                                    </a>
                                </td>
                                @endIf
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="no-data text-center py-5 text-body-secondary fst-italic">
                                    <span class="material-symbols-rounded fs-1 d-block mb-2 opacity-50">person_off</span>
                                    No guest faculty found
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    const table = $('#guess_faculty').DataTable({
        order: [[0, 'asc']], // Sort by Faculty Name by default
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        language: {
            search: "Search:",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            infoEmpty: "Showing 0 to 0 of 0 entries",
            infoFiltered: "(filtered from _MAX_ total entries)",
            paginate: {
                first: "First",
                last: "Last",
                next: "Next",
                previous: "Previous"
            }
        },
        responsive: false,
        autoWidth: false,
        dom: '<"row g-2 align-items-center mb-2"<"col-12 col-md-6"l><"col-12 col-md-6"f>>rt<"row g-2 align-items-center mt-2"<"col-12 col-md-5"i><"col-12 col-md-7"p>>',
        initComplete: function() {
            const wrapper = $('#guess_faculty_wrapper');
            wrapper.find('.dataTables_length select').addClass('form-select form-select-sm');
            wrapper.find('.dataTables_filter input').addClass('form-control ').attr('placeholder', 'Search faculty...');
            wrapper.find('.dataTables_info').addClass('small text-muted');
            wrapper.find('.dataTables_paginate').addClass('small');
            
            // Initialize column visibility dropdown
            initializeColumnToggle(table);
        },
        drawCallback: function() {
            // Add any custom styling after table draw
        }
    });
    
    // Function to initialize column toggle dropdown
    function initializeColumnToggle(table) {
        const columns = table.settings()[0].aoColumns;
        const dropdown = $('#columnToggleDropdown');
        
        // Clear existing items
        dropdown.empty();
        
        // Create checkbox for each column
        columns.forEach((col, index) => {
            const columnTitle = col.sTitle || `Column ${index + 1}`;
            const isVisible = col.bVisible !== false;
            
            const item = $(`
                <div class="toggle-item">
                    <input type="checkbox" id="col-${index}" class="column-checkbox" data-column="${index}" ${isVisible ? 'checked' : ''}>
                    <label for="col-${index}">${columnTitle}</label>
                </div>
            `);
            
            dropdown.append(item);
        });
        
        // Handle checkbox changes
        $(document).on('change', '.column-checkbox', function() {
            const columnIndex = $(this).data('column');
            const isChecked = $(this).is(':checked');
            table.column(columnIndex).visible(isChecked);
        });
    }
    
    // Toggle dropdown visibility
    $('#columnToggleBtn').on('click', function(e) {
        e.stopPropagation();
        $('#columnToggleDropdown').toggleClass('show');
    });
    
    // Close dropdown when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#columnToggleBtn, #columnToggleDropdown').length) {
            $('#columnToggleDropdown').removeClass('show');
        }
    });
});
</script>
@endpush
    
