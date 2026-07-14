<?php
namespace App\Http\Controllers\Admin;

use App\DataTables\MemberDataTable;
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
use App\Exports\MemberExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\AppellationMaster;
class MemberController extends Controller
{
    public function index(MemberDataTable $dataTable)
    {
        return $dataTable->render('admin.member.index');
    }

    public function create()
    {
        $appellationMasterList = AppellationMaster::where('active_inactive', 1)
            ->pluck('appettation_name', 'pk')
            ->toArray();

        return view('admin.member.create', compact('appellationMasterList'));
    }

    /**
     * Pure field mappers for each step — build the employee_master attributes for that
     * step without writing to the DB. Used both for the per-step validate-only endpoints
     * and to assemble the single final create()/update() write.
     */
    private function mapStep1Data(Request $request): array
    {
        return [
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name' => $request->last_name,
            'father_name' => $request->father_husband_name,
            'marital_status' => $request->marital_status,
            'gender' => $request->gender,
            'caste_category_pk' => $request->caste_category,
            'appellation' => $request->appellation,
            'height' => $request->height,
            'dob' => $request->date_of_birth,
        ];
    }

    private function mapStep2Data(Request $request): array
    {
        return [
            'emp_type' => $request->type,
            'emp_id' => $request->id,
            'emp_group_pk' => $request->group,
            'designation_master_pk' => $request->designation,
            'department_master_pk' => $request->section,
        ];
    }

    private function mapStep4Data(Request $request): array
    {
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
            'pzipcode' => $request->permanentpostal,

            'email' => $request->personalemail,
            'officalemail' => $request->officialemail,
            'mobile' => $request->mnumber,
            'emergency_contact_no' => $request->emergencycontact ?? $request->emergencynumber,
            'landline_contact_no' => $request->landlinenumber,
        ];

        if (!empty($request->other_city)) {
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
        } else {
            $address['city'] = $request->city;
        }

        if (!empty($request->permanent_other_city)) {
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
        } else {
            $address['pcity'] = $request->permanentcity;
        }

        return $address;
    }

    private function mapStep5Data(Request $request, ?string $profilePicture, ?string $additionalDocUpload): array
    {
        return [
            'residence_no' => $request->residencenumber,
            'home_town_details' => $request->homeaddress,
            'other_miscellaneous_fields' => $request->miscellaneous ?? null,
            'additional_doc_upload' => $additionalDocUpload,
            'profile_picture' => $profilePicture,
        ];
    }

    /**
     * Validate a single step's fields against its StoreMemberStep{n}Request rules,
     * without persisting anything. Used while the wizard is in progress — the actual
     * DB write happens once, on the final store()/update() submit.
     */
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

        return response()->json([
            'message' => "Step $step validated.",
        ], 200);
    }

    /**
     * Merge rules()/messages() from all 5 step requests into one combined validator,
     * since the final submit carries every step's fields at once.
     */
    private function combinedMemberRules(): array
    {
        $requestClasses = [
            StoreMemberStep1Request::class,
            StoreMemberStep2Request::class,
            StoreMemberStep3Request::class,
            StoreMemberStep4Request::class,
            StoreMemberStep5Request::class,
        ];

        $rules = [];
        $messages = [];
        foreach ($requestClasses as $requestClass) {
            $instance = new $requestClass();
            $rules = array_merge($rules, $instance->rules());
            $messages = array_merge($messages, $instance->messages());
        }

        return [$rules, $messages];
    }

    public function store(Request $request)
    {
        [$rules, $messages] = $this->combinedMemberRules();

        $validator = Validator::make($request->all(), $rules, $messages);

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

        $employee = EmployeeMaster::create(array_merge(
            $this->mapStep1Data($request),
            $this->mapStep2Data($request),
            $this->mapStep4Data($request),
            $this->mapStep5Data($request, $profile_picture, $additional_doc_upload)
        ));

        $userCredential = UserCredential::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email_id' => $request->personalemail,
            'mobile_no' => $request->mnumber,
            'reg_date' => now(),
            'user_id' => $employee->pk,
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

        MemberDataTable::bumpListingCacheEpoch();

        return response()->json(['message' => 'Member successfully created']);
    }

    public function update(Request $request) {

        [$rules, $messages] = $this->combinedMemberRules();

        $validator = Validator::make($request->all(), $rules, $messages);

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

        EmployeeMaster::find($request->emp_id)->update(array_merge(
            $this->mapStep1Data($request),
            $this->mapStep2Data($request),
            $this->mapStep4Data($request),
            $this->mapStep5Data($request, $profile_picture, $additional_doc_upload)
        ));

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

        MemberDataTable::bumpListingCacheEpoch();

        return response()->json(['message' => 'Member successfully updated']);
    }

    public function loadStep($step)
    {
        $appellationMasterList = AppellationMaster::where('active_inactive', 1)
            ->pluck('appettation_name', 'pk')
            ->toArray();
        return view("admin.member.steps.step{$step}", compact('appellationMasterList'));
    }

    public function show($id)
    {
        $member = EmployeeMaster::with('appellationMaster')->findOrFail(decrypt($id));
        return view('admin.member.show', compact('member'));
    }

    public function edit($id) {
        $member = EmployeeMaster::findOrFail($id);
        $appellationMasterList = AppellationMaster::where('active_inactive', 1)
            ->pluck('appettation_name', 'pk')
            ->toArray();
        return view('admin.member.edit', compact('member', 'appellationMasterList'));
    }

    public function editProfile($id) {
        $member = EmployeeMaster::findOrFail($id);
        $appellationMasterList = AppellationMaster::where('active_inactive', 1)
            ->pluck('appettation_name', 'pk')
            ->toArray();
        return view('admin.member.edit_profile', compact('member', 'appellationMasterList'));
    }

    function editStep($step, $id)
    {
        $member = EmployeeMaster::findOrFail($id);
        $appellationMasterList = AppellationMaster::where('active_inactive', 1)
            ->pluck('appettation_name', 'pk')
            ->toArray();
        return view("admin.member.edit_steps.step{$step}", compact('member', 'appellationMasterList'));
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

        return response()->json([
            'message' => "Step $step validated.",
        ], 200);
    }
    public function excelExport(Request $request)
    {
        $fileName = 'members-'.date('d-m-Y').'.xlsx';
        return Excel::download(new MemberExport, $fileName);
    }

    public function toggleStatus(Request $request, $id)
    {
        try {
            // Find the member
            $member = EmployeeMaster::findOrFail($id);

            // Toggle status: 1 (active) ↔ 2 (inactive)
            $newStatus = $member->status == 1 ? 2 : 1;

            // Update the status
            $member->update(['status' => $newStatus]);

            // Bump cache epoch to refresh datatable
            MemberDataTable::bumpListingCacheEpoch();

            // Prepare response message
            $statusLabel = $newStatus == 1 ? 'Active' : 'Inactive';

            // Return JSON response for AJAX
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Status updated to {$statusLabel}.",
                    'status' => $newStatus,
                    'statusLabel' => $statusLabel
                ], 200);
            }

            // Redirect with success message for non-AJAX requests
            return redirect()->route('member.index')->with('success', "Status updated to {$statusLabel}.");
        } catch (\Exception $e) {
            $errorMessage = 'Error toggling status: ' . $e->getMessage();

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 500);
            }

            return redirect()->route('member.index')->with('error', $errorMessage);
        }
    }

    public function destroy($id)
    {
        try {
            $memberId = decrypt($id);
            $member = EmployeeMaster::findOrFail($memberId);

            // Check if member is active
            if ($member->status == 1) {
                $message = 'Cannot delete active record. Please set status to inactive first.';
                if (request()->ajax()) {
                    return response()->json(['success' => false, 'message' => $message], 422);
                }
                return redirect()->route('member.index')->with('error', $message);
            }

            // Delete related UserCredential and EmployeeRoleMapping
            $userCredential = UserCredential::where('user_id', $memberId)->first();
            if ($userCredential) {
                // Delete role mappings first
                EmployeeRoleMapping::where('user_credentials_pk', $userCredential->pk)->delete();
                // Delete user credential
                $userCredential->delete();
            }

            // Delete the member
            $member->delete();

            MemberDataTable::bumpListingCacheEpoch();

            $message = 'Member deleted successfully.';
            if (request()->ajax()) {
                return response()->json(['success' => true, 'message' => $message]);
            }
            return redirect()->route('member.index')->with('success', $message);
        } catch (\Exception $e) {
            $message = 'Error deleting member: ' . $e->getMessage();
            if (request()->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 500);
            }
            return redirect()->route('member.index')->with('error', $message);
        }
    }
}
