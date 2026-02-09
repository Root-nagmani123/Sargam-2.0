@extends('admin.layouts.master')

@section('title', 'Escalation Matrix - Sargam | Lal Bahadur')

@section('css')
<style>
.modal-body { background-color: #fff !important; color: #212529 !important; }
.modal-content { background-color: #fff !important; }
</style>
@endsection

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Escalation Matrix" />
    <div class="datatables">
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="row mb-2">
                    <div class="col-6">
                        <h4 class="mb-0">Escalation Matrix - 3 Level Hierarchy</h4>
                        <p class="text-muted small mb-0 mt-1">Employees mapped with complaint category. Days define escalation timeline from Level 1 → 2 → 3.</p>
                    </div>
                    <div class="col-6 text-end">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMatrixModal">
                            <iconify-icon icon="ep:circle-plus-filled"></iconify-icon> Add Mapping
                        </button>
                    </div>
                </div>
                <hr>
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th width="5%">#</th>
                                <th width="20%">Complaint Category</th>
                                <th width="25%">Level 1 (Employee / Days)</th>
                                <th width="25%">Level 2 (Employee / Days)</th>
                                <th width="25%">Level 3 (Employee / Days)</th>
                                <th width="10%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($matrix as $index => $row)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td><strong>{{ $row['category']->issue_category }}</strong></td>
                                <td>
                                    @if($row['level1'])
                                        {{ $row['level1']->employee->name ?? 'N/A' }} <span class="badge bg-info">{{ $row['level1']->days_notify }} days</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($row['level2'])
                                        {{ $row['level2']->employee->name ?? 'N/A' }} <span class="badge bg-info">{{ $row['level2']->days_notify }} days</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($row['level3'])
                                        {{ $row['level3']->employee->name ?? 'N/A' }} <span class="badge bg-info">{{ $row['level3']->days_notify }} days</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-warning" onclick="editMatrix({{ $row['category']->pk }}, {{ json_encode($row['category']->issue_category) }}, {{ $row['level1']?->employee_master_pk ?? 'null' }}, {{ $row['level1']?->days_notify ?? 0 }}, {{ $row['level2']?->employee_master_pk ?? 'null' }}, {{ $row['level2']?->days_notify ?? 0 }}, {{ $row['level3']?->employee_master_pk ?? 'null' }}, {{ $row['level3']?->days_notify ?? 0 }})">
                                        <iconify-icon icon="solar:pen-bold"></iconify-icon> Edit
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center">No categories found. Add mapping to get started.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Mapping Modal -->
<div class="modal fade" id="addMatrixModal" tabindex="-1" aria-labelledby="addMatrixModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form action="{{ route('admin.issue-escalation-matrix.store') }}" method="POST">
                @csrf
                <div class="modal-header" style="background:#004a93;">
                    <h5 class="modal-title text-white" id="addMatrixModalLabel">Add Employee Complaint Mapping</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @include('admin.issue_management.escalation_matrix._form', ['employees' => $employees])
                </div>
                <div class="modal-footer gap-2">
                    <button type="submit" class="btn btn-success">Save</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Mapping Modal -->
<div class="modal fade" id="editMatrixModal" tabindex="-1" aria-labelledby="editMatrixModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form id="editMatrixForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header" style="background:#004a93;">
                    <h5 class="modal-title text-white" id="editMatrixModalLabel">Edit Employee Complaint Mapping</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @include('admin.issue_management.escalation_matrix._form', ['employees' => $employees, 'isEdit' => true])
                </div>
                <div class="modal-footer gap-2">
                    <button type="submit" class="btn btn-success">Update</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function editMatrix(categoryId, categoryName, emp1, days1, emp2, days2, emp3, days3) {
    document.getElementById('edit_category_pk').value = categoryId;
    document.getElementById('edit_category_name').value = categoryName;
    document.getElementById('edit_level1_employee').value = emp1 != null ? String(emp1) : '';
    document.getElementById('edit_level1_days').value = days1 || 0;
    document.getElementById('edit_level2_employee').value = emp2 != null ? String(emp2) : '';
    document.getElementById('edit_level2_days').value = days2 || 0;
    document.getElementById('edit_level3_employee').value = emp3 != null ? String(emp3) : '';
    document.getElementById('edit_level3_days').value = days3 || 0;

    document.getElementById('editMatrixForm').action = "{{ url('admin/issue-escalation-matrix') }}/" + categoryId;

    new bootstrap.Modal(document.getElementById('editMatrixModal')).show();
}
</script>
@endsection
