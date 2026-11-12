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
    // No marks (not held / no such paper): '-'.
    $num = fn ($v) => $v === null ? '-' : number_format($v, 2);
    // Max marks as the Figma writes them: 90, 172.50, 622.50.
    $outMax = fn ($v) => $v === null ? '-' : (floor($v) == $v ? number_format($v, 0, '.', '') : number_format($v, 2, '.', ''));
    $out = $outMax;
    // Header max, or nothing when the courses listed (All) differ on it.
    $head = fn ($v) => $v === null ? '' : '<span class="mr-out">/ '.e($outMax($v)).'</span>';
    // A cell's own course max, shown only where the header has none.
    $own = fn ($value, $headerMax, $rowMax) => $value !== null && $headerMax === null && $rowMax !== null
        ? '<span class="mr-out">/ '.e($outMax($rowMax)).'</span>' : '';
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
                {{-- Ranks are per course, so the course is chosen on the server. All: every
                     course, each ranked within itself. --}}
                <form method="GET" action="{{ route('admin.coe.merit-ranking.index') }}" id="mrFilterForm"
                      class="d-flex flex-wrap align-items-center gap-3">
                    @if ($isArchived)<input type="hidden" name="scope" value="archived">@endif
                    <span class="programme-dt-filters-label">Filter</span>
                    <div class="programme-dt-filter-select">
                        <select name="course" id="mrFilterCourse" class="form-select" aria-label="Course">
                            <option value="" @selected($coursePk === null)>All</option>
                            @foreach ($courses as $pk => $name)
                                <option value="{{ $pk }}" @selected($pk === $coursePk)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <a href="{{ route('admin.coe.merit-ranking.index', $isArchived ? ['scope' => 'archived'] : []) }}"
                       id="mrResetFilters" class="btn programme-dt-btn-reset">Remove Filter</a>
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

            @if ($pending > 0)
                <p class="coe-muted small mb-3">
                    {{ $pending }} OT(s) on this course are not ranked yet: some of their marks are still to be approved.
                    They show here once every subject held so far is evaluated.
                </p>
            @endif
            @if ($noPassMarks)
                <p class="coe-muted small mb-3">
                    Pass / Fail is not decided for {{ implode(', ', $noPassMarks) }}: set its passing marks in Service Passing Marks.
                </p>
            @endif

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
                                @foreach ($subjects as $s)
                                    <th scope="col">{{ $s['name'] }}{!! $head($s['max']) !!}</th>
                                @endforeach
                                <th scope="col">Total (A){!! $head($maxA) !!}</th>
                                <th scope="col">Director Assessment{!! $head($maxB) !!}</th>
                                <th scope="col">Grand Total (A+B){!! $head($max) !!}</th>
                                @foreach ($qualifying as $paper => $paperMax)
                                    <th scope="col">{{ $paper }}{!! $head($paperMax) !!}</th>
                                @endforeach
                                <th scope="col">Pass</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr>
                                    <td data-order="{{ $row['rank'] }}">{{ $row['rank'] }}</td>
                                    <td class="coe-nowrap">{{ $row['roll'] ?: '-' }}</td>
                                    <td>{{ $row['code'] ?: '-' }}</td>
                                    <td class="coe-nowrap">{{ $row['name'] ?: '-' }}</td>
                                    <td>{{ $row['service'] ?: '-' }}</td>
                                    @foreach ($subjects as $spk => $s)
                                        <td>{{ $num($row['subjects'][$spk]) }}{!! $own($row['subjects'][$spk], $s['max'], $row['max']['subjects'][$spk] ?? null) !!}</td>
                                    @endforeach
                                    <td>{{ $num($row['total_a']) }}{!! $own($row['total_a'], $maxA, $row['max']['a']) !!}</td>
                                    <td>{{ $num($row['director']) }}{!! $own($row['director'], $maxB, $row['max']['b']) !!}</td>
                                    <td data-order="{{ $row['grand'] }}">
                                        {{-- Always the row's own course max: under All courses differ. --}}
                                        <strong>{{ $num($row['grand']) }}</strong><span class="mr-out">/ {{ $num($row['max']['max']) }}</span>
                                    </td>
                                    @foreach ($qualifying as $paper => $paperMax)
                                        <td>{{ $num($row['qualifying'][$paper]) }}{!! $own($row['qualifying'][$paper], $paperMax, $row['max']['qualifying'][$paper]) !!}</td>
                                    @endforeach
                                    <td data-search="{{ $row['result'] ?? '' }}">
                                        @if ($row['result'] === null)
                                            <span title="No passing marks set for this service">-</span>
                                        @else
                                            <span class="coe-state coe-state--{{ $row['result'] === 'Pass' ? 'active' : 'inactive' }}">{{ strtoupper($row['result']) }}</span>
                                        @endif
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

    CoeGrid.init({
        table: '#mrTable',
        title: @json('Merit & Ranking' . ($coursePk ? ' - ' . $courses[$coursePk] : '')),
        snoCol: false,
        // All: course by course, each in rank order, as the server sends them.
        order: @json($coursePk ? [[0, 'asc']] : []),
        download: '#mrDownloadBtn',
        print: '#mrPrintBtn',
        searchToggle: '#mrSearchToggle',
        searchSlot: '#mrDtSearch',
        colvisGrid: '#mrColumnToggleGrid',
        colvisKey: 'coeMeritRanking:hiddenColumns:v4',
        empty: { title: 'No Ranked Officers', zeroTitle: 'No Officers Found', text: 'Nobody on this course has all their marks approved yet. Courses show here once an exam on an Active drive is over.' }
    });

    $('#mrFilterCourse').on('change', function () { $('#mrFilterForm').submit(); });
});
</script>
@endpush
