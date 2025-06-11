@extends('admin.layouts.master')

@section('title', 'City - Sargam | Lal Bahadur')

@section('content')
    <div class="container-fluid">
        <x-breadcrum title="City" />
        <x-session_message />

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
                                    <a href="{{ route('master.city.create') }}" class="btn btn-primary">+ Add City</a>
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
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                                <!-- end row -->
                            </thead>
                            <tbody>
                                @foreach($cities as $key => $city)
                                    <tr> 
                                        <td>{{ $key + 1 }}</td>
                                        <td>{{ $city->city_name }}</td>
                                        <td>{{ $city->state->state_name }}</td>
                                        <td>{{ $city->district?->district_name ?? 'N/A' }}</td>
 <td>
                                        <div class="form-check form-switch d-inline-block">
                                            <input class="form-check-input status-toggle" type="checkbox"
                                                role="switch"
                                                data-table="city_master"
                                                data-column="active_inactive"
                                                data-id="{{ $city->pk }}"
                                                {{ $city->active_inactive == 1 ? 'checked' : '' }}>
                                        </div>
                                    </td>
                                        <td>
                                            <a href="{{ route('master.city.edit', $city->pk) }}"
                                                class="btn btn-success btn-sm">Edit</a>
                                           
                                              <form action="{{ route('master.city.delete', $city->pk) }}"
                                            method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="btn btn-danger btn-sm" onclick="event.preventDefault();
                                                if(confirm('Are you sure you want to delete this ?')) {
                                                    this.closest('form').submit();
                                                }"
                                                {{ $city->active_inactive == 1 ? 'disabled' : '' }}>
                                                Delete
                                            </button>
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