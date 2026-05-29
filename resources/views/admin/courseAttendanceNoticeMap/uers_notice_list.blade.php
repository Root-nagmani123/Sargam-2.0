@extends('admin.layouts.master')

@section('title', 'Memo Management - Sargam | Lal Bahadur Shastri National Academy of Administration')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<link rel="stylesheet" href="{{ asset('css/memo-notice-management-admin.css') }}?v={{ @filemtime(public_path('css/memo-notice-management-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid mnm-master-page py-3 px-3 px-lg-4">
    <x-breadcrum title="Notice / Memo Management" />

    <x-session_message />

    <div class="card mnm-dt-card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 mnm-user-toolbar">
                <h2 class="mnm-page-title mb-0">Notice / Memo Management</h2>

                <div class="d-flex flex-wrap align-items-center justify-content-lg-end gap-2 ms-lg-auto">
                    <div class="search-expand d-flex align-items-center">
                        <a href="javascript:void(0)" id="searchToggle" class="btn mnm-search-trigger text-decoration-none" aria-label="Toggle search">
                            <i class="bi bi-search" aria-hidden="true"></i>
                        </a>
                        <input type="text"
                            class="form-control search-input mnm-search-input ms-2 shadow-none"
                            id="searchInput"
                            placeholder="Search…"
                            aria-label="Search">
                    </div>
                    <a href="{{ route('ot.notice.memo.view') }}" class="btn btn-primary d-inline-flex align-items-center gap-2 px-3 rounded-2 fw-semibold">
                        <i class="bi bi-list-ul" aria-hidden="true"></i>
                        <span>Memo/Notice All activity</span>
                    </a>
                </div>
            </div>

            <div class="programme-dt-panel mnm-dt-panel">
                <div class="table-responsive mnm-dt-scroll">
                    <table class="table table-hover align-middle mb-0 w-100 programme-dt-table mnm-dt-table">
                        <thead>
                            <tr>
                                <th scope="col" class="text-nowrap">S. No.</th>
                                <th scope="col">Participant</th>
                                <th scope="col" class="text-nowrap">Type</th>
                                <th scope="col" class="text-nowrap">Date</th>
                                <th scope="col">Topic</th>
                                <th scope="col">Conversation</th>
                                <th scope="col" class="text-nowrap">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if ($memos->isEmpty())
                            <tr>
                                <td colspan="7" class="mnm-empty-state">
                                    <i class="bi bi-inbox" aria-hidden="true"></i>
                                    No records found
                                </td>
                            </tr>
                            @else
                            @php $grouped = $memos->groupBy('type_notice_memo'); @endphp

                            @foreach ($grouped as $type => $group)
                            @foreach ($group as $index => $memo)
                            <tr>
                                <td class="sno">{{ $memos->firstItem() + $loop->parent->iteration - 1 + $loop->iteration - 1 }}</td>
                                <td class="s_name fw-medium">{{ $memo->student_name }}</td>
                                <td class="type mnm-user-type">
                                    @if ($memo->notice_memo == '1')
                                        Notice
                                    @elseif ($memo->notice_memo == '2')
                                        Memo
                                    @else
                                        Other
                                    @endif
                                </td>
                                <td class="date text-nowrap">{{ $memo->date_ }}</td>
                                <td>{{ $memo->topic_name }}</td>
                                <td class="conversation">
                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        <a href="{{ route('memo.notice.management.conversation_student', ['id' => $memo->notice_id, 'type' => 'notice']) }}"
                                            class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1"
                                            data-bs-toggle="tooltip"
                                            title="Notice Conversation">
                                            <i class="bi bi-chat-dots" aria-hidden="true"></i> Notice
                                        </a>

                                        <a href="javascript:void(0)"
                                            class="btn btn-sm btn-light border d-inline-flex align-items-center view-conversation"
                                            data-bs-toggle="offcanvas"
                                            data-bs-target="#chatOffcanvas"
                                            @if ($memo->notice_memo == '1') data-type="notice" data-id="{{ $memo->notice_id }}" @elseif($memo->notice_memo == '2') data-type="memo" data-id="{{ $memo->memo_id }}" @endif
                                            data-topic="{{ $memo->topic_name }}"
                                            title="Quick View">
                                            <i class="bi bi-eye" aria-hidden="true"></i>
                                        </a>

                                        @if($memo->type_notice_memo == 'Memo')
                                        <a href="{{ route('memo.notice.management.conversation_student', ['id' => $memo->memo_id, 'type' => 'memo']) }}"
                                            class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1"
                                            data-bs-toggle="tooltip"
                                            title="Memo Conversation">
                                            <i class="bi bi-chat-square-text" aria-hidden="true"></i> Memo
                                        </a>
                                        @endif
                                    </div>
                                </td>
                                <td class="status">
                                    @if ($memo->status == 1)
                                    <span class="badge rounded-pill bg-success-subtle text-success">
                                        <i class="bi bi-check-circle me-1" aria-hidden="true"></i> Open
                                    </span>
                                    @else
                                    <span class="badge rounded-pill bg-danger-subtle text-danger">
                                        <i class="bi bi-x-circle me-1" aria-hidden="true"></i> Close
                                    </span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                            @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>

                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="mnm-pagination-nav">
                        {{ $memos->links('vendor.pagination.custom') }}
                    </div>
                    <div class="programme-dt-count mnm-dt-count mb-0">
                        Showing {{ $memos->firstItem() ?? 0 }}
                        to {{ $memos->lastItem() ?? 0 }}
                        of {{ $memos->total() }} items
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="offcanvas offcanvas-end shadow-lg" tabindex="-1" id="chatOffcanvas" aria-labelledby="conversationTopic">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title mb-0" id="conversationTopic">Conversation</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <input type="hidden" id="userType" value="">
        <div class="offcanvas-body d-flex flex-column">
            <div class="chat-body flex-grow-1" id="chatBody">
                <p class="text-muted text-center">Loading conversation...</p>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    $('.view-conversation').on('click', function() {
        let memoId = $(this).data('id');
        let topic = $(this).data('topic');
        let type = $(this).data('type');
        $('#userType').val(type);
        let user_type = 'student';

        $('#conversationTopic').text(topic);
        $('#chatBody').html('<p class="text-muted text-center">Loading conversation...</p>');

        $.ajax({
            url: '/admin/memo-notice-management/get_conversation_model/' + memoId + '/' + type + '/' + user_type,
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

        let chatOffcanvas = new bootstrap.Offcanvas(document.getElementById('chatOffcanvas'));
        chatOffcanvas.show();
    });
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggle = document.getElementById('searchToggle');
    const input = document.getElementById('searchInput');

    toggle.addEventListener('click', () => {
        input.classList.toggle('active');
        if (input.classList.contains('active')) {
            input.focus();
        }
    });

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.search-expand')) {
            input.classList.remove('active');
        }
    });
});
</script>
@endsection
