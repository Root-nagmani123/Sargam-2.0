@extends('admin.layouts.master')

@section('title', 'CENTCOM Complaints - Sargam | Lal Bahadur')

@section('css')
<style>
.table thead th {
    background-color: #f8f9fa;
    font-weight: 600;
    border-bottom: 2px solid #dee2e6;
}
</style>
@endsection

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="CENTCOM - Issues Assigned To You" />
    <div class="datatables">
        <div class="card" >
            <div class="card-body">
                <div class="row mb-2">
                    <div class="col-6">
                        <h4 class="mb-0">CENTCOM - Issues Assigned To You</h4>
                    </div>
                    <div class="col-6 text-end">
                        <a href="{{ route('admin.issue-management.create') }}" class="btn btn-primary">
                            <i class="bi bi-plus-circle-fill" aria-hidden="true"></i> Log New Issue
                        </a>
                    </div>
                </div>
                <hr>
                <div class="card-body">
                    <!-- Filters -->
                    <form method="GET" action="{{ route('admin.issue-management.centcom') }}" id="centcomFilterForm" class="mb-4 p-3 rounded border bg-light" onsubmit="return false;">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <iconify-icon icon="solar:filter-bold-duotone" class="text-primary"></iconify-icon>
                            <span class="fw-semibold small">Filters</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-md-6 col-lg-2">
                                <label class="form-label small fw-medium">Search</label>
                                <input type="text" id="centcomSearchFilter" class="form-control" placeholder="ID, description, category...">
                            </div>
                            <div class="col-12 col-md-4 col-lg-2">
                                <label class="form-label small fw-medium">Status</label>
                                <select id="centcomStatusFilter" class="form-select form-select-sm">
                                    <option value="">All Status</option>
                                    <option value="0">Reported</option>
                                    <option value="1">In Progress</option>
                                    <option value="2">Completed</option>
                                    <option value="3">Pending</option>
                                    <option value="6">Reopened</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-4 col-lg-2">
                                <label class="form-label small fw-medium">Category</label>
                                <select id="centcomCategoryFilter" class="form-select form-select-sm">
                                    <option value="">All Categories</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->pk }}">{{ $category->issue_category }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-4 col-lg-2">
                                <label class="form-label small fw-medium">Priority</label>
                                <select id="centcomPriorityFilter" class="form-select form-select-sm">
                                    <option value="">All Priorities</option>
                                    @foreach($priorities as $p)
                                        <option value="{{ $p->pk }}">{{ $p->priority ?? 'N/A' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-4 col-lg-2">
                                <label class="form-label small fw-medium">Date From</label>
                                <input type="date" id="centcomDateFromFilter" class="form-control">
                            </div>
                            <div class="col-12 col-md-4 col-lg-2">
                                <label class="form-label small fw-medium">Date To</label>
                                <input type="date" id="centcomDateToFilter" class="form-control">
                            </div>
                            <div class="col-12 col-lg-2 d-flex align-items-end gap-2">
                                <button type="button" id="centcomFilterApply" class="btn btn-primary btn-sm">
                                    <iconify-icon icon="solar:magnifer-bold"></iconify-icon> Filter
                                </button>
                                <button type="button" id="centcomFilterClear" class="btn btn-outline-secondary btn-sm">Clear</button>
                            </div>
                        </div>
                    </form>

                    <!-- Issues Table -->
                    <div class="table-responsive">
                        {!! $dataTable->table(['class' => 'table align-middle w-100']) !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
{!! $dataTable->scripts() !!}
<script>
$(document).on('preXhr.dt', '#centcomIssuesTable', function (e, settings, data) {
    data.search_filter = $('#centcomSearchFilter').val() || '';
    data.status_filter = $('#centcomStatusFilter').val() || '';
    data.category_filter = $('#centcomCategoryFilter').val() || '';
    data.priority_filter = $('#centcomPriorityFilter').val() || '';
    data.date_from_filter = $('#centcomDateFromFilter').val() || '';
    data.date_to_filter = $('#centcomDateToFilter').val() || '';
});

$(document).ready(function () {
    var $table = $('#centcomIssuesTable');

    function reload() {
        if ($.fn.DataTable.isDataTable('#centcomIssuesTable')) {
            $table.DataTable().ajax.reload();
        }
    }

    $('#centcomFilterApply').on('click', reload);

    $('#centcomSearchFilter').on('keypress', function (e) {
        if (e.which === 13) {
            e.preventDefault();
            reload();
        }
    });

    $('#centcomFilterClear').on('click', function () {
        $('#centcomFilterForm')[0].reset();
        reload();
    });
});
</script>
@endsection
