@extends('admin.layouts.master')

@php
    $isPending = $status === 'pending';
    $title = $isPending ? 'Pending Participants List' : 'Evaluated Participants List';
    $num = fn ($v) => $v === 'AB' ? 'AB' : \App\Services\COE\ResultMarksService::number($v);
    $grouped = $subjectPk === null;
    // Column index of Action, for the grid script: S.No, Name, Code, components…, [subject total], Action.
    $componentCount = array_sum(array_map(fn ($s) => count($s['columns']), $columns));
    $actionCol = 3 + $componentCount + ($grouped ? 0 : 1);
    $canNotify = $isPending && $scope === 'active';
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
            @include('admin.coe.partials.download_menu', ['prefix' => 'rpp'])
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
                    <input type="hidden" name="scope" value="{{ $scope }}">
                    <span class="programme-dt-filters-label">Filter</span>
                    <div class="programme-dt-filter-select">
                        <select name="course" class="form-select rpp-filter" aria-label="Course">
                            @foreach ($courses as $pk => $name)
                                <option value="{{ $pk }}" @selected($pk === $coursePk)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="programme-dt-filter-select">
                        <select name="subject" class="form-select rpp-filter" aria-label="Subject">
                            <option value="">All Subjects</option>
                            @foreach ($subjects as $pk => $s)
                                <option value="{{ $pk }}" @selected($pk === $subjectPk)>{{ $s['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <a href="{{ route('admin.coe.result-compilation.participants', ['course' => $coursePk, 'status' => $status, 'scope' => $scope]) }}"
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
                                    @foreach ($columns as $s)
                                        <th scope="colgroup" colspan="{{ count($s['columns']) }}">{{ $s['name'] }} ({{ $num($s['max']) }})</th>
                                    @endforeach
                                    @if ($canNotify)<th scope="col" rowspan="2">Action</th>@endif
                                </tr>
                            @endif
                            <tr>
                                @unless ($grouped)
                                    <th scope="col">S.No</th>
                                    <th scope="col">Name</th>
                                    <th scope="col">Code</th>
                                @endunless
                                @foreach ($columns as $s)
                                    @foreach ($s['columns'] as $col)
                                        {{-- The subject prefix is for exports, which only read this row. --}}
                                        <th scope="col">@if ($grouped)<span class="visually-hidden">{{ $s['name'] }} - </span>@endif{{ $col['label'] }} ({{ $num($col['max']) }})</th>
                                    @endforeach
                                @endforeach
                                @unless ($grouped)
                                    <th scope="col">{{ $columns[$subjectPk]['name'] }} ({{ $num($columns[$subjectPk]['max']) }})</th>
                                    @if ($canNotify)<th scope="col">Action</th>@endif
                                @endunless
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr>
                                    <td></td>
                                    <td class="coe-nowrap">{{ $row['name'] }}</td>
                                    <td>{{ $row['code'] }}</td>
                                    @foreach ($columns as $s)
                                        @foreach ($s['columns'] as $key => $col)
                                            <td>{{ $num($row['marks'][$key]) }}</td>
                                        @endforeach
                                    @endforeach
                                    @unless ($grouped)
                                        <td class="fw-semibold">{{ $num($row['subjects'][$subjectPk]) }}</td>
                                    @endunless
                                    @if ($canNotify)
                                        <td>
                                            <button type="button" class="coe-act coe-act--edit coe-act--wide rcp-notify"
                                                    data-student="{{ $row['student'] }}">
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

    var actionCol = @json($canNotify ? $actionCol : null);

    CoeGrid.init({
        table: '#rppTable',
        title: @json($title),
        snoCol: 0,
        order: [[2, 'asc']],
        noSort: actionCol === null ? [0] : [0, actionCol],
        exclude: actionCol === null ? [] : [actionCol],
        searchToggle: '#rppSearchToggle',
        searchSlot: '#rppDtSearch',
        download: '#rppDownloadExcel',
        pdf: '#rppDownloadPdf',
        print: '#rppPrintBtn',
        colvisGrid: '#rppColumnToggleGrid',
        colvisKey: 'coeResultParticipants:hiddenColumns:v2',
        empty: { title: 'No Participants', zeroTitle: 'No Participants Found',
                 text: @json($isPending ? 'Nobody is pending evaluation.' : 'Nobody has been fully evaluated yet.') }
    });

    $('.rpp-filter').on('change', function () { $('#rppFilterForm').submit(); });

    // Bell notification to the faculty who still owe this participant's marks.
    $('#rppTable').on('click', '.rcp-notify', function () {
        var $btn = $(this).prop('disabled', true);
        $.ajax({
            url: @json(route('admin.coe.result-compilation.notify')),
            method: 'POST',
            data: { course: @json($coursePk), subject: @json($subjectPk), student: $btn.data('student') },
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
        }).done(function (res) {
            Swal.fire({ icon: 'success', title: 'Notify for Pending', text: res.message });
        }).fail(function (xhr) {
            Swal.fire({ icon: 'error', title: 'Notify for Pending',
                text: (xhr.responseJSON && xhr.responseJSON.message) || 'Could not send the reminder.' });
        }).always(function () { $btn.prop('disabled', false); });
    });
});
</script>
@endpush
