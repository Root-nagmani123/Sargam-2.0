@extends('admin.layouts.master')

@section('title', 'Sessions - Sargam | Lal Bahadur')

@section('content')
<div class="container-fluid">
    <x-breadcrum title="Sessions"></x-breadcrum>
    
    <div class="card" style="border-left: 4px solid #004a93;">
        <div class="card-body">
            <h4>Session Details</h4>
            <hr class="my-2">
            
            @if($sessions->isEmpty())
                <div class="alert alert-info">
                    <i class="material-icons material-symbols-rounded me-2">info</i>
                    No sessions found.
                </div>
            @else
                <div class="datatables">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="sessionsTable">
                            <thead>
                                <tr>
                                    <th scope="col">Sl. No.</th>
                                    <th scope="col">Course Name</th>
                                    <th scope="col">Subject</th>
                                    <th scope="col">Module</th>
                                    <th scope="col">Topic</th>
                                    <th scope="col">Group</th>
                                    <th scope="col">Venue</th>
                                    <th scope="col">Session Time</th>
                                    <th scope="col">Date</th>
                                    <th scope="col">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($sessions as $index => $session)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $session['course_name'] }}</td>
                                    <td>{{ $session['subject_name'] }}</td>
                                    <td>{{ $session['module_name'] }}</td>
                                    <td>{{ $session['topic'] }}</td>
                                    <td>{{ $session['group_names'] }}</td>
                                    <td>{{ $session['venue_name'] }}</td>
                                    <td>{{ $session['session_time'] }}</td>
                                    <td>{{ $session['session_date'] }}</td>
                                    <td>
                                        @php
                                            $today = \Carbon\Carbon::today();
                                            $sessionDate = \Carbon\Carbon::parse($session['start_date']);
                                        @endphp
                                        @if($sessionDate->isPast())
                                            <span class="badge bg-secondary">Completed</span>
                                        @elseif($sessionDate->isToday())
                                            <span class="badge bg-primary">Today</span>
                                        @else
                                            <span class="badge bg-success">Upcoming</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        if ($('#sessionsTable').length) {
            $('#sessionsTable').DataTable({
                "pageLength": 25,
                "order": [[8, "desc"]], // Sort by date descending
                "language": {
                    "paginate": {
                        "previous": '<i class="material-icons material-symbols-rounded" style="font-size: 24px;">chevron_left</i>',
                        "next": '<i class="material-icons material-symbols-rounded" style="font-size: 24px;">chevron_right</i>'
                    },
                    "search": "_INPUT_",
                    "searchPlaceholder": "Search sessions..."
                },
                "responsive": true,
                "autoWidth": false
            });
        }
    });
</script>
@endpush

@endsection

