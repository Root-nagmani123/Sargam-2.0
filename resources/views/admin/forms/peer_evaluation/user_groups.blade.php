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

                                <td>
                                    <a href="{{ route('peer.index', ['group_id' => $group->id]) }}"
                                        class="btn btn-success btn-sm">
                                        Submit Evaluation
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                {{-- Count only: measured at a handful of rows, so a pager would be
                     ceremony. The missing count was the real gap. --}}
                <div class="d-flex justify-content-end mt-3">
                    <span class="text-muted small">Showing
                        <span class="fw-semibold">{{ count($groups) }}</span>
                        of <span class="fw-semibold">{{ count($groups) }}</span> groups</span>
                </div>
            </div>
        @endif
    </div>
@endsection
