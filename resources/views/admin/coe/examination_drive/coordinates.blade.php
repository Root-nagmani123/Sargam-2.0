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
    </div>

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
                                <td>{{ $row['exam_date'] }}</td>
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

            <div class="edc-footer">
                <a href="{{ route('admin.coe.examination-drive.index') }}" class="btn coe-btn coe-btn-cancel">Back</a>
            </div>
        </div>
    </div>
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
