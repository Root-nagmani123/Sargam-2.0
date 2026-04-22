@extends('admin.layouts.master')
@section('title', 'FC Travel Plans')

@section('setup_content')
<div class="container-fluid px-3">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h4 class="fw-bold mb-0" style="color:#1a3c6e;">
            <i class="bi bi-train-front me-2"></i>FC Travel Plans
        </h4>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.travel.export.pickup') }}" class="btn btn-sm btn-success">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export Pickup List
            </a>
        </div>
    </div>

    <div class="row g-2 mb-3">
        @foreach([
            ['Total Plans', $summary['total'], 'bi-people-fill', '#1a3c6e'],
            ['Submitted', $summary['submitted'], 'bi-send-check-fill', '#198754'],
            ['Need Pickup', $summary['pickup'], 'bi-geo-alt-fill', '#0891b2'],
            ['Need Drop', $summary['drop'], 'bi-sign-turn-right-fill', '#d97706'],
        ] as [$l, $v, $ic, $c])
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm text-center py-3" style="border-radius:8px;">
                    <i class="bi {{ $ic }} fs-3 mb-1" style="color:{{ $c }}"></i>
                    <div class="fw-bold fs-3" style="color:{{ $c }}">{{ $v }}</div>
                    <div class="small text-muted">{{ $l }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <form method="GET" class="card border-0 shadow-sm mb-3 px-3 py-2">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <select name="session_id" class="form-select form-select-sm">
                    <option value="">All Sessions</option>
                    @foreach($sessions as $s)
                        <option value="{{ $s->id }}" {{ request('session_id') == $s->id ? 'selected' : '' }}>{{ $s->session_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="submitted" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    <option value="yes" {{ request('submitted') == 'yes' ? 'selected' : '' }}>Submitted</option>
                    <option value="no" {{ request('submitted') == 'no' ? 'selected' : '' }}>Draft</option>
                </select>
            </div>
            <div class="col-md-2">
                <div class="form-check mt-3">
                    <input type="checkbox" name="pickup" value="1" class="form-check-input" id="fPickup" {{ request('pickup') ? 'checked' : '' }}>
                    <label class="form-check-label small" for="fPickup">Needs Pickup</label>
                </div>
            </div>
            <div class="col-md-3">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Name / Username" value="{{ request('search') }}">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                <a href="{{ route('admin.travel.index') }}" class="btn btn-sm btn-outline-secondary ms-1">Reset</a>
            </div>
        </div>
    </form>

    <div class="card border-0 shadow-sm" style="border-radius:8px;">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0" style="font-size:12px;">
                <thead class="table-dark">
                    <tr>
                        <th class="px-3">#</th>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Service</th>
                        <th>Journey Type</th>
                        <th>Joining Date</th>
                        <th class="text-center">Pickup</th>
                        <th class="text-center">Drop</th>
                        <th class="text-center">Submitted</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($plans as $idx => $p)
                    <tr>
                        <td class="px-3">{{ $plans->firstItem() + $idx }}</td>
                        <td><code style="font-size:10px">{{ $p->username }}</code></td>
                        <td>{{ $p->full_name }}</td>
                        <td><span class="badge bg-primary-subtle text-primary" style="font-size:10px">{{ $p->service_code ?? '—' }}</span></td>
                        <td>{{ $p->travel_type_name ?? '—' }}</td>
                        <td>{{ $p->joining_date ?? '—' }}</td>
                        <td class="text-center">{{ $p->needs_pickup ? '✓' : '—' }}</td>
                        <td class="text-center">{{ $p->needs_drop ? '✓' : '—' }}</td>
                        <td class="text-center">
                            @if($p->is_submitted)
                                <span class="badge bg-success" style="font-size:10px">Yes</span>
                            @else
                                <span class="badge bg-warning text-dark" style="font-size:10px">Draft</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.travel.show', $p->username) }}" class="btn btn-xs btn-outline-primary py-0 px-2" style="font-size:11px">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-center text-muted py-3">No travel plans found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white py-2 px-3">{{ $plans->links() }}</div>
    </div>
</div>
@endsection
