@extends('admin.layouts.master')

@section('title', 'State - Sargam | Lal Bahadur')

@section('content')
<div class="container-fluid">
    <x-breadcrum title="State" />
    <x-session_message />
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>State</h4>
                        </div>
                        <div class="col-6">
                            <div class="float-end gap-2">
                                <a href="{{route('master.state.create')}}" class="btn btn-primary">+ Add State</a>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div id="zero_config_wrapper" class="dataTables_wrapper">

                        <table id="zero_config"
                            class="table table-striped table-bordered text-nowrap align-middle dataTable"
                            aria-describedby="zero_config_info">
                            <thead>
                                <!-- start row -->
                                <tr>
                                    <th class="col">S.No.</th>
                                    <th class="col">State Name</th>
                                    <th class="col">Action</th>
                                    <th class="col">Status</th>
                                </tr>
                                <!-- end row -->
                            </thead> 
                            <tbody>
                                @foreach($states as $key => $state)
                                <tr class="{{ $loop->odd ? 'odd' : 'even' }}">
                                    <td>{{ $key + 1 }}</td>
                                    <td>
                                        {{ $state->state_name }}
                                    </td>
                                     <td>
                                        <div class="form-check form-switch d-inline-block">
                                            <input class="form-check-input status-toggle" type="checkbox"
                                                role="switch"
                                                data-table="state_master"
                                                data-column="active_inactive"
                                                data-id="{{ $state->pk }}"
                                                {{ $state->active_inactive == 1 ? 'checked' : '' }}>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-start align-items-start gap-2">
                                            <a href="{{ route('master.state.edit', $state->pk) }}"
                                                class="btn btn-success text-white btn-sm">
                                                Edit
                                            </a>
                                            
                                             <form action="{{ route('master.state.delete', $state->pk) }}"
                                            method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="btn btn-danger btn-sm" onclick="event.preventDefault();
                                                if(confirm('Are you sure you want to delete this ?')) {
                                                    this.closest('form').submit();
                                                }"
                                                {{ $state->active_inactive == 1 ? 'disabled' : '' }}>
                                                Delete
                                            </button>
                                        </form>
                                        </div>
                                    </td>
                                    <!-- <td>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                    data-table="states" data-column="status" data-id="{{ $state->Pk }}"
                                                    {{ $state->status ? 'checked' : '' }}>
                                            </div>
                                        </td> -->
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
</div>

@endsection