@extends('admin.layouts.master')

@section('title', 'Escalation Matrix')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<style>
.master-filter-select {
    height: 40px; width: 175px; padding: 0 2rem 0 0.875rem; font-size: 0.9375rem;
    color: #344054; background-color: #fff; border: 1px solid #d0d5dd; border-radius: 8px;
}
.master-filter-select:focus { border-color: #004a93; box-shadow: 0 0 0 3px rgba(0, 74, 147, 0.12); }

/* Keep the whole toolbar on one line; scroll horizontally rather than wrap. */
.master-toolbar { flex-wrap: nowrap; overflow-x: auto; }
.master-toolbar > * { flex: 0 0 auto; }
.master-toolbar::-webkit-scrollbar { height: 6px; }
.master-toolbar::-webkit-scrollbar-thumb { background: #d0d5dd; border-radius: 3px; }

.programme-action-group .material-symbols-rounded { font-size: 18px; line-height: 1; }
.escalation-level-empty { color: var(--ds-ink-muted, #98a2b3); }
</style>
@endpush

@section('content')
<div class="container-fluid escalation-matrix-page py-3">
    <x-breadcrum title="Escalation Matrix">
        <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm"
                data-bs-toggle="modal" data-bs-target="#addMatrixModal">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Add Mapping</span>
        </button>
    </x-breadcrum>
    <x-session_message />
    <div class="d-flex flex-wrap justify-content-end align-items-center gap-3 mb-3">
        <button type="button" class="btn programme-dt-btn-columns border-0 text-primary" id="masterPrintBtn" title="Print">
            <i class="bi bi-printer" aria-hidden="true"></i> <span>Print</span>
        </button>
    </div>

    <div class="card overflow-hidden rounded-1">
        <div class="card-body p-3 p-md-4">

            <div class="d-flex align-items-center gap-2 mb-4 programme-dt-toolbar master-toolbar">
                <span class="programme-dt-filters-label">Filters</span>
                {{-- This grid has no active/inactive flag; data-status carries whether
                     all three escalation levels are mapped, so the shared filter works. --}}
                <select id="masterStatusFilter" class="form-select master-filter-select" aria-label="Mapping">
                    <option value="all">All Mappings</option>
                    <option value="1">Complete (3 levels)</option>
                    <option value="0">Incomplete</option>
                </select>
                <button type="button" class="btn programme-dt-btn-reset" id="masterResetFilters">Reset Filters</button>

                <button type="button" class="btn programme-dt-btn-columns ms-auto" id="masterBtnColumns"
                    data-bs-toggle="modal" data-bs-target="#masterColumnVisibilityModal" title="Show / hide columns">
                    <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                </button>
                <div class="programme-dt-search" data-dt-search-for="masterTable"></div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle programme-dt-table" id="masterTable">
                        <thead>
                            <tr>
                                <th>S. No.</th>
                                <th>Complaint Category</th>
                                <th>Level 1 (Employee / Days)</th>
                                <th>Level 2 (Employee / Days)</th>
                                <th>Level 3 (Employee / Days)</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($matrix as $row)
                                @php
                                    $isComplete = $row['level1'] && $row['level2'] && $row['level3'];
                                @endphp
                                <tr data-status="{{ $isComplete ? 1 : 0 }}">
                                    <td class="fw-medium ps-3">{{ $loop->iteration }}</td>
                                    <td class="fw-semibold">{{ $row['category']->issue_category }}</td>
                                    @foreach(['level1', 'level2', 'level3'] as $levelKey)
                                        <td>
                                            @if($row[$levelKey])
                                                {{ $row[$levelKey]->employee->name ?? 'N/A' }}
                                                <span class="badge rounded-1 bg-info ms-1">{{ $row[$levelKey]->days_notify }} days</span>
                                            @else
                                                <span class="escalation-level-empty">—</span>
                                            @endif
                                        </td>
                                    @endforeach
                                    <td class="text-center">
                                        <div class="d-inline-flex align-items-center justify-content-center programme-action-group" role="group" aria-label="Escalation actions">
                                            <button type="button" class="programme-action-btn" title="Edit"
                                                    onclick="editMatrix({{ $row['category']->pk }}, {{ json_encode($row['category']->issue_category) }}, {{ $row['level1']?->employee_master_pk ?? 'null' }}, {{ $row['level1']?->days_notify ?? 0 }}, {{ $row['level2']?->employee_master_pk ?? 'null' }}, {{ $row['level2']?->days_notify ?? 0 }}, {{ $row['level3']?->employee_master_pk ?? 'null' }}, {{ $row['level3']?->days_notify ?? 0 }})">
                                                <i class="material-icons material-symbols-rounded" aria-hidden="true">edit</i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 table-empty-state">
                                        <div class="d-inline-flex flex-column align-items-center p-5 bg-body-tertiary rounded-4 border border-body-secondary">
                                            <i class="material-icons material-symbols-rounded mb-3 text-body-tertiary" style="font-size:56px;">account_tree</i>
                                            <p class="mb-1 fw-semibold text-body-emphasis">No categories found.</p>
                                            <small class="text-body-secondary mb-3">Add a mapping to get started.</small>
                                            <button type="button" class="btn btn-primary rounded-1 px-4 py-2"
                                                    data-bs-toggle="modal" data-bs-target="#addMatrixModal">Add Mapping</button>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3" data-dt-footer-for="masterTable"></div>
            </div>
        </div>
    </div>
</div>

@include('admin.partials._master_columns_modal')

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
        sel.innerHTML = '<option value="">- Select -</option>';
        escalationEmployees.forEach(function(emp) {
            if (String(emp.employee_pk) !== String(excludePk)) {
                sel.insertAdjacentHTML('beforeend', optionHtml(emp));
            }
        });
        sel.value = '';
    }

    function rebuildLevel3Add(excludePk1, excludePk2) {
        var sel = document.getElementById('level3_employee');
        if (!sel) return;
        sel.innerHTML = '<option value="">- Select -</option>';
        escalationEmployees.forEach(function(emp) {
            var pk = String(emp.employee_pk);
            if (pk !== String(excludePk1) && pk !== String(excludePk2)) {
                sel.insertAdjacentHTML('beforeend', optionHtml(emp));
            }
        });
        sel.value = '';
    }

    function rebuildLevel2Edit(excludePk) {
        var sel = document.getElementById('edit_level2_employee');
        if (!sel) return;
        var current = sel.value;
        sel.innerHTML = '<option value="">- Select -</option>';
        escalationEmployees.forEach(function(emp) {
            if (String(emp.employee_pk) !== String(excludePk)) {
                sel.insertAdjacentHTML('beforeend', optionHtml(emp));
            }
        });
        if (current && String(excludePk) !== current) sel.value = current;
    }

    function rebuildLevel3Edit(excludePk1, excludePk2) {
        var sel = document.getElementById('edit_level3_employee');
        if (!sel) return;
        var current = sel.value;
        sel.innerHTML = '<option value="">- Select -</option>';
        escalationEmployees.forEach(function(emp) {
            var pk = String(emp.employee_pk);
            if (pk !== String(excludePk1) && pk !== String(excludePk2)) {
                sel.insertAdjacentHTML('beforeend', optionHtml(emp));
            }
        });
        if (current && current !== String(excludePk1) && current !== String(excludePk2)) sel.value = current;
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

@include('admin.partials._master_list_scripts', [
    'reportTitle'   => 'Escalation Matrix',
    'storageKey'    => 'escalationMatrixGrid:hiddenColumns:v1',
    'statusColumn'  => null,
    'actionColumn'  => 5,
    'statusHeading' => 'Mapping',
    'statusLabels'  => ['Complete', 'Incomplete'],
    'printColumns'  => [
        ['label' => 'Complaint Category', 'index' => 1],
        ['label' => 'Level 1 (Employee / Days)', 'index' => 2],
        ['label' => 'Level 2 (Employee / Days)', 'index' => 3],
        ['label' => 'Level 3 (Employee / Days)', 'index' => 4],
    ],
])
@endsection
