@extends('admin.layouts.master')
@section('title', 'Vehicle Pass Applications')
@section('setup_content')
<div class="container-fluid">
    <div class="card" style="border-left:4px solid #004a93;">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">My Vehicle Pass Applications</h4>
                <a href="{{ route('admin.security.vehicle_pass.create') }}" class="btn btn-primary">
                    <i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">add</i>
                    Apply for Vehicle Pass
                </a>
            </div>
            
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Request ID</th>
                            <th>Vehicle Type</th>
                            <th>Vehicle Number</th>
                            <th>Valid From</th>
                            <th>Valid To</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vehiclePasses as $pass)
                            <tr>
                                <td>{{ $pass->vehicle_req_id ?? '--' }}</td>
                                <td>{{ $pass->vehicleType->vehicle_type ?? '--' }}</td>
                                <td>{{ $pass->vehicle_no }}</td>
                                <td>{{ $pass->veh_card_valid_from ? $pass->veh_card_valid_from->format('d-m-Y') : '--' }}</td>
                                <td>{{ $pass->vech_card_valid_to ? $pass->vech_card_valid_to->format('d-m-Y') : '--' }}</td>
                                <td>
                                    @if($pass->vech_card_status == 1)
                                        <span class="badge bg-warning">Pending</span>
                                    @elseif($pass->vech_card_status == 2)
                                        <span class="badge bg-success">Approved</span>
                                    @else
                                        <span class="badge bg-danger">Rejected</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('admin.security.vehicle_pass.show', encrypt($pass->vehicle_tw_pk)) }}" class="text-info" title="View">
                                            <i class="material-icons material-symbols-rounded" style="font-size:22px;">visibility</i>
                                        </a>
                                        @if($pass->vech_card_status == 1)
                                            <a href="{{ route('admin.security.vehicle_pass.edit', encrypt($pass->vehicle_tw_pk)) }}" class="text-success" title="Edit">
                                                <i class="material-icons material-symbols-rounded" style="font-size:22px;">edit</i>
                                            </a>
                                            <form action="{{ route('admin.security.vehicle_pass.delete', encrypt($pass->vehicle_tw_pk)) }}" method="POST" onsubmit="return confirm('Delete this application?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-link p-0 text-danger" title="Delete">
                                                    <i class="material-icons material-symbols-rounded" style="font-size:22px;">delete</i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">No applications found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $vehiclePasses->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
