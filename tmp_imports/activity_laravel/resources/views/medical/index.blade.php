@extends('layouts.app')
@section('title','Medical Report')
@section('content')
<h2 class="mb-4">Medical Report</h2>
<form id="medForm">
@csrf
<center>
<table cellpadding="20" width="80%">
    <tr>
        <th>Select Course</th><th>:</th>
        <th>
            <select id="selcourser" name="course" style="font-size:16px;height:40px;width:300px;">
                <option value="">Select Course</option>
                @foreach($courses as $c)
                    <option value="{{ $c->c_code }}">{{ $c->c_name }}</option>
                @endforeach
            </select>
        </th>
        <th>OT Code</th><th>:</th>
        <th>
            <input type="text" id="txtotcode" name="ot"
                style="font-size:16px;height:40px;width:300px;"
                placeholder="Enter OT Code">
        </th>
    </tr>
</table>
<div id="spanotreport"></div>
</center>
</form>
@endsection

@push('scripts')
<script>
$('#txtotcode').on('blur', function() {
    var course = $('#selcourser').val();
    var ot     = $(this).val().trim();
    if (!course || !ot) return;
    $.get("{{ route('medical.show') }}", { course: course, ot: ot }, function(html) {
        $('#spanotreport').html(html);
    });
});

// Submit pathology form (upload_report.php → /medical/upload)
$(document).on('submit', '#pathoForm', function(e) {
    e.preventDefault();
    var fd = new FormData(this);
    fd.append('_token', $('meta[name="csrf-token"]').attr('content'));
    $.ajax({
        type: 'POST', url: "{{ route('medical.upload') }}",
        data: fd, contentType: false, processData: false,
        success: function(data) {
            if (data.status === 'ok') {
                alert('Saved successfully.');
                window.location.reload();
            } else {
                alert('Error saving. Please try again.');
            }
        }
    });
});
</script>
@endpush
