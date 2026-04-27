@extends('admin.layouts.master')
@section('title', 'Activity Status - All')
@section('setup_content')
<div class="container-fluid px-3">
    <x-breadcrum title="FC Activities - All Status"></x-breadcrum>
    <h4 class="fw-bold mb-3" style="color:#1a3c6e;">All Activity Status</h4>
    <div class="card border-0 shadow-sm"><div class="table-responsive">
        <table class="table table-sm table-hover mb-0 js-fc-datatable" data-export-title="FC Activities - All Status">
            <thead><tr><th>#</th><th>Name</th><th>OT Code</th><th>Mobile</th><th>Service</th><th>Admin</th><th>Security</th><th>IT</th><th>Training</th><th>Souvenir</th><th>Medical</th></tr></thead>
            <tbody>
            @foreach($rows as $i => $row)
                <tr>
                    <td>{{ $i + 1 }}</td><td>{{ $row['otname'] }}</td><td>{{ $row['otcode'] }}</td><td>{{ $row['mobileno'] }}</td><td>{{ $row['service'] }}</td>
                    @foreach(['joined','idcard','biometric','trgind','souvenir','height'] as $code)
                        <td>{{ empty($row['activities'][$code]) ? '' : 'Done' }}</td>
                    @endforeach
                </tr>
            @endforeach
            </tbody>
        </table>
    </div></div>
</div>
@endsection

@include('admin.fc-activities.partials.datatable-tools')
