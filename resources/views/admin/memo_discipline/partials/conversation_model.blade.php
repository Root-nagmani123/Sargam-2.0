{{-- ===================== --}}
{{-- Conversation Messages --}}
{{-- ===================== --}}
<style>
    /* ===============================
       WhatsApp‑style Chat UI
       (Sargam / LBSNAA branding)
       =============================== */

    .chat-wrapper {
        display: flex;
        flex-direction: column;
        max-height: 85vh;
        min-height: 80vh;
        height: 100%;
        overflow: hidden;
    }

    .chat-container {
        padding: 1rem 1.25rem;
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
        /* WhatsApp-like subtle background, but in LBSNAA blues */
        background: radial-gradient(circle at top left, #e0eafc 0, #f8f9fb 40%, #f1f5f9 100%);
    }

    /* Scrollbar (subtle) */
    .chat-container::-webkit-scrollbar {
        width: 6px;
    }

    .chat-container::-webkit-scrollbar-thumb {
        background: rgba(0, 74, 147, 0.25);
        border-radius: 3px;
    }

    /* Rows */
    .chat-row {
        display: flex;
        margin-bottom: 0.65rem;
    }

    .chat-left {
        justify-content: flex-start;
    }

    .chat-right {
        justify-content: flex-end;
    }

    /* Bubble */
    .chat-bubble {
        max-width: 78%;
        padding: 0.6rem 0.85rem;
        border-radius: 16px;
        background: #ffffff;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
        position: relative;
        font-size: 0.88rem;
    }

    /* Receiver (admin / faculty) – light */
    .chat-left .chat-bubble {
        background: #ffffff;
        border: 1px solid #e2e8f0;
    }

    /* Sender (current user) – LBSNAA blue */
    .chat-right .chat-bubble {
        background: #004a93;
        color: #e5f1ff;
        border: 1px solid #003366;
    }

    .chat-right .chat-bubble .chat-user,
    .chat-right .chat-bubble .chat-time,
    .chat-right .chat-bubble .chat-text {
        color: #e5f1ff;
    }

    /* Bubble "tails" */
    .chat-left .chat-bubble::after,
    .chat-right .chat-bubble::after {
        content: "";
        position: absolute;
        bottom: 0.4rem;
        width: 10px;
        height: 10px;
        background: inherit;
        transform: rotate(45deg);
    }

    .chat-left .chat-bubble::after {
        left: -4px;
        border-left: 1px solid #e2e8f0;
        border-bottom: 1px solid #e2e8f0;
    }

    .chat-right .chat-bubble::after {
        right: -4px;
        border-right: 1px solid #003366;
        border-bottom: 1px solid #003366;
    }

    /* Header (name + time) */
    .chat-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.72rem;
        margin-bottom: 0.15rem;
        gap: 0.5rem;
    }

    .chat-user {
        font-weight: 600;
        color: #004a93;
    }

    .chat-time {
        color: #94a3b8;
        white-space: nowrap;
    }

    /* Text */
    .chat-text {
        font-size: 0.88rem;
        line-height: 1.5;
        color: #1f2937;
        white-space: pre-wrap;
        word-wrap: break-word;
    }

    /* Attachment */
    .chat-attachment {
        margin-top: 0.4rem;
    }

    .chat-attachment a {
        font-size: 0.78rem;
        text-decoration: none;
        color: #ff6b35;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.15rem 0.4rem;
        border-radius: 999px;
        background: rgba(255, 107, 53, 0.06);
    }

    .chat-attachment a:hover {
        background: rgba(255, 107, 53, 0.15);
    }

    /* Composer (bottom input area) */
    .chat-composer {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        border-top: 1px solid #dee2e6;
        padding: 0.6rem 0.75rem;
        background: #ffffff;
    }

    .chat-input {
        flex: 1;
        resize: none;
        border-radius: 20px;
        border: 1px solid #cbd5e1;
        padding: 0.45rem 0.75rem;
        font-size: 0.9rem;
        max-height: 120px;
    }

    .chat-input:focus {
        outline: none;
        border-color: #004a93;
        box-shadow: 0 0 0 2px rgba(0, 74, 147, 0.18);
    }

    /* Buttons */
    .chat-send-btn {
        border: none;
        background: #004a93;
        color: #fff;
        width: 38px;
        height: 38px;
        border-radius: 999px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 6px rgba(15, 23, 42, 0.25);
    }

    .chat-send-btn:hover {
        background: #003366;
    }

    .chat-attach-btn {
        cursor: pointer;
        font-size: 1.2rem;
        color: #6c757d;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 999px;
        transition: background 0.15s ease;
    }

    .chat-attach-btn:hover {
        background: #e2e8f0;
    }

    /* Empty state */
    .chat-empty-icon {
        color: #cbd5e1;
    }

    /* Responsive tweaks */
    @media (max-width: 575.98px) {
        .chat-wrapper {
            max-height: 85vh;
            min-height: 80vh;
        }

        .chat-container {
            padding: 0.75rem 0.5rem;
        }

        .chat-bubble {
            max-width: 86%;
        }
    }
</style>
<div class="chat-wrapper">
    <div class="chat-container">

@forelse ($conversations as $msg)

    <div class="chat-row
        {{ $type == 'student'
            ? ($msg->user_type == 'admin' ? 'chat-left' : 'chat-right')
            : ($msg->user_type == 'admin' ? 'chat-right' : 'chat-left')
        }}">

        <div class="chat-bubble">

            <div class="chat-header">
                <span class="chat-user">{{ $msg->display_name }}</span>
                <span class="chat-time">
                    {{ \Carbon\Carbon::parse($msg->created_date)->format('d M Y, h:i A') }}
                </span>
            </div>

            <div class="chat-text">
                {{ $msg->student_decip_incharge_msg }}
            </div>

            @if (!empty($msg->doc_upload))
                <div class="chat-attachment">
                    <a href="{{ asset('storage/' . $msg->doc_upload) }}" target="_blank">
                        <i class="bi bi-paperclip"></i> View Attachment
                    </a>
                </div>
            @endif

        </div>
    </div>

@empty
    <div class="text-center text-muted py-5">
        <i class="material-icons material-symbols-rounded fs-1 d-block mb-2">chat</i>
        No conversation yet
    </div>
@endforelse

    </div>
    @if($conversations->isNotEmpty())

    @if($conversations->last()->notice_status == 2)

        <form id="memo_notice_conversation"
              method="POST"
              enctype="multipart/form-data"
              action="{{ route('memo.discipline.conversation.store') }}">

            @csrf

            <input type="hidden" name="memo_discipline_id" value="{{ $memoId }}">
            <input type="hidden" name="type" value="{{ $type }}">

            @if ($type == 'OT')
                <input type="hidden" name="created_by" value="@if(isset($conversations->first()->student_id )) {{ $conversations->first()->student_id }} @else {{ auth()->user()->user_id }} @endif">
                <input type="hidden" name="role_type" value="s">
            @else
                <input type="hidden" name="created_by" value="{{ auth()->user()->pk }}">
                <input type="hidden" name="role_type" value="f">
            @endif

            <div class="chat-composer">
                <label class="chat-attach-btn">
                    <i class="material-icons material-symbols-rounded">attach_file</i>
                    <input type="file" name="attachment" hidden>
                </label>

                <textarea name="student_decip_incharge_msg"
                          class="chat-input"
                          rows="1"
                          placeholder="Type your message..."
                          required></textarea>

                <button type="submit" class="chat-send-btn">
                    <i class="material-icons material-symbols-rounded">send</i>
                </button>
            </div>
        </form>

    @elseif($conversations->last()->notice_status == 3)

        <div class="alert alert-warning mt-3 text-center">
            <i class="bi bi-lock-fill me-1"></i>
            <strong>Memo Closed</strong> — You cannot reply further.
        </div>

    @else

        <div class="alert alert-info mt-3 text-center">
            <i class="bi bi-info-circle me-1"></i>
            Memo has not started yet.
        </div>

    @endif

    @endif
</div>

<script>
    // Auto-scroll chat to latest message when partial is rendered
    (function() {
        const container = document.querySelector('#memoNoticeConversationModal .chat-container') ||
            document.querySelector('.chat-container');
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
    })();
</script>
