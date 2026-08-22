@extends('admin.layouts.master')
@section('title', 'View Faculty Details')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/master-admin.css') }}?v={{ @filemtime(public_path('css/master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
@php
    $isActive = (int) $faculty->active_inactive === 1;

    // $sectorName and $serviceName are resolved in FacultyController::show();
    // this view issues no SQL of its own.
    $location = collect([
        $faculty->cityMaster->city_name ?? null,
        $faculty->districtMaster->district_name ?? null,
        $faculty->stateMaster->state_name ?? null,
        $faculty->countryMaster->country_name ?? null,
    ])->filter()->implode(', ');

    // Initials avatar, shown when there is no photograph (or the file is gone).
    // first/last name first — full_name is prefixed with the appellation, so it
    // would give "Mr S" rather than the person's own initials.
    $initials = collect([$faculty->first_name, $faculty->last_name])
        ->map(fn ($part) => trim((string) $part))
        ->filter()
        ->map(fn ($part) => mb_substr($part, 0, 1))
        ->implode('');

    if ($initials === '') {
        $initials = collect(preg_split('/\s+/', trim((string) $faculty->full_name)))
            ->filter()
            ->take(2)
            ->map(fn ($part) => mb_substr($part, 0, 1))
            ->implode('');
    }

    $initials = mb_strtoupper($initials) ?: '?';
    $photoUrl = $faculty->photo_uplode_path ? asset('storage/'.$faculty->photo_uplode_path) : null;

    // The free-text fields are posted by JS, and a field that is absent from the
    // DOM arrives as the STRING "undefined" rather than as nothing — one faculty
    // record stores exactly that in both address columns, and `?: '-'` does not
    // catch it because the string is truthy. Treat the JS junk values as empty.
    $fact = function ($value) {
        $value = trim((string) $value);

        return in_array(strtolower($value), ['', 'undefined', 'null', 'nan'], true) ? '-' : $value;
    };
@endphp

<div class="container-fluid mst-page print-area">
    <x-print-letterhead title="Faculty Details" />
    <x-breadcrum title="View Faculty">
        <a href="{{ route('faculty.edit', ['id' => encrypt($faculty->pk)]) }}"
           class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">edit</i>
            <span>Edit Faculty</span>
        </a>
    </x-breadcrum>

    {{-- Secondary actions above the content, right-aligned, in the same chrome
         as the listing toolbar's buttons. --}}
    <div class="d-flex flex-wrap justify-content-end gap-2 mb-3 no-print">
        <button type="button" class="btn programme-dt-btn-columns border-0 text-primary" onclick="window.print()" title="Print">
            <i class="bi bi-printer" aria-hidden="true"></i>
            <span>Print</span>
        </button>
    </div>

    {{-- Hero: photo, name, the headline facts and the current state. --}}
    <div class="mst-hero">
        {{-- The initials sit underneath and the photo covers them. Plenty of
             records point at a file that is no longer on disk, so a failed load
             removes the <img> and the letters show through. --}}
        <div class="mst-hero__avatar">
            <span class="mst-hero__initials" aria-hidden="true">{{ $initials }}</span>
            @if($photoUrl)
                <img src="{{ $photoUrl }}" alt="Photograph of {{ $faculty->full_name }}"
                     class="mst-hero__photo" onerror="this.remove();">
            @endif
        </div>

        <div class="mst-hero__main">
            <div class="d-flex flex-wrap align-items-center gap-3">
                <h2 class="mst-hero__title">
                    {{ $faculty->full_name }}
                    <span class="mst-hero__code">({{ $faculty->faculty_code }})</span>
                </h2>
                <span class="status-pill badge rounded-1">{{ $isActive ? 'Active' : 'Inactive' }}</span>
            </div>

            <div class="mst-facts mst-facts--hero">
                <div>
                    <span class="mst-fact__label">Faculty Type</span>
                    <span class="mst-fact__value">{{ $faculty->facultyTypeMaster->faculty_type_name ?? '-' }}</span>
                </div>
                @if($faculty->faculty_type == '1' && $faculty->faculty_pa)
                    <div>
                        <span class="mst-fact__label">Faculty (PA)</span>
                        <span class="mst-fact__value">{{ $faculty->faculty_pa }}</span>
                    </div>
                @endif
                <div>
                    <span class="mst-fact__label">Mobile Number</span>
                    <span class="mst-fact__value">{{ $faculty->mobile_no ?: '-' }}</span>
                </div>
                <div>
                    <span class="mst-fact__label">Email ID</span>
                    <span class="mst-fact__value">{{ $faculty->email_id ?: '-' }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Personal information --}}
    <section class="mst-card">
        <h3 class="mst-card__title">Personal Information</h3>
        <div class="mst-facts">
            <div>
                <span class="mst-fact__label">Gender</span>
                <span class="mst-fact__value">{{ $faculty->gender ?: '-' }}</span>
            </div>
            <div>
                <span class="mst-fact__label">Landline Number</span>
                <span class="mst-fact__value">{{ $faculty->landline_no ?: '-' }}</span>
            </div>
            <div>
                <span class="mst-fact__label">Alternate Email</span>
                <span class="mst-fact__value">{{ $fact($faculty->alternate_email_id) }}</span>
            </div>
            <div>
                <span class="mst-fact__label">Current Designation</span>
                <span class="mst-fact__value">{{ $fact($faculty->current_designation) }}</span>
            </div>
            <div>
                <span class="mst-fact__label">Current Department</span>
                <span class="mst-fact__value">{{ $fact($faculty->current_department) }}</span>
            </div>
            <div>
                <span class="mst-fact__label">Location</span>
                <span class="mst-fact__value">{{ $location ?: '-' }}</span>
            </div>
            <div>
                <span class="mst-fact__label">Residence Address</span>
                <span class="mst-fact__value">{{ $fact($faculty->Residence_address) }}</span>
            </div>
            <div>
                <span class="mst-fact__label">Permanent Address</span>
                <span class="mst-fact__value">{{ $fact($faculty->Permanent_Address) }}</span>
            </div>
        </div>
    </section>

    {{-- Qualifications — repeating rows, so a programme-dt table --}}
    <section class="mst-card">
        <h3 class="mst-card__title">Qualification Details</h3>
        @if($faculty->facultyQualificationMap->isNotEmpty())
            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th scope="col" class="text-nowrap" style="width: 5.5rem;">S. No.</th>
                                <th scope="col">Degree</th>
                                <th scope="col">University / Institution</th>
                                <th scope="col" class="text-nowrap">Year of Passing</th>
                                <th scope="col" class="text-nowrap">Percentage / CGPA</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($faculty->facultyQualificationMap as $index => $q)
                                <tr>
                                    <td class="text-nowrap">{{ $index + 1 }}</td>
                                    <td>{{ $q->Degree_name ?: '-' }}</td>
                                    <td>{{ $q->University_Institution_Name ?: '-' }}</td>
                                    <td class="text-nowrap">{{ $q->Year_of_passing ?: '-' }}</td>
                                    <td class="text-nowrap">{{ $q->Percentage_CGPA ?: '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="ds-empty-state">No qualification records for this faculty.</div>
        @endif
    </section>

    {{-- Experience — repeating rows --}}
    <section class="mst-card">
        <h3 class="mst-card__title">Experience Details</h3>
        @if($faculty->facultyExperienceMap->isNotEmpty())
            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th scope="col" class="text-nowrap" style="width: 5.5rem;">S. No.</th>
                                <th scope="col" class="text-nowrap">Years of Experience</th>
                                <th scope="col">Area of Specialisation</th>
                                <th scope="col">Previous Institution</th>
                                <th scope="col">Position Held</th>
                                <th scope="col" class="text-nowrap">Duration</th>
                                <th scope="col">Nature of Work</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($faculty->facultyExperienceMap as $index => $exp)
                                <tr>
                                    <td class="text-nowrap">{{ $index + 1 }}</td>
                                    <td class="text-nowrap">{{ $exp->Years_Of_Experience ?: '-' }}</td>
                                    <td>{{ $exp->Specialization ?: '-' }}</td>
                                    <td>{{ $exp->pre_Institutions ?: '-' }}</td>
                                    <td>{{ $exp->Position_hold ?: '-' }}</td>
                                    <td class="text-nowrap">{{ $exp->duration ?: '-' }}</td>
                                    <td>{{ $exp->Nature_of_Work ?: '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="ds-empty-state">No experience records for this faculty.</div>
        @endif
    </section>

    {{-- Bank details --}}
    <section class="mst-card">
        <h3 class="mst-card__title">Bank Details</h3>
        <div class="mst-facts">
            <div>
                <span class="mst-fact__label">Bank Name</span>
                <span class="mst-fact__value">{{ $faculty->bank_name ?: '-' }}</span>
            </div>
            <div>
                <span class="mst-fact__label">Account Number</span>
                <span class="mst-fact__value">{{ $faculty->Account_No ?: '-' }}</span>
            </div>
            <div>
                <span class="mst-fact__label">IFSC Code</span>
                <span class="mst-fact__value">{{ $faculty->IFSC_Code ?: '-' }}</span>
            </div>
            <div>
                <span class="mst-fact__label">PAN Number</span>
                <span class="mst-fact__value">{{ $faculty->PAN_No ?: '-' }}</span>
            </div>
        </div>
    </section>

    {{-- Area of expertise --}}
    <section class="mst-card">
        <h3 class="mst-card__title">Area of Expertise</h3>
        @if($faculty->facultyExpertiseMap->isNotEmpty())
            <div class="mst-chips">
                @foreach($faculty->facultyExpertiseMap as $area)
                    <span class="mst-chip">{{ $area->facultyExpertise->expertise_name ?? '-' }}</span>
                @endforeach
            </div>
        @else
            <div class="ds-empty-state">No areas of expertise recorded.</div>
        @endif
    </section>

    {{-- Other information --}}
    <section class="mst-card">
        <h3 class="mst-card__title">Other Information</h3>
        <div class="mst-facts">
            <div>
                <span class="mst-fact__label">Joining Date</span>
                <span class="mst-fact__value">{{ $faculty->joining_date ? format_date($faculty->joining_date) : '-' }}</span>
            </div>
            <div>
                <span class="mst-fact__label">Current Sector</span>
                <span class="mst-fact__value">{{ $sectorName ?: '-' }}</span>
            </div>
            <div>
                <span class="mst-fact__label">Service</span>
                <span class="mst-fact__value">{{ $serviceName ?: '-' }}</span>
            </div>
            <div>
                <span class="mst-fact__label">Reference / Recommendation</span>
                <span class="mst-fact__value">{{ $faculty->Reference_Recommendation ?: '-' }}</span>
            </div>
        </div>
    </section>
</div>

@endsection
