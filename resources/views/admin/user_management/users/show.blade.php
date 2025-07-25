@extends('admin.layouts.master')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card mt-4">
                {{-- <div class="card-header d-flex justify-content-between align-items-center">
                    <span>User Details: {{ $user->name }}</span>
                    <div>
                        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-warning btn-sm">Edit</a>
                        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary btn-sm">Back to List</a>
                    </div>
                </div> --}}

                <div class="card-body ">
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Username:</div>
                        <div class="col-md-8">{{ $user->user_name }}</div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Email:</div>
                        <div class="col-md-8">{{ $user->email_id }}</div>
                    </div>

                    
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Roles:</div>
                        <div class="col-md-8">
                            @if($user->roles->count() > 0)
                                @foreach($user->roles as $role)
                                    <span class="badge bg-primary">{{ $role->name }}</span>
                                @endforeach
                            @else
                                <span class="text-muted">No roles assigned</span>
                            @endif
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Permissions:</div>
                        <div class="col-md-8">
                            @if($user->permissions->count() > 0)
                                @foreach($user->permissions as $permission)
                                    <span class="badge bg-info">{{ $permission->name }}</span>
                                @endforeach
                            @else
                                <span class="text-muted">No direct permissions assigned</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 