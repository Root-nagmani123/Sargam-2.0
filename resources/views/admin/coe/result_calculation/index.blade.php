@extends('admin.layouts.master')

@section('title', 'Result Calculation')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}">
<link rel="stylesheet"
      href="{{ asset('css/coe-admin.css') }}?v={{ @filemtime(public_path('css/coe-admin.css')) ?: time() }}">
<style>
/* Info bar above the component table */
.rc-info-bar       { display:flex;flex-wrap:wrap;gap:28px;padding:14px 20px;
                     background:var(--ds-surface-2);border:1px solid var(--ds-line);
                     border-radius:8px;margin-bottom:18px; }
.rc-info-item      { display:flex;flex-direction:column;gap:2px; }
.rc-info-label     { font-size:10px;font-weight:700;color:var(--ds-ink-muted);
                     text-transform:uppercase;letter-spacing:.6px; }
.rc-info-value     { font-size:14px;font-weight:700;color:var(--ds-ink); }
/* Component table */
.rc-comp-table     { width:100%;border-collapse:collapse; }
.rc-comp-table th  { padding:9px 12px;background:var(--ds-surface-2);font-size:12px;
                     font-weight:700;color:var(--ds-ink-muted);border-bottom:1px solid var(--ds-line);
                     white-space:nowrap;cursor:pointer;user-select:none; }
.rc-comp-table th .sort-icon{ margin-left:4px;opacity:.5;font-size:10px; }
.rc-comp-table th.sorted-asc  .sort-icon,
.rc-comp-table th.sorted-desc .sort-icon{ opacity:1; }
.rc-comp-table td  { padding:10px 12px;border-bottom:1px solid var(--ds-line);
                     font-size:13px;vertical-align:middle; }
.rc-comp-table tr:last-child td{ border-bottom:none; }
/* Empty state */
.rc-empty          { display:flex;flex-direction:column;align-items:center;
                     justify-content:center;min-height:260px;gap:14px;color:var(--ds-ink-muted); }
.rc-empty-icon     { width:64px;height:64px;border-radius:50%;background:#eef2ff;
                     display:flex;align-items:center;justify-content:center; }
.rc-empty-icon svg { width:30px;height:30px;color:#7c83c8; }
.rc-empty-text     { font-size:14px;font-weight:500; }
</style>
@endpush

@section('setup_content')
<div class="container-fluid coe-page">
    <x-breadcrum title="Result Calculation" :showBack="false"
                 :items="['Setup', 'COE', 'Examination Management', 'Result Calculation']">
    </x-breadcrum>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            {{-- Toolbar --}}
            <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
                {{-- Left: filters (submit on change) --}}
                <form method="GET" action="{{ route('admin.coe.result-calculation.index') }}"
                      id="rcFilterForm" class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Filter</span>
                    <div class="programme-dt-filter-select">
                        <select name="course" id="rcFilterCourse" class="form-select"
                                aria-label="Course Name">
                            <option value="">Course Name</option>
                            @foreach ($courses as $c)
                            <option value="{{ $c }}" @selected($c === $course)>{{ $c }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="programme-dt-filter-select">
                        <select name="subject" id="rcFilterSubject" class="form-select"
                                aria-label="Subject">
                            <option value="">Subject</option>
                            @foreach ($subjects as $s)
                            <option value="{{ $s }}" @selected($s === $subject)>{{ $s }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if ($course || $subject)
                    <a href="{{ route('admin.coe.result-calculation.index') }}"
                       class="btn programme-dt-btn-reset">Remove Filter</a>
                    @else
                    <button type="button" id="rcResetBtn" class="btn programme-dt-btn-reset">Remove Filter</button>
                    @endif
                </form>

                {{-- Right: Columns + Search + Download + Print --}}
                <div class="d-flex flex-wrap align-items-center gap-2 ms-sm-auto">
                    @if ($drive)
                    <button type="button" class="btn programme-dt-btn-columns"
                            data-bs-toggle="modal" data-bs-target="#rcColVisModal">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <button type="button" id="rcSearchToggle" class="coe-search-toggle" title="Search">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <span class="visually-hidden">Search</span>
                    </button>
                    @else
                    <button type="button" class="btn programme-dt-btn-columns">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <button type="button" class="coe-search-toggle" title="Search">
                        <i class="bi bi-search" aria-hidden="true"></i>
                    </button>
                    @endif
                    <button type="button" id="rcDownloadBtn" class="btn programme-dt-btn-columns">
                        <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
                    </button>
                    <button type="button" id="rcPrintBtn" class="btn programme-dt-btn-columns">
                        <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
                    </button>
                </div>
            </div>

            {{-- Main content area --}}
            @if ($drive)

                {{-- Drive info bar --}}
                <div class="rc-info-bar">
                    <div class="rc-info-item">
                        <span class="rc-info-label">Drive Name</span>
                        <span class="rc-info-value">{{ $drive['drive'] }}</span>
                    </div>
                    <div class="rc-info-item">
                        <span class="rc-info-label">Term Name</span>
                        <span class="rc-info-value">{{ $drive['term'] }}</span>
                    </div>
                    <div class="rc-info-item">
                        <span class="rc-info-label">Course Name</span>
                        <span class="rc-info-value">{{ $drive['course'] }}</span>
                    </div>
                    <div class="rc-info-item">
                        <span class="rc-info-label">Academic Session</span>
                        <span class="rc-info-value">{{ $drive['session'] }}</span>
                    </div>
                    <div class="rc-info-item">
                        <span class="rc-info-label">Subject</span>
                        <span class="rc-info-value">{{ $drive['subject'] }}</span>
                    </div>
                </div>

                {{-- Component table --}}
                <div class="table-responsive">
                    <table class="rc-comp-table" id="rcCompTable">
                        <thead>
                            <tr>
                                <th data-col="component">Components <span class="sort-icon">⇅</span></th>
                                <th data-col="max">Max Marks <span class="sort-icon">⇅</span></th>
                                <th data-col="pass">Passing Marks <span class="sort-icon">⇅</span></th>
                                <th data-col="source">Source</th>
                                <th data-col="faculty">Faculty <span class="sort-icon">⇅</span></th>
                                <th data-col="weightage">Weightage % <span class="sort-icon">⇅</span></th>
                                <th data-col="exam_type">Exam Type</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($drive['components'] as $comp)
                            <tr>
                                <td>{{ $comp['component'] }}</td>
                                <td>{{ number_format($comp['max_marks'], 0) }}</td>
                                <td>{{ number_format($comp['passing_marks'], 2) }}</td>
                                <td>{{ $comp['source'] }}</td>
                                <td>{{ $comp['faculty'] }}</td>
                                <td>{{ number_format($comp['weightage'], 2) }}</td>
                                <td>{{ $comp['exam_type'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            @elseif ($course || $subject)

                {{-- Filters set but no matching data --}}
                <div class="rc-empty">
                    <div class="rc-empty-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="4" y1="6" x2="20" y2="6"/><line x1="8" y1="12" x2="16" y2="12"/>
                            <line x1="11" y1="18" x2="13" y2="18"/>
                        </svg>
                    </div>
                    <p class="rc-empty-text">No result data found for the selected filters.</p>
                </div>

            @else

                {{-- Default empty state --}}
                <div class="rc-empty">
                    <div class="rc-empty-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="4" y1="6" x2="20" y2="6"/><line x1="8" y1="12" x2="16" y2="12"/>
                            <line x1="11" y1="18" x2="13" y2="18"/>
                        </svg>
                    </div>
                    <p class="rc-empty-text">Use Filter to get the data</p>
                </div>

            @endif

        </div>
    </div>
</div>

@if ($drive)
{{-- Column Visibility --}}
<div class="modal fade" id="rcColVisModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0"><hr class="mt-0">
                <div class="row g-3" id="rcColVisGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
$(function () {
    'use strict';

    /* Auto-submit form when either filter changes */
    $('#rcFilterCourse, #rcFilterSubject').on('change', function () {
        $('#rcFilterForm').submit();
    });

    /* Download / Print */
    $('#rcDownloadBtn').on('click', function () {
        Swal.fire({ icon: 'info', title: 'Download', text: 'Downloading result data…', timer: 1200, showConfirmButton: false });
    });
    $('#rcPrintBtn').on('click', function () { window.print(); });

    @if ($drive)
    /* ── Sortable component table (client-side) ───────────────────────── */
    var sortCol = null;
    var sortAsc = true;

    $('#rcCompTable thead th[data-col]').on('click', function () {
        var col = $(this).data('col');
        if (sortCol === col) { sortAsc = !sortAsc; } else { sortCol = col; sortAsc = true; }
        $('#rcCompTable thead th').removeClass('sorted-asc sorted-desc');
        $(this).addClass(sortAsc ? 'sorted-asc' : 'sorted-desc');

        var colIndex = $(this).index();
        var $tbody   = $('#rcCompTable tbody');
        var rows     = $tbody.find('tr').toArray();
        rows.sort(function (a, b) {
            var av = $(a).find('td').eq(colIndex).text().trim();
            var bv = $(b).find('td').eq(colIndex).text().trim();
            var an = parseFloat(av), bn = parseFloat(bv);
            if (!isNaN(an) && !isNaN(bn)) { return sortAsc ? an - bn : bn - an; }
            return sortAsc ? av.localeCompare(bv) : bv.localeCompare(av);
        });
        $tbody.append(rows);
    });

    /* ── Column visibility ─────────────────────────────────────────────── */
    var headers = [];
    $('#rcCompTable thead th').each(function (i) {
        headers.push({ index: i, label: $(this).text().trim().replace('⇅', '').trim() });
    });
    var hidden = {};
    try { hidden = JSON.parse(localStorage.getItem('coeResultCalc:hiddenCols:v1') || '{}'); } catch (e) {}

    headers.forEach(function (h) {
        if (hidden[h.index]) {
            $('#rcCompTable thead th').eq(h.index).hide();
            $('#rcCompTable tbody td').each(function () { $(this).parent().find('td').eq(h.index).hide(); });
        }
        var $chk = $('<div class="col-6 col-md-4"><div class="form-check">' +
            '<input class="form-check-input" type="checkbox" id="rcCol' + h.index + '" ' + (hidden[h.index] ? '' : 'checked') + '>' +
            '<label class="form-check-label" for="rcCol' + h.index + '">' + h.label + '</label></div></div>');
        $chk.find('input').on('change', function () {
            var vis = this.checked;
            hidden[h.index] = !vis;
            try { localStorage.setItem('coeResultCalc:hiddenCols:v1', JSON.stringify(hidden)); } catch (e) {}
            $('#rcCompTable thead th').eq(h.index).toggle(vis);
            $('#rcCompTable tbody tr').each(function () { $(this).find('td').eq(h.index).toggle(vis); });
        });
        $('#rcColVisGrid').append($chk);
    });
    @endif
});
</script>
@endpush
