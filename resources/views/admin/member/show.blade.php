@extends('admin.layouts.master')

@section('title', 'Member Details - Sargam | Lal Bahadur Shastri National Academy of Administration')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/member-admin.css') }}?v={{ @filemtime(public_path('css/member-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
    @php
        $appellation = optional($member->appellationMaster)->appettation_name;
        $fullName = trim(collect([$appellation, $member->first_name, $member->middle_name, $member->last_name])
            ->map(fn ($part) => trim((string) $part))
            ->filter()
            ->implode(' '));
        $initials = collect([$member->first_name, $member->last_name])
            ->map(fn ($part) => mb_substr(trim((string) $part), 0, 1))
            ->filter()
            ->implode('');

        $isActive = (int) $member->status === 1;
        $designation = optional($member->designation)->designation_name;
        $department = optional($member->department)->department_name;

        $sections = [
            'Personal Information' => [
                'Title' => $appellation,
                'First Name' => $member->first_name,
                'Middle Name' => $member->middle_name,
                'Last Name' => $member->last_name,
                "Father's / Husband's Name" => $member->father_name,
                'Date of Birth' => $member->dob,
                'Gender' => App\Models\EmployeeMaster::gender[$member->gender] ?? null,
                'Marital Status' => App\Models\EmployeeMaster::maritalStatus[$member->marital_status] ?? null,
                'Height (Without Shoes)' => filled($member->height) ? $member->height . ' cm' : null,
            ],
            'Employment Details' => [
                'Employee ID' => $member->emp_id,
                'User ID' => optional($member->userCredential)->user_name,
                'Employee Type' => optional($member->employeeType)->category_type_name,
                'Employee Group' => optional($member->employeeGroup)->emp_group_name,
                'Designation' => $designation,
                'Department' => $department,
            ],
            'Contact Information' => [
                'Personal Email' => $member->email,
                'Official Email' => $member->officalemail,
                'Mobile Number' => $member->mobile,
                'Emergency Contact Number' => $member->emergency_contact_no,
                'Landline Number' => $member->landline_contact_no,
                'Residence Number' => $member->residence_no,
            ],
            'Address' => [
                'Country' => optional(App\Models\Country::find($member->country_master_pk))->country_name,
                'State' => optional(App\Models\State::find($member->state_master_pk))->state_name,
                'District' => optional(App\Models\District::find($member->state_district_mapping_pk))->district_name,
                'City' => optional(App\Models\City::find($member->city))->city_name,
                'Postal Code' => $member->zipcode,
                '__wide' => true,
                'Current Address' => $member->current_address,
                'Permanent Address' => $member->permanent_address,
                'Home Address Data' => $member->home_town_details,
            ],
        ];

        $assignedRoles = $member->assignedRoles();
    @endphp

    <div class="container-fluid member-admin-page member-view-page">
        <x-breadcrum title="Member Details">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <a href="{{ route('member.edit', $member->pk) }}" class="btn mbrw-btn mbrw-btn-primary">Edit Member</a>
            </div>
        </x-breadcrum>

        <x-session_message />

        <div class="mbrw-hero">
            <div class="mbrw-hero-main">
                <div class="mbrw-avatar">
                    @if ($member->profile_picture)
                        <img src="{{ asset('storage/' . $member->profile_picture) }}"
                            alt="Photograph of {{ $fullName }}">
                    @else
                        {{ $initials !== '' ? strtoupper($initials) : '—' }}
                    @endif
                </div>
                <div class="min-w-0">
                    <h1 class="mbrw-hero-name">{{ $fullName !== '' ? $fullName : 'Member' }}</h1>
                    <div class="mbrw-hero-meta">
                        <span>{{ filled($member->emp_id) ? 'ID ' . $member->emp_id : 'No employee ID' }}</span>
                        @if (filled($designation))
                            <span>{{ $designation }}</span>
                        @endif
                        @if (filled($department))
                            <span>{{ $department }}</span>
                        @endif
                    </div>
                </div>
            </div>

            <span class="status-pill badge rounded-1 {{ $isActive ? 'bg-success-subtle' : 'bg-danger-subtle' }}">
                {{ $isActive ? 'Active' : 'Inactive' }}
            </span>
        </div>

        @foreach ($sections as $title => $fields)
            <div class="mbrw-card">
                <div class="mbrw-section">
                    <h2 class="mbrw-section-title">{{ $title }}</h2>
                </div>
                <div class="mbrw-facts">
                    @php $wide = false; @endphp
                    @foreach ($fields as $label => $value)
                        {{-- Marker row: everything after it spans the full width. --}}
                        @if ($label === '__wide')
                            @php $wide = true; @endphp
                            @continue
                        @endif
                        <div class="mbrw-fact {{ $wide ? 'mbrw-fact--wide' : '' }}">
                            <span class="mbrw-fact__label">{{ $label }}</span>
                            <div class="mbrw-fact__value {{ filled($value) ? '' : 'is-empty' }}">
                                {{ filled($value) ? $value : '—' }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

        <div class="mbrw-card">
            <div class="mbrw-section">
                <h2 class="mbrw-section-title">Assigned Roles</h2>
            </div>
            {{-- assignedRoles() returns a Collection, so this must be isEmpty(),
                 not empty() — an object is never "empty". --}}
            @if ($assignedRoles->isEmpty())
                <div class="mbrw-fact__value is-empty">No roles assigned</div>
            @else
                <div class="mbrw-role-list">
                    @foreach ($assignedRoles as $role)
                        <span class="mbrw-role-badge">{{ $role['role_name'] }}</span>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="mbrw-card">
            <div class="mbrw-section">
                <h2 class="mbrw-section-title">Uploaded Documents</h2>
            </div>
            @if ($member->profile_picture || $member->additional_doc_upload)
                <div class="mbrw-doc-links">
                    @if ($member->profile_picture)
                        <a href="{{ asset('storage/' . $member->profile_picture) }}" target="_blank" rel="noopener"
                            class="btn mbrw-btn mbrw-btn-cancel mbrw-btn-sm">View Picture</a>
                    @endif
                    @if ($member->additional_doc_upload)
                        <a href="{{ asset('storage/' . $member->additional_doc_upload) }}" target="_blank" rel="noopener"
                            class="btn mbrw-btn mbrw-btn-cancel mbrw-btn-sm">View Document</a>
                    @endif
                </div>
            @else
                <div class="mbrw-fact__value is-empty">No documents uploaded</div>
            @endif
        </div>
    </div>
@endsection
