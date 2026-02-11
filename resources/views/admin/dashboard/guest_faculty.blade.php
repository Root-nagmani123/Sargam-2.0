@extends('admin.layouts.master')

@section('title', 'Guest Faculty - Sargam | Lal Bahadur')

@section('content')
<style>
    .guest-faculty-card {
        border: none;
        box-shadow: 0 0.125rem 0.5rem rgba(0, 0, 0, 0.08);
        border-radius: 0.75rem;
        overflow: hidden;
    }

    .guest-faculty-card .card-header {
        background: linear-gradient(135deg, #0b4f8a 0%, #0d6efd 100%);
        color: #fff;
        border: none;
        padding: 1.25rem 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .guest-faculty-card .card-header .card-header-icon {
        font-size: 1.5rem;
    }

    .guest-faculty-card .card-header span:last-child {
        font-size: 1.125rem;
        font-weight: 600;
        letter-spacing: 0.3px;
    }

    #guess_faculty {
        margin-bottom: 0;
    }

    #guess_faculty thead th {
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

    #guess_faculty tbody td {
        padding: 1rem 0.75rem;
        vertical-align: middle;
        border-bottom: 1px solid #f0f0f0;
    }

    #guess_faculty tbody tr:hover {
        background-color: #f8f9fa;
        transition: background-color 0.2s ease;
    }

    .badge-guest {
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

    @media (max-width: 768px) {
        .table-responsive {
            overflow-x: auto;
        }

        #guess_faculty {
            min-width: 1000px;
        }
    }
</style>

<div class="container-fluid">
    <x-breadcrum title="Guest Faculty"></x-breadcrum>
    <div class="card guest-faculty-card">
        <div class="card-header">
            <span class="material-symbols-rounded card-header-icon">badge</span>
            <span>Guest Faculty</span>
        </div>
        <div class="card-body p-0">
            <div class="datatables">
                <div class="table-responsive">
                    <table class="table align-middle" id="guess_faculty">
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
                            @forelse($guest_faculty as $index => $faculty)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    <span class="badge rounded-pill badge-guest bg-success-subtle text-success">Guest</span>
                                </td>
                                <td>
                                    <span class="faculty-name">{{ $faculty->full_name }}</span>
                                </td>
                                <td>
                                    @if($faculty->email_id)
                                        <a href="mailto:{{ $faculty->email_id }}" class="email-link">
                                            <span class="material-symbols-rounded" style="font-size: 1rem; vertical-align: middle;">mail</span>
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
                                        $overallAvg = ($avgContent + $avgPresentation) / 2;
                                        
                                        $getScoreClass = function($score) {
                                            if ($score >= 80) return 'excellent';
                                            if ($score >= 60) return 'good';
                                            if ($score >= 40) return 'average';
                                            return 'poor';
                                        };
                                    @endphp
                                    @if($faculty->feedback_summary['total_feedback'] > 0)
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
<script src="{{ asset('js/jquery-3.7.1.min.js') }}"></script>
<script>
$(document).ready(function() {
    $('#guess_faculty').DataTable({
        order: [[2, 'asc']], // Sort by Faculty Name by default
        pageLength: 25,
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
        responsive: true,
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        drawCallback: function() {
            // Add any custom styling after table draw
        }
    });
});
</script>
@endpush
    