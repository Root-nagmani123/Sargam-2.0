@extends('admin.layouts.master')

@section('title', 'Memo Discipline - Sargam | Lal Bahadur Shastri National Academy of Administration')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Memo Discipline" />
    <x-session_message />

    <!-- start Zero Configuration -->
    <div class="card" style="border-left:4px solid #004a93;">
        <div class="card-body">
            <div class="row">
                <div class="col-6">
                    <h4 class="card-title">Memo Discipline</h4>
                </div>
                <div class="col-6">
                    <div class="d-flex justify-content-end align-items-center gap-2">

                        <!-- Add Group Mapping -->
                        @if(hasRole('Internal Faculty') || hasRole('Guest Faculty') || hasRole('Admin') ||
                        hasRole('Training'))
                        <a href="{{ route('memo.discipline.create') }}"
                            class="btn btn-primary d-flex align-items-center">
                            <i class="material-icons menu-icon material-symbols-rounded"
                                style="font-size: 24px;">add</i>
                            Marks Deduction
                        </a>
                        @endif


                    </div>
                </div>
            </div>
            <form method="GET" action="{{ route('memo.discipline.index') }}" id="filterForm">
                <div class="row">
                    <div class="col-3">
                        <div class="mb-3">
                            <label for="program_name" class="form-label">Program Name</label>
                            <select class="form-select" id="program_name" name="program_name">
                                <option value="">Select Program</option>
                                @foreach($courses as $course)
                                <option value="{{ $course->pk }}"
                                    {{ (string)$programNameFilter == (string)$course->pk ? 'selected' : '' }}>
                                    {{ $course->course_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-3">
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">Select status</option>
                                <option value="1" {{ $statusFilter == '1' ? 'selected' : '' }}>Open</option>
                                <option value="0" {{ $statusFilter == '0' ? 'selected' : '' }}>Close</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <input type="text" class="form-control" id="search" name="search" placeholder="Search..."
                                value="{{ $searchFilter }}">
                        </div>
                    </div>
                </div>
               
                <div class="col-3">
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">Select status</option>
                            <option value="2" {{ $statusFilter == '2' ? 'selected' : '' }}>Open</option>
                            <option value="3" {{ $statusFilter == '3' ? 'selected' : '' }}>Close</option>
                        </select>
                    </div>
                </div>
                <div class="col-3">
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                       <input type="text" class="form-control" id="search" name="search" placeholder="Search..." value="{{ $searchFilter }}">
                    </div>
                </div>
                </div>
                 <div class="row">
                <div class="col-3">
                    <div class="mb-3">
                        <label for="from_date" class="form-label">From Date</label>
                        <input type="date" class="form-control" id="from_date" name="from_date" value="{{ $fromDateFilter ?: \Carbon\Carbon::today()->toDateString() }}">
                    </div>
                </div>
                <div class="col-3">
                    <div class="mb-3">
                        <label for="to_date" class="form-label">To Date</label>
                        <input type="date" class="form-control" id="to_date" name="to_date" value="{{ $toDateFilter ?: \Carbon\Carbon::today()->toDateString() }}">
                    </div>
                </div>
                <div class="col-6">
                    <div class="mb-3 d-flex align-items-center gap-2">
                        <a href="{{ route('memo.discipline.index') }}" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i> Clear Filters
                        </a>
                    </div>
                </div>
                <div class="col-6 text-end">
                    <div class="mb-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-funnel-fill me-1"></i> Apply Filters
                        </button>
                    </div>
            </div>
            </div>
            </form>
            <hr>
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row g-3 align-items-end">

                        <!-- Search -->
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Search</label>
                            <input type="text" class="form-control" placeholder="Search participant / program"
                                id="tableSearch">
                        </div>

                        <!-- Status Filter -->
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Status</label>
                            <select class="form-select" id="statusFilter">
                                <option value="">All</option>
                                <option value="1">Recorded</option>
                                <option value="2">Memo Sent</option>
                                <option value="3">Memo Closed</option>
                            </select>
                        </div>


                            <!-- Type -->
                           
                            <!-- Status -->
                            <td class="status sticky-status">
                                @if ($memo->status == 1)
                                <span class="badge bg-success-subtle text-success">
                                    <i class="bi bi-check-circle me-1"></i> Recorded
                                </span>
                                @elseif ($memo->status == 2)

                                 <span class="badge bg-danger-subtle text-danger">
                                    <i class="bi bi-x-circle me-1"></i> Memo sent
                                </span>
                                <a href="{{ route('memo.discipline.memo.show', encrypt($memo->pk)) }}" class="badge bg-primary-subtle text-primary ms-2 view-reason" >
                                         View Memo
                                    </a>
                                  <a class="text-success d-flex align-items-center view-conversation"
                                        data-bs-toggle="offcanvas" data-bs-target="#chatOffcanvas" 
                                        data-id="{{ $memo->pk }}" data-type="{{ (hasRole('Internal Faculty') || hasRole('Guest Faculty') || hasRole('Admin') || hasRole('Training')) ? 'admin' : 'OT' }}"
>
                                        <i class="material-icons material-symbols-rounded">chat</i>
                                    </a>
                                @else 
                                <a class="text-success d-flex align-items-center view-conversation"
                                        data-bs-toggle="offcanvas" data-bs-target="#chatOffcanvas" 
                                        data-id="{{ $memo->pk }}" data-type="{{ (hasRole('Internal Faculty') || hasRole('Guest Faculty') || hasRole('Admin') || hasRole('Training')) ? 'admin' : 'OT' }}"
>
                                        <i class="material-icons material-symbols-rounded">chat</i>
                                    </a>
                               
                                <!-- <span class="badge bg-danger-subtle text-danger">
                                    <i class="bi bi-x-circle me-1"></i> Memo Closed
                                </span> -->
                                @endif
                            </td>
                            <td class="s_name fw-medium">
                                @if(hasRole('Internal Faculty') || hasRole('Guest Faculty') || hasRole('Admin') || hasRole('Training'))
                                @if($memo->status == 1)
                                <button class="btn btn-sm btn-primary" data-discipline="{{ $memo->pk }}" id="sendMemoBtn" >
                                    <i class="bi bi-envelope-paper"></i> Send Memo
                                </button>
                                <button class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-file-earmark-pdf me-1"></i> PDF
                                </button>
                            </div>
                        </div>

                    </div>
                </div>
            </div>



        </div>
    </div>
    <!-- end Zero Configuration -->

    <!-- Enhanced Offcanvas with GIGW Guidelines -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="chatOffcanvas" aria-labelledby="conversationTopic"
        role="dialog">
        <div class="offcanvas-header">
            <div class="d-flex flex-column w-100">
                <h4 class="offcanvas-title mb-2" id="conversationTopic">
                    <i class="material-symbols-rounded me-2" style="vertical-align: middle; font-size: 24px;">forum</i>
                    Conversation
                </h4>
                <h5 id="type_side_menu">Loading...</h5>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close conversation panel"
                title="Close">
            </button>
        </div>
        <input type="hidden" id="userType" value="" aria-hidden="true">

        <div class="offcanvas-body d-flex flex-column">
            <!-- Chat Body with Enhanced Styling -->
            <div class="chat-body flex-grow-1" id="chatBody" role="log" aria-live="polite"
                aria-label="Conversation messages">
                <div class="d-flex align-items-center justify-content-center h-100">
                    <div class="text-center">
                        <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                            <span class="visually-hidden">Loading conversation...</span>
                        </div>
                        <p class="text-muted">Loading conversation...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

@push('scripts')
<script>
$(document).ready(function() {

    /* ===============================
       FILTER AUTO SUBMIT
    =============================== */
    $('#program_name, #status').on('change', function() {
        $('#filterForm').submit();
    });

    /* ===============================
       SEND MEMO
    =============================== */
    $(document).on('click', '#sendMemoBtn', function() {

        let discipline = $(this).data('discipline');

        Swal.fire({
            title: 'Are you sure?',
            text: "Do you want to send the memo?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, send it!'
        }).then((result) => {
            if (result.isConfirmed) {

                $.ajax({
                    url: "{{ route('memo.discipline.sendMemo') }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        discipline_pk: discipline
                    },
                    success: function(response) {
                        Swal.fire(
                            'Sent!',
                            'The memo has been sent.',
                            'success'
                        ).then(() => {
                            location.reload(); // refresh list
                        });
                    },
                    error: function() {
                        Swal.fire('Error!', 'Something went wrong.', 'error');
                    }
                });

            }
        });
    });
    $('.view-conversation').on('click', function() {
        let memoId = $(this).data('id');
        let type = $(this).data('type');

        $('#conversationTopic').text("Topic: Discipline Conversation");
        $('#type_side_menu').text(type);
        $('#chatBody').html('<p class="text-muted text-center">Loading conversation...</p>');

        $.ajax({
            url: '/memo/discipline/get_conversation_model/' + memoId + '/' + type,
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

@endpush

@endsection