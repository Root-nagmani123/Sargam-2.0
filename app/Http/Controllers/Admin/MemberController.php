<?php
namespace App\Http\Controllers\Admin;

use App\DataTables\MemberDataTable;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Http\Requests\Admin\Member\{
    StoreMemberStep1Request,
    StoreMemberStep2Request,
    StoreMemberStep3Request,
    StoreMemberStep4Request,
    StoreMemberStep5Request,
    StoreMemberStep6Request,
};
use App\Models\{EmployeeMaster, EmployeeRoleMapping, UserCredential, City, PayrollSalaryMaster, User, UserRoleMaster};
use App\Exports\MemberExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\AppellationMaster;
use Spatie\Permission\Models\Role;
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
     * Step 6 ("Employee Grade Pay") does NOT belong on employee_master — it's saved separately
     * to payroll_salary_master by saveStep6PayrollData().
     *
     * If the admin left the entire step untouched, this returns [] and
     * saveStep6PayrollData() skips writing anything — this table is also read by the Estate
     * module (house eligibility joins on salary_grade_pk), so a member who never uses this
     * step should not get an empty payroll row created for them.
     *
     * If at least one field was filled in, all five keys are returned together, with any
     * field the admin explicitly left blank mapped to null rather than dropped — otherwise a
     * value the admin deliberately cleared would silently keep its old value on update()
     * instead of being cleared (see PR #319 review, F-010).
     */
    private function mapStep6Data(Request $request): array
    {
        $raw = [
            'salary_grade_pk' => $request->gradepay,
            'employee_category_master_pk' => $request->employeecategory,
            'basic_pay' => $request->basicpay,
            'bank_name' => $request->bankname,
            'account_no' => $request->accountno,
        ];

        $hasAnyValue = collect($raw)->contains(fn ($value) => $value !== null && $value !== '');

        if (! $hasAnyValue) {
            return [];
        }

        return array_map(fn ($value) => $value === '' ? null : $value, $raw);
    }

    /**
     * Upserts payroll_salary_master for the given employee, keyed on employee_master_pk.
     * Only touches the five columns this wizard owns (see mapStep6Data()) — this table is
     * also read by the Estate module (house eligibility joins on salary_grade_pk), so an
     * existing row's other columns (net_salary, tds, etc., not exposed on this wizard) must
     * survive untouched.
     */
    private function saveStep6PayrollData(int $employeeMasterPk, Request $request): void
    {
        // PR #319 review round 2 (F-011). update() deliberately permits a non-admin to
        // write their OWN employee record, so the self-service profile form keeps
        // working (see authorizeMemberWrite()). That lane was gated against the role
        // mappings and NOT against payroll, so an ordinary employee could POST
        // gradepay/basicpay for their own emp_id and have it stored: an executed probe
        // wrote basic_pay = 999999.99 and salary_grade_pk = the highest grade in
        // salary_grade_master, on the table the Estate module joins for house
        // eligibility. Whether a field appears on the self-service form is irrelevant —
        // update() accepts it from the request body regardless of what the UI draws, so
        // the check belongs here rather than in the view.
        //
        // Gated on the same predicate as the other administrative member actions. If
        // bank_name/account_no are later judged to be legitimately employee-editable,
        // split mapStep6Data() and gate only the payroll-determining subset
        // (salary_grade_pk, basic_pay, employee_category_master_pk) — that is a product
        // decision and is deliberately not made here.
        if (! $this->actingUserCanManageMembers()) {
            return;
        }

        // PR #319 review round 2 (F-003). The code below depends on schema this PR also
        // ships: payroll_salary_master.basic_pay and .employee_category_master_pk, plus
        // AUTO_INCREMENT on the primary key. Without them the insert fails with
        // SQLSTATE 1364, and because store()/update() wrap every write in one
        // transaction that failure is TOTAL — the member is not created at all,
        // including Steps 1-5, which have nothing to do with the new schema. Degrading
        // Step 6 is strictly better than losing the whole save during a window where
        // code is deployed ahead of `php artisan migrate`.
        //
        // The correct deploy order is still migrate-then-deploy; this guard bounds the
        // damage if that order is not held, it does not replace it.
        if (! $this->step6SchemaIsReady()) {
            Log::warning('Member wizard: Step 6 payroll data skipped — the step-6 schema is not present on this environment. Run php artisan migrate.', [
                'employee_master_pk' => $employeeMasterPk,
            ]);

            return;
        }

        $data = $this->mapStep6Data($request);

        if (empty($data)) {
            return;
        }

        // updateOrCreate rather than first()-then-update-or-create: the two-statement
        // form is a read-then-write with no lock, so two concurrent saves for the same
        // employee could both read "no row" and both insert (independent review of
        // PR #319, F-006). The unique index added by 2026_09_22_000002 is what actually
        // enforces one payroll row per employee; this is the matching code shape.
        PayrollSalaryMaster::updateOrCreate(
            ['employee_master_pk' => $employeeMasterPk],
            $data
        );
    }

    /**
     * Whether the schema Step 6 writes and renders is present on this environment.
     *
     * PR #319 review round 2 (F-003). Resolved once per request and memoised: this is a
     * request-path call, and AUTO-07 exists precisely to stop information_schema reads
     * happening per row. Three objects are checked because three separate migrations
     * supply them, and a half-applied deploy can leave any subset present.
     */
    private function step6SchemaIsReady(): bool
    {
        static $ready = null;

        if ($ready !== null) {
            return $ready;
        }

        return $ready = Schema::hasTable('employee_category_master')
            && Schema::hasColumn('payroll_salary_master', 'basic_pay')
            && Schema::hasColumn('payroll_salary_master', 'employee_category_master_pk');
    }

    /**
     * PR #319 review round 2 (F-018): granting real Spatie roles from this
     * screen must be restricted to actors already privileged enough to grant
     * roles elsewhere. Gated using the `hasRole('Super Admin')` convention
     * already used throughout this codebase for admin-only checks, since
     * there is no `permission:`/`role:` middleware or policy layer in use
     * anywhere in this app to hook into instead. Every member/* and
     * admin/setup/member/* route carries only the generic `auth` middleware,
     * so without this check any authenticated member could grant themselves
     * roles like "Super Admin" simply by ticking them on their own edit form.
     *
     * PR #319 review round 4 (F-028): UserController::assignRoleSave() — the
     * dedicated Role & Permission > Users screen this method's docblock below
     * points to as "the same mechanism" — had no authorization check of its
     * own at all until this same round, meaning it was not actually a valid
     * precedent for "already gated" when this method was first written. It
     * now carries the equivalent `hasRole('Super Admin')` gate directly.
     */
    /**
     * Whether the acting user may administer OTHER members through this wizard.
     *
     * Every member/* and admin/setup/member/* route carries only the generic `auth`
     * middleware — there is no permission:/role: middleware or policy layer in this
     * app to hook into — so without a check in the controller any authenticated
     * account could post an arbitrary emp_id and rewrite another employee's record.
     * That was demonstrated end to end: a zero-role account posted member/update for
     * a different employee and got HTTP 200 back with the victim's name changed.
     *
     * Gated on the same hasRole('Super Admin') convention the rest of this codebase
     * uses for admin-only actions. Checked against the live data before choosing it:
     * every member/employee permission row (employee, employee_master, member_index,
     * employee_type, employee_group) is held by Super Admin and by no other role, so
     * this matches the access model the permission table already describes rather
     * than narrowing it.
     */
    private function actingUserCanManageMembers(): bool
    {
        return hasRole('Super Admin');
    }

    /**
     * Authorise a write against one member record.
     *
     * Super Admin may write any member. Any other authenticated user may write only
     * their OWN employee record — the header's "Edit Profile" link posts to this same
     * member.update endpoint with emp_id carried in a hidden input, so the id is
     * attacker-controlled and must be verified server-side rather than trusted.
     *
     * user_credentials.user_id holds employee_master.pk (store() writes
     * 'user_id' => $employee->pk), which is why that is the column compared here.
     */
    private function authorizeMemberWrite($employeeMasterPk): void
    {
        if ($this->actingUserCanManageMembers()) {
            return;
        }

        $ownEmployeePk = Auth::user()->user_id ?? null;

        abort_unless(
            $ownEmployeePk !== null && (int) $ownEmployeePk === (int) $employeeMasterPk,
            403
        );
    }

    /**
     * Whether this credential row is the only one for its employee.
     *
     * Returns false when the employee owns two or more user_credentials rows, because
     * ->first() then picked one of them arbitrarily and there is no way to tell from
     * here which login the administrator intended. Reconciling the duplicates is a DBA
     * task; until it is done, this is the condition that keeps RBAC off the wrong row.
     */
    private function memberCredentialIsUnambiguous(int $userCredentialPk): bool
    {
        $employeeMasterPk = UserCredential::where('pk', $userCredentialPk)->value('user_id');

        // No employee link means no user_id lookup happened, so nothing was resolved
        // ambiguously — the caller was handed this exact credential row. Only a row
        // reached THROUGH user_id can be the wrong one of several.
        if ($employeeMasterPk === null) {
            return true;
        }

        return UserCredential::where('user_id', $employeeMasterPk)->count() === 1;
    }

    private function actingUserCanManageRbacRoles(): bool
    {
        // hasRole('Super Admin') already checks both 'Super Admin' and 'SuperAdmin'
        // internally (see app/helpers.php). 'Admin' and 'Super-Admin' were dropped
        // (PR #319 review, F-029): neither is aliased by hasRole(), so both were
        // permanently false against the real `roles` table, but would have silently
        // widened this gate's authority the moment either name was ever created for
        // an unrelated purpose — several existing roles already contain "Admin".
        return hasRole('Super Admin');
    }

    /**
     * Step 3 ("Role Assignment")'s checkboxes are drawn from user_role_master
     * (UserRoleMaster::getUserRoleList()), which is a mix of real Spatie roles
     * (kept in sync with the `roles` table — see
     * 2026_09_10_000001_sync_user_role_master_with_roles) and plain HR tags
     * that have no corresponding permission role at all (e.g. "Internal
     * Faculty", "Staff"). Previously, checking a role here only wrote to
     * employee_role_mapping and never touched Spatie RBAC, so it looked like
     * it granted access without actually doing so (PR #319 review, F-007).
     *
     * This grants/revokes real Spatie roles for exactly the subset of
     * user_role_master options that correspond to a real `roles` row — no
     * more, no less — using $user->syncRoles(), the same mechanism
     * UserController::assignRoleSave() already uses for the dedicated
     * Role & Permission > Users screen.
     *
     * PR #319 review round 2 (F-019): the sync migration now mirrors almost
     * every real role into user_role_master, so "preserve whatever this
     * screen doesn't offer" (the v2 approach) preserves almost nothing —
     * saving a member with only "Doctor" ticked silently stripped every other
     * Spatie role the member held, including ones granted via the Users
     * screen. Instead of deriving "preserve" from the *offered* set, this
     * derives "revoke" from what THIS WIZARD itself previously granted for
     * this member — read from employee_role_mapping, which only ever holds
     * rows this screen wrote — via $previouslySelectedUserRoleMasterPks. A
     * role is only ever removed here if the wizard granted it last time and
     * it's unchecked now; a role assigned any other way is never touched,
     * regardless of how many roles this screen happens to offer a checkbox
     * for.
     */
    private function syncSpatieRolesFromWizardSelection(
        int $userCredentialPk,
        array $selectedUserRoleMasterPks,
        array $previouslySelectedUserRoleMasterPks = []
    ): ?string {
        if (! $this->actingUserCanManageRbacRoles()) {
            return null;
        }

        $spatieRoleNames = Role::pluck('name')->all();

        if (empty($spatieRoleNames)) {
            return null;
        }

        $newNames = UserRoleMaster::whereIn('pk', $selectedUserRoleMasterPks)
            ->pluck('user_role_display_name')
            ->all();
        $oldNames = empty($previouslySelectedUserRoleMasterPks)
            ? []
            : UserRoleMaster::whereIn('pk', $previouslySelectedUserRoleMasterPks)
                ->pluck('user_role_display_name')
                ->all();

        $newSpatieRoles = $this->resolveSpatieRoleNames($newNames, $spatieRoleNames);
        $oldSpatieRoles = $this->resolveSpatieRoleNames($oldNames, $spatieRoleNames);

        if (empty($newSpatieRoles) && empty($oldSpatieRoles)) {
            return null;
        }

        // user_credentials.user_id is NOT unique — it carries only the non-unique index
        // idx_user_id, and on the live data 611 user_id values are held by more than one
        // row, 207 of them belonging to a real employee_master record, spanning 1223
        // credential rows of which 603 already hold at least one Spatie role. The caller
        // resolves the member's login with ->first(), so for those members it hands over
        // an arbitrary one of several. Granting or revoking real permissions on a login
        // the administrator did not mean to touch is worse than not acting: refuse, and
        // leave a trace naming the member so it can be reconciled.
        //
        // PR #319 review round 2 (F-002 residual): refusing silently was still wrong —
        // the administrator ticked a role, saw "Member successfully updated", and nothing
        // happened, for a measured 207 employees. The refusal is now returned to the
        // caller so the response can say so out loud.
        if (! $this->memberCredentialIsUnambiguous($userCredentialPk)) {
            Log::warning('Member wizard: skipped Spatie role sync — employee has more than one user_credentials row.', [
                'user_credentials_pk' => $userCredentialPk,
            ]);

            return 'Role permissions were NOT changed: this employee has more than one login account, '
                . 'so the wizard cannot tell which one you meant. The rest of the member record was saved. '
                . 'Ask the DBA to reconcile the duplicate user_credentials rows for this employee.';
        }

        $user = User::find($userCredentialPk);

        if (! $user) {
            return null;
        }

        $currentRoleNames = $user->getRoleNames()->all();

        // Only unchecking a role this same screen previously granted removes it.
        $toRemove = array_diff($oldSpatieRoles, $newSpatieRoles);
        $toAdd = array_diff($newSpatieRoles, $currentRoleNames);

        $finalRoleNames = array_values(array_unique(array_merge(
            array_diff($currentRoleNames, $toRemove),
            $toAdd
        )));

        $user->syncRoles($finalRoleNames);

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        return null;
    }

    /**
     * Map user_role_master display names onto the real `roles` names they denote.
     *
     * PR #319 review round 2 (F-005). This used to be array_intersect(), an exact string
     * comparison — while 2026_09_10_000001_sync_user_role_master_with_roles decides
     * whether a role is "already present" on a NORMALISED key (lowercased, trimmed,
     * /[\s_-]+/ collapsed to one space). The two disagreed, and not at the margins:
     * `roles` holds "Super Admin" while user_role_master holds "Super-Admin", so the
     * migration saw them as the same row and inserted nothing, the wizard offered a
     * checkbox labelled "Super-Admin", and the exact-match intersect returned []. Ticking
     * it wrote an employee_role_mapping row, reported success, and granted no permission —
     * indistinguishable from a working grant, on the highest-privilege role in the system.
     * Measured against live data after the migration had run: of 34 offered options,
     * exactly two were dead this way, "Super-Admin" and "Mess-Admin".
     *
     * Normalising here uses the same rule the migration uses, so both sides of the link
     * now answer the same question. The RETURNED value is always the canonical `roles`
     * name, never the user_role_master spelling, because that is what syncRoles() needs.
     */
    private function resolveSpatieRoleNames(array $displayNames, array $spatieRoleNames): array
    {
        $byNormalisedName = [];

        foreach ($spatieRoleNames as $spatieRoleName) {
            $byNormalisedName[$this->normalizeRoleName($spatieRoleName)] = $spatieRoleName;
        }

        $resolved = [];

        foreach ($displayNames as $displayName) {
            $key = $this->normalizeRoleName($displayName);

            if (isset($byNormalisedName[$key])) {
                $resolved[] = $byNormalisedName[$key];
            }
        }

        return array_values(array_unique($resolved));
    }

    /**
     * Identical to normalize() in 2026_09_10_000001_sync_user_role_master_with_roles.
     * Kept deliberately in step with it: if one changes and the other does not, the
     * silent-no-op class of bug this pair exists to prevent comes straight back.
     */
    private function normalizeRoleName(string $name): string
    {
        return preg_replace('/[\s_-]+/', ' ', mb_strtolower(trim($name)));
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
     * Merge rules()/messages() from all 6 step requests into one combined validator,
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
            StoreMemberStep6Request::class,
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
        // Creating a member is never a self-service action — there is no "own record" to
        // scope it to — so this is admin-only, unlike update().
        abort_unless($this->actingUserCanManageMembers(), 403);

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

        // employee_master, payroll_salary_master, user_credentials and the role mappings
        // are four tables written together for one member — wrapped in a transaction so a
        // failure partway through (e.g. Step 6 hitting a bad value) rolls back the whole
        // thing instead of leaving a member with no login credential and no roles.
        try {
            DB::transaction(function () use ($request, $profile_picture, $additional_doc_upload) {
                $employee = EmployeeMaster::create(array_merge(
                    $this->mapStep1Data($request),
                    $this->mapStep2Data($request),
                    $this->mapStep4Data($request),
                    $this->mapStep5Data($request, $profile_picture, $additional_doc_upload)
                ));

                $this->saveStep6PayrollData($employee->pk, $request);

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

                    $this->syncSpatieRolesFromWizardSelection($userCredential->pk, $roles);
                }
            });
        } catch (\Throwable $e) {
            // The upload happens before the transaction opens (the file has to exist on
            // disk before its path can be written to employee_master), so a rollback here
            // doesn't clean it up on its own — do it here instead (PR #319 review, F-024).
            $this->deleteUploadedMemberFiles($profile_picture, $additional_doc_upload);
            throw $e;
        }

        MemberDataTable::bumpListingCacheEpoch();

        return response()->json(['message' => 'Member successfully created']);
    }

    /**
     * Best-effort cleanup for files already written to the `public` disk before a
     * store()/update() transaction that ended up failing (PR #319 review, F-024).
     */
    private function deleteUploadedMemberFiles(?string $profilePicture, ?string $additionalDocument): void
    {
        foreach ([$profilePicture, $additionalDocument] as $path) {
            if ($path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($path);
            }
        }
    }

    public function update(Request $request) {

        // emp_id arrives in the request body (a hidden input on both the wizard and the
        // self-service profile form) and was previously neither validated nor authorised,
        // so any authenticated account could rewrite any employee's record. Validated
        // first so an unknown id is a 422 rather than a fatal on find()->update(), then
        // authorised so a non-admin can only write their own record.
        $validator = Validator::make(
            $request->all(),
            ['emp_id' => ['required', 'integer', 'exists:employee_master,pk']],
            ['emp_id.required' => 'The member to update was not identified.',
             'emp_id.exists'   => 'The member to update does not exist.']
        );

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $this->authorizeMemberWrite($request->emp_id);

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

        // Set inside the transaction, read after it. Bound by reference so the refusal
        // reason survives back out to the response (PR #319 review round 2, F-002).
        $rbacWarning = null;

        // Same reasoning as store(): one transaction across employee_master,
        // payroll_salary_master, user_credentials and the role mappings.
        try {
            DB::transaction(function () use ($request, $profile_picture, $additional_doc_upload, &$rbacWarning) {
            EmployeeMaster::find($request->emp_id)->update(array_merge(
                $this->mapStep1Data($request),
                $this->mapStep2Data($request),
                $this->mapStep4Data($request),
                $this->mapStep5Data($request, $profile_picture, $additional_doc_upload)
            ));

            $this->saveStep6PayrollData((int) $request->emp_id, $request);

            // PR #319 review round 2 (F-002 residual). This was
            // UserCredential::updateOrCreate(['user_id' => $emp_id], ...) followed by a
            // separate where('user_id')->first() lookup. Both matched on user_id, which
            // carries only the NON-UNIQUE index idx_user_id — 611 duplicate groups on the
            // live data, 207 of them a real employee. So the profile fields could be
            // written to one credential row while the role work was done against another,
            // and updateOrCreate picked its row by a different ordering than first() did.
            //
            // Resolving the row ONCE, deterministically, and then writing through that
            // instance removes the divergence: whatever row is chosen, every write in this
            // request goes to the same one. orderBy('pk') makes the choice stable across
            // saves rather than left to the storage engine. It still does not make the
            // choice CORRECT for a member with duplicates — only a unique constraint on
            // user_credentials.user_id can do that, which needs the DBA to reconcile the
            // duplicates first — which is why syncSpatieRolesFromWizardSelection() refuses
            // to touch RBAC for an ambiguous member and now says so in the response.
            $credentialAttributes = [
                'first_name'    => $request->first_name,
                'last_name'     => $request->last_name,
                'email_id'      => $request->personalemail,
                'mobile_no'     => $request->mnumber,
                'user_name'     => $request->userid,
                'user_category' => 'E',
            ];

            $userCredential = UserCredential::where('user_id', $request->emp_id)
                ->orderBy('pk')
                ->first();

            if ($userCredential) {
                $userCredential->update($credentialAttributes);
            } else {
                $userCredential = UserCredential::create(
                    $credentialAttributes + ['user_id' => $request->emp_id]
                );
            }

            // Role mappings are only rewritten by an actor entitled to manage roles.
            // employee_role_mapping is what the edit form pre-checks Step 3 from, and what
            // syncSpatieRolesFromWizardSelection() reads back as "this wizard granted it
            // last time" — so an unprivileged write here is not a cosmetic tag, it is
            // state a later privileged save converts into a real Spatie grant. Demonstrated
            // end to end: an attacker-seeded mapping row became a live Spatie role the next
            // time a Super Admin saved that member for an unrelated reason. Skipping the
            // block leaves existing mappings untouched rather than clearing them.
            if ($userCredential && $this->actingUserCanManageRbacRoles()) {
                $roles = is_array($request->userrole) ? $request->userrole : [$request->userrole];

                // Only replace mappings for roles that were actually offered as a
                // checkbox (getUserRoleList() — active roles only). A member holding a
                // role that's since been deactivated has no checkbox for it and can't
                // post it back, so deleting *every* mapping here would silently strip
                // that role as a side effect of saving unrelated fields (PR #319
                // review, F-008). Leaving it untouched preserves it until someone
                // deliberately reassigns the member's roles while it's active again.
                $offeredRoleIds = \App\Models\UserRoleMaster::getUserRoleList()->keys()->all();

                // Captured before the delete below so syncSpatieRolesFromWizardSelection()
                // can tell "this wizard granted it last time" apart from "granted some
                // other way" (PR #319 review, F-019) — employee_role_mapping is the only
                // durable record of what this screen itself previously selected.
                $previouslySelectedRoleIds = EmployeeRoleMapping::where('user_credentials_pk', $userCredential->pk)
                    ->whereIn('user_role_master_pk', $offeredRoleIds)
                    ->pluck('user_role_master_pk')
                    ->all();

                EmployeeRoleMapping::where('user_credentials_pk', $userCredential->pk)
                    ->whereIn('user_role_master_pk', $offeredRoleIds)
                    ->delete();

                foreach ($roles as $role) {
                    EmployeeRoleMapping::create([
                        'user_credentials_pk' => $userCredential->pk,
                        'user_role_master_pk' => $role,
                    ]);
                }

                $rbacWarning = $this->syncSpatieRolesFromWizardSelection($userCredential->pk, $roles, $previouslySelectedRoleIds);
            }
            });
        } catch (\Throwable $e) {
            $this->deleteUploadedMemberFiles($profile_picture, $additional_doc_upload);
            throw $e;
        }

        MemberDataTable::bumpListingCacheEpoch();

        // PR #319 review round 2 (F-002 residual): when the role sync refuses because the
        // employee owns more than one login, say so. Reporting plain success while
        // silently doing nothing is what made this defect invisible to administrators.
        if ($rbacWarning !== null) {
            return response()->json([
                'message' => 'Member successfully updated',
                'warning' => $rbacWarning,
            ]);
        }

        return response()->json(['message' => 'Member successfully updated']);
    }

    public function loadStep($step)
    {
        $appellationMasterList = AppellationMaster::where('active_inactive', 1)
            ->pluck('appettation_name', 'pk')
            ->toArray();
        // Only step 6 ("Employee Grade Pay") actually renders these options — querying
        // them for every other step was pure overhead (PR #319 review, F-001 residual/F-023).
        [$gradePayOptions, $employeeCategoryOptions] = ((int) $step === 6)
            ? $this->step6DropdownOptions()
            : [[], []];
        return view("admin.member.steps.step{$step}", compact('appellationMasterList', 'gradePayOptions', 'employeeCategoryOptions'));
    }

    /**
     * Dropdown sources for Step 6 ("Employee Grade Pay"): salary_grade_master and
     * employee_category_master. Neither table has an active/inactive flag, so all rows are listed.
     */
    private function step6DropdownOptions(): array
    {
        // PR #319 review round 2 (F-003). employee_category_master is created by a
        // migration this same PR ships. On an environment where the code is deployed
        // ahead of `php artisan migrate`, the pluck() below raises SQLSTATE 42S02 and
        // Step 6 returns a 500 rather than rendering. Returning empty option lists lets
        // the step draw with empty dropdowns, which is a visible, self-explanatory
        // degradation instead of a broken screen — and keeps the failure contained to
        // Step 6 rather than to the wizard.
        if (! $this->step6SchemaIsReady()) {
            Log::warning('Member wizard: Step 6 dropdowns are empty — the step-6 schema is not present on this environment. Run php artisan migrate.');

            return [[], []];
        }

        $gradePayOptions = \App\Models\SalaryGrade::all()->pluck('display_label_text', 'pk')->toArray();
        $employeeCategoryOptions = \App\Models\EmployeeCategoryMaster::pluck('category', 'pk')->toArray();

        return [$gradePayOptions, $employeeCategoryOptions];
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
        [$gradePayOptions, $employeeCategoryOptions] = ((int) $step === 6)
            ? $this->step6DropdownOptions()
            : [[], []];
        $payrollSalary = PayrollSalaryMaster::where('employee_master_pk', $id)->first();
        return view("admin.member.edit_steps.step{$step}", compact('member', 'appellationMasterList', 'gradePayOptions', 'employeeCategoryOptions', 'payrollSalary'));
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
        // Activating or deactivating a member is an administrative action on someone
        // else's record, so it is admin-only rather than self-scoped.
        abort_unless($this->actingUserCanManageMembers(), 403);

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
        // Deleting a member is administrative, never self-service.
        abort_unless($this->actingUserCanManageMembers(), 403);

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
