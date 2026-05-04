@extends('admin.layouts.master')
@section('title', 'FC Activities')

@section('setup_content')
<div class="container-fluid px-3">
    <x-breadcrum title="FC Activities - Home"></x-breadcrum>
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h4 class="fw-bold mb-0" style="color:#1a3c6e;">Post-Arrival Activities</h4>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('fc-reg.admin.activities.status.index') }}" class="btn btn-sm btn-outline-primary">Status</a>
            @if($showSetupLinks ?? false)
                <a href="{{ route('fc-reg.admin.activities.status.matrix') }}" class="btn btn-sm btn-outline-primary">All-deps matrix</a>
            @endif
            <a href="{{ route('fc-reg.admin.activities.reports.summary') }}" class="btn btn-sm btn-outline-secondary">Reports</a>
            @if($canAccessMedical ?? false)
                <a href="{{ route('fc-reg.admin.activities.medical.index') }}" class="btn btn-sm btn-outline-primary">Medical</a>
            @endif
            @if($showSetupLinks ?? false)
                <a href="{{ route('fc-reg.admin.activity-setup.departments.index') }}" class="btn btn-sm btn-outline-dark">Departments setup</a>
                <a href="{{ route('fc-reg.admin.activity-setup.masters.index') }}" class="btn btn-sm btn-outline-dark">Activities setup</a>
            @endif
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
                    <label class="form-label small">Rank</label>
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
            <p class="small text-muted mb-0 mt-2"><strong>FC activity coordinators</strong> and users with <strong>full setup access</strong> see every active activity. Other users see only activities for <strong>departments you are assigned to</strong> (Activity setup → Departments, or your single department in staff access). <strong>Upsert</strong> updates the last value; <strong>repeat</strong> keeps a new reading each time (medical report shows full history). If the list stays empty after choosing a course, ask a coordinator to assign the right department(s).</p>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 js-fc-datatable" data-export-title="FC Post-Arrival Activities">
                <thead class="table-light">
                    <tr>
                        <th>#</th><th>Name</th><th>OT Code</th><th>Course</th><th>Activity</th><th>Value</th><th>Date/Time</th><th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($activities as $i => $act)
                    @php
                        $actEditPayload = [
                            'updateUrl' => route('fc-reg.admin.activities.update', $act->activityid),
                            'course' => $act->course,
                            'menuid' => $act->activity,
                            'menun' => $act->activityMaster->menun ?? $act->activity,
                            'activityval' => $act->activityval,
                            'otname' => $act->ot->otname ?? '',
                            'otcode' => $act->ot->otcode ?? '',
                            'house' => $act->ot->house ?? '',
                            'housen' => $act->ot->housen ?? '',
                        ];
                    @endphp
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $act->ot->otname ?? '' }}</td>
                        <td>{{ $act->ot->otcode ?? '' }}</td>
                        <td>{{ $act->course }}</td>
                        <td>{{ $act->activityMaster->menun ?? $act->activity }}</td>
                        <td>{{ $act->activityval }}</td>
                        <td>{{ $act->activitydt }}</td>
                        <td>
                            <button type="button" class="btn btn-link btn-sm p-0 js-fc-act-edit"
                                data-bs-toggle="modal" data-bs-target="#modalFcActEdit"
                                data-fc-act-edit='@json($actEditPayload)'>Edit</button>
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

<div class="modal fade" id="modalFcActEdit" tabindex="-1" aria-labelledby="modalFcActEditLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title" id="modalFcActEditLabel">Edit activity</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-2">Course and OT cannot be changed here; only activity type and value are saved.</p>
                <div class="row g-2 mb-3">
                    <div class="col-md-4">
                        <label class="form-label small mb-0">Course</label>
                        <input type="text" class="form-control form-control-sm" id="fcActEditCourse" readonly>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label small mb-0">OT</label>
                        <input type="text" class="form-control form-control-sm" id="fcActEditOt" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small mb-0">House</label>
                        <input type="text" class="form-control form-control-sm" id="fcActEditHouse" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small mb-0">Rank</label>
                        <input type="text" class="form-control form-control-sm" id="fcActEditHousen" readonly>
                    </div>
                </div>
                <div class="row g-2 align-items-end">
                    <div class="col-md-6">
                        <label class="form-label small">Activity</label>
                        <select id="fcActEditActivity" class="form-select form-select-sm"><option value="">Loading…</option></select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Value</label>
                        <input type="text" id="fcActEditValue" class="form-control form-control-sm" maxlength="500">
                    </div>
                </div>
                <div id="fcActEditErr" class="alert alert-danger py-2 small mt-2 d-none mb-0"></div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" id="fcActEditSave">Save</button>
            </div>
        </div>
    </div>
</div>
@endsection

@include('admin.fc-activities.partials.datatable-tools')

@push('scripts')
<script>
(function() {
    const R = {
        courses: "{{ route('fc-reg.admin.activities.ajax.courses') }}",
        otName: "{{ route('fc-reg.admin.activities.ajax.ot-name') }}",
        house: "{{ route('fc-reg.admin.activities.ajax.house') }}",
        activities: "{{ route('fc-reg.admin.activities.ajax.activities') }}",
        store: "{{ route('fc-reg.admin.activities.store') }}",
        csrf: "{{ csrf_token() }}",
    };
    var fcActEditRow = null;
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
        $.get(R.activities, { ccode: this.value }, function(data) {
            const sel = $('#selactivity').empty().append('<option value="">Select Activity</option>');
            const rows = asArray(data);
            rows.forEach(function(a){
                let hint = '';
                if (a.entry_policy === 'upsert') hint = ' (upsert)';
                else if (a.entry_policy === 'repeat') hint = ' (repeat)';
                sel.append(`<option value="${a.menuid}">${a.menun}${hint}</option>`);
            });
            if (rows.length === 0) {
                sel.append('<option value="" disabled>No activities in your scope</option>');
            }
        }).fail(function(xhr) {
            $('#selactivity')
                .empty()
                .append('<option value="">Select Activity</option>')
                .append('<option value="" disabled>Failed to load activities</option>');
            console.error('Activity load failed', xhr?.responseText);
        });
    });
    $('#txtotcode').on('blur', function() {
        const otcode = this.value.trim();
        if (!otcode) return;
        const course = ($('#selcourse').val() || '').trim();
        $.get(R.otName, { otcode, course }, function(d) {
            $('#selot').val(d.name || '');
            $('#prewarning').toggleClass('d-none', !d.warning);
        });
        $.get(R.house, { otcode }, function(d) {
            $('#txthouse').val(d.house || '');
            $('#txthousen').val(d.housen || '');
        });
    });
    $('#btnSaveActivity').on('click', function() {
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
            if (resp.status === 'al') return alert('Already submitted for this OT and activity (unique policy).');
            alert('Unable to save activity.');
        });
    });

    var modalFcActEdit = document.getElementById('modalFcActEdit');
    if (modalFcActEdit) {
        modalFcActEdit.addEventListener('show.bs.modal', function (e) {
            var btn = e.relatedTarget;
            if (!btn || !btn.hasAttribute('data-fc-act-edit')) return;
            fcActEditRow = JSON.parse(btn.getAttribute('data-fc-act-edit'));
            $('#fcActEditErr').addClass('d-none').text('');
            $('#fcActEditCourse').val(fcActEditRow.course || '');
            var otLine = (fcActEditRow.otname || '') + (fcActEditRow.otcode ? ' — ' + fcActEditRow.otcode : '');
            $('#fcActEditOt').val(otLine);
            $('#fcActEditHouse').val(fcActEditRow.house || '');
            $('#fcActEditHousen').val(fcActEditRow.housen || '');
            $('#fcActEditValue').val(fcActEditRow.activityval || '');
            var sel = $('#fcActEditActivity').empty().append('<option value="">Loading…</option>');
            $.get(R.activities, { ccode: fcActEditRow.course || '' }, function (data) {
                var rows = asArray(data);
                sel.empty().append($('<option>').val('').text('Select Activity'));
                rows.forEach(function (a) {
                    var hint = '';
                    if (a.entry_policy === 'upsert') hint = ' (upsert)';
                    else if (a.entry_policy === 'repeat') hint = ' (repeat)';
                    sel.append($('<option>').val(a.menuid).text((a.menun || '') + hint));
                });
                var mid = fcActEditRow.menuid != null ? String(fcActEditRow.menuid) : '';
                if (mid && !sel.find('option').filter(function () { return $(this).val() === mid; }).length) {
                    sel.append($('<option>').val(mid).text((fcActEditRow.menun || mid) + ' (current)'));
                }
                sel.val(mid);
                if (rows.length === 0 && !mid) {
                    sel.append($('<option>').val('').text('No activities in your scope').prop('disabled', true));
                }
            }).fail(function () {
                sel.empty().append('<option value="">Failed to load</option>');
            });
        });
    }
    $('#fcActEditSave').on('click', function () {
        if (!fcActEditRow || !fcActEditRow.updateUrl) return;
        var uactivity = $('#fcActEditActivity').val();
        var actvalue = $('#fcActEditValue').val().trim();
        if (!uactivity || !actvalue) {
            $('#fcActEditErr').removeClass('d-none').text('Activity and value are required.');
            return;
        }
        $('#fcActEditErr').addClass('d-none').text('');
        $.ajax({
            url: fcActEditRow.updateUrl,
            method: 'POST',
            data: { _token: R.csrf, _method: 'PUT', uactivity: uactivity, actvalue: actvalue },
            headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
        }).done(function (resp) {
            if (resp && resp.status === 'ok') return window.location.reload();
            $('#fcActEditErr').removeClass('d-none').text('Unable to save changes.');
        }).fail(function (xhr) {
            var msg = 'Unable to save changes.';
            if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
            if (xhr.responseJSON && xhr.responseJSON.errors) {
                var parts = [];
                Object.keys(xhr.responseJSON.errors).forEach(function (k) {
                    (xhr.responseJSON.errors[k] || []).forEach(function (line) { parts.push(line); });
                });
                if (parts.length) msg = parts.join(' ');
            }
            $('#fcActEditErr').removeClass('d-none').text(msg);
        });
    });
})();
</script>
@endpush
