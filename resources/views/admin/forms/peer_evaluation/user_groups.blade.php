@extends('admin.layouts.master')
@section('title', 'Peer Evaluation Groups | Sargam User Panel')

@section('setup_content')
<div class="container-fluid">
    <div class="card p-3" >
        <h4>All Peer Evaluation Groups</h4>

        @if ($groups->isEmpty())
            <div class="alert alert-info">No evaluation groups available.</div>
        @else
            <div class="table-responsive mt-3">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Group Name</th>
                            <th>Course Name</th>
                            <th>Event Name</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($groups as $group)
                            <tr>
                                <td>{{ $group->group_name }}</td>
                                <td>{{ $group->course_name ?? '-' }}</td>
                                <td>{{ $group->event_name ?? '-' }}</td>

                                {{-- Closed groups stay listed and stay clickable: the OT can still
                                     read back what they submitted. The reason is the same sentence
                                     the form and store() give. --}}
                                <td>
                                    <a href="{{ route('peer.index', ['group_id' => $group->id]) }}"
                                        class="btn btn-sm {{ $group->closed_reason ? 'btn-outline-secondary' : 'btn-success' }}">
                                        {{ $group->closed_reason ? 'View' : 'Submit Evaluation' }}
                                    </a>
                                    @if ($group->closed_reason)
                                        <div class="small text-muted mt-1">{{ $group->closed_reason }}</div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
