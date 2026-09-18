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

        // $sections comes from MemberController::memberProfileSections() so this
        // screen and the print sheet render the same fields from one definition.

        $assignedRoles = $member->assignedRoles();
    @endphp

    <div class="container-fluid member-admin-page member-view-page">
        <x-breadcrum title="Member Details">
            <div class="d-flex flex-wrap align-items-center gap-2">
                {{-- Same sheet the grid's row-level Print opens. --}}
                <a href="{{ route('member.print', encrypt($member->pk)) }}" target="_blank" rel="noopener"
                    class="btn mbrw-btn mbrw-btn-cancel d-inline-flex align-items-center gap-2">
                    <i class="bi bi-printer" aria-hidden="true"></i>
                    <span>Print</span>
                </a>
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
