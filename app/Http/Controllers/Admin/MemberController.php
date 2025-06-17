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
        if ($request->hasFile('additional_doc_upload')) {
            $additional_doc_upload = $request->file('additional_doc_upload')->store('members', 'public');
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

        UserCredential::where('user_id', $request->emp_id)->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email_id' => $request->personalemail,
            'mobile_no' => $request->mnumber,
            // 'user_name' => $request->userid
        ]);
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
        $this->validateEmpId($request);
        \Log::info('Saving Step 3 Data', $request->all());
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
}

    // namespace App\Http\Controllers\Admin;

    // use App\Http\Controllers\Controller;
    // use Illuminate\Http\Request;
    // use App\Http\Requests\Admin\Member\{
    //     StoreMemberStep1Request,
    //     StoreMemberStep2Request,
    //     StoreMemberStep3Request,
//     StoreMemberStep4Request,
//     StoreMemberStep5Request,
// };
// use Illuminate\Support\Facades\Validator;
// use App\Models\{EmployeeMaster, EmployeeRoleMapping, UserCredential};
// use App\DataTables\MemberDataTable;

// class MemberController extends Controller
// {
//     public function index(MemberDataTable $dataTable)
//     {
//         return $dataTable->render('admin.member.index');
//     }

//     public function create()
//     {
//         return view('admin.member.create');
//     }

//     private function saveStep1Data(Request $request)
//     {
//         // Save step 1 data logic here
//         return EmployeeMaster::create([
//             'title' => $request->title,
//             'first_name' => $request->first_name,
//             'middle_name' => $request->middle_name,
//             'last_name' => $request->last_name,
//             'father_name' => $request->father_husband_name,
//             'marital_status' => $request->marital_status,
//             'gender' => $request->gender,
//             'caste_category_pk' => $request->caste_category,
//             'height' => $request->height,
//             'dob' => $request->date_of_birth,
//         ]);
//     }

//     private function saveStep2Data(Request $request)
//     {

//         if (!$request->has('emp_id')) {
//             return response()->json(['error' => 'Employee ID is required'], 422);
//         }

//         EmployeeMaster::where('pk', (int) $request->emp_id)->update([
//             'emp_type' => $request->type, // Employee Type
//             'emp_id' => $request->id, // Employee ID
//             'emp_group_pk' => $request->group, // Employee Group
//             'designation_master_pk' => $request->designation, // Designation
//             'department_master_pk' => $request->section // department
//         ]);

//         return EmployeeMaster::find($request->emp_id);
//     }

//     private function saveStep3Data(Request $request)
//     {

//         if (!$request->has('emp_id')) {
//             return response()->json(['error' => 'Employee ID is required'], 422);
//         }
//         // dd($request->all());
//         \Log::info('Saving Step 3 Data', $request->all());

//         return EmployeeMaster::find($request->emp_id);
//     }

//     private function saveStep4Data(Request $request)
//     {
//         if (!$request->has('emp_id')) {
//             return response()->json(['error' => 'Employee ID is required'], 422);
//         }

//         $address = [
//             'current_address' => $request->address,
//             'country_master_pk' => $request->country,
//             'state_master_pk' => $request->state,
//             'city' => $request->city,
//             'zipcode' => $request->postal,

//             'permanent_address' => $request->permanentaddress,
//             'pcountry_master_pk' => $request->permanentcountry,
//             'pstate_master_pk' => $request->permanentstate,
//             'pcity' => $request->permanentcity,
//             'pzipcode' => $request->permanentpostal,

//             'email' => $request->personalemail,
//             'officalemail' => $request->officialemail,
//             'mobile' => $request->mnumber
//         ];
//         EmployeeMaster::find($request->emp_id)->update($address);
//         return EmployeeMaster::find($request->emp_id);
//     }

//     // public function validateStep(Request $request, $step)
//     // {
//     //     $rules = [];

//     //     switch ($step) {
//     //         case 1:
//     //             $validator = Validator::make($request->all(), (new StoreMemberStep1Request())->rules());
//     //             $this->saveStep1Data($request);
//     //             break;
//     //         case 2:
//     //             $validator = Validator::make($request->all(), (new StoreMemberStep2Request())->rules());
//     //             $this->saveStep2Data($request);
//     //             break;
//     //         case 3:
//     //             $validator = Validator::make($request->all(), (new StoreMemberStep3Request())->rules());
//     //             $this->saveStepData($request);
//     //             break;
//     //         case 4:
//     //             $validator = Validator::make($request->all(), (new StoreMemberStep4Request())->rules());
//     //             $this->saveStepData($request, 4);
//     //             break;
//     //         case 5:
//     //             $validator = Validator::make($request->all(), (new StoreMemberStep5Request())->rules());
//     //             break;
//     //     }



//     //     // if ($validator->fails()) {
//     //     //     return response()->json(['errors' => $validator->errors()], 422);
//     //     // }

//     //     // return response()->json(['message' => 'Step validated'], 200);
//     // }


//     public function validateStep(Request $request, $step)
//     {
//         $validatorClass = "App\\Http\\Requests\\Admin\\Member\\StoreMemberStep{$step}Request";
//         if (!class_exists($validatorClass)) {
//             return response()->json(['error' => 'Invalid step'], 400);
//         }

//         $validator = Validator::make($request->all(), (new $validatorClass())->rules());
//         if ($validator->fails()) {
//             return response()->json(['errors' => $validator->errors()], 422);
//         }

//         $method = "saveStep{$step}Data";

//         $responseData = null;
//         if (method_exists($this, $method)) {
//             $responseData = $this->{$method}($request);
//         }

//         if (!$responseData) {
//             return response()->json(['error' => 'Failed to save data'], 500);
//         }

//         return response()->json(['message' => "Step $step validated and data saved.", 'pk' => $responseData->pk], 200);
//     }


//     public function store(Request $request)
//     {
//         // dd($request->all());
//         $validator = Validator::make($request->all(), (new StoreMemberStep5Request())->rules());

//         if ($validator->fails()) {
//             // echo "inside the if"; die;
//             return response()->json(['errors' => $validator->errors()], 422);
//         }

//         if ($request->hasFile('picture')) {
//             $photoPath = $request->file('picture')->store('members', 'public');
//         }

//         if ($request->hasFile('signature')) {
//             $signaturePath = $request->file('signature')->store('members', 'public');
//         }

//         $address = [
//             'residence_no' => $request->residencenumber,
//             // 'residence_no' => $request->residencenumber,
//         ];

//         EmployeeMaster::find($request->emp_id)->update($address);

//         //         array:40 [ // app/Http/Controllers/Admin/MemberController.php:197
// //   "_token" => "8ZiLdRFT7A3SuPnn3lrmFLkbygV99ica5hQBGIkC"
// //   "title" => "2"
// //   "first_name" => "Graiden"
// //   "middle_name" => "Raya Wilkerson"
// //   "last_name" => "Grant"
// //   "father_husband_name" => "Angela Kramer"
// //   "marital_status" => "2"
// //   "gender" => "1"
// //   "caste_category" => "4"
// //   "height" => "1234"
// //   "date_of_birth" => "1980-08-26"
// //   "emp_id" => "12"
// //   "employeePK" => "12"
// //   "type" => "1362835143"
// //   "id" => "Error voluptatibus a"
// //   "group" => "4"
// //   "designation" => "1507800024"
// //   "userid" => "123"
// //   "section" => "1912178136"
// //   "userrole" => array:2 [
// //     0 => "16"
// //     1 => "17"
// //   ]
// //   "address" => "Veritatis ut quae ha"
// //   "country" => "7"
// //   "state" => "5"
// //   "district" => "756"
// //   "city" => "5"
// //   "postal" => "Lorem mollit magni q"
// //   "styled_max_checkbox" => "on"
// //   "permanentaddress" => "Veritatis ut quae ha"
// //   "permanentcountry" => "7"
// //   "permanentstate" => "5"
// //   "permanentdistrict" => "756"
// //   "permanentcity" => "5"
// //   "permanentpostal" => "Lorem mollit magni q"
// //   "personalemail" => "samaruqi@mailinator.com"
// //   "officialemail" => "kocigiz@mailinator.com"
// //   "mnumber" => "2452452451"
// //   "homeaddress" => "Architecto ad ea nob"
// //   "residencenumber" => "824824824824"
// //   "miscellaneous" => "Quis dignissimos sim"


//         $userCredential = UserCredential::create([
//             'first_name' => $request->first_name,
//             'last_name' => $request->last_name,
//             'email_id' => $request->personalemail,
//             'mobile_no' => $request->mnumber,
//             'reg_date' => now(),
//             'user_id' => $request->emp_id,
//             'user_name' => $request->userid
//         ]);

//         if ($userCredential) {
//             // EmployeeRoleMapping::create([
//             //     'user_credentials_pk' => $userCredential->pk,
//             //     'user_role_master_pk' => $request->userrole,
//             // ]);

//             $request->userrole = is_array($request->userrole) ? $request->userrole : [$request->userrole];
//             foreach ($request->userrole as $role) {
//                 EmployeeRoleMapping::create([
//                     'user_credentials_pk' => $userCredential->pk,
//                     'user_role_master_pk' => $role,
//                 ]);
//             }
//         }

//         dd($request->all());

//         // return response()->json(['message' => 'Member successfully created']);
//     }


//     public function loadStep($step)
//     {
//         return view("admin.member.steps.step{$step}");
//     }
// }
