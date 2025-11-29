@extends('admin.layouts.master')

@section('title', 'MDO Escrot Exemption')

@section('content')
<style>
/* ====== TABLE HEADER ====== */
.custom-mdo-table thead th {
    background: #b72a2a !important;
    color: #fff !important;
    font-weight: 600;
    padding: 14px 12px !important;
    border: none !important;
    white-space: nowrap;
}

/* ====== TABLE ROW ====== */
.custom-mdo-table {
    border-collapse: separate !important;
    border-spacing: 0 8px !important;
}

.custom-mdo-table tbody tr {
    background: #ffffff !important;
    box-shadow: 0 0 6px rgba(0, 0, 0, 0.07);
    border-radius: 10px !important;
}

.custom-mdo-table tbody td {
    padding: 14px 12px !important;
    border: none !important;
    vertical-align: middle !important;
}

/* S.No LEFT aligned */
.custom-mdo-table tbody td:first-child {
    text-align: left !important;
}

/* All other columns center aligned */
.custom-mdo-table tbody td:not(:first-child) {
    text-align: center !important;
}

/* ===== ACTION BUTTON ===== */
.mdo-edit-btn {
    width: 32px;
    height: 32px;
    border-radius: 6px;
    background-color: rgba(183, 42, 42, 0.1);
    border: 1px solid #ddd;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: 0.2s;
}

.mdo-edit-btn:hover {
    background-color: #b72a2a;
    color: #fff !important;
}

.mdo-edit-btn i {
    font-size: 18px;
}

/* ====== DATATABLE CLEANUP ====== */
.dataTables_wrapper .dataTables_length,
.dataTables_wrapper .dataTables_filter {
    margin-bottom: 12px;
}

.dataTables_filter input {
    border-radius: 6px !important;
}

.dataTables_wrapper .dataTables_paginate .paginate_button {
    padding: 4px 10px !important;
}

/* ====== TABLE HEADER (ROUNDED LIKE ATTACHED) ====== */
.custom-mdo-table thead {
    border-radius: 10px !important;
    overflow: hidden;
    /* required for rounded effect */
}

.custom-mdo-table thead th {
    background: #b72a2a !important;
    color: #fff !important;
    font-weight: 600;
    padding: 14px 12px !important;
    border: none !important;
    white-space: nowrap;
}

/* Rounded corners on first and last header cell */
.custom-mdo-table thead th:first-child {
    border-top-left-radius: 10px !important;
}

.custom-mdo-table thead th:last-child {
    border-top-right-radius: 10px !important;
}
</style>

<div class="container-fluid">

    <div class="datatables">
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">

                    <div class="row">
                        <div class="col-6">
                            <h4>MDO Escrot Exemption</h4>
                        </div>
                        <div class="col-6">
                            <div class="d-flex justify-content-end align-items-end mb-3">
                                <div class="d-flex align-items-center gap-2">

                                    <!-- Add New Button -->
                                    <a href="{{ route('mdo-escrot-exemption.create') }}"
                                        class="btn btn-primary px-3 py-2 rounded shadow-sm">
                                        <i class="material-icons menu-icon material-symbols-rounded"
                                            style="font-size: 20px; vertical-align: middle;">add</i>
                                        Add New MDO Escrot Exemption
                                    </a>

                                    <!-- Search Box + Icon -->
                                    <!-- Search Expand -->
                                    <div class="search-expand d-flex align-items-center">
                                        <a href="javascript:void(0)" id="searchToggle">
                                            <i class="material-icons menu-icon material-symbols-rounded"
                                                style="font-size: 24px; vertical-align: middle;font-weight: 600;">search</i>
                                        </a>

                                        <input type="text" class="form-control search-input ms-2" id="searchInput"
                                            placeholder="Search…" aria-label="Search">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <table class="table">
                        <thead>
                            <tr>
                                <th class="col">S.No.</th>
                                <th class="col">Date</th>
                                <th class="col">Student Name</th>
                                <th class="col">Time From</th>
                                <th class="col">Time To</th>
                                <th class="col">Programme Name</th>
                                <th class="col">MDO Name</th>
                                <th class="col">Remarks</th>
                                <th class="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($exemptions as $index => $row)
                            <tr>
                                <td>{{ $exemptions->firstItem() + $index }}</td>
                                <td>{{ format_date($row->mdo_date) ?? 'N/A' }}</td>
                                <td>{{ $row->studentMaster->display_name ?? 'N/A' }}</td>
                                <td>{{ $row->Time_from ?? 'N/A' }}</td>
                                <td>{{ $row->Time_to ?? 'N/A' }}</td>
                                <td>{{ optional($row->courseMaster)->course_name ?? 'N/A' }}</td>
                                <td>{{ optional($row->mdoDutyTypeMaster)->mdo_duty_type_name ?? 'N/A' }}</td>
                                <td>{{ $row->Remark ?? 'N/A' }}</td>
                                <td>
                                    <div class="d-flex justify-content-center gap-2">
                                        <a href="{{ route('mdo-escrot-exemption.edit', $row->pk) }}" title="Edit">
                                            <i class="material-icons material-symbols-rounded">edit</i>
                                        </a>
                                        <form action="{{ route('mdo-escrot-exemption.destroy', $row->pk) }}"
                                            method="POST" class="d-inline"
                                            onsubmit="return confirm('Are you sure you want to delete this record?')">
                                            @csrf
                                            @method('DELETE')
                                            <a href="javascript:void(0)" title="Delete">
                                                <i class="material-icons material-symbols-rounded">delete</i>
                                                </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center">No records found</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap">

                        <div class="text-muted small mb-2">
                            Showing {{ $exemptions->firstItem() }}
                            to {{ $exemptions->lastItem() }}
                            of {{ $exemptions->total() }} items
                        </div>

                        <div>
                            {{ $exemptions->links('vendor.pagination.custom') }}
                        </div>

                    </div>

                </div>
            </div>
        </div>
    </div>

</div>

@endsection