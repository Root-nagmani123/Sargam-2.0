@extends('admin.layouts.master')

@section('title', 'Course Master - Sargam | Lal Bahadur')

@section('content')
<div class="container-fluid">
    <x-breadcrum title="Programme" />
    <x-session_message />

    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>Course Master</h4>
                        </div>
                        <div class="col-6">
                            <div class="float-end gap-2">
                                <a href="{{route('programme.create')}}" class="btn btn-primary">+ Add Course</a>
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
                                    <th>S.No.</th>
                                    <th>Course Name</th>
                                    <th>Short Name</th>
                                    <th>Course Year</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Action</th>
                                </tr>
                                <!-- end row -->
                            </thead>
                            <tbody>
                                @if (!empty($courseMasterList) && count($courseMasterList) > 0)
                                    @foreach ($courseMasterList as $courseMaster)
                                        <tr class="odd">
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $courseMaster->course_name ?? 'N/A' }}</td>
                                            <td>{{ $courseMaster->couse_short_name ?? 'N/A' }}</td>
                                            <td>{{ $courseMaster->course_year }}</td>
                                            <td>{{ $courseMaster->start_year }}</td>
                                            <td>{{ $courseMaster->end_date }}</td>
                                            <td>
                                                <a href="{{ route('programme.edit', ['id' => encrypt($courseMaster->pk)]) }}"
                                                    class="btn btn-primary btn-sm">Edit</a>
                                                <div class="form-check form-switch d-inline-block">
                                                    <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                        data-table="course_master" data-column="active_inactive" data-id="{{ $courseMaster->pk }}" {{ $courseMaster->active_inactive == 1 ? 'checked' : '' }}>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    
                                @endif
                               
                            </tbody>
                        </table>
                        
                    </div>
                </div>
            </div>
        </div>
        <!-- end Zero Configuration -->
    </div>
</div>


@endsection