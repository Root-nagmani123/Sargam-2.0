@extends('admin.layouts.master')

@section('title', 'Feedback - Sargam | Lal Bahadur')

@section('setup_content')

<style>
    /* Feedback Table Styles */
    .feedback-table {
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }

    .feedback-table thead {
        background: linear-gradient(135deg, #004a93 0%, #0066cc 100%);
        color: white;
        font-weight: 600;
    }

    .feedback-table thead th {
        padding: 14px;
        border: none;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
    }

    .feedback-table tbody td {
        padding: 12px 14px;
        vertical-align: middle;
        border-bottom: 1px solid #e5e7eb;
    }

    .feedback-table tbody tr:hover {
        background-color: #f8f9fa;
        transition: all 0.3s ease;
    }

    .feedback-table tbody tr:last-child td {
        border-bottom: none;
    }

    /* Rating Badge Styles */
    .rating-badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 6px;
        font-weight: 600;
        font-size: 0.9rem;
    }

    .rating-excellent {
        background-color: #d4edda;
        color: #155724;
    }

    .rating-good {
        background-color: #cfe2ff;
        color: #084298;
    }

    .rating-average {
        background-color: #fff3cd;
        color: #664d03;
    }

    .rating-poor {
        background-color: #f8d7da;
        color: #842029;
    }

    /* Star Rating Styles */
    .star-rating {
        display: inline-flex;
        gap: 4px;
        font-size: 1.1rem;
    }

    .star-rating .star {
        color: #ffc107;
    }

    .star-rating .star.empty {
        color: #e0e0e0;
    }

    /* Action Buttons */
    .feedback-action-btn {
        transition: all 0.3s ease;
        padding: 8px 12px;
        border-radius: 6px;
        font-size: 0.9rem;
        min-height: 38px;
    }

    .feedback-action-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 74, 147, 0.2);
    }

    /* Feedback Header */
    .feedback-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
    }

    .feedback-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
    }

    .empty-state-icon {
        font-size: 64px;
        color: #d1d5db;
        margin-bottom: 20px;
    }

    .empty-state-text {
        color: #6b7280;
        font-size: 1.1rem;
    }

    /* Pagination Info */
    .pagination-wrapper {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 20px;
        flex-wrap: wrap;
        gap: 15px;
    }

    .pagination-info {
        color: #6b7280;
        font-size: 0.9rem;
    }

    /* Loading Animation */
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
</style>

<div class="container-fluid">
    <x-breadcrum title="Session Feedback" />
    <div>
        <!-- Feedback Card -->
        <div class="card shadow-sm" style="border-left: 4px solid #004a93;">
            <div class="card-body p-4">
                <!-- Header Section -->
                <div class="feedback-header">
                    <div>
                        <h4 class="mb-0">
                            <i class="material-icons material-symbols-rounded me-2"
                                style="vertical-align: middle;">feedback</i>
                            Session Feedback
                        </h4>
                    </div>
                    <div class="feedback-actions">
                        <button class="btn btn-outline-primary btn-sm feedback-action-btn" id="viewOlderBtn"
                            data-bs-toggle="modal" data-bs-target="#olderFeedbackModal">
                            <i class="material-icons material-symbols-rounded me-1"
                                style="font-size: 18px; vertical-align: middle;">history</i>
                            View Older Feedback
                        </button>
                        <button class="btn btn-outline-secondary btn-sm feedback-action-btn"
                            onclick="location.reload()">
                            <i class="material-icons material-symbols-rounded me-1"
                                style="font-size: 18px; vertical-align: middle;">refresh</i>
                            Refresh
                        </button>
                    </div>
                </div>
                <hr class="my-3">

                <!-- Feedback Table -->
                <div class="table-responsive">
                    @if($events && $events->count() > 0)
                    <table class="table feedback-table w-100">
                        <thead>
                            <tr>
                                <th class="col" style="width: 8%;">S.No.</th>
                                <th class="col" style="width: 18%;">Course Name</th>
                                <th class="col" style="width: 18%;">Faculty Name</th>
                                <th class="col" style="width: 18%;">Subject</th>
                                <th class="col" style="width: 20%;">Topic</th>
                                <th class="col" style="width: 18%;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($events as $key => $event)
                            <tr>
                                <td>
                                    <span class="fw-600">{{ $events->firstItem() + $key }}</span>
                                </td>
                                <td>{{ $event->course_name ?? '-' }}</td>
                                <td>{{ $event->faculty_name ?? '-' }}</td>
                                <td>{{ $event->subject_name ?? '-' }}</td>
                                <td>{{ \Illuminate\Support\Str::words($event->subject_topic, 8, '...') ?? '-' }}</td>
                                <td class="text-center">
                                    <div class="dropdown">
                                        <a href="javascript:void(0)" class="px-2 text-decoration-none"
                                            id="actionMenu{{ $event->event_id }}" data-bs-toggle="dropdown"
                                            aria-expanded="false" role="button" title="Actions">
                                            <span class="material-symbols-rounded fs-5">more_horiz</span>
                                        </a>

                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0"
                                            aria-labelledby="actionMenu{{ $event->event_id }}">
                                            <!-- View Option -->
                                            <li>
                                                <a href="javascript:void(0)"
                                                    class="dropdown-item d-flex align-items-center gap-2 view-btn"
                                                    data-event="{{ $event->event_id }}" data-bs-toggle="modal"
                                                    data-bs-target="#viewModal" title="View detailed feedback">
                                                    <i class="material-icons material-symbols-rounded fs-6">visibility</i>
                                                    <span>View Details</span>
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div class="pagination-wrapper">
                        <div class="pagination-info">
                            Showing <strong>{{ $events->firstItem() }}</strong> to
                            <strong>{{ $events->lastItem() }}</strong>
                            of <strong>{{ $events->total() }}</strong> feedback entries
                        </div>
                        <div>
                            {{ $events->links('vendor.pagination.custom') }}
                        </div>
                    </div>
                    @else
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="material-icons material-symbols-rounded">inbox</i>
                        </div>
                        <p class="empty-state-text">No feedback available at the moment.</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        <!-- end Feedback Card -->
    </div>
</div>

<!-- Feedback Details Modal -->
<div class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #004a93, #0066cc); color: white;">
                <h5 class="modal-title" id="viewModalLabel">
                    <i class="material-icons material-symbols-rounded me-2" style="vertical-align: middle;">info</i>
                    Feedback Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="table-responsive">
                    <table class="feedback-table">
                        <thead style="background: linear-gradient(135deg, #004a93, #0066cc);">
                            <tr>
                                <th>S.No.</th>
                                <th>Rating Count</th>
                                <th>Remarks</th>
                                <th style="width: 15%;">Presentation</th>
                                <th style="width: 15%;">Content</th>
                            </tr>
                        </thead>
                        <tbody id="feedbackTableBody">
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    <i class="material-icons material-symbols-rounded"
                                        style="animation: spin 1s linear infinite;">refresh</i>
                                    Loading feedback...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Older Feedback Modal -->
<div class="modal fade" id="olderFeedbackModal" tabindex="-1" aria-labelledby="olderFeedbackLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #004a93, #0066cc); color: white;">
                <h5 class="modal-title" id="olderFeedbackLabel">
                    <i class="material-icons material-symbols-rounded me-2" style="vertical-align: middle;">history</i>
                    Feedback History
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div id="olderFeedbackContent">
                    <div class="text-center text-muted py-5">
                        <i class="material-icons material-symbols-rounded"
                            style="font-size: 48px; opacity: 0.5;">history</i>
                        <p class="mt-3">Click the "View Older Feedback" button to load feedback history</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle View Feedback Button
    document.querySelectorAll('.view-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const eventId = this.getAttribute('data-event');
            const tbody = document.getElementById('feedbackTableBody');

            // Show loading state
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">
                        <i class="material-icons material-symbols-rounded" style="animation: spin 1s linear infinite;">refresh</i>
                        Loading feedback...
                    </td>
                </tr>
            `;

            // Fetch feedback data
            fetch(`/feedback/event-feedback/${eventId}`)
                .then(res => res.json())
                .then(data => {
                    if (!data || data.length === 0) {
                        tbody.innerHTML = `
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    <i class="material-icons material-symbols-rounded">inbox</i>
                                    <p class="mt-2 mb-0">No feedback found for this event</p>
                                </td>
                            </tr>
                        `;
                        return;
                    }

                    // Build table rows
                    let rows = '';
                    data.forEach((item, index) => {
                        const ratingClass = getRatingClass(item.rating);
                        const ratingBadge =
                            `<span class="rating-badge ${ratingClass}">${item.rating ?? 'N/A'}</span>`;

                        rows += `
                            <tr>
                                <td><strong>${index + 1}</strong></td>
                                <td>${ratingBadge}</td>
                                <td>
                                    <small>${item.remark ?? 'No remarks'}</small>
                                </td>
                                <td>${renderStars(item.presentation ?? 0)}</td>
                                <td>${renderStars(item.content ?? 0)}</td>
                            </tr>
                        `;
                    });

                    tbody.innerHTML = rows;
                })
                .catch(error => {
                    console.error('Error:', error);
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="5" class="text-center text-danger py-4">
                                <i class="material-icons material-symbols-rounded">error</i>
                                <p class="mt-2 mb-0">Error loading feedback. Please try again.</p>
                            </td>
                        </tr>
                    `;
                });
        });
    });

    // Handle View Older Feedback Button
    document.getElementById('viewOlderBtn')?.addEventListener('click', function() {
        const content = document.getElementById('olderFeedbackContent');

        // Show loading state
        content.innerHTML = `
            <div class="text-center text-muted py-4">
                <i class="material-icons material-symbols-rounded" style="font-size: 48px; animation: spin 1s linear infinite;">refresh</i>
                <p class="mt-2">Loading older feedback...</p>
            </div>
        `;

        // Fetch all feedback
        fetch('/feedback/all-feedback')
            .then(res => res.json())
            .then(data => {
                if (!data || data.length === 0) {
                    content.innerHTML = `
                        <div class="text-center text-muted py-4">
                            <i class="material-icons material-symbols-rounded" style="font-size: 48px;">history</i>
                            <p class="mt-2">Feedback history mapping is in progress and will be completed soon.</p>
                        </div>
                    `;
                    return;
                }

                // Group feedback by event/course
                const grouped = groupBy(data, 'event_id');
                let html = '';

                Object.entries(grouped).forEach(([eventId, feedbacks], idx) => {
                    const course = feedbacks[0]?.course_name || 'Unknown Course';
                    const faculty = feedbacks[0]?.faculty_name || 'Unknown Faculty';

                    html += `
                        <div class="card mb-3 shadow-sm">
                            <div class="card-header bg-light">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <h6 class="mb-1"><strong>${course}</strong></h6>
                                        <small class="text-muted">Faculty: ${faculty}</small>
                                    </div>
                                    <div class="col-auto">
                                        <span class="badge bg-primary">${feedbacks.length} feedback(s)</span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0">
                                        <thead>
                                            <tr>
                                                <th style="width: 8%;">S.No.</th>
                                                <th>Rating</th>
                                                <th>Remarks</th>
                                                <th>Presentation</th>
                                                <th>Content</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                    `;

                    feedbacks.forEach((item, fbIdx) => {
                        const ratingClass = getRatingClass(item.rating);
                        const ratingBadge =
                            `<span class="rating-badge ${ratingClass}">${item.rating ?? 'N/A'}</span>`;

                        html += `
                            <tr>
                                <td><strong>${fbIdx + 1}</strong></td>
                                <td>${ratingBadge}</td>
                                <td><small>${item.remark ?? 'No remarks'}</small></td>
                                <td>${renderStars(item.presentation ?? 0)}</td>
                                <td>${renderStars(item.content ?? 0)}</td>
                            </tr>
                        `;
                    });

                    html += `
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    `;
                });

                content.innerHTML = html;
            })
            .catch(error => {
                console.error('Error:', error);
                content.innerHTML = `
                    <div class="text-center text-muted py-4">
                        <i class="material-icons material-symbols-rounded" style="font-size: 48px;">history</i>
                        <p class="mt-2">Feedback history mapping is in progress and will be completed as soon as possible.</p>
                    </div>
                `;
            });
    });

    // Helper function to determine rating class
    function getRatingClass(rating) {
        if (rating >= 4) return 'rating-excellent';
        if (rating >= 3) return 'rating-good';
        if (rating >= 2) return 'rating-average';
        return 'rating-poor';
    }

    // Helper function to render stars
    function renderStars(rating) {
        let stars = '';
        for (let i = 1; i <= 5; i++) {
            stars +=
                `<span class="star ${i <= rating ? '' : 'empty'}" style="color: ${i <= rating ? '#ffc107' : '#e0e0e0'};">★</span>`;
        }
        return `<div class="star-rating">${stars}</div>`;
    }

    // Helper function to group array by key
    function groupBy(array, key) {
        return array.reduce((result, item) => {
            (result[item[key]] = result[item[key]] || []).push(item);
            return result;
        }, {});
    }
});
</script>

@endsection
