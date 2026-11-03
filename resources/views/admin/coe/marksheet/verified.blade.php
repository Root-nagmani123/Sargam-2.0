@extends('admin.layouts.master')

@section('title', 'Verified Marksheet')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}">
<link rel="stylesheet"
      href="{{ asset('css/coe-admin.css') }}?v={{ @filemtime(public_path('css/coe-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
@php $isArchived = $scope === 'archived'; @endphp
<div class="container-fluid coe-page">
    <x-breadcrum title="Verified Marksheet" :showBack="false"
                 :items="['Setup', 'COE', 'Document Management', 'Verified Marksheet']">
    </x-breadcrum>

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <nav class="coe-tabs" aria-label="Course scope">
            <a href="{{ route('admin.coe.verified-marksheet.index') }}"
               class="coe-tab {{ $isArchived ? '' : 'is-active' }}" @unless ($isArchived) aria-current="page" @endunless>Active</a>
            <a href="{{ route('admin.coe.verified-marksheet.index', ['scope' => 'archived']) }}"
               class="coe-tab {{ $isArchived ? 'is-active' : '' }}" @if ($isArchived) aria-current="page" @endif>Archived</a>
        </nav>
        <div class="d-flex flex-wrap gap-2 coe-secondary-actions">
            <button type="button" id="vmDownloadBtn" class="btn programme-dt-btn-columns" title="Download as Excel">
                <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
            </button>
            <button type="button" id="vmPrintBtn" class="btn programme-dt-btn-columns" title="Print">
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
                        <select id="vmFilterCourse" class="form-select" data-placeholder="Course" aria-label="Course">
                            <option value="">Course</option>
                            @foreach ($courses as $c)
                                <option value="{{ $c['name'] }}">{{ $c['short'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="programme-dt-filter-select">
                        <select id="vmFilterResult" class="form-select" data-placeholder="Result Status" aria-label="Result Status">
                            <option value="">Result Status</option>
                            <option value="Pass">Pass</option>
                            <option value="Fail">Fail</option>
                        </select>
                    </div>
                    <div class="programme-dt-filter-select">
                        <select id="vmFilterType" class="form-select" data-placeholder="Exam Type" aria-label="Exam Type">
                            <option value="">Exam Type</option>
                            @foreach (collect($rows)->pluck('exam_type')->unique()->sort() as $type)
                                <option value="{{ $type }}">{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="button" id="vmResetFilters" class="btn programme-dt-btn-reset">Remove Filter</button>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns"
                            data-bs-toggle="modal" data-bs-target="#vmColumnVisibilityModal" title="Show / hide columns">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="vmDtSearch" class="programme-dt-search d-none" data-dt-search-for="vmTable"></div>
                    <button type="button" id="vmSearchToggle" class="coe-search-toggle"
                            aria-controls="vmDtSearch" aria-expanded="false" title="Search">
                        <i class="bi bi-search" aria-hidden="true"></i><span class="visually-hidden">Search</span>
                    </button>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table id="vmTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table coe-table--no-sno">
                        <thead>
                            <tr>
                                <th scope="col">Participant</th>
                                <th scope="col">Roll Number</th>
                                <th scope="col">Course</th>
                                <th scope="col">Examination</th>
                                <th scope="col">Marksheet Number</th>
                                <th scope="col">Issue Date</th>
                                <th scope="col">Document Status</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                @php $show = ['course' => $row['course_key'], 'ot' => $row['code']]; @endphp
                                <tr data-result="{{ $row['result'] }}" data-type="{{ $row['exam_type'] }}">
                                    <td class="coe-nowrap">{{ $row['name'] }}</td>
                                    <td class="coe-nowrap">{{ $row['roll'] }}</td>
                                    <td class="coe-col-wrap">{{ $row['course'] }}</td>
                                    <td class="coe-col-wrap">{{ $row['examination'] }}</td>
                                    <td class="coe-nowrap">{{ $row['number'] }}</td>
                                    <td class="coe-nowrap">{{ $row['generated_on'] }}</td>
                                    <td><span class="coe-state coe-state--draft">Published</span></td>
                                    <td>
                                        <div class="coe-act-group" role="group" aria-label="Row actions">
                                            <a href="{{ route('admin.coe.marksheet.show', $show) }}" class="coe-act coe-act--edit">
                                                <span class="coe-act__icon"><i class="bi bi-eye" aria-hidden="true"></i></span>
                                                <span class="coe-act__label">View</span>
                                            </a>
                                            <a href="{{ route('admin.coe.marksheet.show', $show + ['print' => 1]) }}" class="coe-act coe-act--edit" target="_blank" rel="noopener">
                                                <span class="coe-act__icon"><i class="bi bi-download" aria-hidden="true"></i></span>
                                                <span class="coe-act__label">Download</span>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3"
                     data-dt-footer-for="vmTable"></div>
            </div>
        </div>
    </div>
</div>

@include('admin.coe.partials.colvis_modal', ['id' => 'vmColumnVisibilityModal', 'grid' => 'vmColumnToggleGrid'])
@endsection

@push('scripts')
<script src="{{ asset('js/coe-grid.js') }}?v={{ @filemtime(public_path('js/coe-grid.js')) ?: time() }}"></script>
<script>
$(function () {
    'use strict';

    CoeGrid.init({
        table: '#vmTable',
        title: @json($isArchived ? 'Verified Marksheet - Archived' : 'Verified Marksheet'),
        snoCol: false,
        order: [[1, 'asc']],
        noSort: [7],
        exclude: [7],
        filters: { '#vmFilterCourse': 2 },
        rowFilters: { '#vmFilterResult': 'result', '#vmFilterType': 'type' },
        reset: '#vmResetFilters',
        download: '#vmDownloadBtn',
        print: '#vmPrintBtn',
        searchToggle: '#vmSearchToggle',
        searchSlot: '#vmDtSearch',
        colvisGrid: '#vmColumnToggleGrid',
        colvisKey: 'coeVerifiedMarksheet:hiddenColumns:v1',
        empty: { title: 'No Verified Marksheets', zeroTitle: 'No Marksheets Found', text: 'Marksheets appear here once they are verified and published.' }
    });
});
</script>
@endpush
