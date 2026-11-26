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
/* Re-exam filter: the re-exam mark, labelled, with the main mark under it */
.mr-rx { display: block; font-size: 11px; font-weight: 600; color: var(--coe-brand); }
/* Filter caption inside the top of each box ("Course" over "All"), as the Figma */
.coe-page .mr-filter { position: relative; }
.coe-page .mr-filter .mr-filter-cap { position: absolute; top: 7px; left: 13px; z-index: 1; margin: 0; pointer-events: none;
                                      font-size: 11px; line-height: 1; color: var(--ds-ink-muted, #667085); }
.coe-page .mr-filter .form-select { height: 52px; padding-top: 20px; padding-bottom: 4px; }
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
    // Re-exam filter (ResultMarksService::reexamMerit): a cell the re-exam changed shows the
    // re-exam value (or Pending), "Re-exam", and the main merit's value under it.
    $rx = fn ($value, $main) => '<strong>'.e($value === null ? 'Pending' : $num($value)).'</strong>'
        .'<span class="mr-rx">Re-exam</span><span class="mr-out">Main '.e($num($main)).'</span>';
    $isRx = (bool) $reexam;
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
                    <div class="programme-dt-filter-select mr-filter">
                        <label class="mr-filter-cap" for="mrFilterCourse">Course</label>
                        <select name="course" id="mrFilterCourse" class="form-select">
                            <option value="" @selected($coursePk === null)>All</option>
                            @foreach ($courses as $pk => $name)
                                <option value="{{ $pk }}" @selected($pk === $coursePk)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    {{-- Drive: that examination drive only (its subjects and marks); also narrows the Re-exam list. --}}
                    <div class="programme-dt-filter-select mr-filter">
                        <label class="mr-filter-cap" for="mrFilterDrive">Drive</label>
                        <select name="drive" id="mrFilterDrive" class="form-select">
                            <option value="" @selected($drivePk === null)>All</option>
                            {{-- Every main drive; under All courses the course is added (names repeat across courses). --}}
                            @foreach ($driveOptions as $pk => $d)
                                <option value="{{ $pk }}" @selected($pk === $drivePk)>{{ $d['name'] }}{{ $coursePk === null && isset($courses[$d['course']]) ? ' · ' . $courses[$d['course']] : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="programme-dt-filter-select mr-filter">
                        <label class="mr-filter-cap" for="mrFilterStatus">Status</label>
                        <select name="status" id="mrFilterStatus" class="form-select">
                            <option value="" @selected($status === null)>All</option>
                            @foreach ($statuses as $s)
                                <option value="{{ $s }}" @selected($s === $status)>{{ $s }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="programme-dt-filter-select mr-filter">
                        <label class="mr-filter-cap" for="mrFilterReexam">Re-exam</label>
                        <select name="reexam" id="mrFilterReexam" class="form-select">
                            <option value="" @selected($reexamPk === null)>All</option>
                            @foreach ($reexamOptions as $pk => $r)
                                <option value="{{ $pk }}" @selected($pk === $reexamPk)>{{ $r['label'] }}</option>
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

            @if ($isRx)
                <p class="coe-muted small mb-3">
                    Re-exam {{ $reexam['no'] }} of {{ $reexam['main'] }}: only its OTs. In each subject they re-sat, the re-exam mark
                    (counted once approved, no moderation) replaces the main mark in the totals and Pass / Fail; the main mark is shown under it.
                    Rank is the main merit rank and does not change.
                </p>
            @endif
            @if ($pending > 0)
                <p class="coe-muted small mb-3">
                    {{ $pending }} OT(s) on this course are not ranked yet: some of their marks are still to be approved or moderated.
                    They show here once every subject held so far is evaluated and moderated.
                </p>
            @endif
            <p class="coe-muted small mb-3">
                Pass: at least {{ rtrim(rtrim(number_format(\App\Services\COE\ResultMarksService::PASSING_PERCENT, 2), '0'), '.') }}% of the max marks in
                every component ({{ rtrim(rtrim(number_format(\App\Services\COE\ResultMarksService::BHUTAN_PASSING_PERCENT, 2), '0'), '.') }}% for
                {{ implode(', ', ['RBCS', 'RBFoS', 'RBPS']) }}). Absent counts as 0.
            </p>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table id="mrTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table coe-table--no-sno mr-table">
                        <thead>
                            <tr>
                                <th scope="col">Rank</th>
                                <th scope="col">Roll No.</th>
                                <th scope="col">OT Code</th>
                                <th scope="col">Officer Trainee</th>
                                <th scope="col">Examination Drive</th>
                                <th scope="col">Service</th>
                                @foreach ($subjects as $s)
                                    <th scope="col">{{ $s['name'] }}{!! $head($s['max']) !!}</th>
                                @endforeach
                                <th scope="col">Total (A){!! $head($maxA) !!}</th>
                                @if ($hasDirector ?? true)
                                    <th scope="col">Director Assessment{!! $head($maxB) !!}</th>
                                @endif
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
                                    <td>{{ ($row['drive'] ?? '') ?: '-' }}</td>
                                    <td>{{ $row['service'] ?: '-' }}</td>
                                    @foreach ($subjects as $spk => $s)
                                        @if ($isRx && array_key_exists($spk, $row['reexam']['subjects']))
                                            <td>{!! $rx($row['subjects'][$spk], $row['reexam']['subjects'][$spk]) !!}</td>
                                        @else
                                            <td>{{ $num($row['subjects'][$spk]) }}{!! $own($row['subjects'][$spk], $s['max'], $row['max']['subjects'][$spk] ?? null) !!}</td>
                                        @endif
                                    @endforeach
                                    @if ($isRx)
                                        <td>{!! $rx($row['total_a'], $row['reexam']['total_a']) !!}</td>
                                    @else
                                        <td>{{ $num($row['total_a']) }}{!! $own($row['total_a'], $maxA, $row['max']['a']) !!}</td>
                                    @endif
                                    @if ($hasDirector ?? true)
                                        <td>{{ $num($row['director']) }}{!! $own($row['director'], $maxB, $row['max']['b']) !!}</td>
                                    @endif
                                    <td data-order="{{ $row['grand'] ?? 0 }}">
                                        {{-- Always the row's own course max: under All courses differ. --}}
                                        @if ($isRx)
                                            {!! $rx($row['grand'], $row['reexam']['grand']) !!}<span class="mr-out">/ {{ $num($row['max']['max']) }}</span>
                                        @else
                                            <strong>{{ $num($row['grand']) }}</strong><span class="mr-out">/ {{ $num($row['max']['max']) }}</span>
                                        @endif
                                    </td>
                                    @foreach ($qualifying as $paper => $paperMax)
                                        @if ($isRx && array_key_exists($paper, $row['reexam']['qualifying']))
                                            <td>{!! $rx($row['qualifying'][$paper], $row['reexam']['qualifying'][$paper]) !!}</td>
                                        @else
                                            <td>{{ $num($row['qualifying'][$paper]) }}{!! $own($row['qualifying'][$paper], $paperMax, $row['max']['qualifying'][$paper]) !!}</td>
                                        @endif
                                    @endforeach
                                    <td data-search="{{ $row['result'] ?? '' }}">
                                        @if ($row['result'] === null)
                                            <span title="No passing marks set for this service">-</span>
                                        @else
                                            <span class="coe-state coe-state--{{ $row['result'] === 'Pass' ? 'active' : ($row['result'] === 'Pending' ? 'pending' : 'inactive') }}">{{ strtoupper($row['result']) }}</span>
                                            @if ($isRx)
                                                <span class="mr-rx">Re-exam</span><span class="mr-out">Main {{ strtoupper($row['reexam']['result'] ?? '-') }}</span>
                                            @endif
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
        title: @json('Merit & Ranking' . ($coursePk ? ' - ' . $courses[$coursePk] : '') . ($reexam ? ' - Re-exam ' . $reexam['no'] : '')),
        snoCol: false,
        // All: course by course, each in rank order, as the server sends them.
        order: @json($coursePk ? [[0, 'asc']] : []),
        download: '#mrDownloadBtn',
        print: '#mrPrintBtn',
        searchToggle: '#mrSearchToggle',
        searchSlot: '#mrDtSearch',
        colvisGrid: '#mrColumnToggleGrid',
        // v5: the Examination Drive column and per-drive subject columns changed the indexes.
        colvisKey: 'coeMeritRanking:hiddenColumns:v5',
        empty: { title: 'No Ranked Officers', zeroTitle: 'No Officers Found', text: 'Nobody on this course has all their marks approved and moderated yet. Courses show here once an exam on an Active drive is over.' }
    });

    // A filter clears the ones that depend on it (Course → Drive → Re-exam), then reloads:
    // a re-exam picked would otherwise pull its own course and drive back.
    $('#mrFilterCourse').on('change', function () { $('#mrFilterDrive, #mrFilterReexam').val(''); $('#mrFilterForm').submit(); });
    $('#mrFilterDrive').on('change', function () { $('#mrFilterReexam').val(''); $('#mrFilterForm').submit(); });
    $('#mrFilterStatus, #mrFilterReexam').on('change', function () { $('#mrFilterForm').submit(); });
});
</script>
@endpush
