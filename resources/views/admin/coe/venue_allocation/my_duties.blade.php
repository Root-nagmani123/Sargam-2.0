@extends('admin.layouts.master')

@section('title', 'My Invigilation Duty')

@push('styles')
<link rel="stylesheet"
      href="{{ asset('css/coe-admin.css') }}?v={{ @filemtime(public_path('css/coe-admin.css')) ?: time() }}">
<style>
/* My Invigilation Duty (VenueAllocationController::myDuties): the logged-in faculty / MTS
   member's rooms on frozen seating plans. */
.mid-counts        { display:grid;grid-template-columns:repeat(auto-fit, minmax(160px, 1fr));gap:12px;margin-bottom:16px; }
.mid-count         { border:1px solid var(--ds-line, #eaecf0);border-radius:10px;padding:12px 16px;background:#fff; }
.mid-count b       { display:block;font-size:22px;color:#004384;line-height:1.2; }
.mid-count span    { font-size:13px;color:#667085; }
.mid-when          { display:inline-block;border-radius:10px;padding:2px 10px;font-size:12px;font-weight:500;white-space:nowrap; }
.mid-when.is-today     { background:#fef3c7;color:#b54708; }
.mid-when.is-upcoming  { background:#eef4ff;color:#004384; }
.mid-when.is-completed { background:#f2f4f7;color:#667085; }
.mid-role          { font-weight:600;color:#101828; }
.mid-act           { display:inline-flex;flex-direction:column;align-items:center;gap:2px;font-size:11px;color:#004384;
                     text-decoration:none;padding:0 4px; }
.mid-act i         { font-size:16px; }
</style>
@endpush

@section('setup_content')
@php
    $upcoming = $duties->whereIn('when', ['today', 'upcoming']);
    $today = $duties->where('when', 'today')->count();
    $whenLabel = ['today' => 'Today', 'upcoming' => 'Upcoming', 'completed' => 'Completed'];
@endphp
<div class="container-fluid coe-page">
    <x-breadcrum title="My Invigilation Duty" :showBack="false"
                 :items="['Setup', 'COE', 'Examination Management', 'My Invigilation Duty']" />

    <div class="mid-counts">
        <div class="mid-count"><b>{{ $today }}</b><span>Today</span></div>
        <div class="mid-count"><b>{{ $upcoming->count() }}</b><span>Upcoming (incl. today)</span></div>
        <div class="mid-count"><b>{{ $duties->count() - $upcoming->count() }}</b><span>Completed</span></div>
        <div class="mid-count"><b>{{ $duties->count() }}</b><span>Total Duties</span></div>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar coe-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Filter</span>
                    <div class="programme-dt-filter-select">
                        <select id="midFilterWhen" class="form-select" aria-label="Status">
                            <option value="">Status</option>
                            @foreach ($whenLabel as $label)<option value="{{ $label }}">{{ $label }}</option>@endforeach
                        </select>
                    </div>
                    <button type="button" id="midResetFilters" class="btn programme-dt-btn-reset">Remove Filter</button>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" id="midDownloadBtn" class="btn programme-dt-btn-columns">
                        <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
                    </button>
                    <button type="button" id="midPrintBtn" class="btn programme-dt-btn-columns">
                        <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
                    </button>
                    <div id="midDtSearch" class="programme-dt-search d-none" data-dt-search-for="midTable"></div>
                    <button type="button" id="midSearchToggle" class="coe-search-toggle" aria-controls="midDtSearch" aria-expanded="false" title="Search">
                        <i class="bi bi-search" aria-hidden="true"></i><span class="visually-hidden">Search</span>
                    </button>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table id="midTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th>S.No</th>
                                <th>Exam Date</th>
                                <th class="coe-col-center">Status</th>
                                <th>My Role</th>
                                <th>Examination Drive</th>
                                <th>Course</th>
                                <th>Subject / Component</th>
                                <th>Exam Mode</th>
                                <th>Building</th>
                                <th>Floor</th>
                                <th>Room</th>
                                <th>OTs</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($duties as $d)
                                <tr>
                                    <td></td>
                                    <td class="text-nowrap">{{ $d['date'] }}</td>
                                    <td class="coe-col-center"><span class="mid-when is-{{ $d['when'] }}">{{ $whenLabel[$d['when']] }}</span></td>
                                    <td class="mid-role">{{ $d['role'] }}</td>
                                    <td>{{ $d['drive'] }}</td>
                                    <td>{{ $d['course'] }}</td>
                                    <td>{{ $d['paper'] }}</td>
                                    <td>{{ $d['mode'] }}</td>
                                    <td>{{ $d['building'] }}</td>
                                    <td>{{ $d['floor'] }}</td>
                                    <td class="fw-semibold">{{ $d['room'] }}</td>
                                    <td>{{ $d['ots'] }}</td>
                                    <td class="text-nowrap">
                                        <a class="mid-act" href="{{ route('admin.coe.venue-allocation.show', $d['id']) }}" title="Who sits where in this room">
                                            <i class="bi bi-eye" aria-hidden="true"></i><span>View Seating</span></a>
                                        <a class="mid-act" href="{{ route('admin.coe.venue-allocation.print', $d['id']) }}" target="_blank" rel="noopener" title="Print the room's seating plan">
                                            <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span></a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3"
                     data-dt-footer-for="midTable"></div>
            </div>
        </div>
    </div>
</div>
@include('admin.coe.partials.print_brand')
@endsection

@push('scripts')
<script src="{{ asset('js/coe-grid.js') }}?v={{ @filemtime(public_path('js/coe-grid.js')) ?: time() }}"></script>
<script>
$(function () {
    'use strict';
    // Soonest first, as the server orders it: no client re-sort by default.
    CoeGrid.init({
        table: '#midTable',
        title: 'My Invigilation Duty',
        snoCol: 0,
        order: [],
        noSort: [0, 12],
        exclude: [12],
        filters: { '#midFilterWhen': 2 },
        reset: '#midResetFilters',
        download: '#midDownloadBtn',
        print: '#midPrintBtn',
        searchToggle: '#midSearchToggle',
        searchSlot: '#midDtSearch',
        empty: { title: 'No Invigilation Duty', text: 'Duties appear here once the Controller of Examinations freezes a seating plan you invigilate.' }
    });
});
</script>
@endpush
