@extends('admin.layouts.master')

@php
    $num = fn ($v) => \App\Services\COE\ResultMarksService::number($v);
    $two = fn ($v) => $v === null ? '' : number_format((float) $v, 2, '.', '');
    $isCompleted = $tab === 'completed';
    $shown = array_values(array_filter($rows, fn ($r) => $r['sent'] === $isCompleted));
    $filters = array_filter(['course' => $coursePk, 'drive' => $drivePk]);
    $tabFilters = $filters + ($isCompleted ? ['tab' => 'completed'] : []);
    $reviewUrl = fn ($r) => route('admin.coe.evaluation-marks.review', $tabFilters + ['key' => $r['key'], 'student' => $r['student']]);
    $editable = ! $isCompleted && $shown !== [];
    $directorHead = 'Director'.($max !== null ? ' /'.$num($max) : '');
    $facultyCols = array_keys($faculty);
    // Checkbox, Officer trainee, Course / attendance, Source / faculty, faculty marks…, Director, Faculty remarks, Notices, Review status
    $directorCol = 4 + count($facultyCols);
    $statusCol = $directorCol + 3;
    $initials = fn ($name) => collect(preg_split('/\s+/', trim($name)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
    $emptyState = $isCompleted
        ? ['title' => 'Nothing Sent to COE Yet', 'zeroTitle' => 'No Records Found', 'text' => 'Director assessments you send to COE show here.']
        : ['title' => 'No Director Assessment to Review', 'zeroTitle' => 'No Records Found', 'text' => 'OTs show here once a Director Assessment drive is approved by its Course Coordinator and its Director component\'s exam date has passed.'];
@endphp

@section('title', 'Evaluation & Marks')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}">
<link rel="stylesheet"
      href="{{ asset('css/coe-admin.css') }}?v={{ @filemtime(public_path('css/coe-admin.css')) ?: time() }}">
@include('admin.coe.director_assessment.styles')
<style>
.da-head { display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 12px; margin: 4px 0 16px; }
.da-head__title { margin: 0; font-size: 1.0625rem; font-weight: 600; color: var(--da-ink); }
.da-head__sub { margin: 2px 0 0; font-size: .8125rem; color: var(--da-muted); }
.coe-page .da-table thead th { background: #e9eaec; color: #1d2939; font-size: .8125rem; font-weight: 600; white-space: nowrap; vertical-align: middle; }
.coe-page .da-table tbody td { font-size: .8125rem; vertical-align: middle; }
.coe-page .da-table th:last-child,
.coe-page .da-table td:last-child { text-align: left; }
.coe-page .da-table tbody tr.is-picked > * { background: var(--da-tint) !important; }
.da-ot { display: flex; align-items: center; gap: 10px; min-width: 170px; }
.da-ot__name { font-weight: 600; color: var(--da-ink); line-height: 1.2; }
.da-ot__code { font-size: .75rem; color: var(--da-muted); }
.da-course { min-width: 150px; max-width: 190px; font-size: .75rem; color: #344054; line-height: 1.35; }
.da-att { display: block; font-weight: 600; font-size: .75rem; margin-top: 2px; }
.da-att--present { color: var(--coe-brand); }
.da-att--absent { color: #b42318; }
.da-src__name { display: block; font-size: .75rem; color: var(--da-muted); }
.da-mark { width: 84px; font-size: .8125rem; }
.da-mark.is-saved { border-color: var(--coe-success); }
.da-remark { min-width: 150px; max-width: 220px; color: #344054; }
.da-notice { color: var(--coe-brand); font-weight: 600; text-decoration: none; }
.da-status { display: flex; flex-direction: column; align-items: flex-start; gap: 6px; min-width: 120px; }
.da-status__label { font-size: .75rem; color: #475467; }
.da-status__label--sent { color: #067647; font-weight: 600; }
.da-review-btn { padding: 5px 10px; border-radius: 6px; background: var(--da-tint); color: var(--coe-brand); font-size: .8125rem; font-weight: 600; text-decoration: none; white-space: nowrap; }
.da-review-btn:hover { background: #d9e3f5; color: var(--coe-brand); }
.da-foot-note { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 8px; margin-top: 12px; font-size: .75rem; color: var(--da-muted); }
.coe-page .da-approve { min-width: 112px; }
</style>
@endpush

@section('setup_content')
<div class="container-fluid coe-page">
    <x-breadcrum title="Evaluation & Marks" :showBack="false"
                 :items="['Setup', 'COE', 'Examination Management', 'Evaluation & Marks']">
        @if ($editable)
            <button type="button" id="daApproveBtn" class="btn coe-btn-header da-approve"
                    title="Send the ticked OTs' Director assessment to COE">
                <span>Approve</span>
            </button>
        @endif
    </x-breadcrum>

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <nav class="coe-tabs" aria-label="Review status">
            <a href="{{ route('admin.coe.evaluation-marks.index', $filters) }}"
               class="coe-tab {{ $isCompleted ? '' : 'is-active' }}" @unless ($isCompleted) aria-current="page" @endunless>Pending: {{ $pendingCount }}</a>
            <a href="{{ route('admin.coe.evaluation-marks.index', $filters + ['tab' => 'completed']) }}"
               class="coe-tab {{ $isCompleted ? 'is-active' : '' }}" @if ($isCompleted) aria-current="page" @endif>Completed: {{ $completedCount }}</a>
        </nav>
        <div class="d-flex flex-wrap gap-2 coe-secondary-actions">
            @include('admin.coe.partials.download_menu', ['prefix' => 'da'])
            <button type="button" id="daPrintBtn" class="btn programme-dt-btn-columns" title="Print">
                <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
            </button>
        </div>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">
            @include('admin.coe.partials.print_brand')
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-3 programme-dt-toolbar coe-toolbar">
                <form method="GET" action="{{ route('admin.coe.evaluation-marks.index') }}" id="daFilterForm"
                      class="d-flex flex-wrap align-items-center gap-3">
                    @if ($isCompleted)
                        <input type="hidden" name="tab" value="completed">
                    @endif
                    <span class="programme-dt-filters-label">Filter</span>
                    <div class="programme-dt-filter-select">
                        <select name="course" class="form-select da-filter" aria-label="Course Name">
                            @forelse ($courses as $pk => $name)
                                <option value="{{ $pk }}" @selected($pk === $coursePk)>{{ $name }}</option>
                            @empty
                                <option value="">Course Name</option>
                            @endforelse
                        </select>
                    </div>
                    <div class="programme-dt-filter-select">
                        <select name="drive" class="form-select da-filter" aria-label="Examination">
                            <option value="">All Examinations</option>
                            @foreach ($drives as $id => $name)
                                <option value="{{ $id }}" @selected($id === $drivePk)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if ($drivePk)
                        <a href="{{ route('admin.coe.evaluation-marks.index', array_filter(['course' => $coursePk, 'tab' => $isCompleted ? 'completed' : null])) }}"
                           class="btn da-remove-filter">Remove Filter</a>
                    @endif
                </form>
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns"
                            data-bs-toggle="modal" data-bs-target="#daColumnVisibilityModal" title="Show / hide columns">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="daDtSearch" class="programme-dt-search d-none" data-dt-search-for="daTable"></div>
                    <button type="button" id="daSearchToggle" class="coe-search-toggle"
                            aria-controls="daDtSearch" aria-expanded="false" title="Search">
                        <i class="bi bi-search" aria-hidden="true"></i><span class="visually-hidden">Search</span>
                    </button>
                </div>
            </div>

            @unless ($canSave)
                <div class="alert alert-danger py-2 mb-3" role="alert">
                    Saving is off on this server: the database has no <code>coe_evaluation_marks.remarks</code>
                    column yet, which holds the Director's remarks. Ask the DBA to add it.
                </div>
            @endunless
            <div class="da-head">
                <div>
                    <h2 class="da-head__title">Director assessment · Reviewer</h2>
                    <p class="da-head__sub">
                        @if ($isCompleted)
                            Sent to COE · locked. Open the OT profile to see the full review.
                        @else
                            Quick-enter director marks · Open the OT profile for full review and COE submission
                        @endif
                    </p>
                </div>
                <div class="da-seg" role="group" aria-label="View">
                    <span class="da-seg__btn is-active" aria-current="page">Table view</span>
                    @if ($shown)
                        <a class="da-seg__btn" id="daProfileView" href="{{ $reviewUrl($shown[0]) }}">OT profile</a>
                    @else
                        <span class="da-seg__btn is-disabled" aria-disabled="true">OT profile</span>
                    @endif
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table id="daTable" class="table align-middle mb-0 w-100 programme-dt-table coe-table--no-sno da-table">
                        <thead>
                            <tr>
                                <th scope="col" class="coe-col-check">
                                    @if ($editable)
                                        <input type="checkbox" class="form-check-input" id="daCheckAll" aria-label="Select all OTs">
                                    @endif
                                </th>
                                <th scope="col">Officer trainee</th>
                                <th scope="col">Course /<br>attendance</th>
                                <th scope="col">Source / faculty</th>
                                @foreach ($faculty as $head)
                                    <th scope="col">{!! str_replace(' /', '<br>/', e($head)) !!}</th>
                                @endforeach
                                <th scope="col">{!! str_replace(' /', '<br>/', e($directorHead)) !!}</th>
                                <th scope="col">Faculty remarks</th>
                                <th scope="col">Notices</th>
                                <th scope="col">Review status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($shown as $r)
                                @php
                                    $byLabel = collect($r['faculty'])->keyBy('label');
                                    $notes = $r['notices'] + $r['memos'];
                                @endphp
                                <tr data-key="{{ $r['key'] }}" data-student="{{ $r['student'] }}">
                                    <td class="coe-col-check">
                                        @if ($editable)
                                            <input type="checkbox" class="form-check-input da-check"
                                                   aria-label="Select {{ $r['name'] }} ({{ $r['code'] }})">
                                        @endif
                                    </td>
                                    <td>
                                        <div class="da-ot">
                                            @include('admin.coe.director_assessment.avatar', ['photo' => $r['photo'], 'name' => $r['name'], 'size' => 32, 'initials' => $initials($r['name'])])
                                            <div>
                                                <div class="da-ot__name">{{ $r['name'] }}</div>
                                                <div class="da-ot__code">{{ $r['sno'] }} · {{ $r['code'] }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="da-course">
                                            {{ $r['course'] }} · {{ $r['subject'] }}
                                            @if ($r['attendance'])
                                                <span class="da-att da-att--{{ strtolower($r['attendance']) }}">{{ $r['attendance'] }}</span>
                                            @else
                                                <span class="da-att text-muted fw-normal">Not received</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        {{ implode(', ', $r['sources']) ?: '—' }}
                                        <span class="da-src__name">{{ implode(', ', array_diff($r['evaluators'], $r['sources'])) ?: '-' }}</span>
                                    </td>
                                    @foreach ($facultyCols as $label)
                                        @php $f = $byLabel[$label] ?? null; @endphp
                                        <td>
                                            @if (! $f)
                                                <span class="text-muted">—</span>
                                            @elseif ($f['absent'])
                                                Not applicable
                                            @elseif ($f['received'])
                                                {{ $two($f['marks']) }}
                                            @else
                                                <span class="text-muted" title="Not received from faculty yet">-</span>
                                            @endif
                                        </td>
                                    @endforeach
                                    <td>
                                        @if ($isCompleted)
                                            <span class="fw-semibold">{{ $two($r['marks']) }}</span>
                                            <i class="bi bi-lock-fill text-muted small" title="Sent to COE" aria-hidden="true"></i>
                                        @else
                                            <input type="text" inputmode="decimal" class="form-control form-control-sm da-mark"
                                                   value="{{ $two($r['marks']) }}" data-initial="{{ $two($r['marks']) }}"
                                                   data-max="{{ $r['max'] }}" placeholder="0–{{ $num($r['max']) }}" autocomplete="off"
                                                   aria-label="Director marks of {{ $r['name'] }} ({{ $r['code'] }}), out of {{ $num($r['max']) }}">
                                        @endif
                                    </td>
                                    <td><div class="da-remark">{{ implode(' · ', $r['remarks']) ?: '—' }}</div></td>
                                    <td>
                                        @if ($notes)
                                            <a class="da-notice" href="{{ $reviewUrl($r) }}#da-notices"
                                               title="{{ $r['notices'] }} notice(s), {{ $r['memos'] }} memo(s)">{{ $notes }}</a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        <div class="da-status">
                                            @if ($r['sent'])
                                                <span class="da-status__label da-status__label--sent">Sent to COE</span>
                                            @elseif ($r['mark'])
                                                <span class="da-status__label">Draft saved</span>
                                            @else
                                                <span class="da-status__label">Pending Review</span>
                                            @endif
                                            <a class="da-review-btn" href="{{ $reviewUrl($r) }}">Review profile</a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3"
                     data-dt-footer-for="daTable"></div>
            </div>

            @if ($shown)
                <div class="da-foot-note">
                    <span>{{ count($shown) }} OTs · {{ $courses[$coursePk] ?? '' }} &nbsp;|&nbsp; Director assessment: TI → COE</span>
                    <span>Faculty marks are read-only · shown once the faculty approve them</span>
                </div>
            @endif
        </div>
    </div>
</div>

@include('admin.coe.partials.colvis_modal', ['id' => 'daColumnVisibilityModal', 'grid' => 'daColumnToggleGrid'])
@endsection

@push('scripts')
<script src="{{ asset('js/coe-grid.js') }}?v={{ @filemtime(public_path('js/coe-grid.js')) ?: time() }}"></script>
<script>
$(function () {
    'use strict';

    var CSRF = { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' };
    var SAVE_URL = @json(route('admin.coe.evaluation-marks.save'));

    var dt = CoeGrid.init({
        table: '#daTable',
        title: @json('Evaluation & Marks - Director Assessment' . ($coursePk ? ' - ' . $courses[$coursePk] : '')),
        snoCol: false,
        // Rows arrive in drive, subject and OT-code order (A2 before A10).
        order: [],
        noSort: @json([0, $directorCol, $statusCol]),
        exclude: [0],
        rowCheck: '.da-check',
        searchToggle: '#daSearchToggle',
        searchSlot: '#daDtSearch',
        download: '#daDownloadExcel',
        pdf: '#daDownloadPdf',
        print: '#daPrintBtn',
        colvisGrid: '#daColumnToggleGrid',
        colvisKey: 'coeDirectorAssessment:hiddenColumns:v1',
        empty: @json($emptyState)
    });

    // Course / Examination change the table, so they reload it.
    $('.da-filter').on('change', function () {
        if (this.name === 'course') { $('#daFilterForm select[name="drive"]').val(''); }
        $('#daFilterForm').submit();
    });

    if (!$('#daApproveBtn').length) { return; }

    function rows() { return dt.rows().nodes().to$(); }
    function cell($tr) { return $tr.attr('data-key') + '|' + $tr.attr('data-student'); }
    function validMark(value, max) {
        value = $.trim(value);
        return value === '' || (/^\d+(\.\d{1,2})?$/.test(value) && parseFloat(value) <= max);
    }

    // Ticked rows: highlighted, as in the Figma; OT profile opens the first ticked one.
    function syncPicked() {
        rows().each(function () {
            $(this).toggleClass('is-picked', $(this).find('.da-check').prop('checked'));
        });
        var $first = rows().filter('.is-picked').first();
        if ($first.length) {
            $('#daProfileView').attr('href', $first.find('.da-review-btn').attr('href'));
        }
    }
    $(document).on('change', '.da-check', syncPicked);
    $('#daCheckAll').on('change', function () {
        // Only the rows the search leaves.
        dt.rows({ search: 'applied' }).nodes().to$().find('.da-check').prop('checked', this.checked);
        syncPicked();
    });

    // Quick entry: a mark is saved as a draft as soon as it is typed and left.
    $(document).on('input', '.da-mark', function () {
        var $in = $(this), bad = !validMark($in.val(), parseFloat($in.data('max')));
        $in.removeClass('is-saved').toggleClass('is-invalid', bad)
            .attr('title', bad ? 'Enter marks from 0 to ' + $in.data('max') + ' (up to 2 decimals)' : null);
    });
    $(document).on('change', '.da-mark', function () {
        var $in = $(this), $tr = $in.closest('tr'), value = $.trim($in.val());
        if (!validMark(value, parseFloat($in.data('max'))) || value === $in.attr('data-initial')) { return; }
        var marks = {};
        (marks[$tr.attr('data-key')] = {})[$tr.attr('data-student')] = value;
        $.ajax({ url: SAVE_URL, method: 'POST', contentType: 'application/json', headers: CSRF,
                 data: JSON.stringify({ marks: marks }) })
            .done(function () {
                $in.attr('data-initial', value).addClass('is-saved').attr('title', 'Saved as draft');
                if (value !== '') { $tr.find('.da-status__label').text('Draft saved'); }
            })
            .fail(function (xhr) {
                var res = xhr.responseJSON || {};
                $in.addClass('is-invalid').attr('title', res.message || 'Could not save.');
                Swal.fire({ icon: 'error', title: 'Not saved', text: res.message || 'Could not save the marks.' });
            });
    });

    // Approve: send the ticked OTs to COE. Each needs marks and remarks (OT profile),
    // and every faculty mark received.
    $('#daApproveBtn').on('click', function () {
        var $picked = rows().filter(function () { return $(this).find('.da-check').prop('checked'); });
        if (!$picked.length) {
            Swal.fire({ icon: 'info', title: 'Select OTs', text: 'Tick the OTs whose Director assessment you want to send to COE.' });
            return;
        }
        var bad = $picked.find('.da-mark').filter(function () { return !validMark($(this).val(), parseFloat($(this).data('max'))); }).length;
        if (bad) {
            Swal.fire({ icon: 'error', title: 'Check the marks', text: bad + ' mark(s) are not valid. Fix the highlighted cells.' });
            return;
        }
        Swal.fire({
            icon: 'question', title: 'Send to COE?',
            text: 'The Director assessment of ' + $picked.length + ' OT(s) will be locked and sent to COE. It cannot be changed afterwards.',
            showCancelButton: true, confirmButtonText: 'Send to COE', cancelButtonText: 'Cancel'
        }).then(function (r) {
            if (!r.isConfirmed) { return; }
            var marks = {}, send = [];
            $picked.each(function () {
                var $tr = $(this), $in = $tr.find('.da-mark');
                send.push(cell($tr));
                if ($.trim($in.val()) !== $in.attr('data-initial')) {
                    (marks[$tr.attr('data-key')] = marks[$tr.attr('data-key')] || {})[$tr.attr('data-student')] = $.trim($in.val());
                }
            });
            var $btn = $('#daApproveBtn').prop('disabled', true);
            $.ajax({ url: SAVE_URL, method: 'POST', contentType: 'application/json', headers: CSRF,
                     data: JSON.stringify({ marks: marks, send: send }) })
                .done(function (res) {
                    Swal.fire({ icon: 'success', title: 'Sent to COE', text: res.message }).then(function () { window.location.reload(); });
                })
                .fail(function (xhr) {
                    var res = xhr.responseJSON || {}, $list = $('<ul class="small text-start mb-0 ps-3" style="max-height:220px;overflow:auto"></ul>');
                    $.each(res.errors || {}, function (c, message) {
                        $list.append($('<li></li>').text(message));
                        rows().filter(function () { return cell($(this)) === c; }).find('.da-mark').addClass('is-invalid').attr('title', message);
                    });
                    var $html = $('<div></div>').append($('<p class="mb-2"></p>').text('Nothing was sent. ' + (res.message || '')));
                    if ($list.children().length > 1) { $html.append($list); }
                    Swal.fire({ icon: 'error', title: 'Not sent', html: $html[0] });
                })
                .always(function () { $btn.prop('disabled', false); });
        });
    });
});
</script>
@endpush
