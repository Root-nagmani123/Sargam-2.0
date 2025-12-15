{{-- ===================== --}}
{{-- Conversation Messages --}}
{{-- ===================== --}}

@forelse ($conversations as $msg)

    <div class="chat-message
        {{ $type == 'student'
            ? ($msg->user_type == 'admin' ? 'bot justify-content-start' : 'user justify-content-end')
            : ($msg->user_type == 'admin' ? 'user justify-content-end' : 'bot justify-content-start')
        }}">

        <div class="message">
            <strong>{{ $msg->display_name }}:</strong>

            <p>{{ $msg->student_decip_incharge_msg }}</p>

            @if (!empty($msg->doc_upload))
                <a href="{{ asset('storage/' . $msg->doc_upload) }}" target="_blank">
                    📄 View Attachment
                </a>
            @endif

            <small class="d-block text-muted">
                {{ \Carbon\Carbon::parse($msg->created_date)->format('d M Y, h:i A') }}
            </small>
        </div>
    </div>

@empty
    <p class="text-muted text-center">No conversation yet.</p>
@endforelse


{{-- ===================== --}}
{{-- Reply / Status Section --}}
{{-- ===================== --}}

@if($conversations->isNotEmpty())

    @if($conversations->last()->notice_status == 1)

        <form id="memo_notice_conversation"
              method="POST"
              enctype="multipart/form-data"
              action="{{ route('memo.notice.management.memo_notice_conversation_model') }}">

            @csrf

            <input type="hidden" name="memo_notice_id" value="{{ $id }}">
            <input type="hidden" name="type" value="{{ $type }}">
            <input type="hidden" name="user_type" value="{{ $user_type }}">

            @if ($user_type == 'student')
                <input type="hidden" name="created_by" value="{{ $conversations->first()->student_id }}">
                <input type="hidden" name="role_type" value="s">
            @else
                <input type="hidden" name="created_by" value="{{ auth()->user()->pk }}">
                <input type="hidden" name="role_type" value="f">
            @endif

            <div class="border-top p-3">
                <div class="d-flex align-items-center gap-2">

                    <input type="file"
                           name="attachment"
                           class="form-control form-control-sm"
                           style="max-width: 180px;">

                    <textarea name="student_decip_incharge_msg"
                              class="form-control"
                              rows="1"
                              placeholder="Type your message..."
                              required></textarea>

                    <button type="submit" class="btn btn-primary">
                        Send
                    </button>

                </div>
            </div>
        </form>

    @elseif($conversations->last()->notice_status == 2)

        <div class="alert alert-warning mt-3">
            <strong>Notice Closed:</strong>
            This notice has been closed. You cannot reply.
        </div>

    @else

        <div class="alert alert-info mt-3">
            <strong>Notice Not Started:</strong>
            This notice has not started yet.
        </div>

    @endif

@else
    <div class="alert alert-info mt-3">
        <strong>Notice Not Started:</strong>
        Conversation is not yet started.
    </div>
@endif
