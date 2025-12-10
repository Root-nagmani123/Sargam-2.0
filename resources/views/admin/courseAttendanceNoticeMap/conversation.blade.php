@extends('admin.layouts.master')

@section('title', 'Notice Conversation - Sargam | Lal Bahadur Shastri National Academy of Administration')

@section('setup_content')
<style>
/* Container */
.messages {
    padding: 10px;
}

/* Common bubble styling */
.message-row {
    display: flex;
    align-items: flex-end;
}

.message-bubble {
    max-width: 70%;
    padding: 10px 14px;
    border-radius: 12px;
    position: relative;
    font-size: 14px;
    line-height: 1.4;
}

/* Student (left - grey bubble like WhatsApp incoming) */
.from-student .message-bubble {
    background: #ffffff;
    border: 1px solid #ececec;
    border-top-left-radius: 0;
    color: #222;
}

/* Staff (right - green bubble like WhatsApp outgoing) */
.from-staff {
    justify-content: flex-end;
}

.from-staff .message-bubble {
    background: #d9fdd3;
    border-top-right-radius: 0;
    color: #222;
}

/* Meta text (name + timestamp) */
.message-meta {
    font-size: 11px;
    color: #888;
}

/* Avatar circle */
.avatar-circle {
    width: 32px;
    height: 32px;
    background: #004a93;
    color: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 14px;
}

/* Spacing */
.message-row {
    margin-bottom: 12px;
}

.message-text {
    white-space: pre-wrap;
}

/* Attachment link */
.message-attachment a {
    font-size: 13px;
    color: #0b67c2;
}
</style>

<div class="container-fluid px-3 px-md-4" id="notice-conversation-page">
    <x-breadcrum title="Notice Conversation" />
    <x-session_message />

    <main class="mx-auto" aria-labelledby="page-title">

        <article class="card shadow-sm mb-4" style="border-left:4px solid #004a93;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-end mb-2">
                    <a href="{{ route('memo.notice.management.index') }}"
                        class="btn btn-outline-secondary btn-sm">Back</a>
                </div>

                <!-- Letter Header -->
                <header class="text-center mb-3">
                    <h1 id="page-title" class="h5 fw-bold mb-1">{{ $template_details->course_name ?? 'Course Name' }}
                    </h1>
                    <p class="mb-0">Lal Bahadur Shastri National Academy of Administration, Mussoorie</p>
                    <hr class="my-3" />
                </header>

                <!-- Notice body (semantic) -->
                <section aria-labelledby="notice-heading" class="mb-3">
                    <h2 id="notice-heading" class="visually-hidden">Show Cause Notice</h2>

                    <p class="mb-1 text-uppercase small fw-semibold">SHOW CAUSE NOTICE</p>
                    <p class="mb-1"><strong>Date:</strong> {{ \Carbon\Carbon::now()->format('d/m/Y') }}</p>

                    <p>It has been brought to the notice of the undersigned that you were absent without prior
                        authorization
                        from following session(s)...</p>

                    <!-- Session summary (presented as accessible table) -->
                    <div class="table-responsive mb-3" role="region" aria-label="Session details">
                        <table class="table table-bordered text-center">
                            <thead>
                                <tr>
                                    <th scope="col">Date</th>
                                    <th scope="col">No. of Session(s)</th>
                                    <th scope="col">Topics</th>
                                    <th scope="col">Venue</th>
                                    <th scope="col">Session(s)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>{{ \Carbon\Carbon::now()->format('d/m/Y') }}</td>
                                    <td>1</td>
                                    <td>{{ $template_details->subject_topic ?? 'Topic Name' }}</td>
                                    <td>{{ $template_details->venue_name ?? 'Venue' }}</td>
                                    <td>{{ $template_details->session_time ?? '06:00-07:00' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mb-4">
                        <p class="fw-bold">You are advised to do the following:</p>
                        <ul>
                            <li>Reply to this Memo online through this <a href="#conversation"
                                    class="link-primary">conversation</a></li>
                            <li>Appear in person before the undersigned at 1800 hrs on next working day</li>
                        </ul>

                        <div class="notice-content">{!! $template_details->content ?? '' !!}</div>
                    </div>

                    <p><strong>{{ $template_details->display_name ?? 'Student Name' }},
                            {{ $template_details->generated_OT_code ?? 'OT Code' }}</strong><br>
                        Remarks: Show Cause Notice for {{ \Carbon\Carbon::now()->format('d/m/Y') }}</p>

                    <p class="text-end">
                        <strong>{{ $template_details->director_name ?? 'Director Name' }}</strong><br>{{ $template_details->director_designation ?? 'Director Designation' }}
                    </p>
                </section>

                <!-- Exemption Summary -->
                <section aria-labelledby="exemption-heading" class="mb-4">
                    <h3 id="exemption-heading" class="visually-hidden">Exemption summary</h3>
                    <div class="row gx-2 gy-2 align-items-stretch">
                        <div class="col-6 col-md-3">
                            <div class="p-2 border rounded text-center" role="group" aria-label="Total exemption have">
                                <p class="mb-0 small text-muted">Total Exemption Have</p>
                                <p class="h5 mb-0">3</p>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="p-2 border rounded text-center">
                                <p class="mb-0 small text-muted">Total Exemption Taken</p>
                                <p class="h5 mb-0">0</p>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="p-2 border rounded text-center">
                                <p class="mb-0 small text-muted">MOD on SAT / SUN</p>
                                <p class="h5 mb-0">0</p>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="p-2 border rounded text-center bg-light">
                                <p class="mb-0 small text-muted">Exemption Balance (if any)</p>
                                <p class="h5 mb-0">3</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Conversation area -->
                <section id="conversation" aria-labelledby="conversation-heading">
                    <h2 id="conversation-heading" class="h6 fw-bold mb-3">Conversation</h2>
                    <hr class="my-2">
                    @forelse($memoNotice as $row)
                    @php
                    // Determine if this message is from the current user (staff/faculty)
                    $isSender = $row->role_type === 'f'; // 'f' = faculty/staff, 's' = student
                    $fromClass = $isSender ? 'from-staff' : 'from-student';
                    @endphp

                    <div class="message-row {{ $fromClass }}" data-msg-id="{{ $row->pk }}">

                        @if($isSender)
                        <!-- Student -->
                        <div class="avatar me-2"><span class="avatar-circle">S</span></div>
                        <div class="message-bubble">
                            <div class="message-meta">
                                {{ $row->display_name ?? 'Student' }} •
                                {{ \Carbon\Carbon::parse($row->created_date)->format('d-m-Y h:i A') }}
                            </div>
                            <div class="message-text mt-1">{{ $row->student_decip_incharge_msg }}</div>

                            @if($row->doc_upload)
                            <div class="message-attachment mt-2">
                                <a href="{{ asset('storage/' . $row->doc_upload) }}" target="_blank">Attachment
                                    (View)</a>
                            </div>
                            @endif
                        </div>

                        @else
                        <!-- Staff -->
                        <div class="message-bubble">
                            <div class="message-meta text-end">
                                {{ $row->display_name ?? 'Staff' }} •
                                {{ \Carbon\Carbon::parse($row->created_date)->format('d-m-Y h:i A') }}
                            </div>
                            <div class="message-text mt-1 text-end">{{ $row->student_decip_incharge_msg }}</div>

                            @if($row->doc_upload)
                            <div class="message-attachment mt-2 text-end">
                                <a href="{{ asset('storage/' . $row->doc_upload) }}" target="_blank">Attachment
                                    (View)</a>
                            </div>
                            @endif
                        </div>
                        @endif

                    </div>
                    @empty
                    <div class="alert alert-info mt-4" role="status">
                        <i class="material-icons material-symbols-rounded me-2" style="vertical-align: middle;">info</i>
                        <span>No conversation messages available yet.</span>
                    </div>
                    @endforelse

                </section>


            </div>
        </article>

    </main>

</div>


@section('styles')
<style>
/* GIGW: high contrast and readable font sizes; avoid brand-only colors for text */
#notice-conversation-page {
    font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
}

/* Message styles - accessible chat bubbles */
.messages .message-row {
    display: flex;
    gap: 0.75rem;
}

.messages .avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #e9eef8;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
}

.messages .avatar .avatar-circle {
    font-size: 0.875rem;
}

.message-bubble {
    max-width: 75%;
    border-radius: 12px;
    background: #ffffff;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
    border: 1px solid rgba(0, 0, 0, 0.06);
    padding: 0.6rem;
}

.message-text {
    color: #222;
    line-height: 1.35;
}

.message-meta {
    color: #6c757d;
}

/* Alignment classes */
.from-staff {
    justify-content: flex-end;
}

.from-staff .message-bubble {
    background: #e8f0ff;
}

.from-student {
    justify-content: flex-start;
}

/* Attachment link focus for keyboard users */
.message-attachment a:focus,
.message-attachment a:hover {
    text-decoration: underline;
}

/* Responsive tweaks */
@media (max-width:576px) {
    .message-bubble {
        max-width: 100%;
    }
}

/* Visible focus outlines (GIGW keyboard) */
:focus {
    outline: 3px solid #ffd54f;
    outline-offset: 2px;
}

/* Notice closed banner styling */
.notice-closed {
    background: #fff3cd;
    border: 1px solid #ffeeba;
    padding: 0.6rem 1rem;
    border-radius: 6px;
    color: #856404;
}
</style>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // status -> conclusion toggle for accessibility
    var statusSelector = document.getElementById('status');
    var conclusionDiv = document.getElementById('conclusion_div');
    var conclusionTypeSelector = document.getElementById('conclusion_type');
    var deductionDiv = document.getElementById('deduction_div');

    if (statusSelector) {
        statusSelector.addEventListener('change', function() {
            if (this.value === '2') {
                conclusionDiv.style.display = 'block';
                conclusionDiv.setAttribute('aria-hidden', 'false');
            } else {
                conclusionDiv.style.display = 'none';
                conclusionDiv.setAttribute('aria-hidden', 'true');
            }
        });

        // default check on load
        if (statusSelector.value === '2') {
            conclusionDiv.style.display = 'block';
        }
    }

    if (conclusionTypeSelector) {
        conclusionTypeSelector.addEventListener('change', function() {
            var optionText = this.options[this.selectedIndex].text;
            if (optionText.trim().toLowerCase() === 'marks deduction') {
                deductionDiv.style.display = 'block';
                deductionDiv.setAttribute('aria-hidden', 'false');
            } else {
                deductionDiv.style.display = 'none';
                deductionDiv.setAttribute('aria-hidden', 'true');
            }
        });
    }

    // set readonly date and time
    const now = new Date();
    const date = now.toISOString().split('T')[0];
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const time = `${hours}:${minutes}`;
    var dateEl = document.getElementById('current_date');
    var timeEl = document.getElementById('current_time');
    if (dateEl) dateEl.value = date;
    if (timeEl) timeEl.value = time;

    // Ensure messages container is scrolled to bottom
    var messages = document.getElementById('messages');
    if (messages) {
        messages.scrollTop = messages.scrollHeight;
    }

    // Keyboard accessibility: focus new messages container when tabbed to
});
</script>
@endsection

@endsection