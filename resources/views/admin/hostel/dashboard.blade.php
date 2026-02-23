@extends('admin.layouts.master')

@section('title', 'Hostel Management Dashboard - Sargam')

@section('setup_content')
<div class="container-fluid px-2 px-sm-3 px-md-4">
    <x-breadcrum title="Hostel Management Dashboard" variant="glass" />
    <x-session_message />

    {{-- Status bar + Quick actions --}}
    <div class="d-flex flex-wrap align-items-center gap-3 mb-4 py-2">
        <span class="badge text-bg-success rounded-pill d-inline-flex align-items-center gap-2 px-3 py-2 fw-medium" style="font-size: 0.8125rem;">
            <span class="rounded-circle bg-white bg-opacity-75 hostel-dot" style="width: 8px; height: 8px;"></span>
            Live
        </span>
        <span class="text-body-secondary small">Last updated: <span id="lastUpdated" class="fw-semibold">—</span></span>
        <div class="ms-auto d-flex flex-wrap gap-2">
            <a href="{{ route('admin.hostel.room-allotment') }}" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-2 rounded-3 focus-ring focus-ring-primary">
                <i class="bi bi-plus-circle"></i>
                <span>New Check-in</span>
            </a>
            <a href="{{ route('admin.hostel.room-issues') }}" class="btn btn-outline-danger btn-sm d-inline-flex align-items-center gap-2 rounded-3 focus-ring focus-ring-danger">
                <i class="bi bi-exclamation-triangle"></i>
                <span>Report Issue</span>
            </a>
            <a href="{{ route('admin.hostel.rooms') }}" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-2 rounded-3 icon-link link-underline-opacity-0 link-underline-opacity-75-hover focus-ring focus-ring-secondary">
                <i class="bi bi-grid-3x3-gap"></i>
                <span>View Rooms</span>
            </a>
        </div>
    </div>

    {{-- Stat cards - Bootstrap 5.3 --}}
    <div class="row row-cols-2 row-cols-lg-4 g-3 g-xl-4 mb-4">
        <div class="col">
            <div class="card border-0 shadow-sm h-100 rounded-4 overflow-hidden hostel-stat-card">
                <div class="card-body d-flex align-items-center gap-3 p-4">
                    <div class="rounded-4 p-3 bg-primary bg-opacity-10 flex-shrink-0">
                        <i class="bi bi-door-open fs-2 text-primary"></i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <div class="display-6 fw-bold lh-1 mb-1" id="stat-total">{{ $totals['total'] ?? 0 }}</div>
                        <small class="text-dark">Total Rooms</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card border-0 shadow-sm h-100 rounded-4 overflow-hidden hostel-stat-card">
                <div class="card-body d-flex align-items-center gap-3 p-4">
                    <div class="rounded-4 p-3 bg-info bg-opacity-10 flex-shrink-0">
                        <i class="bi bi-people fs-2 text-info"></i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <div class="display-6 fw-bold lh-1 mb-1" id="stat-allotted">{{ $totals['allotted'] ?? 0 }}</div>
                        <small class="text-dark">Allotted</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card border-0 shadow-sm h-100 rounded-4 overflow-hidden hostel-stat-card">
                <div class="card-body d-flex align-items-center gap-3 p-4">
                    <div class="rounded-4 p-3 bg-success bg-opacity-10 flex-shrink-0">
                        <i class="bi bi-check2-circle fs-2 text-success"></i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <div class="display-6 fw-bold lh-1 mb-1" id="stat-available">{{ $totals['available'] ?? 0 }}</div>
                        <small class="text-dark">Available</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card border-0 shadow-sm h-100 rounded-4 overflow-hidden hostel-stat-card">
                <div class="card-body d-flex align-items-center gap-3 p-4">
                    <div class="rounded-4 p-3 bg-warning bg-opacity-15 flex-shrink-0">
                        <i class="bi bi-tools fs-2 text-warning-emphasis"></i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <div class="display-6 fw-bold lh-1 mb-1" id="stat-maintenance">{{ $totals['maintenance'] ?? 0 }}</div>
                        <small class="text-dark">Under Maintenance</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Hostel-wise overview --}}
    <div class="row g-3 g-xl-4 mb-4">
        <div class="col-12">
            <h2 class="h5 fw-semibold text-dark mb-0 d-inline-flex align-items-center gap-2">
                <i class="bi bi-building text-primary"></i>
                Hostel-wise Overview
            </h2>
        </div>
        @forelse($buildings ?? [] as $h)
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card border-0 shadow-sm h-100 rounded-4 overflow-hidden hostel-building-card position-relative">
                <div class="card-header bg-primary py-3">
                    <h3 class="h6 fw-semibold mb-0 text-white d-inline-flex align-items-center gap-2">
                        <i class="bi bi-building-fill-gear text-white"></i>
                        {{ $h->name ?? 'Hostel' }}
                    </h3>
                </div>
                <div class="card-body p-4">
                    @php
                        $total = $h->total_rooms ?? 0;
                        $allotted = $h->allotted_count ?? 0;
                        $available = $h->available_count ?? 0;
                        $maintenance = $h->maintenance_count ?? 0;
                    @endphp
                    <div class="row g-2 text-center">
                        <div class="col-3">
                            <div class="rounded-3 p-2 p-sm-3 bg-body-tertiary">
                                <div class="fw-bold fs-5 text-dark">{{ $total }}</div>
                                <small class="text-dark opacity-90">Total</small>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="rounded-3 p-2 p-sm-3 bg-primary bg-opacity-10">
                                <div class="fw-bold fs-5 text-primary">{{ $allotted }}</div>
                                <small class="text-dark opacity-90">Allotted</small>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="rounded-3 p-2 p-sm-3 bg-success bg-opacity-10">
                                <div class="fw-bold fs-5 text-success">{{ $available }}</div>
                                <small class="text-dark opacity-90">Available</small>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="rounded-3 p-2 p-sm-3 bg-warning bg-opacity-25">
                                <div class="fw-bold fs-5 text-warning-emphasis">{{ $maintenance }}</div>
                                <small class="text-dark opacity-90">Maint.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @empty
        @foreach($hostels as $h)
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm h-100 rounded-4 overflow-hidden hostel-building-card">
                <div class="card-header bg-primary bg-opacity-10 border-0 py-3">
                    <h3 class="h6 fw-semibold mb-0 text-dark">{{ $h['name'] }}</h3>
                </div>
                <div class="card-body p-4">
                    <div class="row g-2 text-center">
                        <div class="col-3"><div class="rounded-4 p-3 bg-body-tertiary"><div class="fw-bold fs-5">{{ $h['total'] }}</div><small class="text-body-secondary">Total</small></div></div>
                        <div class="col-3"><div class="rounded-4 p-3 bg-primary bg-opacity-15"><div class="fw-bold fs-5 text-primary">{{ $h['allotted'] }}</div><small class="text-body-secondary">Allotted</small></div></div>
                        <div class="col-3"><div class="rounded-4 p-3 bg-success bg-opacity-15"><div class="fw-bold fs-5 text-success">{{ $h['available'] }}</div><small class="text-body-secondary">Available</small></div></div>
                        <div class="col-3"><div class="rounded-4 p-3 bg-warning bg-opacity-25"><div class="fw-bold fs-5 text-warning-emphasis">{{ $h['maintenance'] }}</div><small class="text-body-secondary">Maint.</small></div></div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
        @endforelse
    </div>

    {{-- Recent check-ins / Upcoming check-outs --}}
    <div class="row g-4">
        <div class="col-12 col-xl-6">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
                <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center py-3 px-4">
                    <h3 class="h6 fw-semibold mb-0 d-inline-flex align-items-center gap-2">
                        <i class="bi bi-box-arrow-in-right text-success"></i>
                        Recent Check-ins
                    </h3>
                    <a href="{{ route('admin.hostel.room-allotment') }}" class="btn btn-sm btn-outline-primary rounded-3 icon-link link-underline-opacity-0 link-underline-opacity-75-hover focus-ring focus-ring-primary">
                        View all <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table text-nowrapalign-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Room</th>
                                    <th>Participant</th>
                                    <th>Course</th>
                                    <th>Check-in</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentCheckIns ?? [] as $r)
                                <tr class="align-middle">
                                    <td><span class="badge text-bg-primary bg-opacity-10 text-primary rounded-pill">{{ $r->room_no ?? '—' }}</span></td>
                                    <td class="text-truncate" style="max-width: 140px;">{{ $r->participant_name ?? '—' }}</td>
                                    <td>{{ $r->course_name ?? '—' }}</td>
                                    <td><small class="text-body-secondary">{{ $r->check_in_date ? \Carbon\Carbon::parse($r->check_in_date)->format('d M Y') : '—' }}</small></td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center py-5">
                                        <div class="text-body-secondary">
                                            <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
                                            <span>No recent check-ins</span>
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
        <div class="col-12 col-xl-6">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
                <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center py-3 px-4">
                    <h3 class="h6 fw-semibold mb-0 d-inline-flex align-items-center gap-2">
                        <i class="bi bi-box-arrow-right text-warning"></i>
                        Upcoming Check-outs
                    </h3>
                    <a href="{{ route('admin.hostel.room-allotment') }}" class="btn btn-sm btn-outline-primary rounded-3 icon-link link-underline-opacity-0 link-underline-opacity-75-hover focus-ring focus-ring-primary">
                        View all <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table text-nowrap align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Room</th>
                                    <th>Participant</th>
                                    <th>Course</th>
                                    <th>Check-out</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($upcomingCheckOuts ?? [] as $r)
                                <tr class="align-middle">
                                    <td><span class="badge text-bg-warning bg-opacity-25 text-warning-emphasis rounded-pill">{{ $r->room_no ?? '—' }}</span></td>
                                    <td class="text-truncate" style="max-width: 120px;">{{ $r->participant_name ?? '—' }}</td>
                                    <td>{{ $r->course_name ?? '—' }}</td>
                                    <td><small class="text-body-secondary">{{ $r->check_out_date ? \Carbon\Carbon::parse($r->check_out_date)->format('d M Y') : '—' }}</small></td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center py-5">
                                        <div class="text-body-secondary">
                                            <i class="bi bi-calendar-x fs-1 d-block mb-2 opacity-50"></i>
                                            <span>No upcoming check-outs</span>
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
    </div>
</div>

<style>
.hostel-dot { animation: hostel-pulse 2s ease-in-out infinite; }
@keyframes hostel-pulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.5; transform: scale(1.15); }
}
.hostel-stat-card { transition: transform 0.2s ease, box-shadow 0.2s ease; cursor: default; }
.hostel-stat-card:hover { transform: translateY(-4px); box-shadow: 0 0.5rem 1.5rem rgba(0,0,0,0.12) !important; }
.hostel-building-card { transition: transform 0.2s ease, box-shadow 0.2s ease; }
.hostel-building-card:hover { transform: translateY(-2px); box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1) !important; }
.tracking-tight { letter-spacing: 0.025em; }
</style>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    function updateTimestamp() {
        document.getElementById('lastUpdated').textContent = new Date().toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    }
    updateTimestamp();
    setInterval(updateTimestamp, 5000);
});
</script>
@endpush
@endsection
