@extends('admin.layouts.master')
@section('title', 'View Faculty Details')

@section('setup_content')

<style>
/* ------------------------------
   GLOBAL + GIGW COMPLIANT STYLE
--------------------------------*/
.page-wrapper {
    background: #fff;
    padding: 20px 40px;
    font-family: "Inter", Arial, sans-serif;
}

.section-title {
    font-weight: 600;
    margin-bottom: 12px;
    color: #003366;
    font-size: 18px;
    border-bottom: 2px solid #003366;
    padding-bottom: 4px;
}

.label-sm {
    font-size: 14px;
    font-weight: 500;
    color: #333;
}

.data-line {
    border-bottom: 1px solid #bbb;
    min-height: 26px;
    padding-bottom: 2px;
    font-size: 15px;
}

.profile-photo {
    width: 140px;
    height: 140px;
    object-fit: cover;
    border-radius: 6px;
    border: 1px solid #ddd;
}

.section-block {
    margin-bottom: 28px;
}

/* Print Optimisation */
@media print {
    body * {
        visibility: hidden !important;
    }
    .print-area, .print-area * {
        visibility: visible !important;
    }
    .print-area {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        padding: 0 30px;
    }
    .no-print {
        display: none !important;
    }
}
</style>

<div class="container-fluid print-area">
 <!-- ===========================================================
                HEADER + PHOTO + NAME (LIKE ATTACHED SAMPLE)
        ============================================================ -->
        <div class="row mb-4 align-items-center">
            <div class="col-md-2 text-center">
                <img src="{{ $faculty->photo_uplode_path ? asset('storage/'.$faculty->photo_uplode_path) : asset('default-user.png') }}"
                     alt="Faculty Photo" class="profile-photo">
            </div>

            <div class="col-md-10">
                <div class="label-sm">Full Name:</div>
                <div class="data-line">{{ $faculty->full_name }} ( {{ $faculty->faculty_code }} )</div>

                <div class="label-sm mt-3">Faculty Type:</div>
                <div class="data-line">{{ $faculty->facultyTypeMaster->faculty_type_name }}</div>
            </div>
        </div>


        <!-- ===========================================================
                           PERSONAL INFORMATION
        ============================================================ -->
        <div class="section-block">
            <div class="section-title">PERSONAL INFORMATION</div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="label-sm">Gender</div>
                    <div class="data-line">{{ $faculty->gender }}</div>
                </div>

                <div class="col-md-4">
                    <div class="label-sm">Mobile Number</div>
                    <div class="data-line">{{ $faculty->mobile_no }}</div>
                </div>

                <div class="col-md-4">
                    <div class="label-sm">Email ID</div>
                    <div class="data-line">{{ $faculty->email_id }}</div>
                </div>
            </div>

            <div class="label-sm mt-2">Address</div>
            <div class="data-line mb-2">
                {{ $faculty->countryMaster->country_name }},
                {{ $faculty->stateMaster->state_name }},
                {{ $faculty->districtMaster->district_name }},
                {{ $faculty->cityMaster->city_name }}
            </div>
        </div>


        <!-- ===========================================================
                           QUALIFICATION DETAILS
        ============================================================ -->
        <div class="section-block">
            <div class="section-title">QUALIFICATION DETAILS</div>

            @foreach($faculty->facultyQualificationMap as $q)
            <div class="row mb-3">
                <div class="col-md-3">
                    <div class="label-sm">Degree</div>
                    <div class="data-line">{{ $q->Degree_name }}</div>
                </div>

                <div class="col-md-3">
                    <div class="label-sm">University</div>
                    <div class="data-line">{{ $q->University_Institution_Name }}</div>
                </div>

                <div class="col-md-3">
                    <div class="label-sm">Passing Year</div>
                    <div class="data-line">{{ $q->Year_of_passing }}</div>
                </div>

                <div class="col-md-3">
                    <div class="label-sm">Percentage / CGPA</div>
                    <div class="data-line">{{ $q->Percentage_CGPA }}</div>
                </div>
            </div>
            @endforeach
        </div>


        <!-- ===========================================================
                           EXPERIENCE DETAILS
        ============================================================ -->
        <div class="section-block">
            <div class="section-title">EXPERIENCE DETAILS</div>

            @foreach($faculty->facultyExperienceMap as $exp)
            <div class="row mb-3">
                <div class="col-md-3">
                    <div class="label-sm">Years of Experience</div>
                    <div class="data-line">{{ $exp->Years_Of_Experience }}</div>
                </div>

                <div class="col-md-3">
                    <div class="label-sm">Area of Specialisation</div>
                    <div class="data-line">{{ $exp->Specialization }}</div>
                </div>

                <div class="col-md-3">
                    <div class="label-sm">Institution</div>
                    <div class="data-line">{{ $exp->pre_Institutions }}</div>
                </div>

                <div class="col-md-3">
                    <div class="label-sm">Position Held</div>
                    <div class="data-line">{{ $exp->Position_hold }}</div>
                </div>
            </div>
            @endforeach
        </div>


        <!-- ===========================================================
                           BANK DETAILS
        ============================================================ -->
        <div class="section-block">
            <div class="section-title">BANK DETAILS</div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="label-sm">Bank Name</div>
                    <div class="data-line">{{ $faculty->bank_name }}</div>
                </div>

                <div class="col-md-4">
                    <div class="label-sm">Account Number</div>
                    <div class="data-line">{{ $faculty->Account_No }}</div>
                </div>

                <div class="col-md-4">
                    <div class="label-sm">IFSC Code</div>
                    <div class="data-line">{{ $faculty->IFSC_Code }}</div>
                </div>
            </div>
        </div>


        <!-- ===========================================================
                           AREA OF EXPERTISE
        ============================================================ -->
        <div class="section-block">
            <div class="section-title">AREA OF EXPERTISE</div>

            <div class="row">
                @foreach($faculty->facultyExpertiseMap as $area)
                <div class="col-md-3 mb-2">
                    <div class="data-line">{{ $area->facultyExpertise->expertise_name }}</div>
                </div>
                @endforeach
            </div>
        </div>


        <!-- ===========================================================
                           OTHER INFORMATION
        ============================================================ -->
        <div class="section-block">
            <div class="section-title">OTHER INFORMATION</div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="label-sm">Joining Date</div>
                    <div class="data-line">{{ format_date($faculty->joining_date) }}</div>
                </div>

                <div class="col-md-4">
                    <div class="label-sm">Current Sector</div>
                    <div class="data-line">
                        {{ $faculty->faculty_sector == 1 ? 'Government Sector' : 'Private Sector' }}
                    </div>
                </div>
            </div>
        </div>


        <!-- ===========================================================
                           BUTTONS
        ============================================================ -->
        <div class="no-print text-end mt-4">
            <a href="{{ route('faculty.index') }}" class="btn btn-secondary">Back</a>
            <button class="btn btn-primary" onclick="window.print()">Print</button>
        </div>
</div>

@endsection
