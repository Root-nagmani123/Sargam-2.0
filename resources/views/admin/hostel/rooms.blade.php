@extends('admin.layouts.master')

@section('title', 'Hostel Rooms - Sargam')

@section('setup_content')
<div class="container-fluid px-2 px-sm-3 px-md-4 py-3">
    <x-breadcrum title="Hostel Room List" variant="glass" />
    <x-session_message />

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
                <div>
                    <h1 class="h4 fw-bold text-dark mb-1 d-inline-flex align-items-center gap-2">
                        <i class="bi bi-grid-3x3-gap text-primary"></i>
                        Hostel Rooms
                    </h1>
                    <p class="text-body-secondary small mb-0 mt-1 opacity-90">Data from Building Master, Floor, Building Floor Room Mapping. Filter by building.</p>
                </div>
                <div class="d-flex flex-wrap gap-2 flex-shrink-0">
                    <a href="{{ route('admin.hostel.dashboard') }}" class="btn btn-primary d-inline-flex align-items-center gap-2 rounded-3 focus-ring focus-ring-primary">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                    <a href="{{ route('admin.hostel.room-allotment') }}" class="btn btn-outline-primary d-inline-flex align-items-center gap-2 rounded-3 icon-link link-underline-opacity-0 link-underline-opacity-75-hover focus-ring focus-ring-primary">
                        <i class="bi bi-person-plus"></i> Check-in
                    </a>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.hostel.rooms') }}" class="mb-4">
                <div class="row g-2 g-sm-3 align-items-end">
                    <div class="col-md-4 col-lg-3">
                        <label for="building_id" class="form-label fw-medium">Building</label>
                        <select class="form-select form-select-sm rounded-3 focus-ring focus-ring-primary" id="building_id" name="building_id">
                            <option value="">— All —</option>
                            @foreach($buildings ?? [] as $b)
                                <option value="{{ $b->pk ?? $b->id ?? '' }}" {{ request('building_id') == (string)($b->pk ?? $b->id ?? '') ? 'selected' : '' }}>{{ $b->name ?? $b->hostel_building_name ?? $b->building_name ?? '—' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1 rounded-3 focus-ring focus-ring-primary">
                            <i class="bi bi-funnel"></i> Filter
                        </button>
                    </div>
                </div>
            </form>

            <div class="table-responsive rounded-4 overflow-hidden border">
                <table class="table table-hover align-middle mb-0 table-striped-columns table-borderless">
                    <thead class="table-light">
                        <tr>
                            <th class="text-uppercase small fw-semibold ps-4 opacity-75">Building</th>
                            <th class="text-uppercase small fw-semibold opacity-75">Floor</th>
                            <th class="text-uppercase small fw-semibold opacity-75">Room</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rooms ?? [] as $r)
                        <tr>
                            <td class="ps-4">
                                <span class="d-inline-flex align-items-center gap-2">
                                    <i class="bi bi-building text-body-tertiary"></i>
                                    {{ $r->building_name ?? '—' }}
                                </span>
                            </td>
                            <td>
                                <span class="d-inline-flex align-items-center gap-2">
                                    <i class="bi bi-layers text-body-tertiary"></i>
                                    {{ $r->floor_name ?? '—' }}
                                </span>
                            </td>
                            <td>
                                <span class="badge rounded-pill text-bg-primary bg-opacity-10 text-primary">{{ $r->room_name ?? '—' }}</span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center py-5">
                                <div class="text-body-secondary">
                                    <i class="bi bi-grid-3x3-gap fs-1 d-block mb-2 opacity-50"></i>
                                    <span>No rooms found</span>
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
