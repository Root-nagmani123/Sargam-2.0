@forelse ($conversations as $msg)
@if($type == 'student')
    <div class="chat-message {{ $msg->user_type == 'admin' ? 'bot justify-content-start' : 'user justify-content-end' }}">
@else
    <div class="chat-message {{ $msg->user_type == 'admin' ? 'user justify-content-end' : 'bot justify-content-start' }}">
@endif
      


   

        <div class="message">
            <strong>{{ $msg->display_name }}:</strong>
            <p>{{ $msg->student_decip_incharge_msg }}</p>

            @if ($msg->doc_upload)
                <a href="{{ asset('storage/' . $msg->doc_upload) }}" target="_blank">📄 View Attachment</a>
            @endif

            <small class="d-block">{{ \Carbon\Carbon::parse($msg->created_date)->format('d M Y, h:i A') }}</small>
        </div>
    </div>
@empty
    <p class="text-muted text-center">No conversation yet.</p>
@endforelse
<form id="memo_notice_conversation" method="POST" enctype="multipart/form-data"
                action="{{ route('memo.notice.management.memo_notice_conversation_model') }}">
                @csrf
    <div class="input-group">
        <input type="hidden" name="memo_notice_id" id="memo_notice_id" value="{{ $id }}">
        <input type="hidden" name="user_type" id="type" value="{{ $user_type }}">
        <input type="hidden" name="type" id="type" value="{{ $type }}">

        @if ($user_type == 'student')
           <input type="hidden" name="created_by" value="{{ $conversations[0]->student_id ?? '' }}">
           <input type="hidden" name="role_type" value="s">
        @else
            <input type="hidden" name="created_by" value="{{ auth()->user()->pk}}">
           <input type="hidden" name="role_type" value="f">

        @endif


        @if( $conversations->isNotEmpty() && $conversations->last()->notice_status == 1)
            <div class="offcanvas-footer">
                <div class="border-top p-3">
                <form id="chatForm" enctype="multipart/form-data">
                <div class="d-flex align-items-center gap-2">
                    <!-- Attachment -->
                    <input class="form-control form-control-sm" type="file" id="attachment" name="attachment" style="max-width: 180px;">

                    <!-- Message input -->
                    <textarea class="form-control" id="message" name="student_decip_incharge_msg" rows="1" placeholder="Type your message..." required style="resize: none;"></textarea>

                    <!-- Send Button -->
                    <button class="btn btn-primary" type="submit">Send</button>
                </div>
                        
                    @elseif($conversations->isNotEmpty() && $conversations->last()->notice_status == 2) 

                        <div class="alert alert-warning">
                            <strong>Notice Closed:</strong> This notice has been closed. You cannot reply to it.    
                        </div>
                        @else
                        <div class="alert alert-info">
                            <strong>Notice Not started:</strong> This notice has not started yet.
                        </div>

                    @endif
                </form>
            </div>
            </div>