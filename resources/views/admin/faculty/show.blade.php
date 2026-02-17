@extends('admin.layouts.master')
@section('title', 'View Faculty Details')

@section('setup_content')

<style>
/* Faculty Show - Bootstrap 5.3 Enhanced UI */
.profile-photo {
    width: 160px;
    height: 160px;
    object-fit: cover;
    border-radius: 12px;
    border: 3px solid var(--bs-border-color);
}

/* Print Optimisation */
@media print {
    body * { visibility: hidden !important; }
    .print-area, .print-area * { visibility: visible !important; }
    .print-area { position: absolute; left: 0; top: 0; width: 100%; padding: 0 30px; }
    .no-print { display: none !important; }
}
</style>

<div class="container-fluid print-area">
    <x-breadcrum title="Faculty Details"></x-breadcrum>
    <x-session_message />

    <!-- Header Card - Faculty Profile -->
    <div class="card border-0 shadow rounded-3 mb-4 overflow-hidden" style="border-left: 4px solid #004a93;">
        <div class="card-body p-4 p-lg-5">
            <div class="row align-items-center g-4">
                <div class="col-auto">
                    <img src="{{ $faculty->photo_uplode_path ? asset('storage/'.$faculty->photo_uplode_path) : asset('default-user.png') }}"
                         alt="Faculty Photo" class="profile-photo shadow-sm">
                </div>
                <div class="col">
                    <h4 class="mb-2 fw-bold text-dark">
                        <i class="material-icons material-symbols-rounded align-middle me-2" style="font-size:28px;">person</i>
                        {{ $faculty->full_name }}
                    </h4>
                    <p class="text-muted mb-2">
                        <span class="badge bg-primary-subtle text-primary me-2">{{ $faculty->faculty_code }}</span>
                        <span class="badge bg-secondary-subtle text-secondary">{{ $faculty->facultyTypeMaster->faculty_type_name }}</span>
                    </p>
                    @if($faculty->faculty_type == '1' && $faculty->faculty_pa)
                    <p class="text-muted mb-0 small">
                        <span class="text-body-secondary">Faculty (PA):</span> {{ $faculty->faculty_pa }}
                    </p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <!-- Personal Information -->
            <div class="card border-0 shadow-sm rounded-3 mb-4 overflow-hidden">
                <div class="card-header bg-light border-0 rounded-top-3 p-3">
                    <h6 class="mb-0 fw-bold">
                        <i class="material-icons material-symbols-rounded align-middle me-2">badge</i>
                        Personal Information
                    </h6>
                </div>
                <div class="card-body p-3">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded-3">
                                <small class="text-muted d-block mb-1">Gender</small>
                                <strong class="text-dark d-block fs-6">{{ $faculty->gender }}</strong>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded-3">
                                <small class="text-muted d-block mb-1">Mobile Number</small>
                                <strong class="text-dark d-block fs-6">{{ $faculty->mobile_no }}</strong>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded-3">
                                <small class="text-muted d-block mb-1">Email ID</small>
                                <strong class="text-dark d-block fs-6 text-break">{{ $faculty->email_id }}</strong>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded-3">
                                <small class="text-muted d-block mb-1">Current Designation</small>
                                <strong class="text-dark d-block fs-6">{{ $faculty->current_designation ?? '—' }}</strong>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded-3">
                                <small class="text-muted d-block mb-1">Current Department</small>
                                <strong class="text-dark d-block fs-6">{{ $faculty->current_department ?? '—' }}</strong>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded-3">
                                <small class="text-muted d-block mb-1">Address</small>
                                <strong class="text-dark d-block fs-6">
                                    @php
                                        $addr = array_filter([
                                            $faculty->countryMaster?->country_name,
                                            $faculty->stateMaster?->state_name,
                                            $faculty->districtMaster?->district_name,
                                            $faculty->cityMaster?->city_name
                                        ]);
                                    @endphp
                                    {{ implode(', ', $addr) ?: '—' }}
                                </strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Qualification Details -->
            <div class="card border-0 shadow-sm rounded-3 mb-4 overflow-hidden">
                <div class="card-header bg-light border-0 rounded-top-3 p-3">
                    <h6 class="mb-0 fw-bold">
                        <i class="material-icons material-symbols-rounded align-middle me-2">school</i>
                        Qualification Details
                    </h6>
                </div>
                <div class="card-body p-3">
                    @forelse($faculty->facultyQualificationMap as $q)
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <div class="p-3 bg-light rounded-3">
                                <small class="text-muted d-block mb-1">Degree</small>
                                <strong class="text-dark d-block fs-6">{{ $q->Degree_name }}</strong>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 bg-light rounded-3">
                                <small class="text-muted d-block mb-1">University</small>
                                <strong class="text-dark d-block fs-6">{{ $q->University_Institution_Name }}</strong>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 bg-light rounded-3">
                                <small class="text-muted d-block mb-1">Passing Year</small>
                                <strong class="text-dark d-block fs-6">{{ $q->Year_of_passing }}</strong>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 bg-light rounded-3">
                                <small class="text-muted d-block mb-1">Percentage / CGPA</small>
                                <strong class="text-dark d-block fs-6">{{ $q->Percentage_CGPA }}</strong>
                            </div>
                        </div>
                    </div>
                    @empty
                    <p class="text-muted mb-0 py-2">No qualification details available.</p>
                    @endforelse
                </div>
            </div>

            <!-- Experience Details -->
            <div class="card border-0 shadow-sm rounded-3 mb-4 overflow-hidden">
                <div class="card-header bg-light border-0 rounded-top-3 p-3">
                    <h6 class="mb-0 fw-bold">
                        <i class="material-icons material-symbols-rounded align-middle me-2">work</i>
                        Experience Details
                    </h6>
                </div>
                <div class="card-body p-3">
                    @forelse($faculty->facultyExperienceMap as $exp)
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <div class="p-3 bg-light rounded-3">
                                <small class="text-muted d-block mb-1">Years of Experience</small>
                                <strong class="text-dark d-block fs-6">{{ $exp->Years_Of_Experience }}</strong>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 bg-light rounded-3">
                                <small class="text-muted d-block mb-1">Area of Specialisation</small>
                                <strong class="text-dark d-block fs-6">{{ $exp->Specialization }}</strong>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 bg-light rounded-3">
                                <small class="text-muted d-block mb-1">Institution</small>
                                <strong class="text-dark d-block fs-6">{{ $exp->pre_Institutions }}</strong>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 bg-light rounded-3">
                                <small class="text-muted d-block mb-1">Position Held</small>
                                <strong class="text-dark d-block fs-6">{{ $exp->Position_hold }}</strong>
                            </div>
                        </div>
                    </div>
                    @empty
                    <p class="text-muted mb-0 py-2">No experience details available.</p>
                    @endforelse
                </div>
            </div>

            <!-- Bank Details -->
            <div class="card border-0 shadow-sm rounded-3 mb-4 overflow-hidden">
                <div class="card-header bg-light border-0 rounded-top-3 p-3">
                    <h6 class="mb-0 fw-bold">
                        <i class="material-icons material-symbols-rounded align-middle me-2">account_balance</i>
                        Bank Details
                    </h6>
                </div>
                <div class="card-body p-3">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded-3">
                                <small class="text-muted d-block mb-1">Bank Name</small>
                                <strong class="text-dark d-block fs-6">{{ $faculty->bank_name ?? '—' }}</strong>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded-3">
                                <small class="text-muted d-block mb-1">Account Number</small>
                                <strong class="text-dark d-block fs-6">{{ $faculty->Account_No ?? '—' }}</strong>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded-3">
                                <small class="text-muted d-block mb-1">IFSC Code</small>
                                <strong class="text-dark d-block fs-6">{{ $faculty->IFSC_Code ?? '—' }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Area of Expertise -->
            <div class="card border-0 shadow-sm rounded-3 mb-4 overflow-hidden">
                <div class="card-header bg-light border-0 rounded-top-3 p-3">
                    <h6 class="mb-0 fw-bold">
                        <i class="material-icons material-symbols-rounded align-middle me-2">psychology</i>
                        Area of Expertise
                    </h6>
                </div>
                <div class="card-body p-3">
                    @forelse($faculty->facultyExpertiseMap as $area)
                    <span class="badge bg-info-subtle text-info fs-6 py-2 px-3 me-2 mb-2">{{ $area->facultyExpertise->expertise_name }}</span>
                    @empty
                    <p class="text-muted mb-0 py-2">No expertise areas specified.</p>
                    @endforelse
                </div>
            </div>

            <!-- Other Information -->
            <div class="card border-0 shadow-sm rounded-3 mb-4 overflow-hidden">
                <div class="card-header bg-light border-0 rounded-top-3 p-3">
                    <h6 class="mb-0 fw-bold">
                        <i class="material-icons material-symbols-rounded align-middle me-2">info</i>
                        Other Information
                    </h6>
                </div>
                <div class="card-body p-3">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded-3">
                                <small class="text-muted d-block mb-1">Joining Date</small>
                                <strong class="text-dark d-block fs-6">{{ format_date($faculty->joining_date) }}</strong>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded-3">
                                <small class="text-muted d-block mb-1">Current Sector</small>
                                <strong class="text-dark d-block fs-6">
                                    {{ $faculty->faculty_sector == 1 ? 'Government Sector' : 'Private Sector' }}
                                </strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="no-print d-flex flex-wrap justify-content-end gap-2 mt-4 mb-3">
                <a href="{{ route('faculty.index') }}" class="btn btn-outline-secondary btn-sm">
                    Back
                </a>
                <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">
                    Print
                </button>
            </div>
        </div>
    </div>
</div>

@endsection
