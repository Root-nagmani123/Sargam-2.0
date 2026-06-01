<div class="modal fade" id="customWishModal" tabindex="-1" aria-labelledby="customWishModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered dashboard-wish-modal-dialog">
        <div class="modal-content dashboard-wish-modal">
            <div class="modal-header dashboard-wish-modal__header">
                <h5 class="modal-title dashboard-wish-modal__title mb-0" id="customWishModalLabel">Wish on their birthday</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <hr class="dashboard-wish-modal__divider">
            <div class="modal-body dashboard-wish-modal__body">
                <input type="hidden" id="wish-recipient-email">
                <input type="hidden" id="wish-recipient-mobile">
                <input type="hidden" id="wish-modal-mode" value="birthday">
                <p class="dashboard-wish-intro mb-0" id="wish-modal-intro-birthday">
                    Wish <input type="text" class="dashboard-wish-name-inline" id="wish-recipient-name" readonly
                        aria-label="Recipient name" size="16"> on the occasion of their birthday.
                </p>
                <p class="dashboard-wish-intro mb-0 d-none" id="wish-modal-intro-reply">
                    Your reply to <input type="text" class="dashboard-wish-name-inline" id="wish-reply-name-inline" readonly
                        aria-label="Recipient name" size="16"> for their birthday wish.
                </p>
                <div class="dashboard-wish-options row g-3 mt-3" id="wish-modal-extra">
                    <div class="col-sm-6">
                        <label class="form-label" for="wish-template-select">Message template</label>
                        <select class="form-select" id="wish-template-select">
                            <option value="formal">Formal Birthday Wish</option>
                            <option value="casual">Casual Birthday Wish</option>
                            <option value="professional">Professional Birthday Wish</option>
                            <option value="custom">Write Custom Message</option>
                        </select>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="wish-subject">Email subject</label>
                        <input type="text" class="form-control" id="wish-subject" value="Happy Birthday!">
                    </div>
                </div>
                <div class="mt-4" id="wish-modal-message-wrap">
                    <label class="form-label dashboard-wish-message-label d-block" for="wish-message" id="wish-message-label">Your message</label>
                    <textarea class="form-control dashboard-wish-textarea" id="wish-message" rows="7"
                        placeholder="Write your birthday wish here…"></textarea>
                </div>
                <div class="d-flex flex-wrap align-items-center dashboard-wish-channels mt-4 opacity-50 pe-none"
                    id="wish-modal-channels" aria-hidden="true" title="Temporarily unavailable">
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" id="send-via-whatsapp" disabled>
                        <label class="form-check-label text-body-secondary" for="send-via-whatsapp">Via WhatsApp</label>
                    </div>
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" id="send-via-email" disabled>
                        <label class="form-check-label text-body-secondary" for="send-via-email">Via Email</label>
                    </div>
                </div>
                <p class="small text-body-secondary mb-0 mt-2" id="wish-modal-hint">
                    <i class="bi bi-bell me-1" aria-hidden="true"></i>Send delivers an in-app notification with your message.
                </p>
            </div>
            <div class="modal-footer dashboard-wish-modal__footer d-flex justify-content-end border-0">
                <button type="button" class="btn dashboard-wish-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn dashboard-wish-btn-send" id="btn-send-wish">Send</button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<link rel="stylesheet" href="{{ asset('admin_assets/css/dashboard-wish-modal.css') }}?v=2">
@endpush

@push('scripts')
<script>
(function() {
    const templates = {
        formal: function(name) {
            return "Dear " + name + ",\n\nOn the occasion of your birthday, I extend my heartfelt wishes for a wonderful year ahead.\n\nWarm regards,";
        },
        casual: function(name) {
            return "Hey " + name + "! 🎂🎉\n\nWishing you a fantastic birthday!\n\nCheers!";
        },
        professional: function(name) {
            return "Dear " + name + ",\n\nWishing you a very Happy Birthday!\n\nBest wishes,";
        },
        custom: function(name) {
            return "Dear " + name + ",\n\n";
        }
    };
    const replyTemplate = function(name) {
        return "Dear " + name + ",\n\nThank you so much for your lovely birthday wishes! I truly appreciate your thoughtfulness.\n\nWarm regards,";
    };

    var currentRecipient = { mode: 'birthday' };
    var modalEl = document.getElementById('customWishModal');

    function setNameFieldSize(input, name) {
        if (!input) return;
        input.value = name || '';
        input.size = Math.max(4, Math.min(28, (name || '').length + 1));
    }

    function setWishModalMode(mode, name) {
        var isReply = mode === 'reply';
        currentRecipient.mode = mode;

        document.getElementById('wish-modal-mode').value = mode;
        document.getElementById('customWishModalLabel').textContent = isReply ? 'Reply to birthday wish' : 'Wish on their birthday';

        var introBirthday = document.getElementById('wish-modal-intro-birthday');
        var introReply = document.getElementById('wish-modal-intro-reply');
        if (introBirthday) introBirthday.classList.toggle('d-none', isReply);
        if (introReply) introReply.classList.toggle('d-none', !isReply);

        var extra = document.getElementById('wish-modal-extra');
        var channels = document.getElementById('wish-modal-channels');
        if (extra) extra.classList.toggle('d-none', isReply);
        if (channels) channels.classList.toggle('d-none', isReply);

        var messageLabel = document.getElementById('wish-message-label');
        var messageField = document.getElementById('wish-message');
        if (messageLabel) messageLabel.textContent = isReply ? 'Your reply' : 'Your message';
        if (messageField) {
            messageField.placeholder = isReply ? 'Write your thank-you reply…' : 'Write your birthday wish here…';
        }

        setNameFieldSize(document.getElementById('wish-recipient-name'), name);
        setNameFieldSize(document.getElementById('wish-reply-name-inline'), name);
    }

    function openWishModal(recipient, mode) {
        currentRecipient = Object.assign({}, recipient, { mode: mode });
        setWishModalMode(mode, currentRecipient.name);

        document.getElementById('wish-recipient-email').value = currentRecipient.email || '';
        document.getElementById('wish-recipient-mobile').value = currentRecipient.mobile || '';

        if (mode === 'reply') {
            document.getElementById('wish-message').value = replyTemplate(currentRecipient.name || '');
            document.getElementById('wish-subject').value = 'Thank you for the birthday wishes!';
        } else {
            document.getElementById('wish-template-select').value = 'formal';
            document.getElementById('wish-subject').value = 'Happy Birthday ' + (currentRecipient.name || '') + '!';
            document.getElementById('wish-message').value = templates.formal(currentRecipient.name || '');
        }

        if (modalEl) {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
    }

    document.addEventListener('click', function(e) {
        var replyBtn = e.target.closest('.btn-wish-reply');
        if (replyBtn) {
            e.preventDefault();
            e.stopPropagation();
            openWishModal({
                name: replyBtn.dataset.name || '',
                email: replyBtn.dataset.email || '',
                mobile: replyBtn.dataset.mobile || '',
                employee_pk: replyBtn.dataset.pk || ''
            }, 'reply');
            return;
        }

        var btn = e.target.closest('.btn-custom-wish');
        if (!btn) return;
        openWishModal({
            name: btn.dataset.name || '',
            email: btn.dataset.email || '',
            mobile: btn.dataset.mobile || '',
            employee_pk: btn.dataset.pk || ''
        }, 'birthday');
    });

    var templateSelect = document.getElementById('wish-template-select');
    if (templateSelect) {
        templateSelect.addEventListener('change', function() {
            if (currentRecipient.mode === 'reply') return;
            var name = currentRecipient.name || '';
            var tpl = templates[this.value] || templates.custom;
            document.getElementById('wish-message').value = tpl(name);
        });
    }

    var sendBtn = document.getElementById('btn-send-wish');
    if (sendBtn) {
        sendBtn.addEventListener('click', function() {
            var message = document.getElementById('wish-message').value.trim();
            var subject = document.getElementById('wish-subject').value.trim();
            var isReply = currentRecipient.mode === 'reply';
            if (!message) {
                alert('Please enter a message.');
                return;
            }
            if (!currentRecipient.employee_pk) {
                alert('Could not identify the recipient. Please try again.');
                return;
            }
            var defaultTitle = isReply
                ? 'Thank you for the birthday wishes!'
                : ('Happy Birthday ' + (currentRecipient.name || '') + '!');
            var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            sendBtn.disabled = true;
            sendBtn.textContent = 'Sending...';
            fetch('{{ route("admin.birthday-wish.send-notification") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    employee_pks: [parseInt(currentRecipient.employee_pk, 10)],
                    message: message,
                    title: subject || defaultTitle
                })
            }).then(function(r) { return r.json(); }).then(function(data) {
                if (data.success) {
                    bootstrap.Modal.getInstance(modalEl).hide();
                    alert(data.message || (isReply ? 'Reply sent!' : 'Birthday wish notification sent!'));
                } else {
                    alert('Failed to send notification: ' + (data.error || 'Unknown error'));
                }
            }).catch(function(err) {
                alert('Error sending notification: ' + (err.message || 'Unknown error'));
            }).finally(function() {
                sendBtn.disabled = false;
                sendBtn.textContent = 'Send';
            });
        });
    }
})();
</script>
@endpush
