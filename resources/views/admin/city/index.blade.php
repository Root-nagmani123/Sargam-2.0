@extends('admin.layouts.master')

@section('title', 'City - Sargam | Lal Bahadur')

@section('content')
<div class="container-fluid">
    <div class="card card-body py-3">
        <div class="row align-items-center">
            <div class="col-12">
                <div class="d-sm-flex align-items-center justify-space-between">
                    <h4 class="mb-4 mb-sm-0 card-title">City</h4>
                    <nav aria-label="breadcrumb" class="ms-auto">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item d-flex align-items-center">
                                <a class="text-muted text-decoration-none d-flex" href="../main/index.html">
                                    <iconify-icon icon="solar:home-2-line-duotone" class="fs-6"></iconify-icon>
                                </a>
                            </li>
                            <li class="breadcrumb-item" aria-current="page">
                                <span class="badge fw-medium fs-2 bg-primary-subtle text-primary">
                                    City
                                </span>
                            </li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    @if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif 

@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>City</h4>
                        </div>
                        <div class="col-6">
                            <div class="float-end gap-2">
                                <a href="{{ route('city.create') }}" class="btn btn-primary">+ Add City</a>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <table id="zero_config"
                            class="table table-striped table-bordered text-nowrap align-middle dataTable"
                            aria-describedby="zero_config_info">
                          
                            <thead>
                                <!-- start row -->
                                <tr>
                                <th>S.No</th>
                                <th>City Name</th>
                                <th>District</th>
                                <th>State</th>
                                <th>Action</th>
                            </tr>
                                <!-- end row -->
                            </thead>
                            <tbody>
                            @foreach($cities as $key => $city)
                <tr>
                    <td>{{ $key+1 }}</td>
                    <td>{{ $city->city_name }}</td>
                    <td>{{ $city->district_name }}</td>
                    <td>{{ $city->state_name }}</td>
                    <td>
                        <a href="{{ route('city.edit', $city->pk) }}" class="btn btn-success btn-sm">Edit</a>
                        <form action="{{ route('city.delete', $city->pk) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" onclick="return confirm('Delete this city?')" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>
            @endforeach
                            </tbody>
                        </table>
                       
                </div>
            </div>
        </div>
        <!-- end Zero Configuration -->
    </div>
</div>


@endsection