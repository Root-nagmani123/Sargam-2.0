@extends('admin.layouts.master')

@section('title', 'Inhouse Faculty - Sargam | Lal Bahadur')

@section('content')
<style>
    .inhouse-faculty-card {
        border: none;
        box-shadow: 0 0.125rem 0.5rem rgba(0, 0, 0, 0.08);
        border-radius: 0.75rem;
        overflow: visible;
    }

    .inhouse-faculty-card .card-header {
        background: #004a93;
        border-radius: 0.75rem 0.75rem 0 0;
        color: #fff;
        border: none;
        padding: 1.25rem 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .inhouse-faculty-card .card-header .card-header-icon {
        font-size: 1.5rem;
    }

    .inhouse-faculty-card .card-header span:last-child {
        font-size: 1.125rem;
        font-weight: 600;
        letter-spacing: 0.3px;
    }

    .inhouse-faculty-card .card-body .datatables,
    .inhouse-faculty-card .card-body .dataTables_wrapper {
        width: 100%;
        max-width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .inhouse-faculty-card .card-body .table-responsive {
        width: 100%;
        max-width: 100%;
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch;
    }

    #inhouse {
        margin-bottom: 0;
        min-width: 1000px;
    }

    #inhouse thead th {
        background-color: #f8f9fa;
        color: #495057;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        padding: 1rem 0.75rem;
        border-bottom: 2px solid #dee2e6;
        white-space: nowrap;
    }

    #inhouse tbody td {
        padding: 1rem 0.75rem;
        vertical-align: middle;
        border-bottom: 1px solid #f0f0f0;
    }

    #inhouse tbody tr:hover {
        background-color: #f8f9fa;
        transition: background-color 0.2s ease;
    }

    .badge-inhouse {
        font-size: 0.75rem;
        padding: 0.4rem 0.75rem;
        font-weight: 500;
    }

    .badge-sector-gov {
        background-color: #e3f2fd;
        color: #1976d2;
        font-size: 0.75rem;
        padding: 0.4rem 0.75rem;
        font-weight: 500;
    }

    .badge-sector-private {
        background-color: #fff3e0;
        color: #f57c00;
        font-size: 0.75rem;
        padding: 0.4rem 0.75rem;
        font-weight: 500;
    }

    .badge-sector-other {
        background-color: #f3e5f5;
        color: #7b1fa2;
        font-size: 0.75rem;
        padding: 0.4rem 0.75rem;
        font-weight: 500;
    }

    .faculty-name {
        font-weight: 600;
        color: #212529;
        font-size: 0.95rem;
    }

    .email-link {
        color: #0d6efd;
        text-decoration: none;
        font-size: 0.9rem;
        transition: color 0.2s ease;
    }

    .email-link:hover {
        color: #0b4f8a;
        text-decoration: underline;
    }

    .session-count-badge {
        background-color: #e7f3ff;
        color: #0d6efd;
        font-weight: 600;
        padding: 0.4rem 0.75rem;
        border-radius: 0.5rem;
        font-size: 0.85rem;
    }

    .feedback-average {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .feedback-score {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.875rem;
    }

    .feedback-label {
        font-weight: 500;
        color: #6c757d;
        min-width: 80px;
    }

    .feedback-value {
        font-weight: 600;
        padding: 0.25rem 0.5rem;
        border-radius: 0.375rem;
        font-size: 0.875rem;
    }

    .feedback-value.excellent {
        background-color: #d1e7dd;
        color: #0f5132;
    }

    .feedback-value.good {
        background-color: #d4edda;
        color: #155724;
    }

    .feedback-value.average {
        background-color: #fff3cd;
        color: #856404;
    }

    .feedback-value.poor {
        background-color: #f8d7da;
        color: #842029;
    }

    .btn-view-feedback {
        background: linear-gradient(135deg, #0d6efd 0%, #0b4f8a 100%);
        border: none;
        color: #fff;
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        font-weight: 500;
        font-size: 0.875rem;
        transition: all 0.3s ease;
        box-shadow: 0 2px 4px rgba(13, 110, 253, 0.2);
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-view-feedback:hover {
        background: linear-gradient(135deg, #0b4f8a 0%, #0a3d6d 100%);
        color: #fff;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(13, 110, 253, 0.3);
    }

    .btn-view-feedback:active {
        transform: translateY(0);
    }

    .btn-view-feedback .material-symbols-rounded {
        font-size: 1.1rem;
    }

    .no-data {
        text-align: center;
        padding: 2rem;
        color: #6c757d;
        font-style: italic;
    }

    /* Responsive - Tablet (max 991px) */
    @media (max-width: 991.98px) {
        .inhouse-faculty-card .card-body {
            padding: 1rem !important;
        }

        .table-responsive {
            overflow-x: auto !important;
            -webkit-overflow-scrolling: touch;
        }

        #inhouse {
            min-width: 1000px;
        }

        #inhouse thead th,
        #inhouse tbody td {
            padding: 0.75rem 0.6rem;
            font-size: 0.875rem;
        }

        .faculty-name {
            font-size: 0.9rem;
        }

        .email-link {
            font-size: 0.85rem;
        }

        .btn-view-feedback {
            padding: 0.4rem 0.75rem;
            font-size: 0.8rem;
        }
    }

    /* Responsive - Small tablet / large phone (max 767px) */
    @media (max-width: 767.98px) {
        .inhouse-faculty-card .card-header {
            padding: 1rem 1.25rem;
        }

        .inhouse-faculty-card .card-header .card-header-icon {
            font-size: 1.25rem;
        }

        .inhouse-faculty-card .card-header span:last-child {
            font-size: 1rem;
        }

        .inhouse-faculty-card .card-body {
            padding: 0.875rem !important;
        }

        .table-responsive {
            overflow-x: auto !important;
            -webkit-overflow-scrolling: touch;
            margin: 0 -0.875rem;
            padding: 0 0.875rem;
        }

        #inhouse {
            min-width: 1000px;
        }

        #inhouse thead th {
            padding: 0.75rem 0.5rem;
            font-size: 0.75rem;
        }

        #inhouse tbody td {
            padding: 0.75rem 0.5rem;
            font-size: 0.8rem;
        }

        .faculty-name {
            font-size: 0.875rem;
        }

        .email-link {
            font-size: 0.8rem;
        }

        .badge-inhouse,
        .badge-sector-gov,
        .badge-sector-private,
        .badge-sector-other {
            font-size: 0.7rem;
            padding: 0.3rem 0.6rem;
        }

        .session-count-badge {
            font-size: 0.75rem;
            padding: 0.3rem 0.6rem;
        }

        .feedback-score {
            font-size: 0.8rem;
            gap: 0.375rem;
        }

        .feedback-label {
            min-width: 70px;
            font-size: 0.8rem;
        }

        .feedback-value {
            font-size: 0.8rem;
            padding: 0.2rem 0.4rem;
        }

        .btn-view-feedback {
            padding: 0.375rem 0.625rem;
            font-size: 0.75rem;
        }

        .btn-view-feedback .material-symbols-rounded {
            font-size: 1rem;
        }

        /* Stack DataTables controls on mobile */
        #inhouse_wrapper .dataTables_wrapper .row:first-child,
        #inhouse_wrapper .dataTables_wrapper .dt-row:first-child {
            flex-direction: column !important;
            align-items: stretch !important;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
        }

        #inhouse_wrapper .dataTables_length,
        #inhouse_wrapper .dataTables_filter {
            text-align: left !important;
            margin-bottom: 0;
            display: block;
            width: 100%;
        }

        #inhouse_wrapper .dataTables_length label,
        #inhouse_wrapper .dataTables_filter label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0;
            flex-wrap: wrap;
            font-size: 0.875rem;
        }

        #inhouse_wrapper .dataTables_length select {
            margin: 0;
            min-width: 80px;
            max-width: 100%;
            min-height: 38px;
            padding: 0.375rem 2rem 0.375rem 0.5rem;
        }

        #inhouse_wrapper .dataTables_filter input {
            margin-left: 0 !important;
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
            min-height: 38px;
            padding: 0.375rem 0.75rem;
        }

        /* Stack DataTables info and pagination on mobile */
        #inhouse_wrapper .dataTables_wrapper .row:last-child,
        #inhouse_wrapper .dataTables_wrapper .dt-row:last-child {
            flex-direction: column !important;
            align-items: stretch !important;
            gap: 0.75rem;
            margin-top: 0.75rem;
            padding-top: 0.75rem;
            border-top: 1px solid rgba(0, 0, 0, 0.08);
        }

        #inhouse_wrapper .dataTables_info {
            text-align: center !important;
            margin-bottom: 0;
        }

        #inhouse_wrapper .dataTables_paginate {
            text-align: center !important;
            margin-top: 0;
        }
    }

    /* Responsive - Small phone (max 576px) */
    @media (max-width: 575.98px) {
        .inhouse-faculty-card .card-header {
            padding: 0.875rem 1rem;
        }

        .inhouse-faculty-card .card-body {
            padding: 0.75rem !important;
        }

        .table-responsive {
            margin: 0 -0.75rem;
            padding: 0 0.75rem;
        }

        #inhouse {
            min-width: 1000px;
        }

        #inhouse thead th {
            padding: 0.625rem 0.4rem;
            font-size: 0.7rem;
        }

        #inhouse tbody td {
            padding: 0.625rem 0.4rem;
            font-size: 0.75rem;
        }

        .btn-view-feedback {
            padding: 0.3rem 0.5rem;
            font-size: 0.7rem;
        }

        .btn-view-feedback .material-symbols-rounded {
            font-size: 0.9rem;
        }
    }
</style>

<div class="container-fluid">
    <x-breadcrum title="Inhouse Faculty"></x-breadcrum>
    <div class="card inhouse-faculty-card">
        <div class="card-header">
            <span class="material-symbols-rounded card-header-icon">badge</span>
            <span>Inhouse Faculty</span>
        </div>
        <div class="card-body">
            <div class="datatables">
                <div class="table-responsive">
                    <table class="table align-middle text-nowrap" id="inhouse">
                        <thead>
                            <tr>
                                <th scope="col">Sl. No.</th>
                                <th scope="col">Faculty Type</th>
                                <th scope="col">Faculty Name</th>
                                <th scope="col">Email</th>
                                <th scope="col">Mobile Number</th>
                                <th scope="col">Current Sector</th>
                                <th scope="col">Session Count</th>
                                <th scope="col">Feedback Average</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($inhouse_faculty as $index => $faculty)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    <span class="badge rounded-pill badge-inhouse bg-success-subtle text-success">Inhouse</span>
                                </td>
                                <td>
                                    <span class="faculty-name">{{ $faculty->full_name }}</span>
                                </td>
                                <td>
                                    @if($faculty->email_id ?? null)
                                        <a href="mailto:{{ $faculty->email_id }}" class="email-link">
                                            {{ $faculty->email_id }}
                                        </a>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>{{ $faculty->mobile_no ?? 'N/A' }}</td>
                                <td>
                                    @if($faculty->faculty_sector == 1)
                                        <span class="badge rounded-pill badge-sector-gov">Government</span>
                                    @elseif($faculty->faculty_sector == 2)
                                        <span class="badge rounded-pill badge-sector-private">Private</span>
                                    @else
                                        <span class="badge rounded-pill badge-sector-other">Other</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="session-count-badge">
                                        <span class="material-symbols-rounded" style="font-size: 1rem; vertical-align: middle;">event</span>
                                        {{ $faculty->session_count ?? 0 }}
                                    </span>
                                </td>
                                <td>
                                    @php
                                        $avgContent = $faculty->feedback_summary['avg_content'] ?? 0;
                                        $avgPresentation = $faculty->feedback_summary['avg_presentation'] ?? 0;
                                        $getScoreClass = function($score) {
                                            if ($score >= 80) return 'excellent';
                                            if ($score >= 60) return 'good';
                                            if ($score >= 40) return 'average';
                                            return 'poor';
                                        };
                                    @endphp
                                    @if(($faculty->feedback_summary['total_feedback'] ?? 0) > 0)
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
                                        <span class="text-muted">No feedback yet</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('feedback.average', ['faculty_name' => $faculty->full_name]) }}"
                                       class="btn btn-view-feedback">
                                        <span class="material-symbols-rounded">visibility</span>
                                        View Feedback
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="no-data">
                                    <span class="material-symbols-rounded" style="font-size: 3rem; display: block; margin-bottom: 0.5rem; opacity: 0.5;">person_off</span>
                                    No inhouse faculty found
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
<script src="{{ asset('js/jquery-3.7.1.min.js') }}"></script>
<script>
$(document).ready(function() {
    $('#inhouse').DataTable({
        order: [[2, 'asc']],
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
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        drawCallback: function() {
            // Add any custom styling after table draw
        }
    });
});
</script>
@endpush
