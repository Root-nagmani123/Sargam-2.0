@extends('admin.layouts.master')

@section('title', 'Employee Type Master')

@section('setup_content')
<style>
/* DataTable chrome — match master list reference */
#employeetypemaster-table_wrapper .dataTables_length,
#employeetypemaster-table_wrapper .dataTables_filter,
#employeetypemaster-table_wrapper .dataTables_info,
#employeetypemaster-table_wrapper .dt-buttons {
    display: none !important;
}

#employeetypemaster-table_wrapper .dataTables_paginate {
    margin-top: 0 !important;
    display: flex;
    align-items: center;
    gap: 2px;
}

#employeetypemaster-table_wrapper .paginate_button {
    border: none !important;
    background: transparent !important;
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 0.8125rem;
    cursor: pointer;
    color: #495057 !important;
}

#employeetypemaster-table_wrapper .paginate_button:hover:not(.disabled) {
    background: #f1f3f5 !important;
}

#employeetypemaster-table_wrapper .paginate_button.current,
#employeetypemaster-table_wrapper .paginate_button.current:hover {
    background: #1b3a5c !important;
    color: #fff !important;
    border-radius: 4px;
}

#employeetypemaster-table_wrapper .paginate_button.disabled {
    opacity: 0.35;
    cursor: default;
}

#employeetypemaster-table_wrapper .ellipsis {
    padding: 5px 4px;
    color: #adb5bd;
}

.etm-search-wrap {
    position: relative;
    width: min(100%, 260px);
}

.etm-search-wrap .form-control {
    border-radius: 8px;
    border: 1px solid #dee2e6;
    padding-left: 38px;
    font-size: 0.875rem;
}

.etm-search-wrap .etm-search-icon {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: #adb5bd;
    font-size: 18px;
    pointer-events: none;
}

#employeetypemaster-table {
    margin-bottom: 0;
}

#employeetypemaster-table thead th {
    background-color: #f8f9fa;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #6c757d;
    border-bottom: 2px solid #dee2e6;
    padding: 12px 16px;
    white-space: nowrap;
    vertical-align: middle;
}

#employeetypemaster-table tbody td {
    font-size: 0.875rem;
    padding: 12px 16px;
    vertical-align: middle;
    border-bottom: 1px solid #f1f3f5;
    color: #212529;
}

#employeetypemaster-table tbody tr:hover td {
    background-color: #fafbfc;
}

.etm-action-btn {
    background: none;
    border: none;
    padding: 3px 5px;
    cursor: pointer;
    line-height: 1;
    display: inline-flex;
    align-items: center;
    text-decoration: none;
}

.etm-action-btn .material-symbols-rounded {
    font-size: 20px;
}

.etm-action-btn:hover {
    opacity: 0.75;
}

/* Toggle — reference: active = amber, inactive = green */
#employeetypemaster-table .etm-status-switch .form-check-input.status-toggle {
    width: 2.5em;
    height: 1.25em;
    cursor: pointer;
    margin: 0;
}

#employeetypemaster-table .etm-status-switch .form-check-input.status-toggle:focus {
    box-shadow: 0 0 0 0.2rem rgba(27, 58, 92, 0.15);
}
</style>

<div class="container-fluid">
    <x-breadcrum title="Employee Type Master" subtitle="List of employee types">
        <a href="{{ route('master.employee.type.create') }}"
            class="btn btn-sm btn-primary d-inline-flex align-items-center justify-content-center gap-1 rounded-1 shadow-sm px-3 fw-semibold text-nowrap">
            <i class="material-icons material-symbols-rounded fs-6 lh-1" aria-hidden="true">add</i>
            <span>Add Employee Type</span>
        </a>
    </x-breadcrum>

    <div class="datatables">
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex justify-content-end mb-3">
                    <div class="etm-search-wrap">
                        <i class="material-icons material-symbols-rounded etm-search-icon" aria-hidden="true">search</i>
                        <input type="search" id="etmSearch" class="form-control" placeholder="Search"
                            aria-controls="employeetypemaster-table" autocomplete="off">
                    </div>
                </div>

                <div class="table-responsive rounded-2 border border-light-subtle">
                    {{ $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100', 'id' => 'employeetypemaster-table']) }}
                </div>

                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mt-3">
                    <div id="etmPaginationCell"></div>
                    <div class="d-flex align-items-center gap-1 flex-wrap">
                        <span class="text-muted small">Showing</span>
                        <select id="etmPerPage" class="form-select form-select-sm" style="width: 78px;"
                            aria-label="Rows per page">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="200">200</option>
                        </select>
                        <span id="etmTotalInfo" class="text-muted small">of 0 items</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{ $dataTable->scripts() }}
<script>
$(function () {
    var $tableEl = $('#employeetypemaster-table');
    if (!$tableEl.length || !$.fn.DataTable || !$.fn.DataTable.isDataTable($tableEl)) {
        return;
    }

    var table = $tableEl.DataTable();

    var $paginate = $('#employeetypemaster-table_wrapper .dataTables_paginate');
    if ($paginate.length) {
        $paginate.appendTo('#etmPaginationCell');
    }

    $tableEl.on('draw.dt', function () {
        var info = table.page.info();
        $('#etmTotalInfo').text('of ' + info.recordsTotal + ' items');
        $('#etmPerPage').val(table.page.len());
    }).trigger('draw.dt');

    $('#etmSearch').on('input', function () {
        clearTimeout(window._etmSearchTimer);
        var q = $(this).val();
        window._etmSearchTimer = setTimeout(function () {
            table.search(q).draw();
        }, 350);
    });

    $('#etmPerPage').on('change', function () {
        table.page.len(parseInt($(this).val(), 10)).draw();
    });
});
</script>
@endpush
