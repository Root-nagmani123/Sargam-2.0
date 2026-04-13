@extends('admin.layouts.master')

@section('title', 'Birthday Wishes')

@section('content')
<style>
.birthday-wish-page .birthday-person-card {
    border: 1px solid rgba(var(--bs-primary-rgb), 0.15);
    border-radius: 1rem;
    background: linear-gradient(180deg, #fff 0%, rgba(248,250,252,0.8) 100%);
    transition: all 0.2s ease;
    overflow: hidden;
}
.birthday-wish-page .birthday-person-card:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
    transform: translateY(-2px);
}
.birthday-wish-page .birthday-person-card.selected {
    border-color: var(--bs-primary);
    background: linear-gradient(180deg, rgba(var(--bs-primary-rgb), 0.06) 0%, rgba(var(--bs-primary-rgb), 0.02) 100%);
    box-shadow: 0 0 0 2px rgba(var(--bs-primary-rgb), 0.2);
}
.birthday-wish-page .person-avatar {
    width: 3rem;
    height: 3rem;
    font-size: 1.1rem;
    border-radius: 50%;
}
.birthday-wish-page .template-card {
    cursor: pointer;
    border: 2px solid var(--bs-border-color);
    border-radius: 0.75rem;
    padding: 1rem;
    transition: all 0.15s ease;
}
.birthday-wish-page .template-card:hover {
    border-color: var(--bs-primary);
    background: rgba(var(--bs-primary-rgb), 0.04);
}
.birthday-wish-page .template-card.active {
    border-color: var(--bs-primary);
    background: rgba(var(--bs-primary-rgb), 0.08);
}
.birthday-wish-page .channel-btn {
    border: 2px solid var(--bs-border-color);
    border-radius: 0.75rem;
    padding: 0.65rem 1.25rem;
    cursor: pointer;
    transition: all 0.15s ease;
    background: var(--bs-body-bg);
}
.birthday-wish-page .channel-btn:hover { border-color: var(--bs-primary); }
.birthday-wish-page .channel-btn.active { border-color: var(--bs-primary); background: rgba(var(--bs-primary-rgb), 0.08); }
</style>

@php
$user = Auth::user();
$senderName = $user ? ($user->first_name ?? $user->name ?? 'User') : 'User';
@endphp

<div class="container-fluid px-3 px-lg-4 birthday-wish-page">
    <x-breadcrum title="Birthday Wishes" />

    <div class="row g-4">
        {{-- Left: Today's Birthdays --}}
        <div class="col-lg-5 col-xl-4">
            <div class="card rounded-4 border-0 shadow-sm">
                <div class="card-header bg-transparent border-bottom-0 py-3 px-4">
                    <h6 class="mb-0 fw-semibold d-flex align-items-center gap-2">
                        <span class="material-icons material-symbols-rounded text-primary">cake</span>
                        Today's Birthdays
                        <span class="badge rounded-pill text-bg-primary ms-auto">{{ $todayBirthdays->count() }}</span>
                    </h6>
                </div>
                <div class="card-body p-3" style="max-height: 65vh; overflow-y: auto;">
                    @if($todayBirthdays->isEmpty())
                    <div class="text-center py-5 text-body-secondary">
                        <span class="material-icons material-symbols-rounded d-block mb-2" style="font-size:3rem; opacity:0.3;">card_giftcard</span>
                        <p class="small mb-0">No birthdays today.</p>
                    </div>
                    @else
                    <div class="d-grid gap-2">
                        @foreach($todayBirthdays as $person)
                        @php
                            $avClasses = ['text-bg-primary', 'text-bg-info', 'text-bg-success', 'text-bg-warning', 'text-bg-danger'];
                            $avClass = $avClasses[$loop->index % count($avClasses)];
                            $photo = !empty($person->profile_picture) ? asset('storage/' . $person->profile_picture) : null;
                            $fullName = trim(($person->first_name ?? '') . ' ' . ($person->last_name ?? ''));
                        @endphp
                        <div class="birthday-person-card p-3" role="button"
                             data-name="{{ $fullName }}"
                             data-email="{{ $person->email ?? '' }}"
                             data-mobile="{{ $person->mobile ?? '' }}"
                             data-pk="{{ $person->pk }}">
                            <div class="d-flex align-items-center gap-3">
                                @if($photo)
                                    <img src="{{ $photo }}" alt="" class="person-avatar object-fit-cover flex-shrink-0">
                                @else
                                    <div class="person-avatar {{ $avClass }} fw-semibold d-inline-flex align-items-center justify-content-center flex-shrink-0">
                                        {{ strtoupper(substr($person->first_name, 0, 1)) }}
                                    </div>
                                @endif
                                <div class="min-w-0 flex-grow-1">
                                    <div class="fw-semibold text-truncate">{{ $fullName }}</div>
                                    <div class="text-body-secondary small text-truncate">{{ $person->designation_name ?? '' }}</div>
                                    <div class="d-flex gap-3 mt-1 small text-body-secondary">
                                        @if($person->email)<span class="text-truncate"><span class="material-icons material-symbols-rounded align-middle" style="font-size:14px;">mail</span> {{ $person->email }}</span>@endif
                                        @if($person->mobile)<span class="text-truncate"><span class="material-icons material-symbols-rounded align-middle" style="font-size:14px;">call</span> {{ $person->mobile }}</span>@endif
                                    </div>
                                </div>
                                <div class="form-check flex-shrink-0">
                                    <input class="form-check-input person-checkbox" type="checkbox"
                                           data-name="{{ $fullName }}"
                                           data-email="{{ $person->email ?? '' }}"
                                           data-mobile="{{ $person->mobile ?? '' }}"
                                           data-pk="{{ $person->pk }}">
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <button class="btn btn-sm btn-outline-primary rounded-pill w-100 mt-3" id="btn-select-all">Select All</button>
                    @endif
                </div>
            </div>
        </div>

        {{-- Right: Compose Message --}}
        <div class="col-lg-7 col-xl-8">
            <div class="card rounded-4 border-0 shadow-sm">
                <div class="card-header bg-transparent border-bottom-0 py-3 px-4">
                    <h6 class="mb-0 fw-semibold d-flex align-items-center gap-2">
                        <span class="material-icons material-symbols-rounded text-primary">edit_note</span>
                        Compose Message
                    </h6>
                </div>
                <div class="card-body px-4 pb-4">
                    {{-- Templates --}}
                    <label class="form-label fw-semibold small mb-2">Choose a Template</label>
                    <div class="row g-2 mb-4">
                        <div class="col-md-3 col-6">
                            <div class="template-card active text-center" data-template="formal">
                                <span class="material-icons material-symbols-rounded text-primary mb-1" style="font-size:1.5rem;">description</span>
                                <div class="fw-semibold small">Formal</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="template-card text-center" data-template="casual">
                                <span class="material-icons material-symbols-rounded text-warning mb-1" style="font-size:1.5rem;">sentiment_satisfied</span>
                                <div class="fw-semibold small">Casual</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="template-card text-center" data-template="professional">
                                <span class="material-icons material-symbols-rounded text-success mb-1" style="font-size:1.5rem;">business</span>
                                <div class="fw-semibold small">Professional</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="template-card text-center" data-template="custom">
                                <span class="material-icons material-symbols-rounded text-secondary mb-1" style="font-size:1.5rem;">edit</span>
                                <div class="fw-semibold small">Custom</div>
                            </div>
                        </div>
                    </div>

                    {{-- Subject & Message --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Subject <small class="text-muted">(for Email)</small></label>
                        <input type="text" class="form-control" id="compose-subject" value="Happy Birthday!">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">
                            Message
                            <small class="text-muted">Use <code>{name}</code> for full name, <code>{first_name}</code> for first name</small>
                        </label>
                        <textarea class="form-control" id="compose-message" rows="7"></textarea>
                    </div>

                    {{-- Channel Selection --}}
                    <label class="form-label fw-semibold small mb-2">Send via</label>
                    <div class="d-flex gap-3 mb-4 flex-wrap">
                        <label class="channel-btn d-flex align-items-center gap-2 active" id="channel-email-btn">
                            <input type="checkbox" class="form-check-input" id="compose-via-email" checked>
                            <span class="material-icons material-symbols-rounded text-primary" style="font-size:20px;">mail</span>
                            <span class="fw-semibold small">Email</span>
                        </label>
                        <label class="channel-btn d-flex align-items-center gap-2" id="channel-whatsapp-btn">
                            <input type="checkbox" class="form-check-input" id="compose-via-whatsapp">
                            <span class="material-icons material-symbols-rounded text-success" style="font-size:20px;">chat</span>
                            <span class="fw-semibold small">WhatsApp</span>
                        </label>
                    </div>

                    {{-- Send Button --}}
                    <div class="d-flex gap-3">
                        <button class="btn btn-primary rounded-pill px-5 d-flex align-items-center gap-2" id="btn-send-compose">
                            <span class="material-icons material-symbols-rounded" style="font-size:18px;">send</span>
                            Send to Selected (<span id="selected-count">0</span>)
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function birthdayWishParseJsonResponse(response) {
    var ct = response.headers.get('content-type') || '';
    if (ct.indexOf('application/json') === -1) {
        return response.text().then(function () {
            throw new Error('Session may have expired or the server returned an error page. Please refresh and try again.');
        });
    }
    return response.json();
}
(function() {
    var senderName = @json($senderName);

    var templates = {
        formal: "Dear {name},\n\nOn the occasion of your birthday, I extend my heartfelt wishes for a wonderful year ahead. May this special day bring you joy, success, and good health.\n\nWarm regards,\n" + senderName,
        casual: "Hey {first_name}! 🎂🎉\n\nWishing you a fantastic birthday! Hope your day is filled with joy, laughter, and all things wonderful. Have an amazing year ahead!\n\nCheers,\n" + senderName,
        professional: "Dear {name},\n\nWishing you a very Happy Birthday! May this new year of your life bring you continued success and fulfilment in all your endeavours.\n\nBest wishes,\n" + senderName,
        custom: "Dear {name},\n\n\n\nRegards,\n" + senderName
    };

    // Init message
    document.getElementById('compose-message').value = templates.formal;

    // Template selection
    document.querySelectorAll('.template-card').forEach(function(card) {
        card.addEventListener('click', function() {
            document.querySelectorAll('.template-card').forEach(function(c) { c.classList.remove('active'); });
            card.classList.add('active');
            var tpl = card.dataset.template;
            document.getElementById('compose-message').value = templates[tpl] || templates.custom;
        });
    });

    // Channel toggle styling
    ['compose-via-email', 'compose-via-whatsapp'].forEach(function(id) {
        var cb = document.getElementById(id);
        if (cb) {
            cb.addEventListener('change', function() {
                this.closest('.channel-btn').classList.toggle('active', this.checked);
            });
        }
    });

    // Person card click = toggle checkbox
    document.querySelectorAll('.birthday-person-card').forEach(function(card) {
        card.addEventListener('click', function(e) {
            if (e.target.classList.contains('form-check-input')) return;
            var cb = card.querySelector('.person-checkbox');
            if (cb) { cb.checked = !cb.checked; cb.dispatchEvent(new Event('change')); }
        });
    });

    // Checkbox change → update card styling & count
    document.querySelectorAll('.person-checkbox').forEach(function(cb) {
        cb.addEventListener('change', function() {
            var card = cb.closest('.birthday-person-card');
            if (card) card.classList.toggle('selected', cb.checked);
            updateSelectedCount();
        });
    });

    // Select all
    var selectAllBtn = document.getElementById('btn-select-all');
    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', function() {
            var boxes = document.querySelectorAll('.person-checkbox');
            var allChecked = Array.from(boxes).every(function(b) { return b.checked; });
            boxes.forEach(function(b) {
                b.checked = !allChecked;
                b.dispatchEvent(new Event('change'));
            });
            selectAllBtn.textContent = allChecked ? 'Select All' : 'Deselect All';
        });
    }

    function updateSelectedCount() {
        var count = document.querySelectorAll('.person-checkbox:checked').length;
        document.getElementById('selected-count').textContent = count;
    }

    function getSelectedRecipients() {
        var recipients = [];
        document.querySelectorAll('.person-checkbox:checked').forEach(function(cb) {
            recipients.push({
                name: cb.dataset.name,
                email: cb.dataset.email,
                mobile: cb.dataset.mobile,
                employee_pk: cb.dataset.pk
            });
        });
        return recipients;
    }

    // Send
    var sendBtn = document.getElementById('btn-send-compose');
    if (sendBtn) {
        sendBtn.addEventListener('click', function() {
            var recipients = getSelectedRecipients();
            if (recipients.length === 0) { alert('Please select at least one person.'); return; }

            var message = document.getElementById('compose-message').value.trim();
            var subject = document.getElementById('compose-subject').value.trim();
            var emailCh = document.getElementById('compose-via-email');
            var waCh = document.getElementById('compose-via-whatsapp');
            var sendEmail = emailCh ? emailCh.checked : false;
            var sendWhatsapp = waCh ? waCh.checked : false;

            if (!message) { alert('Please enter a message.'); return; }
            if (!sendEmail && !sendWhatsapp) { alert('Please select at least one channel.'); return; }

            // Send emails via server
            if (sendEmail) {
                var emailRecipients = recipients.filter(function(r) { return r.email; });
                if (emailRecipients.length === 0 && !sendWhatsapp) {
                    alert('No email addresses available for selected recipients.');
                    return;
                }

                if (emailRecipients.length > 0) {
                    sendBtn.disabled = true;
                    sendBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Sending emails...';
                    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

                    fetch('{{ route("admin.birthday-wish.send-bulk-email") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            recipients: emailRecipients.map(function(r) { return { email: r.email, name: r.name, employee_pk: r.employee_pk }; }),
                            subject: subject,
                            message_template: message
                        })
                    })
                    .then(function(r) { return birthdayWishParseJsonResponse(r); })
                    .then(function(data) {
                        if (data.success) {
                            showToast(data.message, 'success');
                        } else {
                            alert('Error: ' + (data.error || 'Unknown'));
                        }
                    })
                    .catch(function(err) { alert('Error: ' + err.message); })
                    .finally(function() {
                        sendBtn.disabled = false;
                        sendBtn.innerHTML = '<span class="material-icons material-symbols-rounded" style="font-size:18px;">send</span> Send to Selected (<span id="selected-count">0</span>)';
                        updateSelectedCount();
                    });
                }
            }

            // Open WhatsApp for each recipient
            if (sendWhatsapp) {
                var whatsappRecipients = recipients.filter(function(r) { return r.mobile; });
                if (whatsappRecipients.length === 0) {
                    alert('No phone numbers available for WhatsApp.');
                    return;
                }
                // Send in-app notifications for WhatsApp-only recipients (those not already notified via email)
                var whatsappOnlyPks = whatsappRecipients
                    .filter(function(r) { return r.employee_pk && (!sendEmail || !r.email); })
                    .map(function(r) { return parseInt(r.employee_pk); })
                    .filter(function(pk) { return !isNaN(pk) && pk > 0; });
                if (whatsappOnlyPks.length > 0) {
                    var csrfToken2 = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
                    fetch('{{ route("admin.birthday-wish.send-notification") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken2,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({ employee_pks: whatsappOnlyPks })
                    }).then(function(r) { return birthdayWishParseJsonResponse(r); }).catch(function() {});
                }
                whatsappRecipients.forEach(function(r, idx) {
                    var personalMsg = message.replace(/\{name\}/g, r.name).replace(/\{first_name\}/g, r.name.split(' ')[0]);
                    var phone = r.mobile.replace(/[^0-9]/g, '');
                    if (phone.length === 10) phone = '91' + phone;
                    setTimeout(function() {
                        window.open('https://wa.me/' + phone + '?text=' + encodeURIComponent(personalMsg), '_blank');
                    }, idx * 800);
                });
            }
        });
    }

    function showToast(msg, type) {
        var toastHtml = '<div class="toast align-items-center text-bg-' + (type || 'primary') + ' border-0 show" role="alert" style="position:fixed;top:20px;right:20px;z-index:9999;">' +
            '<div class="d-flex"><div class="toast-body">' + msg + '</div>' +
            '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div></div>';
        var div = document.createElement('div');
        div.innerHTML = toastHtml;
        document.body.appendChild(div);
        setTimeout(function() { div.remove(); }, 5000);
    }
})();
</script>
@endpush
@endsection
