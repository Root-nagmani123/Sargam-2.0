@extends('admin.layouts.master')
@section('title', 'Activity status')

@push('styles')
<style>
    .fc-status-hero {
        background: linear-gradient(135deg, rgba(26, 60, 110, 0.06) 0%, rgba(26, 60, 110, 0.02) 100%);
        border: 1px solid rgba(26, 60, 110, 0.12);
        border-radius: 12px;
        padding: 1.25rem 1.5rem;
    }
    .fc-status-dept-card {
        border-radius: 12px;
        border: 1px solid rgba(0, 0, 0, 0.06) !important;
        transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
    }
    a.text-decoration-none:hover .fc-status-dept-card {
        transform: translateY(-3px);
        box-shadow: 0 0.65rem 1.35rem rgba(26, 60, 110, 0.14) !important;
        border-color: rgba(26, 60, 110, 0.22) !important;
    }
    .fc-status-dept-icon {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        background: rgba(26, 60, 110, 0.1);
        color: #1a3c6e;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
        flex-shrink: 0;
    }
</style>
@endpush

@section('setup_content')
<div class="container-fluid px-3">
    <x-breadcrum title="FC Activities — Status"></x-breadcrum>

    <div class="fc-status-hero mb-4">
        <h4 class="fw-bold mb-2" style="color: #1a3c6e;">Post-arrival status</h4>
        <p class="text-muted mb-0 small lh-lg">
            Open a department to view all active trainees and each post-arrival activity column.
            Values come from submitted activity records; empty cells mean nothing recorded yet.
        </p>
        @if($departments->isNotEmpty())
            <div class="d-flex flex-wrap gap-2 mt-3">
                <span class="badge rounded-pill bg-white text-dark border fw-normal px-3 py-2">
                    <i class="bi bi-building me-1 text-primary"></i>{{ $departments->count() }} department{{ $departments->count() === 1 ? '' : 's' }}
                </span>
            </div>
        @endif
    </div>

    @if($departments->isEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-inbox display-4 text-muted d-block mb-3"></i>
                <p class="text-muted mb-3 mb-md-0">No departments are available for your account.</p>
                <a href="{{ route('fc-reg.admin.activities.index') }}" class="btn btn-sm btn-primary">Back to activities</a>
            </div>
        </div>
    @else
        <h6 class="text-uppercase text-muted fw-semibold small mb-3" style="letter-spacing: 0.04em;">Choose department</h6>
        <div class="row g-3">
            @foreach($departments as $d)
                <div class="col-md-6 col-xl-4">
                    <a class="text-decoration-none text-body"
                       href="{{ route('fc-reg.admin.activities.status.grid', $d->code) }}">
                        <div class="card fc-status-dept-card shadow-sm h-100">
                            <div class="card-body d-flex align-items-center gap-3 py-3">
                                <div class="fc-status-dept-icon">
                                    <i class="bi bi-folder2-open"></i>
                                </div>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-semibold mb-0 text-truncate" style="color: #1a3c6e;">{{ $d->name }}</div>
                                    <div class="small text-muted">Code <code class="small bg-light px-1 rounded">{{ $d->code }}</code></div>
                                </div>
                                <i class="bi bi-chevron-right text-muted flex-shrink-0"></i>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
