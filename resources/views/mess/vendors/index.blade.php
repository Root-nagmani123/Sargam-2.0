@extends('admin.layouts.master')
@section('title', 'Mess Vendors')
@section('setup_content')
<div class="container-fluid">
    <h4>Mess Vendors</h4>
    <a href="{{ route('mess.vendors.create') }}" class="btn btn-primary mb-3">Add Vendor</a>
    <table class="table table-bordered">
        <thead><tr><th>Name</th><th>Contact Person</th><th>Phone</th><th>Email</th><th>Address</th></tr></thead>
        <tbody>
        @foreach($vendors as $vendor)
            <tr>
                <td>{{ $vendor->name }}</td>
                <td>{{ $vendor->contact_person }}</td>
                <td>{{ $vendor->phone }}</td>
                <td>{{ $vendor->email }}</td>
                <td>{{ $vendor->address }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
