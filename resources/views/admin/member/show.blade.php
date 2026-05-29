@extends('admin.layouts.master')

@section('title', 'Member Details - Sargam | Lal Bahadur Shastri National Academy of Administration')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/member-show-admin.css') }}?v={{ @filemtime(public_path('css/member-show-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
@php
    $sections = [
        'Personal Info' => [
            'icon' => 'bi-person',
            'fields' => [
                'Title' => App\Models\EmployeeMaster::title[$member->title] ?? '',
                'First Name' => $member->first_name,
                'Middle Name' => $member->middle_name,
                'Last Name' => $member->last_name,
                'Father Name' => $member->father_name,
                'DOB' => $member->dob,
                'Gender' => App\Models\EmployeeMaster::gender[$member->gender] ?? '',
                'Marital Status' => App\Models\EmployeeMaster::maritalStatus[$member->marital_status] ?? '',
                'Height' => $member->height,
            ],
        ],
        'Contact Info' => [
            'icon' => 'bi-telephone',
            'fields' => [
                'Email' => $member->email,
                'Mobile' => $member->mobile,
                'Alternate Mobile' => $member->emergency_contact_no,
                'Landline Number' => $member->landline_contact_no,
                'Current Address' => $member->current_address,
                'Permanent Address' => $member->permanent_address,
                'City' => App\Models\City::find($member->city)->city_name ?? '',
                'State' => App\Models\State::find($member->state_master_pk)->state_name ?? '',
                'Country' => App\Models\Country::find($member->country_master_pk)->country_name ?? '',
                'Zipcode' => $member->zipcode,
            ],
        ],
        'Employment Info' => [
            'icon' => 'bi-briefcase',
            'fields' => [
                'Employee ID' => $member->emp_id,
                'Designation' => optional($member->designation)->designation_name ?? '',
                'Department' => optional($member->department)->department_name ?? '',
                'Employee Type' => optional($member->employeeType)->category_type_name ?? '',
                'Official Email' => $member->officalemail,
                'Employee Group' => optional($member->employeeGroup)->emp_group_name ?? '',
            ],
        ],
        'Uploaded Documents' => [
            'icon' => 'bi-file-earmark-text',
            'fields' => [
                'Picture' => $member->profile_picture
                    ? '<a href="' . asset('storage/' . $member->profile_picture) . '" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-image me-1" aria-hidden="true"></i>View Picture</a>'
                    : '',
                'Additional Document' => $member->additional_doc_upload
                    ? '<a href="' . asset('storage/' . $member->additional_doc_upload) . '" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-file-earmark me-1" aria-hidden="true"></i>View Document</a>'
                    : '',
            ],
        ],
    ];

    $fullName = trim(collect([$member->first_name, $member->middle_name, $member->last_name])->filter()->join(' '));
    $initials = collect(preg_split('/\s+/', $fullName))
        ->filter()
        ->take(2)
        ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
        ->join('');
    $designation = optional($member->designation)->designation_name ?? '—';
    $department = optional($member->department)->department_name ?? '—';
    $employeeType = optional($member->employeeType)->category_type_name ?? '—';
@endphp

<div class="container-fluid em-member-show-page pb-4">
    <x-breadcrum title="Employee Master Details">
        <div class="ems-toolbar-actions">
            <a href="{{ route('member.index') }}" class="ems-btn-outline">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                <span>Back</span>
            </a>
            <a href="{{ route('member.edit', $member->pk) }}" class="ems-btn-primary">
                <i class="bi bi-pencil" aria-hidden="true"></i>
                <span>Edit</span>
            </a>
        </div>
    </x-breadcrum>

    <x-session_message />

    <div class="card border-0 shadow-sm rounded-3 mb-4 ems-hero-card overflow-hidden">
        <div class="card-body p-4 p-lg-5">
            <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center gap-4">
                <div class="ems-hero-avatar">
                    @if($member->profile_picture)
                        <img src="{{ asset('storage/' . $member->profile_picture) }}" alt="{{ $fullName ?: 'Employee photo' }}">
                    @else
                        <span class="ems-hero-avatar__fallback" aria-hidden="true">{{ $initials ?: 'EM' }}</span>
                    @endif
                </div>
                <div class="min-w-0 flex-grow-1">
                    <h1 class="h4 fw-bold text-dark mb-1">{{ $fullName ?: '—' }}</h1>
                    <p class="text-body-secondary mb-2">
                        Employee ID: <span class="fw-semibold text-dark">{{ $member->emp_id ?: '—' }}</span>
                    </p>
                    <div class="d-flex flex-wrap gap-2">
                        @if($designation !== '—')
                            <span class="badge rounded-pill text-bg-light border fw-semibold">{{ $designation }}</span>
                        @endif
                        @if($department !== '—')
                            <span class="badge rounded-pill text-bg-light border fw-semibold">{{ $department }}</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 h-100 ems-stat-card">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <span class="ems-stat-icon bg-primary-subtle text-primary">
                        <i class="bi bi-person-badge" aria-hidden="true"></i>
                    </span>
                    <div class="min-w-0">
                        <div class="small text-body-secondary">Employee ID</div>
                        <div class="fw-bold text-dark text-truncate" title="{{ $member->emp_id }}">{{ $member->emp_id ?: '—' }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 h-100 ems-stat-card">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <span class="ems-stat-icon bg-success-subtle text-success">
                        <i class="bi bi-building" aria-hidden="true"></i>
                    </span>
                    <div class="min-w-0">
                        <div class="small text-body-secondary">Department</div>
                        <div class="fw-bold text-dark text-truncate" title="{{ $department }}">{{ $department }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 h-100 ems-stat-card">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <span class="ems-stat-icon bg-warning-subtle text-warning">
                        <i class="bi bi-briefcase" aria-hidden="true"></i>
                    </span>
                    <div class="min-w-0">
                        <div class="small text-body-secondary">Employee Type</div>
                        <div class="fw-bold text-dark text-truncate" title="{{ $employeeType }}">{{ $employeeType }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 h-100 ems-stat-card">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <span class="ems-stat-icon bg-info-subtle text-info">
                        <i class="bi bi-phone" aria-hidden="true"></i>
                    </span>
                    <div class="min-w-0">
                        <div class="small text-body-secondary">Mobile</div>
                        <div class="fw-bold text-dark text-truncate" title="{{ $member->mobile }}">{{ $member->mobile ?: '—' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @foreach ($sections as $title => $section)
        <div class="card border-0 shadow-sm rounded-3 mb-4 ems-section-card">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="bi {{ $section['icon'] }} text-primary fs-5" aria-hidden="true"></i>
                    <h2 class="h5 fw-bold text-dark mb-0">{{ $title }}</h2>
                </div>
                <hr class="ems-section-divider mb-4">

                <div class="row g-4">
                    @foreach ($section['fields'] as $label => $value)
                        <div class="col-md-4">
                            <div class="ems-field">
                                <div class="ems-field__label">{{ $label }}</div>
                                <div class="ems-field__value">
                                    {!! $value ?: '—' !!}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach

    <div class="card border-0 shadow-sm rounded-3 mb-4 ems-section-card">
        <div class="card-body p-4">
            <div class="d-flex align-items-center gap-2 mb-2">
                <i class="bi bi-shield-check text-primary fs-5" aria-hidden="true"></i>
                <h2 class="h5 fw-bold text-dark mb-0">Assigned Roles</h2>
            </div>
            <hr class="ems-section-divider mb-4">

            @if($member->assignedRoles()->isEmpty())
                <p class="ems-empty-text">No roles assigned</p>
            @else
                <div class="d-flex flex-wrap gap-2">
                    @foreach($member->assignedRoles() as $role)
                        <span class="ems-role-badge">{{ $role['role_name'] }}</span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
