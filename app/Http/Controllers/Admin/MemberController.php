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
use App\Models\{EmployeeMaster, EmployeeRoleMapping, UserCredential, City};
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

    private function saveOrUpdateStep1Data(Request $request)
    {
        $data = [
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
        ];

        if ($request->filled('emp_id')) {
            // Update
            EmployeeMaster::where('pk', (int) $request->emp_id)->update($data);
            return EmployeeMaster::where('pk', (int) $request->emp_id)->first();
        } else {
            // Create
            return EmployeeMaster::create($data);
        }
    }


    // private function saveStep2Data(Request $request)
    // {

    //     $this->validateEmpId($request);

    //     EmployeeMaster::where('pk', (int) $request->emp_id)->update([
    //         'emp_type' => $request->type, // Employee Type
    //         'emp_id' => $request->id, // Employee ID
    //         'emp_group_pk' => $request->group, // Employee Group
    //         'designation_master_pk' => $request->designation, // Designation
    //         'department_master_pk' => $request->section // department
    //     ]);

    //     return EmployeeMaster::find($request->emp_id);
    // }

    private function saveOrUpdateStep2Data(Request $request)
    {
        $this->validateEmpId($request);

        $data = [
            'emp_type' => $request->type,
            'emp_id' => $request->id,
            'emp_group_pk' => $request->group,
            'designation_master_pk' => $request->designation,
            'department_master_pk' => $request->section,
        ];

        EmployeeMaster::where('pk', (int) $request->emp_id)->update($data);

        return EmployeeMaster::find((int) $request->emp_id);
    }


    // private function saveStep3Data(Request $request)
    // {
    //     $this->validateEmpId($request);
    //     \Log::info('Saving Step 3 Data', $request->all());
    //     return EmployeeMaster::find($request->emp_id);
    // }


    private function saveOrUpdateStep3Data(Request $request)
    {
        $this->validateEmpId($request);

        \Log::info('Saving Step 3 Data', $request->all());

        return EmployeeMaster::find((int) $request->emp_id);
    }


    // private function saveStep4Data(Request $request)
    // {
    //     if (!$request->has('emp_id')) {
    //         return response()->json(['error' => 'Employee ID is required'], 422);
    //     }

    //     $address = [
    //         'current_address' => $request->address,
    //         'country_master_pk' => $request->country,
    //         'state_master_pk' => $request->state,
    //         'state_district_mapping_pk' => $request->district,
    //         'city' => $request->city,
    //         'zipcode' => $request->postal,

    //         'permanent_address' => $request->permanentaddress,
    //         'pcountry_master_pk' => $request->permanentcountry,
    //         'pstate_master_pk' => $request->permanentstate,
    //         'pstate_district_mapping_pk' => $request->permanentdistrict,
    //         'pcity' => $request->permanentcity,
    //         'pzipcode' => $request->permanentpostal,

    //         'email' => $request->personalemail,
    //         'officalemail' => $request->officialemail,
    //         'mobile' => $request->mnumber,
    //         'emergency_contact_no' => $request->emergencycontact,
    //         'landline_contact_no' => $request->landlinenumber,
    //     ];
    //     EmployeeMaster::find($request->emp_id)->update($address);
    //     return EmployeeMaster::find($request->emp_id);
    // }

    private function saveOrUpdateStep4Data(Request $request)
    {
        // dd($request->all());
        if (!$request->has('emp_id')) {
            return response()->json(['error' => 'Employee ID is required'], 422);
        }

        $address = [
            'current_address' => $request->address,
            'country_master_pk' => $request->country,
            'state_master_pk' => $request->state,
            'state_district_mapping_pk' => $request->district,
            'zipcode' => $request->postal,

            'permanent_address' => $request->permanentaddress,
            'pcountry_master_pk' => $request->permanentcountry,
            'pstate_master_pk' => $request->permanentstate,
            'pstate_district_mapping_pk' => $request->permanentdistrict,
            // 'pcity' => $request->permanentcity,
            'pzipcode' => $request->permanentpostal,

            'email' => $request->personalemail,
            'officalemail' => $request->officialemail,
            'mobile' => $request->mnumber,
            'emergency_contact_no' => $request->emergencycontact ?? $request->emergencynumber,
            'landline_contact_no' => $request->landlinenumber,
        ];

        if(!empty($request->other_city)) {
            $otherCity = City::firstOrCreate(
                [
                    'country_master_pk' => $request->country,
                    'state_master_pk' => $request->state,
                    'district_master_pk' => $request->district,
                    'city_name' => $request->other_city,
                    'active_inactive' => 1
                ],
            [
                'active_inactive' => 1
                ]
            );
                $address['city'] = $otherCity->pk;
        }
        else {
            $address['city'] = $request->city;
        }

        if(!empty($request->permanent_other_city)) {
            $permanentOtherCity = City::firstOrCreate(
                [
                    'country_master_pk' => $request->permanentcountry,
                    'state_master_pk' => $request->permanentstate,
                    'district_master_pk' => $request->permanentdistrict,
                    'city_name' => $request->permanent_other_city,
                    'active_inactive' => 1
                ],
            [
                'active_inactive' => 1
                ]
            );
            $address['pcity'] = $permanentOtherCity->pk;
        }
        else {
            $address['pcity'] = $request->permanentcity;
        }

        EmployeeMaster::where('pk', $request->emp_id)->update($address);

        return EmployeeMaster::find($request->emp_id);
    }


    public function validateStep(Request $request, $step)
    {
        $validatorClass = "App\\Http\\Requests\\Admin\\Member\\StoreMemberStep{$step}Request";

        if (!class_exists($validatorClass)) {
            return response()->json(['error' => 'Invalid step'], 400);
        }

        // Instantiate the FormRequest dynamically
        $formRequest = new $validatorClass;

        // Inject the container and initialize with current request data (no redirector)
        $formRequest->setContainer(app())->initialize(
            $request->query->all(),     // GET params
            $request->post(),           // POST data
            [], [],                     // attributes, cookies
            $request->files->all(),     // uploaded files
            $request->server->all(),    // server info
            $request->getContent()      // raw body
        );

        // Provide user resolver (for authorize() method)
        $formRequest->setUserResolver(fn () => $request->user());

        // Run authorization logic
        if (! $formRequest->authorize()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Run validation using FormRequest's rules & messages
        $validator = Validator::make(
            $formRequest->all(),
            $formRequest->rules(),
            $formRequest->messages(),
            $formRequest->attributes()
        );

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        // Call step-specific save/update method if it exists
        $method = "saveOrUpdateStep{$step}Data";
        $responseData = method_exists($this, $method) ? $this->{$method}($formRequest) : null;

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
        $requestRules = (new StoreMemberStep5Request())->rules();
        $requestMessages = (new StoreMemberStep5Request())->messages();
        
        $validator = Validator::make($request->all(), $requestRules, $requestMessages);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        createDirectory('members');
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
            'other_miscellaneous_fields' => $request->miscellaneous ?? null,
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
            'user_name' => $request->userid,
            'user_category' => 'E'
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

        $requestRules = (new StoreMemberStep5Request())->rules();
        $requestMessages = (new StoreMemberStep5Request())->messages();
        
        $validator = Validator::make($request->all(), $requestRules, $requestMessages);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        createDirectory('members');

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
            'other_miscellaneous_fields' => $request->miscellaneous ?? null,
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
                'user_name'   => $request->userid,
                'user_category' => 'E'
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

        $request->merge(['emp_id' => $id]);

        $validatorClass = "App\\Http\\Requests\\Admin\\Member\\StoreMemberStep{$step}Request";
        if (!class_exists($validatorClass)) {
            return response()->json(['error' => 'Invalid step'], 400);
        }

        // Dynamically instantiate the FormRequest
        $formRequest = new $validatorClass;

        // Manually initialize it without redirector
        $formRequest->setContainer(app())->initialize(
            $request->query->all(),
            $request->post(),
            [], [], $request->files->all(),
            $request->server->all(),
            $request->getContent()
        );

        // Resolve the user (for authorize())
        $formRequest->setUserResolver(fn () => $request->user());

        // Run authorization
        if (! $formRequest->authorize()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Run validation with rules & messages from FormRequest
        $validator = Validator::make(
            $formRequest->all(),
            $formRequest->rules(),
            $formRequest->messages(),
            $formRequest->attributes()
        );

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        // Call the step handler
        $method = "saveOrUpdateStep{$step}Data";
        $responseData = method_exists($this, $method) ? $this->{$method}($formRequest) : null;

        if (!$responseData) {
            return response()->json(['error' => 'Failed to save data'], 500);
        }

        return response()->json([
            'message' => "Step $step validated and data saved.",
            'pk' => $responseData->pk
        ], 200);
    }
    public function excelExport(Request $request)
    {
        $fileName = 'members-'.date('d-m-Y').'.xlsx';
        return Excel::download(new MemberExport, $fileName);
    }
}