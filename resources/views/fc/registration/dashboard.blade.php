@extends('admin.layouts.master')
@section('title', 'Dashboard – FC Registration')

@section('setup_content')
@php
    $progress = fc_registration_progress_view($progress ?? null);
@endphp
<div class="row g-3">

    <!-- ── Page Header ─────────────────────────────────────────────── -->
    <div class="col-12">
        <div class="card border-0 shadow-sm" style="background:linear-gradient(90deg,#1a3c6e,#2e6da4);color:#fff;border-radius:10px;">
            <div class="card-body py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0 fw-bold">
                        <i class="bi bi-mortarboard-fill me-2"></i>
                        Welcome, {{ $step1->full_name ?? Auth::user()->username }}
                    </h5>
                    <small class="opacity-75">
                        {{ $step1->service?->service_name ?? '' }}
                        {{-- {{ $step1->cadre ? '| Cadre: '.$step1->cadre : '' }} --}}
                        {{-- {{ $step1->session?->session_name ? '| Session: '.$step1->session->session_name : '' }} --}}
                    </small>
                </div>
                <div class="text-end">
                    <div class="fs-4 fw-bold">{{ $progress['percentage'] }}%</div>
                    <small class="opacity-75">Registration Complete</small>
                </div>
            </div>
            <!-- Overall progress bar -->
            <div class="px-4 pb-3">
                <div class="progress" style="height:8px;border-radius:4px;background:rgba(255,255,255,0.3);">
                    <div class="progress-bar bg-warning"
                         style="width:{{ $progress['percentage'] }}%;transition:width 0.6s ease;border-radius:4px;">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Registration Steps Grid ─────────────────────────────────── -->
    @php
        $steps = [
            ['key'=>'step1',     'label'=>'Step 1: Basic Info',      'icon'=>'bi-person-fill',          'route'=>'fc-reg.registration.step1',     'desc'=>'Name, DOB, Service, Cadre'],
            ['key'=>'step2',     'label'=>'Step 2: Personal Details', 'icon'=>'bi-card-list',            'route'=>'fc-reg.registration.step2',     'desc'=>'Address, Category, Emergency contact'],
            ['key'=>'step3',     'label'=>'Step 3: Other Details',    'icon'=>'bi-journal-text',         'route'=>'fc-reg.registration.step3',     'desc'=>'Qualifications, Employment, Languages'],
            ['key'=>'bank',      'label'=>'Bank Details',             'icon'=>'bi-bank',                 'route'=>'fc-reg.registration.bank',      'desc'=>'Bank account for stipend payment'],
            ['key'=>'travel',    'label'=>'Travel Plan',              'icon'=>'bi-train-front',          'route'=>'fc-reg.registration.travel',    'desc'=>'Journey to Mussoorie and pickup preferences'],
            ['key'=>'documents', 'label'=>'Document Upload',          'icon'=>'bi-file-earmark-arrow-up','route'=>'fc-reg.registration.documents', 'desc'=>'Joining documents checklist'],
            ['key'=>'confirmed', 'label'=>'Declaration & Submit',     'icon'=>'bi-check-circle-fill',    'route'=>'fc-reg.registration.status',    'desc'=>'Final declaration & submission'],
        ];
    @endphp

    @foreach($steps as $i => $step)
        @php
            $done    = $progress['steps'][$step['key']] ?? false;
            $prevKey = $i > 0 ? $steps[$i-1]['key'] : null;
            $prevDone= $prevKey ? ($progress['steps'][$prevKey] ?? false) : true;
            $isNext  = !$done && $prevDone;
        @endphp
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 border-0 shadow-sm
                 {{ $done ? 'border-start border-success border-3' : ($isNext ? 'border-start border-warning border-3' : '') }}"
                 style="border-radius:10px;">
                <div class="card-body p-3 d-flex align-items-start gap-3">

                    <!-- Icon badge -->
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:48px;height:48px;
                         background:{{ $done ? '#d4edda' : ($isNext ? '#fff3cd' : '#f0f0f0') }};">
                        <i class="bi {{ $step['icon'] }} fs-5
                           {{ $done ? 'text-success' : ($isNext ? 'text-warning' : 'text-muted') }}"></i>
                    </div>

                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start">
                            <h6 class="fw-bold mb-1 small">{{ $step['label'] }}</h6>
                            @if($done)
                                <span class="badge bg-success-subtle text-success rounded-pill small">
                                    <i class="bi bi-check2"></i> Done
                                </span>
                            @elseif($isNext)
                                <span class="badge bg-warning-subtle text-warning rounded-pill small">
                                    Pending
                                </span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary rounded-pill small">
                                    Locked
                                </span>
                            @endif
                        </div>
                        <p class="text-muted mb-2" style="font-size:0.78rem;">{{ $step['desc'] }}</p>
                        @if($done)
                            <a href="{{ route($step['route']) }}" class="btn btn-sm btn-outline-success py-0 px-2">
                                <i class="bi bi-pencil me-1"></i>Edit
                            </a>
                        @elseif($isNext)
                            <a href="{{ route($step['route']) }}" class="btn btn-sm btn-warning py-0 px-2">
                                <i class="bi bi-arrow-right-circle me-1"></i>Start Now
                            </a>
                        @else
                            <button class="btn btn-sm btn-outline-secondary py-0 px-2" disabled>
                                <i class="bi bi-lock me-1"></i>Complete previous step
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    <!-- ── Info Panel ───────────────────────────────────────────────── -->
    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm h-100" style="border-radius:10px;">
            <div class="card-header bg-white border-0 fw-bold pt-3 pb-1">
                <i class="bi bi-info-circle-fill text-primary me-2"></i>Instructions
            </div>
            <div class="card-body small text-muted pt-2">
                <ul class="ps-3 mb-0">
                    <li class="mb-2">Complete all steps in sequence. Each step must be saved before proceeding to the next.</li>
                    <li class="mb-2">Upload clear scanned copies of documents (PDF/JPG, max 5MB each).</li>
                    <li class="mb-2">Ensure bank account details are accurate for stipend disbursement.</li>
                    <li class="mb-2">Final submission is <strong>irreversible</strong>. Review all details carefully.</li>
                    <li class="mb-2">For technical issues contact: <a href="mailto:ict@lbsnaa.gov.in">ict@lbsnaa.gov.in</a></li>
                </ul>
            </div>
        </div>
    </div>

</div>
@endsection
