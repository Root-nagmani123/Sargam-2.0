@extends('admin.layouts.master')
@section('title', 'Edit Activity')

@section('setup_content')
<div class="container-fluid px-3">
    <h4 class="fw-bold mb-3" style="color:#1a3c6e;">Edit Activity</h4>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small">Course</label>
                    <select id="selcoursee" class="form-select form-select-sm">
                        @foreach($courses as $c)
                            <option value="{{ $c->c_code }}" {{ $c->c_code == $record->course ? 'selected' : '' }}>{{ $c->c_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">OT</label>
                    <select id="selote" class="form-select form-select-sm">
                        @foreach($ots as $ot)
                            <option value="{{ $ot->username }}" {{ $ot->username == $record->username ? 'selected' : '' }}>
                                {{ $ot->otname }}, {{ $ot->otcode }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">House</label>
                    <input type="text" class="form-control form-control-sm" value="{{ $house }}" readonly>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Activity</label>
                    <select id="selactivitye" class="form-select form-select-sm">
                        @foreach($activities as $act)
                            <option value="{{ $act->menuid }}" {{ $act->menuid == $record->activity ? 'selected' : '' }}>{{ $act->menun }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Value</label>
                    <input id="txtactvaluee" class="form-control form-control-sm" value="{{ $record->activityval }}">
                </div>
                <div class="col-md-3">
                    <button class="btn btn-primary btn-sm w-100" id="btnUpdate">Update</button>
                </div>
                <div class="col-md-3">
                    <a href="{{ route('fc-reg.admin.activities.index') }}" class="btn btn-outline-secondary btn-sm w-100">Back</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$('#btnUpdate').on('click', function() {
    $.ajax({
        type: 'POST',
        url: "{{ route('fc-reg.admin.activities.update', $record->activityid) }}",
        data: {
            _token: '{{ csrf_token() }}',
            _method: 'PUT',
            uactivity: $('#selactivitye').val(),
            actvalue: $('#txtactvaluee').val().trim(),
        },
        success: function(resp) {
            if (resp.status === 'ok') {
                window.location = "{{ route('fc-reg.admin.activities.index') }}";
            } else {
                alert('Update failed.');
            }
        }
    });
});
</script>
@endpush
