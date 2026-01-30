@extends('admin.layouts.master')
@section('title', 'Request Family ID Card - Sargam | Lal Bahadur Shastri')
@section('setup_content')
<div class="container-fluid family-idcard-index-page">
    <x-breadcrum title="Request Family ID Card"></x-breadcrum>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <h2 class="h5 mb-0 fw-bold text-dark">Request Family ID Card</h2>
        <a href="{{ route('admin.family_idcard.create') }}" class="btn btn-primary px-4 py-2 rounded-pill shadow-sm">
            Generate New ID Card
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle family-idcard-table">
                    <thead>
                        <tr>
                            <th>S.No.</th>
                            <th>Request date</th>
                            <th>Employee Name</th>
                            <th>Designation</th>
                            <th>Department</th>
                            <th>Department Name</th>
                            <th>No. of Member ID Card</th>
                            <th>ID Type</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $index => $req)
                            <tr>
                                <td class="fw-medium">{{ $requests->firstItem() + $index }}</td>
                                <td>{{ $req->created_at ? $req->created_at->format('d/m/Y') : '--' }}</td>
                                <td>{{ $req->employee_name ?? $req->employee_id ?? '--' }}</td>
                                <td>{{ $req->designation ?? '--' }}</td>
                                <td>{{ $req->department ?? '--' }}</td>
                                <td>{{ $req->no_of_member_id_card ?? '--' }}</td>
                                <td>{{ $req->id_type ?? '--' }}</td>
                                <td>
                                    <div class="d-flex align-items-center justify-content-end gap-2">
                                        <a href="{{ route('admin.family_idcard.show', $req) }}" class="text-primary" title="View" data-bs-toggle="tooltip" data-bs-placement="top" title="View">
                                            <i class="material-icons material-symbols-rounded">visibility</i>
                                        </a>
                                        <a href="{{ route('admin.family_idcard.edit', $req) }}" class="text-primary" title="Edit" onclick="return confirm('Are you sure you want to edit this request?');" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit">
                                            <i class="material-icons material-symbols-rounded">edit</i>
                                        </a>
                                        <form action="{{ route('admin.family_idcard.destroy', $req) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this request?');">
                                            @csrf
                                            @method('DELETE')
                                            <a class="text-danger" title="Delete" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete">
                                                <i class="material-icons material-symbols-rounded">delete</i>
                                            </a>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="material-icons material-symbols-rounded d-block mb-2" style="font-size:48px; opacity:0.4;">inbox</i>
                                    <p class="mb-1">No family ID card requests found.</p>
                                    <small>Click "Generate New ID Card" to create one.</small>
                                    <a href="{{ route('admin.family_idcard.create') }}" class="btn btn-primary btn-sm mt-2 rounded-pill px-3">
                                        <i class="material-icons material-symbols-rounded align-middle me-1" style="font-size:16px;">add</i>
                                        Create Request
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($requests->hasPages())
                <div class="d-flex justify-content-between align-items-center px-3 py-3 border-top flex-wrap gap-2">
                    <div class="small text-muted">
                        Showing <strong>{{ $requests->firstItem() ?? 0 }}</strong> to <strong>{{ $requests->lastItem() ?? 0 }}</strong> of <strong>{{ $requests->total() }}</strong> items
                    </div>
                    <nav>
                        {{ $requests->links('pagination::bootstrap-5') }}
                    </nav>
                </div>
            @endif
        </div>
    </div>
</div>

<style>
.family-idcard-index-page .card { border-radius: 0.5rem; overflow: hidden; }
.family-idcard-table thead tr { background: #122442; color: #fff; }
.family-idcard-table thead th { font-weight: 600; font-size: 0.8125rem; padding: 0.75rem 1rem; border: none; }
.family-idcard-table tbody td { padding: 0.75rem 1rem; vertical-align: middle; border-bottom: 1px solid #f1f3f5; }
.family-idcard-table tbody tr:hover { background: #f8fafc; }
.family-idcard-index-page .pagination .page-item.active .page-link { background-color: #004a93; border-color: #004a93; color: #fff; }
.family-idcard-index-page .pagination .page-link { color: #004a93; }
</style>
@endsection
