@extends('admin.layouts.master')

@section('title', 'faculty - Sargam | Lal Bahadur')

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


    <div class="widget-content searchable-container list">
        <div class="card card-body">
            <div class="row">
                
                <div
                    class="col-md-8 col-xl-9 text-end d-flex justify-content-md-end justify-content-center mt-3 mt-md-0 gap-3">
                    <a href="{{route('faculty.create')}}" id="btn-add-contact" class="btn btn-primary d-flex align-items-center">Add Faculty
                    </a>
                    <a href="javascript:void(0)" id="btn-add-contact" class="btn btn-success d-flex align-items-center" data-bs-toggle="modal"
                    data-bs-target="#vertical-center-scroll-modal">Bulk Upload
                    </a>
                </div>
            </div>
        </div>
        <div class="datatables">
            <!-- start Zero Configuration -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <!-- Vertically centered modal -->
                        <div class="modal fade" id="vertical-center-scroll-modal" tabindex="-1"
                            aria-labelledby="vertical-center-modal" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                                <div class="modal-content">
                                    <div class="modal-header d-flex align-items-center">
                                        <h4 class="modal-title" id="myLargeModalLabel">
                                            Bulk Upload for Faculty
                                        </h4>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                            aria-label="Close"></button>
                                    </div>
                                    <hr>
                                    <div class="modal-body">
                                        <form action="" method="POST">
                                            <label for="" class="form-label">Upload CSV</label>
                                            <input type="file" name="file" id="file" class="form-control">
                                        </form>
                                    </div>
                                    <hr>
                                    <div class="modal-footer">
                                        <button type="submit"
                                            class="btn bg-success-subtle text-success  waves-effect text-start">
                                            Submit
                                        </button>
                                        <button type="button"
                                            class="btn bg-danger-subtle text-danger  waves-effect text-start"
                                            data-bs-dismiss="modal">
                                            Close
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div id="zero_config_wrapper" class="dataTables_wrapper">
                            <div class="dataTables_length" id="zero_config_length"><label>Show <select
                                        name="zero_config_length" aria-controls="zero_config" class="">
                                        <option value="10">10</option>
                                        <option value="25">25</option>
                                        <option value="50">50</option>
                                        <option value="100">100</option>
                                    </select> entries</label></div>
                            <div id="zero_config_filter" class="dataTables_filter"><label>Search:<input type="search"
                                        class="" placeholder="" aria-controls="zero_config"></label></div>
                            <table id="zero_config"
                                class="table table-striped table-bordered text-nowrap align-middle dataTable"
                                aria-describedby="zero_config_info">
                                <thead>
                                    <!-- start row -->
                                    <tr>
                                        <th class="col">S.No.</th>
                                        <th class="col">Faculty Name</th>
                                        <th class="col">Faculty Code</th>
                                        <th class="col">Faculty Type</th>
                                        <th class="col">Mobile</th>
                                        <th class="col">Email</th>
                                        <th class="col">Biodata</th>
                                        <th class="col">Action</th>
                                        <th class="col">Status</th>
                                    </tr>
                                    <!-- end row -->
                                </thead>
                                <tbody>
                                    <tr class="odd">
                                        <td>1</td>
                                        <td class="sorting_1">
                                            <div class="d-flex align-items-center gap-6">
                                                <img src="../assets/images/profile/user-5.jpg" width="45"
                                                    class="rounded-circle">
                                                <h6 class="mb-0"> Airi Satou</h6>
                                            </div>
                                        </td>
                                        <td>Accountant</td>
                                        <td>Tokyo</td>
                                        <td>2008/11/28</td>
                                        <td>33</td>
                                        <td>2008/11/28</td>
                                        <td>
                                            <div class="d-flex justify-content-start align-items-start gap-2">
                                                <a href="#" class="btn btn-success text-white btn-sm">
                                                    Edit
                                                </a>
                                                <form action="#" method="POST" class="m-0">
                                                    <input type="hidden" name="_token"
                                                        value="7m53OwU7KaFp1PPyJcyUuVMXW7xvrGr12yL6QycA"> <input
                                                        type="hidden" name="_method" value="DELETE"> <button
                                                        type="submit" class="btn btn-danger text-white btn-sm"
                                                        onclick="return confirm('Are you sure you want to delete?')">
                                                        Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input status-toggle" type="checkbox"
                                                    role="switch" data-table="news" data-column="status" data-id="21"
                                                    checked="">
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <div class="dataTables_info" id="zero_config_info" role="status" aria-live="polite">Showing
                                1 to
                                10 of 57 entries</div>
                            <div class="dataTables_paginate paging_simple_numbers" id="zero_config_paginate"><a
                                    class="paginate_button previous disabled" aria-controls="zero_config"
                                    aria-disabled="true" role="link" data-dt-idx="previous" tabindex="-1"
                                    id="zero_config_previous">Previous</a><span><a class="paginate_button current"
                                        aria-controls="zero_config" role="link" aria-current="page" data-dt-idx="0"
                                        tabindex="0">1</a><a class="paginate_button " aria-controls="zero_config"
                                        role="link" data-dt-idx="1" tabindex="0">2</a><a class="paginate_button "
                                        aria-controls="zero_config" role="link" data-dt-idx="2" tabindex="0">3</a><a
                                        class="paginate_button " aria-controls="zero_config" role="link" data-dt-idx="3"
                                        tabindex="0">4</a><a class="paginate_button " aria-controls="zero_config"
                                        role="link" data-dt-idx="4" tabindex="0">5</a><a class="paginate_button "
                                        aria-controls="zero_config" role="link" data-dt-idx="5"
                                        tabindex="0">6</a></span><a class="paginate_button next"
                                    aria-controls="zero_config" role="link" data-dt-idx="next" tabindex="0"
                                    id="zero_config_next">Next</a></div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- end Zero Configuration -->
        </div>
    </div>

</div>


@endsection