@extends('admin.layouts.master')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">

@section('title', 'Memo Management - Sargam | Lal Bahadur Shastri National Academy of Administration')

@section('setup_content')
<style>
/* GIGW Color Palette - Enhanced */
:root {
    --gigw-primary: #004a93;
    --gigw-primary-dark: #003366;
    --gigw-secondary: #0066cc;
    --gigw-light-bg: #f8f9fa;
    --gigw-border: #dee2e6;
    --gigw-text-muted: #6c757d;
    --gigw-success: #198754;
    --gigw-white: #ffffff;
    --gigw-card-shadow: 0 1px 3px rgba(0, 74, 147, 0.08);
    --gigw-card-shadow-hover: 0 4px 12px rgba(0, 74, 147, 0.12);
    --gigw-radius: 0.75rem;
    --gigw-radius-sm: 0.5rem;
}

.blink {
    animation: blinker 1s linear infinite;
}

@keyframes blinker {
    50% { opacity: 0; }
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

/* Chat loading overlay – blur only during load */
.chat-body.is-loading {
    position: relative;
    min-height: 320px;
}
.chat-body.is-loading .chat-loading-overlay {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(248, 249, 250, 0.85);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    z-index: 10;
    border-radius: 0.75rem;
}
.chat-body:not(.is-loading) .chat-loading-overlay {
    display: none;
}
.chat-body .chat-loading-content {
    text-align: center;
    padding: 1.5rem;
}
.chat-body .chat-loading-content .spinner-border {
    width: 2.5rem;
    height: 2.5rem;
    border-width: 0.2em;
}
.chat-body .chat-loading-content .loading-text {
    margin-top: 0.75rem;
    font-size: 0.9375rem;
    color: var(--gigw-text-muted);
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

/* Page responsive helpers (mobile-only; preserve desktop) */
@media (max-width: 575.98px) {
    .card-title {
        font-size: 1.05rem;
    }

    /* Prevent sticky status cell overlapping during horizontal scroll */
    .table .sticky-status {
        position: static;
        right: auto;
        box-shadow: none;
    }

    /* Let action buttons wrap naturally */
    .d-flex.justify-content-end.align-items-center.gap-2 {
        flex-wrap: wrap;
        justify-content: flex-start !important;
    }
}

@media (min-width: 576px) and (max-width: 767.98px) {
    /* On small tablets, also avoid sticky overlap */
    .table .sticky-status {
        position: static;
        right: auto;
        box-shadow: none;
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

/* Modern Card */
.memo-management-card {
    border: none;
    border-radius: var(--gigw-radius);
    box-shadow: var(--gigw-card-shadow);
    transition: box-shadow 0.25s ease;
    overflow: hidden;
}
.memo-management-card .card-body { padding: 1.5rem 1.75rem; }
@media (min-width: 768px) {
    .memo-management-card:hover { box-shadow: var(--gigw-card-shadow-hover); }
}

/* Filter Section */
.filter-section {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-radius: var(--gigw-radius-sm);
    padding: 1.25rem;
    margin-bottom: 1.5rem;
    border: 1px solid rgba(0, 74, 147, 0.08);
}
.filter-section .form-label {
    font-weight: 600;
    font-size: 0.8125rem;
    color: #475569;
    margin-bottom: 0.375rem;
}
.filter-section .form-control,
.filter-section .form-select {
    border-radius: var(--gigw-radius-sm);
    border-color: #e2e8f0;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.filter-section .form-control:focus,
.filter-section .form-select:focus {
    border-color: var(--gigw-primary);
    box-shadow: 0 0 0 3px rgba(0, 74, 147, 0.15);
}
.filter-section .input-group {
    border-radius: var(--gigw-radius-sm);
}
.filter-section .input-group:focus-within .input-group-text,
.filter-section .input-group:focus-within .form-control {
    border-color: var(--gigw-primary);
}
.filter-section .input-group:focus-within {
    box-shadow: 0 0 0 3px rgba(0, 74, 147, 0.15);
}
.filter-section .input-group .input-group-text {
    border-radius: var(--gigw-radius-sm) 0 0 var(--gigw-radius-sm);
    border-color: #e2e8f0;
    background: #fff;
}
.filter-section .input-group .form-control {
    border-radius: 0 var(--gigw-radius-sm) var(--gigw-radius-sm) 0;
    border-left: none;
}
.filter-section .input-group .form-control:focus {
    box-shadow: none;
}

/* Modern Table */
.memo-table {
    --bs-table-hover-bg: rgba(0, 74, 147, 0.04);
    --bs-table-hover-color: inherit;
}
.memo-table thead th {
    font-weight: 600;
    font-size: 0.8125rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #475569;
    background: #f8fafc;
    border-bottom: 2px solid #e2e8f0;
    padding: 1rem 0.75rem;
    white-space: nowrap;
}
.memo-table tbody td {
    padding: 0.875rem 0.75rem;
    vertical-align: middle;
}
.memo-table tbody tr {
    transition: background-color 0.15s ease;
}
.table-responsive {
    transition: opacity 0.2s ease;
}

/* Action Buttons */
.btn-action-sm {
    padding: 0.35rem 0.65rem;
    font-size: 0.8125rem;
    border-radius: var(--gigw-radius-sm);
    transition: all 0.2s ease;
}
.btn-action-sm:hover { transform: translateY(-1px); }

/* Empty State */
.empty-state {
    padding: 3rem 2rem;
    text-align: center;
}
.empty-state .bi-inbox {
    font-size: 3rem;
    color: #cbd5e1;
    margin-bottom: 1rem;
}
.empty-state .text-muted { font-size: 0.9375rem; }

/* Pagination */
.pagination-wrapper {
    background: #f8fafc;
    border-radius: var(--gigw-radius-sm);
    padding: 0.875rem 1rem;
    border: 1px solid #e2e8f0;
}

/* Modal Form */
#memo_generate .modal-body .form-label {
    font-weight: 600;
    font-size: 0.8125rem;
    color: #475569;
}
#memo_generate .form-control:focus,
#memo_generate .form-select:focus {
    border-color: var(--gigw-primary);
    box-shadow: 0 0 0 3px rgba(0, 74, 147, 0.15);
}
#memo_generate .form-control[readonly] {
    background-color: #f1f5f9;
}
/* Choices.js dropdown above modal overlay */
#memo_generate .choices__list--dropdown { z-index: 1060; }
</style>
<div class="container-fluid px-2 px-sm-3 px-lg-4 py-2 py-md-3">
    <x-breadcrum title="Notice /Memo Management" />
    <x-session_message />

    <!-- Main Card -->
    <div class="card memo-management-card border-start border-4 border-primary">
        <div class="card-body">
            <div class="row align-items-center g-3 mb-4">
                <div class="col-12 col-md-6">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 bg-primary bg-opacity-10 p-2">
                            <i class="bi bi-file-earmark-text text-primary" style="font-size: 1.75rem;"></i>
                        </div>
                        <div>
                            <h4 class="card-title mb-0 fw-semibold">Notice / Memo Management</h4>
                            <p class="text-muted small mb-0 mt-1">Manage notices and memos for course attendance</p>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="d-flex justify-content-md-end align-items-center gap-2 flex-wrap">

                        <a href="{{ route('memo.notice.management.export_pdf', request()->query()) }}"
                            class="btn btn-danger d-inline-flex align-items-center gap-2" target="_blank">
                            <i class="bi bi-file-earmark-pdf"></i>
                            <span>Export PDF</span>
                        </a>

                        <a href="{{ route('memo.notice.management.create') }}"
                            class="btn btn-primary d-inline-flex align-items-center gap-2">
                            <i class="bi bi-plus-lg"></i>
                            <span>Add Notice</span>
                        </a>
                    </div>
                </div>
            </div>
            <form method="GET" action="{{ route('memo.notice.management.index') }}" id="filterForm">
            <div class="filter-section rounded-3 border border-primary border-opacity-25 shadow-sm">
                <div class="row g-3 g-md-4">
                    <div class="col-12 col-sm-6 col-lg-3">
                        <label for="program_name" class="form-label fw-semibold text-body-secondary d-flex align-items-center gap-1">
                            <i class="bi bi-mortarboard-fill text-primary opacity-75"></i>
                            <span>Program Name</span>
                        </label>
                        <select class="form-select rounded-2 border-primary border-opacity-25" id="program_name" name="program_name">
                            <option value="">Select Program</option>
                            @foreach($courses as $course)
                            <option value="{{ $course->pk }}" {{ (string)$programNameFilter == (string)$course->pk ? 'selected' : '' }}>{{ $course->course_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <label for="type" class="form-label fw-semibold text-body-secondary d-flex align-items-center gap-1">
                            <i class="bi bi-file-earmark-text text-primary opacity-75"></i>
                            <span>Type</span>
                        </label>
                        <select class="form-select rounded-2 border-primary border-opacity-25" id="type" name="type">
                            <option value="">Select type</option>
                            <option value="1" {{ $typeFilter == '1' ? 'selected' : '' }}>Notice</option>
                            <option value="0" {{ $typeFilter == '0' ? 'selected' : '' }}>Memo</option>
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <label for="status" class="form-label fw-semibold text-body-secondary d-flex align-items-center gap-1">
                            <i class="bi bi-toggle-on text-primary opacity-75"></i>
                            <span>Status</span>
                        </label>
                        <select class="form-select rounded-2 border-primary border-opacity-25" id="status" name="status">
                            <option value="">Select status</option>
                            <option value="1" {{ $statusFilter == '1' ? 'selected' : '' }}>Open</option>
                            <option value="0" {{ $statusFilter == '0' ? 'selected' : '' }}>Close</option>
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <label for="search" class="form-label fw-semibold text-body-secondary d-flex align-items-center gap-1">
                            <i class="bi bi-search text-primary opacity-75"></i>
                            <span>Search</span>
                        </label>
                        <div class="input-group rounded-2 border border-primary border-opacity-25 overflow-hidden">
                            <span class="input-group-text bg-white border-end-0 border-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" class="form-control border-start-0 ps-0 focus-ring focus-ring-primary" id="search" name="search" placeholder="Search notices, memos..." value="{{ $searchFilter }}">
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <label for="from_date" class="form-label fw-semibold text-body-secondary d-flex align-items-center gap-1">
                            <i class="bi bi-calendar-event text-primary opacity-75"></i>
                            <span>From Date</span>
                        </label>
                        <input type="date" class="form-control rounded-2 border-primary border-opacity-25" id="from_date" name="from_date" value="{{ $fromDateFilter ?: \Carbon\Carbon::today()->toDateString() }}">
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <label for="to_date" class="form-label fw-semibold text-body-secondary d-flex align-items-center gap-1">
                            <i class="bi bi-calendar-event text-primary opacity-75"></i>
                            <span>To Date</span>
                        </label>
                        <input type="date" class="form-control rounded-2 border-primary border-opacity-25" id="to_date" name="to_date" value="{{ $toDateFilter ?: \Carbon\Carbon::today()->toDateString() }}">
                    </div>
                    <div class="col-12 col-sm-6 col-lg-6 d-flex align-items-end gap-2 flex-wrap">
                        <button type="submit" form="filterForm" class="btn btn-primary rounded-2 d-inline-flex align-items-center gap-2" id="applyFilters">
                            <i class="bi bi-funnel"></i>
                            <span>Apply Filters</span>
                        </button>
                        <button type="button" id="clearFilters" class="btn btn-outline-secondary rounded-2 d-inline-flex align-items-center gap-2">
                            <i class="bi bi-x-circle"></i>
                            <span>Clear Filters</span>
                        </button>
                    </div>
                </div>
            </div>
            </form>
            <div class="position-relative">
                <div id="memoTableLoadingOverlay" class="position-absolute top-0 start-0 end-0 bottom-0 bg-white bg-opacity-75 d-flex align-items-center justify-content-center rounded-3" style="display: none; z-index: 10;">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
                        <p class="text-body-secondary small mt-2 mb-0">Loading table...</p>
                    </div>
                </div>
                <div id="memoTableWrapper" class="table-responsive rounded-3 border border-primary border-opacity-25 shadow-sm overflow-hidden">
                <table class="table memo-table text-nowrap mb-0">
                    <thead class="table-light border-bottom border-2 border-primary border-opacity-25">
                        <tr>
                            <th class="py-3 fw-semibold text-body">S.No.</th>
                            <th class="py-3 fw-semibold text-body">Program Name</th>
                            <th class="py-3 fw-semibold text-body">Participant Name</th>
                            <th class="py-3 fw-semibold text-body">Type</th>
                            <th class="py-3 fw-semibold text-body">Session Date</th>
                            <th class="py-3 fw-semibold text-body">Topic</th>
                            <th class="py-3 fw-semibold text-body">Conversation</th>
                            <th class="py-3 fw-semibold text-body">Response</th>
                            <th class="py-3 fw-semibold text-body">Conclusion Type</th>
                            <th class="py-3 fw-semibold text-body">Discussion Name</th>
                            <th class="py-3 fw-semibold text-body">Conclusion Remark</th>
                            <th class="py-3 fw-semibold text-body">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($memos->isEmpty())
                        <tr>
                            <td colspan="12" class="empty-state py-5">
                                <span class="d-inline-flex rounded-circle bg-primary bg-opacity-10 p-3 mb-3">
                                    <i class="bi bi-inbox text-primary fs-2"></i>
                                </span>
                                <p class="text-body-secondary fw-semibold mb-1">No records found</p>
                                <span class="text-muted small">Try adjusting your filters.</span>
                            </td>
                        </tr>
                        @else
                        @foreach ($memos as $index => $memo)
                        <tr>
                            <!-- Serial -->
                            <td class="sno">{{ $memos->firstItem() + $index }}</td>

                            <!-- Program Name -->
                            <td class="fw-medium">{{ $memo->course_name ?? 'N/A' }}</td>

                            <!-- Student -->
                            <td class="s_name fw-medium">{{ $memo->student_name }}</td>

                            <!-- Type -->
                            <td class="type">
                                @if ($memo->notice_memo == '1')
                                <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">
                                    <i class="bi bi-file-earmark-text me-1"></i> Notice
                                </span>
                                @elseif ($memo->notice_memo == '2')
                                <span class="badge rounded-pill bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25">
                                    <i class="bi bi-file-earmark me-1"></i> Memo
                                </span>
                                @else
                                <span class="badge rounded-pill bg-info bg-opacity-10 text-info border border-info border-opacity-25">
                                    <i class="bi bi-question-circle me-1"></i> Other
                                </span>
                                @endif
                            </td>

                            <!-- Session Date -->
                            <td class="1">
                                @if(isset($memo->session_date) && $memo->session_date)
                                    {{ date('d-m-Y', strtotime($memo->session_date)) }}
                                @else
                                    {{ date('d-m-Y', strtotime($memo->date_)) }}
                                @endif
                            </td>

                            <!-- Topic -->
                            <td>{{ $memo->topic_name }}</td>
@php
$noticeKey = $memo->student_pk . '_' . $memo->course_master_pk;
@endphp
                            <!-- Conversations -->
                            <td class="conversation">
                                <div class="d-flex align-items-center gap-2 flex-nowrap">
                                    @if($memo->type_notice_memo == 'Notice' || $memo->type_notice_memo == 'Memo')
                                    @if($memo->notice_id)
                                    <a href="{{ route('memo.notice.management.conversation', ['id' => $memo->notice_id, 'type' => 'notice']) }}"
                                        class="btn btn-sm btn-outline-primary btn-action-sm d-inline-flex align-items-center">
                                        <i class="bi bi-chat-dots me-1"></i> Notice
                                    </a>
                                    @else
                                    <span class="text-muted small d-flex align-items-center">
                                        <i class="bi bi-chat-slash me-1"></i> No Conversation
                                    </span>
                                    @endif
                                    @endif
                                    @if(isset($noticeCount[$noticeKey]) && ($noticeCount[$noticeKey] >= 2) && $memo->type_notice_memo != 'Memo')
                                            <span class="position-relative d-inline-block ms-2">
                                                <!-- Bell Icon -->
                                                <i class="bi bi-bell-fill text-warning blink" 
                                                title="{{ $noticeCount[$noticeKey] }} notices sent, please send memo"></i>

                                                <!-- Count Badge -->
                                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                                    {{ $noticeCount[$noticeKey] }}
                                                </span>
                                            </span>
                                        @endif

                                    @php 
                                    $role = session()->get('role_name');
                                    @endphp

                                    <!-- Admin Offcanvas -->
                                     @if($memo->type_notice_memo == 'Notice')
                                    <a
                                        class="text-primary d-flex align-items-center view-conversation"
                                        data-bs-toggle="offcanvas" data-bs-target="#chatOffcanvas" data-type="notice" 
                                        data-id="{{ $memo->notice_id }}" data-topic="{{ $memo->topic_name }}">
                                        <i class="material-icons material-symbols-rounded">chat</i> {{ $role }}
                                    </a>
                                    @elseif($memo->type_notice_memo == 'Memo')
                                    <a
                                        class="text-primary d-flex align-items-center view-conversation"
                                        data-bs-toggle="offcanvas" data-bs-target="#chatOffcanvas" data-type="memo"
                                        data-id="{{ $memo->memo_id }}" data-topic="{{ $memo->topic_name }}">
                                        <i class="material-icons material-symbols-rounded">chat</i> {{ $role }}
                                    </a>
                                    @else
                                    <span class="text-muted small d-flex align-items-center">
                                        <i class="bi bi-chat-slash me-1"></i> No Conversation
                                    </span>
                                    @endif

                                    @if($memo->type_notice_memo == 'Notice')
                                    <button class="btn btn-sm btn-outline-secondary" disabled>
                                        Memo
                                    </button>
                                    @elseif($memo->type_notice_memo == 'Memo' &&
                                    in_array($memo->communication_status,[1,2]))
                                    <a href="{{ route('memo.notice.management.conversation', ['id' => $memo->memo_id, 'type' => 'memo']) }}"
                                        class="btn btn-sm btn-outline-primary btn-action-sm d-inline-flex align-items-center">
                                        <i class="bi bi-chat-square-text me-1"></i> Memo
                                    </a>
                                    @endif
                                </div>
                            </td>


                            <!-- Response (Generate Memo) -->
                            <td class="response">
                                @if($memo->type_notice_memo == 'Notice')
                                @if($memo->status == 1)
                                <button type="button" class="btn btn-sm btn-secondary btn-action-sm" disabled data-bs-toggle="tooltip"
                                    data-bs-placement="top" title="Memo generation not available yet">
                                    <i class="bi bi-file-earmark-lock me-1"></i> Generate Memo
                                </button>
                                @elseif($memo->status == 2)
                                <a href="javascript:void(0)" class="btn btn-sm btn-success btn-action-sm generate-memo-btn"
                                    data-id="{{ $memo->memo_notice_id }}" data-bs-toggle="modal"
                                    data-bs-target="#memo_generate">
                                    <i class="bi bi-file-earmark-plus me-1"></i> Generate Memo
                                </a>
                                @endif
                                @endif
                            </td>

                            <!-- Conclusion -->
                            <td class="conclusion_type">
                                @if($memo->type_notice_memo == 'Memo')
                                <a href="javascript:void(0)" class="btn btn-sm btn-outline-secondary btn-action-sm preview-memo-btn"
                                    data-notice-id="{{ $memo->notice_id }}" data-memo-id="{{ $memo->memo_id }}" data-bs-toggle="modal"
                                    data-bs-target="#memo_generate">
                                    <i class="bi bi-file-check me-1"></i> Memo Generated
                                </a>
                                @endif
                            </td>

                            <!-- Discussion Name -->
                            <td class="discussion_name">
                                @if($memo->type_notice_memo == 'Memo' && $memo->communication_status == 2)
                                {{ $memo->discussion_name }}
                                @endif
                            </td>

                            <!-- Conclusion Remark -->
                            <td>
                                @if($memo->type_notice_memo == 'Memo' && $memo->communication_status == 2)
                                {{ $memo->conclusion_remark }}
                                @endif
                            </td>

                            <!-- Status -->
                            <td class="status sticky-status">
                                @if ($memo->status == 1)
                                <span class="badge rounded-pill bg-success bg-opacity-10 text-success border border-success border-opacity-25">
                                    <i class="bi bi-check-circle me-1"></i> Open
                                </span>
                                @else
                                <span class="badge rounded-pill bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">
                                    <i class="bi bi-x-circle me-1"></i> Close
                                </span>
                                @endif
                            </td>

                        </tr>
                        @endforeach
                        @endif
                    </tbody>

                </table>

                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 pagination-wrapper mt-3 rounded-2">
                    <div class="text-muted small">
                        Showing <strong>{{ $memos->firstItem() ?? 0 }}</strong>
                        to <strong>{{ $memos->lastItem() }}</strong>
                        of <strong>{{ $memos->total() }}</strong> items
                    </div>
                    <div>
                        {{ $memos->links('vendor.pagination.custom') }}
                    </div>
                </div>
                </div>
            </div>

        </div>
    </div>
    <!-- end Zero Configuration -->
   
    <!-- Enhanced Offcanvas with GIGW Guidelines -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="chatOffcanvas" aria-labelledby="conversationTopic" role="dialog">
        <div class="offcanvas-header">
            <div class="d-flex flex-column w-100">
                <h4 class="offcanvas-title mb-2" id="conversationTopic">
                    <i class="material-symbols-rounded me-2" style="vertical-align: middle; font-size: 24px;">forum</i>
                    Conversation
                </h4>
                <h5 id="type_side_menu">Loading...</h5>
            </div>
            <button type="button" 
                    class="btn-close" 
                    data-bs-dismiss="offcanvas" 
                    aria-label="Close conversation panel"
                    title="Close">
            </button>
        </div>
        <input type="hidden" id="userType" value="" aria-hidden="true">

        <div class="offcanvas-body d-flex flex-column">
            <!-- Chat Body with Enhanced Styling -->
            <div class="chat-body flex-grow-1 is-loading" id="chatBody" role="log" aria-live="polite" aria-label="Conversation messages">
                <div class="chat-loading-overlay">
                    <div class="chat-loading-content">
                        <div class="spinner-border text-primary" role="status" aria-hidden="false">
                            <span class="visually-hidden">Loading conversation...</span>
                        </div>
                        <p class="loading-text mb-0">Loading conversation...</p>
                    </div>
                </div>
                <div class="chat-body-inner" style="display: none;"></div>
            </div>
        </div>
    </div>
    <!-- Memo Generation Modal -->
    <div class="modal fade" id="memo_generate" tabindex="-1" aria-labelledby="memo_generateLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-3 overflow-hidden">
                <div class="modal-header bg-primary bg-gradient text-white border-0 py-3 px-4">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-file-earmark-plus fs-5"></i>
                        <h5 class="modal-title mb-0 fw-semibold" id="memo_generateLabel">Generate Memo</h5>
                    </div>
                    <button type="button" class="btn-close btn-close-white opacity-100" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body bg-light px-4 py-4">
                    <form action="{{ route('memo.notice.management.store_memo_status') }}" method="POST">
                        @csrf
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="course_master_name" class="form-label fw-semibold text-body-secondary">Course</label>
                                <input type="text" id="course_master_name" class="form-control rounded-2"
                                    name="course_master_name" readonly>
                                <input type="hidden" id="course_master_pk" name="course_master_pk">
                                <input type="hidden" id="student_notice_status_pk" name="student_notice_status_pk">
                                <input type="hidden" id="memo_count" name="memo_count">
                                <input type="hidden" id="student_pk" name="student_pk">
                                @error('course_master_name')
                                <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="date_memo_notice" class="form-label fw-semibold text-body-secondary">Date</label>
                                <input type="date" class="form-control rounded-2" id="date_memo_notice" name="date_memo_notice"
                                    required readonly>
                                @error('date_memo_notice')
                                <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="subject_master_id" class="form-label fw-semibold text-body-secondary">Subject <span class="text-danger">*</span></label>
                                <input type="text" id="subject_master_id" class="form-control rounded-2" name="subject_master_id" readonly>
                                @error('subject_master_id')
                                <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="topic_id" class="form-label fw-semibold text-body-secondary">Topic</label>
                                <input type="text" id="topic_id" class="form-control rounded-2" name="topic_id" readonly>
                                @error('topic_id')
                                <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="session_name" class="form-label fw-semibold text-body-secondary">Session</label>
                                <input type="text" id="class_session_master_pk" class="form-control rounded-2" readonly>
                                @error('session_name')
                                <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="faculty_name" class="form-label fw-semibold text-body-secondary">Faculty Name</label>
                                <input type="text" id="faculty_name" class="form-control rounded-2" readonly>
                                @error('faculty_name')
                                <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="student_name" class="form-label fw-semibold text-body-secondary">Student Name</label>
                                <input type="text" id="student_name" class="form-control rounded-2" readonly>
                                @error('student_name')
                                <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-12 col-md-6">
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="memo_type_master_pk" class="form-label fw-semibold text-body-secondary">Memo Type</label>
                                <select name="memo_type_master_pk" id="memo_type_master_pk" class="form-select rounded-2">
                                    <option value="">Select Memo Type</option>
                                    @foreach ($memo_master as $master)
                                    <option value="{{ $master->pk }}">{{ $master->memo_type_name }}</option>
                                    @endforeach
                                </select>
                                @error('memo_type_master_pk')
                                <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="memo_number" class="form-label fw-semibold text-body-secondary">Memo Number</label>
                                <input type="text" id="memo_number" name="memo_number" class="form-control rounded-2" readonly>
                                @error('memo_number')
                                <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="venue" class="form-label fw-semibold text-body-secondary">Venue</label>
                                <select name="venue" id="venue" class="form-select rounded-2">
                                    <option value="">Select Venue</option>
                                    @foreach ($venue as $v)
                                    <option value="{{ $v->venue_id }}">{{ $v->venue_name }}</option>
                                    @endforeach
                                </select>
                                @error('venue')
                                <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-12 col-md-3">
                                <label for="memo_date" class="form-label fw-semibold text-body-secondary">Date</label>
                                <input type="date" id="memo_date" class="form-control rounded-2">
                                @error('memo_date')
                                <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-12 col-md-3">
                                <label for="meeting_time" class="form-label fw-semibold text-body-secondary">Meeting Time</label>
                                <input type="time" id="meeting_time" name="meeting_time" class="form-control rounded-2">
                                @error('meeting_time')
                                <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-12">
                                <label for="textarea" class="form-label fw-semibold text-body-secondary">Message (If Any)</label>
                                <textarea class="form-control rounded-2" id="textarea" name="Remark" rows="3" placeholder="Enter remarks..."></textarea>
                                @error('Remark')
                                <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                </div>
                <div class="modal-footer border-top border-primary border-opacity-25 bg-white py-3 px-4 gap-2">
                    <button type="button" class="btn btn-outline-secondary rounded-2" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary rounded-2">
                        <i class="bi bi-check-lg me-1"></i> Save
                    </button>
                </div>
                </form>
            </div>
        </div>

    </div>
    <!-- Memo generation end -->
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script>
$(document).ready(function() {
    // Choices.js for filter selects (searchable Program, Type, Status)
    if (typeof Choices !== 'undefined') {
        window.memoFilterChoices = {};
        var filterSelectIds = ['program_name', 'type', 'status'];
        filterSelectIds.forEach(function(id) {
            var el = document.getElementById(id);
            if (el && !el.dataset.choicesInitialized) {
                window.memoFilterChoices[id] = new Choices(el, {
                    searchEnabled: id === 'program_name',
                    searchPlaceholderValue: 'Search...',
                    itemSelectText: '',
                    shouldSort: false,
                    placeholder: true,
                    placeholderValue: el.options[0] ? el.options[0].text : 'Select'
                });
                el.dataset.choicesInitialized = 'true';
            }
        });
    }

    $(document).on('click', '.view-conversation', function() {
        let memoId = $(this).data('id');
        let topic = $(this).data('topic');
        let type = $(this).data('type');
        $('#userType').val(type);
        let user_type = 'admin';

        $('#conversationTopic').text("Topic: " + topic);
        $('#type_side_menu').text(type);

        var $chatBody = $('#chatBody');
        $chatBody.addClass('is-loading');
        $chatBody.find('.chat-body-inner').hide().empty();
        $chatBody.find('.chat-loading-overlay').show();

        $.ajax({
            url: '/admin/memo-notice-management/get_conversation_model/' + memoId + '/' + type + '/' + user_type,
            type: 'GET',
            success: function(res) {
                $chatBody.removeClass('is-loading');
                $chatBody.find('.chat-loading-overlay').hide();
                $chatBody.find('.chat-body-inner').html(res).show();
            },
            error: function() {
                $chatBody.removeClass('is-loading');
                $chatBody.find('.chat-loading-overlay').hide();
                $chatBody.find('.chat-body-inner')
                    .html('<p class="text-danger text-center py-4">Failed to load conversation.</p>')
                    .show();
            },
            complete: function() {
                $chatBody.removeClass('is-loading');
            }
        });

        // Show offcanvas
        let chatOffcanvas = new bootstrap.Offcanvas(document.getElementById('chatOffcanvas'));
        chatOffcanvas.show();
    });
});
</script>
@push('scripts')
<script>
$(document).ready(function() {
    var indexUrl = $('#filterForm').attr('action') || '{{ route("memo.notice.management.index") }}';

    // Load table content via AJAX (no full page reload)
    function loadTableContent(url) {
        var $overlay = $('#memoTableLoadingOverlay');
        var $wrapper = $('#memoTableWrapper');
        $overlay.show();
        $.get(url)
            .done(function(html) {
                var wrapperEl = $wrapper[0];
                if (!wrapperEl) { return; }
                try {
                    var parser = new DOMParser();
                    var doc = parser.parseFromString(html, 'text/html');
                    var newWrap = doc.getElementById('memoTableWrapper');
                    if (newWrap) {
                        wrapperEl.innerHTML = newWrap.innerHTML;
                    }
                    if (typeof history !== 'undefined' && history.pushState) {
                        history.pushState({ memoTable: true }, '', url);
                    }
                } catch (err) {
                    console.error('Table parse error:', err);
                }
            })
            .fail(function() {
                alert('Failed to load table. Please try again.');
            })
            .always(function() {
                $overlay.hide();
            });
    }

    // Intercept form submit: load table via AJAX instead of full reload
    $('#filterForm').on('submit', function(e) {
        e.preventDefault();
        var params = $(this).serialize();
        var url = params ? (indexUrl + (indexUrl.indexOf('?') >= 0 ? '&' : '?') + params) : indexUrl;
        loadTableContent(url);
    });

    // Filter change still triggers form submit (which is now AJAX)
    $('#program_name, #type, #status, #from_date, #to_date').on('change', function() {
        $('#filterForm').submit();
    });

    // Pagination: intercept links inside table wrapper so they load via AJAX
    $(document).on('click', '#memoTableWrapper a.page-link[href]', function(e) {
        var href = $(this).attr('href');
        if (href && href.indexOf('memo-notice-management') !== -1 && !$(this).closest('.page-item').hasClass('disabled')) {
            e.preventDefault();
            loadTableContent(href);
        }
    });

    // Clear filters: reset form and load table without params
    $('#clearFilters').on('click', function() {
        var today = new Date().toISOString().split('T')[0];
        $('#search').val('');
        $('#from_date').val(today);
        $('#to_date').val(today);
        if (window.memoFilterChoices) {
            if (window.memoFilterChoices.program_name) window.memoFilterChoices.program_name.setChoiceByValue('', true);
            if (window.memoFilterChoices.type) window.memoFilterChoices.type.setChoiceByValue('', true);
            if (window.memoFilterChoices.status) window.memoFilterChoices.status.setChoiceByValue('', true);
        }
        $('#program_name').val('');
        $('#type').val('');
        $('#status').val('');
        loadTableContent(indexUrl);
    });

    // Browser back/forward: reload table for current URL
    $(window).on('popstate', function(e) {
        if (e.originalEvent.state && e.originalEvent.state.memoTable) {
            loadTableContent(location.href);
        }
    });

    // Choices.js for modal selects: init on show, destroy on hide (so form reset works)
    var memoTypeEl = document.getElementById('memo_type_master_pk');
    var venueEl = document.getElementById('venue');
    function initMemoModalChoices() {
        if (typeof Choices === 'undefined' || !memoTypeEl || !venueEl) return;
        window.memoModalChoices = window.memoModalChoices || {};
        if (!memoTypeEl.dataset.choicesInitialized) {
            window.memoModalChoices.memoType = new Choices(memoTypeEl, {
                searchEnabled: true,
                searchPlaceholderValue: 'Search...',
                itemSelectText: '',
                shouldSort: false,
                placeholder: true,
                placeholderValue: 'Select Memo Type'
            });
            memoTypeEl.dataset.choicesInitialized = 'true';
        }
        if (!venueEl.dataset.choicesInitialized) {
            window.memoModalChoices.venue = new Choices(venueEl, {
                searchEnabled: true,
                searchPlaceholderValue: 'Search...',
                itemSelectText: '',
                shouldSort: false,
                placeholder: true,
                placeholderValue: 'Select Venue'
            });
            venueEl.dataset.choicesInitialized = 'true';
        }
    }
    function destroyMemoModalChoices() {
        if (window.memoModalChoices) {
            if (window.memoModalChoices.memoType) { window.memoModalChoices.memoType.destroy(); window.memoModalChoices.memoType = null; }
            if (window.memoModalChoices.venue) { window.memoModalChoices.venue.destroy(); window.memoModalChoices.venue = null; }
            if (memoTypeEl) memoTypeEl.dataset.choicesInitialized = '';
            if (venueEl) venueEl.dataset.choicesInitialized = '';
        }
    }
    $('#memo_generate').on('shown.bs.modal', initMemoModalChoices);
    $('#memo_generate').on('hidden.bs.modal', function() {
        destroyMemoModalChoices();
    });
    
    // Handle Generate Memo button (editable mode) - delegation so it works after AJAX table load
    $(document).on('click', '.generate-memo-btn', function() {
        let memoId = $(this).data('id');
        setModalMode('generate');

        $.ajax({
            url: "{{ route('memo.notice.management.get_memo_data') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                memo_notice_id: memoId
            },
            success: function(res) {
                // Populate modal fields
                $('#course_master_name').val(res.course_master_name);
                $('#date_memo_notice').val(res.date_);
                $('#student_name').val(res.student_name);
                $('#subject_master_id').val(res.student_name);
                $('#topic_id').val(res.subject_topic);
                $('#venue_id').val(res.venue_id);
                $('#venue').val(res.venue_id);
                $('#student_notice_status_pk').val(res
                    .student_notice_status_pk);
                $('#course_master_pk').val(res.course_master_pk);
                $('#memo_count').val(res.memo_count + 1);

                $('#session_name').val(res.session_name);
                $('#class_session_master_pk').val(res.class_session_master_pk);
                $('#faculty_name').val(res.faculty_name);
                $('#student_pk').val(res.student_pk);
                $('#memo_number').val(res.memo_number);

                // Sync Choices.js if modal selects are initialized (modal opens after AJAX)
                setTimeout(function() {
                    if (window.memoModalChoices && window.memoModalChoices.venue && res.venue_id) {
                        window.memoModalChoices.venue.setChoiceByValue(String(res.venue_id), true);
                    }
                }, 100);
            },
            error: function() {
                alert('Something went wrong!');
            }
        });
    });

    // Handle Preview Memo button (read-only mode) - delegation for AJAX-loaded content
    $(document).on('click', '.preview-memo-btn', function() {
        let memoId = $(this).data('memo-id');
        // Set preview mode immediately to hide save button
        setModalMode('preview');
        // Also explicitly hide save button as backup
        $('#memo_generate').find('.modal-footer').find('button[type="submit"]').hide();

        if (!memoId) {
            alert('Memo ID not found!');
            return;
        }

        // Fetch all memo data (including notice-related data)
        $.ajax({
            url: "{{ route('memo.notice.management.get_generated_memo_data') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                memo_id: memoId
            },
            success: function(res) {
                // Populate all modal fields from the response
                $('#course_master_name').val(res.course_master_name || '');
                $('#date_memo_notice').val(res.date_ || '');
                $('#student_name').val(res.student_name || '');
                $('#subject_master_id').val(res.subject_master_name || res.student_name || '');
                $('#topic_id').val(res.subject_topic || '');
                $('#student_notice_status_pk').val(res.student_notice_status_pk || '');
                $('#course_master_pk').val(res.course_master_pk || '');
                $('#memo_count').val(res.memo_count || '');

                $('#session_name').val(res.session_name || '');
                $('#class_session_master_pk').val(res.class_session_master_pk || '');
                $('#faculty_name').val(res.faculty_name || '');
                $('#student_pk').val(res.student_pk || '');
                $('#memo_number').val(res.memo_number || '');

                // Populate memo-specific fields
                if (res.memo_type_master_pk) {
                    $('#memo_type_master_pk').val(res.memo_type_master_pk);
                    if (window.memoModalChoices && window.memoModalChoices.memoType) {
                        window.memoModalChoices.memoType.setChoiceByValue(String(res.memo_type_master_pk), true);
                    }
                }
                if (res.venue_master_pk) {
                    $('#venue').val(res.venue_master_pk);
                    if (window.memoModalChoices && window.memoModalChoices.venue) {
                        window.memoModalChoices.venue.setChoiceByValue(String(res.venue_master_pk), true);
                    }
                }
                if (res.date) {
                    $('#memo_date').val(res.date);
                }
                if (res.start_time) {
                    $('#meeting_time').val(res.start_time);
                }
                if (res.message) {
                    $('#textarea').val(res.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error fetching memo data:', error);
                console.error('Response:', xhr.responseText);
                alert('Failed to load memo data. Please try again.');
            }
        });
    });

    // Function to set modal mode (generate or preview)
    function setModalMode(mode) {
        const modal = $('#memo_generate');
        const form = modal.find('form');
        // Use more specific selector for save button
        const saveButton = modal.find('.modal-footer').find('button[type="submit"]');
        const modalTitle = $('#memo_generateLabel');

        if (mode === 'preview') {
            // Preview mode: make all fields read-only
            form.find('input[type="text"], input[type="date"], input[type="time"], textarea').prop('readonly', true);
            form.find('select').prop('disabled', true);
            if (window.memoModalChoices) {
                if (window.memoModalChoices.memoType) window.memoModalChoices.memoType.disable();
                if (window.memoModalChoices.venue) window.memoModalChoices.venue.disable();
            }
            saveButton.hide();
            modalTitle.text('Preview Memo');
        } else {
            // Generate mode: enable editable fields
            form.find('input, textarea').prop('readonly', false);
            form.find('select').prop('disabled', false);
            if (window.memoModalChoices) {
                if (window.memoModalChoices.memoType) window.memoModalChoices.memoType.enable();
                if (window.memoModalChoices.venue) window.memoModalChoices.venue.enable();
            }
            $('#course_master_name, #date_memo_notice, #subject_master_id, #topic_id, #class_session_master_pk, #faculty_name, #student_name, #memo_number').prop('readonly', true);
            form.find('select').not('#memo_type_master_pk, #venue').prop('disabled', true);
            saveButton.show();
            modalTitle.text('Generate Memo');
        }
    }

    // Reset modal when closed
    $('#memo_generate').on('hidden.bs.modal', function() {
        $(this).find('form')[0].reset();
        setModalMode('generate'); // Reset to default mode
    });
});
</script>
@endpush

@endsection