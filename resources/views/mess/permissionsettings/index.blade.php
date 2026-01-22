@extends('admin.layouts.master')
@section('title', 'Mess Permission Settings')
@section('setup_content')
<div class="container-fluid">
    <h4>Mess Permission Settings</h4>
    <a href="{{ route('mess.permissionsettings.create') }}" class="btn btn-primary mb-3">Add Permission Setting</a>
    <table class="table table-bordered">
        <thead><tr><th>Name</th><th>Description</th></tr></thead>
        <tbody>
        @foreach($permissionsettings as $setting)
            <tr>
                <td>{{ $setting->name }}</td>
                <td>{{ $setting->description }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
