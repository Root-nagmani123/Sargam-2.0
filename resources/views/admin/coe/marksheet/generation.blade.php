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
                            <option value="Generated">Generated</option>
                            <option value="Published">Published</option>
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
                                @php $show = ['course' => $row['course_key'], 'ot' => $row['code']]; @endphp
                                <tr data-course="{{ $row['course_key'] }}" data-code="{{ $row['code'] }}">
                                    <td><input type="checkbox" class="form-check-input mg-row-check" aria-label="Select {{ $row['name'] }}"></td>
                                    <td></td>
                                    <td class="coe-nowrap">{{ $row['name'] }}<div class="coe-muted small">Result locked</div></td>
                                    <td class="coe-nowrap">{{ $row['roll'] }}</td>
                                    <td class="coe-col-wrap">{{ $row['course'] }}</td>
                                    <td class="coe-col-wrap">{{ $row['batch'] }}</td>
                                    <td>{{ $row['exam_term'] }}</td>
                                    <td class="coe-col-wrap">{{ $row['exam_type'] }}</td>
                                    <td>{{ $row['result'] }}</td>
                                    <td class="coe-nowrap">{{ $row['number'] }}</td>
                                    <td class="coe-nowrap">{{ $row['generated_on'] }}</td>
                                    <td>
                                        <span class="coe-state coe-state--{{ $row['published'] ? 'published' : 'draft' }}">{{ $row['published'] ? 'Published' : 'Generated' }}</span>
                                    </td>
                                    <td>
                                        <div class="coe-act-group" role="group" aria-label="Row actions">
                                            <a href="{{ route('admin.coe.marksheet.show', $show) }}" class="coe-act coe-act--edit">
                                                <span class="coe-act__icon"><i class="bi bi-eye" aria-hidden="true"></i></span>
                                                <span class="coe-act__label">View</span>
                                            </a>
                                            @unless ($row['published'])
                                                <a href="{{ route('admin.coe.marksheet-generation.verify', ['course' => $row['course_key'], 'ots' => [$row['code']]]) }}"
                                                   class="coe-act coe-act--approve mg-verify">
                                                    <span class="coe-act__icon"><i class="bi bi-check2" aria-hidden="true"></i></span>
                                                    <span class="coe-act__label">Verify</span>
                                                </a>
                                                <a href="{{ route('admin.coe.marksheet-generation.verify', ['course' => $row['course_key'], 'first' => $row['code']]) }}" class="coe-act coe-act--edit coe-act--wide">
                                                    <span class="coe-act__icon"><i class="bi bi-eye-fill" aria-hidden="true"></i></span>
                                                    <span class="coe-act__label">View &amp; Verify</span>
                                                </a>
                                            @endunless
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

    /* ── Verify on a row: publish that marksheet here, after a confirm; the server checks the signatures ── */
    $('#mgTable').on('click', '.mg-verify', function (e) {
        e.preventDefault();
        var $tr = $(this).closest('tr');
        Swal.fire({
            icon: 'question', title: 'Verify & Publish', text: 'Publish this marksheet? It cannot be changed afterwards.',
            showCancelButton: true, confirmButtonText: 'Verify & Publish'
        }).then(function (r) {
            if (!r.isConfirmed) { return; }
            $.ajax({
                url: @json(route('admin.coe.marksheet-generation.publish')), method: 'POST', contentType: 'application/json',
                data: JSON.stringify({ course: String($tr.attr('data-course')), ots: [String($tr.attr('data-code'))] }),
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
            }).done(function (res) {
                Swal.fire({ icon: 'success', title: 'Published', text: res.message }).then(function () { window.location.reload(); });
            }).fail(function (xhr) {
                Swal.fire({ icon: 'error', title: 'Not published', text: (xhr.responseJSON && xhr.responseJSON.message) || 'Something went wrong. Please try again.' });
            });
        });
    });

    $('#mgVerifySelected').on('click', function () {
        var $rows = ticked();
        var courses = $.unique($rows.map(function () { return $(this).attr('data-course'); }).get());
        if (courses.length !== 1) {
            Swal.fire({ icon: 'warning', title: 'One course at a time', text: 'Select marksheets of a single course to verify together.' });
            return;
        }
        var q = $.param({ course: courses[0], ots: $rows.map(function () { return $(this).attr('data-code'); }).get() });
        window.location.href = VERIFY_URL + '?' + q;
    });
});
</script>
@endpush
