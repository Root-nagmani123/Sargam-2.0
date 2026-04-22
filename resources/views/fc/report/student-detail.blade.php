@extends('admin.layouts.master')
@section('title', 'Student Profile – ' . ($step1->full_name ?? $username))

@section('setup_content')
<div class="container-fluid px-3">

    {{-- Breadcrumb + Actions --}}
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="{{ route('admin.reports.overview') }}">Reports</a></li>
                    <li class="breadcrumb-item active">{{ $step1->full_name ?? $username }}</li>
                </ol>
            </nav>
        </div>
        <button onclick="window.print()" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-printer me-1"></i>Print
        </button>
    </div>

    {{-- Profile Header --}}
    <div class="card border-0 shadow-sm mb-3" style="border-radius:10px;background:linear-gradient(90deg,#1a3c6e,#2e6da4);color:#fff;">
        <div class="card-body py-3 px-4 d-flex align-items-center gap-4 flex-wrap">
            {{-- Photo --}}
            <div class="flex-shrink-0">
                @php $photoUrl = view_file_link($step1->photo_path); @endphp
                @if($photoUrl)
                    <img src="{{ $photoUrl }}" alt="Photo"
                         style="width:72px;height:72px;object-fit:cover;border-radius:8px;border:2px solid rgba(255,255,255,.5);">
                @else
                    <div style="width:72px;height:72px;border-radius:8px;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;">
                        <i class="bi bi-person-fill fs-2" style="opacity:.7;"></i>
                    </div>
                @endif
            </div>
            <div class="flex-grow-1">
                <h5 class="fw-bold mb-1">{{ $step1->full_name ?? '—' }}</h5>
                <div style="font-size:.82rem;opacity:.85;">
                    <span class="me-3"><i class="bi bi-person-badge me-1"></i>{{ $username }}</span>
                    <span class="me-3"><i class="bi bi-briefcase me-1"></i>{{ $step1->service?->service_name ?? '—' }} ({{ $step1->service?->service_code ?? '—' }})</span>
                    <span class="me-3"><i class="bi bi-geo-alt me-1"></i>{{ $step1->allottedState?->state_name ?? '—' }}</span>
                    <span><i class="bi bi-calendar3 me-1"></i>{{ $step1->session?->session_name ?? '—' }}</span>
                </div>
            </div>
            <div class="text-end flex-shrink-0">
                @if($master?->status === 'SUBMITTED')
                    <span class="badge bg-success fs-6 px-3 py-2">SUBMITTED</span>
                @else
                    <span class="badge bg-warning text-dark fs-6 px-3 py-2">INCOMPLETE</span>
                @endif
                @if($confirmation?->declaration_accepted)
                    <div class="mt-1" style="font-size:.75rem;opacity:.85;">
                        <i class="bi bi-patch-check-fill text-warning me-1"></i>Declaration accepted
                        {{ $confirmation->confirmed_at?->format('d M Y, H:i') }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Progress Bar --}}
    @php
        $steps = ['Step 1'=>$master?->step1_done,'Step 2'=>$master?->step2_done,'Step 3'=>$master?->step3_done,'Bank'=>$master?->bank_done,'Docs'=>$master?->docs_done];
        $done  = collect($steps)->filter()->count();
    @endphp
    <div class="card border-0 shadow-sm mb-3 px-4 py-3">
        <div class="d-flex justify-content-between mb-1" style="font-size:12px;">
            <span class="fw-semibold">Registration Progress</span>
            <span class="text-muted">{{ $done }}/5 steps</span>
        </div>
        <div class="d-flex gap-1">
            @foreach($steps as $lbl => $done_flag)
                <div style="flex:1;text-align:center;">
                    <div style="height:8px;border-radius:4px;background:{{ $done_flag ? '#16a34a' : '#e5e7eb' }};margin-bottom:4px;"></div>
                    <div style="font-size:10px;color:{{ $done_flag ? '#16a34a' : '#9ca3af' }};">{{ $lbl }}</div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="row g-3">

        {{-- Basic Info --}}
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100" style="border-radius:8px;">
                <div class="card-header bg-white border-bottom py-2 px-3 fw-semibold small" style="color:#1a3c6e;">
                    <i class="bi bi-person-fill me-1"></i>Basic Information
                </div>
                <div class="card-body p-3">
                    @php
                    $rows = [
                        ["Father's Name",   $step1->fathers_name],
                        ["Mother's Name",   $step1->mothers_name],
                        ['Date of Birth',   $step1->date_of_birth?->format('d M Y')],
                        ['Gender',          $step1->gender],
                        ['Cadre',           $step1->cadre],
                        ['Mobile',          $step1->mobile_no],
                        ['Email',           $step1->email],
                    ];
                    @endphp
                    @foreach($rows as [$l,$v])
                        <div class="d-flex border-bottom py-1" style="font-size:12px;">
                            <div class="text-muted fw-semibold" style="width:130px;flex-shrink:0;">{{ $l }}</div>
                            <div>{{ $v ?? '—' }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Personal / Address --}}
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100" style="border-radius:8px;">
                <div class="card-header bg-white border-bottom py-2 px-3 fw-semibold small" style="color:#1a3c6e;">
                    <i class="bi bi-card-list me-1"></i>Personal Details
                </div>
                <div class="card-body p-3">
                    @php
                    $rows2 = [
                        ['Category',        $step2?->category?->category_name],
                        ['Religion',        $step2?->religion?->religion_name],
                        ['Nationality',     $step2?->nationality],
                        ['Marital Status',  $step2?->marital_status],
                        ['Blood Group',     $step2?->blood_group],
                        ['Height',          $step2?->height_cm ? $step2->height_cm.' cm' : null],
                        ['Weight',          $step2?->weight_kg ? $step2->weight_kg.' kg' : null],
                        ['Perm. Address',   implode(', ', array_filter([
                                                $step2?->perm_address_line1,
                                                $step2?->perm_city,
                                                $step2?->permState?->state_name,
                                                $step2?->perm_pincode
                                            ]))],
                        ['Emergency Contact', $step2?->emergency_contact_name
                                              ? "{$step2->emergency_contact_name} ({$step2->emergency_contact_relation}) – {$step2->emergency_contact_mobile}"
                                              : null],
                    ];
                    @endphp
                    @foreach($rows2 as [$l,$v])
                        <div class="d-flex border-bottom py-1" style="font-size:12px;">
                            <div class="text-muted fw-semibold" style="width:130px;flex-shrink:0;">{{ $l }}</div>
                            <div>{{ $v ?: '—' }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Qualifications --}}
        @if($qualifications->count())
        <div class="col-12">
            <div class="card border-0 shadow-sm" style="border-radius:8px;">
                <div class="card-header bg-white border-bottom py-2 px-3 fw-semibold small" style="color:#1a3c6e;">
                    <i class="bi bi-mortarboard me-1"></i>Educational Qualifications
                </div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0" style="font-size:12px;">
                        <thead class="table-light"><tr><th>Qualification</th><th>Degree / Specialisation</th><th>Board / University</th><th>Institution</th><th>Year</th><th>%/CGPA</th></tr></thead>
                        <tbody>
                        @foreach($qualifications as $q)
                            <tr>
                                <td>{{ $q->qualification_name ?? '—' }}</td>
                                <td>{{ $q->degree_name ?? '—' }}</td>
                                <td>{{ $q->board_name ?? '—' }}</td>
                                <td>{{ $q->institution_name ?? '—' }}</td>
                                <td>{{ $q->year_of_passing ?? '—' }}</td>
                                <td>{{ $q->percentage_cgpa ?? '—' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        {{-- Employment --}}
        @if($employments->count())
        <div class="col-12">
            <div class="card border-0 shadow-sm" style="border-radius:8px;">
                <div class="card-header bg-white border-bottom py-2 px-3 fw-semibold small" style="color:#1a3c6e;">
                    <i class="bi bi-briefcase me-1"></i>Employment History
                </div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0" style="font-size:12px;">
                        <thead class="table-light"><tr><th>Organisation</th><th>Designation</th><th>Type</th><th>From</th><th>To</th><th>Current</th></tr></thead>
                        <tbody>
                        @foreach($employments as $e)
                            <tr>
                                <td>{{ $e->organisation_name }}</td>
                                <td>{{ $e->designation }}</td>
                                <td>{{ $e->job_type_name ?? '—' }}</td>
                                <td>{{ $e->from_date }}</td>
                                <td>{{ $e->to_date ?? '—' }}</td>
                                <td>{{ $e->is_current ? 'Yes' : 'No' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        {{-- Languages --}}
        @if($languages->count())
        <div class="col-md-6">
            <div class="card border-0 shadow-sm" style="border-radius:8px;">
                <div class="card-header bg-white border-bottom py-2 px-3 fw-semibold small" style="color:#1a3c6e;">
                    <i class="bi bi-translate me-1"></i>Languages Known
                </div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0" style="font-size:12px;">
                        <thead class="table-light"><tr><th>Language</th><th>Proficiency</th><th class="text-center">R</th><th class="text-center">W</th><th class="text-center">S</th></tr></thead>
                        <tbody>
                        @foreach($languages as $l)
                            <tr>
                                <td>{{ $l->language_name }}</td>
                                <td>{{ $l->proficiency ?? '—' }}</td>
                                <td class="text-center">{{ $l->can_read ? '✓' : '' }}</td>
                                <td class="text-center">{{ $l->can_write ? '✓' : '' }}</td>
                                <td class="text-center">{{ $l->can_speak ? '✓' : '' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        {{-- Bank Details --}}
        <div class="col-md-6">
            <div class="card border-0 shadow-sm" style="border-radius:8px;">
                <div class="card-header bg-white border-bottom py-2 px-3 fw-semibold small" style="color:#1a3c6e;">
                    <i class="bi bi-bank me-1"></i>Bank Details
                    @if($bank?->is_verified)<span class="badge bg-success ms-2" style="font-size:.65rem;">Verified</span>@endif
                </div>
                <div class="card-body p-3">
                    @if($bank)
                        @foreach([['Bank','bank_name'],['Branch','branch_name'],['IFSC','ifsc_code'],['Account No','account_no'],['Holder Name','account_holder_name'],['Type','account_type']] as [$l,$f])
                            <div class="d-flex border-bottom py-1" style="font-size:12px;">
                                <div class="text-muted fw-semibold" style="width:110px;flex-shrink:0;">{{ $l }}</div>
                                <div>{{ $bank->{$f} ?? '—' }}</div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-muted small py-2">Bank details not filled.</div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Documents --}}
        <div class="col-12">
            <div class="card border-0 shadow-sm" style="border-radius:8px;">
                <div class="card-header bg-white border-bottom py-2 px-3 fw-semibold small" style="color:#1a3c6e;">
                    <i class="bi bi-file-earmark-check me-1"></i>Document Upload Status
                </div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0" style="font-size:12px;">
                        <thead class="table-light">
                            <tr><th>#</th><th>Document</th><th class="text-center">Mandatory</th><th class="text-center">Uploaded</th><th class="text-center">Verified</th><th>Remarks</th></tr>
                        </thead>
                        <tbody>
                        @forelse($documents as $i => $doc)
                            <tr>
                                <td>{{ $i+1 }}</td>
                                <td>{{ $doc->documentMaster?->document_name ?? $doc->document_name }}</td>
                                <td class="text-center">
                                    @if($doc->documentMaster?->is_mandatory)
                                        <span class="badge bg-danger-subtle text-danger" style="font-size:10px;">Yes</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($doc->is_uploaded)
                                        @php $docFileUrl = view_file_link($doc->file_path); @endphp
                                        @if($docFileUrl)
                                            <a href="{{ $docFileUrl }}" target="_blank" rel="noopener" class="btn btn-xs btn-outline-success py-0 px-2" style="font-size:10px;">
                                                <i class="bi bi-eye me-1"></i>View
                                            </a>
                                        @else
                                            <span class="text-warning" style="font-size:11px;" title="{{ e($doc->file_path ?? '') }}">Path missing</span>
                                        @endif
                                    @else
                                        <span class="text-muted" style="font-size:11px;">Not uploaded</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($doc->is_verified)
                                        <i class="bi bi-patch-check-fill text-success"></i>
                                    @else
                                        <i class="bi bi-clock text-warning"></i>
                                    @endif
                                </td>
                                <td style="font-size:11px;">{{ $doc->remarks ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-2">No documents uploaded yet.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>{{-- /row --}}
</div>
@endsection
