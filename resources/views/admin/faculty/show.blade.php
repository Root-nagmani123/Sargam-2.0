@extends('admin.layouts.master')

@section('title', 'View Faculty Details')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="{{ asset('css/faculty-detail-admin.css') }}?v={{ @filemtime(public_path('css/faculty-detail-admin.css')) ?: time() }}">
@endpush

@section('setup_content')

<div class="container-fluid faculty-detail-page pb-4">
    <x-breadcrum title="View Faculty Details" />

    <div class="fc-detail-card print-area">
        <div class="fc-detail-header">
            <div class="row fc-detail-grid g-3 align-items-center">
                <div class="col-md-2 text-center">
                    <img src="{{ $faculty->photo_uplode_path ? asset('storage/'.$faculty->photo_uplode_path) : asset('images/dummypic.jpeg') }}"
                        alt="Faculty Photo" class="fc-detail-photo mx-auto">
                </div>
                <div class="col-md-10">
                    <div class="row fc-detail-grid g-3">
                        <div class="col-md-8">
                            <div class="fc-field-label">Full Name</div>
                            <div class="fc-field-value">{{ $faculty->full_name }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="fc-field-label">Faculty Code</div>
                            <div class="fc-field-value">{{ $faculty->faculty_code }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="fc-field-label">Faculty Type</div>
                            <div class="fc-field-value">{{ $faculty->facultyTypeMaster->faculty_type_name ?? '-' }}</div>
                        </div>
                        @if($faculty->faculty_type == '1' && $faculty->faculty_pa)
                        <div class="col-md-6">
                            <div class="fc-field-label">Faculty (PA)</div>
                            <div class="fc-field-value">{{ $faculty->faculty_pa }}</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="fc-section">
            <div class="fc-section-title">Personal Information</div>
            <div class="row fc-detail-grid g-3">
                <div class="col-md-4">
                    <div class="fc-field-label">Gender</div>
                    <div class="fc-field-value">{{ $faculty->gender ?? '-' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="fc-field-label">Mobile Number</div>
                    <div class="fc-field-value">{{ $faculty->mobile_no ?? '-' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="fc-field-label">Email ID</div>
                    <div class="fc-field-value">{{ $faculty->email_id ?? '-' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="fc-field-label">Current Designation</div>
                    <div class="fc-field-value">{{ $faculty->current_designation ?? '-' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="fc-field-label">Current Department</div>
                    <div class="fc-field-value">{{ $faculty->current_department ?? '-' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="fc-field-label">Address</div>
                    <div class="fc-field-value">
                        {{ $faculty->countryMaster->country_name ?? '' }}{{ ($faculty->stateMaster->state_name ?? '') ? ', '.$faculty->stateMaster->state_name : '' }}{{ ($faculty->districtMaster->district_name ?? '') ? ', '.$faculty->districtMaster->district_name : '' }}{{ ($faculty->cityMaster->city_name ?? '') ? ', '.$faculty->cityMaster->city_name : '' }}
                    </div>
                </div>
            </div>
        </div>

        <div class="fc-section">
            <div class="fc-section-title">Qualifications Details</div>
            @forelse($faculty->facultyQualificationMap as $q)
            <div class="fc-panel-block">
                <div class="row fc-detail-grid g-3">
                    <div class="col-md-6">
                        <div class="fc-field-label">Degree</div>
                        <div class="fc-field-value">{{ $q->Degree_name ?? '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="fc-field-label">University / Institution</div>
                        <div class="fc-field-value">{{ $q->University_Institution_Name ?? '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="fc-field-label">Year of Passing</div>
                        <div class="fc-field-value">{{ $q->Year_of_passing ?? '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="fc-field-label">Percentage / CGPA</div>
                        <div class="fc-field-value">{{ $q->Percentage_CGPA ?? '-' }}</div>
                    </div>
                </div>
            </div>
            @empty
            <div class="fc-field-value">-</div>
            @endforelse
        </div>

        <div class="fc-section">
            <div class="fc-section-title">Experience Details</div>
            @forelse($faculty->facultyExperienceMap as $exp)
            <div class="fc-panel-block">
                <div class="row fc-detail-grid g-3">
                    <div class="col-md-6">
                        <div class="fc-field-label">Years of Experience</div>
                        <div class="fc-field-value">{{ $exp->Years_Of_Experience ?? '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="fc-field-label">Area of Specialisation</div>
                        <div class="fc-field-value">{{ $exp->Specialization ?? '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="fc-field-label">Previous Institution</div>
                        <div class="fc-field-value">{{ $exp->pre_Institutions ?? '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="fc-field-label">Position Held</div>
                        <div class="fc-field-value">{{ $exp->Position_hold ?? '-' }}</div>
                    </div>
                </div>
            </div>
            @empty
            <div class="fc-field-value">-</div>
            @endforelse
        </div>

        <div class="fc-section">
            <div class="fc-section-title">Bank Details</div>
            <div class="row fc-detail-grid g-3">
                <div class="col-md-6">
                    <div class="fc-field-label">Bank Name</div>
                    <div class="fc-field-value">{{ $faculty->bank_name ?? '-' }}</div>
                </div>
                <div class="col-md-6">
                    <div class="fc-field-label">Account Number</div>
                    <div class="fc-field-value">{{ $faculty->Account_No ?? '-' }}</div>
                </div>
                <div class="col-md-6">
                    <div class="fc-field-label">IFSC Code</div>
                    <div class="fc-field-value">{{ $faculty->IFSC_Code ?? '-' }}</div>
                </div>
                <div class="col-md-6">
                    <div class="fc-field-label">PAN Number</div>
                    <div class="fc-field-value">{{ $faculty->PAN_No ?? '-' }}</div>
                </div>
            </div>
        </div>

        <div class="fc-section">
            <div class="fc-section-title">Area of Expertise</div>
            <div class="fc-expertise-list">
                @forelse($faculty->facultyExpertiseMap as $area)
                <span class="fc-expertise-pill">{{ $area->facultyExpertise->expertise_name ?? '-' }}</span>
                @empty
                <span class="fc-field-value">-</span>
                @endforelse
            </div>
        </div>

        <div class="fc-section">
            <div class="fc-section-title">Other Information</div>
            <div class="row fc-detail-grid g-3">
                <div class="col-md-6">
                    <div class="fc-field-label">Joining Date</div>
                    <div class="fc-field-value">{{ $faculty->joining_date ? format_date($faculty->joining_date) : '-' }}</div>
                </div>
                <div class="col-md-6">
                    <div class="fc-field-label">Current Sector</div>
                    <div class="fc-field-value">
                        {{ $faculty->faculty_sector == 1 ? 'Government Sector' : ($faculty->faculty_sector == 2 ? 'Private Sector' : '-') }}
                    </div>
                </div>
            </div>
        </div>

        <div class="fc-detail-actions no-print">
            <a href="{{ route('faculty.index') }}" class="fc-btn-outline">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                Back
            </a>
            <button type="button" class="fc-btn-primary" onclick="window.print()">
                <i class="bi bi-printer" aria-hidden="true"></i>
                Print
            </button>
        </div>
    </div>
</div>

@endsection
