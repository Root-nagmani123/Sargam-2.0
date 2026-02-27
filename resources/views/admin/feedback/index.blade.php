@extends('admin.layouts.master')

@section('title', 'Feedback - Sargam | Lal Bahadur')

@section('setup_content')
<style>
    :root {
        --lbsnaa-blue: #004a93;
        --lbsnaa-blue-light: #e3f2fd;
        --lbsnaa-orange: #ff6b35;
        --lbsnaa-orange-light: #fff1eb;
    }

    /* Enhanced Card Styles */
    .feedback-card {
        border: none;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .feedback-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 30px rgba(0, 74, 147, 0.15);
    }

    /* Tab Navigation */
    .feedback-tabs {
        border-bottom: 2px solid #e5e7eb;
        margin-bottom: 2rem;
    }

    .feedback-tabs .nav-link {
        padding: 0.875rem 1.75rem;
        font-weight: 600;
        font-size: 0.95rem;
        color: #6b7280;
        border: none;
        border-radius: 12px 12px 0 0;
        background: transparent;
        margin-right: 0.5rem;
        transition: all 0.3s ease;
        position: relative;
    }

    .feedback-tabs .nav-link:hover {
        color: var(--lbsnaa-blue);
        background-color: rgba(0, 74, 147, 0.05);
    }

    .feedback-tabs .nav-link.active {
        color: var(--lbsnaa-blue);
        background-color: white;
        border-bottom: 3px solid var(--lbsnaa-blue);
        box-shadow: 0 -2px 10px rgba(0, 74, 147, 0.1);
    }

    .feedback-tabs .nav-link .badge {
        font-size: 0.7rem;
        padding: 0.25rem 0.5rem;
        margin-left: 0.5rem;
    }

    /* Filter Section */
    .filter-section {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        border: 1px solid #e2e8f0;
    }

    .filter-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1.25rem;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .filter-title {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        color: #1f2937;
        font-weight: 600;
        font-size: 1.1rem;
    }

    .filter-actions {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .filter-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.25rem;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .filter-label {
        font-size: 0.875rem;
        font-weight: 600;
        color: #4b5563;
        display: flex;
        align-items: center;
        gap: 0.375rem;
    }

    .form-select-enhanced,
    .form-input-enhanced {
        border: 1.5px solid #d1d5db;
        border-radius: 10px;
        padding: 0.75rem 1rem;
        font-size: 0.95rem;
        transition: all 0.3s ease;
        background-color: white;
    }

    .form-select-enhanced:focus,
    .form-input-enhanced:focus {
        border-color: var(--lbsnaa-blue);
        box-shadow: 0 0 0 3px rgba(0, 74, 147, 0.15);
        outline: none;
    }

    .form-select-enhanced:disabled,
    .form-input-enhanced:disabled {
        background-color: #f9fafb;
        cursor: not-allowed;
    }

    /* Enhanced Table Styles */
    .feedback-table {
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid #e5e7eb;
        background: white;
    }

    .feedback-table thead {
        background: linear-gradient(135deg, var(--lbsnaa-blue) 0%, var(--lbsnaa-blue-dark) 100%);
        color: white;
    }

    .feedback-table thead th {
        padding: 1rem 1.25rem;
        border: none;
        font-weight: 600;
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        position: relative;
    }

    .feedback-table thead th::after {
        content: '';
        position: absolute;
        right: 0;
        top: 25%;
        height: 50%;
        width: 1px;
        background: rgba(255, 255, 255, 0.2);
    }

    .feedback-table thead th:last-child::after {
        display: none;
    }

    .feedback-table tbody td {
        padding: 1rem 1.25rem;
        vertical-align: middle;
        border-bottom: 1px solid #f3f4f6;
        transition: background-color 0.2s ease;
    }

    .feedback-table tbody tr {
        transition: all 0.2s ease;
    }

    .feedback-table tbody tr:hover {
        background-color: #f8fafc;
        transform: scale(1.002);
    }

    /* Rating Badge Enhancements */
    .rating-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        padding: 0.5rem 0.875rem;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.85rem;
        line-height: 1;
    }

    .rating-excellent {
        background: linear-gradient(135deg, #d4edda, #c3e6cb);
        color: #155724;
        border: 1px solid rgba(21, 87, 36, 0.1);
    }

    .rating-good {
        background: linear-gradient(135deg, #cfe2ff, #b6d4fe);
        color: #084298;
        border: 1px solid rgba(8, 66, 152, 0.1);
    }

    .rating-average {
        background: linear-gradient(135deg, #fff3cd, #ffe69c);
        color: #664d03;
        border: 1px solid rgba(102, 77, 3, 0.1);
    }

    .rating-poor {
        background: linear-gradient(135deg, #f8d7da, #f5c6cb);
        color: #842029;
        border: 1px solid rgba(132, 32, 41, 0.1);
    }

    /* Star Rating Enhancements */
    .star-rating-display {
        display: inline-flex;
        gap: 0.125rem;
        font-size: 1.25rem;
    }

    .star-rating-display .star {
        color: #ffc107;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    }

    .star-rating-display .star.empty {
        color: #e5e7eb;
    }

    /* Action Buttons Enhancement */
    .action-menu-trigger {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #6b7280;
        transition: all 0.2s ease;
        background: transparent;
        border: 1px solid transparent;
    }

    .action-menu-trigger:hover,
    .action-menu-trigger:focus {
        background: #f3f4f6;
        border-color: #d1d5db;
        color: var(--lbsnaa-blue);
        outline: none;
    }

    .action-dropdown {
        min-width: 200px;
        border-radius: 10px;
        border: 1px solid #e5e7eb;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        padding: 0.5rem;
    }

    .action-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 1rem;
        border-radius: 8px;
        color: #4b5563;
        text-decoration: none;
        transition: all 0.2s ease;
        cursor: pointer;
    }

    .action-item:hover {
        background-color: #f3f4f6;
        color: var(--lbsnaa-blue);
        transform: translateX(4px);
    }

    .action-item .material-icons {
        font-size: 1.125rem;
        width: 24px;
    }

    /* Empty State Enhancement */
    .empty-state-enhanced {
        text-align: center;
        padding: 4rem 2rem;
        background: white;
        border-radius: 12px;
        border: 2px dashed #e5e7eb;
    }

    .empty-state-icon {
        font-size: 4rem;
        color: #d1d5db;
        margin-bottom: 1.5rem;
        opacity: 0.5;
    }

    .empty-state-text {
        color: #6b7280;
        font-size: 1.125rem;
        margin-bottom: 0;
        max-width: 400px;
        margin: 0 auto;
    }

    /* Pagination Enhancement */
    .pagination-enhanced {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 2rem;
        padding: 1.25rem;
        background: #f9fafb;
        border-radius: 12px;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .pagination-info-enhanced {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        color: #6b7280;
        font-size: 0.9rem;
    }

    .pagination-info-enhanced .badge {
        background: var(--lbsnaa-blue);
        color: white;
        padding: 0.375rem 0.75rem;
        border-radius: 6px;
        font-weight: 500;
    }

    /* Modal Enhancements */
    .feedback-modal .modal-header {
        background: linear-gradient(135deg, var(--lbsnaa-blue) 0%, var(--lbsnaa-blue-dark) 100%);
        color: white;
        padding: 1.5rem 2rem;
        border-bottom: none;
        border-radius: 12px 12px 0 0;
    }

    .feedback-modal .modal-title {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-weight: 600;
    }

    .feedback-modal .modal-content {
        border: none;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
    }

    /* Status Indicators */
    .status-indicator {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        padding: 0.375rem 0.75rem;
        border-radius: 6px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .status-active {
        background: rgba(34, 197, 94, 0.1);
        color: #16a34a;
        border: 1px solid rgba(34, 197, 94, 0.2);
    }

    .status-archived {
        background: rgba(107, 114, 128, 0.1);
        color: #6b7280;
        border: 1px solid rgba(107, 114, 128, 0.2);
    }

    /* Accessibility */
    .sr-only {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border: 0;
    }

    /* Focus Styles */
    *:focus {
        outline: 2px solid var(--lbsnaa-blue);
        outline-offset: 2px;
    }

    *:focus:not(.focus-visible) {
        outline: none;
    }

    /* Responsive Design - Only affects screens below desktop, desktop view unchanged */
    @media (max-width: 991px) {
        .container-fluid {
            padding-left: 1rem;
            padding-right: 1rem;
        }

        .feedback-card .card-body {
            padding: 1rem !important;
        }

        .table-responsive {
            -webkit-overflow-scrolling: touch;
            overflow-x: auto;
        }

        .feedback-table {
            min-width: 800px;
            font-size: 0.9rem;
        }

        .feedback-table thead th,
        .feedback-table tbody td {
            padding: 0.75rem 0.5rem;
            white-space: nowrap;
        }

        .feedback-table .text-truncate {
            max-width: 120px !important;
        }

        .filter-row {
            grid-template-columns: repeat(2, 1fr);
        }

        #advancedFilters .row .col-md-3 {
            flex: 0 0 50%;
            max-width: 50%;
        }
    }

    @media (max-width: 768px) {
        .container-fluid {
            padding-left: 0.75rem;
            padding-right: 0.75rem;
        }

        .feedback-tabs {
            margin-bottom: 1.5rem;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            padding-bottom: 0.5rem;
        }

        .feedback-tabs .nav {
            flex-wrap: nowrap;
            padding-bottom: 0.25rem;
        }

        .feedback-tabs .nav-link {
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            white-space: nowrap;
        }

        .feedback-tabs .nav-link .material-icons {
            display: inline-block;
        }

        .filter-section {
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .filter-row {
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        .filter-header {
            flex-direction: column;
            align-items: stretch;
            margin-bottom: 1rem;
        }

        .filter-actions {
            justify-content: stretch;
            flex-direction: column;
        }

        .filter-actions .btn {
            flex: 1;
            min-width: 0;
            width: 100%;
        }

        .pagination-enhanced {
            flex-direction: column;
            text-align: center;
            gap: 0.75rem;
            padding: 1rem;
            margin-top: 1.5rem;
        }

        .pagination-info-enhanced {
            flex-wrap: wrap;
            justify-content: center;
            font-size: 0.85rem;
        }

        .feedback-modal .modal-dialog {
            margin: 0.5rem;
            max-width: calc(100% - 1rem);
        }

        .feedback-modal .modal-body {
            padding: 1rem !important;
        }

        .feedback-modal .modal-header {
            padding: 1rem 1.25rem;
        }

        .feedback-modal .feedback-table {
            min-width: 600px;
        }

        .empty-state-enhanced {
            padding: 3rem 1.5rem;
        }

        .empty-state-icon {
            font-size: 3rem;
        }

        .action-dropdown {
            min-width: 180px;
        }

        .rating-badge {
            padding: 0.375rem 0.625rem;
            font-size: 0.8rem;
        }

        .status-indicator {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }

        .status-indicator .material-icons {
            font-size: 14px !important;
        }

        .star-rating-display {
            font-size: 1rem;
        }
    }

    @media (max-width: 576px) {
        .container-fluid {
            padding-left: 0.5rem;
            padding-right: 0.5rem;
        }

        .feedback-tabs .nav-link {
            padding: 0.625rem 0.75rem;
            font-size: 0.8125rem;
        }

        .feedback-tabs .nav-link .badge {
            font-size: 0.65rem;
            padding: 0.2rem 0.4rem;
        }

        .filter-section {
            padding: 0.75rem;
        }

        .filter-title {
            font-size: 1rem;
        }

        .feedback-card .card-body {
            padding: 0.75rem !important;
        }

        .feedback-table {
            font-size: 0.8125rem;
            min-width: 700px;
        }

        .feedback-table thead th,
        .feedback-table tbody td {
            padding: 0.5rem 0.375rem;
        }

        .empty-state-enhanced {
            padding: 2rem 1rem;
        }

        .empty-state-icon {
            font-size: 2.5rem;
        }

        .empty-state-text {
            font-size: 0.95rem;
        }

        .action-menu-trigger {
            width: 32px;
            height: 32px;
        }

        .action-menu-trigger .material-icons {
            font-size: 18px !important;
        }

        .modal-footer .btn {
            flex: 1;
            min-width: 0;
        }

        #advancedFilters .row .col-md-3 {
            flex: 0 0 100%;
            max-width: 100%;
        }
    }

    @media (max-width: 400px) {
        .feedback-tabs .nav-link .material-icons {
            display: none;
        }

        .filter-title .material-icons {
            display: none;
        }
    }

    /* Card-based table layout for small screens - main list only, not modal */
    @media (max-width: 576px) {
        .tab-content .table-responsive {
            overflow-x: visible;
        }

        .tab-content .feedback-table {
            min-width: 100% !important;
            display: block;
        }

        .tab-content .feedback-table thead {
            display: none;
        }

        .tab-content .feedback-table tbody {
            display: block;
        }

        .tab-content .feedback-table tbody tr {
            display: block;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            margin-bottom: 1rem;
            padding: 1rem;
            background: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            transition: none;
        }

        .tab-content .feedback-table tbody tr:hover {
            transform: none;
        }

        .tab-content .feedback-table tbody td {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.625rem 0;
            border-bottom: 1px solid #f3f4f6;
            white-space: normal;
        }

        .tab-content .feedback-table tbody td:last-child {
            border-bottom: none;
            justify-content: flex-end;
            padding-top: 0.75rem;
            margin-top: 0.25rem;
            border-top: 1px solid #e5e7eb;
        }

        .tab-content .feedback-table tbody td::before {
            font-weight: 600;
            color: #4b5563;
            font-size: 0.8125rem;
            flex-shrink: 0;
        }

        .tab-content .feedback-table tbody td:nth-child(1)::before { content: "S.No."; }
        .tab-content .feedback-table tbody td:nth-child(2)::before { content: "Course"; }
        .tab-content .feedback-table tbody td:nth-child(3)::before { content: "Faculty"; }
        .tab-content .feedback-table tbody td:nth-child(4)::before { content: "Subject"; }
        .tab-content .feedback-table tbody td:nth-child(5)::before { content: "Topic"; }
        .tab-content .feedback-table tbody td:nth-child(6)::before { content: "Rating"; }
        .tab-content .feedback-table tbody td:nth-child(7)::before { content: "Status"; }
        .tab-content .feedback-table tbody td:nth-child(8)::before { content: ""; }

        .tab-content .feedback-table tbody td:nth-child(2) .d-flex,
        .tab-content .feedback-table tbody td:nth-child(4) .text-truncate,
        .tab-content .feedback-table tbody td:nth-child(5) .text-truncate {
            max-width: none !important;
        }

        .tab-content .feedback-table tbody td .text-truncate {
            white-space: normal;
            text-overflow: clip;
        }

        .tab-content .dropdown .dropdown-menu {
            right: 0;
            left: auto;
        }
    }

    /* Tablet: horizontal scroll hint - subtle shadow on right edge */
    @media (min-width: 577px) and (max-width: 991px) {
        .tab-content .table-responsive {
            box-shadow: inset -12px 0 12px -12px rgba(0, 0, 0, 0.06);
            border-radius: 8px;
        }
    }

    /* Disable hover transform on touch devices - prevents sticky hover state */
    @media (hover: none) {
        .feedback-card:hover {
            transform: none;
        }

        .feedback-table tbody tr:hover {
            transform: none;
        }

        .action-item:hover {
            transform: none;
        }
    }

    /* Safe area support for notched devices (iPhone X+) */
    @supports (padding: max(0px)) {
        @media (max-width: 768px) {
            .container-fluid {
                padding-left: max(0.75rem, env(safe-area-inset-left));
                padding-right: max(0.75rem, env(safe-area-inset-right));
            }
        }

        @media (max-width: 576px) {
            .container-fluid {
                padding-left: max(0.5rem, env(safe-area-inset-left));
                padding-right: max(0.5rem, env(safe-area-inset-right));
            }

            .feedback-modal .modal-dialog {
                margin-left: max(0.5rem, env(safe-area-inset-left));
                margin-right: max(0.5rem, env(safe-area-inset-right));
            }
        }
    }

    /* Minimum touch target size (44x44px) for interactive elements */
    @media (max-width: 768px) {
        .action-menu-trigger {
            min-width: 44px;
            min-height: 44px;
        }

        .form-select-enhanced,
        .form-input-enhanced {
            min-height: 44px;
        }

        .filter-actions .btn {
            min-height: 44px;
            padding: 0.625rem 1rem;
        }

        .feedback-tabs .nav-link {
            min-height: 44px;
            padding: 0.75rem 1rem;
        }
    }

    /* Filter actions: 2-column grid on tablet for better space use */
    @media (min-width: 577px) and (max-width: 768px) {
        .filter-actions {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.5rem;
        }

        .filter-actions .btn {
            width: auto;
        }
    }

    /* Modal: nearly fullscreen on very small screens */
    @media (max-width: 480px) {
        .feedback-modal .modal-dialog {
            margin: 0.25rem;
            max-width: calc(100% - 0.5rem);
            max-height: calc(100vh - 0.5rem);
        }

        .feedback-modal .modal-content {
            max-height: calc(100vh - 0.5rem);
        }

        .feedback-modal .modal-body {
            max-height: calc(100vh - 180px);
            overflow-y: auto;
        }

        .feedback-modal .table-responsive {
            -webkit-overflow-scrolling: touch;
        }

        .feedback-modal .feedback-table {
            min-width: 500px;
        }
    }

    /* Collapsible advanced filters - touch-friendly toggle */
    @media (max-width: 768px) {
        [data-bs-toggle="collapse"][data-bs-target="#advancedFilters"] {
            min-height: 44px;
            padding: 0.5rem 0;
            display: flex;
            align-items: center;
        }
    }

    /* Pagination: touch-friendly spacing and wrap */
    @media (max-width: 768px) {
        .pagination-enhanced .pagination {
            flex-wrap: wrap;
            justify-content: center;
            gap: 0.25rem;
        }

        .pagination-enhanced .page-link {
            min-width: 40px;
            min-height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem;
        }
    }

    /* Loading States */
    .loading-skeleton {
        animation: pulse 1.5s ease-in-out infinite;
    }

    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }

    /* Print Styles */
    @media print {
        .filter-section,
        .feedback-tabs,
        .action-menu-trigger,
        .btn {
            display: none !important;
        }

        .feedback-table {
            box-shadow: none;
            border: 1px solid #dee2e6;
        }
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
            </div>
        </form>
    </section>

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

                        <!-- Pagination -->
                        <div class="pagination-enhanced">
                            <div class="pagination-info-enhanced">
                                Showing <strong>{{ $activeEvents->firstItem() }}</strong> to 
                                <strong>{{ $activeEvents->lastItem() }}</strong> of 
                                <strong>{{ $activeEvents->total() }}</strong> active feedback entries
                                <span class="badge">{{ $activeEvents->total() }}</span>
                            </div>
                            <div>
                                {{ $activeEvents->links('vendor.pagination.custom') }}
                            </div>
                        </div>
                    @else
                        <div class="empty-state-enhanced" role="status" aria-live="polite">
                            <div class="empty-state-icon">
                                <i class="material-icons" aria-hidden="true">feedback</i>
                            </div>
                            <p class="empty-state-text">No active feedback available at the moment.</p>
                            <button class="btn btn-primary mt-3" onclick="location.reload()" aria-label="Refresh page">
                                <i class="material-icons me-2" aria-hidden="true">refresh</i>
                                Refresh
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Archive Tab -->
        <div class="tab-pane fade" id="archive-content" role="tabpanel" aria-labelledby="archive-tab">
            <div class="feedback-card">
                <div class="card-body p-4">
                    @if($archivedEvents && $archivedEvents->count() > 0)
                        <div class="table-responsive">
                            <table class="table feedback-table" aria-label="Archived feedback entries">
                                <thead>
                                    <tr>
                                        <th scope="col" style="width: 8%;">S.No.</th>
                                        <th scope="col" style="width: 20%;">Course Name</th>
                                        <th scope="col" style="width: 18%;">Faculty</th>
                                        <th scope="col" style="width: 16%;">Subject</th>
                                        <th scope="col" style="width: 18%;">Topic</th>
                                        <th scope="col" style="width: 10%;" class="text-center">Avg. Rating</th>
                                        <th scope="col" style="width: 10%;" class="text-center">Status</th>
                                        <th scope="col" style="width: 10%;" class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($archivedEvents as $key => $event)
                                    <tr>
                                        <td>
                                            <span class="fw-semibold text-secondary">{{ $archivedEvents->firstItem() + $key }}</span>
                                        </td>
                                        <td>{{ $event->course_name ?? '-' }}</td>
                                        <td>{{ $event->faculty_name ?? '-' }}</td>
                                        <td>{{ $event->subject_name ?? '-' }}</td>
                                        <td>{{ \Illuminate\Support\Str::words($event->subject_topic, 5, '...') ?? '-' }}</td>
                                        <td class="text-center">
                                            @if($event->average_rating)
                                                <div class="star-rating-display">
                                                    @for($i = 1; $i <= 5; $i++)
                                                        <span class="star {{ $i <= $event->average_rating ? '' : 'empty' }}">
                                                            ★
                                                        </span>
                                                    @endfor
                                                </div>
                                                <small class="text-muted d-block mt-1">{{ number_format($event->average_rating, 1) }}/5</small>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="status-indicator status-archived">
                                                <i class="material-icons" aria-hidden="true">archive</i>
                                                Archived
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <button class="action-menu-trigger" 
                                                        id="archiveActionMenu{{ $event->event_id }}" 
                                                        data-bs-toggle="dropdown" 
                                                        aria-expanded="false"
                                                        aria-label="Actions for archived feedback">
                                                    <i class="material-icons" aria-hidden="true">more_vert</i>
                                                </button>
                                                
                                                <ul class="dropdown-menu action-dropdown shadow-lg" 
                                                    aria-labelledby="archiveActionMenu{{ $event->event_id }}">
                                                    <li>
                                                        <a href="javascript:void(0)" 
                                                           class="action-item view-feedback"
                                                           data-event="{{ $event->event_id }}"
                                                           data-bs-toggle="modal" 
                                                           data-bs-target="#viewModal"
                                                           aria-label="View archived feedback details">
                                                            <i class="material-icons" aria-hidden="true">visibility</i>
                                                            <span>View Details</span>
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a href="javascript:void(0)" 
                                                           class="action-item restore-feedback"
                                                           data-event="{{ $event->event_id }}"
                                                           aria-label="Restore this feedback to active">
                                                            <i class="material-icons" aria-hidden="true">restore</i>
                                                            <span>Restore</span>
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a href="javascript:void(0)" 
                                                           class="action-item text-danger delete-feedback"
                                                           data-event="{{ $event->event_id }}"
                                                           aria-label="Permanently delete this feedback">
                                                            <i class="material-icons" aria-hidden="true">delete</i>
                                                            <span>Delete Permanently</span>
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="pagination-enhanced">
                            <div class="pagination-info-enhanced">
                                Showing <strong>{{ $archivedEvents->firstItem() }}</strong> to 
                                <strong>{{ $archivedEvents->lastItem() }}</strong> of 
                                <strong>{{ $archivedEvents->total() }}</strong> archived feedback entries
                                <span class="badge bg-secondary">{{ $archivedEvents->total() }}</span>
                            </div>
                            <div>
                                {{ $archivedEvents->links('vendor.pagination.custom') }}
                            </div>
                        </div>
                    @else
                        <div class="empty-state-enhanced" role="status" aria-live="polite">
                            <div class="empty-state-icon">
                                <i class="material-icons" aria-hidden="true">archive</i>
                            </div>
                            <p class="empty-state-text">No archived feedback available.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Feedback Details Modal -->
    <div class="modal fade feedback-modal" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewModalLabel">
                        <i class="material-icons me-2" aria-hidden="true">info</i>
                        Feedback Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="table-responsive">
                        <table class="feedback-table">
                            <thead>
                                <tr>
                                    <th>S.No.</th>
                                    <th>Rating</th>
                                    <th>Remarks</th>
                                    <th style="width: 15%;">Presentation</th>
                                    <th style="width: 15%;">Content</th>
                                    <th style="width: 15%;">Submitted On</th>
                                </tr>
                            </thead>
                            <tbody id="feedbackTableBody">
                                <!-- Dynamic content will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="exportDetailsBtn">
                        <i class="material-icons me-2" aria-hidden="true">download</i>
                        Export Details
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize date range picker
    const dateRangePicker = new DateRangePicker(document.getElementById('dateRange'), {
        format: 'yyyy-mm-dd',
        autoclose: true
    });

    // Handle filter application
    document.getElementById('applyFiltersBtn').addEventListener('click', function() {
        applyFilters();
    });

    // Handle filter reset
    document.getElementById('resetFiltersBtn').addEventListener('click', function() {
        resetFilters();
    });

    // Handle export
    document.getElementById('exportBtn').addEventListener('click', function() {
        exportFeedback();
    });

    // Handle tab switching
    const feedbackTab = document.getElementById('feedbackTab');
    feedbackTab.addEventListener('shown.bs.tab', function(event) {
        const activeTab = event.target.getAttribute('aria-controls');
        updateFilterState(activeTab);
    });

    // View feedback details
    document.querySelectorAll('.view-feedback').forEach(btn => {
        btn.addEventListener('click', function() {
            const eventId = this.getAttribute('data-event');
            loadFeedbackDetails(eventId);
        });
    });

    // Archive feedback
    document.querySelectorAll('.archive-feedback').forEach(btn => {
        btn.addEventListener('click', function() {
            const eventId = this.getAttribute('data-event');
            archiveFeedback(eventId);
        });
    });

    // Restore feedback
    document.querySelectorAll('.restore-feedback').forEach(btn => {
        btn.addEventListener('click', function() {
            const eventId = this.getAttribute('data-event');
            restoreFeedback(eventId);
        });
    });

    // Delete feedback
    document.querySelectorAll('.delete-feedback').forEach(btn => {
        btn.addEventListener('click', function() {
            const eventId = this.getAttribute('data-event');
            deleteFeedback(eventId);
        });
    });

    // Export feedback details
    document.getElementById('exportDetailsBtn').addEventListener('click', function() {
        exportFeedbackDetails();
    });

    // Functions
    function applyFilters() {
        const formData = new FormData(document.getElementById('feedbackFilterForm'));
        const activeTab = document.querySelector('#feedbackTab .nav-link.active').getAttribute('aria-controls');
        
        // Show loading state
        showLoading(activeTab);
        
        // AJAX request to filter data
        fetch(`/feedback/filter?tab=${activeTab}&${new URLSearchParams(formData)}`)
            .then(res => res.json())
            .then(data => {
                updateTable(data, activeTab);
                updateCounts(data.counts);
            })
            .catch(error => {
                console.error('Error:', error);
                showError(activeTab, 'Failed to apply filters');
            });
    }

    function resetFilters() {
        document.getElementById('feedbackFilterForm').reset();
        document.getElementById('dateRange').value = '';
        applyFilters();
    }

    function exportFeedback() {
        const formData = new FormData(document.getElementById('feedbackFilterForm'));
        const activeTab = document.querySelector('#feedbackTab .nav-link.active').getAttribute('aria-controls');
        
        // Create export URL
        const exportUrl = `/feedback/export?tab=${activeTab}&${new URLSearchParams(formData)}`;
        window.open(exportUrl, '_blank');
    }

    function updateFilterState(tab) {
        // Store/restore filter state based on active tab
        const filters = JSON.parse(localStorage.getItem(`feedbackFilters_${tab}`) || '{}');
        Object.keys(filters).forEach(key => {
            const element = document.querySelector(`[name="${key}"]`);
            if (element) element.value = filters[key];
        });
    }

    function loadFeedbackDetails(eventId) {
        const tbody = document.getElementById('feedbackTableBody');
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading feedback details...</p>
                </td>
            </tr>
        `;

        fetch(`/feedback/event-feedback/${eventId}`)
            .then(res => res.json())
            .then(data => {
                if (!data || data.length === 0) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <i class="material-icons text-muted" style="font-size: 48px;">inbox</i>
                                <p class="mt-2 mb-0 text-muted">No feedback found for this event</p>
                            </td>
                        </tr>
                    `;
                    return;
                }

                let rows = '';
                data.forEach((item, index) => {
                    const ratingClass = getRatingClass(item.rating);
                    rows += `
                        <tr>
                            <td><strong>${index + 1}</strong></td>
                            <td>
                                <span class="rating-badge ${ratingClass}">
                                    ${item.rating ?? 'N/A'}
                                </span>
                            </td>
                            <td>
                                <div class="text-truncate" style="max-width: 300px;" title="${item.remark || 'No remarks'}">
                                    ${item.remark || 'No remarks'}
                                </div>
                            </td>
                            <td>${renderStars(item.presentation ?? 0)}</td>
                            <td>${renderStars(item.content ?? 0)}</td>
                            <td>${formatDate(item.created_at)}</td>
                        </tr>
                    `;
                });

                tbody.innerHTML = rows;
            })
            .catch(error => {
                console.error('Error:', error);
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            <i class="material-icons text-danger" style="font-size: 48px;">error</i>
                            <p class="mt-2 mb-0 text-danger">Error loading feedback details</p>
                        </td>
                    </tr>
                `;
            });
    }

    function archiveFeedback(eventId) {
        if (confirm('Are you sure you want to archive this feedback?')) {
            fetch(`/feedback/archive/${eventId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to archive feedback');
            });
        }
    }

    function restoreFeedback(eventId) {
        if (confirm('Are you sure you want to restore this feedback?')) {
            fetch(`/feedback/restore/${eventId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to restore feedback');
            });
        }
    }

    function deleteFeedback(eventId) {
        if (confirm('Are you sure you want to permanently delete this feedback? This action cannot be undone.')) {
            fetch(`/feedback/delete/${eventId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to delete feedback');
            });
        }
    }

    function exportFeedbackDetails() {
        // Implementation for exporting detailed feedback
        window.print();
    }

    // Helper functions
    function getRatingClass(rating) {
        if (rating >= 4.5) return 'rating-excellent';
        if (rating >= 3.5) return 'rating-good';
        if (rating >= 2.5) return 'rating-average';
        return 'rating-poor';
    }

    function renderStars(count) {
        let stars = '';
        for (let i = 1; i <= 5; i++) {
            stars += `<span class="star ${i <= count ? '' : 'empty'}">★</span>`;
        }
        return `<div class="star-rating-display">${stars}</div>`;
    }

    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        return date.toLocaleDateString('en-IN', {
            day: '2-digit',
            month: 'short',
            year: 'numeric'
        });
    }

    function showLoading(tab) {
        const tableBody = document.querySelector(`#${tab}-content tbody`);
        if (tableBody) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Applying filters...</p>
                    </td>
                </tr>
            `;
        }
    }

    function showError(tab, message) {
        const tableBody = document.querySelector(`#${tab}-content tbody`);
        if (tableBody) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-5 text-danger">
                        <i class="material-icons" style="font-size: 48px;">error</i>
                        <p class="mt-2">${message}</p>
                        <button class="btn btn-outline-danger mt-2" onclick="location.reload()">
                            Try Again
                        </button>
                    </td>
                </tr>
            `;
        }
    }

    // Save filter state when leaving page
    window.addEventListener('beforeunload', function() {
        const activeTab = document.querySelector('#feedbackTab .nav-link.active').getAttribute('aria-controls');
        const formData = new FormData(document.getElementById('feedbackFilterForm'));
        const filters = Object.fromEntries(formData);
        localStorage.setItem(`feedbackFilters_${activeTab}`, JSON.stringify(filters));
    });

    // Initialize filters from localStorage
    const activeTab = document.querySelector('#feedbackTab .nav-link.active').getAttribute('aria-controls');
    updateFilterState(activeTab);
});
</script>
@endsection