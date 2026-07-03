@extends('layouts.app')
@section('title','Home – Activity Tracker')

@section('content')
<h2 class="mb-4">Activity</h2>

<span id="spanres" style="color:red;display:block;"></span>

{{-- Activity recording form --}}
<form id="activityForm">
@csrf
<center>
<table cellpadding="20" width="80%">
    <tr>
        <th>Select Course</th><th>:</th>
        <th>
            <select id="selcourse" name="ccode" style="font-size:16px;height:40px;width:300px;" required>
                <option value="">Select Course</option>
            </select>
        </th>
        <th>OT Code</th><th>:</th>
        <th><input type="text" id="txtotcode" name="otcode" style="font-size:16px;height:40px;width:300px;" placeholder="Enter OT Code"></th>
    </tr>
    <tr>
        <th>Officer Trainee Name</th><th>:</th>
        <th>
            <span id="spanotname">
                <input type="text" id="selot" name="username" style="font-size:16px;height:40px;width:300px;" readonly>
            </span>
            <span id="prewarning" style="color:red;display:none;">(Consultation required)</span>
        </th>
        <th>House</th><th>:</th>
        <th>
            <span id="spanhouse">
                <input type="text" id="txthouse"  style="font-size:16px;height:40px;width:300px;" readonly>
                <input type="text" id="txthousen" style="font-size:16px;height:40px;width:300px;" readonly>
            </span>
        </th>
    </tr>
    <tr><th colspan="6"></th></tr>
</table>

{{-- Activity type + value (loaded after course select) --}}
<div id="spanactivity"></div>
</center>
</form>

{{-- Activities submitted by this staff member --}}
<table class="tbl" id="activityTable">
    <tr>
        <th>#</th><th>Name</th><th>OT Code</th><th>Course</th>
        <th>Activity</th><th>Value</th><th>Date/Time</th><th>Action</th>
    </tr>
    @forelse($activities as $i => $act)
    <tr>
        <td>{{ $i+1 }}</td>
        <td>{{ $act['otname'] }}</td>
        <td>{{ $act['otcode'] }}</td>
        <td>{{ $act['course'] }}</td>
        <td>{{ $act['activity'] }}</td>
        <td>{{ $act['activityval'] }}</td>
        <td>{{ $act['activitydt'] }}</td>
        <td>
            <a href="{{ route('activity.edit', $act['activityid']) }}">EDIT</a>
            |
            <form method="POST" action="{{ route('activity.destroy', $act['activityid']) }}" style="display:inline"
                  onsubmit="return confirm('Delete this record?')">
                @csrf @method('DELETE')
                <button type="submit" style="background:none;border:none;color:blue;cursor:pointer;padding:0">DELETE</button>
            </form>
        </td>
    </tr>
    @empty
    <tr><td colspan="8" style="text-align:center">No activities recorded yet.</td></tr>
    @endforelse
</table>
@endsection

@push('scripts')
<script>
const ROUTES = {
    courses:    "{{ route('ajax.courses') }}",
    ots:        "{{ route('ajax.ots') }}",
    otName:     "{{ route('ajax.ot-name') }}",
    house:      "{{ route('ajax.house') }}",
    activities: "{{ route('ajax.activities') }}",
    store:      "{{ route('activity.store') }}",
};

// Load courses on page load (original: loadData() on body onLoad)
$(function() { loadCourses(); });

function loadCourses() {
    $.get(ROUTES.courses, function(data) {
        var sel = $('#selcourse').empty().append('<option value="">Select Course</option>');
        $.each(data, function(_, c) {
            sel.append($('<option>').val(c.c_code).text(c.c_name));
        });
        sel.trigger('change.select2');
    });
}

// Course change → load OTs + activities (original: loadOT() + showAct())
$('#selcourse').on('change', function() {
    loadOTs($(this).val());
    loadActivities($(this).val());
});

// OT code blur → load name + house + activities (original: loadaction() on onfocusout)
$('#txtotcode').on('blur', function() {
    var otcode = $(this).val().trim();
    if (!otcode) return;
    loadOtName(otcode);
    loadHouse(otcode);
});

function loadOTs(course) {
    $.get(ROUTES.ots, { course: course }, function(data) {
        // OT dropdown hidden in original (uses text input), kept for compatibility
    });
}

function loadOtName(otcode) {
    $.get(ROUTES.otName, { otcode: otcode }, function(data) {
        $('#selot').val(data.name);
        $('#prewarning').toggle(data.warning);
    });
}

function loadHouse(otcode) {
    $.get(ROUTES.house, { otcode: otcode }, function(data) {
        $('#txthouse').val(data.house);
        $('#txthousen').val(data.housen);
    });
}

function loadActivities(ccode) {
    if (!ccode) { $('#spanactivity').empty(); return; }
    $.get(ROUTES.activities, { ccode: ccode }, function(data) {
        var html = '<table cellpadding="10"><tr>';
        html += '<td>Activity</td><td>:</td>';
        html += '<td><select id="selactivity" name="uactivity" style="font-size:16px;height:40px;width:300px;" onchange="showfile()">';
        html += '<option value="">Select Activity</option>';
        $.each(data, function(_, a) {
            html += '<option value="'+a.menuid+'">'+a.menun+'</option>';
        });
        html += '</select></td>';
        html += '<td>Value</td><td>:</td>';
        html += '<td><input type="text" id="txtactvalue" name="actvalue" style="font-size:16px;height:40px;width:300px;"></td>';
        html += '<td><textarea id="docremarks" name="actvalue" style="display:none;" cols="30" rows="3"></textarea></td>';
        html += '</tr><tr>';
        html += '<td colspan="6" style="text-align:center;">';
        html += '<button type="button" onclick="saveAct()" style="height:40px;width:200px;background:lightblue;">SAVE</button>';
        html += '</td></tr></table>';
        $('#spanactivity').html(html);
    });
}

// Toggle value vs remarks textarea (original: showfile())
function showfile() {
    var act = $('#selactivity').val();
    if (act === 'preremarks') {
        $('#txtactvalue').hide();
        $('#docremarks').show().attr('id','txtactvalue');
    } else {
        $('#docremarks').hide();
        $('#txtactvalue').show();
    }
}

// Save activity (original: saveAct() + upload.php)
function saveAct() {
    var otcode    = $('#txtotcode').val();
    var ccode     = $('#selcourse').val();
    var uactivity = $('#selactivity').val();
    var actvalue  = $('#txtactvalue').val();

    if (!ccode || !otcode || !uactivity || !actvalue) {
        alert('All fields are mandatory'); return;
    }

    $.post(ROUTES.store, {
        otcode: otcode, ccode: ccode, uactivity: uactivity, actvalue: actvalue
    }, function(data) {
        if (data.status === 'ok') {
            $('#spanres').text('Record saved successfully').show();
            $('#txtactvalue').val('');
            setTimeout(function(){ $('#spanres').hide(); location.reload(); }, 3000);
        } else if (data.status === 'al') {
            alert('Already submitted for this OT and activity.');
        } else {
            alert('Some problem occurred, please try again.');
        }
    });
}
</script>
@endpush
