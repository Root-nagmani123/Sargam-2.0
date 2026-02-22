@extends('admin.layouts.master')

@section('title', 'Room Issues - Building-wise - Sargam')

@section('setup_content')
<div class="container-fluid px-2 px-sm-3 px-md-4">
    <x-breadcrum title="Room Issues - Building-wise" variant="glass" />
    <x-session_message />

    {{-- Summary cards --}}
    <div class="row row-cols-2 row-cols-md-3 g-3 g-xl-4 mb-4">
        <div class="col">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
                <div class="card-body d-flex align-items-center gap-3 p-4">
                    <div class="rounded-4 p-3 bg-warning bg-opacity-15 flex-shrink-0">
                        <i class="bi bi-hourglass-split fs-3 text-warning"></i>
                    </div>
                    <div>
                        <div class="display-6 fw-bold lh-1">{{ $summary['pending'] ?? 0 }}</div>
                        <small class="text-body-secondary text-uppercase">Pending</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
                <div class="card-body d-flex align-items-center gap-3 p-4">
                    <div class="rounded-4 p-3 bg-info bg-opacity-15 flex-shrink-0">
                        <i class="bi bi-exclamation-circle fs-3 text-info"></i>
                    </div>
                    <div>
                        <div class="display-6 fw-bold lh-1">{{ $summary['unresolved'] ?? 0 }}</div>
                        <small class="text-body-secondary text-uppercase">Unresolved</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card border border-danger border-2 shadow-sm rounded-4 overflow-hidden h-100">
                <div class="card-body d-flex align-items-center gap-3 p-4">
                    <div class="rounded-4 p-3 bg-danger bg-opacity-15 flex-shrink-0">
                        <i class="bi bi-exclamation-triangle-fill fs-3 text-danger"></i>
                    </div>
                    <div>
                        <div class="display-6 fw-bold lh-1 text-danger">{{ $summary['red'] ?? 0 }}</div>
                        <small class="text-body-secondary text-uppercase">Red (Exceeded)</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Building-wise stats - dynamic from hostel_building_master + issue_log_hostel_map --}}
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="card-header bg-primary bg-opacity-10 border-0 py-3 px-4">
            <h3 class="h6 fw-semibold mb-0 d-inline-flex align-items-center gap-2">
                <i class="bi bi-building text-primary"></i>
                Building-wise Issue Count
            </h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 table-striped-columns table-borderless">
                    <thead class="table-light">
                        <tr>
                            <th class="text-uppercase small fw-semibold ps-4 opacity-75">Building</th>
                            <th class="text-uppercase small fw-semibold text-center opacity-75">Pending</th>
                            <th class="text-uppercase small fw-semibold text-center opacity-75">Unresolved</th>
                            <th class="text-uppercase small fw-semibold text-center text-danger opacity-75">Red</th>
                            <th class="text-uppercase small fw-semibold text-end pe-4 opacity-75">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($buildingStats ?? [] as $b)
                        <tr>
                            <td class="ps-4">
                                <span class="fw-medium d-inline-flex align-items-center gap-2">
                                    <i class="bi bi-building text-body-tertiary"></i>
                                    {{ $b->name ?? '—' }}
                                </span>
                            </td>
                            <td class="text-center"><span class="badge rounded-pill text-bg-warning">{{ $b->pending_count ?? 0 }}</span></td>
                            <td class="text-center"><span class="badge rounded-pill text-bg-info">{{ $b->unresolved_count ?? 0 }}</span></td>
                            <td class="text-center"><span class="badge rounded-pill text-bg-danger">{{ $b->red_count ?? 0 }}</span></td>
                            <td class="text-end pe-4">
                                <a href="{{ route('admin.hostel.room-issues', ['building_id' => $b->id ?? $b->pk ?? '']) }}" class="btn btn-sm btn-outline-primary rounded-3 icon-link link-underline-opacity-0 link-underline-opacity-75-hover focus-ring focus-ring-primary">
                                    View <i class="bi bi-arrow-right"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-body-secondary">
                                <i class="bi bi-building-slash fs-1 d-block mb-2 opacity-50"></i>
                                No building stats
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Report new issue --}}
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center py-3 px-4">
            <h3 class="h6 fw-semibold mb-0 d-inline-flex align-items-center gap-2">
                <i class="bi bi-plus-circle text-primary"></i>
                Report New Issue
            </h3>
            <button type="button" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1 rounded-3 focus-ring focus-ring-primary" data-bs-toggle="collapse" data-bs-target="#reportIssueForm" aria-expanded="false">
                <i class="bi bi-plus"></i> Add
            </button>
        </div>
        <div class="collapse" id="reportIssueForm">
            <div class="card-body border-top bg-body-tertiary bg-opacity-25">
                <form action="{{ route('admin.hostel.room-issues.store') }}" method="POST">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-medium">Building</label>
                            <select name="hostel_building_id" class="form-select form-select-sm">
                                <option value="">— Select —</option>
                                @foreach($buildings ?? $buildingStats ?? [] as $b)
                                    <option value="{{ $b->id ?? $b->pk ?? '' }}">{{ $b->name ?? $b->hostel_building_name ?? $b->building_name ?? '—' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium">Room (optional)</label>
                            <input type="text" name="hostel_room_id" class="form-control form-control-sm" placeholder="Room no.">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium">Category <span class="text-danger">*</span></label>
                            <input type="text" name="category_name" class="form-control form-control-sm" required placeholder="e.g. Plumbing, Electrical">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-medium">Description <span class="text-danger">*</span></label>
                            <textarea name="description" class="form-control form-control-sm" rows="2" required placeholder="Describe the issue..."></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2 rounded-3 focus-ring focus-ring-primary">
                                <i class="bi bi-send"></i> Submit Issue
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Issue log - dynamic from issue_log_management + issue_log_hostel_map --}}
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-transparent border-0 py-3 px-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h3 class="h6 fw-semibold mb-0 d-inline-flex align-items-center gap-2">
                <i class="bi bi-list-check text-primary"></i>
                Issue Log
            </h3>
            <form method="GET" action="{{ route('admin.hostel.room-issues') }}" class="d-flex gap-2">
                <select name="building_id" class="form-select form-select-sm" style="width: auto; min-width: 160px;">
                    <option value="">— All Buildings —</option>
                    @foreach($buildings ?? [] as $b)
                        <option value="{{ $b->pk ?? $b->id ?? '' }}" {{ request('building_id') == (string)($b->pk ?? $b->id ?? '') ? 'selected' : '' }}>
                            {{ $b->name ?? $b->hostel_building_name ?? $b->building_name ?? '—' }}
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1">
                    <i class="bi bi-funnel"></i> Filter
                </button>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 table-striped-columns table-borderless">
                    <thead class="table-light">
                        <tr>
                            <th class="text-uppercase small fw-semibold ps-4 opacity-75">Code</th>
                            <th class="text-uppercase small fw-semibold opacity-75">Building / Room</th>
                            <th class="text-uppercase small fw-semibold opacity-75">Description</th>
                            <th class="text-uppercase small fw-semibold opacity-75">Status</th>
                            <th class="text-uppercase small fw-semibold opacity-75">Escalation</th>
                            <th class="text-uppercase small fw-semibold pe-4 opacity-75">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($issues ?? [] as $i)
                        @php
                            $status = $i->status ?? 'pending';
                            $escalation = $i->escalation_level ?? 0;
                            $isRed = $escalation >= 2;
                            $dateVal = $i->created_date ?? $i->created_at ?? null;
                        @endphp
                        <tr class="{{ $isRed ? 'table-danger' : '' }}">
                            <td class="ps-4"><code class="fs-6">{{ $i->issue_code ?? '—' }}</code></td>
                            <td>
                                <span class="text-truncate d-inline-block" style="max-width: 150px;" title="{{ ($i->building_name ?? '') }} / {{ ($i->room_name ?? '—') }}">
                                    {{ ($i->building_name ?? '') }} / {{ ($i->room_name ?? '—') }}
                                </span>
                            </td>
                            <td>
                                <span class="text-truncate d-inline-block" style="max-width: 200px;" title="{{ $i->description ?? '' }}">
                                    {{ Str::limit($i->description ?? '', 40) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge rounded-pill {{ $status === 'resolved' ? 'text-bg-success' : ($status === 'assigned' ? 'text-bg-info' : 'text-bg-warning') }}">
                                    {{ ucfirst($status) }}
                                </span>
                            </td>
                            <td>
                                @if($isRed)
                                    <span class="badge rounded-pill text-bg-danger">Red</span>
                                @elseif($escalation >= 1)
                                    <span class="badge rounded-pill text-bg-warning">Escalated</span>
                                @else
                                    <span class="badge rounded-pill text-bg-secondary">—</span>
                                @endif
                            </td>
                            <td class="pe-4"><small class="text-body-secondary">{{ $dateVal ? \Carbon\Carbon::parse($dateVal)->format('d M Y') : '—' }}</small></td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="text-body-secondary">
                                    <i class="bi bi-check2-circle fs-1 d-block mb-2 opacity-50"></i>
                                    <span>No issues reported yet.</span>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <a href="{{ route('admin.hostel.dashboard') }}" class="btn btn-outline-secondary rounded-3 d-inline-flex align-items-center gap-2 icon-link link-underline-opacity-0 link-underline-opacity-75-hover focus-ring focus-ring-secondary">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a>
    </div>
</div>
@endsection
