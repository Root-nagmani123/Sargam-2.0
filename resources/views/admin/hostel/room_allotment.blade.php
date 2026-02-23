@extends('admin.layouts.master')

@section('title', 'Room Allotment & Check-in - Sargam')

@section('setup_content')
<div class="container-fluid px-2 px-sm-3 px-md-4">
    <x-breadcrum title="Room Allotment & Check-in" variant="glass" />
    <x-session_message />

    <div class="row g-4">
        <div class="col-12 col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
                <div class="card-header bg-primary bg-opacity-10 border-0 py-3 px-4">
                    <h3 class="h6 fw-semibold mb-0 d-inline-flex align-items-center gap-2">
                        <i class="bi bi-person-plus-fill text-primary"></i>
                        New Check-in
                    </h3>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('admin.hostel.room-allotment.store') }}" method="POST" id="checkinForm">
                        @csrf
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-medium">Building</label>
                                <select name="hostel_building_id" id="hostel_building_id" class="form-select form-select-sm rounded-3 focus-ring focus-ring-primary">
                                    <option value="">— Select —</option>
                                    @foreach($buildings ?? [] as $b)
                                        <option value="{{ $b->pk ?? $b->id ?? '' }}">{{ $b->name ?? $b->hostel_building_name ?? $b->building_name ?? '—' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-medium">Room Number <span class="text-danger">*</span></label>
                                <select name="room_no" id="room_no" class="form-select form-select-sm rounded-3 focus-ring focus-ring-primary" required>
                                    <option value="">— Select Building First —</option>
                                </select>
                                <div id="roomNoSpinner" class="spinner-border spinner-border-sm text-primary mt-2 d-none" role="status"><span class="visually-hidden">Loading...</span></div>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-medium">Room Type</label>
                                <select name="room_type" class="form-select form-select-sm rounded-3 focus-ring focus-ring-primary" required>
                                    <option value="single">Single</option>
                                    <option value="double">Double</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-medium">Bed No</label>
                                <input type="number" name="bed_no" class="form-control focus-ring-primary" min="1" placeholder="1">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-medium">Participant Name <span class="text-danger">*</span></label>
                                <input type="text" name="participant_name" class="form-control focus-ring-primary" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-medium">Course <span class="text-danger">*</span></label>
                                <select name="course_name" class="form-select form-select-sm rounded-3 focus-ring focus-ring-primary" required>
                                    <option value="">— Select —</option>
                                    @foreach($courses ?? [] as $k => $v)
                                        <option value="{{ is_numeric($k) ? $v : $v }}">{{ $v }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-medium">Check-in Date <span class="text-danger">*</span></label>
                                <input type="date" name="check_in_date" class="form-control focus-ring-primary" required value="{{ date('Y-m-d') }}">
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-medium">Check-out Date</label>
                                <input type="date" name="check_out_date" class="form-control focus-ring-primary">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-medium">Issue at Check-in</label>
                                <div class="btn-group w-100 rounded-3 overflow-hidden" role="group">
                                    <input type="radio" class="btn-check" name="issue_at_checkin" id="issueNil" value="nil" checked>
                                    <label class="btn btn-outline-success btn-sm rounded-0" for="issueNil">Nil</label>
                                    <input type="radio" class="btn-check" name="issue_at_checkin" id="issueYes" value="yes">
                                    <label class="btn btn-outline-warning btn-sm rounded-0" for="issueYes">Yes (Register)</label>
                                </div>
                            </div>
                            <div class="col-12" id="issueDetailsWrap" style="display: none;">
                                <label class="form-label fw-medium">Issue Details <span class="text-danger">*</span></label>
                                <textarea name="issue_details" class="form-control focus-ring-primary" rows="3" placeholder="Describe the issue..."></textarea>
                            </div>
                            <div class="col-12 d-flex flex-wrap gap-2">
                                <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2 rounded-1 focus-ring focus-ring-primary">
                                    <i class="bi bi-check-lg"></i> Save Check-in
                                </button>
                                <a href="{{ route('admin.hostel.dashboard') }}" class="btn btn-outline-secondary rounded-1 icon-link link-underline-opacity-0 focus-ring focus-ring-secondary">Cancel</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-transparent border-0 d-flex flex-wrap justify-content-between align-items-center gap-2 py-3 px-4">
                    <h3 class="h6 fw-semibold mb-0 d-inline-flex align-items-center gap-2">
                        <i class="bi bi-list-ul text-primary"></i>
                        Room Occupancy
                    </h3>
                    <form method="GET" class="d-flex flex-wrap gap-2">
                        <select name="hostel" class="form-select form-select-sm" style="width: auto; min-width: 140px;">
                            <option value="">All Buildings</option>
                            @foreach($buildings ?? [] as $b)
                                <option value="{{ $b->code ?? $b->pk ?? $b->id ?? '' }}" {{ request('hostel') == (string)($b->code ?? $b->pk ?? $b->id ?? '') ? 'selected' : '' }}>{{ $b->name ?? $b->hostel_building_name ?? $b->building_name ?? '—' }}</option>
                            @endforeach
                        </select>
                        <select name="status" class="form-select form-select-sm" style="width: auto; min-width: 120px;">
                            <option value="">All Status</option>
                            <option value="occupied" {{ request('status') == 'occupied' ? 'selected' : '' }}>Occupied</option>
                            <option value="available" {{ request('status') == 'available' ? 'selected' : '' }}>Available</option>
                            <option value="maintenance" {{ request('status') == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                        </select>
                        <button type="submit" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1">
                            <i class="bi bi-funnel"></i> Filter
                        </button>
                    </form>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table text-nowrap align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="col">Room</th>
                                    <th class="col">Type</th>
                                    <th class="col">Participant</th>
                                    <th class="col">Course</th>
                                    <th class="col">Check-in</th>
                                    <th class="col">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($rooms ?? [] as $r)
                                <tr>
                                    <td><span class="badge rounded-pill text-bg-primary">{{ $r->room_no ?? '—' }}</span></td>
                                    <td>{{ $r->room_type ?? '—' }}</td>
                                    <td class="text-truncate" style="max-width: 140px;">{{ $r->participant_name ?? '—' }}</td>
                                    <td>{{ $r->course_name ?? '—' }}</td>
                                    <td><small class="text-body-secondary">{{ $r->check_in_date ?? '—' }}</small></td>
                                    <td><span class="badge rounded-pill text-bg-success">Occupied</span></td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <div class="text-body-secondary">
                                            <i class="bi bi-door-open fs-1 d-block mb-2 opacity-50"></i>
                                            <span>No allotments yet. Use the form to add a check-in.</span>
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const nilRadio = document.getElementById('issueNil');
    const yesRadio = document.getElementById('issueYes');
    const wrap = document.getElementById('issueDetailsWrap');
    const details = document.querySelector('textarea[name="issue_details"]');

    function toggleIssueDetails() {
        wrap.style.display = yesRadio.checked ? 'block' : 'none';
        if (yesRadio.checked) details.required = true;
        else { details.required = false; details.value = ''; }
    }
    nilRadio.addEventListener('change', toggleIssueDetails);
    yesRadio.addEventListener('change', toggleIssueDetails);
    toggleIssueDetails();

    // Room Number from Building selection
    const buildingSelect = document.getElementById('hostel_building_id');
    const roomSelect = document.getElementById('room_no');
    const roomSpinner = document.getElementById('roomNoSpinner');
    const roomsUrl = '{{ route("admin.hostel.rooms-by-building") }}';

    function loadRooms(buildingId) {
        roomSelect.innerHTML = '<option value="">— Select Building First —</option>';
        roomSelect.disabled = true;
        if (!buildingId) return;

        roomSpinner.classList.remove('d-none');
        roomSelect.innerHTML = '<option value="">— Loading —</option>';

        fetch(roomsUrl + '?building_id=' + encodeURIComponent(buildingId))
            .then(r => r.json())
            .then(data => {
                roomSpinner.classList.add('d-none');
                roomSelect.innerHTML = '<option value="">— Select Room —</option>';
                (data.rooms || []).forEach(function(r) {
                    if (r.value) {
                        const opt = document.createElement('option');
                        opt.value = r.value;
                        opt.textContent = r.label || r.value;
                        roomSelect.appendChild(opt);
                    }
                });
                roomSelect.disabled = false;
            })
            .catch(function() {
                roomSpinner.classList.add('d-none');
                roomSelect.innerHTML = '<option value="">— Select Room —</option>';
                roomSelect.disabled = false;
            });
    }

    buildingSelect.addEventListener('change', function() {
        loadRooms(this.value);
    });
});
</script>
@endpush
@endsection
