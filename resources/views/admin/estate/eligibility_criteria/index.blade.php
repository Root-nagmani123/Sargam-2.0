@extends('admin.layouts.master')

@section('title', 'Eligibility - Criteria - Sargam')

@section('setup_content')
<div class="container-fluid px-2 px-sm-3 px-md-4">
<x-breadcrum title="Eligibility - Criteria" />

    <x-session_message />

        <div class="card">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
                <div>
                    <h1 class="h4 fw-bold text-dark mb-1">Eligibility - Criteria</h1>
                    <p class="text-muted small mb-0">This page displays all the Estate Eligibility Block Mapping added in the system and provides options such as add, edit, delete, excel upload, print etc.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.estate.eligibility-criteria.create') }}" class="btn btn-primary"><i class="material-icons material-symbols-rounded">add</i> Add New</a>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="border-0 ps-3 fw-semibold text-secondary">S.No.</th>
                            <th class="border-0 fw-semibold text-secondary">Pay Scale</th>
                            <th class="border-0 fw-semibold text-secondary">Unit Type</th>
                            <th class="border-0 fw-semibold text-secondary">Unit Sub Type</th>
                            <th class="border-0 pe-3 fw-semibold text-secondary text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $index => $row)
                        <tr>
                            <td class="ps-3">{{ $items->firstItem() + $index }}</td>
                            <td class="fw-medium">{{ $row->salaryGrade ? $row->salaryGrade->display_label_text : '-' }}</td>
                            <td class="fw-medium">{{ $row->unitType->name ?? '-' }}</td>
                            <td class="fw-medium">{{ $row->unitSubType->name ?? '-' }}</td>
                            <td class="pe-3 text-end">
                                <a href="{{ route('admin.estate.eligibility-criteria.edit', $row->pk) }}" class="text-primary" title="Edit">
                                    <i class="material-icons material-symbols-rounded">edit</i>
                                </a>
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
