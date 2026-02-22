@extends('admin.layouts.master')

@section('title', 'Hostel-wise List - Sargam')

@section('setup_content')
<div class="container-fluid px-2 px-sm-3 px-md-4 py-3">
    <x-breadcrum title="Hostel-wise List" variant="glass" />
    <x-session_message />

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
                <div>
                    <h1 class="h4 fw-bold text-dark mb-1 d-inline-flex align-items-center gap-2">
                        <i class="bi bi-building text-primary"></i>
                        Hostel-wise List
                    </h1>
                    <p class="text-body-secondary small mb-0 mt-1 opacity-90">Data from Building Master, Building Floor Room Mapping, Assign Hostel.</p>
                </div>
                <div class="d-flex flex-wrap gap-2 flex-shrink-0">
                    <a href="{{ route('admin.hostel.dashboard') }}" class="btn btn-primary d-inline-flex align-items-center gap-2 rounded-3 focus-ring focus-ring-primary">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                    <a href="{{ route('admin.hostel.rooms') }}" class="btn btn-outline-primary d-inline-flex align-items-center gap-2 rounded-3 icon-link link-underline-opacity-0 link-underline-opacity-75-hover focus-ring focus-ring-primary">
                        <i class="bi bi-grid-3x3-gap"></i> Room List
                    </a>
                </div>
            </div>

            <div class="table-responsive rounded-4 overflow-hidden border">
                <table class="table table-hover align-middle mb-0 table-striped-columns table-borderless">
                    <thead class="table-light">
                        <tr>
                            <th class="text-uppercase small fw-semibold ps-4 opacity-75">#</th>
                            <th class="text-uppercase small fw-semibold opacity-75">Hostel / Building</th>
                            <th class="text-uppercase small fw-semibold text-center opacity-75">Total Rooms</th>
                            <th class="text-uppercase small fw-semibold text-center opacity-75">Allotted</th>
                            <th class="text-uppercase small fw-semibold text-center opacity-75">Available</th>
                            <th class="text-uppercase small fw-semibold text-center opacity-75">Under Maintenance</th>
                            <th class="text-uppercase small fw-semibold text-center opacity-75">Occupancy %</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($buildings ?? [] as $i => $h)
                        @php
                            $total = $h->total_rooms ?? 0;
                            $allotted = $h->allotted_count ?? 0;
                            $available = $h->available_count ?? 0;
                            $maintenance = $h->maintenance_count ?? 0;
                            $occupancy = $total > 0 ? round(($allotted / $total) * 100, 1) : 0;
                        @endphp
                        <tr>
                            <td class="ps-4 fw-medium text-body-secondary">{{ $i + 1 }}</td>
                            <td>
                                <span class="d-inline-flex align-items-center gap-2">
                                    <i class="bi bi-building-fill-gear text-primary"></i>
                                    <span class="fw-medium">{{ $h->name ?? 'Hostel' }}</span>
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge rounded-pill bg-body-secondary text-dark">{{ $total }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary">{{ $allotted }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge rounded-pill bg-success bg-opacity-10 text-success">{{ $available }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge rounded-pill bg-warning bg-opacity-25 text-warning-emphasis">{{ $maintenance }}</span>
                            </td>
                            <td class="text-center">
                                <span class="fw-medium {{ $occupancy >= 90 ? 'text-danger' : ($occupancy >= 70 ? 'text-warning-emphasis' : 'text-success') }}">
                                    {{ $occupancy }}%
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="text-body-secondary">
                                    <i class="bi bi-building fs-1 d-block mb-2 opacity-50"></i>
                                    <span>No hostel data found</span>
                                    <p class="small mb-0 mt-1">Setup Building Master, Floor, Building Floor Room Mapping (Hostel menu).</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
