@extends('admin.layouts.master')
@section('title', 'All Employee ID Card Requests - Approval Status')
@section('setup_content')
<div class="container-fluid">
    @include('components.breadcrum', ['title' => 'All ID Card Requests'])
    <div class="card" style="border-left:4px solid #004a93;">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                <h4 class="mb-0">All Employee ID Card Requests</h4>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.security.employee_idcard_approval.approval1') }}" class="btn btn-outline-primary btn-sm">Approval I</a>
                    <a href="{{ route('admin.security.employee_idcard_approval.approval2') }}" class="btn btn-outline-primary btn-sm">Approval II</a>
                    <a href="{{ route('admin.employee_idcard.index') }}" class="btn btn-secondary btn-sm">ID Card List</a>
                </div>
            </div>

            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-3">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Status</option>
                        <option value="Pending_A1" {{ request('status') == 'Pending_A1' ? 'selected' : '' }}>Pending (Approval I)</option>
                        <option value="Pending_A2" {{ request('status') == 'Pending_A2' ? 'selected' : '' }}>Pending (Approval II)</option>
                        <option value="Approved" {{ request('status') == 'Approved' ? 'selected' : '' }}>Approved</option>
                        <option value="Rejected" {{ request('status') == 'Rejected' ? 'selected' : '' }}>Rejected</option>
                        <option value="Issued" {{ request('status') == 'Issued' ? 'selected' : '' }}>Issued</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by name, designation..." value="{{ request('search') }}">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                </div>
            </form>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>S.No.</th>
                            <th>Request Date</th>
                            <th>Category</th>
                            <th>Request Type</th>
                            <th>Employee Name</th>
                            <th>Request Status</th>
                            <th>Approved By A1</th>
                            <th>Approved By A2</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $index => $req)
                            <tr>
                                <td>{{ $requests->firstItem() + $index }}</td>
                                <td>{{ $req->created_at ? $req->created_at->format('d/m/Y') : '--' }}</td>
                                <td>{{ $req->card_type ?? '--' }}</td>
                                <td>{{ $req->request_for ?? '--' }}</td>
                                <td>{{ $req->name }}</td>
                                <td>
                                    @php
                                        $statusClass = match($req->status) {
                                            'Pending' => 'warning',
                                            'Approved' => 'success',
                                            'Rejected' => 'danger',
                                            'Issued' => 'primary',
                                            default => 'secondary'
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $statusClass }}">{{ $req->status }}</span>
                                </td>
                                <td>
                                    @if($req->approver1)
                                        <span class="badge bg-success">Approved</span>
                                        <br><small class="text-muted">{{ $req->approver1->name }}</small>
                                    @else
                                        <span class="text-muted">--</span>
                                    @endif
                                </td>
                                <td>
                                    @if($req->approver2)
                                        <span class="badge bg-success">Approved</span>
                                        <br><small class="text-muted">{{ $req->approver2->name }}</small>
                                    @else
                                        <span class="text-muted">--</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.security.employee_idcard_approval.show', encrypt($req->id)) }}" class="btn btn-sm btn-info" title="View">
                                        <i class="material-icons material-symbols-rounded" style="font-size:18px;">visibility</i>
                                    </a>
                                    <a href="{{ route('admin.employee_idcard.show', $req->id) }}" class="btn btn-sm btn-outline-secondary" title="Full Details">
                                        <i class="material-icons material-symbols-rounded" style="font-size:18px;">open_in_new</i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">No requests found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $requests->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
