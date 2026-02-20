@extends('admin.layouts.master')
@section('title', 'Duplicate Vehicle Pass Request - Sargam')
@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Duplicate Vehicle Pass Request"></x-breadcrum>

    <p class="text-muted mb-4">
        This page displays all vehicle pass request added in the system, and provides options to manage records such as edit, delete, excel upload, excel download, print etc.
    </p>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <form method="GET" class="d-flex align-items-center gap-2">
                        <label class="text-muted small">Show</label>
                        <select name="per_page" class="form-select form-select-sm" style="width:90px" onchange="this.form.submit()">
                            @foreach([10, 25, 50, 100] as $n)
                                <option value="{{ $n }}" {{ (int)request('per_page', 10) === $n ? 'selected' : '' }}>{{ $n }}</option>
                            @endforeach
                        </select>
                        <span class="text-muted small">entries</span>
                        @if(request('search'))
                            <input type="hidden" name="search" value="{{ request('search') }}">
                        @endif
                    </form>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="toggleColumnsBtn" title="Show / hide columns">
                        <i class="material-icons material-symbols-rounded" style="font-size:18px;">view_column</i>
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()" title="Print">
                        <i class="material-icons material-symbols-rounded" style="font-size:18px;">print</i>
                    </button>
                </div>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <form method="GET" class="d-flex gap-2 align-items-center">
                        <input type="hidden" name="per_page" value="{{ request('per_page', 10) }}">
                        <label class="text-muted small">Search within table:</label>
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Search..." style="width:200px">
                        <button class="btn btn-sm btn-primary">Search</button>
                    </form>
                    <a href="{{ route('admin.security.duplicate_vehicle_pass.create') }}" class="btn btn-success btn-sm">
                        <i class="material-icons material-symbols-rounded align-middle me-1" style="font-size:18px;">add</i>
                        Add New Request
                    </a>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0" id="duplicateVehPassTable">
                    <thead class="table-primary text-white">
                        <tr>
                            <th style="width:50px">S.NO.</th>
                            <th class="col-emp">EMPLOYEE NAME</th>
                            <th class="col-pass">VEHICLE PASS NO</th>
                            <th class="col-type">VEHICLE TYPE</th>
                            <th class="col-veh">VEHICLE NUMBER</th>
                            <th class="col-doc">UPLOADED DOCUMENT</th>
                            <th class="col-date">REQUEST DATE</th>
                            <th class="col-status">STATUS</th>
                            <th class="col-action" style="width:120px">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $idx => $r)
                            <tr>
                                <td>{{ ($requests->currentPage() - 1) * $requests->perPage() + $idx + 1 }}</td>
                                <td class="col-emp">{{ $r->employee_name ?? '--' }}</td>
                                <td class="col-pass">{{ $r->vehicle_pass_no ?? '--' }}</td>
                                <td class="col-type">{{ $r->vehicleType->vehicle_type ?? '--' }}</td>
                                <td class="col-veh">{{ $r->vehicle_no ?? '--' }}</td>
                                <td class="col-doc">
                                    @if($r->doc_upload)
                                        <a href="{{ asset('storage/' . $r->doc_upload) }}" target="_blank" class="text-primary">
                                            <i class="material-icons material-symbols-rounded" style="font-size:20px;">description</i> Download
                                        </a>
                                    @else
                                        --
                                    @endif
                                </td>
                                <td class="col-date">{{ $r->created_date ? $r->created_date->format('d-m-Y') : '--' }}</td>
                                <td class="col-status">
                                    @php
                                        $badge = match($r->status_text) {
                                            'Approved' => 'bg-success',
                                            'Rejected' => 'bg-danger',
                                            'Issued' => 'bg-info',
                                            default => 'bg-warning text-dark',
                                        };
                                    @endphp
                                    <span class="badge {{ $badge }}">{{ $r->status_text }}</span>
                                </td>
                                <td class="col-action">
                                    <div class="d-flex align-items-center gap-1">
                                        <a href="{{ route('admin.security.duplicate_vehicle_pass.show', encrypt($r->vehicle_tw_pk)) }}" class="btn btn-sm btn-outline-primary" title="View">
                                            <i class="material-icons material-symbols-rounded" style="font-size:18px;">visibility</i>
                                        </a>
                                        <a href="{{ route('admin.security.duplicate_vehicle_pass.edit', encrypt($r->vehicle_tw_pk)) }}" class="btn btn-sm btn-outline-success" title="Edit">
                                            <i class="material-icons material-symbols-rounded" style="font-size:18px;">edit</i>
                                        </a>
                                        <form action="{{ route('admin.security.duplicate_vehicle_pass.delete', encrypt($r->vehicle_tw_pk)) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this request?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                <i class="material-icons material-symbols-rounded" style="font-size:18px;">delete</i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-5">
                                    No data available in table.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                <div class="text-muted small">
                    Showing {{ $requests->firstItem() ?? 0 }} to {{ $requests->lastItem() ?? 0 }} of {{ $requests->total() }} entries
                </div>
                <div>
                    {{ $requests->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    document.getElementById('toggleColumnsBtn')?.addEventListener('click', function() {
        document.querySelectorAll('#duplicateVehPassTable .col-doc').forEach(function(el) { el.classList.toggle('d-none'); });
    });
})();
</script>
@endpush
@endsection
