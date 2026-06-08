@extends('admin.layouts.master')
@section('title', 'Visitor Pass Management')
@section('setup_content')
<div class="container-fluid">
    @include('components.breadcrum', ['title' => 'Visitor Pass Management']) 
    <div class="card" style="border-left:4px solid #004a93;">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Visitor Pass Management</h4>
                <a href="{{ route('admin.security.visitor_pass.create') }}" class="btn btn-primary">
                    <i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">add</i>
                    Register New Visitor
                </a>
            </div>
            
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Pass #</th>
                            <th>Visitor(s)</th>
                            <th>Company</th>
                            <th>Purpose</th>
                            <th>Host Employee</th>
                            <th>In Time</th>
                            <th>Out Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($visitorPasses as $pass)
                            <tr>
                                <td>{{ $pass->pass_number }}</td>
                                <td>
                                    @if($pass->visitorNames->count() > 0)
                                        @foreach($pass->visitorNames->take(2) as $vn)
                                            {{ $vn->visitor_name }}@if(!$loop->last),@endif
                                        @endforeach
                                        @if($pass->visitorNames->count() > 2)
                                            <small class="text-muted">(+{{ $pass->visitorNames->count() - 2 }} more)</small>
                                        @endif
                                    @else
                                        --
                                    @endif
                                </td>
                                <td>{{ $pass->company ?? '--' }}</td>
                                <td>{{ Str::limit($pass->purpose, 30) }}</td>
                                <td>{{ $pass->employee ? $pass->employee->first_name . ' ' . $pass->employee->last_name : '--' }}</td>
                                <td>{{ $pass->in_time ? $pass->in_time->format('d-m-Y H:i') : '--' }}</td>
                                <td>
                                    @if($pass->out_time)
                                        {{ $pass->out_time->format('d-m-Y H:i') }}
                                    @else
                                        <span class="badge bg-success">Active</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('admin.security.visitor_pass.show', encrypt($pass->pk)) }}" class="text-info" title="View">
                                            <i class="material-icons material-symbols-rounded" style="font-size:22px;">visibility</i>
                                        </a>
                                        @if(!$pass->out_time)
                                            <form action="{{ route('admin.security.visitor_pass.checkout', encrypt($pass->pk)) }}" method="POST" onsubmit="return confirm('Mark visitor as checked out?')">
                                                @csrf
                                                <button type="submit" class="btn btn-link p-0 text-warning" title="Check Out">
                                                    <i class="material-icons material-symbols-rounded" style="font-size:22px;">logout</i>
                                                </button>
                                            </form>
                                            <a href="{{ route('admin.security.visitor_pass.edit', encrypt($pass->pk)) }}" class="text-success" title="Edit">
                                                <i class="material-icons material-symbols-rounded" style="font-size:22px;">edit</i>
                                            </a>
                                        @endif
                                        <form action="{{ route('admin.security.visitor_pass.delete', encrypt($pass->pk)) }}" method="POST" onsubmit="return confirm('Delete this visitor pass?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-link p-0 text-danger" title="Delete">
                                                <i class="material-icons material-symbols-rounded" style="font-size:22px;">delete</i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">No visitor passes found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $visitorPasses->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
