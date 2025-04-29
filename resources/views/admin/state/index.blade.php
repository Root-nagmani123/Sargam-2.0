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
                                        <th class="sorting sorting_asc" tabindex="0" aria-controls="zero_config" rowspan="1"
                                            colspan="1" aria-sort="ascending"
                                            aria-label="Name: activate to sort column descending" style="width: 224.625px;">
                                            S.No.</th>
                                        <th class="sorting" tabindex="0" aria-controls="zero_config" rowspan="1" colspan="1"
                                            aria-label="Position: activate to sort column ascending"
                                            style="width: 225.875px;">State Name</th>
                                        <th class="sorting" tabindex="0" aria-controls="zero_config" rowspan="1" colspan="1"
                                            aria-label="Salary: activate to sort column ascending"
                                            style="width: 85.8906px;">Action</th>
                                        <!-- <th class="sorting" tabindex="0" aria-controls="zero_config" rowspan="1" colspan="1"
                                            aria-label="Salary: activate to sort column ascending"
                                            style="width: 85.8906px;">Status</th> -->
                                    </tr>
                                    <!-- end row -->
                                </thead>
                                <tbody>
                                    @foreach($states as $key => $state)
                                                            <tr class="{{ $loop->odd ? 'odd' : 'even' }}">
                                                                <td>{{ $key + 1 }}</td>
                                                                <td class="sorting_1">
                                                                    <div class="d-flex align-items-center gap-6">
                                                                        <h6 class="mb-0">{{ $state->state_name }}</h6>
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <div class="d-flex justify-content-start align-items-start gap-2">
                                                                        <a href="{{ route('master.state.edit', $state->Pk) }}"
                                                                            class="btn btn-success text-white btn-sm">
                                                                            Edit
                                                                        </a>
                                                                        <form action="{{ route('master.state.delete', $state->Pk) }}" method="POST"
                                                                            class="m-0">
                                                                            @csrf
                                                                            @method('DELETE')
                                                                            <button type="submit" class="btn btn-danger text-white btn-sm"
                                                                                onclick="return confirm('Are you sure you want to delete?')">
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


@endsection