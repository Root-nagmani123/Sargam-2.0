@extends('admin.layouts.master')

@section('title', 'Faculty')

@section('content')
<div class="container-fluid">

    <x-breadcrum title="Faculty" />
    <x-session_message />

    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>Faculty</h4>
                        </div>
                        <div class="col-6">
                            <div class="float-end gap-2">
                                <a href="{{route('faculty.create')}}" class="btn btn-primary">+ Add Faculty</a>
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
                                    <th>Faculty Type</th>
                                    <th>Full Name</th>
                                    <th>Mobile Number</th>
                                    <th>Current Sector</th>
                                    <th>Area of Expertise</th>
                                    <th>Created At</th>
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
                                                {{-- <form action="{{ route('programme.destroy', $courseMaster->id) }}"
                                                    method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                                </form> --}}
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