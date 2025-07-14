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

        @if ($type == 'student')
           <input type="hidden" name="created_by" value="{{ $conversations[0]->student_id ?? '' }}">
           <input type="hidden" name="role_type" value="s">
        @else
            <input type="hidden" name="created_by" value="{{ auth()->user()->id}}">
           <input type="hidden" name="role_type" value="f">

        @endif
        

        @if($conversations->isNotEmpty() && $conversations->last()->notice_status == 1)
            <div class="mb-3">
                <label for="message" class="form-label">Your Message</label>
                <textarea class="form-control" id="message" name="student_decip_incharge_msg" rows="3" required></textarea>
            </div>
            <input type="text" class="form-control" id="chatInput" name="message" placeholder="Type your message...">
        <button class="btn btn-primary" type="submit">Send</button>
        @else
            <div class="alert alert-warning">
                <strong>Notice Closed:</strong> This notice has been closed. You cannot reply to it.    
            </div>
        @endif

        
    </div>
</form>