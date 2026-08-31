<?php
namespace App\Http\Controllers\Admin;

use App\DataTables\MemberDataTable;
use App\Http\Controllers\Concerns\ExportsBrandedGrid;
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
use App\Models\{EmployeeMaster, EmployeeRoleMapping, UserCredential, City,
    EmployeeTypeMaster, EmployeeGroupMaster, DepartmentMaster};
use App\Exports\MemberExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\AppellationMaster;
class MemberController extends Controller
{
    use ExportsBrandedGrid;

    public function index(MemberDataTable $dataTable)
    {
        return $dataTable->render('admin.member.index', $this->listingFilterOptions());
    }

    /**
     * Options for the listing's Type / Group / Department dropdowns.
     *
     * Only active rows, and only ones actually used by an employee — the three
     * masters carry retired entries, and a dropdown that offers 71 departments
     * where 40 can never match is a filter that mostly returns nothing.
     *
     * @return array{employeeTypes: \Illuminate\Support\Collection, employeeGroups: \Illuminate\Support\Collection, departments: \Illuminate\Support\Collection}
     */
    private function listingFilterOptions(): array
    {
        $inUse = fn (string $column) => EmployeeMaster::query()
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->pluck($column);

        return [
            'employeeTypes' => EmployeeTypeMaster::whereIn('pk', $inUse('emp_type'))
                ->where('active_inactive', 1)
                ->orderBy('category_type_name')
                ->pluck('category_type_name', 'pk'),
            'employeeGroups' => EmployeeGroupMaster::whereIn('pk', $inUse('emp_group_pk'))
                ->where('active_inactive', 1)
                ->orderBy('emp_group_name')
                ->pluck('emp_group_name', 'pk'),
            'departments' => DepartmentMaster::whereIn('pk', $inUse('department_master_pk'))
                ->where('active_inactive', 1)
                ->orderBy('department_name')
                ->pluck('department_name', 'pk'),
        ];
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

        return view('admin.member.show', [
            'member' => $member,
            'sections' => $this->memberProfileSections($member),
        ]);
    }

    /**
     * One member's profile, grouped into the sections both the View screen and
     * the print sheet render.
     *
     * Built here rather than in either view so the printed copy cannot quietly
     * drift from the screen it was printed off — the same reason the listing's
     * four export formats share one column list.
     *
     * A '__wide' marker means "every field after this one spans the full width".
     *
     * @return array<string, array<string, mixed>>
     */
    private function memberProfileSections(EmployeeMaster $member): array
    {
        return [
            'Personal Information' => [
                'Title' => optional($member->appellationMaster)->appettation_name,
                'First Name' => $member->first_name,
                'Middle Name' => $member->middle_name,
                'Last Name' => $member->last_name,
                "Father's / Husband's Name" => $member->father_name,
                'Date of Birth' => $member->dob,
                'Gender' => EmployeeMaster::gender[$member->gender] ?? null,
                'Marital Status' => EmployeeMaster::maritalStatus[$member->marital_status] ?? null,
                'Height (Without Shoes)' => filled($member->height) ? $member->height . ' cm' : null,
            ],
            'Employment Details' => [
                'Employee ID' => $member->emp_id,
                'User ID' => optional($member->userCredential)->user_name,
                'Employee Type' => optional($member->employeeType)->category_type_name,
                'Employee Group' => optional($member->employeeGroup)->emp_group_name,
                'Designation' => optional($member->designation)->designation_name,
                'Department' => optional($member->department)->department_name,
            ],
            'Contact Information' => [
                'Personal Email' => $member->email,
                'Official Email' => $member->officalemail,
                'Mobile Number' => $member->mobile,
                'Emergency Contact Number' => $member->emergency_contact_no,
                'Landline Number' => $member->landline_contact_no,
                'Residence Number' => $member->residence_no,
            ],
            'Address' => [
                'Country' => optional(\App\Models\Country::find($member->country_master_pk))->country_name,
                'State' => optional(\App\Models\State::find($member->state_master_pk))->state_name,
                'District' => optional(\App\Models\District::find($member->state_district_mapping_pk))->district_name,
                'City' => optional(City::find($member->city))->city_name,
                'Postal Code' => $member->zipcode,
                '__wide' => true,
                'Current Address' => $member->current_address,
                'Permanent Address' => $member->permanent_address,
                'Home Address Data' => $member->home_town_details,
            ],
        ];
    }

    /**
     * Branded print sheet for ONE member — the row-level Print action.
     *
     * The listing's Print gives the whole filtered table; this gives the single
     * profile, which is what you want when handing someone their own record. It
     * is a server-rendered page like every other export here, not window.print()
     * over the View screen, so it carries the letterhead and none of the app
     * chrome (docs/new-design-index-page.md §1).
     */
    public function printMember($id)
    {
        $member = EmployeeMaster::with('appellationMaster')->findOrFail(decrypt($id));

        return view('admin.member.print', [
            'member' => $member,
            'sections' => $this->memberProfileSections($member),
            'assignedRoles' => $member->assignedRoles(),
            'exportDate' => now()->format('d-m-Y h:i A'),
        ]);
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
    /**
     * The listing's export columns - deliberately the same nine the grid shows,
     * in the same order, so a downloaded report can be reconciled against the
     * screen it came from (docs/new-design-index-page.md section 1).
     *
     * One definition feeds CSV, Excel, PDF and Print; none of them may build
     * their own column list.
     *
     * @return array<string, array{heading:string, class:string, value:callable}>
     */
    private function exportColumnDefs(): array
    {
        return [
            'sno' => [
                'heading' => 'S. No.',
                'class' => 'col-sno',
                'value' => fn ($row, int $index) => $index + 1,
            ],
            'employee_name' => [
                'heading' => 'Employee Name',
                'class' => 'col-name',
                'value' => function ($row) {
                    $appellation = $row->appellation ? ($row->appellationMaster->appettation_name ?? null) : null;

                    $parts = array_filter(
                        array_map(
                            fn ($part) => trim((string) $part),
                            [$appellation, $row->first_name, $row->middle_name, $row->last_name]
                        ),
                        fn ($part) => $part !== ''
                    );

                    return implode(' ', $parts);
                },
            ],
            'employee_id' => [
                'heading' => 'Employee ID',
                'class' => 'col-empid',
                'value' => fn ($row) => (string) $row->emp_id,
            ],
            'employee_type' => [
                'heading' => 'Employee Type',
                'class' => 'col-type',
                'value' => fn ($row) => (string) optional($row->employeeType)->category_type_name,
            ],
            'employee_group' => [
                'heading' => 'Employee Group',
                'class' => 'col-group',
                'value' => fn ($row) => (string) optional($row->employeeGroup)->emp_group_name,
            ],
            'department' => [
                'heading' => 'Department',
                'class' => 'col-dept',
                'value' => fn ($row) => (string) optional($row->department)->department_name,
            ],
            'mobile_no' => [
                'heading' => 'Mobile No',
                'class' => 'col-mobile',
                'value' => fn ($row) => (string) $row->mobile,
            ],
            'email' => [
                'heading' => 'Email',
                'class' => 'col-email',
                'value' => fn ($row) => (string) $row->email,
            ],
            'status' => [
                'heading' => 'Status',
                'class' => 'col-status',
                'value' => fn ($row) => ((int) $row->status === 1) ? 'Active' : 'Inactive',
            ],
        ];
    }

    /**
     * Member listing -> CSV / Excel / PDF / Print, all through
     * {@see \App\Http\Controllers\Concerns\ExportsBrandedGrid}.
     *
     * The four run off one query and one column list, and honour whatever the
     * grid is showing: the search box and the Columns modal, plus a
     * ?status_filter= when one is deep-linked. Print is a server-rendered
     * branded view, not window.print() over the screen.
     */
    public function export(Request $request, string $format = 'csv')
    {
        $format = strtolower($format);
        abort_unless(in_array($format, ['csv', 'excel', 'pdf', 'print'], true), 404);

        $filters = MemberDataTable::resolveFilters();
        $search = trim((string) $request->query('q', ''));

        // Same relations MemberDataTable::query() loads - the Type / Group /
        // Department columns read them, and this query is not paginated, so
        // lazy-loading them would be three round trips per exported row.
        $query = EmployeeMaster::query()->with([
            'appellationMaster',
            'employeeType',
            'employeeGroup',
            'department',
        ]);
        MemberDataTable::applyListingFilters($query, $filters, $search);
        $rows = $query->orderBy('pk', 'desc')->get();

        // One filter description, rendered by all four formats. The dropdowns are
        // named on the sheet, not just applied to it — a report that silently
        // omits 1,700 rows is indistinguishable from a broken one.
        $filterParts = array_filter([
            $filters['status'] !== '' ? 'Status: ' . ucfirst($filters['status']) : null,
            $filters['type'] ? 'Type: ' . (EmployeeTypeMaster::find($filters['type'])->category_type_name ?? $filters['type']) : null,
            $filters['group'] ? 'Group: ' . (EmployeeGroupMaster::find($filters['group'])->emp_group_name ?? $filters['group']) : null,
            $filters['department'] ? 'Department: ' . (DepartmentMaster::find($filters['department'])->department_name ?? $filters['department']) : null,
            $search !== '' ? 'Search: ' . $search : null,
        ]);

        return $this->brandedGridResponse(
            $format,
            'Members',
            'Members',
            $rows,
            $this->resolveExportColumns($this->exportColumnDefs(), $request),
            $filterParts === [] ? null : implode('  |  ', $filterParts),
            [
                'emptyText' => 'No members to export',
                'centeredKeys' => ['sno', 'status'],
                'textKeys' => ['employee_id', 'mobile_no'],
                // This grid is four figures deep, and DomPDF needs ~276 MB to lay
                // out the 1,000-row cap — a hard OOM fatal (blank page, no error)
                // wherever memory_limit is 256M. mPDF does the same page in
                // ~144 MB and half the time. See ExportsBrandedGrid::$pdfRowCap.
                'pdfEngine' => 'mpdf',
                // Nine columns on A4 portrait: Name and Email give up the room
                // Type / Group / Department need. Percentages, so the sheet still
                // fills the page when the Columns modal drops some of them.
                'columnStyles' => '
        .col-sno    { width: 5%;  text-align: center; }
        .col-name   { width: 17%; }
        .col-empid  { width: 10%; }
        .col-type   { width: 11%; }
        .col-group  { width: 10%; }
        .col-dept   { width: 13%; }
        .col-mobile { width: 10%; }
        .col-email  { width: 16%; }
        .col-status { width: 8%;  text-align: center; }',
            ]
        );
    }

    /**
     * The legacy full-profile dump (every employee_master column). Kept alongside
     * the grid exports above because it is a different report, not a format of
     * the same one — dropping it would lose data the grid exports don't carry.
     */
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
