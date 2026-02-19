@extends('admin.layouts.master')

@section('title', 'Eligibility - Criteria - Sargam')

@section('setup_content')
<div class="container-fluid px-2 px-sm-3 px-md-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Protocol</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.estate.request-for-others') }}">Estate Management</a></li>
            <li class="breadcrumb-item active" aria-current="page">Eligibility - Criteria</li>
        </ol>
    </nav>

    <x-session_message />

    <div class="card shadow-sm border-0" style="border-left: 4px solid #0d6efd;">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
                <div>
                    <h1 class="h4 fw-bold text-dark mb-1">Eligibility - Criteria</h1>
                    <p class="text-muted small mb-0">This page displays all the Estate Eligibility Block Mapping added in the system and provides options such as add, edit, delete, excel upload, print etc.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.estate.eligibility-criteria.create') }}" class="btn btn-success"><i class="bi bi-plus-lg me-1"></i> Add New</a>
                    <button type="button" class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer"></i></button>
                </div>
            </div>

            <div class="row align-items-center gap-2 mb-3">
                <div class="col-auto">
                    <label class="form-label mb-0">Show</label>
                    <select id="perPage" class="form-select form-select-sm d-inline-block w-auto">
                        <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10</option>
                        <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                        <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                    </select>
                    <span class="ms-1">entries</span>
                </div>
                <div class="col ms-auto">
                    <label class="form-label mb-0 me-2">Search within table:</label>
                    <input type="search" class="form-control form-control-sm d-inline-block w-auto" id="searchTable" placeholder="Search...">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover align-middle mb-0">
                    <thead class="table-primary">
                        <tr>
                            <th class="text-center" style="width: 40px;"><input type="checkbox" class="form-check-input" id="selectAll" aria-label="Select all"></th>
                            <th class="text-center">S.NO.</th>
                            <th>PAY SCALE</th>
                            <th>UNIT TYPE</th>
                            <th>UNIT SUB TYPE</th>
                            <th class="text-center" style="width: 80px;">EDIT</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $index => $row)
                        <tr>
                            <td class="text-center"><input type="checkbox" class="form-check-input row-check" value="{{ $row->pk }}"></td>
                            <td class="text-center">{{ $items->firstItem() + $index }}</td>
                            <td>{{ $row->payScale ? $row->payScale->display_label_text : '-' }}</td>
                            <td>{{ $row->unitType->name ?? '-' }}</td>
                            <td>{{ $row->unitSubType->name ?? '-' }}</td>
                            <td class="text-center">
                                <a href="{{ route('admin.estate.eligibility-criteria.edit', $row->pk) }}" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No eligibility mapping found. Add Pay Scale, Unit Type and Unit Sub Type first, then <a href="{{ route('admin.estate.eligibility-criteria.create') }}">add mapping</a>.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($items->hasPages())
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2 mt-3">
                <div class="text-muted small">Showing {{ $items->firstItem() }} to {{ $items->lastItem() }} of {{ $items->total() }} entries</div>
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item {{ $items->onFirstPage() ? 'disabled' : '' }}"><a class="page-link" href="{{ $items->url(1) }}">First</a></li>
                    <li class="page-item {{ $items->onFirstPage() ? 'disabled' : '' }}"><a class="page-link" href="{{ $items->previousPageUrl() }}">Previous</a></li>
                    @foreach($items->getUrlRange(max(1, $items->currentPage() - 2), min($items->lastPage(), $items->currentPage() + 2)) as $page => $url)
                    <li class="page-item {{ $page == $items->currentPage() ? 'active' : '' }}"><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>
                    @endforeach
                    <li class="page-item {{ !$items->hasMorePages() ? 'disabled' : '' }}"><a class="page-link" href="{{ $items->nextPageUrl() }}">Next</a></li>
                    <li class="page-item {{ !$items->hasMorePages() ? 'disabled' : '' }}"><a class="page-link" href="{{ $items->url($items->lastPage()) }}">Last</a></li>
                </ul>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var selectAll = document.getElementById('selectAll');
    var rowChecks = document.querySelectorAll('.row-check');
    var btnDelete = document.getElementById('btnDeleteSelected');
    if (btnDelete) {
        function update() { btnDelete.disabled = document.querySelectorAll('.row-check:checked').length === 0; }
        if (selectAll) selectAll.addEventListener('change', function() { rowChecks.forEach(function(c) { c.checked = selectAll.checked; }); update(); });
        rowChecks.forEach(function(c) { c.addEventListener('change', update); });
    }
});
</script>
@endpush
