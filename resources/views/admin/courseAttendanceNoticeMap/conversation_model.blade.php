{{-- ===================== --}}
{{-- Conversation Messages --}}
{{-- ===================== --}}

@php
    use Carbon\Carbon;
    $tz = 'Asia/Kolkata';
    $currentDateKey = null;
    $isStudentView = $type == 'student';
    // Treat stored created_date as UTC (OT/DB) and display in India time
@endphp

<style>
    /* Flush the chat panel to the offcanvas edges (overrides the shared padded box) */
    #chatOffcanvas .offcanvas-body{padding:0;overflow:hidden}
    #chatBody{padding:0;border:0;background:transparent;box-shadow:none;border-radius:0;overflow:hidden;display:flex;flex-direction:column;min-height:0;height:auto}

    .chat-wrapper{display:flex;flex-direction:column;flex:1 1 auto;min-height:0;height:100%;overflow:hidden}
    .chat-container{padding:1rem .85rem;flex:1 1 auto;min-height:0;overflow-y:auto;background:#e9f1fb}
    .chat-container::-webkit-scrollbar{width:6px}
    .chat-container::-webkit-scrollbar-thumb{background:rgba(0,74,147,.22);border-radius:3px}

    /* Date separator chip */
    .date-separator{display:flex;justify-content:center;margin:.75rem 0}
    .date-chip{font-size:.72rem;color:#51627a;background:#d7e4f5;border-radius:999px;padding:.2rem .75rem;font-weight:500}

    /* Bubbles */
    .chat-row{display:flex;margin-bottom:.5rem}
    .chat-left{justify-content:flex-start}
    .chat-right{justify-content:flex-end}
    .chat-bubble{max-width:78%;padding:.5rem .7rem;border-radius:14px;position:relative;font-size:.86rem;box-shadow:0 1px 1.5px rgba(15,23,42,.08)}
    .chat-left .chat-bubble{background:#fff;color:#1f2937;border-top-left-radius:3px}
    .chat-right .chat-bubble{background:#1b5a9e;color:#fff;border-top-right-radius:3px}
    /* little beak at the top outer corner */
    .chat-left .chat-bubble::before{content:"";position:absolute;top:0;left:-6px;width:12px;height:12px;background:#fff;clip-path:polygon(100% 0,0 0,100% 100%)}
    .chat-right .chat-bubble::before{content:"";position:absolute;top:0;right:-6px;width:12px;height:12px;background:#1b5a9e;clip-path:polygon(0 0,100% 0,0 100%)}

    .chat-head{display:flex;justify-content:space-between;align-items:center;gap:.6rem;font-size:.68rem;line-height:1;margin-bottom:.2rem}
    .chat-user{font-weight:600}
    .chat-left .chat-user{color:#1b5a9e}
    .chat-right .chat-user{color:#d3e2f4}
    .chat-time{white-space:nowrap;font-size:.66rem}
    .chat-left .chat-time{color:#9aa7b8}
    .chat-right .chat-time{color:#cadcf0}
    .chat-text{font-size:.86rem;line-height:1.45;white-space:pre-wrap;word-wrap:break-word}
    .chat-left .chat-text{color:#1f2937}
    .chat-right .chat-text{color:#fff}

    /* read receipt at bottom-right of own bubbles */
    .chat-receipt{display:flex;justify-content:flex-end;align-items:center;margin-top:.15rem;font-size:.72rem;line-height:1}
    .tick-single::after{content:'✓'}
    .tick-double::after{content:'✓✓';letter-spacing:-2px}
    .tick-sent,.tick-delivered{color:#bcd4ee}
    .tick-seen{color:#8ff0a4}

    /* attachment card */
    .chat-file{display:flex;align-items:center;gap:.55rem;background:#fff;border-radius:10px;padding:.45rem .55rem;margin-bottom:.4rem;text-decoration:none;max-width:230px}
    .chat-file .file-ic{width:34px;height:34px;border-radius:8px;background:#eaf1fb;color:#1b5a9e;display:flex;align-items:center;justify-content:center;font-size:1.05rem;flex-shrink:0}
    .chat-file .file-info{display:flex;flex-direction:column;min-width:0;line-height:1.25}
    .chat-file .file-name{display:block;font-weight:600;font-size:.76rem;color:#1f2937;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:160px}
    .chat-file .file-sub{display:block;font-size:.68rem;color:#8a97a8}

    .file-preview-chip{display:inline-flex;align-items:center;gap:.4rem;background:#f0f4ff;border:1px solid #c7d7fc;border-radius:8px;padding:.25rem .55rem;font-size:.75rem;color:#1e3a8a;margin:.25rem 0}
    .file-preview-chip .chip-remove{cursor:pointer;font-size:.8rem;color:#64748b;line-height:1}
    .file-preview-chip .chip-remove:hover{color:#ef4444}

    .flash-new{animation:flashFade .8s ease}
    @keyframes flashFade{from{background:rgba(255,235,120,.5)}to{}}

    /* Composer */
    .chat-composer{display:flex;align-items:center;gap:.5rem;border-top:1px solid #e6eaf0;padding:.6rem .7rem;background:#fff}
    .chat-attach-btn{cursor:pointer;font-size:1.2rem;color:#8a97a8;display:flex;align-items:center;justify-content:center;width:38px;height:38px;border-radius:50%;flex-shrink:0}
    .chat-attach-btn:hover{background:#eef2f7}
    .chat-input{flex:1;resize:none;border-radius:22px;border:1px solid #e2e8f0;background:#f4f6fa;padding:.55rem .95rem;font-size:.9rem;max-height:120px;line-height:1.4}
    .chat-input:focus{outline:none;border-color:#1b5a9e;background:#fff;box-shadow:0 0 0 2px rgba(27,90,158,.15)}
    .chat-send-btn{border:none;background:#1b5a9e;color:#fff;width:42px;height:42px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 2px 6px rgba(27,90,158,.35);font-size:1rem}
    .chat-send-btn[disabled]{opacity:.6;cursor:not-allowed}

    .visually-hidden{position:absolute!important;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
    .chat-send-toast{position:absolute;top:0;left:0;right:0;z-index:10;padding:.5rem .75rem;margin:.5rem;border-radius:12px;background:#004a93;color:#fff;font-size:.8rem;font-weight:500;text-align:center;box-shadow:0 2px 8px rgba(0,74,147,.35);animation:chatToastIn .25s ease}
    @keyframes chatToastIn{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}
    @media (max-width:575.98px){.chat-bubble{max-width:86%}.chat-container{padding:.75rem .6rem}}
  </style>

<div class="chat-wrapper" data-memo-id="{{ $id }}" data-type="{{ $type }}" data-user-type="{{ $user_type }}">
  <div class="chat-container" id="conversationScroll">

@forelse ($conversations as $msg)
    @php
        $msgTime = Carbon::parse($msg->created_date ?? 'now', 'UTC')->timezone($tz);
        $dateKey = $msgTime->toDateString();
        $label = $msgTime->isSameDay(Carbon::now($tz))
            ? 'Today'
            : ($msgTime->isSameDay(Carbon::yesterday($tz)) ? 'Yesterday' : $msgTime->format('D d M, Y'));
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
      <div class="chat-bubble" data-message-id="{{ data_get($msg, 'id', data_get($msg, 'pk', '')) }}">
        <div class="chat-head">
          <span class="chat-user">{{ $isOwn ? 'You' : $msg->display_name }}</span>
          <span class="chat-time">{{ $msgTime->format('d M Y, h:i A') }}</span>
        </div>

        @if ($hasAttachment)
          @php
            $fileName = basename($msg->doc_upload);
            $ext = strtoupper(pathinfo($fileName, PATHINFO_EXTENSION));
            $filePath = storage_path('app/public/' . $msg->doc_upload);
            $rawSize = @filesize($filePath);
            $fileSize = $rawSize !== false
                ? ($rawSize >= 1048576 ? round($rawSize / 1048576, 2) . ' MB' : round($rawSize / 1024, 1) . ' KB')
                : '';
          @endphp
          <a class="chat-file" href="{{ asset('storage/' . $msg->doc_upload) }}" target="_blank" rel="noopener">
            <span class="file-ic"><i class="bi bi-file-earmark-text-fill"></i></span>
            <span class="file-info">
              <span class="file-name">{{ $fileName }}</span>
              <span class="file-sub">{{ $fileSize !== '' ? $fileSize : $ext }}</span>
            </span>
          </a>
        @endif

        @if (trim((string) $msg->student_decip_incharge_msg) !== '')
          <div class="chat-text">{{ $msg->student_decip_incharge_msg }}</div>
        @endif

        @if ($isOwn)
          <div class="chat-receipt">
            @if ($isRead)
                <span class="tick tick-double tick-seen" title="Seen"></span>
            @elseif ($delivered)
                <span class="tick tick-double tick-delivered" title="Delivered"></span>
            @else
                <span class="tick tick-single tick-sent" title="Sent"></span>
            @endif
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

@php
    // Use the conversation's last status when available; fall back to the direct $noticeStatus
    // from the source record (passed by controller) — handles empty conversation correctly.
    $currentStatus    = $conversations->isNotEmpty()
        ? (int) ($conversations->last()->notice_status ?? 0)
        : (int) ($noticeStatus ?? 0);
    $currentStudentId = $conversations->isNotEmpty()
        ? ($conversations->first()->student_id ?? $studentPk ?? 0)
        : ($studentPk ?? 0);
@endphp

@if($currentStatus == 1)

    <form id="memo_notice_conversation"
          method="POST"
          enctype="multipart/form-data"
          action="{{ route('memo.notice.management.memo_notice_conversation_model') }}"
          class="chat-composer"
          novalidate
          onsubmit="return false;">

        @csrf

        <input type="hidden" name="memo_notice_id" value="{{ $id }}">
        <input type="hidden" name="type" value="{{ $type }}">
        <input type="hidden" name="user_type" value="{{ $user_type }}">

        @if ($user_type == 'student')
            <input type="hidden" name="created_by" value="{{ $currentStudentId }}">
            <input type="hidden" name="role_type" value="s">
        @else
            <input type="hidden" name="created_by" value="{{ auth()->user()->pk }}">
            <input type="hidden" name="role_type" value="f">
        @endif

        <label class="chat-attach-btn" for="memo_notice_attachment" title="Attach file" aria-label="Attach file"><i class="bi bi-paperclip"></i></label>
        <input id="memo_notice_attachment" type="file" name="document" hidden>

        <textarea name="student_decip_incharge_msg"
                  class="chat-input"
                  rows="1"
                  placeholder="Type your message..."
                  required></textarea>

        <button type="button" class="chat-send-btn" aria-label="Send">
            <span class="visually-hidden">Send</span><i class="bi bi-send-fill"></i>
        </button>
    </form>
    <div id="file-preview-area" style="padding:.15rem .75rem;background:#fff;"></div>

@elseif($currentStatus == 2)

    <div class="alert alert-warning mt-3 text-center">
        <strong>{{ $type == 'memo' ? 'Memo' : 'Notice' }} Closed:</strong>
        This {{ $type == 'memo' ? 'memo' : 'notice' }} has been closed. You cannot reply.
    </div>

@else

    <div class="alert alert-info mt-3 text-center">
        <strong>{{ $type == 'memo' ? 'Memo' : 'Notice' }} Not Started:</strong>
        Conversation is not yet started.
    </div>

@endif

</div>

<script>
    (function() {
        const root = document.currentScript ? document.currentScript.closest('.chat-wrapper') : null
                     || document.querySelector('.chat-wrapper');

        const chatBody = document.getElementById('chatBody');
        const csrfToken = '{{ csrf_token() }}';

        const scrollToBottom = () => {
            const c = document.querySelector('#conversationScroll');
            if (c) c.scrollTop = c.scrollHeight;
        };
        scrollToBottom();

        // ─── Prevent duplicate listener registration ───────────────────
        // jQuery re-executes scripts on each $.ajax load; guard against it
        if (document._memoNoticeListenersRegistered) {
            return; // listeners already on document, just scrolled above
        }
        document._memoNoticeListenersRegistered = true;

        // ─── Send lock ─────────────────────────────────────────────────
        let isSending = false;

        // ─── Helpers ───────────────────────────────────────────────────
        function getCurrentWrapper() {
            return document.querySelector('#chatBody .chat-wrapper');
        }

        function doSend(wrapper) {
            if (!wrapper || isSending) return;
            const form = wrapper.querySelector('#memo_notice_conversation');
            if (!form) return;
            const ta = form.querySelector('.chat-input');
            const sendBtn = form.querySelector('.chat-send-btn');
            const fileBtn = form.querySelector('#memo_notice_attachment');
            if (!ta.value.trim() && !(fileBtn && fileBtn.files && fileBtn.files.length)) return;

            // Block send if file input was cleared due to validation error
            if (fileBtn && fileBtn.files && fileBtn.files.length === 0 && !ta.value.trim()) return;

            isSending = true;
            if (sendBtn) sendBtn.disabled = true;

            const fd = new FormData(form);
            fetch(form.action, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken },
                body: fd
            }).then(r => {
                const ct = r.headers.get('content-type') || '';
                if (ct.includes('application/json')) return r.json();
                return r.text().then(t => ({ success: false, message: t || 'Request failed.' }));
            }).then(data => {
                const ok = data && data.success;
                if (ok) {
                    const hadAttachment = !!(fileBtn && fileBtn.files && fileBtn.files.length);
                    if (ta) { ta.value = ''; ta.style.height = 'auto'; }
                    if (fileBtn) fileBtn.value = '';
                    // Clear file preview chip
                    const previewArea = wrapper.querySelector('#file-preview-area');
                    if (previewArea) previewArea.innerHTML = '';
                    showSendSuccess(hadAttachment);
                    reloadConversation(true);
                } else {
                    const msg = (data && data.message) ? data.message : 'Failed to send message.';
                    alert(msg);
                }
            }).catch(() => {
                alert('Failed to send message.');
            }).finally(() => {
                isSending = false;
                if (sendBtn) sendBtn.disabled = false;
            });
        }

        function showSendSuccess(hadAttachment) {
            const container = chatBody || document.getElementById('chatBody');
            if (!container) return;
            const msg = hadAttachment ? 'Message and attachment sent.' : 'Message sent.';
            const el = document.createElement('div');
            el.className = 'chat-send-toast';
            el.setAttribute('role', 'status');
            el.textContent = msg;
            container.style.position = container.style.position || 'relative';
            container.insertBefore(el, container.firstChild);
            setTimeout(() => { if (el.parentNode) el.remove(); }, 2500);
        }

        function reloadConversation(flashNew) {
            // Always read current memoId/type/userType from live DOM, not stale closure
            const w = getCurrentWrapper();
            if (!w) return;
            const memoId   = w.dataset.memoId;
            const type     = w.dataset.type;
            const userType = w.dataset.userType;
            const target   = chatBody || document.getElementById('chatBody');
            if (!target || !memoId) return;

            const url = '/admin/memo-notice-management/get_conversation_model/'
                + encodeURIComponent(memoId) + '/' + encodeURIComponent(type) + '/' + encodeURIComponent(userType);

            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
              .then(r => r.text())
              .then(html => {
                  target.innerHTML = html;
                  if (flashNew) {
                      const last = target.querySelector('.chat-bubble:last-child');
                      if (last) last.classList.add('flash-new');
                  }
                  scrollToBottom();
              })
              .catch(() => {});
        }

        // ─── Event delegation (registered once) ───────────────────────
        document.addEventListener('submit', function(e) {
            if (!e.target.matches('#memo_notice_conversation')) return;
            e.preventDefault();
            const wrap = e.target.closest('.chat-wrapper');
            if (wrap) doSend(wrap);
        });
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.chat-send-btn')) return;
            const wrap = e.target.closest('.chat-wrapper');
            if (wrap && wrap.querySelector('#memo_notice_conversation')) doSend(wrap);
        });
        document.addEventListener('keydown', function(e) {
            if (e.key !== 'Enter' || e.shiftKey || !e.target.matches('.chat-input')) return;
            const wrap = e.target.closest('.chat-wrapper');
            if (wrap) { e.preventDefault(); doSend(wrap); }
        });
        document.addEventListener('input', function(e) {
            if (!e.target.matches('.chat-input')) return;
            const ta = e.target;
            ta.style.height = 'auto';
            ta.style.height = Math.min(ta.scrollHeight, 120) + 'px';
        });

        // ─── File preview chip + client-side validation ───────────────
        const ALLOWED_TYPES = ['image/jpeg','image/png','application/pdf'];
        const MAX_SIZE_BYTES = 2 * 1024 * 1024; // 2 MB

        document.addEventListener('change', function(e) {
            if (!e.target.matches('#memo_notice_attachment')) return;
            const file = e.target.files && e.target.files[0];
            const form = e.target.closest('form');
            const area = form && form.closest('.chat-wrapper') && form.closest('.chat-wrapper').querySelector('#file-preview-area');
            if (!area) return;
            if (!file) { area.innerHTML = ''; return; }

            // Validate type
            if (!ALLOWED_TYPES.includes(file.type)) {
                area.innerHTML = `<span class="file-preview-chip" style="border-color:#fca5a5;background:#fef2f2;color:#b91c1c;">
                    ⚠️ Only JPG, PNG, PDF allowed.
                    <span class="chip-remove" title="Remove">✕</span>
                </span>`;
                e.target.value = '';
                return;
            }
            // Validate size
            if (file.size > MAX_SIZE_BYTES) {
                const sizeMB = (file.size / 1048576).toFixed(1);
                area.innerHTML = `<span class="file-preview-chip" style="border-color:#fca5a5;background:#fef2f2;color:#b91c1c;">
                    ⚠️ File too large (${sizeMB} MB). Max 2 MB.
                    <span class="chip-remove" title="Remove">✕</span>
                </span>`;
                e.target.value = '';
                return;
            }

            const ext = file.name.split('.').pop().toUpperCase();
            const sizeLabel = file.size >= 1048576
                ? (file.size / 1048576).toFixed(1) + ' MB'
                : (file.size / 1024).toFixed(1) + ' KB';
            area.innerHTML = `<span class="file-preview-chip">
                <span>📄 ${escHtml(file.name)}</span>
                <span style="color:#64748b;">${ext} · ${sizeLabel}</span>
                <span class="chip-remove" title="Remove">✕</span>
            </span>`;
        });

        document.addEventListener('click', function(e) {
            if (!e.target.matches('.chip-remove')) return;
            const form = e.target.closest('form');
            if (!form) return;
            const fi = form.querySelector('#memo_notice_attachment');
            if (fi) fi.value = '';
            const area = form.closest('.chat-wrapper') ? form.closest('.chat-wrapper').querySelector('#file-preview-area') : null;
            if (area) area.innerHTML = '';
        });

        function escHtml(str) {
            return str.replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        }

        // Clear send lock when offcanvas closes (safety reset)
        const offcanvasEl = document.getElementById('chatOffcanvas');
        if (offcanvasEl) {
            offcanvasEl.addEventListener('hide.bs.offcanvas', () => { isSending = false; });
        }

    })();
</script>
