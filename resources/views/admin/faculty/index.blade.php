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
                                    
                                    <th>Action</th>
                                </tr>
                                <!-- end row -->
                            </thead>
                            <tbody>
                                @if (!empty($faculties) && count($faculties) > 0)
                                    @foreach ($faculties as $faculty)
                                        <tr class="odd">
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $faculty->faculty_type == 1 ? 'Govt' : 'Private' }}</td>
                                            <td>{{ $faculty->full_name ?? 'N/A' }}</td>
                                            <td>{{ $faculty->mobile_no }}</td>
                                            <td>{{ $faculty->faculty_sector }}</td>
                                           
                                            <td>
                                                <a href="{{ route('programme.edit', ['id' => encrypt($faculty->pk)]) }}"
                                                    class="btn btn-primary btn-sm">Edit</a>
                                                
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