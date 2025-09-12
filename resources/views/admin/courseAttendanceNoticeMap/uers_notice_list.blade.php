@extends('admin.layouts.master')

@section('title', 'Memo Management - Sargam | Lal Bahadur Shastri National Academy of Administration')

@section('content')
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
            </div>

            <hr>
            <div class="dataTables_wrapper" id="alt_pagination_wrapper">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered text-nowrap" id="alt_pagination" data-toggle="data-table">
                        <thead class="table-light">
                            <tr>
                                <th class="col">S.No.</th>
                                <th class="col">Participant</th>
                                <th class="col">Type</th>
                                <th class="col">Date</th>
                                <th class="col">Topic</th>
                                <th class="col">Conversation</th>
                                <th class="col">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if ($memos->isEmpty())
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox me-1"></i> No records found
                                    </td>
                                </tr>
                            @else
                                @php $grouped = $memos->groupBy('type_notice_memo'); @endphp

                                @foreach ($grouped as $type => $group)
                                    @foreach ($group as $memo)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $memo->student_name }}</td>
                                            <td>
                                                @if ($memo->notice_memo == '1')
                                                    <span class="badge rounded-pill bg-primary-subtle text-primary">Notice</span>
                                                @elseif ($memo->notice_memo == '2')
                                                    <span class="badge rounded-pill bg-secondary-subtle text-secondary">Memo</span>
                                                @else
                                                    <span class="badge rounded-pill bg-info-subtle text-info">Other</span>
                                                @endif
                                            </td>
                                            <td class="date">{{ $memo->date_ }}</td>
                                            <td>{{ $memo->topic_name }}</td>
                                            <td>
                                                <div class="d-flex gap-2 flex-nowrap">
                                                    <a href="{{ route('memo.notice.management.conversation_student', ['id' => $memo->notice_id, 'type' => 'notice']) }}"
                                                    class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Notice Conversation">
                                                        <i class="bi bi-chat-dots"></i> Notice
                                                    </a>

                                                    <button type="button"
                                                        class="btn btn-sm btn-outline-secondary view-conversation"
                                                        data-bs-toggle="offcanvas"
                                                        data-bs-target="#chatOffcanvas"
                                                        data-type="student"
                                                        data-id="{{ $memo->notice_id }}"
                                                        data-topic="{{ $memo->topic_name }}"
                                                        data-bs-toggle="tooltip"
                                                        title="Quick View">
                                                        <i class="bi bi-eye"></i>
                                                    </button>

                                                    @if($memo->type_notice_memo == 'Memo')
                                                        <a href="{{ route('memo.notice.management.conversation_student', ['id' => $memo->memo_id, 'type' => 'memo']) }}"
                                                        class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Memo Conversation">
                                                            <i class="bi bi-chat-square-text"></i> Memo
                                                        </a>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                @if ($memo->status == 1)
                                                    <span class="badge rounded-pill bg-success-subtle text-success">
                                                        <i class="bi bi-check-circle me-1"></i> Open
                                                    </span>
                                                @else
                                                    <span class="badge rounded-pill bg-danger-subtle text-danger">
                                                        <i class="bi bi-x-circle me-1"></i> Close
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
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
$(document).ready(function() {
    $('.view-conversation').on('click', function() {
        let memoId = $(this).data('id');
        let topic = $(this).data('topic');
        let type = $(this).data('type');
        $('#userType').val(type);

        $('#conversationTopic').text(topic);
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

@endsection