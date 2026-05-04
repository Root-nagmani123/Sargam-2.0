@extends('admin.layouts.master')
@section('title', 'Activity status grid')

@push('styles')
<style>
    .fc-status-grid-hero {
        background: linear-gradient(135deg, rgba(26, 60, 110, 0.06) 0%, rgba(26, 60, 110, 0.02) 100%);
        border: 1px solid rgba(26, 60, 110, 0.12);
        border-radius: 12px;
        padding: 1.1rem 1.35rem;
    }
    .fc-status-stat {
        border-radius: 10px;
        padding: 0.5rem 0.85rem;
        background: #fff;
        border: 1px solid rgba(0, 0, 0, 0.06);
        font-size: 0.8125rem;
    }
    .fc-status-stat .num {
        font-weight: 700;
        color: #1a3c6e;
        font-size: 1.05rem;
    }
    .fc-status-grid-card {
        border-radius: 12px;
        overflow: hidden;
    }
    /* Header text: global .table th uses white !important — keep contrast on any background */
    .fc-status-grid-table thead th {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        color: #ffffff !important;
        vertical-align: middle;
        white-space: nowrap;
    }
    .fc-status-cell-value {
        font-size: 0.8125rem;
        line-height: 1.4;
        word-break: break-word;
    }
    /* Sticky OT columns (DataTables scrollX). Header must stay #004a93 + white text like .table th — light bg + white text was invisible. */
    .dataTables_scrollBody .fc-status-grid-table tbody td.fc-status-sticky-1 {
        position: sticky;
        left: 0;
        z-index: 2;
        min-width: 11rem;
        max-width: 14rem;
        box-shadow: 4px 0 12px -6px rgba(0, 0, 0, 0.18);
        background-color: #fff !important;
    }
    .dataTables_scrollBody .fc-status-grid-table tbody td.fc-status-sticky-2 {
        position: sticky;
        left: 11rem;
        z-index: 2;
        min-width: 7.5rem;
        box-shadow: 4px 0 12px -6px rgba(0, 0, 0, 0.18);
        background-color: #fff !important;
    }
    .dataTables_scrollHead .fc-status-grid-table thead th.fc-status-sticky-1,
    .dataTables_scrollHead .fc-status-grid-table thead th.fc-status-sticky-2 {
        position: sticky;
        left: 0;
        z-index: 4;
        min-width: 11rem;
        box-shadow: 4px 0 12px -6px rgba(0, 0, 0, 0.25);
        background-color: #004a93 !important;
        color: #ffffff !important;
    }
    .dataTables_scrollHead .fc-status-grid-table thead th.fc-status-sticky-2 {
        left: 11rem;
        min-width: 7.5rem;
    }
    .dataTables_scrollBody .table.fc-status-grid-table > tbody > tr:hover > td.fc-status-sticky-1,
    .dataTables_scrollBody .table.fc-status-grid-table > tbody > tr:hover > td.fc-status-sticky-2 {
        background-color: var(--bs-table-hover-bg, #e9ecef) !important;
    }
    .fc-status-grid-hint {
        font-size: 0.8125rem;
        color: #6c757d;
        border-top: 1px solid rgba(0, 0, 0, 0.06);
        margin-top: 0;
    }
</style>
@endpush

@section('setup_content')
@php
    $traineeCount = $rows->count();
    $colCount = $columnDefs->count();
@endphp

<div class="container-fluid px-3">
    <x-breadcrum title="FC Activities — Status grid"></x-breadcrum>

    <div class="fc-status-grid-hero mb-3">
        <div class="row align-items-start g-3">
            <div class="col-lg-7">
                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                    <h4 class="fw-bold mb-0" style="color: #1a3c6e;">
                        @if($combined)
                            All departments
                        @else
                            {{ $department->name }}
                        @endif
                    </h4>
                    @if($combined)
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 fw-normal">Matrix</span>
                    @endif
                </div>
                <p class="text-muted small mb-0 lh-lg">
                    @if($combined)
                        Combined view of every active activity column across departments. Scroll horizontally to compare; OT name and code stay pinned.
                    @else
                        Trainee rows and activity values for <strong>{{ $department->name }}</strong>. Search and column tools are below the table.
                    @endif
                </p>
            </div>
            <div class="col-lg-5">
                <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
                    @if($combined)
                        <a href="{{ route('fc-reg.admin.activities.status.index') }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1">
                            <i class="bi bi-building"></i> Department picker
                        </a>
                    @else
                        <a href="{{ route('fc-reg.admin.activities.status.index') }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1">
                            <i class="bi bi-building"></i> All departments
                        </a>
                    @endif
                    <a href="{{ route('fc-reg.admin.activities.index') }}" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1">
                        <i class="bi bi-house"></i> Activities home
                    </a>
                    <a href="{{ route('fc-reg.admin.activities.reports.summary') }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1">
                        <i class="bi bi-graph-up"></i> Reports
                    </a>
                    @if($canAccessMedical ?? false)
                        <a href="{{ route('fc-reg.admin.activities.medical.index') }}" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1">
                            <i class="bi bi-heart-pulse"></i> Medical
                        </a>
                    @endif
                    @if($showSetupLinks ?? false)
                        <a href="{{ route('fc-reg.admin.activity-setup.departments.index') }}" class="btn btn-sm btn-outline-dark d-inline-flex align-items-center gap-1">
                            <i class="bi bi-sliders"></i> Setup
                        </a>
                    @endif
                </div>
            </div>
        </div>

        @if($columnDefs->isNotEmpty())
            <div class="row g-2 mt-2 pt-2 border-top border-white border-opacity-50">
                <div class="col-sm-auto">
                    <div class="fc-status-stat h-100 d-flex align-items-center gap-2">
                        <i class="bi bi-people text-primary"></i>
                        <div><span class="num">{{ $traineeCount }}</span> <span class="text-muted">trainees</span></div>
                    </div>
                </div>
                <div class="col-sm-auto">
                    <div class="fc-status-stat h-100 d-flex align-items-center gap-2">
                        <i class="bi bi-list-check text-primary"></i>
                        <div><span class="num">{{ $colCount }}</span> <span class="text-muted">activity columns</span></div>
                    </div>
                </div>
                <div class="col d-none d-md-flex align-items-center">
                    <span class="small text-muted ms-md-2"><i class="bi bi-info-circle me-1"></i>Empty cells show — (no value recorded).</span>
                </div>
            </div>
        @endif
    </div>

    @if($columnDefs->isEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-table display-4 text-muted d-block mb-3"></i>
                <p class="text-muted mb-3">No active activities are configured for this view.</p>
                @if($showSetupLinks ?? false)
                    <a href="{{ route('fc-reg.admin.activity-setup.masters.index') }}" class="btn btn-sm btn-primary">Activities setup</a>
                @else
                    <a href="{{ route('fc-reg.admin.activities.status.index') }}" class="btn btn-sm btn-outline-secondary">Back</a>
                @endif
            </div>
        </div>
    @elseif($rows->isEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-person-x display-4 text-muted d-block mb-3"></i>
                <p class="text-muted mb-0">No active trainees to list.</p>
            </div>
        </div>
    @else
        <div class="card border-0 shadow-sm fc-status-grid-card">
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0 js-fc-datatable fc-status-grid-table" data-export-title="FC Activity status{{ $combined ? ' (matrix)' : '' }}">
                    <thead class="table-light">
                        <tr>
                            <th class="fc-status-sticky-1">OT name</th>
                            <th class="fc-status-sticky-2">OT code</th>
                            @unless($combined ?? false)
                                <th>Mobile</th>
                                <th>Service</th>
                            @endunless
                            @foreach($columnDefs as $col)
                                <th class="text-nowrap">{{ $col['header'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $row)
                            <tr>
                                <td class="fc-status-sticky-1 align-middle fw-medium">{{ $row['otname'] }}</td>
                                <td class="fc-status-sticky-2 align-middle"><code class="small">{{ $row['otcode'] }}</code></td>
                                @unless($combined ?? false)
                                    <td class="align-middle small text-nowrap">{{ $row['mobileno'] ?? '' }}</td>
                                    <td class="align-middle small">{{ $row['service'] ?? '' }}</td>
                                @endunless
                                @foreach($columnDefs as $col)
                                    @php $mid = $col['menuid']; @endphp
                                    @php $cell = $row['activities'][$mid] ?? null; @endphp
                                    <td class="align-middle">
                                        @if($cell !== null && $cell !== '')
                                            <span class="fc-status-cell-value" title="{{ $cell }}">{{ $cell }}</span>
                                        @else
                                            <span class="text-muted user-select-none">—</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="fc-status-grid-hint px-3 py-2 mb-0 d-md-none">
                <i class="bi bi-info-circle me-1"></i>Empty cells show — (no value recorded).
            </div>
        </div>
    @endif
</div>
@endsection

@include('admin.fc-activities.partials.datatable-tools')
