@extends('admin.layouts.master')
@section('title', 'FC Activities')

@section('setup_content')
<div class="container-fluid px-3">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h4 class="fw-bold mb-0" style="color:#1a3c6e;">Post-Arrival Activities</h4>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('fc-reg.admin.activities.status.all') }}" class="btn btn-sm btn-outline-primary">All Status</a>
            <a href="{{ route('fc-reg.admin.activities.reports.summary') }}" class="btn btn-sm btn-outline-secondary">Reports</a>
            <a href="{{ route('fc-reg.admin.activities.medical.index') }}" class="btn btn-sm btn-outline-success">Medical</a>
        </div>
    </div>

    @if(session('success')) <div class="alert alert-success py-2">{{ session('success') }}</div> @endif

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small">Course</label>
                    <select id="selcourse" class="form-select form-select-sm"><option value="">Select Course</option></select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">OT Code</label>
                    <input type="text" id="txtotcode" class="form-control form-control-sm" placeholder="Enter OT code">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">OT Name</label>
                    <input type="text" id="selot" class="form-control form-control-sm" readonly>
                    <small id="prewarning" class="text-danger d-none">Consultation required</small>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">House</label>
                    <input type="text" id="txthouse" class="form-control form-control-sm mb-1" readonly>
                    <input type="text" id="txthousen" class="form-control form-control-sm" readonly>
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Activity</label>
                    <select id="selactivity" class="form-select form-select-sm"><option value="">Select Activity</option></select>
                </div>
                <div class="col-md-5">
                    <label class="form-label small">Value</label>
                    <input type="text" id="txtactvalue" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <button type="button" id="btnSaveActivity" class="btn btn-sm btn-primary w-100">Save Activity</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="medicalBulkModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Medical Activity Entry</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-md-3"><label class="form-label small">Height</label><input type="text" id="m_height" class="form-control form-control-sm" placeholder="cm"></div>
                        <div class="col-md-3"><label class="form-label small">Weight</label><input type="text" id="m_weight" class="form-control form-control-sm" placeholder="kg"></div>
                        <div class="col-md-3"><label class="form-label small">SpO2</label><input type="text" id="m_spo2" class="form-control form-control-sm" placeholder="%"></div>
                        <div class="col-md-3"><label class="form-label small">Pulse</label><input type="text" id="m_pulse" class="form-control form-control-sm"></div>
                        <div class="col-md-4"><label class="form-label small">Blood Pressure</label><input type="text" id="m_bp" class="form-control form-control-sm" placeholder="120/80"></div>
                        <div class="col-md-4"><label class="form-label small">Vial Tube</label><input type="text" id="m_vialtube" class="form-control form-control-sm" placeholder="Done / Yes"></div>
                        <div class="col-md-4"><label class="form-label small">Blood Sample</label><input type="text" id="m_bloodsample" class="form-control form-control-sm" placeholder="Done / Yes"></div>
                        <div class="col-12"><label class="form-label small">Pre-remarks</label><textarea id="m_preremarks" rows="2" class="form-control form-control-sm"></textarea></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary btn-sm" id="btnSaveMedicalBulk">Save Medical Details</button>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th><th>Name</th><th>OT Code</th><th>Course</th><th>Activity</th><th>Value</th><th>Date/Time</th><th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($activities as $i => $act)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $act->ot->otname ?? '' }}</td>
                        <td>{{ $act->ot->otcode ?? '' }}</td>
                        <td>{{ $act->course }}</td>
                        <td>{{ $act->activityMaster->menun ?? $act->activity }}</td>
                        <td>{{ $act->activityval }}</td>
                        <td>{{ $act->activitydt }}</td>
                        <td>
                            <a href="{{ route('fc-reg.admin.activities.edit', $act->activityid) }}" class="btn btn-link btn-sm p-0">Edit</a>
                            <form method="POST" action="{{ route('fc-reg.admin.activities.destroy', $act->activityid) }}" class="d-inline" onsubmit="return confirm('Delete this record?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-link btn-sm text-danger p-0 ms-1">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-muted py-3">No activities recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
    const R = {
        courses: "{{ route('fc-reg.admin.activities.ajax.courses') }}",
        otName: "{{ route('fc-reg.admin.activities.ajax.ot-name') }}",
        house: "{{ route('fc-reg.admin.activities.ajax.house') }}",
        activities: "{{ route('fc-reg.admin.activities.ajax.activities') }}",
        store: "{{ route('fc-reg.admin.activities.store') }}",
        storeMedicalBulk: "{{ route('fc-reg.admin.activities.store-medical-bulk') }}",
    };
    function asArray(data) {
        if (Array.isArray(data)) return data;
        if (data && Array.isArray(data.data)) return data.data;
        return [];
    }

    $.get(R.courses, function(data) {
        const sel = $('#selcourse');
        asArray(data).forEach(function(c){ sel.append(`<option value="${c.c_code}">${c.c_name}</option>`); });
    }).fail(function() {
        alert('Unable to load courses. Please refresh the page.');
    });

    $('#selcourse').on('change', function() {
        const course = (this.value || '').trim();
        $.get(R.activities, { ccode: this.value }, function(data) {
            const sel = $('#selactivity').empty().append('<option value="">Select Activity</option>');
            const rows = asArray(data);
            sel.append('<option value="__medical_bundle__">Medical (fill all vitals at once)</option>');
            rows.forEach(function(a){ sel.append(`<option value="${a.menuid}">${a.menun}</option>`); });
            if (rows.length === 0) {
                sel.append('<option value="" disabled>No activities found</option>');
            }
        }).fail(function(xhr) {
            $('#selactivity')
                .empty()
                .append('<option value="">Select Activity</option>')
                .append('<option value="" disabled>Failed to load activities</option>');
            console.error('Activity load failed', course, xhr?.responseText);
        });
    });
    $('#txtotcode').on('blur', function() {
        const otcode = this.value.trim();
        if (!otcode) return;
        $.get(R.otName, { otcode }, function(d) {
            $('#selot').val(d.name || '');
            $('#prewarning').toggleClass('d-none', !d.warning);
        });
        $.get(R.house, { otcode }, function(d) {
            $('#txthouse').val(d.house || '');
            $('#txthousen').val(d.housen || '');
        });
    });
    $('#btnSaveActivity').on('click', function() {
        if ($('#selactivity').val() === '__medical_bundle__') {
            if (!$('#selcourse').val() || !$('#txtotcode').val().trim()) {
                alert('Please select course and OT code first.');
                return;
            }
            const modal = new bootstrap.Modal(document.getElementById('medicalBulkModal'));
            modal.show();
            return;
        }

        const payload = {
            _token: '{{ csrf_token() }}',
            ccode: $('#selcourse').val(),
            otcode: $('#txtotcode').val().trim(),
            uactivity: $('#selactivity').val(),
            actvalue: $('#txtactvalue').val().trim(),
        };
        if (!payload.ccode || !payload.otcode || !payload.uactivity || !payload.actvalue) {
            alert('All fields are mandatory.');
            return;
        }
        $.post(R.store, payload, function(resp) {
            if (resp.status === 'ok') return window.location.reload();
            if (resp.status === 'al') return alert('Already submitted for this OT and activity.');
            alert('Unable to save activity.');
        });
    });

    $('#btnSaveMedicalBulk').on('click', function() {
        const payload = {
            _token: '{{ csrf_token() }}',
            ccode: $('#selcourse').val(),
            otcode: $('#txtotcode').val().trim(),
            height: $('#m_height').val().trim(),
            weight: $('#m_weight').val().trim(),
            spo2: $('#m_spo2').val().trim(),
            pulse: $('#m_pulse').val().trim(),
            bp: $('#m_bp').val().trim(),
            preremarks: $('#m_preremarks').val().trim(),
            vialtube: $('#m_vialtube').val().trim(),
            bloodsample: $('#m_bloodsample').val().trim(),
        };

        if (!payload.ccode || !payload.otcode) {
            alert('Course and OT code are required.');
            return;
        }
        if (!payload.height && !payload.weight && !payload.spo2 && !payload.pulse && !payload.bp && !payload.preremarks && !payload.vialtube && !payload.bloodsample) {
            alert('Enter at least one medical value.');
            return;
        }

        $.post(R.storeMedicalBulk, payload, function(resp) {
            if (resp.status === 'ok') {
                window.location.reload();
                return;
            }
            alert(resp.message || 'Unable to save medical details.');
        }).fail(function(xhr) {
            alert(xhr?.responseJSON?.message || 'Unable to save medical details.');
        });
    });
})();
</script>
@endpush
