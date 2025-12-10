@extends('admin.layouts.master')
@section('title', 'Faculty Details – Blank Form')

@section('setup_content')

<style>
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
    background: #f5f5f5;
}

.section-block {
    margin-bottom: 28px;
}

/* Print */
@media print {
    body * { visibility: hidden !important; }
    .print-area, .print-area * { visibility: visible !important; }
    .print-area { position: absolute; left: 0; top: 0; width: 100%; padding: 0 30px; }
    .no-print { display: none !important; }
}
</style>

<div class="container-fluid print-area">
 <!-- ===========================================================
                    HEADER + PHOTO + NAME
        ============================================================ -->
        <div class="row mb-4 align-items-center">
            <div class="col-md-2 text-center">
                <div class="profile-photo"></div>
            </div>

            <div class="col-md-10">
                <div class="label-sm">Full Name:</div>
                <div class="data-line"></div>

                <div class="label-sm mt-3">Faculty Type:</div>
                <div class="data-line"></div>
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
                    <div class="data-line"></div>
                </div>

                <div class="col-md-4">
                    <div class="label-sm">Mobile Number</div>
                    <div class="data-line"></div>
                </div>

                <div class="col-md-4">
                    <div class="label-sm">Email ID</div>
                    <div class="data-line"></div>
                </div>
            </div>

            <div class="label-sm mt-2">Address</div>
            <div class="data-line mb-2"></div>
        </div>


        <!-- ===========================================================
                           QUALIFICATION DETAILS
        ============================================================ -->
        <div class="section-block">
            <div class="section-title">QUALIFICATION DETAILS</div>

            @for($i=1; $i<=3; $i++)
            <div class="row mb-3">
                <div class="col-md-3">
                    <div class="label-sm">Degree</div>
                    <div class="data-line"></div>
                </div>

                <div class="col-md-3">
                    <div class="label-sm">University</div>
                    <div class="data-line"></div>
                </div>

                <div class="col-md-3">
                    <div class="label-sm">Passing Year</div>
                    <div class="data-line"></div>
                </div>

                <div class="col-md-3">
                    <div class="label-sm">Percentage / CGPA</div>
                    <div class="data-line"></div>
                </div>
            </div>
            @endfor
        </div>


        <!-- ===========================================================
                           EXPERIENCE DETAILS
        ============================================================ -->
        <div class="section-block">
            <div class="section-title">EXPERIENCE DETAILS</div>

            @for($i=1; $i<=3; $i++)
            <div class="row mb-3">
                <div class="col-md-3">
                    <div class="label-sm">Years of Experience</div>
                    <div class="data-line"></div>
                </div>

                <div class="col-md-3">
                    <div class="label-sm">Area of Specialisation</div>
                    <div class="data-line"></div>
                </div>

                <div class="col-md-3">
                    <div class="label-sm">Institution</div>
                    <div class="data-line"></div>
                </div>

                <div class="col-md-3">
                    <div class="label-sm">Position Held</div>
                    <div class="data-line"></div>
                </div>
            </div>
            @endfor
        </div>


        <!-- ===========================================================
                           BANK DETAILS
        ============================================================ -->
        <div class="section-block">
            <div class="section-title">BANK DETAILS</div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="label-sm">Bank Name</div>
                    <div class="data-line"></div>
                </div>

                <div class="col-md-4">
                    <div class="label-sm">Account Number</div>
                    <div class="data-line"></div>
                </div>

                <div class="col-md-4">
                    <div class="label-sm">IFSC Code</div>
                    <div class="data-line"></div>
                </div>
            </div>
        </div>


        <!-- ===========================================================
                           AREA OF EXPERTISE
        ============================================================ -->
        <div class="section-block">
            <div class="section-title">AREA OF EXPERTISE</div>

            <div class="row">
                @for($i=1; $i<=6; $i++)
                <div class="col-md-3 mb-2">
                    <div class="data-line"></div>
                </div>
                @endfor
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
                    <div class="data-line"></div>
                </div>

                <div class="col-md-4">
                    <div class="label-sm">Current Sector</div>
                    <div class="data-line"></div>
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
