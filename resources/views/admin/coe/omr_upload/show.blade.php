@extends('admin.layouts.master')

@section('title', 'OMR Answer Sheets')

@php
    // Query string of the current filters, for Download / Remove Filter links.
    $active = array_filter(\Illuminate\Support\Arr::except($filters, ['per_page']), fn ($v) => $v !== '' && $v !== null);
    $showUrl = route('admin.coe.omr-upload.show', ['upload' => $upload->pk]);
    $exportUrl = route('admin.coe.omr-upload.export', ['upload' => $upload->pk] + $active);
    $from = $sheets->firstItem();
    $to = $sheets->lastItem();
@endphp

@push('styles')
<link rel="stylesheet"
      href="{{ asset('css/coe-admin.css') }}?v={{ @filemtime(public_path('css/coe-admin.css')) ?: time() }}">
<style>
.omr-answers { font-family: SFMono-Regular, Consolas, "Liberation Mono", monospace; font-size: .8rem; letter-spacing: .04em; white-space: normal; word-break: break-all; min-width: 18rem; }
.omr-summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(10rem, 1fr)); gap: .75rem; }
.omr-summary__item { border: 1px solid var(--bs-border-color); border-radius: .5rem; padding: .6rem .8rem; }
.omr-summary__label { font-size: .75rem; color: var(--bs-secondary-color); }
.omr-summary__value { font-weight: 600; word-break: break-all; }
.omr-filters .form-label { font-size: .78rem; font-weight: 600; margin-bottom: .25rem; }
.omr-filters .omr-range { display: flex; gap: .4rem; }
.omr-qgrid { display: grid; grid-template-columns: repeat(auto-fill, minmax(4.25rem, 1fr)); gap: .4rem; }
.omr-q { border: 1px solid var(--bs-border-color); border-radius: .4rem; text-align: center; padding: .25rem; }
.omr-q__no { font-size: .7rem; color: var(--bs-secondary-color); }
.omr-q__val { font-weight: 600; }
.omr-q--blank { background: var(--bs-tertiary-bg); }
.omr-q--multi { background: #fef3f2; border-color: #fecdca; color: #b42318; }
</style>
@endpush

@section('setup_content')
<div class="container-fluid coe-page">
    <x-breadcrum title="OMR Answer Sheets" :showBack="true"
                 :items="['Setup', 'COE', 'Result Management', 'OMR Upload', 'Answer Sheets']">
        <button type="button" class="btn coe-btn-header" id="omrsRematchBtn" title="Link sheets to participants again by Roll No.">
            <i class="bi bi-link-45deg" aria-hidden="true"></i><span>Re-match Participants</span>
        </button>
        <a href="{{ route('admin.coe.omr-upload.index') }}" class="btn coe-btn-header">
            <i class="bi bi-list-ul" aria-hidden="true"></i><span>All Uploads</span>
        </a>
    </x-breadcrum>

    <div class="card rounded-3 mb-3">
        <div class="card-body p-3">
            <div class="omr-summary">
                <div class="omr-summary__item"><div class="omr-summary__label">File</div><div class="omr-summary__value">{{ $upload->original_file_name }}</div></div>
                <div class="omr-summary__item"><div class="omr-summary__label">Remarks</div><div class="omr-summary__value">{{ $upload->remarks ?? '—' }}</div></div>
                <div class="omr-summary__item"><div class="omr-summary__label">Uploaded On</div><div class="omr-summary__value">{{ $upload->created_date?->format('d-m-Y h:i A') }}</div></div>
                <div class="omr-summary__item"><div class="omr-summary__label">Questions</div><div class="omr-summary__value">{{ $upload->question_count }}</div></div>
                <div class="omr-summary__item"><div class="omr-summary__label">Answer Sheets</div><div class="omr-summary__value">{{ number_format($upload->total_rows) }}</div></div>
                <div class="omr-summary__item"><div class="omr-summary__label">Linked to Participant</div><div class="omr-summary__value">{{ number_format($linked) }} / {{ number_format($upload->total_rows) }}</div></div>
                <div class="omr-summary__item"><div class="omr-summary__label">Sheets with Problems</div><div class="omr-summary__value">{{ number_format($upload->error_rows) }}</div></div>
            </div>
        </div>
    </div>

    {{-- ── Filters: a plain GET form, so every page link keeps them ── --}}
    <div class="card rounded-3 mb-3">
        <div class="card-body p-3">
            <form method="GET" action="{{ $showUrl }}" class="omr-filters" id="omrsFilterForm">
                <div class="row g-3">
                    <div class="col-12 col-md-6 col-xl-3">
                        <label class="form-label" for="fQ">Search</label>
                        <input type="search" id="fQ" name="q" value="{{ $filters['q'] }}" class="form-control form-control-sm" maxlength="100"
                               placeholder="Barcode, Roll No., name, OT code, remarks…">
                    </div>
                    <div class="col-6 col-md-3 col-xl-2">
                        <label class="form-label" for="fBarcode">Barcode</label>
                        <input type="text" id="fBarcode" name="barcode" value="{{ $filters['barcode'] }}" class="form-control form-control-sm" maxlength="30">
                    </div>
                    <div class="col-6 col-md-3 col-xl-2">
                        <label class="form-label" for="fRoll">Roll No.</label>
                        <input type="text" id="fRoll" name="roll" value="{{ $filters['roll'] }}" class="form-control form-control-sm" maxlength="50">
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <label class="form-label" for="fParticipant">Participant / OT Code</label>
                        <input type="text" id="fParticipant" name="participant" value="{{ $filters['participant'] }}" class="form-control form-control-sm" maxlength="100">
                    </div>
                    <div class="col-12 col-md-6 col-xl-2">
                        <label class="form-label" for="fCourse">Course</label>
                        <select id="fCourse" name="course" class="form-select form-select-sm">
                            <option value="">All</option>
                            @foreach ($courses as $pk => $name)
                                <option value="{{ $pk }}" @selected((string) $filters['course'] === (string) $pk)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-md-3 col-xl-2">
                        <label class="form-label" for="fStatus">Status</label>
                        <select id="fStatus" name="status" class="form-select form-select-sm">
                            <option value="">All</option>
                            @foreach (['OK', 'Problem'] as $v)
                                <option value="{{ $v }}" @selected($filters['status'] === $v)>{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-3 col-xl-2">
                        <label class="form-label" for="fLink">Participant Link</label>
                        <select id="fLink" name="link" class="form-select form-select-sm">
                            <option value="">All</option>
                            @foreach (['Linked', 'Not linked'] as $v)
                                <option value="{{ $v }}" @selected($filters['link'] === $v)>{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <label class="form-label" for="fProblem">Problem</label>
                        <select id="fProblem" name="problem" class="form-select form-select-sm">
                            <option value="">All</option>
                            @foreach ($problemTypes as $key => $label)
                                <option value="{{ $key }}" @selected($filters['problem'] === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-3 col-xl-1">
                        <label class="form-label" for="fBooklet">Booklet</label>
                        <select id="fBooklet" name="booklet" class="form-select form-select-sm">
                            <option value="">All</option>
                            @foreach ($booklets as $b)
                                <option value="{{ $b }}" @selected($filters['booklet'] === $b)>{{ $b }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-3 col-xl-2">
                        <label class="form-label" for="fMulti">Multi-marked</label>
                        <select id="fMulti" name="multi" class="form-select form-select-sm">
                            <option value="">All</option>
                            <option value="yes" @selected($filters['multi'] === 'yes')>Has multi-marked</option>
                            <option value="no" @selected($filters['multi'] === 'no')>None</option>
                        </select>
                    </div>
                    @if ($upload->has_score)
                        <div class="col-12 col-md-6 col-xl-2">
                            <label class="form-label" for="fScoreMin">Score</label>
                            <div class="omr-range">
                                <input type="number" id="fScoreMin" name="score_min" value="{{ $filters['score_min'] }}" class="form-control form-control-sm" placeholder="From" step="1">
                                <input type="number" name="score_max" value="{{ $filters['score_max'] }}" class="form-control form-control-sm" placeholder="To" step="1" aria-label="Score to">
                            </div>
                        </div>
                    @endif

                    <div class="col-12 col-md-6 col-xl-2">
                        <label class="form-label" for="fAttMin">Attempted</label>
                        <div class="omr-range">
                            <input type="number" id="fAttMin" name="attempted_min" value="{{ $filters['attempted_min'] }}" class="form-control form-control-sm" placeholder="From" min="0" max="{{ $upload->question_count }}" step="1">
                            <input type="number" name="attempted_max" value="{{ $filters['attempted_max'] }}" class="form-control form-control-sm" placeholder="To" min="0" max="{{ $upload->question_count }}" step="1" aria-label="Attempted to">
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-xl-2">
                        <label class="form-label" for="fLineFrom">Line</label>
                        <div class="omr-range">
                            <input type="number" id="fLineFrom" name="line_from" value="{{ $filters['line_from'] }}" class="form-control form-control-sm" placeholder="From" min="1" step="1">
                            <input type="number" name="line_to" value="{{ $filters['line_to'] }}" class="form-control form-control-sm" placeholder="To" min="1" step="1" aria-label="Line to">
                        </div>
                    </div>
                    <div class="col-6 col-md-3 col-xl-2">
                        <label class="form-label" for="fSort">Sort by</label>
                        <select id="fSort" name="sort" class="form-select form-select-sm">
                            @foreach ($sorts as $key => $label)
                                <option value="{{ $key }}" @selected($filters['sort'] === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-3 col-xl-2">
                        <label class="form-label" for="fDir">Order</label>
                        <select id="fDir" name="dir" class="form-select form-select-sm">
                            <option value="asc" @selected($filters['dir'] === 'asc')>Ascending</option>
                            <option value="desc" @selected($filters['dir'] === 'desc')>Descending</option>
                        </select>
                    </div>
                    <input type="hidden" name="per_page" value="{{ $filters['per_page'] }}">

                    <div class="col-12 d-flex flex-wrap align-items-end gap-2">
                        <button type="submit" class="btn coe-btn coe-btn-primary btn-sm">
                            <i class="bi bi-funnel" aria-hidden="true"></i> Apply Filter
                        </button>
                        <a href="{{ $showUrl }}" class="btn programme-dt-btn-reset btn-sm">Remove Filter</a>
                        <div class="ms-auto d-flex flex-wrap gap-2 coe-secondary-actions">
                            <a href="{{ $exportUrl }}" id="omrsDownloadBtn" class="btn programme-dt-btn-columns btn-sm" title="Download every sheet matching these filters as CSV">
                                <i class="bi bi-download" aria-hidden="true"></i><span>Download ({{ number_format($sheets->total()) }})</span>
                            </a>
                            <button type="button" id="omrsPrintBtn" class="btn programme-dt-btn-columns btn-sm" title="Print the rows on this page">
                                <i class="bi bi-printer" aria-hidden="true"></i><span>Print (this page)</span>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">
            @if ($filtered)
                <p class="small text-muted mb-3">
                    {{ number_format($sheets->total()) }} of {{ number_format($upload->total_rows) }} sheets match the filters.
                </p>
            @endif

            <div class="table-responsive">
                <table id="omrsTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                    <thead>
                        <tr>
                            <th scope="col">S. No.</th>
                            <th scope="col" class="coe-col-center">Line</th>
                            <th scope="col">NCS Header</th>
                            <th scope="col">Barcode</th>
                            <th scope="col">Roll No.</th>
                            <th scope="col">Participant</th>
                            <th scope="col">OT Code</th>
                            <th scope="col">Course</th>
                            <th scope="col" class="coe-col-center">Link</th>
                            <th scope="col" class="coe-col-center">Booklet</th>
                            <th scope="col" class="coe-col-center">Attempted</th>
                            <th scope="col" class="coe-col-center">Blank</th>
                            <th scope="col" class="coe-col-center">Multi-marked</th>
                            @if ($upload->has_score)
                                <th scope="col" class="coe-col-center">Score</th>
                            @endif
                            <th scope="col">Answers (Q1–Q{{ $upload->question_count }})</th>
                            <th scope="col" class="coe-col-center">Status</th>
                            <th scope="col">Remarks</th>
                            <th scope="col" class="omr-no-print">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sheets as $i => $row)
                            @php
                                // Blank as '-', in groups of five so a question is easy to find.
                                $answerText = collect($row['answers'])
                                    ->map(fn ($a) => $a === '' ? '-' : $a)
                                    ->chunk(5)->map(fn ($c) => $c->implode(''))->implode(' ');
                            @endphp
                            <tr data-row='@json($row)'>
                                <td>{{ $from + $i }}</td>
                                <td class="coe-col-center">{{ $row['line'] }}</td>
                                <td class="text-nowrap"><small>{{ $row['header'] ?? '—' }}</small></td>
                                <td>{{ $row['barcode'] ?? '—' }}</td>
                                <td>{{ $row['roll'] ?? '—' }}</td>
                                <td class="coe-col-wrap">{{ $row['participant'] ?? '—' }}</td>
                                <td>{{ $row['ot_code'] ?? '—' }}</td>
                                <td class="coe-col-wrap">{{ $row['course'] ?? '—' }}</td>
                                <td class="coe-col-center">
                                    <span class="coe-state coe-state--{{ $row['student_pk'] ? 'active' : 'inactive' }}">{{ $row['student_pk'] ? 'Linked' : 'Not linked' }}</span>
                                    @if (! $row['student_pk'] && $row['match_remarks'])<div class="small text-muted mt-1">{{ $row['match_remarks'] }}</div>@endif
                                </td>
                                <td class="coe-col-center">{{ $row['booklet'] ?? '—' }}</td>
                                <td class="coe-col-center">{{ $row['attempted'] }}</td>
                                <td class="coe-col-center">{{ $row['blank'] }}</td>
                                <td class="coe-col-center">{{ $row['multi'] }}</td>
                                @if ($upload->has_score)
                                    <td class="coe-col-center">{{ $row['score'] ?? '—' }}</td>
                                @endif
                                <td><span class="omr-answers">{{ $answerText }}</span></td>
                                <td class="coe-col-center">
                                    <span class="coe-state coe-state--{{ $row['has_error'] ? 'inactive' : 'active' }}">{{ $row['has_error'] ? 'Problem' : 'OK' }}</span>
                                </td>
                                <td class="coe-col-wrap">{{ $row['remarks'] ?? '—' }}</td>
                                <td class="omr-no-print">
                                    <div class="coe-act-group" role="group" aria-label="Row actions">
                                        <button type="button" class="coe-act coe-act--edit omr-view">
                                            <span class="coe-act__icon"><i class="bi bi-grid-3x3-gap" aria-hidden="true"></i></span>
                                            <span class="coe-act__label">Answers</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $upload->has_score ? 18 : 17 }}" class="text-center py-4 text-muted">
                                    {{ $filtered ? 'No answer sheets match these filters.' : 'This upload has no answer sheets.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Footer: Laravel pagination + count + page size --}}
            <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3">
                <div class="programme-dt-pagination">
                    {{ $sheets->links('vendor.pagination.custom') }}
                </div>
                <div class="programme-dt-count d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <label class="d-inline-flex align-items-center gap-2 mb-0" for="omrsPerPage">
                        <span>Showing</span>
                        <select id="omrsPerPage" class="form-select form-select-sm" style="width:auto;">
                            @foreach ($perPageOptions as $size)
                                <option value="{{ $size }}" @selected($filters['per_page'] === $size)>{{ $size }}</option>
                            @endforeach
                        </select>
                    </label>
                    <span class="text-muted">
                        {{ $from ? number_format($from) . '–' . number_format($to) . ' of ' : '' }}{{ number_format($sheets->total()) }} sheets
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- One sheet's answers, question by question. --}}
<div class="modal fade" id="omrsAnswersModal" tabindex="-1" aria-labelledby="omrsAnswersLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content coe-modal border-0 shadow">
            <div class="modal-header coe-modal-header coe-modal-header--rule">
                <h5 class="modal-title" id="omrsAnswersLabel">Answers</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body coe-modal-body">
                <p class="small text-muted mb-2" id="omrsAnswersMeta"></p>
                <div class="omr-qgrid" id="omrsAnswersGrid"></div>
                <p class="small text-muted mt-3 mb-0">— blank &nbsp;·&nbsp; * more than one option marked</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    'use strict';

    var HAS_SCORE = @json($upload->has_score);

    /* Rows per page: back to page 1 with the same filters. */
    $('#omrsPerPage').on('change', function () {
        var form = document.getElementById('omrsFilterForm');
        form.elements['per_page'].value = this.value;
        form.submit();
    });

    /* Empty fields stay out of the URL, so page links stay short. */
    $('#omrsFilterForm').on('submit', function () {
        $(this).find('input, select').each(function () {
            if (this.name && this.value === '') { this.disabled = true; }
        });
    });
    // Back button restores the page as it was left — with those fields still disabled.
    window.addEventListener('pageshow', function () { $('#omrsFilterForm').find('input, select').prop('disabled', false); });

    /* Print: the rows on this page, in a window of their own. */
    $('#omrsPrintBtn').on('click', function () {
        var $table = $('#omrsTable').clone();
        $table.find('.omr-no-print').remove();
        var win = window.open('', '_blank');
        if (!win) { return; }
        var doc = win.document;
        doc.open();
        doc.write('<!doctype html><html><head><meta charset="utf-8"><title></title>' +
            '<style>@page{size:A3 landscape;margin:8mm}body{font-family:Arial,sans-serif;font-size:8px}' +
            'table{width:100%;border-collapse:collapse;table-layout:fixed}th,td{border:1px solid #999;padding:2px 3px;word-break:break-word;vertical-align:top}' +
            'h1{font-size:13px;margin:0 0 6px}</style></head><body></body></html>');
        doc.close();
        doc.title = 'OMR Answer Sheets';
        $('<h1>').text(@json('OMR Answer Sheets — ' . $upload->original_file_name) + '  (' + $('.programme-dt-count .text-muted').text().trim() + ')').appendTo(doc.body);
        doc.body.appendChild(doc.importNode($table[0], true));
        win.focus();
        win.print();
    });

    /* ── Re-match: link sheets to participants again by Roll No. ── */
    $('#omrsRematchBtn').on('click', function () {
        var $btn = $(this);
        if ($btn.prop('disabled')) { return; }
        $btn.prop('disabled', true);
        $.ajax({
            url: @json(route('admin.coe.omr-upload.rematch', ['upload' => $upload->pk])),
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || '', 'Accept': 'application/json' }
        }).done(function (res) {
            Swal.fire({ icon: 'success', title: 'Done', text: res.message }).then(function () { window.location.reload(); });
        }).fail(function (xhr) {
            var res = xhr && xhr.responseJSON;
            Swal.fire({ icon: 'error', title: 'Error', text: (res && res.message) || 'Something went wrong. Please try again.' });
        }).always(function () { $btn.prop('disabled', false); });
    });

    $('#omrsTable').on('click', '.omr-view', function () {
        var row = JSON.parse($(this).closest('tr').attr('data-row'));
        var meta = ['Barcode: ' + (row.barcode || '—'), 'Roll No.: ' + (row.roll || '—'), 'Participant: ' + (row.participant || '—'), 'Booklet: ' + (row.booklet || '—')];
        if (HAS_SCORE) { meta.push('Score: ' + (row.score === null ? '—' : row.score)); }
        $('#omrsAnswersLabel').text('Answers — line ' + row.line);
        $('#omrsAnswersMeta').text(meta.join('  |  '));

        var $grid = $('#omrsAnswersGrid').empty();
        row.answers.forEach(function (a, i) {
            var cls = a === '' ? ' omr-q--blank' : (a === '*' ? ' omr-q--multi' : '');
            $('<div class="omr-q' + cls + '"></div>')
                .append($('<div class="omr-q__no"></div>').text('Q' + (i + 1)))
                .append($('<div class="omr-q__val"></div>').text(a === '' ? '—' : a))
                .appendTo($grid);
        });
        bootstrap.Modal.getOrCreateInstance(document.getElementById('omrsAnswersModal')).show();
    });
});
</script>
@endpush
