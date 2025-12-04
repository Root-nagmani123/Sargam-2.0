@extends('admin.layouts.master')
@section('title', 'View Faculty Details')

@section('setup_content')
<style>
@media print {
    /* Hide everything except the print area */
    body * {
        visibility: hidden;
    }

    .print-area, .print-area * {
        visibility: visible;
    }

    .print-area {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        background: #fff !important;
        padding: 20px 40px;
    }

    /* Keep Bootstrap grid intact */
    .row {
        display: flex !important;
        flex-wrap: wrap !important;
        margin-right: -0.75rem !important;
        margin-left: -0.75rem !important;
    }

    .col-md-6, .col-lg-6, .col-sm-6 {
        flex: 0 0 50% !important;
        max-width: 50% !important;
        padding: 0 0.75rem !important;
        box-sizing: border-box;
    }

    /* Optional: smaller screen columns */
    .col-12 {
        flex: 0 0 100% !important;
        max-width: 100% !important;
    }

    /* Card appearance */
    .card {
        border: 1px solid #ddd !important;
        box-shadow: none !important;
        page-break-inside: avoid;
        margin-bottom: 1.5rem;
    }

    .card-header {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color: white !important;
    }

    /* Hide unwanted UI */
    .btn, .btn-group, .text-end, nav, header, footer {
        display: none !important;
    }

    /* Typography tweaks for printing */
    * {
        font-size: 13px !important;
        line-height: 1.4 !important;
        color: #000 !important;
    }

    /* Each section starts neatly */
    .card.shadow-sm.mb-4 {
        page-break-after: always;
    }
}
</style>



<div class="container-fluid">
    <div class="print-area">
    <x-breadcrum title="Faculty Details" />

    {{-- PERSONAL INFO --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary ">
            <h5 class="mb-0 text-white">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="white"
                    class="me-2 bi bi-person-fill" viewBox="0 0 16 16">
                    <path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H3zm5-6a3 3 0 100-6 3 3 0 000 6z" />
                </svg>
                Personal Information
            </h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <x-view-item label="Faculty Type" :value="$faculty->facultyTypeMaster->faculty_type_name" />
                <x-view-item label="First Name" :value="$faculty->first_name" />
                <x-view-item label="Middle Name" :value="$faculty->middle_name" />
                <x-view-item label="Last Name" :value="$faculty->last_name" />
                <x-view-item label="Full Name" :value="$faculty->full_name" />
                <x-view-item label="Gender" :value="$faculty->gender" />
                <x-view-item label="Landline Number" :value="$faculty->landline_no" />
                <x-view-item label="Mobile Number" :value="$faculty->mobile_no" />
                <x-view-item label="Email" :value="$faculty->email_id" />
                <x-view-item label="Alternate Email" :value="$faculty->alternate_email_id" />
                <x-view-item label="Country" :value="$faculty->countryMaster->country_name" />
                <x-view-item label="State" :value="$faculty->stateMaster->state_name" />
                <x-view-item label="District" :value="$faculty->districtMaster->district_name" />
                <x-view-item label="City" :value="$faculty->cityMaster->city_name" />
                @if(!empty($faculty->photo_uplode_path))
                <br />
                <span class="text-info text-bold">Previously Uploaded Photo</span>
                <a href="{{ asset('storage/'.$faculty->photo_uplode_path) }}" target="_blank" class="rounded-circle"
                    title="View Photo">
                    <iconify-icon icon="lets-icons:eye" width="24" height="24"></iconify-icon>
                </a>
                @endif
                @if(!empty($faculty->Doc_uplode_path))
                <br />
                <span class="text-info text-bold">Previously Uploaded Document</span>
                <a href="{{ asset('storage/'.$faculty->Doc_uplode_path) }}" target="_blank" class="rounded-circle"
                    title="View Document">
                    <iconify-icon icon="lets-icons:eye" width="24" height="24"></iconify-icon>
                </a>
                @endif
            </div>
        </div>
    </div>

    {{-- QUALIFICATION --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-success ">
            <h5 class="mb-0 text-white">🎓 Qualification Details</h5>
        </div>
        <div class="card-body">
            @foreach ($faculty->facultyQualificationMap as $facultyQualification)
            <div class="border rounded p-3 mb-3 bg-light">
                <div class="row g-3">
                    <x-view-item label="Qualification" :value="$facultyQualification->Degree_name" />
                    <x-view-item label="Specialization" :value="$facultyQualification->Specialization" />
                    <x-view-item label="University" :value="$facultyQualification->University_Institution_Name" />
                    <x-view-item label="Year of Passing" :value="$facultyQualification->Year_of_passing" />
                    <x-view-item label="Percentage/CGPA" :value="$facultyQualification->Percentage_CGPA" />
                    @if(!empty($facultyQualification->Certifcates_upload_path))
                    <div class="col-md-6">
                        <a class="btn btn-primary" href="{{ asset('storage/'.$facultyQualification->Certifcates_upload_path) }}" target="_blank">
                            View Document
                        </a>
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- EXPERIENCE --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-warning ">
            <h5 class="mb-0 text-white">💼 Experience Details</h5>
        </div>
        <div class="card-body">
            @foreach($faculty->facultyExperienceMap as $exp)
            <div class="border rounded p-3 mb-3 bg-light">
                <div class="row g-3">
                    <x-view-item label="Years of Experience" :value="$exp->Years_Of_Experience" />
                    <x-view-item label="Area of Specialization" :value="$exp->Specialization" />
                    <x-view-item label="Previous Institutions" :value="$exp->pre_Institutions" />
                    <x-view-item label="Position Held" :value="$exp->Position_Held" />
                    <x-view-item label="Duration" :value="$exp->Duration" />
                    <x-view-item label="Nature of Work" :value="$exp->Nature_of_Work" />
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- BANK DETAILS --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-info ">
            <h5 class="mb-0 text-white">🏦 Bank Details</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <x-view-item label="Bank Name" :value="$faculty->bank_name" />
                <x-view-item label="Account Number" :value="$faculty->Account_No" />
                <x-view-item label="IFSC Code" :value="$faculty->IFSC_Code" />
                <x-view-item label="PAN Number" :value="$faculty->PAN_No" />
            </div>
        </div>
    </div>

    {{-- OTHER INFO --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-secondary">
            <h5 class="mb-0 text-white">📁 Other Information</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                @if( !empty($faculty->Rech_Publi_Upload_path) )
                <span class="text-info text-bold">Research Publications</span>
                <a href="{{ asset('storage/'.$faculty->Rech_Publi_Upload_path) }}" target="_blank" class="rounded-circle"
                    title="View Document">
                    <iconify-icon icon="lets-icons:eye" width="24" height="24"></iconify-icon>
                </a>
                @endif

                @if( !empty($faculty->Professional_Memberships_doc_upload_path) )
                <span class="text-info text-bold">Professional Memberships</span>
                <a href="{{ asset('storage/'.$faculty->Professional_Memberships_doc_upload_path) }}" target="_blank"
                    class="rounded-circle" title="View Document">
                    <iconify-icon icon="lets-icons:eye" width="24" height="24"></iconify-icon>
                </a>
                @endif

                @if( !empty($faculty->Reference_Recommendation) )
                <br>
                <span class="text-info text-bold">Recommendation Details</span>
                <a href="{{ asset('storage/'.$faculty->Reference_Recommendation) }}" target="_blank" class="rounded-circle"
                    title="View Document">
                    <iconify-icon icon="lets-icons:eye" width="24" height="24"></iconify-icon>
                </a>
                @endif
                <x-view-item label="Joining Date" :value="format_date($faculty->joining_date)" />
                <x-view-item label="Current Sector"
                    :value="$faculty->faculty_sector == 1 ? 'Government Sector' : 'Private Sector' " />
            </div>
        </div>
    </div>

    {{-- EXPERTISE --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-dark">
            <h5 class="mb-0 text-white">🧠 Area of Expertise</h5>
        </div>
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2">
                @foreach($faculty->facultyExpertiseMap as $area)
                <span
                    class="badge rounded-pill bg-primary px-3 py-2">{{ $area->facultyExpertise->expertise_name }}</span>
                @endforeach
            </div>
        </div>
    </div>

    {{-- BACK BUTTON --}}
    <div class="text-end mt-4">
        <a href="{{ route('faculty.index') }}" class="btn btn-secondary">
           Back
        </a>
         <div class="btn-group" role="group">
      <button type="button" class="btn btn-outline-primary" onclick="printFacultyDetails()">
        <i class="material-icons">print</i> Print
    </button>
</div>
    </div>
    </div>
</div>
@endsection

<script>
function printFacultyDetails() {
    window.print();
}
</script>



