@extends('admin.layouts.master')

@section('title', 'Define Pay Scale - Sargam')

@section('setup_content')
<div class="container-fluid px-2 px-sm-3 px-md-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Protocol</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.estate.request-for-others') }}">Estate Management</a></li>
            <li class="breadcrumb-item active" aria-current="page">Define Pay Scale</li>
        </ol>
    </nav>

    <x-session_message />

    <div class="card shadow-sm border-0" style="border-left: 4px solid #0d6efd;">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
                <div>
                    <h1 class="h4 fw-bold text-dark mb-1">Define Pay Scale</h1>
                    <p class="text-muted small mb-0">Manage pay scale range and level for eligibility mapping.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.estate.define-pay-scale.create') }}" class="btn btn-success"><i class="bi bi-plus-lg me-1"></i> Add New</a>
                    <button type="button" class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer"></i></button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover align-middle mb-0">
                    <thead class="table-primary">
                        <tr>
                            <th class="text-center">S.NO.</th>
                            <th>Pay Scale Range</th>
                            <th>Level</th>
                            <th>Display Label</th>
                            <th class="text-center" style="width: 80px;">EDIT</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $index => $row)
                        <tr>
                            <td class="text-center">{{ $items->firstItem() + $index }}</td>
                            <td>{{ $row->pay_scale_range }}</td>
                            <td>{{ $row->pay_scale_level }}</td>
                            <td>{{ $row->display_label_text }}</td>
                            <td class="text-center">
                                <a href="{{ route('admin.estate.define-pay-scale.edit', $row->pk) }}" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No pay scale found. <a href="{{ route('admin.estate.define-pay-scale.create') }}">Add one</a> to use in Eligibility Criteria.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($items->hasPages())
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2 mt-3">
                <div class="text-muted small">Showing {{ $items->firstItem() }} to {{ $items->lastItem() }} of {{ $items->total() }} entries</div>
                {{ $items->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
