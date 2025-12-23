{{-- ===================== --}}
{{-- Conversation Messages --}}
{{-- ===================== --}}

@php
    use Carbon\Carbon;
    $tz = 'Asia/Kolkata';
    $currentDateKey = null;
    $isStudentView = $type == 'student';
@endphp

<style>
    .chat-wrapper{display:flex;flex-direction:column;max-height:85vh;min-height:70vh;height:100%;overflow:hidden}
    .chat-container{padding:1rem 1.25rem;flex:1 1 auto;min-height:0;overflow-y:auto;background:radial-gradient(circle at top left,#e0eafc 0,#f8f9fb 40%,#f1f5f9 100%)}
    .chat-container::-webkit-scrollbar{width:6px}
    .chat-container::-webkit-scrollbar-thumb{background:rgba(0,74,147,.25);border-radius:3px}
    .chat-row{display:flex;margin-bottom:.65rem}
    .chat-left{justify-content:flex-start}
    .chat-right{justify-content:flex-end}
    .chat-bubble{max-width:78%;padding:.6rem .85rem;border-radius:16px;background:#fff;box-shadow:0 1px 3px rgba(15,23,42,.08);position:relative;font-size:.88rem}
    .chat-left .chat-bubble{background:#fff;border:1px solid #e2e8f0}
    .chat-right .chat-bubble{background:#004a93;color:#e5f1ff;border:1px solid #003366}
    .chat-right .chat-bubble .chat-user,.chat-right .chat-bubble .chat-time,.chat-right .chat-bubble .chat-text{color:#e5f1ff}
    .chat-left .chat-bubble::after,.chat-right .chat-bubble::after{content:"";position:absolute;bottom:.4rem;width:10px;height:10px;background:inherit;transform:rotate(45deg)}
    .chat-left .chat-bubble::after{left:-4px;border-left:1px solid #e2e8f0;border-bottom:1px solid #e2e8f0}
    .chat-right .chat-bubble::after{right:-4px;border-right:1px solid #003366;border-bottom:1px solid #003366}
    .chat-header{display:flex;justify-content:space-between;align-items:center;font-size:.72rem;margin-bottom:.15rem;gap:.5rem}
    .chat-user{font-weight:600;color:#004a93}
    .chat-meta{display:flex;align-items:center;gap:.35rem}
    .chat-time{color:#94a3b8;white-space:nowrap}
    .chat-text{font-size:.9rem;line-height:1.5;color:#1f2937;white-space:pre-wrap;word-wrap:break-word}
    .chat-attachment{margin-top:.4rem}
    .chat-attachment a{font-size:.78rem;text-decoration:none;color:#ff6b35;display:inline-flex;align-items:center;gap:.25rem;padding:.15rem .4rem;border-radius:999px;background:rgba(255,107,53,.06)}
    .chat-attachment a:hover{background:rgba(255,107,53,.15)}
    .date-separator{display:flex;align-items:center;gap:.75rem;margin:1rem 0;position:relative}
    .date-separator::before,.date-separator::after{content:"";flex:1;height:1px;background:linear-gradient(90deg,transparent 0,#cbd5e1 20%,#cbd5e1 80%,transparent 100%)}
    .date-chip{font-size:.72rem;color:#334155;background:#e2e8f0;border-radius:999px;padding:.15rem .6rem}
    .read-receipt{font-size:.7rem;opacity:.9}
    .read-receipt .tick{display:inline-block;transform:translateY(1px)}
    .tick-single::after{content:'✓'}
    .tick-double::after{content:'✓✓'}
    .tick-gray{color:#94a3b8}
    .tick-blue{color:#60a5fa}
    @media (max-width:575.98px){.chat-bubble{max-width:86%}.chat-container{padding:.75rem .5rem}}
    .chat-composer{display:flex;align-items:center;gap:.5rem;border-top:1px solid #dee2e6;padding:.6rem .75rem;background:#fff}
    .chat-input{flex:1;resize:none;border-radius:20px;border:1px solid #cbd5e1;padding:.45rem .75rem;font-size:.9rem;max-height:120px}
    .chat-input:focus{outline:none;border-color:#004a93;box-shadow:0 0 0 2px rgba(0,74,147,.18)}
    .chat-send-btn{border:none;background:#004a93;color:#fff;min-width:44px;height:38px;border-radius:999px;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 6px rgba(15,23,42,.25)}
    .chat-send-btn[disabled]{opacity:.6;cursor:not-allowed}
    .chat-attach-btn{cursor:pointer;font-size:1.1rem;color:#6c757d;display:flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:999px}
    .chat-attach-btn:hover{background:#e2e8f0}
    .flash-new{animation:flashFade .8s ease}
    @keyframes flashFade{from{background:rgba(255,255,0,.25)}to{background:transparent}}
    .text-muted-2{color:#94a3b8}
    .text-muted-inv{color:#dbeafe}
    .visually-hidden{position:absolute!important;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
  </style>

<div class="chat-wrapper" data-memo-id="{{ $id }}" data-type="{{ $type }}" data-user-type="{{ $user_type }}">
  <div class="chat-container" id="conversationScroll">

@forelse ($conversations as $msg)
    @php
        $msgTime = Carbon::parse($msg->created_date)->timezone($tz);
        $dateKey = $msgTime->toDateString();
        $label = $msgTime->isSameDay(Carbon::now($tz))
            ? 'Today'
            : ($msgTime->isSameDay(Carbon::yesterday($tz)) ? 'Yesterday' : $msgTime->format('D, d M Y'));
        $isAdminMsg = $msg->user_type == 'admin';
        $isOwn = $isStudentView ? !$isAdminMsg : $isAdminMsg; // same logic as original alignment
        $hasAttachment = !empty($msg->doc_upload);
        $isRead = (isset($msg->is_read) && (int)$msg->is_read === 1) || !empty(data_get($msg, 'seen_at'));
        $delivered = !empty(data_get($msg, 'delivered_at'));
    @endphp

    @if ($currentDateKey !== $dateKey)
        @php $currentDateKey = $dateKey; @endphp
        <div class="date-separator" aria-label="{{ $label }}">
            <span class="date-chip">{{ $label }}</span>
        </div>
    @endif

    <div class="chat-row {{ $isOwn ? 'chat-right' : 'chat-left' }}">
      <div class="chat-bubble flash-new" data-message-id="{{ data_get($msg, 'id', data_get($msg, 'pk', '')) }}">
        <div class="chat-header">
          <span class="chat-user">{{ $msg->display_name }}</span>
          <span class="chat-meta">
            <span class="chat-time">{{ $msgTime->format('d M Y, h:i A') }}</span>
            @if ($isOwn)
              <span class="read-receipt {{ $isOwn ? '' : 'd-none' }}">
                @if ($isRead)
                    <span class="tick tick-double tick-blue" title="Seen"></span>
                @elseif ($delivered)
                    <span class="tick tick-double tick-gray" title="Delivered"></span>
                @else
                    <span class="tick tick-single tick-gray" title="Sent"></span>
                @endif
              </span>
            @endif
          </span>
        </div>

        <div class="chat-text">{{ $msg->student_decip_incharge_msg }}</div>

        @if ($hasAttachment)
          <div class="chat-attachment">
            <a href="{{ asset('storage/' . $msg->doc_upload) }}" target="_blank" rel="noopener">
              📎 View Attachment
            </a>
          </div>
        @endif
      </div>
    </div>

@empty
    <div class="text-center text-muted py-5">
        <span class="chat-empty-icon" aria-hidden="true">💬</span>
        <div>No conversation yet.</div>
    </div>
@endforelse

  </div>

{{-- ===================== --}}
{{-- Reply / Status Section --}}
{{-- ===================== --}}

@if($conversations->isNotEmpty())

    @if($conversations->last()->notice_status == 1)

        <form id="memo_notice_conversation"
              method="POST"
              enctype="multipart/form-data"
              action="{{ route('memo.notice.management.memo_notice_conversation_model') }}"
              class="chat-composer" novalidate>

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

            <label class="chat-attach-btn" for="memo_notice_attachment" title="Attach file" aria-label="Attach file">📎</label>
            <input id="memo_notice_attachment" type="file" name="attachment" hidden>

            <textarea name="student_decip_incharge_msg"
                      class="chat-input"
                      rows="1"
                      placeholder="Type your message..."
                      required></textarea>

            <button type="submit" class="chat-send-btn">
                <span class="visually-hidden">Send</span>➤
            </button>
        </form>

    @elseif($conversations->last()->notice_status == 2)

        <div class="alert alert-warning mt-3 text-center">
            <strong>Notice Closed:</strong>
            This notice has been closed. You cannot reply.
        </div>

    @else

        <div class="alert alert-info mt-3 text-center">
            <strong>Notice Not Started:</strong>
            This notice has not started yet.
        </div>

    @endif

@else
    <div class="alert alert-info mt-3 text-center">
        <strong>Notice Not Started:</strong>
        Conversation is not yet started.
    </div>
@endif

</div>

<script>
    (function() {
        const root = document.currentScript.closest('.chat-wrapper') || document.querySelector('.chat-wrapper');
        const container = root ? root.querySelector('#conversationScroll') : document.querySelector('#conversationScroll');
        const form = root ? root.querySelector('#memo_notice_conversation') : document.querySelector('#memo_notice_conversation');
        const memoId = root ? root.dataset.memoId : '{{ $id }}';
        const type = root ? root.dataset.type : '{{ $type }}';
        const userType = root ? root.dataset.userType : '{{ $user_type }}';

        const scrollToBottom = () => { if (container) { container.scrollTop = container.scrollHeight; }};
        scrollToBottom();

        // Auto-resize textarea
        if (form) {
            const ta = form.querySelector('.chat-input');
            const sendBtn = form.querySelector('.chat-send-btn');
            const fileBtn = form.querySelector('#memo_notice_attachment');
            const attachLabel = form.querySelector('label[for="memo_notice_attachment"]');

            const resize = () => { ta.style.height = 'auto'; ta.style.height = Math.min(ta.scrollHeight, 120) + 'px'; };
            ta && (ta.addEventListener('input', resize), resize());

            // Submit via AJAX to keep UX smooth
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                if (!ta.value.trim() && !(fileBtn && fileBtn.files && fileBtn.files.length)) return;

                sendBtn.disabled = true;
                const fd = new FormData(form);

                fetch(form.action, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: fd
                }).then(r => {
                    // Try JSON first; fall back to text
                    const ct = r.headers.get('content-type') || '';
                    return ct.includes('application/json') ? r.json() : r.text();
                }).then(() => {
                    ta.value = '';
                    if (fileBtn) fileBtn.value = '';
                    resize();
                    reloadConversation(true);
                }).catch(() => {
                    // Fallback: still try to reload
                    reloadConversation(true);
                }).finally(() => {
                    sendBtn.disabled = false;
                });
            });

            if (attachLabel && fileBtn) {
                attachLabel.addEventListener('click', () => fileBtn.click());
            }
        }

        function reloadConversation(flashNew) {
            const target = document.getElementById('chatBody') || (root ? root.parentElement : null);
            if (!target) { scrollToBottom(); return; }
            const url = '/admin/memo-notice-management/get_conversation_model/' + encodeURIComponent(memoId) + '/' + encodeURIComponent(type) + '/' + encodeURIComponent(userType);
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
              .then(r => r.text())
              .then(html => {
                  target.innerHTML = html;
                  // Mark last bubble as new for a quick flash
                  if (flashNew) {
                      const last = target.querySelector('.chat-bubble:last-child');
                      if (last) last.classList.add('flash-new');
                  }
              })
              .catch(() => {});
        }

        // Laravel Echo / Pusher real-time polish (if available)
        try {
            if (window.Echo) {
                // Use a generic public channel name pattern; adjust if you already have specific ones
                const channelName = 'memo.notice.' + memoId;
                const channel = (window.Echo.private ? window.Echo.private(channelName) : window.Echo.channel(channelName));

                channel.listen('.MemoNoticeMessageCreated', (e) => {
                    reloadConversation(true);
                    setTimeout(scrollToBottom, 150);
                });

                channel.listen('.MemoNoticeMessageRead', (e) => {
                    // Update read receipts if message id provided
                    if (e && e.message_id) {
                        document.querySelectorAll('[data-message-id="' + e.message_id + '"] .read-receipt .tick')
                          .forEach(el => { el.className = 'tick tick-double tick-blue'; el.title = 'Seen'; });
                    } else {
                        reloadConversation(false);
                    }
                });
            }
        } catch (err) { /* no-op */ }

        // Custom DOM events as fallback (can be dispatched from elsewhere)
        window.addEventListener('memo:notice:new', function(ev){
            if (!ev.detail || String(ev.detail.memoId) !== String(memoId)) return;
            reloadConversation(true); setTimeout(scrollToBottom, 125);
        });
        window.addEventListener('memo:notice:read', function(ev){
            if (!ev.detail || String(ev.detail.memoId) !== String(memoId)) return;
            if (ev.detail.messageId) {
                document.querySelectorAll('[data-message-id="' + ev.detail.messageId + '"] .read-receipt .tick')
                  .forEach(el => { el.className = 'tick tick-double tick-blue'; el.title = 'Seen'; });
            }
        });

        // Ensure scroll to bottom on images/attachments load
        const imgs = container ? container.querySelectorAll('img') : [];
        imgs.forEach(img => img.addEventListener('load', scrollToBottom));
    })();
</script>
