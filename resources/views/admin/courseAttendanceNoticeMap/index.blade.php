@extends('admin.layouts.master')

@section('title', 'Memo Management - Sargam | Lal Bahadur Shastri National Academy of Administration')

@section('content')
<div class="container-fluid">

    <x-breadcrum title="Memo Management" />
    <x-session_message />

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
                    <div class="dataTables_wrapper" id="alt_pagination_wrapper">
                       <div class="table-responsive">
                         <table 
                            class="table table-striped table-bordered text-nowrap" id="alt_pagination"
                            data-toggle="data-table">
                            <thead>
                                <!-- start row -->
                                <tr>
                                    <th class="col">S.No.</th>
                                    <th class="col">Participant Name</th>
                                    <th class="col">Memo Type</th>
                                    <th class="col">Session Date</th>
                                    <th class="col">Topic</th>
                                    <th class="col">Conversation Response</th>
                                    <th class="col">Status</th>
                                </tr>
                                <!-- end row -->
                            </thead>
                            <tbody>
                                @if (count($memos) == 0)
                                    <tr>
                                        <td colspan="9" class="text-center">No records found</td>
                                    </tr>
                                @else
                                @foreach ($memos as $memo)
                                       
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $memo->student_name }}</td>
                                    <td>
                                        @if ($memo->notice_memo == '1')
                                            <span class="badge bg-primary-subtle text-primary">Notice</span>
                                        @elseif ($memo->notice_memo == '2')
                                            <span class="badge bg-secondary-subtle text-secondary">Memo</span>
                                        @else
                                            <span class="badge bg-info-subtle text-info">Other</span>
                                        @endif
                                    </td>
                                    <td>{{ $memo->date_}}</td>
                                    <td>{{ $memo->topic_name }}</td>
                                    <td> 
                                        <a href="{{route('memo.notice.management.conversation')}}"
                                            class="btn btn-primary btn-sm">View Conversation</a>
                                    </td>
                                    <td>
                                        @if ($memo->status == 1)
                                            <span class="badge bg-success-subtle text-success">Open</span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger">Close</span>
                                        @endif
                                        </td>
                                </tr>
                                @endforeach
                                @endif
                            </tbody>
                        </table>
                       </div>

                    </div>
            </div>
        </div>
        <!-- end Zero Configuration -->
</div>
@endsection