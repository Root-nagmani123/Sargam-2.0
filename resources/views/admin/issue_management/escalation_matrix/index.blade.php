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
                                <th>#</th>
                                <th>Complaint Category</th>
                                <th>Level 1 (Employee / Days)</th>
                                <th>Level 2 (Employee / Days)</th>
                                <th>Level 3 (Employee / Days)</th>
                                <th>Actions</th>
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
                                    <a href="javascript:void(0)" class="btn btn-sm btn-warning" onclick="editMatrix({{ $row['category']->pk }}, {{ json_encode($row['category']->issue_category) }}, {{ $row['level1']?->employee_master_pk ?? 'null' }}, {{ $row['level1']?->days_notify ?? 0 }}, {{ $row['level2']?->employee_master_pk ?? 'null' }}, {{ $row['level2']?->days_notify ?? 0 }}, {{ $row['level3']?->employee_master_pk ?? 'null' }}, {{ $row['level3']?->days_notify ?? 0 }})">
                                        <iconify-icon icon="solar:pen-bold"></iconify-icon> Edit
                                    </a>
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
(function() {
    var escalationEmployees = @json($employees);

    function optionHtml(emp) {
        return '<option value="' + emp.employee_pk + '">' + (emp.employee_name || '').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</option>';
    }

    function rebuildLevel2Add(excludePk) {
        var sel = document.getElementById('level2_employee');
        if (!sel) return;
        var current = sel.value;
        if (typeof DropdownSearch !== 'undefined') DropdownSearch.destroy('#level2_employee');
        sel.innerHTML = '<option value="">- Select -</option>';
        escalationEmployees.forEach(function(emp) {
            if (String(emp.employee_pk) !== String(excludePk)) {
                sel.insertAdjacentHTML('beforeend', optionHtml(emp));
            }
        });
        sel.value = '';
        if (typeof DropdownSearch !== 'undefined') DropdownSearch.reinit('#level2_employee', { placeholder: '- Select -', allowClear: true });
    }

    function rebuildLevel3Add(excludePk1, excludePk2) {
        var sel = document.getElementById('level3_employee');
        if (!sel) return;
        if (typeof DropdownSearch !== 'undefined') DropdownSearch.destroy('#level3_employee');
        sel.innerHTML = '<option value="">- Select -</option>';
        escalationEmployees.forEach(function(emp) {
            var pk = String(emp.employee_pk);
            if (pk !== String(excludePk1) && pk !== String(excludePk2)) {
                sel.insertAdjacentHTML('beforeend', optionHtml(emp));
            }
        });
        sel.value = '';
        if (typeof DropdownSearch !== 'undefined') DropdownSearch.reinit('#level3_employee', { placeholder: '- Select -', allowClear: true });
    }

    function rebuildLevel2Edit(excludePk) {
        var sel = document.getElementById('edit_level2_employee');
        if (!sel) return;
        var current = sel.value;
        if (typeof DropdownSearch !== 'undefined') DropdownSearch.destroy('#edit_level2_employee');
        sel.innerHTML = '<option value="">- Select -</option>';
        escalationEmployees.forEach(function(emp) {
            if (String(emp.employee_pk) !== String(excludePk)) {
                sel.insertAdjacentHTML('beforeend', optionHtml(emp));
            }
        });
        if (current && String(excludePk) !== current) sel.value = current;
        if (typeof DropdownSearch !== 'undefined') DropdownSearch.reinit('#edit_level2_employee', { placeholder: '- Select -', allowClear: true });
    }

    function rebuildLevel3Edit(excludePk1, excludePk2) {
        var sel = document.getElementById('edit_level3_employee');
        if (!sel) return;
        var current = sel.value;
        if (typeof DropdownSearch !== 'undefined') DropdownSearch.destroy('#edit_level3_employee');
        sel.innerHTML = '<option value="">- Select -</option>';
        escalationEmployees.forEach(function(emp) {
            var pk = String(emp.employee_pk);
            if (pk !== String(excludePk1) && pk !== String(excludePk2)) {
                sel.insertAdjacentHTML('beforeend', optionHtml(emp));
            }
        });
        if (current && current !== String(excludePk1) && current !== String(excludePk2)) sel.value = current;
        if (typeof DropdownSearch !== 'undefined') DropdownSearch.reinit('#edit_level3_employee', { placeholder: '- Select -', allowClear: true });
    }

    document.addEventListener('DOMContentLoaded', function() {
        var level1Add = document.getElementById('level1_employee');
        var level2Add = document.getElementById('level2_employee');
        var level3Add = document.getElementById('level3_employee');
        if (level1Add) {
            level1Add.addEventListener('change', function() {
                var pk = this.value;
                rebuildLevel2Add(pk);
                rebuildLevel3Add(pk, null);
            });
        }
        if (level2Add) {
            level2Add.addEventListener('change', function() {
                var pk1 = level1Add && level1Add.value ? level1Add.value : null;
                var pk2 = this.value;
                rebuildLevel3Add(pk1, pk2);
            });
        }

        var level1Edit = document.getElementById('edit_level1_employee');
        var level2Edit = document.getElementById('edit_level2_employee');
        var level3Edit = document.getElementById('edit_level3_employee');
        if (level1Edit) {
            level1Edit.addEventListener('change', function() {
                var pk = this.value;
                rebuildLevel2Edit(pk);
                rebuildLevel3Edit(pk, level2Edit && level2Edit.value ? level2Edit.value : null);
            });
        }
        if (level2Edit) {
            level2Edit.addEventListener('change', function() {
                var pk1 = level1Edit && level1Edit.value ? level1Edit.value : null;
                var pk2 = this.value;
                rebuildLevel3Edit(pk1, pk2);
            });
        }
    });
})();

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

    var escalationEmployees = @json($employees);
    function opt(emp) { return '<option value="' + emp.employee_pk + '">' + (emp.employee_name || '').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</option>'; }
    var L1 = document.getElementById('edit_level1_employee');
    var L2 = document.getElementById('edit_level2_employee');
    var L3 = document.getElementById('edit_level3_employee');
    var v1 = L1 && L1.value ? L1.value : '';
    var v2 = L2 && L2.value ? L2.value : '';
    L2.innerHTML = '<option value="">- Select -</option>';
    escalationEmployees.forEach(function(e) { if (String(e.employee_pk) !== v1) L2.insertAdjacentHTML('beforeend', opt(e)); });
    L2.value = v2;
    L3.innerHTML = '<option value="">- Select -</option>';
    escalationEmployees.forEach(function(e) {
        var p = String(e.employee_pk);
        if (p !== v1 && p !== v2) L3.insertAdjacentHTML('beforeend', opt(e));
    });
    L3.value = (emp3 != null && String(emp3) !== v1 && String(emp3) !== v2) ? String(emp3) : '';

    new bootstrap.Modal(document.getElementById('editMatrixModal')).show();
}
</script>
@endsection
