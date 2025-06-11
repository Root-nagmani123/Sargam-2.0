@extends('admin.layouts.master')

@section('title', 'Memo Management - Sargam | Lal Bahadur Shastri National Academy of Administration')

@section('content')
<div class="container-fluid">

    <x-breadcrum title="Memo Management" />
    <x-session_message />

    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card">
            <div class="card-body">
                 <div class="row">
                        <div class="col-6">
                            <h4 class="card-title">Memo Management</h4>
                        </div>
                        <div class="col-6">
                            <div class="float-end gap-2">
                                <a href="{{ route('memo.notice.management.create') }}" class="btn btn-primary">+ Add Notice/Memo</a>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="mb-3">
                                <label for="program_name" class="form-label">Program Name</label>
                                <select class="form-select" id="program_name" name="program_name">
                                    <option value="">Select Program</option>
                                    <!-- Options will be populated dynamically -->
                                </select>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">Select status</option>
                                    <option value="1">Open</option>
                                    <option value="0">Close</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div id="zero_config_wrapper" class="dataTables_wrapper">
                       <div class="table-responsive">
                         <table id="zero_config"
                            class="table table-striped table-bordered text-nowrap align-middle dataTable w-100"
                            aria-describedby="zero_config_info">
                            <thead>
                                <!-- start row -->
                                <tr>
                                    <th class="col"><input type="checkbox" name="select_all" id=""></th>
                                    <th class="col">S.No.</th>
                                    <th class="col">Participant Name</th>
                                    <th class="col">Memo Type</th>
                                    <th class="col">Session Date</th>
                                    <th class="col">Topic</th>
                                    <th class="col">Conversation</th>
                                    <th class="col">Memo Count</th>
                                    <th class="col">Notice Count</th>
                                    <th class="col">Status</th>
                                </tr>
                                <!-- end row -->
                            </thead>
                            <tbody>
                                <tr>
                                    <td><input type="checkbox" name="select_memo" id=""></td>
                                    <td>1</td>
                                    <td>John Doe</td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <span class="badge text-bg-danger">Memo</span><span
                                                class="badge text-bg-primary">Notice</span>
                                        </div>
                                    </td>
                                    <td>2023-10-01</td>
                                    <td>Discussion on project progress</td>
                                    <td> 
                                        <a href="{{route('memo.notice.management.conversation')}}"
                                            class="btn btn-primary btn-sm">View Conversation</a>
                                    </td>
                                    <td>2</td>
                                    <td>3</td>
                                    <td><span class="badge bg-danger-subtle text-danger">Close</span></td>
                                </tr>
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