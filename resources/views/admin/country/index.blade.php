@extends('admin.layouts.master')

@section('title', 'Country List')

@section('content')
<div class="container-fluid">

    <x-breadcrum title="Country List" />
    <x-session_message />

    <div class="card card-body py-3">
        <div class="row">
            <div class="col-8">
                <h4>Country List</h4>
            </div>
            @can('master.country.create')
                <div class="col-4 text-end">
                    <a href="{{ route('master.country.create') }}" class="btn btn-primary">Add Country</a>
                </div>
            @endcan
            
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
                            <th>Status</th>
                            <th>Actions</th>
                            
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($countries as $index => $country)
                            <tr>
                                <td>{{ $index + 1 }}</td> 
                                <td>{{ $country->country_name }}</td>
                               
                               <td>
                                @can('master.country.active_inactive')
                                    <div class="form-check form-switch d-inline-block">
                                        <input class="form-check-input status-toggle" type="checkbox"
                                            role="switch"
                                            data-table="country_master"
                                            data-column="active_inactive"
                                            data-id="{{ $country->pk }}"
                                            {{ $country->active_inactive == 1 ? 'checked' : '' }}>
                                    </div>
                                @endcan
                                        
                                    </td>
                                     <td>
                                        @can('master.country.edit')
                                            <a href="{{ route('master.country.edit', $country->pk) }}" class="btn btn-success btn-sm">Edit</a>
                                        @endcan
                                        @can('master.country.delete')
                                            <form action="{{ route('master.country.delete', $country->pk) }}"
                                            method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn btn-danger btn-sm" onclick="event.preventDefault();
                                                    if(confirm('Are you sure you want to delete this ?')) {
                                                        this.closest('form').submit();
                                                    }"
                                                    {{ $country->active_inactive == 1 ? 'disabled' : '' }}>
                                                    Delete
                                                </button>
                                            </form>
                                        @endcan
                                    
                                </td>
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
