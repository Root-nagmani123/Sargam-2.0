@extends('admin.layouts.master')

@section('title', 'Memo Management')

@section('setup_content')
<div class="container-fluid">
<x-breadcrum title="Notice /Memo Management">
     <a href="{{ route('ot.notice.memo.view') }}" class="ms-2">
                                <button class="btn btn-primary">Memo/Notice All activity</button>
                            </a>
                            </x-breadcrum>
    <x-session_message />
    <!-- start Zero Configuration -->
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-6">
                    <h4 class="card-title">Notice /Memo Management</h4>
                </div>
                <div class="col-6">
                    <div class="d-flex justify-content-end align-items-center gap-2">

                        <!-- Search Expand -->
                        <div class="search-expand d-flex align-items-center">
                            <a href="javascript:void(0)" id="searchToggle">
                                <i class="material-icons menu-icon material-symbols-rounded"
                                    style="font-size: 24px;">search</i>
                            </a>

                            <input type="text" class="form-control search-input ms-2" id="searchInput"
                                placeholder="Search…" aria-label="Search">
                        </div>

                    </div>
                </div>

            </div>

            <hr>
            <div class="table-responsive">
                <table class="table text-nowrap" style="border-radius: 10px; overflow: hidden; width: 100%;">
                    <thead style="background-color: #af2910;">
                        <tr>
                            <th class="col">S.No.</th>
                            <th class="col">Participant</th>
                            <th class="col">Type</th>
                            <th class="col">Date</th>
                            <th class="col">Topic</th>
                            <th class="col">Conversation</th>
                            <th class="col">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($memos->isEmpty())
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="bi bi-inbox me-1"></i> No records found
                            </td>
                        </tr>
                        @else
                        @php $grouped = $memos->groupBy('type_notice_memo'); @endphp

                        @foreach ($grouped as $type => $group)
                        @foreach ($group as $index => $memo)
                        <tr>
                            <td>{{ $memos->firstItem() + $loop->parent->iteration - 1 + $loop->iteration - 1 }}</td>
                            <td>{{ $memo->student_name }}</td>
                            <td>
                                @if ($memo->notice_memo == '1')
                                <span class="badge rounded-1 bg-primary-subtle text-primary">Notice</span>
                                @elseif ($memo->notice_memo == '2')
                                <span class="badge rounded-1 bg-secondary-subtle text-secondary">Memo</span>
                                @else
                                <span class="badge rounded-1 bg-info-subtle text-info">Other</span>
                                @endif
                            </td>
                            <td class="date">{{ $memo->date_ }}</td>
                            <td>{{ $memo->topic_name ?? 'N/A' }}</td>
                            <td>
                                <div class="d-flex gap-2 flex-nowrap">
                                    <a href="{{ route('memo.notice.management.conversation_student', ['id' => $memo->notice_id, 'type' => 'notice']) }}"
                                        class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip"
                                        title="Notice Conversation">
                                        <i class="bi bi-chat-dots"></i> Notice
                                    </a>

                                    <a href="javascript:void(0)" class="view-conversation position-relative"
                                        data-bs-target="#chatOffcanvas"
                                        @if ($memo->notice_memo == '1') data-type="notice" data-id="{{ $memo->notice_id }}"
                                        @elseif($memo->notice_memo == '2') data-type="memo" data-id="{{ $memo->memo_id ?? $memo->notice_id }}"
                                        @else data-type="notice" data-id="{{ $memo->notice_id }}"
                                        @endif
                                        data-topic="{{ $memo->topic_name }}"
                                        data-bs-toggle="tooltip"
                                        title="Reply">
                                        <i class="material-icons menu-icon material-symbols-rounded"
                                            style="font-size: 24px;">reply</i>
                                        @if (($memo->chat_unread ?? 0) > 0)
                                        <span class="position-absolute translate-middle badge rounded-1 bg-danger chat-unread-badge"
                                            style="top: 2px; left: 100%; font-size: 9px;">
                                            {{ $memo->chat_unread > 99 ? '99+' : $memo->chat_unread }}
                                            <span class="visually-hidden">unread messages</span>
                                        </span>
                                        @endif
                                    </a>

                                    @if($memo->type_notice_memo == 'Memo')
                                    <a href="{{ route('memo.notice.management.conversation_student', ['id' => $memo->memo_id, 'type' => 'memo']) }}"
                                        class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip"
                                        title="Memo Conversation">
                                        <i class="bi bi-chat-square-text"></i> Memo
                                    </a>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @if ($memo->status == 1)
                                <span class="badge rounded-1 bg-success-subtle text-success">
                                    <i class="bi bi-check-circle me-1"></i> Open
                                </span>
                                @else
                                <span class="badge rounded-1 bg-danger-subtle text-danger">
                                    <i class="bi bi-x-circle me-1"></i> Close
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
            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap">

                <div class="text-muted small mb-2">
                    Showing {{ $memos->firstItem() ?? 0 }}
                    to {{ $memos->lastItem() }}
                    of {{ $memos->total() }} items
                </div>

                <div>
                    {{ $memos->links('vendor.pagination.custom') }}
                </div>

            </div>
        </div>
    </div>

</div>
<div class="offcanvas offcanvas-end" tabindex="-1" id="chatOffcanvas">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="conversationTopic">Conversation</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <input type="hidden" id="userType" value="">
    <div class="offcanvas-body d-flex flex-column">
        <!-- Chat Body -->
        <div class="chat-body flex-grow-1 mb-3" id="chatBody">
            <p class="text-muted text-center">Loading conversation...</p>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    let pollInterval = null;
    const chatOffcanvasEl = document.getElementById('chatOffcanvas');

    function loadConversation(memoId, type, userType) {
        return fetch('/admin/memo-notice-management/get_conversation_model/'
            + encodeURIComponent(memoId) + '/' + encodeURIComponent(type) + '/' + encodeURIComponent(userType),
            { headers: { 'X-Requested-With': 'XMLHttpRequest' } }
        ).then(r => r.text());
    }

    function applyConversationHtml(html, memoId, scrollToBottom) {
        const chatBody = document.getElementById('chatBody');
        const w = chatBody && chatBody.querySelector('.chat-wrapper');
        // Bail if user switched to a different conversation while loading
        if (w && w.dataset.memoId !== String(memoId)) return;

        const scroll = document.querySelector('#conversationScroll');
        const wasAtBottom = !scroll || (scroll.scrollHeight - scroll.scrollTop - scroll.clientHeight < 60);

        $('#chatBody').html(html); // jQuery html() executes scripts on first load

        const newScroll = document.querySelector('#conversationScroll');
        if (newScroll && (scrollToBottom || wasAtBottom)) {
            newScroll.scrollTop = newScroll.scrollHeight;
        }
    }

    // Poll-only refresh: swap in just the new messages, never touch the composer.
    // A background poll replacing the whole panel can race the native file-picker
    // dialog — the dialog stays open (blocking the user) for as long as they're
    // browsing folders, but page timers keep running underneath it. If a poll fires
    // before they've picked a file, the composer (and its <input type=file>) gets
    // replaced while the OS dialog is still open; the eventual file selection then
    // fires a change event on a detached input that can no longer bubble up to our
    // document-level listener, so nothing shows and nothing sends. Only ever
    // touching the message list during polling avoids this race entirely.
    function mergeNewMessages(html, memoId) {
        const chatBody = document.getElementById('chatBody');
        const w = chatBody && chatBody.querySelector('.chat-wrapper');
        if (!w || w.dataset.memoId !== String(memoId)) return;

        const scroll = document.getElementById('conversationScroll');
        if (!scroll) return;
        const wasAtBottom = scroll.scrollHeight - scroll.scrollTop - scroll.clientHeight < 60;

        const parsed = new DOMParser().parseFromString(html, 'text/html');
        const newScroll = parsed.getElementById('conversationScroll');
        if (!newScroll) return;

        scroll.innerHTML = newScroll.innerHTML;
        if (wasAtBottom) scroll.scrollTop = scroll.scrollHeight;
    }

    $('.view-conversation').on('click', function() {
        let memoId = $(this).data('id');
        let topic = $(this).data('topic');
        let type = $(this).data('type');
        $('#userType').val(type);
        let user_type = 'student';

        // Opening the chat marks it read server-side; clear the badge immediately for feedback.
        $(this).find('.chat-unread-badge').remove();

        $('#conversationTopic').text(topic);
        $('#chatBody').html('<p class="text-muted text-center">Loading conversation...</p>');

        loadConversation(memoId, type, user_type)
            .then(html => applyConversationHtml(html, memoId, true))
            .catch(() => {
                $('#chatBody').html('<p class="text-danger text-center">Failed to load conversation.</p>');
            });

        // Show offcanvas
        let chatOffcanvas = new bootstrap.Offcanvas(chatOffcanvasEl);
        chatOffcanvas.show();
    });

    // Start real-time polling when offcanvas is visible
    chatOffcanvasEl.addEventListener('shown.bs.offcanvas', function() {
        if (pollInterval) clearInterval(pollInterval);
        pollInterval = setInterval(function() {
            const w = document.querySelector('#chatBody .chat-wrapper');
            if (!w) return;
            const memoId   = w.dataset.memoId;
            const type     = w.dataset.type;
            const userType = w.dataset.userType;
            if (!memoId) return;
            loadConversation(memoId, type, userType)
                .then(html => mergeNewMessages(html, memoId))
                .catch(() => {});
        }, 5000); // poll every 5 seconds
    });

    // Stop polling when offcanvas closes
    chatOffcanvasEl.addEventListener('hide.bs.offcanvas', function() {
        if (pollInterval) { clearInterval(pollInterval); pollInterval = null; }
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

    // Close on outside click
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.search-expand')) {
            input.classList.remove('active');
        }
    });
});
</script>
@endsection