@extends('admin.layouts.master')

@section('title', 'Marksheet Generation')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}">
<link rel="stylesheet"
      href="{{ asset('css/coe-admin.css') }}?v={{ @filemtime(public_path('css/coe-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
@php $isArchived = $scope === 'archived'; @endphp
<div class="container-fluid coe-page">
    <x-breadcrum title="Marksheet Generation" :showBack="false"
                 :items="['Setup', 'COE', 'Document Management', 'Marksheet Generation']">
        <button type="button" class="btn coe-btn-header d-none" id="mgVerifySelected">
            <i class="bi bi-check2-all" aria-hidden="true"></i><span>Verify Selected</span>
        </button>
    </x-breadcrum>

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <nav class="coe-tabs" aria-label="Course scope">
            <a href="{{ route('admin.coe.marksheet-generation.index') }}"
               class="coe-tab {{ $isArchived ? '' : 'is-active' }}" @unless ($isArchived) aria-current="page" @endunless>Active</a>
            <a href="{{ route('admin.coe.marksheet-generation.index', ['scope' => 'archived']) }}"
               class="coe-tab {{ $isArchived ? 'is-active' : '' }}" @if ($isArchived) aria-current="page" @endif>Archived</a>
            @if (\App\Services\COE\ReexamService::enabled())
                <a href="{{ route('admin.coe.reexam-marksheet.index') }}" class="coe-tab">Re-exam</a>
            @endif
        </nav>
        <div class="d-flex flex-wrap gap-2 coe-secondary-actions">
            <button type="button" id="mgDownloadBtn" class="btn programme-dt-btn-columns" title="Download as Excel">
                <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
            </button>
            <button type="button" id="mgPrintBtn" class="btn programme-dt-btn-columns" title="Print">
                <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
            </button>
        </div>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar coe-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Filter</span>
                    <div class="programme-dt-filter-select">
                        <select id="mgFilterCourse" class="form-select" data-placeholder="Course" aria-label="Course">
                            <option value="">Course</option>
                            @foreach ($courses as $c)
                                <option value="{{ $c['name'] }}">{{ $c['short'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="programme-dt-filter-select">
                        <select id="mgFilterResult" class="form-select" data-placeholder="Result Status" aria-label="Result Status">
                            <option value="">Result Status</option>
                            <option value="Pass">Pass</option>
                            <option value="Fail">Fail</option>
                        </select>
                    </div>
                    <div class="programme-dt-filter-select">
                        <select id="mgFilterStatus" class="form-select" data-placeholder="Marksheet Status" aria-label="Marksheet Status">
                            <option value="">Marksheet Status</option>
                            @foreach (\App\Services\COE\MarksheetService::STATUSES as $status)
                                <option value="{{ $status }}">{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="programme-dt-filter-select">
                        <select id="mgFilterType" class="form-select" data-placeholder="Examination Type" aria-label="Examination Type">
                            <option value="">Examination Type</option>
                            @foreach (collect($rows)->pluck('exam_type')->unique()->sort() as $type)
                                <option value="{{ $type }}">{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="button" id="mgResetFilters" class="btn programme-dt-btn-reset">Remove Filter</button>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns"
                            data-bs-toggle="modal" data-bs-target="#mgColumnVisibilityModal" title="Show / hide columns">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="mgDtSearch" class="programme-dt-search d-none" data-dt-search-for="mgTable"></div>
                    <button type="button" id="mgSearchToggle" class="coe-search-toggle"
                            aria-controls="mgDtSearch" aria-expanded="false" title="Search">
                        <i class="bi bi-search" aria-hidden="true"></i><span class="visually-hidden">Search</span>
                    </button>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table id="mgTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th scope="col"><input type="checkbox" class="form-check-input" id="mgCheckAll" aria-label="Select all"></th>
                                <th scope="col">S. No.</th>
                                <th scope="col">Participant</th>
                                <th scope="col">Roll Number</th>
                                <th scope="col">Course</th>
                                <th scope="col">Batch / Phase</th>
                                <th scope="col">Examination Term</th>
                                <th scope="col">Examination Type</th>
                                <th scope="col">Result</th>
                                <th scope="col">Marksheet Number</th>
                                <th scope="col">Generated On</th>
                                <th scope="col">Status</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                @php
                                    // A re-exam marksheet (Pass / Fail only) lists here too, tagged "(Re-exam)";
                                    // it opens and verifies through ReexamMarksheetController.
                                    $rx = $row['reexam_key'];
                                    $show = $rx ? ['reexam' => $rx, 'ot' => $row['code']] : ['sheet' => $row['sheet_key'], 'ot' => $row['code']];
                                    $showRoute = $rx ? 'admin.coe.reexam-marksheet.show' : 'admin.coe.marksheet.show';
                                @endphp
                                <tr data-course="{{ $row['course_key'] }}" data-sheet="{{ $row['sheet_key'] ?? '' }}" data-code="{{ $row['code'] }}" data-reexam="{{ $rx }}">
                                    <td><input type="checkbox" class="form-check-input mg-row-check" aria-label="Select {{ $row['name'] }}"></td>
                                    <td></td>
                                    <td class="coe-nowrap">{{ $row['name'] }}<div class="coe-muted small">Result locked</div>@if ($rx)<div class="coe-reexam-tag">(Re-exam)</div>@endif</td>
                                    <td class="coe-nowrap">{{ $row['roll'] }}</td>
                                    {{-- One marksheet per examination drive: name the drive under its course. --}}
                                    <td class="coe-col-wrap">{{ $row['course'] }}@if (($row['examination'] ?? '-') !== '-')<div class="coe-muted small">{{ $row['examination'] }}</div>@endif</td>
                                    <td class="coe-col-wrap">{{ $row['batch'] }}</td>
                                    <td>{{ $row['exam_term'] }}</td>
                                    <td class="coe-col-wrap">{{ $row['exam_type'] }}</td>
                                    <td>{{ $row['result'] }}</td>
                                    <td class="coe-nowrap">{{ $row['number'] }}</td>
                                    <td class="coe-nowrap">{{ $row['generated_on'] }}</td>
                                    <td>
                                        <span class="coe-state coe-state--{{ $row['state'] }}">{{ $row['status'] }}</span>
                                    </td>
                                    <td>
                                        <div class="coe-act-group" role="group" aria-label="Row actions">
                                            <a href="{{ route($showRoute, $show) }}" class="coe-act coe-act--edit">
                                                <span class="coe-act__icon"><i class="bi bi-eye" aria-hidden="true"></i></span>
                                                <span class="coe-act__label">View</span>
                                            </a>
                                            @if ($row['can_verify'])
                                                <a href="{{ $rx ? '#' : route('admin.coe.marksheet-generation.verify', ['sheet' => $row['sheet_key'], 'ots' => [$row['code']]]) }}"
                                                   class="coe-act coe-act--approve mg-verify">
                                                    <span class="coe-act__icon"><i class="bi bi-check2" aria-hidden="true"></i></span>
                                                    <span class="coe-act__label">Verify</span>
                                                </a>
                                                <a href="{{ $rx ? route($showRoute, $show + ['verify' => 1]) : route('admin.coe.marksheet-generation.verify', ['sheet' => $row['sheet_key'], 'first' => $row['code']]) }}" class="coe-act coe-act--edit coe-act--wide">
                                                    <span class="coe-act__icon"><i class="bi bi-eye-fill" aria-hidden="true"></i></span>
                                                    <span class="coe-act__label">View &amp; Verify</span>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3"
                     data-dt-footer-for="mgTable"></div>
            </div>
        </div>
    </div>
</div>

@include('admin.coe.partials.colvis_modal', ['id' => 'mgColumnVisibilityModal', 'grid' => 'mgColumnToggleGrid'])
@endsection

@push('scripts')
<script src="{{ asset('js/coe-grid.js') }}?v={{ @filemtime(public_path('js/coe-grid.js')) ?: time() }}"></script>
<script>
$(function () {
    'use strict';

    var COL = { check: 0, sno: 1, roll: 3, course: 4, type: 7, result: 8, status: 11, action: 12 };
    var VERIFY_URL = @json(route('admin.coe.marksheet-generation.verify'));

    var dt = CoeGrid.init({
        table: '#mgTable',
        title: @json($isArchived ? 'Marksheet Generation - Archived' : 'Marksheet Generation'),
        snoCol: COL.sno,
        order: [[COL.roll, 'asc']],
        noSort: [COL.check, COL.sno, COL.action],
        exclude: [COL.check, COL.action],
        rowCheck: '.mg-row-check',
        filters: { '#mgFilterCourse': COL.course, '#mgFilterResult': COL.result, '#mgFilterStatus': COL.status, '#mgFilterType': COL.type },
        reset: '#mgResetFilters',
        download: '#mgDownloadBtn',
        print: '#mgPrintBtn',
        searchToggle: '#mgSearchToggle',
        searchSlot: '#mgDtSearch',
        colvisGrid: '#mgColumnToggleGrid',
        colvisKey: 'coeMarksheetGeneration:hiddenColumns:v1',
        empty: { title: 'No Marksheets', zeroTitle: 'No Marksheets Found', text: 'Marksheets appear once a participant is fully evaluated.' }
    });

    /* ── Selection → Verify Selected ── */
    function ticked() { return dt.rows({ search: 'applied' }).nodes().to$().filter(function () { return $(this).find('.mg-row-check').prop('checked'); }); }
    function refresh() { $('#mgVerifySelected').toggleClass('d-none', ticked().length === 0); }

    $('#mgCheckAll').on('change', function () {
        dt.rows({ search: 'applied' }).nodes().to$().find('.mg-row-check').prop('checked', this.checked);
        refresh();
    });
    $('#mgTable').on('change', '.mg-row-check', refresh);

    /* ── Verify on a row: puts the logged-in UDC / AAO / COE signature on it here, after a confirm; the COE's publishes it ── */
    // Main: MarksheetController::publish ({course, ots}); re-exam: ReexamMarksheetController::publish ({reexam, ots}).
    function verify(reexam, sheet, ots) {
        Swal.fire({
            icon: 'question', title: 'Verify',
            text: (ots.length === 1 ? 'Verify this ' : 'Verify these ' + ots.length + ' ') + (reexam ? 're-exam ' : '')
                + (ots.length === 1 ? 'marksheet? Your signature will be added to it.' : 'marksheets? Your signature will be added to them.'),
            showCancelButton: true, confirmButtonText: 'Verify'
        }).then(function (r) {
            if (!r.isConfirmed) { return; }
            $.ajax({
                url: reexam ? @json(route('admin.coe.reexam-marksheet.publish')) : @json(route('admin.coe.marksheet-generation.publish')),
                method: 'POST', contentType: 'application/json',
                data: JSON.stringify(reexam ? { reexam: String(reexam), ots: ots } : { sheet: String(sheet), ots: ots }),
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
            }).done(function (res) {
                Swal.fire({ icon: 'success', title: 'Verified', text: res.message }).then(function () { window.location.reload(); });
            }).fail(function (xhr) {
                Swal.fire({ icon: 'error', title: 'Not verified', text: (xhr.responseJSON && xhr.responseJSON.message) || 'Something went wrong. Please try again.' });
            });
        });
    }

    $('#mgTable').on('click', '.mg-verify', function (e) {
        e.preventDefault();
        var $tr = $(this).closest('tr');
        verify($tr.attr('data-reexam') || '', $tr.attr('data-sheet'), [String($tr.attr('data-code'))]);
    });

    $('#mgVerifySelected').on('click', function () {
        var $rows = ticked();
        // Re-exam marksheets are verified on their own, one re-exam at a time (no Verify Document page for them).
        var reexams = $.unique($rows.map(function () { return $(this).attr('data-reexam') || ''; }).get());
        if (reexams.length > 1) {
            Swal.fire({ icon: 'warning', title: 'Select one kind', text: 'Verify re-exam marksheets separately from the main ones, one re-exam at a time.' });
            return;
        }
        if (reexams[0]) {
            verify(reexams[0], null, $rows.map(function () { return String($(this).attr('data-code')); }).get());
            return;
        }
        // Marksheets are per examination drive: verify one drive's at a time.
        var sheets = $.unique($rows.map(function () { return $(this).attr('data-sheet'); }).get());
        if (sheets.length !== 1) {
            Swal.fire({ icon: 'warning', title: 'One drive at a time', text: 'Select marksheets of a single examination drive to verify together.' });
            return;
        }
        var q = $.param({ sheet: sheets[0], ots: $rows.map(function () { return $(this).attr('data-code'); }).get() });
        window.location.href = VERIFY_URL + '?' + q;
    });
});
</script>
@endpush
