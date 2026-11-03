@extends('admin.layouts.master')

@section('title', 'Merit & Ranking')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}">
<link rel="stylesheet"
      href="{{ asset('css/coe-admin.css') }}?v={{ @filemtime(public_path('css/coe-admin.css')) ?: time() }}">
<style>
.mr-table th { white-space: normal; min-width: 64px; vertical-align: bottom; }
.mr-out { display: block; font-size: 11px; color: var(--ds-ink-muted, #7c7c7c); font-weight: 400; }
</style>
@endpush

@section('setup_content')
@php
    $isArchived = $scope === 'archived';
    $num = fn ($v) => number_format($v, 2);
    $out = fn ($v) => rtrim(rtrim(number_format($v, 2), '0'), '.');
    $grandMax = $writtenMax + $assessmentMax;
@endphp
<div class="container-fluid coe-page">
    <x-breadcrum title="Merit & Ranking" :showBack="false"
                 :items="['Setup', 'COE', 'Result Management', 'Merit & Ranking']">
    </x-breadcrum>

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <nav class="coe-tabs" aria-label="Course scope">
            <a href="{{ route('admin.coe.merit-ranking.index') }}"
               class="coe-tab {{ $isArchived ? '' : 'is-active' }}" @unless ($isArchived) aria-current="page" @endunless>Active</a>
            <a href="{{ route('admin.coe.merit-ranking.index', ['scope' => 'archived']) }}"
               class="coe-tab {{ $isArchived ? 'is-active' : '' }}" @if ($isArchived) aria-current="page" @endif>Archived</a>
        </nav>
        <div class="d-flex flex-wrap gap-2 coe-secondary-actions">
            <button type="button" id="mrDownloadBtn" class="btn programme-dt-btn-columns" title="Download as Excel">
                <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
            </button>
            <button type="button" id="mrPrintBtn" class="btn programme-dt-btn-columns" title="Print">
                <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
            </button>
        </div>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar coe-toolbar">
                {{-- Ranks are per course, so the course is chosen on the server. --}}
                <form method="GET" action="{{ route('admin.coe.merit-ranking.index') }}" id="mrFilterForm"
                      class="d-flex flex-wrap align-items-center gap-3">
                    @if ($isArchived)<input type="hidden" name="scope" value="archived">@endif
                    <span class="programme-dt-filters-label">Filter</span>
                    <div class="programme-dt-filter-select">
                        <select name="course" id="mrFilterCourse" class="form-select" aria-label="Course">
                            @foreach ($courses as $key => $c)
                                <option value="{{ $key }}" @selected($key === $courseKey)>{{ $c['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="programme-dt-filter-select">
                        <select id="mrFilterResult" class="form-select" data-placeholder="Result" aria-label="Result">
                            <option value="">Result</option>
                            <option value="Pass">Pass</option>
                            <option value="Fail">Fail</option>
                        </select>
                    </div>
                    <button type="button" id="mrResetFilters" class="btn programme-dt-btn-reset">Remove Filter</button>
                </form>
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns"
                            data-bs-toggle="modal" data-bs-target="#mrColumnVisibilityModal" title="Show / hide columns">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="mrDtSearch" class="programme-dt-search d-none" data-dt-search-for="mrTable"></div>
                    <button type="button" id="mrSearchToggle" class="coe-search-toggle"
                            aria-controls="mrDtSearch" aria-expanded="false" title="Search">
                        <i class="bi bi-search" aria-hidden="true"></i><span class="visually-hidden">Search</span>
                    </button>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table id="mrTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table coe-table--no-sno mr-table">
                        <thead>
                            <tr>
                                <th scope="col">Rank</th>
                                <th scope="col">Roll No.</th>
                                <th scope="col">OT Code</th>
                                <th scope="col">Officer Trainee</th>
                                <th scope="col">Service</th>
                                @foreach ($subjects as $name => $max)
                                    <th scope="col">{{ $name }} / {{ $out($max) }}</th>
                                @endforeach
                                <th scope="col">Total (A) / {{ $out($writtenMax) }}</th>
                                <th scope="col">Director Assessment (B) / {{ $out($assessmentMax) }}</th>
                                <th scope="col">Grand Total (A+B) / {{ $out($grandMax) }}</th>
                                <th scope="col">ICT / 50</th>
                                <th scope="col">Hindi / 50</th>
                                <th scope="col">Result</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr>
                                    <td data-order="{{ $row['rank'] }}">{{ $row['rank'] }}</td>
                                    <td class="coe-nowrap">{{ $row['roll'] }}</td>
                                    <td>{{ $row['code'] }}</td>
                                    <td class="coe-nowrap">{{ $row['name'] }}</td>
                                    <td>{{ $row['service'] }}</td>
                                    @foreach ($subjects as $name => $max)
                                        <td>{{ $num($row['subjects'][$name]) }}</td>
                                    @endforeach
                                    <td>{{ $num($row['written']) }}</td>
                                    <td>{{ $num($row['assessment']) }}</td>
                                    <td data-order="{{ $row['grand'] }}">
                                        <strong>{{ $num($row['grand']) }}</strong><span class="mr-out">/ {{ $num($grandMax) }}</span>
                                    </td>
                                    <td>{{ $num($row['ict']) }}</td>
                                    {{-- Hindi is only set for officers whose mother tongue is not Hindi. --}}
                                    <td>NA</td>
                                    <td data-search="{{ $row['result'] }}">
                                        <span class="coe-state coe-state--{{ $row['result'] === 'Pass' ? 'active' : 'inactive' }}">{{ strtoupper($row['result']) }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3"
                     data-dt-footer-for="mrTable"></div>
            </div>
        </div>
    </div>
</div>

@include('admin.coe.partials.colvis_modal', ['id' => 'mrColumnVisibilityModal', 'grid' => 'mrColumnToggleGrid'])
@endsection

@push('scripts')
<script src="{{ asset('js/coe-grid.js') }}?v={{ @filemtime(public_path('js/coe-grid.js')) ?: time() }}"></script>
<script>
$(function () {
    'use strict';

    var resultCol = $('#mrTable thead th').length - 1;

    CoeGrid.init({
        table: '#mrTable',
        title: @json('Merit & Ranking - '.$courses[$courseKey]['name']),
        snoCol: false,
        order: [[0, 'asc']],
        filters: { '#mrFilterResult': resultCol },
        reset: '#mrResetFilters',
        download: '#mrDownloadBtn',
        print: '#mrPrintBtn',
        searchToggle: '#mrSearchToggle',
        searchSlot: '#mrDtSearch',
        colvisGrid: '#mrColumnToggleGrid',
        colvisKey: 'coeMeritRanking:hiddenColumns:v1',
        empty: { title: 'No Ranked Officers', zeroTitle: 'No Officers Found', text: 'Nobody on this course has been fully evaluated yet.' }
    });

    $('#mrFilterCourse').on('change', function () { $('#mrFilterForm').submit(); });
});
</script>
@endpush
