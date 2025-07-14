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
                                    <a href="{{ route('memo.notice.management.conversation_student', $memo->memo_notice_id) }}" class="btn btn-primary btn-sm">View Conversation</a>

                                     <a href="javascript:void(0)" class="text-primary btn btn-sm view-conversation" data-bs-toggle="offcanvas"
                                        data-bs-target="#chatOffcanvas"  data-type="student" data-id="{{ $memo->memo_notice_id }}" 
       data-topic="{{ $memo->topic_name }}"><i
                                            class="material-icons md-18">crisis_alert</i></a>
                                   
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
  
</div>
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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>

$(document).ready(function () {
    $('.view-conversation').on('click', function () {
        let memoId = $(this).data('id');
        let topic = $(this).data('topic');
        let type = $(this).data('type');
        $('#userType').val(type);

        $('#conversationTopic').text(topic);
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