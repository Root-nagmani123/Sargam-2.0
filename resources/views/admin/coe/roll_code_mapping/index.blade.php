{{--
    COE → Examination Management → Roll No ↔ Unique Code Mapping (RollCodeMappingController).
    Read-only and confidential: which Unique Participant Code belongs to which roll
    number. Rows come from App\Services\COE\RollCodeMapping (nothing is stored).
    Same chrome as Unique Participant Code: scope tabs, filters, Columns, search;
    Download / Print are the Sargam master export (server), following the filters.
--}}
@extends('admin.layouts.master')

@section('title', 'Roll No ↔ Unique Code Mapping')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}">
<link rel="stylesheet"
      href="{{ asset('css/coe-admin.css') }}?v={{ @filemtime(public_path('css/coe-admin.css')) ?: time() }}">
<style>
.rcm-summary      { display:flex;flex-wrap:wrap;gap:10px;margin-bottom:18px; }
.rcm-chip         { display:flex;flex-direction:column;min-width:150px;padding:10px 16px;border-radius:10px;border:1px solid var(--ds-line);
                    background:#fff;cursor:pointer;text-align:left; }
.rcm-chip strong  { font-size:22px;line-height:1.1; }
.rcm-chip span    { font-size:12px;color:var(--ds-ink-muted); }
.rcm-chip.is-active { box-shadow:0 0 0 2px var(--ds-primary); }
.rcm-chip--mapped strong { color:#15803d; }
.rcm-chip--code strong   { color:#b45309; }
.rcm-chip--roll strong   { color:#b91c1c; }
.rcm-state        { display:inline-block;border-radius:10px;padding:3px 10px;font-size:12px;font-weight:600;white-space:nowrap; }
.rcm-state--mapped { background:#dcfce7;color:#15803d; }
.rcm-state--code   { background:#fef3c7;color:#b45309; }
.rcm-state--roll   { background:#fee2e2;color:#b91c1c; }
.rcm-missing      { color:var(--ds-ink-muted);font-style:italic; }
.rcm-code         { font-weight:600;letter-spacing:.3px; }
tr.rcm-row--gap td { background:#fffbeb; }
</style>
@endpush

@section('setup_content')
@php
    $isArchived = $scope === 'archived';
    $stateClass = fn (string $s) => match ($s) {
        \App\Services\COE\RollCodeMapping::MAPPED => 'mapped',
        \App\Services\COE\RollCodeMapping::CODE_MISSING => 'code',
        default => 'roll',
    };
    $exportUrl = fn (string $format) => route('admin.coe.roll-code-mapping.export', ['format' => $format, 'scope' => $scope]);
@endphp
<div class="container-fluid coe-page">
    <x-breadcrum title="Roll No ↔ Unique Code Mapping" :showBack="false"
                 :items="['Setup', 'COE', 'Examination Management', 'Roll No ↔ Unique Code Mapping']" />

    <x-session_message />

    {{-- Scope tabs · Download / Print --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <nav class="coe-tabs" aria-label="Course scope">
            <a href="{{ route('admin.coe.roll-code-mapping.index') }}"
               class="coe-tab {{ $isArchived ? '' : 'is-active' }}"
               @unless ($isArchived) aria-current="page" @endunless>Active</a>
            <a href="{{ route('admin.coe.roll-code-mapping.index', ['scope' => 'archived']) }}"
               class="coe-tab {{ $isArchived ? 'is-active' : '' }}"
               @if ($isArchived) aria-current="page" @endif>Archived</a>
        </nav>

        <div class="d-flex flex-wrap gap-2 align-items-center coe-secondary-actions">
            <div class="dropdown">
                <button type="button" id="rcmDownloadToggle"
                        class="btn programme-dt-btn-columns border-0 text-primary dropdown-toggle"
                        data-bs-toggle="dropdown" aria-expanded="false" title="Download">
                    <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="rcmDownloadToggle">
                    <li><a class="dropdown-item rcm-export-link" href="{{ $exportUrl('csv') }}">
                        <i class="bi bi-filetype-csv me-1" aria-hidden="true"></i> CSV</a></li>
                    <li><a class="dropdown-item rcm-export-link" href="{{ $exportUrl('excel') }}">
                        <i class="bi bi-file-earmark-excel me-1" aria-hidden="true"></i> Excel (.xlsx)</a></li>
                    <li><a class="dropdown-item rcm-export-link" href="{{ $exportUrl('pdf') }}">
                        <i class="bi bi-file-earmark-pdf me-1" aria-hidden="true"></i> PDF</a></li>
                </ul>
            </div>
            <a href="{{ $exportUrl('print') }}" target="_blank" rel="noopener"
               class="btn programme-dt-btn-columns border-0 text-primary rcm-export-link" title="Print">
                <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
            </a>
        </div>
    </div>

    {{-- Summary of the rows the filters show; a chip filters by its mapping state. --}}
    <div class="rcm-summary" id="rcmSummary">
        <button type="button" class="rcm-chip" data-state="">
            <strong id="rcmCountTotal">{{ $summary['total'] }}</strong><span>Total OTs</span>
        </button>
        <button type="button" class="rcm-chip rcm-chip--mapped" data-state="{{ \App\Services\COE\RollCodeMapping::MAPPED }}">
            <strong id="rcmCountMapped">{{ $summary['mapped'] }}</strong><span>Mapped</span>
        </button>
        <button type="button" class="rcm-chip rcm-chip--code" data-state="{{ \App\Services\COE\RollCodeMapping::CODE_MISSING }}">
            <strong id="rcmCountCode">{{ $summary['code_missing'] }}</strong><span>Unique code missing</span>
        </button>
        <button type="button" class="rcm-chip rcm-chip--roll" data-state="{{ \App\Services\COE\RollCodeMapping::ROLL_MISSING }}">
            <strong id="rcmCountRoll">{{ $summary['roll_missing'] }}</strong><span>Roll number missing</span>
        </button>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar coe-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Filter</span>

                    <div class="programme-dt-filter-select">
                        <select id="rcmFilterCourse" class="form-select" data-placeholder="Course Name" aria-label="Course Name">
                            <option value="">Course Name</option>
                            @foreach ($courses as $course)
                                <option value="{{ $course }}">{{ $course }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="programme-dt-filter-select">
                        <select id="rcmFilterMapping" class="form-select" data-placeholder="Mapping" aria-label="Mapping">
                            <option value="">Mapping</option>
                            @foreach ($states as $state)
                                <option value="{{ $state }}">{{ $state }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="button" id="rcmResetFilters" class="btn programme-dt-btn-reset">Remove Filter</button>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns"
                            data-bs-toggle="modal" data-bs-target="#rcmColumnVisibilityModal"
                            title="Show / hide columns">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>

                    <div id="rcmDtSearch" class="programme-dt-search d-none" data-dt-search-for="rcmTable"></div>
                    <button type="button" id="rcmSearchToggle" class="coe-search-toggle"
                            aria-controls="rcmDtSearch" aria-expanded="false" title="Search">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <span class="visually-hidden">Search</span>
                    </button>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table id="rcmTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th scope="col">S. No.</th>
                                <th scope="col">Course Name</th>
                                <th scope="col" class="coe-col-center">Batch</th>
                                <th scope="col">Roll Number</th>
                                <th scope="col">Unique Code</th>
                                <th scope="col">OT Name</th>
                                <th scope="col">Service</th>
                                <th scope="col" class="coe-col-center">Roll No. Status</th>
                                <th scope="col" class="coe-col-center">Code Status</th>
                                <th scope="col" class="coe-col-center">Mapping</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr class="{{ $row['mapping'] === \App\Services\COE\RollCodeMapping::MAPPED ? '' : 'rcm-row--gap' }}">
                                    <td></td>
                                    <td class="coe-strong">{{ $row['course_name'] }}</td>
                                    <td class="coe-col-center">{{ $row['batch'] }}</td>
                                    <td data-order="{{ $row['serial'] }}">
                                        @if ($row['roll_number'] !== '') {{ $row['roll_number'] }} @else <span class="rcm-missing">Not generated</span> @endif
                                    </td>
                                    <td>
                                        @if ($row['unique_code'] !== '') <span class="rcm-code">{{ $row['unique_code'] }}</span> @else <span class="rcm-missing">Not generated</span> @endif
                                    </td>
                                    <td>{{ $row['ot_name'] }}</td>
                                    <td>{{ $row['service'] }}</td>
                                    <td class="coe-col-center">{{ $row['roll_status'] }}</td>
                                    <td class="coe-col-center">{{ $row['code_status'] }}</td>
                                    <td class="coe-col-center" data-search="{{ $row['mapping'] }}">
                                        <span class="rcm-state rcm-state--{{ $stateClass($row['mapping']) }}">{{ $row['mapping'] }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3"
                     data-dt-footer-for="rcmTable"></div>
            </div>

        </div>
    </div>
</div>

{{-- Column Visibility --}}
<div class="modal fade" id="rcmColumnVisibilityModal" tabindex="-1" aria-labelledby="rcmColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="rcmColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="rcmColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/coe-grid.js') }}?v={{ @filemtime(public_path('js/coe-grid.js')) ?: time() }}"></script>
<script>
$(function () {
    'use strict';

    /* Column indexes — keep in step with the <thead>. */
    var COL = { sno: 0, course: 1, batch: 2, roll: 3, code: 4, ot: 5, service: 6, rollStatus: 7, codeStatus: 8, mapping: 9 };
    /* Export column keys (RollCodeMappingController::export), by column index. */
    var EXPORT_KEYS = ['sno', 'course', 'batch', 'roll', 'code', 'ot', 'service', 'roll_status', 'code_status', 'mapping'];

    var dt = CoeGrid.init({
        table: '#rcmTable',
        title: 'Roll Number - Unique Code Mapping',
        snoCol: COL.sno,
        order: [],
        noSort: [COL.sno],
        filters: { '#rcmFilterCourse': COL.course, '#rcmFilterMapping': COL.mapping },
        reset: '#rcmResetFilters',
        searchToggle: '#rcmSearchToggle',
        searchSlot: '#rcmDtSearch',
        colvisGrid: '#rcmColumnToggleGrid',
        colvisKey: 'coeRollCodeMapping:hiddenColumns:v1',
        empty: {
            title: 'No Roll Numbers or Unique Codes',
            zeroTitle: 'No OTs Found',
            text: 'Generate roll numbers (Roll Numbers) and unique codes (Unique Participant Code) for a course to see its mapping here.'
        }
    });

    /* Summary chips count the rows the course filter and search leave (not the mapping filter). */
    function updateSummary() {
        var counts = { total: 0 };
        dt.rows(function (idx, data, node) {
            var courseOk = !$('#rcmFilterCourse').val() || $.trim($(node).children().eq(COL.course).text()) === $('#rcmFilterCourse').val();
            return courseOk;
        }, { search: 'none' }).every(function () {
            var state = $.trim($(this.node()).children().eq(COL.mapping).text());
            counts.total++;
            counts[state] = (counts[state] || 0) + 1;
        });
        $('#rcmCountTotal').text(counts.total);
        $('#rcmCountMapped').text(counts[@json(\App\Services\COE\RollCodeMapping::MAPPED)] || 0);
        $('#rcmCountCode').text(counts[@json(\App\Services\COE\RollCodeMapping::CODE_MISSING)] || 0);
        $('#rcmCountRoll').text(counts[@json(\App\Services\COE\RollCodeMapping::ROLL_MISSING)] || 0);
        var state = $('#rcmFilterMapping').val() || '';
        $('.rcm-chip').each(function () { $(this).toggleClass('is-active', $(this).data('state') === state); });
    }

    /* A chip sets the Mapping filter. */
    $('.rcm-chip').on('click', function () {
        $('#rcmFilterMapping').val($(this).data('state') || '').trigger('change');
    });

    /* Download / Print links carry the current course, mapping, search and visible columns. */
    function syncExportLinks() {
        var cols = [];
        dt.columns().every(function () { if (this.visible()) { cols.push(EXPORT_KEYS[this.index()]); } });
        $('.rcm-export-link').each(function () {
            var parts = this.href.split('?'), params = new URLSearchParams(parts[1] || '');
            ['course', 'mapping', 'q', 'cols'].forEach(function (k) { params.delete(k); });
            if ($('#rcmFilterCourse').val()) { params.set('course', $('#rcmFilterCourse').val()); }
            if ($('#rcmFilterMapping').val()) { params.set('mapping', $('#rcmFilterMapping').val()); }
            if (dt.search()) { params.set('q', dt.search()); }
            if (cols.length && cols.length < EXPORT_KEYS.length) { params.set('cols', cols.join(',')); }
            this.href = parts[0] + '?' + params.toString();
        });
    }

    dt.on('draw column-visibility.dt', function () { updateSummary(); syncExportLinks(); });
    updateSummary();
    syncExportLinks();
});
</script>
@endpush
