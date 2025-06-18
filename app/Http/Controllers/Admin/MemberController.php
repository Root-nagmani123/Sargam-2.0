<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\Admin\Member\{
    StoreMemberStep1Request,
    StoreMemberStep2Request,
    StoreMemberStep3Request,
    StoreMemberStep4Request,
    StoreMemberStep5Request,
};
use App\Models\{EmployeeMaster, EmployeeRoleMapping, UserCredential};
use App\DataTables\MemberDataTable;
use App\Exports\MemberExport;
use Maatwebsite\Excel\Facades\Excel;
class MemberController extends Controller
{
    public function index(MemberDataTable $dataTable)
    {
        return $dataTable->render('admin.member.index');
    }

    public function create()
    {
        return view('admin.member.create');
    }

    private function validateEmpId(Request $request)
    {
        if (!$request->has('emp_id')) {
            abort(response()->json(['error' => 'Employee ID is required'], 422));
        }
    }

    private function saveStep1Data(Request $request)
    {
        return EmployeeMaster::create([
            'title' => $request->title,
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name' => $request->last_name,
            'father_name' => $request->father_husband_name,
            'marital_status' => $request->marital_status,
            'gender' => $request->gender,
            'caste_category_pk' => $request->caste_category,
            'height' => $request->height,
            'dob' => $request->date_of_birth,
        ]);
    }

    private function saveStep2Data(Request $request)
    {

        $this->validateEmpId($request);

        EmployeeMaster::where('pk', (int) $request->emp_id)->update([
            'emp_type' => $request->type, // Employee Type
            'emp_id' => $request->id, // Employee ID
            'emp_group_pk' => $request->group, // Employee Group
            'designation_master_pk' => $request->designation, // Designation
            'department_master_pk' => $request->section // department
        ]);

        return EmployeeMaster::find($request->emp_id);
    }

    private function saveStep3Data(Request $request)
    {
        $this->validateEmpId($request);
        \Log::info('Saving Step 3 Data', $request->all());
        return EmployeeMaster::find($request->emp_id);
    }

    private function saveStep4Data(Request $request)
    {
        if (!$request->has('emp_id')) {
            return response()->json(['error' => 'Employee ID is required'], 422);
        }

        $address = [
            'current_address' => $request->address,
            'country_master_pk' => $request->country,
            'state_master_pk' => $request->state,
            'state_district_mapping_pk' => $request->district,
            'city' => $request->city,
            'zipcode' => $request->postal,

            'permanent_address' => $request->permanentaddress,
            'pcountry_master_pk' => $request->permanentcountry,
            'pstate_master_pk' => $request->permanentstate,
            'pstate_district_mapping_pk' => $request->permanentdistrict,
            'pcity' => $request->permanentcity,
            'pzipcode' => $request->permanentpostal,

            'email' => $request->personalemail,
            'officalemail' => $request->officialemail,
            'mobile' => $request->mnumber,
            'emergency_contact_no' => $request->emergencycontact,
            'landline_contact_no' => $request->landlinenumber,
        ];
        EmployeeMaster::find($request->emp_id)->update($address);
        return EmployeeMaster::find($request->emp_id);
    }

    public function validateStep(Request $request, $step)
    {
        $validatorClass = "App\\Http\\Requests\\Admin\\Member\\StoreMemberStep{$step}Request";
        if (!class_exists($validatorClass)) {
            return response()->json(['error' => 'Invalid step'], 400);
        }

        $validator = Validator::make($request->all(), (new $validatorClass())->rules());

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $method = "saveStep{$step}Data";
        $responseData = method_exists($this, $method) ? $this->{$method}($request) : null;

        if (!$responseData) {
            return response()->json(['error' => 'Failed to save data'], 500);
        }

        return response()->json([
            'message' => "Step $step validated and data saved.",
            'pk' => $responseData->pk
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), (new StoreMemberStep5Request())->rules());

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $profile_picture = $additional_doc_upload = null;
        if ($request->hasFile('picture')) {
            $profile_picture = $request->file('picture')->store('members', 'public');
        }
        if ($request->hasFile('additionaldocument')) {
            $additional_doc_upload = $request->file('additionaldocument')->store('members', 'public');
        }

        EmployeeMaster::find($request->emp_id)->update([
            'residence_no' => $request->residencenumber,
            'home_town_details' => $request->homeaddress,
            'other_miscellaneous_fields' => $request->miscellaneous,
            'additional_doc_upload' => $additional_doc_upload,
            'profile_picture' => $profile_picture,
        ]);

        $userCredential = UserCredential::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email_id' => $request->personalemail,
            'mobile_no' => $request->mnumber,
            'reg_date' => now(),
            'user_id' => $request->emp_id,
            'user_name' => $request->userid
        ]);

        if ($userCredential) {
            $roles = is_array($request->userrole) ? $request->userrole : [$request->userrole];
            foreach ($roles as $role) {
                EmployeeRoleMapping::create([
                    'user_credentials_pk' => $userCredential->pk,
                    'user_role_master_pk' => $role,
                ]);
            }
        }

        return response()->json(['message' => 'Member successfully created']);
    }

    public function update(Request $request) {
        $validator = Validator::make($request->all(), (new StoreMemberStep5Request())->rules());
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $profile_picture = $additional_doc_upload = null;
        if ($request->hasFile('picture')) {
            $profile_picture = $request->file('picture')->store('members', 'public');
        }
        if ($request->hasFile('additionaldocument')) {
            $additional_doc_upload = $request->file('additionaldocument')->store('members', 'public');
        }

        EmployeeMaster::find($request->emp_id)->update([
            'residence_no' => $request->residencenumber,
            'home_town_details' => $request->homeaddress,
            'other_miscellaneous_fields' => $request->miscellaneous,
            'additional_doc_upload' => $additional_doc_upload,
            'profile_picture' => $profile_picture,
        ]);

        UserCredential::updateOrCreate(
            ['user_id' => $request->emp_id], // Search condition
            [
                'first_name'  => $request->first_name,
                'last_name'   => $request->last_name,
                'email_id'    => $request->personalemail,
                'mobile_no'   => $request->mnumber,
                'user_name'   => $request->userid
            ]
        );
        $userCredential = UserCredential::where('user_id', $request->emp_id)->first();

        if ($userCredential) {
            $roles = is_array($request->userrole) ? $request->userrole : [$request->userrole];

            EmployeeRoleMapping::where('user_credentials_pk', $userCredential->pk)->delete();

            foreach ($roles as $role) {
                EmployeeRoleMapping::create([
                    'user_credentials_pk' => $userCredential->pk,
                    'user_role_master_pk' => $role,
                ]);
            }
        }

        return response()->json(['message' => 'Member successfully updated']);
    }

    public function loadStep($step)
    {
        return view("admin.member.steps.step{$step}");
    }

    public function show($id)
    {
        $member = EmployeeMaster::findOrFail(decrypt($id));
        return view('admin.member.show', compact('member'));
    }

    public function edit($id) {
        $member = EmployeeMaster::findOrFail($id);
        return view('admin.member.edit', compact('member'));
    }

    function editStep($step, $id)
    {
        $member = EmployeeMaster::findOrFail($id);
        return view("admin.member.edit_steps.step{$step}", compact('member'));
    }

    public function updateValidateStep(Request $request, $step, $id)
    {
        $request->merge(['emp_id' => ($id)]);
        $validatorClass = "App\\Http\\Requests\\Admin\\Member\\StoreMemberStep{$step}Request";
        if (!class_exists($validatorClass)) {
            return response()->json(['error' => 'Invalid step'], 400);
        }

        $validator = Validator::make($request->all(), (new $validatorClass())->rules());

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $method = "updatedStep{$step}Data";
        $responseData = method_exists($this, $method) ? $this->{$method}($request) : null;

        if (!$responseData) {
            return response()->json(['error' => 'Failed to save data'], 500);
        }

        return response()->json([
            'message' => "Step $step validated and data saved.",
            'pk' => $responseData->pk
        ], 200);
    }

    function updatedStep1Data(Request $request)
    {
        EmployeeMaster::where('pk', (int) $request->emp_id)->update([
            'title' => $request->title,
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name' => $request->last_name,
            'father_name' => $request->father_husband_name,
            'marital_status' => $request->marital_status,
            'gender' => $request->gender,
            'caste_category_pk' => $request->caste_category,
            'height' => $request->height,
            'dob' => $request->date_of_birth
        ]);
        return EmployeeMaster::where('pk', (int) $request->emp_id)->first();
    }

    function updatedStep2Data(Request $request)
    {
        EmployeeMaster::where('pk', (int) $request->emp_id)->update([  
            'emp_type' => $request->type, // Employee Type
            'emp_id' => $request->id, // Employee ID
            'emp_group_pk' => $request->group, // Employee Group
            'designation_master_pk' => $request->designation, // Designation
            'department_master_pk' => $request->section // department
        ]);
        return EmployeeMaster::where('pk', (int) $request->emp_id)->first();
    }

    function updatedStep3Data(Request $request)
    {
        return EmployeeMaster::find($request->emp_id);
    }

    function updatedStep4Data(Request $request)
    {
        $address = [
            'current_address' => $request->address,
            'country_master_pk' => $request->country,
            'state_master_pk' => $request->state,
            'state_district_mapping_pk' => $request->district,
            'city' => $request->city,
            'zipcode' => $request->postal,

            'permanent_address' => $request->permanentaddress,
            'pcountry_master_pk' => $request->permanentcountry,
            'pstate_master_pk' => $request->permanentstate,
            'pstate_district_mapping_pk' => $request->permanentdistrict,
            'pcity' => $request->permanentcity,
            'pzipcode' => $request->permanentpostal,

            'email' => $request->personalemail,
            'officalemail' => $request->officialemail,
            'mobile' => $request->mnumber,
            'emergency_contact_no' => $request->emergencynumber,
            'landline_contact_no' => $request->landlinenumber,
        ];
        EmployeeMaster::find($request->emp_id)->update($address);
        return EmployeeMaster::find($request->emp_id);
    }

    public function excelExport(Request $request)
    {
        $fileName = 'members-'.date('d-m-Y').'.xlsx';
        return Excel::download(new MemberExport, $fileName);
    }
}