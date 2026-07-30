@extends('admin.layouts.master')
@section('title', 'View Faculty Details')

@section('setup_content')

<style>
/* ------------------------------
   GLOBAL + GIGW COMPLIANT STYLE
--------------------------------*/
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
    width: 170px;
    height: 170px;
    object-fit: fill;
    border-radius: 6px;
    border: 1px solid #ddd;
}

.section-block {
    margin-bottom: 0;
}

/* Printing goes through the standard print sheet (faculty.print), not this
   screen view — see resources/views/admin/faculty/details_print.blade.php. */
</style>

<div class="container-fluid faculty-show-page">
    <x-breadcrum title="Faculty Details">
        <div class="d-flex flex-wrap align-items-center gap-2 no-print">
            <a href="{{ route('faculty.edit', ['id' => encrypt($faculty->pk)]) }}"
               class="btn programme-dt-btn-columns border-0 text-primary" title="Edit Faculty">
                <i class="bi bi-pencil" aria-hidden="true"></i>
                <span>Edit</span>
            </a>
            <a href="{{ route('faculty.print', ['id' => encrypt($faculty->pk)]) }}" target="_blank" rel="noopener"
               class="btn programme-dt-btn-columns border-0 text-primary" title="Print">
                <i class="bi bi-printer" aria-hidden="true"></i>
                <span>Print</span>
            </a>
        </div>
    </x-breadcrum>


        <!-- ===========================================================
                HEADER + PHOTO + NAME
        ============================================================ -->
        <div class="card overflow-hidden rounded-3 mb-4">
            <div class="card-body p-3 p-md-4">
                <div class="row align-items-center">
                    <div class="col-md-2 text-center">
                        <img src="{{ $faculty->photo_uplode_path ? asset('storage/'.$faculty->photo_uplode_path) : asset('images/dummypic.jpeg') }}"
                             alt="Faculty Photo" class="profile-photo">
                    </div>

                    <div class="col-md-10">
                        <div class="label-sm">Full Name:</div>
                        <div class="data-line">{{ $faculty->full_name }} ( {{ $faculty->faculty_code }} )</div>

                        <div class="label-sm mt-3">Faculty Type:</div>
                        <div class="data-line">{{ $faculty->facultyTypeMaster->faculty_type_name ?? '-' }}</div>

                        @if($faculty->faculty_type == '1' && $faculty->faculty_pa)
                        <div class="label-sm mt-3">Faculty (PA):</div>
                        <div class="data-line">{{ $faculty->faculty_pa }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>


        <!-- ===========================================================
                           PERSONAL INFORMATION
        ============================================================ -->
        <div class="card overflow-hidden rounded-3 mb-4">
            <div class="card-body p-3 p-md-4">
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

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="label-sm">Current Designation</div>
                            <div class="data-line">
                                {{ $faculty->current_designation ?? '-' }}
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="label-sm">Current Department</div>
                            <div class="data-line">
                                {{ $faculty->current_department ?? '-' }}
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="label-sm">Address</div>
                            <div class="data-line">
                                {{ $faculty->countryMaster->country_name ?? '' }},
                                {{ $faculty->stateMaster->state_name ?? '' }},
                                {{ $faculty->districtMaster->district_name ?? '' }},
                                {{ $faculty->cityMaster->city_name ?? '' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!-- ===========================================================
                           QUALIFICATION DETAILS
        ============================================================ -->
        <div class="card overflow-hidden rounded-3 mb-4">
            <div class="card-body p-3 p-md-4">
                <div class="section-block">
                    <div class="section-title">QUALIFICATION DETAILS</div>

                    @forelse($faculty->facultyQualificationMap as $q)
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
                    @empty
                    <p class="text-body-secondary mb-0">No qualification details recorded.</p>
                    @endforelse
                </div>
            </div>
        </div>


        <!-- ===========================================================
                           EXPERIENCE DETAILS
        ============================================================ -->
        <div class="card overflow-hidden rounded-3 mb-4">
            <div class="card-body p-3 p-md-4">
                <div class="section-block">
                    <div class="section-title">EXPERIENCE DETAILS</div>

                    @forelse($faculty->facultyExperienceMap as $exp)
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
                    @empty
                    <p class="text-body-secondary mb-0">No experience details recorded.</p>
                    @endforelse
                </div>
            </div>
        </div>


        <!-- ===========================================================
                           BANK DETAILS
        ============================================================ -->
        <div class="card overflow-hidden rounded-3 mb-4">
            <div class="card-body p-3 p-md-4">
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
            </div>
        </div>


        <!-- ===========================================================
                           AREA OF EXPERTISE
        ============================================================ -->
        <div class="card overflow-hidden rounded-3 mb-4">
            <div class="card-body p-3 p-md-4">
                <div class="section-block">
                    <div class="section-title">AREA OF EXPERTISE</div>

                    @php
                        // Some mappings point at expertise rows that no longer exist;
                        // drop those instead of rendering an empty box for each.
                        $expertiseNames = $faculty->facultyExpertiseMap
                            ->map(fn ($area) => $area->facultyExpertise?->expertise_name)
                            ->filter()
                            ->values();
                    @endphp

                    <div class="row">
                        @forelse($expertiseNames as $name)
                        <div class="col-md-3 mb-2">
                            <div class="data-line">{{ $name }}</div>
                        </div>
                        @empty
                        <div class="col-12">
                            <p class="text-body-secondary mb-0">No area of expertise recorded.</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>


        <!-- ===========================================================
                           OTHER INFORMATION
        ============================================================ -->
        <div class="card overflow-hidden rounded-3 mb-4">
            <div class="card-body p-3 p-md-4">
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
                                @php
                                    $sectorName = \Illuminate\Support\Facades\DB::table('faculty_sector_master')
                                        ->where('pk', $faculty->faculty_sector)
                                        ->value('name');
                                @endphp
                                {{ $sectorName ?? '-' }}
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="label-sm">Service</div>
                            <div class="data-line">
                                @php
                                    $serviceName = \Illuminate\Support\Facades\DB::table('service_master')
                                        ->where('pk', $faculty->service_master_pk)
                                        ->value('service_name');
                                @endphp
                                {{ $serviceName ?? '-' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

</div>

@endsection
