@extends('admin.layouts.master')

@section('title', 'Memo Discipline - Sargam | Lal Bahadur Shastri National Academy of Administration')

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
<div class="container-fluid">
    <x-breadcrum title="Memo Discipline" />
    <x-session_message />

    <!-- start Zero Configuration -->
    <div class="card" style="border-left:4px solid #004a93;">
        <div class="card-body">
            <div class="row">
                <div class="col-6">
                    <h4 class="card-title">Memo Discipline</h4>
                </div>
                <div class="col-6">
                    <div class="d-flex justify-content-end align-items-center gap-2">

                        <!-- Add Group Mapping -->
                        @if(hasRole('Internal Faculty') || hasRole('Guest Faculty') || hasRole('Admin') ||
                        hasRole('Training'))
                        <a href="{{ route('memo.discipline.create') }}"
                            class="btn btn-primary d-flex align-items-center">
                            <i class="material-icons menu-icon material-symbols-rounded"
                                style="font-size: 24px;">add</i>
                            Marks Deduction
                        </a>
                        @endif


                    </div>
                </div>
            </div>
            <form method="GET" action="{{ route('memo.discipline.index') }}" id="filterForm">
                <div class="row">
                    <div class="col-3">
                        <div class="mb-3">
                            <label for="program_name" class="form-label">Program Name</label>
                            <select class="form-select" id="program_name" name="program_name">
                                <option value="">Select Program</option>
                                @foreach($courses as $course)
                                <option value="{{ $course->pk }}"
                                    {{ (string)$programNameFilter == (string)$course->pk ? 'selected' : '' }}>
                                    {{ $course->course_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-3">
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">Select status</option>
                                <option value="2" {{ $statusFilter == '2' ? 'selected' : '' }}>Open</option>
                                <option value="3" {{ $statusFilter == '3' ? 'selected' : '' }}>Close</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <input type="text" class="form-control" id="search" name="search" placeholder="Search..."
                                value="{{ $searchFilter }}">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-3">
                        <div class="mb-3">
                            <label for="from_date" class="form-label">From Date</label>
                            <input type="date" class="form-control" id="from_date" name="from_date"
                                value="{{ $fromDateFilter ?: \Carbon\Carbon::today()->toDateString() }}">
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="mb-3">
                            <label for="to_date" class="form-label">To Date</label>
                            <input type="date" class="form-control" id="to_date" name="to_date"
                                value="{{ $toDateFilter ?: \Carbon\Carbon::today()->toDateString() }}">
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3 d-flex align-items-center gap-2">
                            <a href="{{ route('memo.discipline.index') }}" class="btn btn-secondary">
                                <i class="bi bi-x-circle me-1"></i> Clear Filters
                            </a>
                        </div>
                    </div>
                    <div class="col-6 text-end">
                        <div class="mb-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-funnel-fill me-1"></i> Apply Filters
                            </button>
                        </div>
                    </div>
                </div>
            </form>
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
                            <th class="text-end">Action</th>
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
                                        data-type="{{ (hasRole('Internal Faculty') || hasRole('Guest Faculty') || hasRole('Admin') || hasRole('Training')) ? 'admin' : 'OT' }}">
                                        <i class="material-icons material-symbols-rounded fs-5">chat</i>
                                    </a>
                                </div>
                                @else
                                <span class="badge bg-secondary-subtle text-secondary">
                                    <i class="bi bi-lock me-1"></i> Closed
                                </span>
                                @endif
                            </td>

                            <!-- Action -->
                            <td class="text-end">
                                @if(hasRole('Internal Faculty') || hasRole('Guest Faculty') || hasRole('Admin')
                                || hasRole('Training'))
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
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

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