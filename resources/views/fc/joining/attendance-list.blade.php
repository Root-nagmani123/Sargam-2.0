@extends('layouts.app')
@section('title', 'Joining Attendance – {{ $hostel }}')
@section('content')
<div class="row g-3">
    <div class="col-12">
        <div class="card border-0 shadow-sm" style="border-radius:10px;">
            <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold mb-0" style="color:#1a3c6e;">
                        <i class="bi bi-house-door me-2"></i>FC Joining Attendance – {{ $hostel }} Hostel
                    </h5>
                    <small class="text-muted">
                        {{ $session?->session_name ?? 'Current Session' }} |
                        Total: {{ $students->count() }} |
                        Joined: {{ $attendanceMap->where('attended', 1)->count() }}
                    </small>
                </div>
                <div class="d-flex gap-2">
                    <!-- Hostel switcher -->
                    @foreach(['Ganga','Kaveri','Narmada','Mahanadi','HappyValley','Silverwood'] as $h)
                        <a href="{{ route('fc-reg.admin.joining.hostel', $h) }}"
                           class="btn btn-sm {{ $h === $hostel ? 'btn-primary' : 'btn-outline-secondary' }} py-0 px-2">
                            {{ $h }}
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th class="ps-3">#</th>
                                <th>Username</th>
                                <th>Name</th>
                                <th>Service</th>
                                <th>Room No.</th>
                                <th>Joining Date</th>
                                <th>Time</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($students as $i => $student)
                            @php $att = $attendanceMap->get($student->username); @endphp
                            <tr>
                                <td class="ps-3 text-muted small">{{ $i + 1 }}</td>
                                <td class="fw-semibold small">{{ $student->username }}</td>
                                <td class="small">{{ $student->session?->session_name ?? '—' }}</td>
                                <td class="small">{{ $student->allotted_hostel }}</td>
                                <td class="small">{{ $att?->room_no ?? '—' }}</td>
                                <td class="small">{{ $att?->joining_date?->format('d M Y') ?? '—' }}</td>
                                <td class="small">{{ $att?->joining_time ?? '—' }}</td>
                                <td>
                                    @if($att?->attended)
                                        <span class="badge bg-success-subtle text-success">Joined</span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning">Pending</span>
                                    @endif
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary py-0 px-2"
                                            data-bs-toggle="modal"
                                            data-bs-target="#markModal"
                                            data-username="{{ $student->username }}"
                                            data-room="{{ $att?->room_no }}"
                                            data-date="{{ $att?->joining_date?->format('Y-m-d') }}"
                                            data-attended="{{ $att?->attended ? 1 : 0 }}"
                                            data-remarks="{{ $att?->remarks }}"
                                            onclick="fillModal(this)">
                                        <i class="bi bi-pencil me-1"></i>Mark
                                    </button>
                                    <a href="{{ route('fc-reg.admin.joining.medical', $student->username) }}"
                                       class="btn btn-sm btn-outline-secondary py-0 px-2 ms-1">
                                        <i class="bi bi-heart-pulse me-1"></i>Medical
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    No students allotted to {{ $hostel }} hostel.
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

<!-- Mark Attendance Modal -->
<div class="modal fade" id="markModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header" style="background:#1a3c6e;color:#fff;">
                <h6 class="modal-title fw-bold">
                    <i class="bi bi-clipboard-check me-2"></i>Mark Attendance
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('fc-reg.admin.joining.mark', $hostel) }}">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="username" id="modal_username">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Username</label>
                            <input type="text" id="modal_username_display" class="form-control" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Room No.</label>
                            <input type="text" name="room_no" id="modal_room" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Joining Date <span class="text-danger">*</span></label>
                            <input type="date" name="joining_date" id="modal_date" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Joining Time</label>
                            <input type="time" name="joining_time" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Attendance Status <span class="text-danger">*</span></label>
                            <select name="attended" id="modal_attended" class="form-select" required>
                                <option value="0">Not Joined</option>
                                <option value="1">Joined</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Remarks</label>
                            <input type="text" name="remarks" id="modal_remarks" class="form-control" placeholder="Optional remarks…">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-save me-1"></i>Save Attendance
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function fillModal(btn) {
    document.getElementById('modal_username').value         = btn.dataset.username;
    document.getElementById('modal_username_display').value = btn.dataset.username;
    document.getElementById('modal_room').value             = btn.dataset.room || '';
    document.getElementById('modal_date').value             = btn.dataset.date || '';
    document.getElementById('modal_attended').value         = btn.dataset.attended || '0';
    document.getElementById('modal_remarks').value          = btn.dataset.remarks || '';
}
</script>
@endpush
