{{-- ===================== --}}
{{-- Conversation Messages --}}
{{-- ===================== --}}
<style>
    /* ===============================
   Modern Chat UI
   =============================== */

.chat-container {
    padding: 1rem;
    max-height: 60vh;
    overflow-y: auto;
    background: #f8f9fa;
}

/* Rows */
.chat-row {
    display: flex;
    margin-bottom: 1rem;
}

.chat-left {
    justify-content: flex-start;
}

.chat-right {
    justify-content: flex-end;
}

/* Bubble */
.chat-bubble {
    max-width: 70%;
    padding: 0.75rem 1rem;
    border-radius: 14px;
    background: #ffffff;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
    position: relative;
}

.chat-right .chat-bubble {
    background: #e7f1ff;
}

/* Header */
.chat-header {
    display: flex;
    justify-content: space-between;
    font-size: 0.75rem;
    margin-bottom: 0.25rem;
}

.chat-user {
    font-weight: 600;
    color: #004a93;
}

.chat-time {
    color: #6c757d;
}

/* Text */
.chat-text {
    font-size: 0.9rem;
    line-height: 1.5;
}

/* Attachment */
.chat-attachment {
    margin-top: 0.5rem;
}

.chat-attachment a {
    font-size: 0.8rem;
    text-decoration: none;
    color: #0d6efd;
}

/* Composer */
.chat-composer {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    border-top: 1px solid #dee2e6;
    padding: 0.75rem;
    background: #ffffff;
}

.chat-input {
    flex: 1;
    resize: none;
    border-radius: 20px;
    border: 1px solid #ced4da;
    padding: 0.5rem 0.75rem;
    font-size: 0.9rem;
}

.chat-input:focus {
    outline: none;
    border-color: #004a93;
}

/* Buttons */
.chat-send-btn {
    border: none;
    background: #004a93;
    color: #fff;
    width: 38px;
    height: 38px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.chat-attach-btn {
    cursor: pointer;
    font-size: 1.2rem;
    color: #6c757d;
}

</style>
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
        <i class="bi bi-chat-dots fs-1 d-block mb-2"></i>
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
                    <i class="bi bi-paperclip"></i>
                    <input type="file" name="attachment" hidden>
                </label>

                <textarea name="student_decip_incharge_msg"
                          class="chat-input"
                          rows="1"
                          placeholder="Type your message..."
                          required></textarea>

                <button type="submit" class="chat-send-btn">
                    <i class="bi bi-send-fill"></i>
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
