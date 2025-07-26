@extends('admin.layouts.master')
@section('title', 'Preview Import')

@section('content')
    <div class="container mt-4">
        <h4>Preview Records</h4>

        <form action="{{ route('admin.registration.import.confirm') }}" method="POST">
            @csrf
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Contact No</th>
                        <th>Display Name</th>
                        <th>Schema ID</th>
                        <th>First Name</th>
                        <th>Middle Name</th>
                        <th>Last Name</th>
                        <th>Rank</th>
                        <th>Exam Year</th>
                        <th>Service Master PK</th>
                        <th>Web Auth</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td>{{ $row['email'] }}</td>
                            <td>{{ $row['contact_no'] }}</td>
                            <td>{{ $row['display_name'] }}</td>
                            <td>{{ $row['schema_id'] }}</td>
                            <td>{{ $row['first_name'] }}</td>
                            <td>{{ $row['middle_name'] }}</td>
                            <td>{{ $row['last_name'] }}</td>
                            <td>{{ $row['rank'] }}</td>
                            <td>{{ $row['exam_year'] }}</td>
                            <td>{{ $row['service_master_pk'] }}</td>
                            <td>{{ $row['web_auth'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <button type="submit" class="btn btn-success">Confirm Import</button>
            <a href="{{ route('admin.registration.import.form') }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
@endsection


{{-- @extends('admin.layouts.master')
@section('title', 'Preview & Edit Import')

@section('content')
<div class="container mt-4">
    <h4>Preview & Edit Records Before Import</h4>

    <form action="{{ route('admin.registration.import.confirm') }}" method="POST">
        @csrf
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Email</th>
                    <th>Contact No</th>
                    <th>First Name</th>
                    <th>Middle Name</th>
                    <th>Last Name</th>
                    <th>Rank</th>
                    <th>Web Auth</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $index => $row)
                <tr>
                    <td><input type="text" name="data[{{ $index }}][email]" class="form-control" value="{{ $row['email'] }}" required></td>
                    <td><input type="text" name="data[{{ $index }}][contact_no]" class="form-control" value="{{ $row['contact_no'] }}"></td>
                    <td><input type="text" name="data[{{ $index }}][first_name]" class="form-control" value="{{ $row['first_name'] }}"></td>
                    <td><input type="text" name="data[{{ $index }}][middle_name]" class="form-control" value="{{ $row['middle_name'] }}"></td>
                    <td><input type="text" name="data[{{ $index }}][last_name]" class="form-control" value="{{ $row['last_name'] }}"></td>
                    <td><input type="text" name="data[{{ $index }}][rank]" class="form-control" value="{{ $row['rank'] }}"></td>
                    <td><input type="text" name="data[{{ $index }}][web_auth]" class="form-control" value="{{ $row['web_auth'] }}"></td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <button type="submit" class="btn btn-success">Confirm & Import</button>
        <a href="{{ route('admin.registration.import.form') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection --}}
