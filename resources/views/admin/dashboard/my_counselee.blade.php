@extends('admin.layouts.master')

@section('title', "My Counselee - Sargam | LBSNAA")

@section('content')
@php
    $counselees = $counselees ?? [];
@endphp
<div class="container-fluid py-3 py-md-4 px-3 px-md-4">
    <x-breadcrum title="My Counselee" />

    {{-- Page header --}}
    <div class="card">
        <div class="card-body">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div class="d-flex align-items-center gap-3">
            <div class="d-flex align-items-center gap-2">
                <span class="rounded-3 bg-primary bg-opacity-10 p-2 d-flex align-items-center justify-content-center">
                    <i class="bi bi-people-fill text-primary fs-5"></i>
                </span>
                <div>
                    <h1 class="h4 mb-0 fw-bold text-body-emphasis lh-sm">My Counselee</h1>
                    <p class="small text-body-secondary mb-0 mt-1">View and manage your assigned counselees</p>
                </div>
            </div>
        </div>
        @if(count($counselees) > 0)
        <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2 fw-semibold">{{ count($counselees) }} counselee{{ count($counselees) !== 1 ? 's' : '' }}</span>
        @endif
    </div>
</div>
    </div>

    @if(count($counselees) === 0)
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body text-center py-5 px-4">
            <span class="rounded-circle bg-body-tertiary d-inline-flex align-items-center justify-content-center mb-3" style="width: 4rem; height: 4rem;">
                <i class="bi bi-person-x fs-2 text-body-secondary"></i>
            </span>
            <h5 class="fw-semibold text-body-emphasis mb-2">No counselees assigned</h5>
            <p class="text-body-secondary small mb-0">You don’t have any counselees to display yet.</p>
        </div>
    </div>
    @else
    <div class="d-flex flex-column gap-4">
        @foreach($counselees as $c)
        <article class="card border-0 shadow-sm rounded-4 overflow-hidden transition-all" style="border-left: 4px solid var(--bs-primary); transition: box-shadow 0.2s ease, transform 0.2s ease;">
            <div class="card-body p-4 p-md-4">
                {{-- Header: Avatar + Name/ID + Service/Cadre + Email/Date --}}
                <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
                    <div class="position-relative flex-shrink-0">
                        <div class="rounded-circle overflow-hidden bg-body-tertiary ratio ratio-1x1" style="width: 64px; height: 64px;">
                            @if($c['photo'] ?? null)
                                <img src="{{ $c['photo'] }}" alt="{{ $c['name'] }}" class="object-fit-cover">
                            @else
                                <div class="d-flex align-items-center justify-content-center text-body-secondary fw-bold fs-4">{{ substr($c['name'], 0, 1) }}</div>
                            @endif
                        </div>
                        @if($c['active'] ?? false)
                        <span class="position-absolute bottom-0 start-0 rounded-pill bg-success border border-2 border-white" style="width: 12px; height: 12px;" aria-hidden="true"></span>
                        @endif
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                            <h2 class="h5 mb-0 fw-bold text-body-emphasis lh-sm">{{ $c['name'] }}</h2>
                            <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-semibold">{{ $c['id'] }}</span>
                        </div>
                        <p class="mb-2 small text-body-secondary lh-sm">{{ $c['service'] }} <span class="text-body-tertiary">|</span> {{ $c['cadre'] }}</p>
                        <div class="d-flex flex-wrap align-items-center gap-3 small text-body-secondary">
                            <span class="d-inline-flex align-items-center gap-1 text-break">
                                <i class="bi bi-envelope flex-shrink-0"></i>
                                <span>{{ $c['email'] }}</span>
                            </span>
                            <span class="d-inline-flex align-items-center gap-1">
                                <i class="bi bi-calendar3-event flex-shrink-0"></i>
                                <span>{{ $c['fc_date'] }}</span>
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Summary stats --}}
                <div class="row g-0 align-items-center border-top border-bottom border-secondary-subtle py-3 mb-4">
                    <div class="col-auto pe-3 pe-md-4 border-end border-secondary-subtle">
                        <span class="badge bg-primary text-white px-3 py-2 rounded-pill fw-semibold">{{ $c['phase_badge'] }}</span>
                    </div>
                    <div class="col">
                        <div class="row g-0 g-md-3 align-items-center justify-content-md-start">
                            <div class="col-4 col-md-auto text-center text-md-start py-2 py-md-0">
                                <div class="small text-body-secondary text-uppercase fw-semibold">Attendance</div>
                                <div class="fw-bold text-primary fs-5 lh-1">{{ $c['attendance'] }}%</div>
                            </div>
                            <div class="col-4 col-md-auto text-center text-md-start py-2 py-md-0">
                                <div class="small text-body-secondary text-uppercase fw-semibold">Memos</div>
                                <div class="fw-bold text-primary fs-5 lh-1">{{ $c['memos'] }}</div>
                            </div>
                            <div class="col-4 col-md-auto text-center text-md-start py-2 py-md-0">
                                <div class="small text-body-secondary text-uppercase fw-semibold">Exemptions</div>
                                <div class="fw-bold text-primary fs-5 lh-1">{{ $c['exemptions'] }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Phase progress timeline --}}
                <div class="mb-4">
                    <p class="small text-body-secondary text-uppercase fw-semibold mb-3">Phase progress</p>
                    <div class="d-flex flex-wrap align-items-end gap-2 gap-md-3">
                        @foreach($c['phases'] as $phase)
                        <div class="d-flex flex-column align-items-center text-center flex-shrink-0" style="min-width: 64px;">
                            @if(($phase['status'] ?? '') === 'completed')
                                <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center mb-2 flex-shrink-0" style="width: 40px; height: 40px;">
                                    <i class="bi bi-check-lg" aria-hidden="true"></i>
                                </div>
                                <span class="small fw-medium text-body-emphasis">{{ $phase['name'] }}</span>
                                <span class="small text-body-secondary">GPA: {{ $phase['gpa'] ?? '—' }}</span>
                            @elseif(($phase['status'] ?? '') === 'active')
                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mb-2 flex-shrink-0" style="width: 40px; height: 40px;">
                                    <i class="bi bi-clock" aria-hidden="true"></i>
                                </div>
                                <span class="small fw-medium text-body-emphasis">{{ $phase['name'] }}</span>
                                <span class="small text-primary fw-semibold">{{ $phase['label'] ?? 'Active' }}</span>
                            @else
                                <div class="rounded-circle border border-2 border-secondary-subtle bg-body d-flex align-items-center justify-content-center mb-2 flex-shrink-0" style="width: 40px; height: 40px;">
                                    <span class="visually-hidden">Upcoming</span>
                                </div>
                                <span class="small text-body-secondary">{{ $phase['name'] }}</span>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- View Details --}}
                <div class="text-center pt-2 border-top border-secondary-subtle">
                    <a href="#" class="btn btn-link text-primary text-decoration-none fw-semibold d-inline-flex align-items-center gap-1 rounded-2 px-3 py-2">
                        View Details
                        <i class="bi bi-chevron-down small" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
        </article>
        @endforeach
    </div>
    @endif
</div>

@push('styles')
<style>
.card.overflow-hidden:hover { box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08) !important; }
</style>
@endpush
@endsection
