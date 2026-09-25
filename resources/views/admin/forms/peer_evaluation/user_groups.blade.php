@extends('admin.layouts.master')
@section('title', 'Peer Evaluation Groups | Sargam User Panel')

@section('setup_content')
<div class="container-fluid">
    <div class="card p-3" >
        <h4>All Peer Evaluation Groups</h4>

        {{-- user_report() turns somebody away by redirecting here with 'error' -
             a group they are not in, or a report asked for while the evaluation
             is still open. Without this the redirect looked like a dead link. --}}
        @if (session('error'))
            <div class="alert alert-warning mt-3 mb-0">{{ session('error') }}</div>
        @endif

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

                                {{-- My Report is on EVERY row, open or closed - an OT
                                     can read what they have been scored while the
                                     evaluation is still running, so the two buttons
                                     sit side by side on an open group.

                                     Submit Evaluation only appears when the form is
                                     actually open: once an evaluation is locked there
                                     is no form to serve, only the notice saying so.
                                     That notice is the sentence the form and store()
                                     both give, so all three agree on what happened.

                                     Groups an admin has DEACTIVATED are not here at
                                     all - groupsFor() drops them, rather than listing
                                     them as locked. --}}
                                <td>
                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        @unless ($group->closed_reason)
                                            <a href="{{ route('peer.index', ['group_id' => $group->id]) }}"
                                                class="btn btn-sm btn-success">
                                                Submit Evaluation
                                            </a>
                                        @endunless

                                        <a href="{{ route('peer.user_report', ['groupId' => $group->id]) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            My Report
                                        </a>
                                    </div>

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
</div>
@endsection
