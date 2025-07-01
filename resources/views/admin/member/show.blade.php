@extends('admin.layouts.master')

@section('title', 'Member Details - Sargam | Lal Bahadur Shastri National Academy of Administration')

@section('content')
    <div class="container py-4">
        <x-breadcrum title="Member Details" />
        <x-session_message />

        @php
            $sections = [
                'Personal Info' => [
                    'Title' => App\Models\EmployeeMaster::title[$member->title] ?? '',
                    'First Name' => $member->first_name,
                    'Middle Name' => $member->middle_name,
                    'Last Name' => $member->last_name,
                    'Father Name' => $member->father_name,
                    'DOB' => $member->dob,
                    'Gender' => App\Models\EmployeeMaster::gender[$member->gender] ?? '',
                    'Marital Status' => App\Models\EmployeeMaster::maritalStatus[$member->marital_status] ?? '',
                    // 'Nationality' => $member->nationality,
                    'Height' => $member->height,
                ],
                'Contact Info' => [
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
                'Employment Info' => [
                    'Employee ID' => $member->emp_id,
                    'Designation' => optional($member->designation)->designation_name ?? '',
                    'Department' => optional($member->department)->department_name ?? '',
                    // 'Reporting To' => $member->reporting_to_employee_pk,
                    // 'Alternate Reporting To' => $member->alternate_reporting_to_emp_pk,
                    // 'Experience' => $member->experience,
                    // 'Date of Joining' => $member->doj,
                    // 'Govt DOJ' => $member->govt_doj,
                    // 'Initial Leaving Date' => $member->initial_leaving_date,
                    // 'Appraisal Date' => $member->appraisal_date,
                    // 'Payroll Date' => $member->payroll_date,
                    // 'Payroll' => $member->payroll,
                    'Employee Type' => optional($member->employeeType)->category_type_name ?? '',
                    // 'Finance Book Code' => $member->finance_bookEntityCode,
                    'Official Email' => $member->officalemail,
                    'Employee Group' => optional($member->employeeGroup)->emp_group_name ?? '',
                ],
                // 'Identification' => [
                //     'Aadhar No' => $member->aadar_no,
                //     'PAN No' => $member->pan_no,
                //     'Passport No' => $member->passport_no,
                //     'Employee Govt ID' => $member->emp_gov_id,
                //     'Employee Group' => optional($member->employeeGroup)->emp_group_name ?? '',
                //     'Home Town Details' => $member->home_town_details,
                //     'Thumb Path' => $member->thumbPath,
                //     'Signature Path' => $member->sigPath,
                // ],
                // 'Audit Info' => [
                //     'Status' => $member->status,
                //     'Created By' => $member->created_by,
                //     'Created Date' => $member->created_date,
                //     'Modified By' => $member->modified_by,
                //     'Modified Date' => $member->modified_date,
                // ],
                'Uploaded Documents' => [
                    'Picture' => $member->profile_picture ? '<a href="' . asset('storage/' . $member->profile_picture) . '" target="_blank" class="btn btn-primary">View Picture</a>' : '—',
                    'Additional Document' => $member->additional_doc_upload ? '<a href="' . asset('storage/' . $member->additional_doc_upload) . '" target="_blank" class="btn btn-primary">View Document</a>' : '—',
                ],
            ];
        @endphp

        @foreach ($sections as $title => $fields)
            <div class="card mb-4 shadow-sm" style="border-left: 4px solid #004a93;">
                <div class="card-header" style="background-color: #fff; border-bottom: 2px solid #004a93;">
                    <h5 class="mb-0" style="color: #004a93 !important; font-size: 20px;font-weight: 600">{{ $title }}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach ($fields as $label => $value)
                            <div class="col-md-4 mb-3">
                                <label class="form-label text-muted small">{{ $label }}</label>
                                <div class="fw-semibold text-dark">
                                    {!! $value ?: '—' !!}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach

        <div class="card mb-4 shadow-sm" style="border-left: 4px solid #004a93;">
            <div class="card-header" style="background-color: #fff; border-bottom: 2px solid #004a93;">
                <h5 class="mb-0" style="color: #004a93 !important; font-size: 20px;font-weight: 600">Assigned Roles</h5>
            </div>
            <div class="card-body">

                @if($member->assignedRoles()->isEmpty())
                    <li class="list-group-item">No roles assigned</li>
                @else
                    @foreach($member->assignedRoles() as $role)
                        <span class="badge bg-secondary">{{ $role['role_name'] }}</span>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
@endsection