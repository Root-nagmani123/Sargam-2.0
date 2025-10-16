@extends('admin.layouts.master')
@section('content')
    <div class="card p-3">
        <h4>All Peer Evaluation Groups</h4>

        @if ($groups->isEmpty())
            <div class="alert alert-info">No evaluation groups available.</div>
        @else
            <div class="list-group mt-3">
                @foreach ($groups as $group)
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>{{ $group->group_name }}</span>

                        {{-- @if (in_array($group->id, $userGroups)) --}}
                        {{-- <a href="{{ route('peer.index', $group->id) }}" class="btn btn-success btn-sm">
                            Submit Evaluation
                        </a> --}}

                        <a href="{{ route('peer.index', ['group_id' => $group->id]) }}" class="btn btn-success btn-sm">
                            Submit Evaluation
                        </a>

                        {{-- @else
                        <span class="badge bg-secondary">Not Assigned</span>
                    @endif --}}
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection
