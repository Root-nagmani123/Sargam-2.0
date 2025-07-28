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
                                <th class="col">Conclusion Type</th>
                                <th class="col">Conclusion Remark</th>
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
                                    @if($memo->type_notice_memo == 'Notice' || $memo->type_notice_memo == 'Memo')
                                    @if( $memo->notice_id != null)
                                    <a href="{{ route('memo.notice.management.conversation', ['id' => $memo->notice_id, 'type' => 'notice']) }}"
                                        class="btn btn-primary btn-sm">Notice Conversation</a>
                                    @else
                                    <span class="text-muted">No Conversation</span>
                                    @endif
                                    @endif

                                    <a href="javascript:void(0)" class="text-primary btn btn-sm view-conversation"
                                        data-bs-toggle="offcanvas" data-bs-target="#chatOffcanvas" data-type="admin"
                                        data-id="{{ $memo->notice_id }}" data-topic="{{ $memo->topic_name }}"><i
                                            class="material-icons md-18"
                                            style="vertical-align: middle; line-height: 1;color: #af2910;">mark_unread_chat_alt</i></a>
                                    @if($memo->type_notice_memo == 'Notice')

                                    <a href="script:void(0)" class="btn btn-secondary btn-sm">Memo Conversation</a>


                                    @elseif($memo->type_notice_memo == 'Memo')
                                    @if($memo->status == 1 || $memo->communication_status == 1 ||
                                    $memo->communication_status == 2)

                                    <a href="{{ route('memo.notice.management.conversation', ['id' => $memo->memo_id, 'type' => 'memo']) }}"
                                        class="btn btn-primary btn-sm">Memo Conversation</a>

                                    @endif
                                    @endif

                                </td>
                                <td>
                                     @if($memo->type_notice_memo == 'Notice')
                                    @if($memo->status == 1)
                                    <button href="" class="btn-outline-secondary btn btn-sm" readonly>Generate
                                        Memo</button>
                                    @elseif($memo->status == 2)
                                    <a href="javascript:void(0)" class="btn btn-danger btn-sm generate-memo-btn"
                                        data-id="{{ $memo->memo_notice_id }}" data-bs-toggle="modal"
                                        data-bs-target="#memo_generate">
                                        Generate Memo
                                    </a>

                                    @endif
                                    @elseif($memo->type_notice_memo == 'Memo')
                                   <button href="" class="btn-outline-secondary btn btn-sm" readonly>Memo Generated
                                        </button>

                                    @endif
                                </td>
                                <td>
                                    @if($memo->type_notice_memo == 'Memo')
                                    @if ($memo->communication_status == 2)
                                    {{ $memo->discussion_name }}
                                    @endif
                                    @endif
                                </td>
                                <td>
                                    @if($memo->type_notice_memo == 'Memo')

                                    @if( $memo->communication_status == 2)
                                    {{ $memo->conclusion_remark }}
                                    @endif
                                    @endif
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
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="memo_generateLabel">Generate Memo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('memo.notice.management.store_memo_status') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label for="course_master_name" class="form-label">Course</label>

                                <input type="text" id="course_master_name" class="form-control"
                                    name="course_master_name" readonly>
                                <input type="hidden" id="course_master_pk" name="course_master_pk">
                                <input type="hidden" id="course_attendance_notice_map_pk"
                                    name="course_attendance_notice_map_pk">
                                <input type="hidden" id="memo_count" name="memo_count">
                                <input type="hidden" id="student_pk" name="student_pk">
                                @error('course_master_name')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-6 mb-3">
                                <label for="date_memo_notice" class="form-label">Date</label>
                                <input type="date" class="form-control" id="date_memo_notice" name="date_memo_notice"
                                    required readonly>
                                @error('date_memo_notice')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-6 mb-3">
                                <label for="subject_master_id" class="form-label">Subject <span
                                        class="text-danger">*</span></label>

                                <input type="text" id="subject_master_id" class="form-control" name="subject_master_id"
                                    readonly>

                                @error('subject_master_id')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-6 mb-3">
                                <label for="topic_id" class="form-label">Topic</label>

                                <input type="text" id="topic_id" class="form-control" name="topic_id" readonly>

                                @error('topic_id')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>



                            <div class="col-6 mb-3">
                                <label for="session_name" class="form-label">Session</label>
                                <input type="text" id="class_session_master_pk" class="form-control" readonly>
                                @error('session_name')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-6 mb-3">
                                <label for="faculty_name" class="form-label">Faculty Name</label>
                                <input type="text" id="faculty_name" class="form-control" readonly>
                                @error('faculty_name')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-6 mb-3">
                                <label for="student_name" class="form-label">Student Name</label>
                                <input type="text" id="student_name" class="form-control" readonly>
                                @error('student_name')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-6 mb-3">
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
                                <input type="text" id="memo_number" name="memo_number" class="form-control" readonly>
                                @error('memo_number')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>


                            <div class="col-6 mb-3">
                                <label for="venue" class="form-label">Venue</label>
                                <select name="venue" id="venue" class="form-control">
                                    <option value="">Select Venue</option>
                                    @foreach ($venue as $v)
                                    <option value="{{ $v->venue_id }}">{{ $v->venue_name }}</option>
                                    @endforeach
                                </select>
                                @error('venue')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-3 mb-3">
                                <label for="memo_date" class="form-label">Date</label>
                                <input type="date" id="memo_date" class="form-control">
                                @error('memo_date')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-3 mb-3">
                                <label for="meeting_time" class="form-label">Meeting Time</label>
                                <input type="time" id="meeting_time" name="meeting_time" class="form-control">
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
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
                </form>
            </div>
        </div>

    </div>
<!-- Memo generation end -->
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    $('.view-conversation').on('click', function() {
        let memoId = $(this).data('id');
        let topic = $(this).data('topic');
        let type = $(this).data('type');
        $('#userType').val(type);

        $('#conversationTopic').text(topic);
        $('#chatBody').html('<p class="text-muted text-center">Loading conversation...</p>');

        $.ajax({
            url: '/admin/memo-notice-management/get_conversation_model/' + memoId + '/' + type,
            type: 'GET',
            success: function(res) {
                $('#chatBody').html(res);
            },
            error: function() {
                $('#chatBody').html(
                    '<p class="text-danger text-center">Failed to load conversation.</p>'
                );
            }
        });

        // Show offcanvas
        let chatOffcanvas = new bootstrap.Offcanvas(document.getElementById('chatOffcanvas'));
        chatOffcanvas.show();
    });
});
</script>
@push('scripts')
<script>
$(document).ready(function() {
    $('.generate-memo-btn').on('click', function() {
        let memoId = $(this).data('id');

        $.ajax({
            url: "{{ route('memo.notice.management.get_memo_data') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                memo_notice_id: memoId
            },
            success: function(res) {
                // Populate modal fields
                $('#course_master_name').val(res.course_master_name);
                $('#date_memo_notice').val(res.date_);
                $('#student_name').val(res.student_name);
                $('#subject_master_id').val(res.student_name);
                $('#topic_id').val(res.subject_topic);
                $('#venue_id').val(res.venue_id);
                $('#course_attendance_notice_map_pk').val(res
                    .course_attendance_notice_map_pk);
                $('#course_master_pk').val(res.course_master_pk);
                $('#memo_count').val(res.memo_count + 1);

                $('#session_name').val(res.session_name);
                $('#class_session_master_pk').val(res.class_session_master_pk);
                $('#faculty_name').val(res.faculty_name);
                $('#student_pk').val(res.student_pk);
                $('#memo_number').val(res.memo_number);

                // Add more if needed
            },
            error: function() {
                alert('Something went wrong!');
            }
        });
    });
});
</script>
@endpush

@endsection