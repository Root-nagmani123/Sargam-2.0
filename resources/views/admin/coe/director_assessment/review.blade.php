@extends('admin.layouts.master')

@php
    $num = fn ($v) => \App\Services\COE\ResultMarksService::number($v);
    $two = fn ($v) => $v === null ? '' : number_format((float) $v, 2, '.', '');
    $r = $row;
    $sent = $r['sent'];
    $initials = collect(preg_split('/\s+/', trim($r['name'])))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
    $link = fn ($x) => route('admin.coe.evaluation-marks.review', $filters + ['key' => $x['key'], 'student' => $x['student']]);
    $tableUrl = route('admin.coe.evaluation-marks.index', $filters);
    $scored = array_values(array_filter($r['faculty'], fn ($f) => $f['received'] && ! $f['absent']));
    $scoredMax = array_sum(array_column($scored, 'max'));
    $scoredSum = array_sum(array_column($scored, 'marks'));
    $date = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('d M Y') : '—';
    $noticeStatus = fn ($s) => match ((int) $s) { 1 => 'Open', 2 => 'Closed', default => '—' };
    // Notice-memo portal (student_*_status): 1 Open, 2 Closed. Discipline Memo module: 1 Recorded, 2 Memo Sent, 3 Closed.
    $memoStatus = fn ($m) => $m->kind === 'Discipline memo'
        ? match ((int) $m->status) { 1 => 'Recorded', 2 => 'Memo sent', 3 => 'Closed', default => '—' }
        : $noticeStatus($m->status);
@endphp

@section('title', 'Director Assessment · '.$r['name'])

@push('styles')
<link rel="stylesheet"
      href="{{ asset('css/coe-admin.css') }}?v={{ @filemtime(public_path('css/coe-admin.css')) ?: time() }}">
@include('admin.coe.director_assessment.styles')
<style>
.dar { background: #fff; border-radius: 12px; overflow: hidden; }
.dar-top { padding: 24px 32px 20px; display: flex; flex-wrap: wrap; justify-content: space-between; gap: 16px; align-items: flex-start; }
.dar-crumb { font-size: .8125rem; color: #475467; margin-bottom: 18px; }
.dar-crumb a { color: inherit; text-decoration: none; }
.dar-title { font-size: 1.625rem; font-weight: 600; color: var(--da-ink); margin: 0; }
.dar-sub { margin: 4px 0 0; color: #475467; font-size: .9375rem; }
.dar-band { background: var(--da-band); padding: 20px 32px 24px; }
.dar-id { display: flex; flex-wrap: wrap; gap: 20px; justify-content: space-between; align-items: flex-start; }
.dar-id__who { display: flex; gap: 22px; align-items: center; }
.dar-id__name { font-size: 1.625rem; font-weight: 600; color: var(--da-ink); line-height: 1.2; }
.dar-id__meta { color: #344054; margin-top: 6px; }
.dar-id__subject { color: var(--coe-brand); font-weight: 600; margin-top: 4px; }
.dar-id__state { display: flex; flex-direction: column; align-items: flex-start; gap: 12px; min-width: 190px; }
.dar-id__stage { font-size: .875rem; font-weight: 600; color: #344054; }
.dar-steps { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 8px 16px; background: #fff; border-radius: 6px; padding: 12px 16px; margin-top: 22px; font-size: .875rem; color: #475467; }
.dar-steps__step.is-done { color: var(--da-ink); font-weight: 600; }
.dar-steps__step.is-current { color: var(--coe-brand); font-weight: 600; }
.dar-steps__arrow { color: #98a2b3; }
.dar-nav { display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; }
.dar-nav .btn { min-width: 128px; border: 1px solid var(--coe-brand); color: var(--coe-brand); background: #fff; font-weight: 600; padding: 10px 16px; }
.dar-nav .btn:hover { background: #eef3fb; }
.dar-nav .btn.disabled { opacity: .45; }
.dar-nav__pos { align-self: center; font-size: .8125rem; color: #475467; margin-right: auto; }
.dar-body { padding: 28px 32px; display: grid; grid-template-columns: minmax(0, 1.55fr) minmax(0, 1fr); gap: 24px; align-items: start; }
@media (max-width: 1199.98px) { .dar-body { grid-template-columns: 1fr; } }
@media (max-width: 575.98px) { .dar-top, .dar-band, .dar-body { padding-left: 16px; padding-right: 16px; } .dar-id__who { gap: 14px; } }
.dar-card { border: 1px solid var(--da-line); border-radius: 8px; padding: 20px; background: #fff; }
.dar-card + .dar-card { margin-top: 20px; }
.dar-card__head { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; }
.dar-card__title { font-size: 1.1875rem; font-weight: 600; color: var(--da-ink); margin: 0; }
.dar-card__text { color: #475467; font-size: .875rem; margin: 16px 0; }
.dar-tiles { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-bottom: 16px; }
.dar-tile { background: var(--da-tint); border-radius: 6px; padding: 14px 12px; }
.dar-tile__label { font-size: .8125rem; color: #475467; }
.dar-tile__value { font-size: 1.25rem; font-weight: 600; color: var(--coe-brand); margin: 4px 0 2px; }
.dar-tile__hint { font-size: .75rem; color: #475467; }
.dar-table { width: 100%; font-size: .8125rem; }
.dar-table th { background: #e9eaec; font-weight: 600; color: #1d2939; padding: 10px 8px; white-space: nowrap; }
.dar-table td { padding: 12px 8px; border-bottom: 1px solid var(--da-line); vertical-align: top; color: #344054; }
.dar-table td.is-marks { color: var(--coe-brand); }
.dar-total { font-size: .8125rem; font-weight: 600; color: #344054; margin-top: 14px; }
.dar-rec__head { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-top: 4px; }
.dar-rec__title { font-weight: 600; color: var(--da-ink); }
.dar-rec__empty { font-size: .875rem; color: #475467; margin: 6px 0 0; }
.dar-rec__list { list-style: none; padding: 0; margin: 10px 0 0; }
.dar-rec__list li { padding: 10px 0; border-top: 1px solid var(--da-line); font-size: .8125rem; color: #344054; }
.dar-rec__list li:first-child { border-top: 0; }
.dar-rec__meta { color: var(--da-muted); font-size: .75rem; }
.dar-divider { border-top: 1px solid var(--da-line); margin: 18px 0; }
.dar-form__title { font-size: 1.375rem; font-weight: 600; color: var(--da-ink); margin: 18px 0 10px; line-height: 1.35; }
.dar-form__text { color: #475467; font-size: .875rem; padding-bottom: 18px; border-bottom: 1px solid var(--da-line); margin-bottom: 18px; }
.dar-label { font-weight: 600; color: var(--da-ink); font-size: .9375rem; margin-bottom: 8px; display: block; }
.dar-marks { display: flex; align-items: center; border: 1px solid #98a2b3; border-radius: 6px; padding: 0 14px; background: #fff; }
.dar-marks:focus-within { border-color: var(--coe-brand); box-shadow: 0 0 0 3px rgba(0, 67, 132, .12); }
.dar-marks input { border: 0; outline: 0; flex: 1; padding: 12px 0; font-size: 1rem; min-width: 0; background: transparent; }
.dar-marks__of { font-weight: 600; color: var(--da-ink); }
.dar-marks.is-invalid { border-color: var(--coe-danger); }
.dar-help { font-size: .75rem; color: #475467; margin: 8px 0 20px; }
.dar-remarks { width: 100%; min-height: 136px; border: 1px solid #98a2b3; border-radius: 6px; padding: 12px 14px; font-size: .875rem; resize: vertical; }
.dar-remarks:focus { outline: 0; border-color: var(--coe-brand); box-shadow: 0 0 0 3px rgba(0, 67, 132, .12); }
.dar-remarks.is-invalid { border-color: var(--coe-danger); }
.dar-counter { display: flex; justify-content: space-between; font-size: .75rem; color: #475467; margin-top: 6px; }
.dar-info { background: var(--da-tint); border-radius: 6px; padding: 14px 12px; margin-top: 18px; font-size: .8125rem; color: #344054; }
.dar-info__title { color: var(--coe-brand); font-weight: 600; margin-bottom: 4px; }
.dar-assessor { font-size: .8125rem; color: #475467; margin-top: 14px; padding-left: 4px; }
.dar-bar { position: sticky; bottom: 0; z-index: 5; background: #fff; border-top: 1px solid var(--da-line); padding: 18px 32px; display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center; }
.dar-bar__title { font-weight: 600; color: var(--da-ink); }
.dar-bar__text { font-size: .8125rem; color: #475467; }
.dar-bar .btn { min-width: 116px; font-weight: 600; padding: 10px 18px; }
.dar-bar .dar-draft { border: 1px solid var(--coe-brand); color: var(--coe-brand); background: #fff; }
.dar-bar .dar-draft:hover { background: #eef3fb; color: var(--coe-brand); }
.dar-bar .dar-send { background: var(--coe-brand); color: #fff; border: 1px solid var(--coe-brand); }
.dar-bar .dar-send:hover { background: var(--coe-brand-hover); color: #fff; }
.dar-bar .dar-send:disabled { background: #eaecf0; border-color: #eaecf0; color: #667085; opacity: 1; }
</style>
@endpush

@section('setup_content')
<div class="container-fluid coe-page">
    <x-breadcrum title="Evaluation & Marks" :showBack="false"
                 :items="['Setup', 'COE', 'Examination Management', 'Evaluation & Marks']" />

    <div class="dar" id="darRoot" data-key="{{ $r['key'] }}" data-student="{{ $r['student'] }}">
        <div class="dar-top">
            <div>
                <div class="dar-crumb">
                    <a href="{{ $tableUrl }}">Assessment</a> / Director assessment / OT review
                </div>
                <h1 class="dar-title">Director assessment</h1>
                <p class="dar-sub">Review the OT record before completing the director’s overall assessment.</p>
            </div>
            <div class="da-seg" role="group" aria-label="View">
                <a class="da-seg__btn da-seg__btn--plain" href="{{ $tableUrl }}">Table view</a>
                <span class="da-seg__btn is-active" aria-current="page">OT profile</span>
            </div>
        </div>

        <section class="dar-band" aria-label="Officer trainee">
            <div class="dar-id">
                <div class="dar-id__who">
                    @include('admin.coe.director_assessment.avatar', ['photo' => $r['photo'], 'name' => $r['name'], 'size' => 88, 'initials' => $initials])
                    <div>
                        <div class="dar-id__name">{{ $r['name'] }}</div>
                        <div class="dar-id__meta">{{ $r['code'] }} &nbsp;·&nbsp; Officer trainee &nbsp;·&nbsp; {{ $r['course'] }}</div>
                        <div class="dar-id__subject">{{ $r['subject'] }}</div>
                    </div>
                </div>
                <div class="dar-id__state">
                    @if ($sent)
                        <span class="da-chip da-chip--green">Sent to COE</span>
                        <span class="dar-id__stage">With COE</span>
                    @elseif ($r['eligible'])
                        <span class="da-chip da-chip--green">Eligible for assessment</span>
                        <span class="dar-id__stage">Pending TI review</span>
                    @else
                        <span class="da-chip da-chip--amber">Faculty marks pending</span>
                        <span class="dar-id__stage">Waiting for faculty</span>
                    @endif
                </div>
            </div>

            <div class="dar-steps" aria-label="Assessment handoff">
                <span class="dar-steps__step {{ $r['eligible'] ? 'is-done' : 'is-current' }}">01&nbsp; Faculty marks {{ $r['eligible'] ? 'received' : 'pending' }}</span>
                <span class="dar-steps__arrow" aria-hidden="true">→</span>
                <span class="dar-steps__step {{ $sent ? 'is-done' : ($r['eligible'] ? 'is-current' : '') }}">02&nbsp; TI · Director assessment</span>
                <span class="dar-steps__arrow" aria-hidden="true">→</span>
                <span class="dar-steps__step {{ $sent ? 'is-current' : '' }}">03&nbsp; COE · {{ $sent ? 'Received' : 'Awaiting submission' }}</span>
            </div>

            <nav class="dar-nav" aria-label="Record navigation">
                <span class="dar-nav__pos">OT {{ $position }} of {{ $total }}</span>
                <a class="btn {{ $prev ? '' : 'disabled' }}" href="{{ $prev ? $link($prev) : '#' }}" @unless ($prev) aria-disabled="true" tabindex="-1" @endunless>← Previous</a>
                <a class="btn {{ $next ? '' : 'disabled' }}" href="{{ $next ? $link($next) : '#' }}" @unless ($next) aria-disabled="true" tabindex="-1" @endunless>Next →</a>
            </nav>
        </section>

        <div class="dar-body">
            <div>
                <section class="dar-card" aria-labelledby="darFacultyTitle">
                    <div class="dar-card__head">
                        <h2 class="dar-card__title" id="darFacultyTitle">Faculty assessment</h2>
                        <span class="da-chip">Read-only</span>
                    </div>
                    <p class="dar-card__text">Marks submitted by faculty are reflected here. Only the director’s assessment is entered by the TI.</p>

                    @if ($r['faculty'])
                        <div class="dar-tiles">
                            @foreach ($r['faculty'] as $f)
                                <div class="dar-tile">
                                    <div class="dar-tile__label">{{ $f['label'] }}</div>
                                    @if ($f['absent'])
                                        <div class="dar-tile__value">Absent</div>
                                        <div class="dar-tile__hint">Not applicable</div>
                                    @elseif ($f['received'])
                                        <div class="dar-tile__value">{{ $two($f['marks']) }} / {{ $num($f['max']) }}</div>
                                        <div class="dar-tile__hint">Submitted by faculty</div>
                                    @else
                                        <div class="dar-tile__value">Not entered</div>
                                        <div class="dar-tile__hint">{{ $f['over'] ? 'No marks recorded' : 'Exam not held yet' }}</div>
                                    @endif
                                </div>
                            @endforeach
                            <div class="dar-tile">
                                <div class="dar-tile__label">Attendance</div>
                                <div class="dar-tile__value">{{ $r['attendance'] ?? 'Not received' }}</div>
                                <div class="dar-tile__hint">{{ $r['subject'] }}</div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="dar-table">
                                <thead>
                                    <tr><th>Component</th><th>Faculty / source</th><th>Marks</th><th>Status</th><th>Faculty remarks</th></tr>
                                </thead>
                                <tbody>
                                    @foreach ($r['faculty'] as $f)
                                        <tr>
                                            <td>{{ $f['label'] }} /{{ $num($f['max']) }}</td>
                                            <td>{{ $f['evaluator'] ?: '—' }}@if ($f['source'] && $f['source'] !== $f['evaluator'])<br><span class="text-muted">{{ $f['source'] }}</span>@endif</td>
                                            <td class="is-marks">{{ $f['absent'] ? 'AB' : ($f['received'] ? $two($f['marks']) : '—') }}</td>
                                            <td>{{ $f['received'] ? ($f['absent'] ? 'Absent' : 'Received') : 'No entry' }}</td>
                                            <td>{{ $f['remarks'] !== '' ? $f['remarks'] : '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="dar-total">
                            Recorded faculty marks: {{ $two($scoredSum) }} / {{ $num($scoredMax) }} across {{ count($scored) }} scored component{{ count($scored) === 1 ? '' : 's' }}.
                        </div>
                    @else
                        <p class="dar-rec__empty">This drive has no faculty components on {{ $r['subject'] }}: only the Director’s assessment.</p>
                    @endif
                </section>

                <section class="dar-card" id="da-notices" aria-labelledby="darNoticeTitle">
                    <div class="dar-card__head">
                        <h2 class="dar-card__title" id="darNoticeTitle">Notices &amp; memos</h2>
                        <span class="da-chip">OT record · {{ $r['code'] }}</span>
                    </div>
                    <p class="dar-card__text mb-3">Review conduct records alongside the faculty assessment.</p>

                    <div class="dar-rec__head">
                        <span class="dar-rec__title">Notices</span>
                        <span class="da-chip {{ $notices->isEmpty() ? 'da-chip--green' : 'da-chip--amber' }}">{{ $notices->isEmpty() ? 'None recorded' : $notices->count().' recorded' }}</span>
                    </div>
                    @if ($notices->isEmpty())
                        <p class="dar-rec__empty">No notices recorded for {{ $r['name'] }}.</p>
                    @else
                        <ul class="dar-rec__list">
                            @foreach ($notices as $n)
                                <li>
                                    {{ $n->topic ?: 'Notice' }}
                                    <div class="dar-rec__meta">{{ $date($n->date) }} · {{ $n->course ?: '—' }} · {{ $noticeStatus($n->status) }}</div>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    <div class="dar-divider"></div>

                    <div class="dar-rec__head">
                        <span class="dar-rec__title">Memos</span>
                        <span class="da-chip {{ $memos->isEmpty() ? 'da-chip--green' : 'da-chip--amber' }}">{{ $memos->isEmpty() ? 'None recorded' : $memos->count().' recorded' }}</span>
                    </div>
                    @if ($memos->isEmpty())
                        <p class="dar-rec__empty">No memos recorded for {{ $r['name'] }}.</p>
                    @else
                        <ul class="dar-rec__list">
                            @foreach ($memos as $m)
                                <li>
                                    {{ $m->topic ?: $m->kind }}
                                    @if ($m->conclusion || $m->remark)
                                        — {{ trim(($m->conclusion ?? '').($m->conclusion && $m->remark ? ': ' : '').($m->remark ?? '')) }}
                                    @endif
                                    <div class="dar-rec__meta">{{ $m->kind }} · {{ $date($m->date) }} · {{ $m->course ?: '—' }} · {{ $memoStatus($m) }}</div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            </div>

            <div>
                <section class="dar-card" aria-labelledby="darFormTitle">
                    <div class="dar-card__head">
                        <span class="da-chip">TI · Reviewer</span>
                        @if ($sent)
                            <span class="da-chip da-chip--green">Sent to COE</span>
                        @elseif ($r['mark'])
                            <span class="da-chip da-chip--amber">Draft saved</span>
                        @else
                            <span class="da-chip da-chip--amber">Not submitted</span>
                        @endif
                    </div>
                    <h2 class="dar-form__title" id="darFormTitle">Director’s overall assessment</h2>
                    <p class="dar-form__text">Assess the OT’s overall performance using the profile, faculty marks and conduct records.</p>

                    <label class="dar-label" for="darMarks">Director’s overall marks <span class="coe-req">*</span></label>
                    <div class="dar-marks" id="darMarksBox">
                        <input type="text" inputmode="decimal" id="darMarks" value="{{ $two($r['marks']) }}"
                               data-max="{{ $r['max'] }}" placeholder="Enter marks" autocomplete="off" @disabled($sent)
                               aria-describedby="darMarksHelp">
                        <span class="dar-marks__of">/ {{ $num($r['max']) }}</span>
                    </div>
                    <p class="dar-help" id="darMarksHelp">Enter a value from 0 to {{ $num($r['max']) }}{{ $sent ? '. Sent to COE: locked.' : ', up to 2 decimals.' }}</p>

                    <label class="dar-label" for="darRemarks">Director’s overall remarks <span class="coe-req">*</span></label>
                    <textarea id="darRemarks" class="dar-remarks" maxlength="{{ $remarksMax }}" @disabled($sent)
                              placeholder="Record your overall assessment, including the reasons for the marks awarded…">{{ $r['note'] }}</textarea>
                    <div class="dar-counter">
                        <span>Required for COE submission</span>
                        <span><span id="darCount">{{ mb_strlen($r['note']) }}</span> / {{ $remarksMax }}</span>
                    </div>

                    <div class="dar-info">
                        <div class="dar-info__title">Before sending to COE</div>
                        Review the evidence, then enter director marks and remarks. Faculty marks remain unchanged.
                        @unless ($r['eligible'])
                            <div class="mt-1"><strong>Every faculty mark of {{ $r['subject'] }} must be received first.</strong></div>
                        @endunless
                    </div>
                </section>
                <div class="dar-assessor">
                    Assessor: {{ \App\Services\COE\DirectorAssessmentService::EVALUATOR }} (TI)<br>
                    Next reviewer: COE
                </div>
            </div>
        </div>

        <div class="dar-bar">
            <div>
                <div class="dar-bar__title" id="darBarTitle">
                    {{ $sent ? 'Director assessment sent to COE' : 'Director assessment has not been submitted' }}
                </div>
                <div class="dar-bar__text" id="darBarText">
                    @if ($sent)
                        Locked{{ $r['mark']?->approved_at ? ' on '.$r['mark']->approved_at->format('d M Y, h:i A') : '' }}. COE takes it from here.
                    @elseif (! $r['eligible'])
                        Send to COE opens once every faculty mark is received.
                    @else
                        Enter marks and remarks to enable Send to COE.
                    @endif
                </div>
            </div>
            @unless ($sent)
                <div class="d-flex flex-wrap gap-3">
                    <button type="button" class="btn dar-draft" id="darDraftBtn">Save draft</button>
                    <button type="button" class="btn dar-send" id="darSendBtn" disabled
                            data-eligible="{{ $r['eligible'] ? 1 : 0 }}">Send to COE</button>
                </div>
            @endunless
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    'use strict';
    if (!$('#darSendBtn').length) { return; }

    var CSRF = { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' };
    var KEY = $('#darRoot').attr('data-key'), STUDENT = $('#darRoot').attr('data-student');
    var $marks = $('#darMarks'), $remarks = $('#darRemarks'), $send = $('#darSendBtn');
    var MAX = parseFloat($marks.data('max'));
    var NEXT_URL = @json($next ? $link($next) : null), TABLE_URL = @json($tableUrl);
    var saved = { marks: $marks.val(), remarks: $.trim($remarks.val()) };

    function validMark(value) {
        value = $.trim(value);
        return value === '' || (/^\d+(\.\d{1,2})?$/.test(value) && parseFloat(value) <= MAX);
    }
    function dirty() { return $.trim($marks.val()) !== saved.marks || $.trim($remarks.val()) !== saved.remarks; }

    function refresh() {
        var mark = $.trim($marks.val()), ok = validMark(mark);
        $('#darMarksBox').toggleClass('is-invalid', !ok);
        $('#darCount').text($remarks.val().length);
        $send.prop('disabled', !(ok && mark !== '' && $.trim($remarks.val()) !== '' && $send.data('eligible') === 1));
    }
    $marks.on('input', refresh);
    $remarks.on('input', refresh);
    refresh();

    function payload(send) {
        var data = { marks: {}, remarks: {}, send: send ? [KEY + '|' + STUDENT] : [] };
        (data.marks[KEY] = {})[STUDENT] = $.trim($marks.val());
        (data.remarks[KEY] = {})[STUDENT] = $.trim($remarks.val());
        return data;
    }

    function post(send) {
        if (!validMark($marks.val())) {
            Swal.fire({ icon: 'error', title: 'Check the marks', text: 'Enter marks from 0 to ' + MAX + ' (up to 2 decimals).' });
            return;
        }
        var $btns = $('#darDraftBtn, #darSendBtn').prop('disabled', true);
        $.ajax({
            url: @json(route('admin.coe.evaluation-marks.save')), method: 'POST',
            contentType: 'application/json', headers: CSRF, data: JSON.stringify(payload(send))
        }).done(function (res) {
            saved = { marks: $.trim($marks.val()), remarks: $.trim($remarks.val()) };
            // Sent: straight on to the next OT (or back to the table after the last one).
            if (send) {
                Swal.fire({
                    icon: 'success', title: 'Sent to COE', text: res.message,
                    showCancelButton: true,
                    confirmButtonText: NEXT_URL ? 'Next OT →' : 'Back to table',
                    cancelButtonText: 'Stay here'
                }).then(function (r) { window.location.href = r.isConfirmed ? (NEXT_URL || TABLE_URL) : window.location.href; });
                return;
            }
            Swal.fire({ icon: 'success', title: 'Saved', text: res.message })
                .then(function () { window.location.reload(); });
        }).fail(function (xhr) {
            var res = xhr.responseJSON || {};
            Swal.fire({ icon: 'error', title: send ? 'Not sent' : 'Not saved', text: res.message || 'Could not save the assessment.' });
        }).always(function () { $('#darDraftBtn').prop('disabled', false); refresh(); });
    }

    $('#darDraftBtn').on('click', function () { post(false); });
    $send.on('click', function () {
        Swal.fire({
            icon: 'question', title: 'Send to COE?',
            text: 'The Director’s marks and remarks for ' + @json($r['name']) + ' will be locked and sent to COE. They cannot be changed afterwards.',
            showCancelButton: true, confirmButtonText: 'Send to COE', cancelButtonText: 'Cancel'
        }).then(function (r) { if (r.isConfirmed) { post(true); } });
    });

    // Previous / Next / Table view with unsaved typing: ask first.
    $('.dar-nav a, .da-seg a, .dar-crumb a').on('click', function (e) {
        if (!dirty() || $(this).hasClass('disabled')) { return; }
        e.preventDefault();
        var href = this.href;
        Swal.fire({
            icon: 'warning', title: 'Leave without saving?',
            text: 'The marks or remarks you typed for this OT are not saved.',
            showCancelButton: true, confirmButtonText: 'Leave', cancelButtonText: 'Stay'
        }).then(function (r) { if (r.isConfirmed) { saved = { marks: $.trim($marks.val()), remarks: $.trim($remarks.val()) }; window.location.href = href; } });
    });
    window.addEventListener('beforeunload', function (e) { if (dirty()) { e.preventDefault(); e.returnValue = ''; } });
});
</script>
@endpush
