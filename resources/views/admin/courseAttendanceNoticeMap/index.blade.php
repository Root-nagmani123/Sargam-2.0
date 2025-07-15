@extends('admin.layouts.master')

@section('title', 'Memo Management - Sargam | Lal Bahadur Shastri National Academy of Administration')

@section('content')
<style>
.chat-body {
    height: 400px;
    overflow-y: auto;
    background-color: #f8f9fa;
    padding: 1rem;
    border-radius: 0.5rem;
}

.chat-message {
    margin-bottom: 0.75rem;
}

.chat-message.user {
    text-align: right;
}

.chat-message .message {
    display: inline-block;
    padding: 0.5rem 0.75rem;
    border-radius: 1rem;
    max-width: 75%;
}

.chat-message.bot .message {
    background-color: #e9ecef;
}

.chat-message.user .message {
    background-color: #0d6efd;
    color: white;
}
</style>
<div class="container-fluid">

    <x-breadcrum title="Notice /Memo Management" />
    <x-session_message />

    
    <!-- start Zero Configuration -->
    <div class="card" style="border-left:4px solid #004a93;">
        <div class="card-body">
            <div class="row">
                <div class="col-6">
                    <h4 class="card-title">Notice /Memo Management</h4>
                </div>
                <div class="col-6">
                    <div class="float-end gap-2">
                        <a href="{{ route('memo.notice.management.create') }}" class="btn btn-primary">+ Add
                            Notice</a>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-4">
                    <div class="mb-3">
                        <label for="program_name" class="form-label">Program Name</label>
                        <select class="form-select" id="program_name" name="program_name">
                            <option value="">Select Program</option>
                            <!-- Options will be populated dynamically -->
                        </select>
                    </div>
                </div>
                <div class="col-4">
                    <div class="mb-3">
                        <label for="type" class="form-label">Type (Notice / Memo)</label>
                        <select class="form-select" id="type" name="type">
                            <option value="">Select type</option>
                            <option value="1">Notice</option>
                            <option value="0">Memo</option>
                        </select>
                    </div>
                </div>
                <div class="col-4">
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
                    <table class="table table-striped table-bordered text-nowrap" id="alt_pagination"
                        data-toggle="data-table">
                        <thead>
                            <!-- start row -->
                            <tr>
                                <th class="col">S.No.</th>
                                <th class="col">Participant Name</th>
                                <th class="col">Type</th>
                                <th class="col">Session Date</th>
                                <th class="col">Topic</th>
                                <th class="col">Conversation</th>
                                <th class="col">Response</th>
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
                                    <a href="{{ route('memo.notice.management.conversation', $memo->memo_notice_id) }}" class="btn btn-primary btn-sm">Notice Conversation</a>

                                    <a href="javascript:void(0)" class="text-primary btn btn-sm view-conversation" data-bs-toggle="offcanvas"
                                        data-bs-target="#chatOffcanvas" data-type="admin" data-id="{{ $memo->memo_notice_id }}" 
       data-topic="{{ $memo->topic_name }}"><i
                                            class="material-icons md-18">crisis_alert</i></a>
                                    @if($memo->status == 1)
                                    <a href="script:void(0)" class="btn btn-secondary btn-sm">Memo Conversation</a>
                                    @elseif($memo->status == 2)
                                    <a href="{{route('admin.courseAttendanceNoticeMap.memo_conversation')}}" class="btn btn-primary btn-sm">Memo Conversation</a>
                                    @else
                                    @endif
                                    
                                </td>
                                <td>
                                   
                                    @if($memo->status == 1)
                                    <button href="" class="btn-secondary btn btn-sm"  readonly>Generate Memo</button>
                                    @elseif($memo->status == 2)
                                      <a href="" class="btn-danger btn btn-sm" data-bs-toggle="modal"
                                        data-bs-target="#memo_generate" >Generate Memo</a>

                                    
                                </td>
                                <!-- Offcanvas Chat Component -->
                                
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
    <!-- memo generation modal -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="chatOffcanvas">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="conversationTopic">Conversation</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <input type="hidden" id="userType" value="">

    <div class="offcanvas-body d-flex flex-column">
        <!-- Chat Body -->
        <div class="chat-body flex-grow-1 mb-3" id="chatBody">
            <p class="text-muted text-center">Loading conversation...</p>
        </div>
    </div>
</div>

    <div class="modal fade" id="memo_generate" tabindex="-1" aria-labelledby="memo_generateLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="memo_generateLabel">Generate Memo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <hr>
                <form action="{{ route('memo.notice.management.store_memo_notice') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label for="course_master_pk" class="form-label">Course</label>
                                <select name="course_master_pk" class="form-control" id="courseSelect" required>
                                    <option value="">Select Course</option>
                                </select>
                                @error('course_master_pk')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-6 mb-3">
                                <label for="date_memo_notice" class="form-label">Date</label>
                                <input type="date" class="form-control" id="date_memo_notice" name="date_memo_notice"
                                    required>
                                @error('date_memo_notice')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-6 mb-3">
                                <label for="subject_master_id" class="form-label">Subject <span
                                        class="text-danger">*</span></label>
                                <select name="subject_master_id" class="form-control" id="subject_master_id">
                                    <option value="">Select Subject</option>
                                </select>
                                @error('subject_master_id')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-6 mb-3">
                                <label for="topic_id" class="form-label">Topic</label>
                                <select name="topic_id" class="form-control" id="topic_id">
                                    <option value="">Select Topic</option>
                                </select>
                                @error('topic_id')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-6 mb-3">
                                <label for="venue_name" class="form-label">Venue <span
                                        class="text-danger">*</span></label>
                                <select name="venue_id" class="form-control" id="venue_id" required>
                                    <option value="">Select Vanue</option>
                                    @foreach ($venue as $v)
                                    <option value="{{ $v->pk }}">{{ $v->venue_name }}</option>
                                    @endforeach
                                </select>
                                
                                @error('venue_id')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-6 mb-3">
                                <label for="session_name" class="form-label">Session</label>
                                <input type="text" id="session_name" class="form-control" readonly>
                                <input type="hidden" id="class_session_master_pk" name="class_session_master_pk">
                                @error('session_name')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-6 mb-3">
                                <label for="faculty_name" class="form-label">Faculty Name</label>
                                <input type="text" id="faculty_name" class="form-control" readonly>
                                <input type="hidden" id="faculty_master_pk" name="faculty_master_pk">
                                @error('faculty_name')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-6 mb-3">
                                <label for="faculty_name" class="form-label">Student Name</label>
                                <input type="text" id="faculty_name" class="form-control" readonly>
                                <input type="hidden" id="faculty_master_pk" name="faculty_master_pk">
                                @error('faculty_name')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-6 mb-3">
                                <label for="memo_type" class="form-label">Memo Type</label>
                                <select name="memo_type_master_pk" id="memo_type_master_pk" class="form-control">
                                    <option value="">Select Memo Type</option>
                                    @foreach ($memo_master as $master)
                                    <option value="{{ $master->pk }}">{{ $master->memo_type_name }}</option>
                                    @endforeach
                                </select>
                                @error('memo_type_master_pk')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-6 mb-3">
                                <label for="memo_number" class="form-label">Memo Number</label>
                                <input type="text" id="memo_number" class="form-control" readonly>
                                <input type="hidden" id="memo_number" name="memo_number">
                                @error('memo_number')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-6 mb-3">
                                <label for="venue" class="form-label">Venue</label>
                                <select name="venue" id="venue" class="form-control">
                                    <option value="">Select Venue</option>
                                    @foreach ($venue as $v)
                                    <option value="{{ $v->pk }}">{{ $v->venue_name }}</option>
                                    @endforeach
                                </select>
                                @error('venue')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-6 mb-3">
                                <label for="memo_date" class="form-label">Date</label>
                                <input type="date" id="memo_date" class="form-control" readonly>
                                <input type="hidden" id="memo_date" name="memo_date">
                                @error('memo_date')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-6 mb-3">
                                <label for="meeting_time" class="form-label">Meeting Time</label>
                                <input type="datetime" id="meeting_time" class="form-control">
                                <input type="hidden" id="meeting_time" name="meeting_time">
                                @error('meeting_time')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-6 mb-3">
                                <label for="meeting_time" class="form-label">Meeting Time</label>
                                <input type="datetime" id="meeting_time" class="form-control">
                                <input type="hidden" id="meeting_time" name="meeting_time">
                                @error('meeting_time')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 mb-3">
                                <label for="textarea" class="form-label">Message (If Any)</label>
                                <textarea class="form-control" id="textarea" name="Remark" rows="3"
                                    placeholder="Enter remarks..."></textarea>
                                @error('Remark')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="modal-footer d-flex text-end gap-3">
                        <div>
                            <button type="submit" class="btn btn-primary">Send</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Memo generation end -->
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>

$(document).ready(function () {
    $('.view-conversation').on('click', function () {
        let memoId = $(this).data('id');
        let topic = $(this).data('topic');
        let type = $(this).data('type');
        $('#userType').val(type);

        $('#conversationTopic').text(topic);
        $('#chatBody').html('<p class="text-muted text-center">Loading conversation...</p>');

        $.ajax({
            url: '/admin/memo-notice-management/get_conversation_model/' + memoId+ '/' + type,
            type: 'GET',
            success: function (res) {
                $('#chatBody').html(res);
            },
            error: function () {
                $('#chatBody').html('<p class="text-danger text-center">Failed to load conversation.</p>');
            }
        });

        // Show offcanvas
        let chatOffcanvas = new bootstrap.Offcanvas(document.getElementById('chatOffcanvas'));
        chatOffcanvas.show();
    });
});
</script>

@endsection