@extends('admin.layouts.master')
@section('title', 'Registration Status')
@section('setup_content')
@php
    $progress = fc_registration_progress_view($progress ?? null);
@endphp
<div class="row justify-content-center">
<div class="col-12 col-xl-9">

    @include('partials.step-indicator', ['current' => 7])

    <!-- Status Banner -->
    @php
        $isSubmitted  = $master?->status === 'SUBMITTED';
        $isConfirmed  = $confirmation?->declaration_accepted;
    @endphp
    <div class="alert {{ $isSubmitted ? ($isConfirmed ? 'alert-success' : 'alert-warning') : 'alert-info' }} border-0 shadow-sm mb-3" style="border-radius:10px;">
        <div class="d-flex align-items-center gap-3">
            <i class="bi {{ $isConfirmed ? 'bi-patch-check-fill' : ($isSubmitted ? 'bi-hourglass-split' : 'bi-info-circle-fill') }} fs-3"></i>
            <div>
                <strong>
                    @if($isConfirmed)    Registration Confirmed ✓
                    @elseif($isSubmitted) Registration Submitted – Declaration Pending
                    @else                Registration Incomplete
                    @endif
                </strong>
                <div class="small mt-1">
                    @if($isConfirmed)
                        Your registration is complete. Declaration accepted on {{ $confirmation?->confirmed_at?->format('d M Y, H:i') ?? '—' }}.
                    @elseif($isSubmitted)
                        All steps completed. Please read and accept the declaration below.
                    @else
                        Please complete all steps ({{ $progress['done'] }}/{{ $progress['total'] }} done).
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Progress bar -->
    <div class="card border-0 shadow-sm mb-3" style="border-radius:10px;">
        <div class="card-body py-3 px-4">
            <div class="d-flex justify-content-between small fw-semibold mb-1">
                <span>Overall Progress</span><span>{{ $progress['percentage'] }}%</span>
            </div>
            <div class="progress" style="height:10px;border-radius:5px;">
                <div class="progress-bar bg-success" style="width:{{ $progress['percentage'] }}%"></div>
            </div>
        </div>
    </div>

    <!-- Summary Table -->
    <div class="card border-0 shadow-sm mb-3" style="border-radius:10px;">
        <div class="card-header bg-white fw-bold py-3 px-4 border-bottom">
            <i class="bi bi-clipboard-check me-2"></i>Registration Summary
        </div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0 small">
                <tbody>
                    <tr>
                        <td class="ps-4 text-muted" width="35%">Full Name</td>
                        <td class="fw-semibold">{{ $step1?->full_name ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="ps-4 text-muted">Service</td>
                        <td>{{ $step1?->service?->service_name ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="ps-4 text-muted">Cadre / State</td>
                        <td>{{ $step1?->cadre ?? '—' }} / {{ $step1?->allottedState?->state_name ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="ps-4 text-muted">FC Session</td>
                        <td>{{ $step1?->session?->session_name ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="ps-4 text-muted">Mobile / Email</td>
                        <td>{{ $step1?->mobile_no ?? '—' }} / {{ $step1?->email ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="ps-4 text-muted">Category</td>
                        <td>{{ $step2?->category?->category_name ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="ps-4 text-muted">Bank Account</td>
                        <td>{{ $bank ? $bank->bank_name.' – '.$bank->account_no : '—' }}</td>
                    </tr>
                    <tr>
                        <td class="ps-4 text-muted">Documents Uploaded</td>
                        <td>{{ $documents->where('is_uploaded',1)->count() }} of {{ $documents->count() }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Step checklist -->
    <div class="card border-0 shadow-sm mb-3" style="border-radius:10px;">
        <div class="card-body px-4 py-3">
            <div class="row g-2">
                @foreach([
                    ['step1','Step 1: Basic Info','fc-reg.registration.step1'],
                    ['step2','Step 2: Personal Details','fc-reg.registration.step2'],
                    ['step3','Step 3: Other Details','fc-reg.registration.step3'],
                    ['bank','Bank Details','fc-reg.registration.bank'],
                    ['travel','Travel Plan','fc-reg.registration.travel'],
                    ['documents','Documents','fc-reg.registration.documents'],
                    ['confirmed','Declaration','fc-reg.registration.status'],
                ] as [$key,$label,$route])
                    @php $done = $progress['steps'][$key] ?? false; @endphp
                    <div class="col-md-6">
                        <div class="d-flex align-items-center gap-2 py-1">
                            <i class="bi {{ $done ? 'bi-check-circle-fill text-success' : 'bi-circle text-muted' }}"></i>
                            <span class="{{ $done ? '' : 'text-muted' }} small">{{ $label }}</span>
                            @if(!$done)
                                <a href="{{ route($route) }}" class="ms-auto btn btn-xs btn-outline-warning py-0 px-2"
                                   style="font-size:0.7rem;">Fill Now</a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Declaration Form -->
    @if($isSubmitted && !$isConfirmed)
        <div class="card border-0 shadow-sm border-warning" style="border-radius:10px;border-width:2px!important;">
            <div class="card-header bg-warning-subtle py-3 px-4 fw-bold border-bottom">
                <i class="bi bi-pen me-2"></i>Declaration
            </div>
            <div class="card-body p-4">
                <div class="bg-light rounded p-3 mb-3 small" style="line-height:1.7;max-height:200px;overflow-y:auto;">
                    I hereby declare that the information furnished in this registration form is true, complete and correct
                    to the best of my knowledge and belief. I understand that in the event of any information being found
                    false or incorrect or any ineligibility being detected before or after the Foundation Course, my
                    registration is liable to be cancelled. I also undertake to abide by the rules, regulations and code of
                    conduct of Lal Bahadur Shastri National Academy of Administration, Mussoorie.
                </div>
                <form method="POST" action="{{ route('fc-reg.registration.confirm') }}">
                    @csrf
                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" id="declaration" name="declaration"
                               value="1" required>
                        <label class="form-check-label fw-semibold" for="declaration">
                            I have read and agree to the above declaration.
                        </label>
                    </div>
                    @error('declaration')
                        <div class="text-danger small mb-2">{{ $message }}</div>
                    @enderror
                    <button type="submit" class="btn btn-success px-4">
                        <i class="bi bi-patch-check me-2"></i>Accept &amp; Confirm Registration
                    </button>
                </form>
            </div>
        </div>
    @endif

    @if($isConfirmed)
        <div class="text-center mt-3">
            <a href="#" onclick="window.print()" class="btn btn-outline-primary me-2">
                <i class="bi bi-printer me-1"></i>Print Registration
            </a>
            <a href="{{ route('fc-reg.dashboard') }}" class="btn btn-outline-secondary">
                <i class="bi bi-house me-1"></i>Dashboard
            </a>
        </div>
    @endif

</div>
</div>
@endsection
