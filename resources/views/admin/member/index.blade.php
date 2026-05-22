@extends('admin.layouts.master')

@section('title', 'Member')

@section('setup_content')
<style>
#member-table_wrapper .dataTables_length,
#member-table_wrapper .dataTables_filter,
#member-table_wrapper .dataTables_info,
#member-table_wrapper .dt-buttons {
    display: none !important;
}

#member-table_wrapper .dataTables_paginate {
    margin-top: 0 !important;
    display: flex;
    align-items: center;
    gap: 2px;
}

#member-table_wrapper .paginate_button {
    border: none !important;
    background: transparent !important;
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 0.8125rem;
    cursor: pointer;
    color: #495057 !important;
}

#member-table_wrapper .paginate_button:hover:not(.disabled) {
    background: #f1f3f5 !important;
}

#member-table_wrapper .paginate_button.current,
#member-table_wrapper .paginate_button.current:hover {
    background: #1b3a5c !important;
    color: #fff !important;
    border-radius: 4px;
}

#member-table_wrapper .paginate_button.disabled {
    opacity: 0.35;
    cursor: default;
}

#member-table_wrapper .ellipsis {
    padding: 5px 4px;
    color: #adb5bd;
}

.mem-search-wrap {
    position: relative;
    width: min(100%, 260px);
}

.mem-search-wrap .form-control {
    border-radius: 8px;
    border: 1px solid #dee2e6;
    padding-left: 38px;
    font-size: 0.875rem;
}

.mem-search-wrap .mem-search-icon {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: #adb5bd;
    font-size: 18px;
    pointer-events: none;
}

#member-table {
    margin-bottom: 0;
}

#member-table thead th {
    background-color: #f8f9fa;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #6c757d;
    border-bottom: 2px solid #dee2e6;
    padding: 12px 16px;
    white-space: nowrap;
    vertical-align: middle;
}

#member-table tbody td {
    font-size: 0.875rem;
    padding: 12px 16px;
    vertical-align: middle;
    border-bottom: 1px solid #f1f3f5;
    color: #212529;
}

#member-table tbody tr:hover td {
    background-color: #fafbfc;
}

.mem-action-btn {
    background: none;
    border: none;
    padding: 3px 5px;
    cursor: pointer;
    line-height: 1;
    display: inline-flex;
    align-items: center;
    text-decoration: none;
}

.mem-action-btn .material-symbols-rounded {
    font-size: 20px;
}

.mem-action-btn:hover {
    opacity: 0.75;
}

.mem-action-btn.text-danger:hover {
    opacity: 0.85;
}
</style>

<div class="container-fluid px-3 px-lg-4">
    <x-breadcrum title="Member" subtitle="List of members">
        <a href="{{ route('member.create') }}"
            class="btn btn-sm btn-primary d-inline-flex align-items-center justify-content-center gap-1 rounded-1 shadow-sm px-3 fw-semibold text-nowrap">
            <i class="material-icons material-symbols-rounded fs-6 lh-1" aria-hidden="true">add</i>
            <span>Add Member</span>
        </a>
        <a href="{{ route('member.excel.export') }}"
            class="btn btn-sm btn-secondary d-inline-flex align-items-center justify-content-center gap-1 rounded-1 shadow-sm px-3 fw-semibold text-nowrap">
            <i class="material-icons material-symbols-rounded fs-6 lh-1" aria-hidden="true">file_export</i>
            <span>Export</span>
        </a>
    </x-breadcrum>

    <div class="datatables">
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-body p-3 p-md-4">
                <div class="modal fade" id="vertical-center-scroll-modal" tabindex="-1"
                    aria-labelledby="vertical-center-modal" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                        <div class="modal-content rounded-3 border-0 shadow">
                            <div class="modal-header d-flex align-items-center border-0 pb-0">
                                <h4 class="modal-title fs-6 fw-bold text-primary-emphasis" id="myLargeModalLabel">
                                    Bulk Upload for member
                                </h4>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body pt-2">
                                <form action="" method="POST">
                                    <label for="file" class="form-label small fw-medium">Upload CSV</label>
                                    <input type="file" name="file" id="file" class="form-control rounded-2">
                                </form>
                            </div>
                            <div class="modal-footer border-0 pt-0">
                                <button type="submit"
                                    class="btn btn-success btn-sm px-4 fw-semibold">
                                    Submit
                                </button>
                                <button type="button"
                                    class="btn btn-outline-secondary btn-sm px-4"
                                    data-bs-dismiss="modal">
                                    Close
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end mb-3">
                    <div class="mem-search-wrap">
                        <i class="material-icons material-symbols-rounded mem-search-icon" aria-hidden="true">search</i>
                        <input type="search" id="memSearch" class="form-control" placeholder="Search"
                            aria-controls="member-table" autocomplete="off">
                    </div>
                </div>

                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100', 'id' => 'member-table']) !!}
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
    var $tableEl = $('#member-table');
    if (!$tableEl.length || !$.fn.DataTable || !$.fn.DataTable.isDataTable($tableEl)) {
        return;
    }

    var table = $tableEl.DataTable();

    var $paginate = $('#member-table_wrapper .dataTables_paginate');
    if ($paginate.length) {
        $paginate.appendTo('#memPaginationCell');
    }

    $tableEl.on('draw.dt', function () {
        var info = table.page.info();
        $('#memTotalInfo').text('of ' + info.recordsTotal + ' items');
        $('#memPerPage').val(table.page.len());
    }).trigger('draw.dt');

    $('#memSearch').on('input', function () {
        clearTimeout(window._memSearchTimer);
        var q = $(this).val();
        window._memSearchTimer = setTimeout(function () {
            table.search(q).draw();
        }, 350);
    });

    $('#memPerPage').on('change', function () {
        table.page.len(parseInt($(this).val(), 10)).draw();
    });
});
</script>
@endpush
