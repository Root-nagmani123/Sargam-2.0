{{-- Modal: who wished you today + reply (opened from dashboard or birthday-wish page) --}}
<div class="modal fade" id="birthdayWishesReceivedModal" tabindex="-1" aria-labelledby="birthdayWishesReceivedModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-semibold" id="birthdayWishesReceivedModalLabel">Birthday wishes today</h5>
                    <p class="text-muted small mb-0">People who wished you — you can send a thank-you reply.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2">
                <div id="birthday-wishes-loading" class="text-center py-4 text-muted d-none">
                    <div class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></div>
                    <span class="ms-2">Loading…</span>
                </div>
                <div id="birthday-wishes-error" class="alert alert-danger d-none" role="alert"></div>
                <div id="birthday-wishes-list" class="vstack gap-2"></div>
                <div id="birthday-wishes-empty" class="text-center text-muted py-4 d-none">No wishes found for today.</div>

                <div id="birthday-wishes-reply-panel" class="d-none mt-4 pt-3 border-top">
                    <label class="form-label fw-semibold" for="birthday-reply-message">Reply to <span id="reply-to-name"
                            class="text-primary"></span></label>
                    <textarea id="birthday-reply-message" class="form-control rounded-3" rows="4" maxlength="2000"
                        placeholder="Thank them for their wish…"></textarea>
                    <input type="hidden" id="birthday-reply-notification-pk" value="">
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <button type="button" class="btn btn-primary rounded-pill px-4" id="birthday-reply-send">
                            Send reply
                        </button>
                        <button type="button" class="btn btn-outline-secondary rounded-pill" id="birthday-reply-cancel">
                            Cancel
                        </button>
                    </div>
                    <div id="birthday-reply-feedback" class="small mt-2" aria-live="polite"></div>
                </div>
            </div>
        </div>
    </div>
</div>

@once('birthday-wishes-received-modal-script')
@push('scripts')
<script>
(function () {
    var listUrl = @json(route('admin.birthday-wish.my-wishes-today'));
    var replyUrl = @json(route('admin.birthday-wish.reply'));
    var csrf = document.querySelector('meta[name="csrf-token"]');
    var csrfToken = csrf ? csrf.getAttribute('content') : '';

    function esc(s) {
        if (!s) return '';
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    function formatTime(iso) {
        if (!iso) return '';
        try {
            var d = new Date(iso);
            if (isNaN(d.getTime())) return '';
            return d.toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
        } catch (e) { return ''; }
    }

    function loadWishes() {
        var loading = document.getElementById('birthday-wishes-loading');
        var errEl = document.getElementById('birthday-wishes-error');
        var listEl = document.getElementById('birthday-wishes-list');
        var emptyEl = document.getElementById('birthday-wishes-empty');
        if (!listEl) return;

        loading.classList.remove('d-none');
        errEl.classList.add('d-none');
        errEl.textContent = '';
        listEl.innerHTML = '';
        emptyEl.classList.add('d-none');

        fetch(listUrl, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            loading.classList.add('d-none');
            if (!data.success) {
                errEl.textContent = data.error || 'Could not load wishes.';
                errEl.classList.remove('d-none');
                return;
            }
            var wishes = data.wishes || [];
            if (wishes.length === 0) {
                emptyEl.classList.remove('d-none');
                return;
            }
            wishes.forEach(function (w) {
                var pk = w.pk;
                var name = (w.sender_name || '').trim() || 'Colleague';
                var sid = w.sender_user_id;
                var canReply = sid != null && String(sid) !== '' && parseInt(sid, 10) > 0;
                var alreadyReplied = !!w.already_replied;
                var msg = w.message || '';
                var t = formatTime(w.created_at);
                var actionHtml;
                if (alreadyReplied) {
                    actionHtml = '<span class="badge bg-secondary rounded-pill birthday-wish-replied-badge">Reply sent</span>';
                } else if (canReply) {
                    actionHtml = '<button type="button" class="btn btn-sm btn-outline-primary rounded-pill birthday-wish-reply-btn" data-pk="' + esc(String(pk)) + '" data-name="' + esc(name) + '">Reply</button>';
                } else {
                    actionHtml = '<span class="small text-muted">Reply unavailable</span>';
                }
                var card = document.createElement('div');
                card.className = 'card border shadow-sm rounded-3';
                card.setAttribute('data-wish-pk', String(pk));
                card.innerHTML =
                    '<div class="card-body py-3">' +
                    '<div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">' +
                    '<div class="min-w-0">' +
                    '<div class="fw-semibold">' + esc(name) + '</div>' +
                    (t ? '<div class="small text-muted">' + esc(t) + '</div>' : '') +
                    '</div>' +
                    '<div class="flex-shrink-0 birthday-wish-action-slot">' + actionHtml + '</div>' +
                    '</div>' +
                    '<p class="mb-0 mt-2 small text-body-secondary">' + esc(msg) + '</p>' +
                    '</div>';
                listEl.appendChild(card);
            });

            listEl.querySelectorAll('.birthday-wish-reply-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    openReplyPanel(btn.getAttribute('data-pk'), btn.getAttribute('data-name'));
                });
            });
        })
        .catch(function () {
            loading.classList.add('d-none');
            errEl.textContent = 'Network error. Please try again.';
            errEl.classList.remove('d-none');
        });
    }

    function hideReplyPanel() {
        var panel = document.getElementById('birthday-wishes-reply-panel');
        var ta = document.getElementById('birthday-reply-message');
        var hid = document.getElementById('birthday-reply-notification-pk');
        var fb = document.getElementById('birthday-reply-feedback');
        if (panel) panel.classList.add('d-none');
        if (ta) ta.value = '';
        if (hid) hid.value = '';
        if (fb) fb.textContent = '';
    }

    function openReplyPanel(notificationPk, senderName) {
        var panel = document.getElementById('birthday-wishes-reply-panel');
        var nameEl = document.getElementById('reply-to-name');
        var hid = document.getElementById('birthday-reply-notification-pk');
        var fb = document.getElementById('birthday-reply-feedback');
        if (!panel || !hid) return;
        hid.value = notificationPk || '';
        if (nameEl) nameEl.textContent = senderName || '';
        if (fb) fb.textContent = '';
        panel.classList.remove('d-none');
        var ta = document.getElementById('birthday-reply-message');
        if (ta) { ta.focus(); try { ta.scrollIntoView({ block: 'nearest', behavior: 'smooth' }); } catch (e) {} }
    }

    var modalEl = document.getElementById('birthdayWishesReceivedModal');
    if (modalEl) {
        modalEl.addEventListener('show.bs.modal', function () {
            hideReplyPanel();
            loadWishes();
        });
        modalEl.addEventListener('hidden.bs.modal', function () {
            hideReplyPanel();
        });
    }

    var cancelReply = document.getElementById('birthday-reply-cancel');
    if (cancelReply) cancelReply.addEventListener('click', hideReplyPanel);

    var sendReply = document.getElementById('birthday-reply-send');
    if (sendReply) {
        sendReply.addEventListener('click', function () {
            var hid = document.getElementById('birthday-reply-notification-pk');
            var ta = document.getElementById('birthday-reply-message');
            var fb = document.getElementById('birthday-reply-feedback');
            var pk = hid ? hid.value.trim() : '';
            var msg = ta ? ta.value.trim() : '';
            if (!pk) { if (fb) fb.innerHTML = '<span class="text-danger">Missing wish reference.</span>'; return; }
            if (!msg) { if (fb) fb.innerHTML = '<span class="text-danger">Please enter a message.</span>'; return; }

            sendReply.disabled = true;
            if (fb) fb.textContent = 'Sending…';

            fetch(replyUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ notification_pk: parseInt(pk, 10), message: msg })
            })
            .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, data: j }; }); })
            .then(function (res) {
                if (res.ok && res.data && res.data.success) {
                    if (fb) fb.innerHTML = '<span class="text-success">' + esc(res.data.message || 'Reply sent.') + '</span>';
                    if (ta) ta.value = '';
                    var card = document.querySelector('[data-wish-pk="' + String(pk) + '"]');
                    if (card) {
                        var slot = card.querySelector('.birthday-wish-action-slot');
                        if (slot) {
                            slot.innerHTML = '<span class="badge bg-secondary rounded-pill birthday-wish-replied-badge">Reply sent</span>';
                        }
                    }
                    hideReplyPanel();
                    loadWishes();
                } else {
                    var err = (res.data && res.data.error) ? res.data.error : 'Could not send reply.';
                    if (fb) fb.innerHTML = '<span class="text-danger">' + esc(err) + '</span>';
                    if (res.data && res.data.error && /already replied/i.test(String(res.data.error))) {
                        hideReplyPanel();
                        loadWishes();
                    }
                }
            })
            .catch(function () {
                if (fb) fb.innerHTML = '<span class="text-danger">Network error.</span>';
            })
            .finally(function () { sendReply.disabled = false; });
        });
    }
})();
</script>
@endpush
@endonce
