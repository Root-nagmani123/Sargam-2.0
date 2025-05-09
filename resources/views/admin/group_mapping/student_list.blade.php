<table class="table table-bordered table-striped">
    <caption>Student List</caption>
    <thead>
        <tr>
            <th colspan="4">Group: {{ $groupName ?? 'Group Name' }}</th>
        </tr>
    </thead>
    <thead>
        <tr>
            <th>Student ID</th>
            <th>Student Email</th>
            <th>Contact No</th>
            <th>Name</th>
        </tr>
    </thead>
    <tbody>
        @foreach($students as $student)
        <tr>
            <td>{{ $student['pk'] }}</td>
            <td>{{ $student['email'] }}</td>
            <td>{{ $student['contact_no'] }}</td>
            <td>{{ $student['display_name'] }}</td>
        </tr>
        @endforeach
    </tbody>
</table>