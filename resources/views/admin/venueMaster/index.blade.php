@extends('admin.layouts.master')

@section('title', 'Venue-Master - Sargam | Lal Bahadur')

@section('content')
<div class="container-fluid">
    <x-breadcrum title="Venue Master" />
    <x-session_message />

    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card">
            <div class="card-body">
            <div class="table-responsive" style="overflow-x: auto;">

                    <div class="row">
                        <div class="col-6">
                            <h4>Venue Master</h4>
                        </div>
                        <div class="col-6">
                            <div class="float-end gap-2">
                                <a href="{{ route('Venue-Master.create') }}" class="btn btn-primary">+Add New Venue</a>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div id="zero_config_wrapper" class="dataTables_wrapper">

                    <table id="zero_config"
        class="table table-striped table-bordered text-nowrap align-middle dataTable"
        aria-describedby="zero_config_info" style="white-space: nowrap;">
                            <thead>
                                <!-- start row -->
                                <tr>
                                    <th class="sorting sorting_asc" tabindex="0" aria-controls="zero_config" rowspan="1"
                                        colspan="1" aria-sort="ascending"
                                        aria-label="Name: activate to sort column descending" style="width: 224.625px;">
                                        S.No.</th>
                                    <th class="sorting" tabindex="0" aria-controls="zero_config" rowspan="1" colspan="1"
                                        aria-label="Position: activate to sort column ascending"
                                        style="width: 225.875px;">Venue Name</th>
                                    <th class="sorting" tabindex="0" aria-controls="zero_config" rowspan="1" colspan="1"
                                        aria-label="Position: activate to sort column ascending"
                                        style="width: 225.875px;">Short Name</th>
                                    <th class="sorting" tabindex="0" aria-controls="zero_config" rowspan="1" colspan="1"
                                        aria-label="Position: activate to sort column ascending"
                                        style="width: 225.875px;">Description</th>
                                    <th class="sorting" tabindex="0" aria-controls="zero_config" rowspan="1" colspan="1"
                                        aria-label="Salary: activate to sort column ascending"
                                        style="width: 85.8906px;">Action</th>
                                    <th class="sorting" tabindex="0" aria-controls="zero_config" rowspan="1" colspan="1"
                                        aria-label="Salary: activate to sort column ascending"
                                        style="width: 85.8906px;">Status</th>

                                </tr>
                                <!-- end row -->
                            </thead>
                            <tbody>
                                @foreach($venues as $key =>$venue)
                                <tr class="{{ $loop->odd ? 'odd' : 'even' }}">
                                    <td>{{ $key + 1 }}</td>
                                    <td class="sorting_1">
                                        <div class="d-flex align-items-center gap-6">
                                            <h6 class="mb-0">{{ $venue->venue_name  }}</h6>
                                        </div>
                                    </td>
                                    <td class="sorting_1">
                                        <div class="d-flex align-items-center gap-6">
                                            <h6 class="mb-0">{{ $venue->venue_short_name   }}</h6>
                                        </div>
                                    </td>
                                    <td class="sorting_1">
                                        <div class="d-flex align-items-center gap-6">
                                            <h6 class="mb-0">{{ $venue->description   }}</h6>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-start align-items-start gap-2">
                                            <a href="{{ route('Venue-Master.edit', $venue->venue_id) }}"
                                                class="btn btn-success text-white btn-sm">
                                                Edit
                                            </a>
                                            <form action="{{ route('Venue-Master.destroy', $venue->venue_id) }}"
                                                method="POST" class="m-0">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger text-white btn-sm"
                                                    onclick="return confirm('Are you sure you want to delete?')">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                data-table="venue_master" data-column="active_inactive"
                                                data-id="{{ $venue->venue_id }}"  data-id_column="venue_id"
                                                {{ $venue->active_inactive == 1 ? 'checked' : '' }}>
                                        </div>
                                    </td>

                                </tr>
                                @endforeach
                            </tbody>


                    </div>
                </div>
            </div>
        </div>
        <!-- end Zero Configuration -->
    </div>
</div>

<script>
window.statusToggleUrl = "{{ route('admin.toggleStatus') }}";
</script>
@endsection