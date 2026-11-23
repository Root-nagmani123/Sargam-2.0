@extends('admin.layouts.master')

@php
    // One view for both pages' Re-exam tab: Marksheet Generation (all) and Verified Marksheet (published only).
    $page = $verifiedOnly ? 'Verified Marksheet' : 'Marksheet Generation';
    $indexRoute = $verifiedOnly ? 'admin.coe.verified-marksheet.index' : 'admin.coe.marksheet-generation.index';
@endphp

@section('title', $page.' - Re-exam')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}">
<link rel="stylesheet"
      href="{{ asset('css/coe-admin.css') }}?v={{ @filemtime(public_path('css/coe-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid coe-page">
    <x-breadcrum :title="$page" :showBack="false"
                 :items="['Setup', 'COE', 'Document Management', $page]">
        @unless ($verifiedOnly)
            <button type="button" class="btn coe-btn-header d-none" id="rmVerifySelected">
                <i class="bi bi-check2-all" aria-hidden="true"></i><span>Verify Selected</span>
            </button>
        @endunless
    </x-breadcrum>

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <nav class="coe-tabs" aria-label="Course scope">
            <a href="{{ route($indexRoute) }}" class="coe-tab">Active</a>
            <a href="{{ route($indexRoute, ['scope' => 'archived']) }}" class="coe-tab">Archived</a>
            <a href="{{ route($verifiedOnly ? 'admin.coe.reexam-marksheet.verified' : 'admin.coe.reexam-marksheet.index') }}"
               class="coe-tab is-active" aria-current="page">Re-exam</a>
        </nav>
        <div class="d-flex flex-wrap gap-2 coe-secondary-actions">
            <button type="button" id="rmDownloadBtn" class="btn programme-dt-btn-columns" title="Download as Excel">
                <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
            </button>
            <button type="button" id="rmPrintBtn" class="btn programme-dt-btn-columns" title="Print">
                <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
            </button>
        </div>
    </div>

    @unless ($enabled)
        <div class="alert alert-warning">Re-exam marksheets are listed but not numbered or signed yet: run the coe_reexam_marksheets migration.</div>
    @endunless

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">
            <p class="coe-muted mb-3">A re-exam marksheet shows only Pass / Fail for each re-exam subject, no marks. It appears once every
                re-exam subject of the OT has approved marks, and is signed UDC → AAO → COE like the main marksheet.</p>
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar coe-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Filter</span>
                    <div class="programme-dt-filter-select">
                        <select id="rmFilterCourse" class="form-select" data-placeholder="Course" aria-label="Course">
                            <option value="">Course</option>
                            @foreach ($courses as $c)
                                <option value="{{ $c['name'] }}">{{ $c['short'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="programme-dt-filter-select">
                        <select id="rmFilterResult" class="form-select" data-placeholder="Result Status" aria-label="Result Status">
                            <option value="">Result Status</option>
                            <option value="Pass">Pass</option>
                            <option value="Fail">Fail</option>
                        </select>
                    </div>
                    @unless ($verifiedOnly)
                        <div class="programme-dt-filter-select">
                            <select id="rmFilterStatus" class="form-select" data-placeholder="Marksheet Status" aria-label="Marksheet Status">
                                <option value="">Marksheet Status</option>
                                @foreach (\App\Services\COE\MarksheetService::STATUSES as $status)
                                    <option value="{{ $status }}">{{ $status }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endunless
                    <button type="button" id="rmResetFilters" class="btn programme-dt-btn-reset">Remove Filter</button>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns"
                            data-bs-toggle="modal" data-bs-target="#rmColumnVisibilityModal" title="Show / hide columns">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="rmDtSearch" class="programme-dt-search d-none" data-dt-search-for="rmTable"></div>
                    <button type="button" id="rmSearchToggle" class="coe-search-toggle"
                            aria-controls="rmDtSearch" aria-expanded="false" title="Search">
                        <i class="bi bi-search" aria-hidden="true"></i><span class="visually-hidden">Search</span>
                    </button>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table id="rmTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th scope="col"><input type="checkbox" class="form-check-input" id="rmCheckAll" aria-label="Select all"></th>
                                <th scope="col">S. No.</th>
                                <th scope="col">Participant</th>
                                <th scope="col">Roll Number</th>
                                <th scope="col">Course</th>
                                <th scope="col">Re-exam</th>
                                <th scope="col">Re-exam Subjects</th>
                                <th scope="col">Result</th>
                                <th scope="col">Marksheet Number</th>
                                <th scope="col">Generated On</th>
                                <th scope="col">Status</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                @php $show = ['reexam' => $row['reexam_key'], 'ot' => $row['code']]; @endphp
                                <tr data-reexam="{{ $row['reexam_key'] }}" data-code="{{ $row['code'] }}">
                                    <td><input type="checkbox" class="form-check-input rm-row-check" aria-label="Select {{ $row['name'] }}"></td>
                                    <td></td>
                                    <td class="coe-nowrap">{{ $row['name'] }}<div class="coe-muted small">{{ $row['service'] }}</div></td>
                                    <td class="coe-nowrap">{{ $row['roll'] }}</td>
                                    <td class="coe-col-wrap">{{ $row['course'] }}</td>
                                    <td class="coe-col-wrap">{{ $row['examination'] }}<div class="coe-muted small">of {{ $row['main_examination'] }}</div></td>
                                    <td class="coe-col-wrap">
                                        @foreach ($row['subjects'] as $s)
                                            <div>{{ $s['name'] }}: <strong>{{ $s['result'] }}</strong></div>
                                        @endforeach
                                    </td>
                                    <td>{{ $row['result'] }}</td>
                                    <td class="coe-nowrap">{{ $row['number'] }}</td>
                                    <td class="coe-nowrap">{{ $row['generated_on'] }}</td>
                                    <td><span class="coe-state coe-state--{{ $row['state'] }}">{{ $row['status'] }}</span></td>
                                    <td>
                                        <div class="coe-act-group" role="group" aria-label="Row actions">
                                            <a href="{{ route('admin.coe.reexam-marksheet.show', $show) }}" class="coe-act coe-act--edit">
                                                <span class="coe-act__icon"><i class="bi bi-eye" aria-hidden="true"></i></span>
                                                <span class="coe-act__label">View</span>
                                            </a>
                                            @if (! $verifiedOnly && $enabled && $row['can_verify'])
                                                <button type="button" class="coe-act coe-act--approve rm-verify">
                                                    <span class="coe-act__icon"><i class="bi bi-check2" aria-hidden="true"></i></span>
                                                    <span class="coe-act__label">Verify</span>
                                                </button>
                                                <a href="{{ route('admin.coe.reexam-marksheet.show', $show + ['verify' => 1]) }}" class="coe-act coe-act--edit coe-act--wide">
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
                     data-dt-footer-for="rmTable"></div>
            </div>
        </div>
    </div>
</div>

@include('admin.coe.partials.colvis_modal', ['id' => 'rmColumnVisibilityModal', 'grid' => 'rmColumnToggleGrid'])
@endsection

@push('scripts')
<script src="{{ asset('js/coe-grid.js') }}?v={{ @filemtime(public_path('js/coe-grid.js')) ?: time() }}"></script>
<script>
$(function () {
    'use strict';

    var COL = { check: 0, sno: 1, roll: 3, course: 4, result: 7, status: 10, action: 11 };
    var PUBLISH_URL = @json(route('admin.coe.reexam-marksheet.publish'));
    var filters = { '#rmFilterCourse': COL.course, '#rmFilterResult': COL.result };
    if ($('#rmFilterStatus').length) { filters['#rmFilterStatus'] = COL.status; }

    var dt = CoeGrid.init({
        table: '#rmTable',
        title: @json($page.' - Re-exam'),
        snoCol: COL.sno,
        order: [[COL.roll, 'asc']],
        noSort: [COL.check, COL.sno, COL.action],
        exclude: [COL.check, COL.action],
        rowCheck: '.rm-row-check',
        filters: filters,
        reset: '#rmResetFilters',
        download: '#rmDownloadBtn',
        print: '#rmPrintBtn',
        searchToggle: '#rmSearchToggle',
        searchSlot: '#rmDtSearch',
        colvisGrid: '#rmColumnToggleGrid',
        colvisKey: @json($verifiedOnly ? 'coeReexamVerifiedMarksheet:hiddenColumns:v1' : 'coeReexamMarksheet:hiddenColumns:v1'),
        empty: { title: 'No Re-exam Marksheets', zeroTitle: 'No Re-exam Marksheets Found',
                 text: 'A re-exam marksheet appears once every re-exam subject of the OT has approved marks.' }
    });

    function ticked() { return dt.rows({ search: 'applied' }).nodes().to$().filter(function () { return $(this).find('.rm-row-check').prop('checked'); }); }
    function refresh() { $('#rmVerifySelected').toggleClass('d-none', ticked().length === 0); }

    $('#rmCheckAll').on('change', function () {
        dt.rows({ search: 'applied' }).nodes().to$().find('.rm-row-check').prop('checked', this.checked);
        refresh();
    });
    $('#rmTable').on('change', '.rm-row-check', refresh);

    /* Verify puts the logged-in UDC / AAO / COE signature on the marksheet; the COE's publishes it (ReexamMarksheetService::verify()). */
    function verify(reexam, ots) {
        Swal.fire({
            icon: 'question', title: 'Verify',
            text: ots.length === 1 ? 'Verify this re-exam marksheet? Your signature will be added to it.'
                : 'Verify these ' + ots.length + ' re-exam marksheets? Your signature will be added to them.',
            showCancelButton: true, confirmButtonText: 'Verify'
        }).then(function (r) {
            if (!r.isConfirmed) { return; }
            $.ajax({
                url: PUBLISH_URL, method: 'POST', contentType: 'application/json',
                data: JSON.stringify({ reexam: String(reexam), ots: ots }),
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
            }).done(function (res) {
                Swal.fire({ icon: 'success', title: 'Verified', text: res.message }).then(function () { window.location.reload(); });
            }).fail(function (xhr) {
                Swal.fire({ icon: 'error', title: 'Not verified', text: (xhr.responseJSON && xhr.responseJSON.message) || 'Something went wrong. Please try again.' });
            });
        });
    }

    $('#rmTable').on('click', '.rm-verify', function () {
        var $tr = $(this).closest('tr');
        verify($tr.attr('data-reexam'), [String($tr.attr('data-code'))]);
    });

    $('#rmVerifySelected').on('click', function () {
        var $rows = ticked();
        var reexams = $.unique($rows.map(function () { return $(this).attr('data-reexam'); }).get());
        if (reexams.length !== 1) {
            Swal.fire({ icon: 'warning', title: 'One re-exam at a time', text: 'Select marksheets of a single re-exam to verify together.' });
            return;
        }
        verify(reexams[0], $rows.map(function () { return String($(this).attr('data-code')); }).get());
    });
});
</script>
@endpush
