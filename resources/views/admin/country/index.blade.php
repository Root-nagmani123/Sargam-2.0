@extends('admin.layouts.master')

@section('title', 'Country List')

@section('content')
<div class="container-fluid">

    <x-breadcrum title="Country List" />
    <x-session_message />

    <div class="card card-body py-3">
        <div class="row align-items-center">
            <div class="col-12">
                <h4 class="mb-4">Country List</h4>
                <a href="{{ route('master.country.create') }}" class="btn btn-primary">+ Add Country</a>
            </div>
        </div>
    </div>
    

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Country Name</th>
                            <th>Actions</th>
                            <!-- <th>Status</th> -->
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($countries as $index => $country)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $country->country_name }}</td>
                                <td>
                                    <a href="{{ route('master.country.edit', $country->pk) }}" class="btn btn-success btn-sm">Edit</a>
                                    <form action="{{ route('master.country.delete', $country->pk) }}" method="POST" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this?')">Delete</button>
                                    </form>
                                </td>
                                <!-- <td>
                                    <input type="checkbox" class="status-toggle" data-id="{{ $country->pk }}" {{ $country->status ? 'checked' : '' }}>
                                </td> -->
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <!-- Pagination (if applicable) -->
                <div class="d-flex justify-content-end">
                {{ $countries->links('pagination::bootstrap-5') }} <!-- If using pagination -->
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
