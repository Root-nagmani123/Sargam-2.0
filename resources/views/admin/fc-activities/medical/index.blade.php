@extends('admin.layouts.master')
@section('title', 'Activity Medical')
@section('setup_content')
<div class="container-fluid px-3">
    <h4 class="fw-bold mb-3" style="color:#1a3c6e;">Medical Report</h4>
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-4">
                    <label class="form-label small">Course</label>
                    <select id="selcourser" class="form-select form-select-sm">
                        <option value="">Select Course</option>
                        @foreach($courses as $c)
                            <option value="{{ $c->c_code }}">{{ $c->c_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small">OT Code</label>
                    <input type="text" id="txtotcode" class="form-control form-control-sm" placeholder="Enter OT code">
                </div>
            </div>
        </div>
    </div>
    <div id="spanotreport"></div>
</div>
@endsection
@push('scripts')
<script>
$('#txtotcode').on('blur', function() {
    const course = $('#selcourser').val();
    const ot = $(this).val().trim();
    if (!course || !ot) return;
    $.get("{{ route('fc-reg.admin.activities.medical.show') }}", { course: course, ot: ot }, function(html) {
        $('#spanotreport').html(html);
    });
});
</script>
@endpush
