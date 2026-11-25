@extends('admin.layouts.master')

@section('title', 'Faculty Coordinates – ' . $drive['name'])

@push('styles')
<link rel="stylesheet" href="{{ asset('css/coe-admin.css') }}?v={{ @filemtime(public_path('css/coe-admin.css')) ?: time() }}">
<style>
.edc-toolbar         { display:flex;justify-content:flex-end;gap:10px;margin-bottom:16px; }
.edc-tool-btn        { display:inline-flex;align-items:center;gap:8px;padding:9px 18px;border:1px solid var(--ds-line);border-radius:8px;
                       background:#fff;color:var(--ds-brand);font-size:14px;font-weight:500; }
.edc-tool-btn:hover  { background:var(--ds-surface-2); }
.edc-info-bar        { background:#fff;border:1px solid var(--ds-line);border-radius:12px;padding:18px 24px;margin-bottom:20px;
                       display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px; }
.edc-info-item       { display:flex;flex-direction:column;gap:4px; }
.edc-info-label      { font-size:12px;color:var(--ds-ink-muted);text-transform:uppercase;letter-spacing:.4px; }
.edc-info-value      { font-size:15px;font-weight:600;color:var(--ds-ink); }
/* Component table */
.edc-coord-table     { width:100%;border-collapse:separate;border-spacing:0; }
.edc-coord-table > thead th { padding:12px 14px;background:var(--ds-surface-2);font-size:13px;font-weight:500;color:var(--ds-ink-muted);white-space:nowrap; }
.edc-coord-table > thead th:first-child { border-radius:8px 0 0 8px; }
.edc-coord-table > thead th:last-child  { border-radius:0 8px 8px 0; }
.edc-coord-table > tbody > tr.edc-main > td { padding:16px 14px;border-bottom:1px solid var(--ds-line);font-size:14px;vertical-align:middle; }
.edc-coord-table > tbody > tr.edc-main.is-open > td { border-bottom:none; }
.edc-people          { max-width:220px; }
.edc-people a        { color:var(--ds-brand);text-decoration:underline; }
/* Open / Close toggle */
.edc-toggle          { display:inline-flex;flex-direction:column;align-items:center;gap:2px;border:0;background:none;
                       color:var(--ds-brand);font-size:13px;padding:2px 6px; }
.edc-toggle i        { font-size:14px;transition:transform .15s; }
.edc-toggle[aria-expanded="true"] i { transform:rotate(180deg); }
.edc-toggle:disabled { color:var(--ds-ink-muted);opacity:.45;cursor:not-allowed; }
/* Expanded faculty / roll-number sub-table */
.edc-detail > td     { padding:0 0 12px !important;border-bottom:1px solid var(--ds-line); }
.edc-sub-table       { width:100%;border-collapse:collapse; }
.edc-sub-table th    { padding:11px 14px;background:#e8eefb;font-size:13px;font-weight:500;color:var(--ds-ink); }
.edc-sub-table td    { padding:13px 14px;border-bottom:1px solid var(--ds-line);font-size:14px; }
.edc-sub-table tr:last-child td { border-bottom:none; }
.edc-footer          { display:flex;justify-content:flex-end;margin-top:22px; }
/* Re-exam (ReexamService::overview): main exam + candidates on a re-exam, re-exams on a main drive */
.edc-rx-banner       { display:flex;flex-wrap:wrap;align-items:center;gap:8px;margin-bottom:16px;padding:12px 18px;border-radius:10px;
                       background:#fffaeb;border:1px solid #fedf89;color:#93370d;font-size:14px; }
.edc-rx-banner a     { color:var(--ds-brand);font-weight:600; }
.edc-section-title   { font-size:16px;font-weight:600;color:var(--ds-ink);margin:0 0 12px; }
.edc-section-note    { font-size:13px;color:var(--ds-ink-muted);margin:-6px 0 12px; }
.edc-coord-table td small { display:block;margin-top:2px;color:var(--ds-ink-muted);font-size:12px; }
.edc-badge           { display:inline-block;padding:3px 10px;border-radius:6px;font-size:13px;font-weight:500;line-height:1.4;white-space:nowrap; }
.edc-badge--failed, .edc-badge--fail { background:#fde2e4;color:#d92d20; }
.edc-badge--absent   { background:#fef0c7;color:#b54708; }
.edc-badge--pass     { background:#dcfae6;color:#067647; }
.edc-badge--pending  { background:#eef0f3;color:#475467; }
@media print {
    .edc-toolbar, .edc-footer, .edc-toggle { display:none !important; }
    .edc-detail.d-none   { display:table-row !important; }
}
</style>
@endpush

@section('setup_content')
@php
    // A row expands only when it has more than one person to list.
    $peopleFor = function (array $row): array {
        if ($row['sub_coordinators']) {
            return $row['sub_coordinators'];
        }
        // System-sourced components are marked by source, not by a person.
        return in_array($row['source'], ['Sargam', 'Sargam 2.0'], true) ? [$row['source']] : [];
    };

    // A row opens when it has a faculty (one or more) to list with their roll numbers.
    $canOpen = fn (array $row, array $people) => $people !== [] && ! in_array($row['source'], ['Sargam', 'Sargam 2.0'], true);

    // Codes as ranges, e.g. "AI-0001 – AI-0004 (4 OTs)" (same text as the export).
    $codeText = fn ($codes) => \App\Http\Controllers\Admin\COE\ExaminationDriveController::codeRangeText($codes);

    // Download / Print: Sargam master export (ExaminationDriveController::coordinatesExport).
    $coordExport = fn (string $format) => route('admin.coe.examination-drive.coordinates.export', ['id' => $drive['id'], 'format' => $format]);
@endphp
<div class="container-fluid coe-page">
    <x-breadcrum title="Faculty Coordinates & Components" :showBack="true"
                 :backUrl="route('admin.coe.examination-drive.index')"
                 :items="['Setup', 'COE', 'Examination Management', 'Faculty Coordinates & Components']" />

    <div class="edc-toolbar">
        <div class="dropdown">
            <button type="button" class="edc-tool-btn dropdown-toggle" id="edcDownload"
                    data-bs-toggle="dropdown" aria-expanded="false" title="Download">
                <i class="bi bi-download" aria-hidden="true"></i>Download
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="edcDownload">
                <li><a class="dropdown-item" href="{{ $coordExport('csv') }}">
                    <i class="bi bi-filetype-csv me-1" aria-hidden="true"></i> CSV</a></li>
                <li><a class="dropdown-item" href="{{ $coordExport('excel') }}">
                    <i class="bi bi-file-earmark-excel me-1" aria-hidden="true"></i> Excel (.xlsx)</a></li>
                <li><a class="dropdown-item" href="{{ $coordExport('pdf') }}">
                    <i class="bi bi-file-earmark-pdf me-1" aria-hidden="true"></i> PDF</a></li>
            </ul>
        </div>
        <a href="{{ $coordExport('print') }}" target="_blank" rel="noopener" class="edc-tool-btn text-decoration-none" id="edcPrint" title="Print">
            <i class="bi bi-printer" aria-hidden="true"></i>Print
        </a>
    </div>

    @php($isRx = ($reexam['type'] ?? null) === 'reexam')
    @if ($isRx)
        <div class="edc-rx-banner" role="note">
            <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
            <span>Re-exam {{ $reexam['no'] }} of
                @if ($reexam['main'])
                    <a href="{{ route('admin.coe.examination-drive.coordinates', $reexam['main']['id']) }}">{{ $reexam['main']['name'] }}</a>.
                @else
                    a deleted drive.
                @endif
                The main exam and the re-exam OTs are shown below.</span>
        </div>
    @endif

    {{-- Drive info bar. FACULTY = faculty mapped when the drive was created. --}}
    <div class="edc-info-bar">
        <div class="edc-info-item">
            <span class="edc-info-label">Examination Name</span>
            <span class="edc-info-value">{{ $drive['name'] }}</span>
        </div>
        <div class="edc-info-item">
            <span class="edc-info-label">Examination Type</span>
            <span class="edc-info-value">{{ $drive['exam_type'] }}</span>
        </div>
        <div class="edc-info-item">
            <span class="edc-info-label">Term Name</span>
            <span class="edc-info-value">{{ $drive['term'] }}</span>
        </div>
        <div class="edc-info-item">
            <span class="edc-info-label">Course Name</span>
            <span class="edc-info-value">{{ $drive['course'] }}</span>
        </div>
        <div class="edc-info-item">
            <span class="edc-info-label">Academic Session</span>
            <span class="edc-info-value">{{ $drive['session'] }}</span>
        </div>
        <div class="edc-info-item">
            <span class="edc-info-label">Faculty</span>
            <span class="edc-info-value">{{ implode(', ', $drive['drive_faculty']) ?: '—' }}</span>
        </div>
        @if ($isRx)
            <div class="edc-info-item">
                <span class="edc-info-label">Re-exam Dates</span>
                <span class="edc-info-value">{{ $drive['start_date'] ?: '-' }} to {{ $drive['end_date'] ?: '-' }}</span>
            </div>
            <div class="edc-info-item">
                <span class="edc-info-label">Status</span>
                <span class="edc-info-value">{{ $drive['status'] }}</span>
            </div>
        @endif
    </div>

    @if ($isRx)
        <h2 class="edc-section-title">Re-exam {{ $reexam['no'] }} · Components</h2>
    @endif

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">
            <div class="table-responsive">
                <table class="edc-coord-table" id="edcCoordTable">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Components</th>
                            <th>Max Marks</th>
                            <th>Passing Marks</th>
                            <th>Source</th>
                            <th>Faculty &amp; Subcoordinates</th>
                            <th>Weightage %</th>
                            <th>Exam Date</th>
                            <th>Exam Mode</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($drive['component_rows'] as $i => $row)
                            @php($people = $peopleFor($row))
                            <tr class="edc-main" data-row="{{ $i }}">
                                <td>{{ $row['subject'] }}</td>
                                <td>{{ $row['component'] }}</td>
                                <td>{{ $row['max_marks'] }}</td>
                                <td>{{ $row['passing_marks'] }}</td>
                                <td>{{ $row['source'] }}</td>
                                <td class="edc-people">
                                    @if (count($people) > 1)
                                        {{-- Names link to the expanded list below (escaped text, no HTML popover). --}}
                                        @foreach ($people as $name)
                                            <a href="#edcDetail{{ $i }}" class="edc-toggle-link" data-row="{{ $i }}">{{ $name }}</a>{{ $loop->last ? '' : ',' }}
                                        @endforeach
                                    @else
                                        {{ $people[0] ?? '—' }}
                                    @endif
                                </td>
                                <td>{{ $row['weightage'] }}</td>
                                {{-- A re-exam's copied rows have no date until the faculty sets one. --}}
                                <td>{{ $isRx && ! $row['dated'] ? 'Not set yet' : $row['exam_date'] }}</td>
                                <td>{{ $row['exam_mode'] }}</td>
                                <td class="text-center">
                                    <button type="button" class="edc-toggle" data-row="{{ $i }}"
                                            aria-expanded="false" aria-controls="edcDetail{{ $i }}"
                                            @disabled(! $canOpen($row, $people))
                                            title="{{ $canOpen($row, $people) ? 'Show faculty and roll numbers' : 'No faculty assigned to this component' }}">
                                        <i class="bi bi-chevron-down" aria-hidden="true"></i>
                                        <span class="edc-toggle-label">Open</span>
                                    </button>
                                </td>
                            </tr>
                            @if ($canOpen($row, $people))
                                <tr class="edc-detail d-none" id="edcDetail{{ $i }}">
                                    <td colspan="10">
                                        <table class="edc-sub-table">
                                            <thead>
                                                <tr>
                                                    <th>Faculty</th>
                                                    <th style="width:30%">OT's Roll Number</th>
                                                    <th style="width:30%">Unique Participant Codes</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($people as $name)
                                                    <tr>
                                                        <td>{{ $name }}</td>
                                                        {{-- Set on Complete Setup: roll numbers from Select Roll Number,
                                                             Unique Participant Codes from the Multi Faculty tab. --}}
                                                        @foreach (['faculty_codes', 'faculty_unique_codes'] as $list)
                                                            @if (filled($row[$list][$name] ?? null))
                                                                <td>{{ $codeText($row[$list][$name]) }}</td>
                                                            @else
                                                                <td class="coe-muted">Not allocated</td>
                                                            @endif
                                                        @endforeach
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr class="edc-main">
                                <td colspan="10" class="text-center coe-muted py-4">No components mapped to this drive yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @unless ($reexam)
                <div class="edc-footer">
                    <a href="{{ route('admin.coe.examination-drive.index') }}" class="btn coe-btn coe-btn-cancel">Back</a>
                </div>
            @endunless
        </div>
    </div>

    @if ($isRx)
        @php($rx = $reexam['result'])
        {{-- Main exam: the main drive's components of the re-exam subjects. --}}
        @if ($reexam['main'])
            <div class="card overflow-hidden rounded-3 mt-4">
                <div class="card-body p-3 p-md-4">
                    <h2 class="edc-section-title">Main Exam · {{ $reexam['main']['name'] }}</h2>
                    <p class="edc-section-note">{{ $reexam['main']['exam_type'] }} · {{ $reexam['main']['start_date'] }} to {{ $reexam['main']['end_date'] }} · {{ $reexam['main']['status'] }}.
                        Only the subjects of this re-exam are listed.</p>
                    <div class="table-responsive">
                        <table class="edc-coord-table">
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Components</th>
                                    <th>Max Marks</th>
                                    <th>Passing Marks</th>
                                    <th>Exam Date</th>
                                    <th>Exam Mode</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($reexam['main']['components'] as $c)
                                    <tr class="edc-main">
                                        <td>{{ $c['subject'] }}</td>
                                        <td>{{ $c['component'] }}</td>
                                        <td>{{ $c['max_marks'] }}</td>
                                        <td>{{ $c['passing_marks'] }}</td>
                                        <td>{{ $c['exam_range'] }}</td>
                                        <td>{{ $c['exam_mode'] }}</td>
                                    </tr>
                                @empty
                                    <tr class="edc-main"><td colspan="6" class="text-center coe-muted py-4">No components on the main drive.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        {{-- Candidates: main exam reason + score, re-exam score + result (ReexamService::result). --}}
        <div class="card overflow-hidden rounded-3 mt-4">
            <div class="card-body p-3 p-md-4">
                <h2 class="edc-section-title">Re-exam OTs ({{ count($rx['ots']) }})</h2>
                <p class="edc-section-note">Pass {{ $rx['counts']['Pass'] }} · Fail {{ $rx['counts']['Fail'] }} · Pending {{ $rx['counts']['Pending'] }}.
                    Re-exam marks count once approved (no moderation); they replace the main exam marks for these subjects.
                    {{ $rx['message'] }}</p>
                <div class="table-responsive">
                    <table class="edc-coord-table">
                        <thead>
                            <tr>
                                <th>Officer Trainee</th>
                                <th>Roll No.</th>
                                <th>Subject</th>
                                <th>Main Exam</th>
                                <th>Main Score</th>
                                <th>Re-exam Score</th>
                                <th>Subject Result</th>
                                <th>OT Result</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rx['rows'] as $r)
                                @php($ot = $rx['ots'][$r['student_pk']] ?? null)
                                <tr class="edc-main">
                                    <td>{{ $r['name'] ?: '-' }}@if ($r['ot_code'])<small>{{ $r['ot_code'] }}</small>@endif</td>
                                    <td>{{ $r['roll'] ?: '-' }}</td>
                                    <td>{{ $r['subject'] }}</td>
                                    <td><span class="edc-badge edc-badge--{{ strtolower($r['previous']) ?: 'pending' }}">{{ $r['previous'] ?: '-' }}</span></td>
                                    <td>{{ number_format($r['previous_score'], 1) }}%</td>
                                    <td>{{ $r['score'] === null ? '-' : number_format($r['score'], 1) . '%' }}@if ($r['absent'])<small>Absent</small>@endif</td>
                                    <td><span class="edc-badge edc-badge--{{ strtolower($r['result']) }}">{{ $r['result'] }}</span></td>
                                    <td>@if ($ot)<span class="edc-badge edc-badge--{{ strtolower($ot['result']) }}">{{ $ot['result'] }}</span>@endif</td>
                                </tr>
                            @empty
                                <tr class="edc-main"><td colspan="8" class="text-center coe-muted py-4">This re-exam has no OTs.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @elseif (($reexam['type'] ?? null) === 'main')
        {{-- A main drive: its re-exams. --}}
        <div class="card overflow-hidden rounded-3 mt-4">
            <div class="card-body p-3 p-md-4">
                <h2 class="edc-section-title">Re-exams ({{ count($reexam['reexams']) }})</h2>
                <div class="table-responsive">
                    <table class="edc-coord-table">
                        <thead>
                            <tr>
                                <th>Re-exam</th>
                                <th>Drive Name</th>
                                <th>Dates</th>
                                <th>OTs</th>
                                <th>Status</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($reexam['reexams'] as $r)
                                <tr class="edc-main">
                                    <td>Re-exam {{ $r['no'] }}</td>
                                    <td>{{ $r['name'] }}</td>
                                    <td>{{ $r['start_date'] }} to {{ $r['end_date'] }}</td>
                                    <td>{{ $r['ots'] }}<small>{{ $r['subjects'] }} OT-subject(s)</small></td>
                                    <td>{{ $r['status'] }}</td>
                                    <td class="text-center"><a href="{{ route('admin.coe.examination-drive.coordinates', $r['id']) }}" class="edc-toggle text-decoration-none">
                                        <i class="bi bi-eye" aria-hidden="true"></i><span>View</span></a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    @if ($reexam)
        <div class="edc-footer">
            <a href="{{ route('admin.coe.examination-drive.index') }}" class="btn coe-btn coe-btn-cancel">Back</a>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
$(function () {
    'use strict';

    function setOpen(rowIdx, open) {
        var $btn = $('.edc-toggle[data-row="' + rowIdx + '"]');
        if ($btn.prop('disabled')) { return; }
        $('#edcDetail' + rowIdx).toggleClass('d-none', !open);
        $btn.closest('tr').toggleClass('is-open', open);
        $btn.attr('aria-expanded', open ? 'true' : 'false')
            .find('.edc-toggle-label').text(open ? 'Close' : 'Open');
    }

    $('.edc-toggle').on('click', function () {
        setOpen($(this).data('row'), $(this).attr('aria-expanded') !== 'true');
    });

    // Clicking a name opens that row's list.
    $('.edc-toggle-link').on('click', function (e) {
        e.preventDefault();
        setOpen($(this).data('row'), true);
    });

});
</script>
@endpush
