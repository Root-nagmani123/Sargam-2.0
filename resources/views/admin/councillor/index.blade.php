@extends('admin.layouts.master')

@section('title', 'Councillor Group - Sargam | Lal Bahadur Shastri National Academy of Administration')

@section('content')
    <div class="container-fluid">

        <x-breadcrum title="Councillor Group" />
        <x-session_message />

        <div class="datatables">
            <div class="card" style="border-left: 4px solid #004a93;">
                <div class="card-body">
                    <div class="table-responsive">
                        <div class="row mb-3">
                            <div class="col-6">
                                <h4>Councillor Group</h4>
                            </div>
                            <div class="col-6">
                                <div class="float-end gap-2">
                                    <a href="{{ route('councillor.create') }}" class="btn btn-primary">Add New</a>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <table class="table table-striped table-bordered" id="councillor_group_datatable">
                            <thead>
                                <tr>
                                    <th>S.No</th>
                                    <th>Course Name</th>
                                    <th>Counsellor Code </th>
                                    <th>Faculty name</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                               
                                    <tr>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td>
                                            <a href="{{ route('councillor.edit') }}"
                                                class="btn btn-sm btn-primary">Edit</a>
                                        </td>
                                    </tr>
                                
                            </tbody>
                        </table>
                    </>
                </div>
            </div>
        </div>
    </div>
@endsection


@extends('admin.layouts.master')

@section('title', 'Councillor Group - Sargam | Lal Bahadur Shastri National Academy of Administration')

@section('content')
    <div class="container-fluid">

        <x-breadcrum title="Councillor Group" />
        <x-session_message />

        <div class="datatables">
            <div class="card" style="border-left: 4px solid #004a93;">
                <div class="card-body">
                    <div class="table-responsive">
                        <div class="row mb-3">
                            <div class="col-6">
                                <h4>Councillor Group</h4>
                            </div>
                            <div class="col-6">
                                <div class="float-end gap-2">
                                    <a href="{{ route('councillor.create') }}" class="btn btn-primary">Add New</a>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <table class="table table-striped table-bordered" id="councillor_group_datatable">
                            <thead>
                                <tr>
                                    <th>S.No</th>
                                    <th>Group Name</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                               
                                    <tr>
                                        <td></td>
                                        <td></td>
                                        <td>
                                            <a href="{{ route('councillor.edit') }}"
                                                class="btn btn-sm btn-primary">Edit</a>
                                        </td>
                                    </tr>
                                
                            </tbody>
                        </table>
                    </>
                </div>
            </div>
        </div>
    </div>
@endsection

