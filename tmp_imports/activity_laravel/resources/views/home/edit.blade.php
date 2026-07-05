@extends('layouts.app')
@section('title','Edit Activity')
@section('content')
<h2 class="mb-4">Edit Activity</h2>
<span id="spanres"></span>

<form id="editForm">
<center>
<table cellpadding="20" width="80%">
    <tr>
        <th>Select Course</th><th>:</th>
        <th>
            <select id="selcoursee" name="ccode" style="font-size:16px;height:40px;width:300px;" required>
                <option value="">Select Course</option>
                @foreach($courses as $c)
                    <option value="{{ $c->c_code }}" {{ $c->c_code == $record->course ? 'selected':'' }}>
                        {{ $c->c_name }}
                    </option>
                @endforeach
            </select>
        </th>
        <th>Select Officer Trainee</th><th>:</th>
        <th>
            <select id="selote" name="username" style="font-size:16px;height:40px;width:300px;" required>
                <option value="">Select Officer Trainee</option>
                @foreach($ots as $ot)
                    <option value="{{ $ot->username }}" {{ $ot->username == $record->username ? 'selected':'' }}>
                        {{ $ot->otname }}, {{ $ot->otcode }}
                    </option>
                @endforeach
            </select>
        </th>
        <th>House</th><th>:</th>
        <th><input type="text" value="{{ $house }}" style="font-size:16px;height:40px;width:200px;" readonly></th>
    </tr>
    <tr>
        <td>Activity</td><td>:</td>
        <td>
            <select id="selactivitye" name="uactivity" style="font-size:16px;height:40px;width:300px;">
                <option value="">Select Activity</option>
                @foreach($activities as $act)
                    <option value="{{ $act->menuid }}" {{ $act->menuid == $record->activity ? 'selected':'' }}>
                        {{ $act->menun }}
                    </option>
                @endforeach
            </select>
        </td>
        <td>Value</td><td>:</td>
        <td><textarea id="txtactvaluee" cols="40" rows="2">{{ $record->activityval }}</textarea></td>
    </tr>
    <tr>
        <td colspan="6" style="text-align:center;">
            <button type="button" onclick="updateAct('{{ $record->activityid }}')"
                style="height:40px;width:200px;background:lightblue;">UPDATE</button>
        </td>
    </tr>
</table>
</center>
</form>
@endsection

@push('scripts')
<script>
function updateAct(activityId) {
    var ccode    = $('#selcoursee').val();
    var username = $('#selote').val();
    var uactivity= $('#selactivitye').val();
    var actvalue = $('#txtactvaluee').val();
    if (!ccode || !username || !uactivity || !actvalue) { alert('All fields are mandatory'); return; }

    $.ajax({
        type: 'PUT',
        url: '/activity/' + activityId,
        data: { ccode, username, uactivity, actvalue },
        success: function(data) {
            if (data.status === 'ok') {
                $('#spanres').text('Updated successfully');
                setTimeout(function(){ window.location = "{{ route('home') }}"; }, 1500);
            } else {
                $('#spanres').text('Update failed, please try again.');
            }
        }
    });
}
</script>
@endpush
