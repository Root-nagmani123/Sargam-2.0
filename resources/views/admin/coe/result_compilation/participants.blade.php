@extends('admin.layouts.master')

@php
    $isPending = $status === 'pending';
    $title = $isPending ? 'Pending Participants List' : 'Evaluated Participants List';
    $num = fn ($v) => $v === null ? '-' : rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');
    $grouped = $subject === null;
    // Column index of Action, for the grid script: S.No, Name, Code, components…, [subject total], Action.
    $componentCount = array_sum(array_map(fn ($s) => count($s['components']), $columns));
    $actionCol = 3 + $componentCount + ($grouped ? 0 : 1);
@endphp

@section('title', $title)

@push('styles')
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}">
<link rel="stylesheet"
      href="{{ asset('css/coe-admin.css') }}?v={{ @filemtime(public_path('css/coe-admin.css')) ?: time() }}">
<style>
.rcp-marks th { white-space: normal; min-width: 72px; vertical-align: bottom; }
.rcp-marks thead tr:first-child th[colspan] { text-align: center; border-bottom: 1px solid var(--ds-line, #e8e8e8); }
</style>
@endpush

@section('setup_content')
<div class="container-fluid coe-page">
    <x-breadcrum :title="$title" :showBack="true"
                 :items="['Setup', 'COE', 'Result Management', 'Result Compilation', 'Participants List']">
    </x-breadcrum>

    <div class="d-flex flex-wrap align-items-center justify-content-end gap-3 mb-3">
        <div class="d-flex flex-wrap gap-2 coe-secondary-actions">
            <button type="button" id="rppDownloadBtn" class="btn programme-dt-btn-columns" title="Download as Excel">
                <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
            </button>
            <button type="button" id="rppPrintBtn" class="btn programme-dt-btn-columns" title="Print">
                <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
            </button>
        </div>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar coe-toolbar">
                {{-- Course / Subject change the columns, so they reload the page. --}}
                <form method="GET" action="{{ route('admin.coe.result-compilation.participants') }}" id="rppFilterForm"
                      class="d-flex flex-wrap align-items-center gap-3">
                    <input type="hidden" name="status" value="{{ $status }}">
                    <span class="programme-dt-filters-label">Filter</span>
                    <div class="programme-dt-filter-select">
                        <select name="course" class="form-select rpp-filter" aria-label="Course">
                            @foreach ($courses as $key => $c)
                                <option value="{{ $key }}" @selected($key === $courseKey)>{{ $c['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="programme-dt-filter-select">
                        <select name="subject" class="form-select rpp-filter" aria-label="Subject">
                            <option value="">All Subjects</option>
                            @foreach ($subjects as $name => $s)
                                <option value="{{ $name }}" @selected($name === $subject)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <a href="{{ route('admin.coe.result-compilation.participants', ['course' => $courseKey, 'status' => $status]) }}"
                       class="btn programme-dt-btn-reset d-inline-flex align-items-center">Remove Filter</a>
                </form>
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns"
                            data-bs-toggle="modal" data-bs-target="#rppColumnVisibilityModal" title="Show / hide columns">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="rppDtSearch" class="programme-dt-search d-none" data-dt-search-for="rppTable"></div>
                    <button type="button" id="rppSearchToggle" class="coe-search-toggle"
                            aria-controls="rppDtSearch" aria-expanded="false" title="Search">
                        <i class="bi bi-search" aria-hidden="true"></i><span class="visually-hidden">Search</span>
                    </button>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table id="rppTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table rcp-marks">
                        <thead>
                            @if ($grouped)
                                <tr>
                                    <th scope="col" rowspan="2">S.No</th>
                                    <th scope="col" rowspan="2">Name</th>
                                    <th scope="col" rowspan="2">Code</th>
                                    @foreach ($columns as $name => $s)
                                        <th scope="colgroup" colspan="{{ count($s['components']) }}">{{ $name }} ({{ $num(array_sum($s['components'])) }})</th>
                                    @endforeach
                                    @if ($isPending)<th scope="col" rowspan="2">Action</th>@endif
                                </tr>
                            @endif
                            <tr>
                                @unless ($grouped)
                                    <th scope="col">S.No</th>
                                    <th scope="col">Name</th>
                                    <th scope="col">Code</th>
                                @endunless
                                @foreach ($columns as $s)
                                    @foreach ($s['components'] as $component => $max)
                                        <th scope="col">{{ $component }} ({{ $num($max) }})</th>
                                    @endforeach
                                @endforeach
                                @unless ($grouped)
                                    <th scope="col">{{ $subject }} ({{ $num(array_sum($columns[$subject]['components'])) }})</th>
                                    @if ($isPending)<th scope="col">Action</th>@endif
                                @endunless
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr>
                                    <td></td>
                                    <td class="coe-nowrap">{{ $row['name'] }}</td>
                                    <td>{{ $row['code'] }}</td>
                                    @foreach ($columns as $name => $s)
                                        @foreach ($s['components'] as $component => $max)
                                            <td>{{ $num($row['marks'][$name][$component]) }}</td>
                                        @endforeach
                                    @endforeach
                                    @unless ($grouped)
                                        <td class="fw-semibold">{{ $num($row['subjects'][$subject]) }}</td>
                                    @endunless
                                    @if ($isPending)
                                        <td>
                                            <button type="button" class="coe-act coe-act--edit coe-act--wide rcp-notify">
                                                <span class="coe-act__icon"><i class="bi bi-chat-square-dots" aria-hidden="true"></i></span>
                                                <span class="coe-act__label">Notify for Pending</span>
                                            </button>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3"
                     data-dt-footer-for="rppTable"></div>
            </div>
        </div>
    </div>
</div>

@include('admin.coe.partials.colvis_modal', ['id' => 'rppColumnVisibilityModal', 'grid' => 'rppColumnToggleGrid'])
@endsection

@push('scripts')
<script src="{{ asset('js/coe-grid.js') }}?v={{ @filemtime(public_path('js/coe-grid.js')) ?: time() }}"></script>
<script>
$(function () {
    'use strict';

    var actionCol = @json($isPending ? $actionCol : null);

    CoeGrid.init({
        table: '#rppTable',
        title: @json($title),
        snoCol: 0,
        order: [[2, 'asc']],
        noSort: actionCol === null ? [0] : [0, actionCol],
        exclude: actionCol === null ? [] : [actionCol],
        searchToggle: '#rppSearchToggle',
        searchSlot: '#rppDtSearch',
        download: '#rppDownloadBtn',
        print: '#rppPrintBtn',
        colvisGrid: '#rppColumnToggleGrid',
        colvisKey: 'coeResultParticipants:hiddenColumns:v1',
        empty: { title: 'No Participants', zeroTitle: 'No Participants Found',
                 text: @json($isPending ? 'Nobody is pending evaluation.' : 'Nobody has been fully evaluated yet.') }
    });

    $('.rpp-filter').on('change', function () { $('#rppFilterForm').submit(); });

    // shortcut: no faculty notification exists yet; wire it when Evaluation & Marks stores marks.
    $('#rppTable').on('click', '.rcp-notify', function () {
        Swal.fire({ icon: 'info', title: 'Notify for Pending',
            text: 'Faculty reminders will be sent from here once Evaluation & Marks is live. This screen shows sample results.' });
    });
});
</script>
@endpush
