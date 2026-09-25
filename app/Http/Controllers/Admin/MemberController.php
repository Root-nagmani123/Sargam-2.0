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
/**
 * NOTE ON THE "PR #319 review, F-0xx" CITATIONS IN THIS FILE.
 *
 * Two separate reviews of PR #319 exist, and their finding numbers COLLIDE. A comment
 * here reading "PR #319 review, F-006" therefore points at two different defects
 * depending on which report the reader is holding. Raised by the independent review as
 * F-040; recorded here rather than left for the next reader to trip over.
 *
 *   - The long-running series (rounds 1-9, F-001..F-040) is the one kept in the review
 *     workspace. Its F-001 is the missing employee_category_master table, its F-006 the
 *     role-sync near-duplicates. Citations naming a round ("round 4 (F-028)") and all of
 *     F-012..F-040 belong to it.
 *   - A second, shorter review run during development used its own F-001..F-011 plus
 *     R-001..R-004. Its F-001 is the confused-deputy RBAC escalation, its F-006 the
 *     payroll read-then-write. Every "R-00x" citation is unambiguously from this one.
 *
 * Where the two cannot be told apart from the ID alone, the surrounding comment states
 * the defect in words — read that, not the number. Neither report is authority for what
 * this code does: the comments below are descriptions to verify, not evidence
 * (agent-operating-rules.md 9.3 rank 1).
 */
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
    private function saveStep6PayrollData(int $employeeMasterPk, Request $request): ?string
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
            return null;
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
        //
        // PR #319 review round 3 (R-003): the refusal used to be silent — an
        // administrator who filled Step 6 during a deploy window got "success" and lost
        // the data, which is the same defect shape R-001 records for the ambiguous
        // credential branch. Both refusals now return their reason to the caller.
        if (! $this->step6SchemaIsReady()) {
            Log::warning('Member wizard: Step 6 payroll data skipped — the step-6 schema is not present on this environment. Run php artisan migrate.', [
                'employee_master_pk' => $employeeMasterPk,
            ]);

            if ($this->mapStep6Data($request) === []) {
                // Nothing was entered on Step 6, so nothing was lost — no need to alarm.
                return null;
            }

            return 'Employee Grade Pay (Step 6) was NOT saved: this environment is missing the '
                . 'payroll schema that step needs. The rest of the member record was saved. '
                . 'Ask the release owner to run the pending database migrations.';
        }

        $data = $this->mapStep6Data($request);

        if (empty($data)) {
            return null;
        }

        // updateOrCreate rather than first()-then-update-or-create: the two-statement
        // form is a read-then-write with no lock, so two concurrent saves for the same
        // employee could both read "no row" and both insert (independent review of
        // PR #319, F-006). The unique index added by 2026_09_22_000002 is what actually
        // enforces one payroll row per employee; this is the matching code shape.
        // Keyed through payrollEmployeeKey(), not by the raw employee_master.pk — see that
        // method. Keying by pk matched no existing row on any measured environment
        // (F-037), so every save created an orphan the Estate module could not read.
        PayrollSalaryMaster::updateOrCreate(
            ['employee_master_pk' => $this->payrollEmployeeKey($employeeMasterPk)],
            $data
        );

        return null;
    }

    /**
     * The key `payroll_salary_master.employee_master_pk` actually holds for an employee.
     *
     * Independent review of PR #319, F-037. Despite its name, that column does NOT hold
     * employee_master.pk on this data. Measured on saragam_live: of 892 payroll rows,
     * 876 match an employee_master.pk_old and **0** match a pk, and the two ranges do not
     * overlap (payroll keys run from 1,001,235,060 upward; employee pks are 10001-11838).
     * employee_master.pk_old is populated on 1826 of 1831 rows and equals pk on none.
     *
     * This is not a guess about intent — the downstream consumer says so in its own code.
     * EstateController::estateEmployeePkColumn() returns 'pk_old' whenever that column
     * exists, and the comment above its payroll join reads "payroll_salary_master may
     * still reference employee_master.pk_old, so we keep a separate column for joins"
     * (EstateController.php:939). Estate joins this table for house eligibility.
     *
     * So Step 6, keying by pk, read nothing for every existing employee and wrote an
     * orphan row Estate would never find. This resolver mirrors Estate's convention.
     *
     * RESIDUAL, deliberately not decided here: 5 employees on this database have a NULL
     * pk_old (the newest rows, and any member this wizard creates). For them there is no
     * legacy key, so their payroll row is keyed by pk — which Estate's pk_old join will
     * not match. Whether those employees should get a pk_old, or Estate should widen its
     * join, is an Estate/payroll domain decision and is raised as a human action rather
     * than settled in this controller.
     */
    private function payrollEmployeeKey(int $employeeMasterPk): int
    {
        if (! Schema::hasColumn('employee_master', 'pk_old')) {
            return $employeeMasterPk;
        }

        $pkOld = EmployeeMaster::where('pk', $employeeMasterPk)->value('pk_old');

        return ($pkOld === null || (int) $pkOld === 0) ? $employeeMasterPk : (int) $pkOld;
    }

    /**
     * Whether the schema Step 6 writes and renders is present on this environment.
     *
     * PR #319 review round 2 (F-003). Resolved once per request and memoised: this is a
     * request-path call, and AUTO-07 exists precisely to stop information_schema reads
     * happening per row. Three objects are checked because three separate migrations
     * supply them, and a half-applied deploy can leave any subset present.
     *
     * PR #319 review round 3 (R-004) — the assumption this static encodes, stated so a
     * later reader does not have to infer it: the cache is per PROCESS, not per request.
     * Under PHP-FPM (how this application is deployed) a process serves one request at a
     * time, so the two are the same thing and this is correct. Under a long-running
     * worker — Laravel Octane, or a queue worker doing member writes — the answer would
     * outlive the migration that changes it, and a freshly-migrated environment would
     * keep reporting the schema as absent until the worker restarted. If Octane is ever
     * adopted, move this to a request-scoped container binding.
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

        $actor = Auth::user();

        // Independent review of PR #319, F-038. The previous version compared
        // Auth::user()->user_id to emp_id and stopped there. That treats user_id as an
        // employee_master.pk for EVERY login, and it is not one: user_id is scoped per
        // user_category. Measured on this database, 328 logins that are NOT employee
        // accounts (327 NULL-category, 1 'S') carry a user_id equal to some unrelated
        // employee's pk — so each of them passed the "it's my own record" test for a
        // stranger. Reproduced end to end on testsargam6 by the independent reviewer: a
        // trainee login renamed employee 11056, and another rewrote employee 11058's own
        // user_credentials row.
        //
        // The category check is what makes user_id mean "employee_master.pk" — store()
        // creates employee logins with user_category 'E' and 'user_id' => $employee->pk,
        // so that is the only category in which the two are the same namespace.
        $isEmployeeLogin = ($actor->user_category ?? null) === 'E';
        $ownEmployeePk = $actor->user_id ?? null;

        abort_unless(
            $isEmployeeLogin
                && $ownEmployeePk !== null
                && (int) $ownEmployeePk === (int) $employeeMasterPk,
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

        // PR #319 review round 3, R-002 follow-through. Blocking Super Admin at
        // resolveSpatieRoleNames() re-created, for that one option, exactly the defect
        // F-005 was raised about: tick the box, see "success", get no permission, with
        // nothing to distinguish that from a working grant. The block is deliberate this
        // time, so the administrator is told instead of left to discover it.
        //
        // The employee_role_mapping row is still written by the caller — the option can
        // legitimately be an HR tag as well as an RBAC name, and this method has no
        // business deciding that. Only the RBAC half is refused, and only that is
        // reported.
        $blockedSelections = $this->blockedRoleSelections($newNames, $spatieRoleNames);

        $blockedWarning = $blockedSelections === [] ? null
            : 'No permissions were granted for ' . implode(', ', $blockedSelections) . ': that role '
            . 'is not grantable from the Member wizard. It is assigned from Role & Permission > Users. '
            . 'The rest of the member record was saved.';

        // A blocked option that was ticked before and is not now. The block filters these
        // out of BOTH sets above, so unticking one is a no-op — correct, but it has to
        // announce itself, and the check needs the member's CURRENT roles to know whether
        // it mattered. That is why this cannot short-circuit below on empty role sets:
        // when the only thing the administrator changed was a blocked option, both sets
        // are empty and the early return would swallow the warning.
        $blockedUnticked = array_diff(
            $this->blockedRoleSelections($oldNames, $spatieRoleNames),
            $blockedSelections
        );

        if (empty($newSpatieRoles) && empty($oldSpatieRoles) && $blockedUnticked === []) {
            return $blockedWarning;
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
                . 'Ask the DBA to reconcile the duplicate user_credentials rows for this employee.'
                . ($blockedWarning ? ' ' . $blockedWarning : '');
        }

        $user = User::find($userCredentialPk);

        if (! $user) {
            return $blockedWarning;
        }

        $currentRoleNames = $user->getRoleNames()->all();

        // The block is symmetric by construction: resolveSpatieRoleNames() filters
        // blocked roles out of BOTH the new and the old selection, so a blocked role can
        // never enter $toAdd and never enter $toRemove. Refusing to revoke is the right
        // half of "this screen does not manage Super Admin" — but doing it silently is
        // the same defect as refusing to grant silently. An administrator who UNTICKS a
        // blocked role a member actually holds sees success and the role stays.
        //
        // Only warn when it would have mattered: the option was ticked before, is not
        // now, and the member really does still hold the role. Warning on every save of
        // a Super Admin would be noise. ($blockedUnticked was computed above, before the
        // early return, so this case can actually be reached.)
        $stillHeld = [];

        foreach ($blockedUnticked as $displayName) {
            foreach ($currentRoleNames as $held) {
                if ($this->normalizeRoleName($held) === $this->normalizeRoleName($displayName)) {
                    $stillHeld[] = $displayName;
                }
            }
        }

        if ($stillHeld !== []) {
            $blockedWarning = trim(($blockedWarning ?? '') . ' '
                . implode(', ', array_unique($stillHeld)) . ' was NOT removed: that role is not '
                . 'managed from the Member wizard. Remove it from Role & Permission > Users.');
        }

        // Only unchecking a role this same screen previously granted removes it.
        $toRemove = array_diff($oldSpatieRoles, $newSpatieRoles);
        $toAdd = array_diff($newSpatieRoles, $currentRoleNames);

        $finalRoleNames = array_values(array_unique(array_merge(
            array_diff($currentRoleNames, $toRemove),
            $toAdd
        )));

        $user->syncRoles($finalRoleNames);

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        return $blockedWarning;
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
            if ($this->roleIsNotGrantableFromThisScreen($spatieRoleName)) {
                continue;
            }

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
     * Roles this screen must never grant or revoke, whatever the checkboxes say.
     *
     * PR #319 review round 3 (R-002). Correcting the F-005 name mismatch had a side
     * effect nobody asked for: it widened what this screen can grant from 19 roles to
     * 21, and the two it added were "Mess Admin" (17 permissions) and "Super Admin"
     * (171 permissions, the highest privilege in the application). Before the fix,
     * ticking "Super-Admin" wrote an employee_role_mapping row and granted nothing —
     * a bug, but one that happened to keep the Member wizard from minting Super Admins.
     *
     * It must not. Super Admin is granted from Role & Permission > Users, which is the
     * screen built for it and carries its own abort_unless(hasRole('Super Admin')) gate.
     * Nobody loses the ability to grant it; it stops being a checkbox on a screen where
     * almost every other option is a plain HR tag with nothing to distinguish the two.
     *
     * This choice was made during development and is NOT a recorded decision of the
     * Engineering lead — an earlier version of this comment claimed it was, which the
     * independent review raised as F-040. If the wizard IS meant to grant Super Admin,
     * remove it from the list below; that reversal is one line.
     *
     * Matched on a SEPARATOR-FREE key, not the normalised name. normalizeRoleName()
     * collapses separators to a single space, so it maps "Super-Admin" to "super admin"
     * but "SuperAdmin" to "superadmin" — meaning the concatenated spelling slipped the
     * block while this docblock claimed it was covered (F-039). app/helpers.php's
     * hasRole() already treats "SuperAdmin" and "Super Admin" as the same role, so a
     * Spatie role under that spelling is a real possibility, not a hypothetical.
     */
    /**
     * Which of the ticked options name a role this screen refuses to grant.
     *
     * Returns the option's own spelling (what the administrator actually clicked), not
     * the canonical `roles` name, so the message names the checkbox they can see.
     */
    private function blockedRoleSelections(array $displayNames, array $spatieRoleNames): array
    {
        // blockedRoleKey(), matching roleIsNotGrantableFromThisScreen(). If this used
        // normalizeRoleName() instead, a ticked "SuperAdmin" option would be refused the
        // grant and produce NO warning — silently dead, which is the F-005 shape the
        // warning exists to prevent.
        $blockedKeys = [];

        foreach ($spatieRoleNames as $spatieRoleName) {
            if ($this->roleIsNotGrantableFromThisScreen($spatieRoleName)) {
                $blockedKeys[$this->blockedRoleKey($spatieRoleName)] = true;
            }
        }

        $hits = [];

        foreach ($displayNames as $displayName) {
            if (isset($blockedKeys[$this->blockedRoleKey($displayName)])) {
                $hits[] = $displayName;
            }
        }

        return array_values(array_unique($hits));
    }

    private function roleIsNotGrantableFromThisScreen(string $spatieRoleName): bool
    {
        $blocked = array_map(
            fn ($name) => $this->blockedRoleKey($name),
            ['Super Admin']
        );

        return in_array($this->blockedRoleKey($spatieRoleName), $blocked, true);
    }

    /**
     * Comparison key for the block list: lowercase with every separator REMOVED.
     *
     * Deliberately not normalizeRoleName(), which collapses separators to a single space
     * and so distinguishes "Super Admin"/"Super-Admin" (both "super admin") from
     * "SuperAdmin" ("superadmin"). That gap let the concatenated spelling through the
     * block (F-039). Dropping separators entirely makes all three the same key.
     *
     * This is the right rule for a deny-list and the wrong one for the grant lookup:
     * resolveSpatieRoleNames() must keep using normalizeRoleName(), because that mirrors
     * the sync migration's own normalize() and a looser key there would start conflating
     * genuinely different roles. A deny-list may over-match safely; a grant map may not.
     */
    private function blockedRoleKey(string $name): string
    {
        return preg_replace('/[\s_-]+/', '', mb_strtolower(trim($name)));
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
        // Same reasoning as update(): a refusal inside the save has to reach the response
        // rather than be swallowed (PR #319 review round 3, R-003). On this path only the
        // missing-schema refusal can fire — a member being created cannot yet have two
        // logins, and store() is admin-only — but it is collected the same way so the two
        // paths cannot drift.
        $saveWarnings = [];

        try {
            DB::transaction(function () use ($request, $profile_picture, $additional_doc_upload, &$saveWarnings) {
                $employee = EmployeeMaster::create(array_merge(
                    $this->mapStep1Data($request),
                    $this->mapStep2Data($request),
                    $this->mapStep4Data($request),
                    $this->mapStep5Data($request, $profile_picture, $additional_doc_upload)
                ));

                $saveWarnings[] = $this->saveStep6PayrollData($employee->pk, $request);

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

        $saveWarnings = array_values(array_filter($saveWarnings));

        if ($saveWarnings !== []) {
            return response()->json([
                'message'  => 'Member successfully created',
                'warning'  => implode(' ', $saveWarnings),
                'warnings' => $saveWarnings,
            ]);
        }

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
        // PR #319 review round 3 (R-001/R-003): a save can now refuse more than one thing
        // — RBAC for an ambiguous member, and Step 6 when its schema is missing — so the
        // reasons are collected rather than overwritten, and every one of them reaches
        // the response.
        $saveWarnings = [];

        // Same reasoning as store(): one transaction across employee_master,
        // payroll_salary_master, user_credentials and the role mappings.
        try {
            DB::transaction(function () use ($request, $profile_picture, $additional_doc_upload, &$saveWarnings) {
            EmployeeMaster::find($request->emp_id)->update(array_merge(
                $this->mapStep1Data($request),
                $this->mapStep2Data($request),
                $this->mapStep4Data($request),
                $this->mapStep5Data($request, $profile_picture, $additional_doc_upload)
            ));

            $saveWarnings[] = $this->saveStep6PayrollData((int) $request->emp_id, $request);

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
            // Independent review of PR #319, F-038 (second half). An administrator may
            // rewrite the member's login; a self-service actor may only touch their OWN
            // credential row, and may not rename the login at all.
            //
            // Two things were wrong before. The row was always resolved by
            // where('user_id', emp_id)->first(), so a self-service save wrote to whichever
            // row that lookup returned rather than to the actor's own — on the measured
            // data that is a DIFFERENT person's login for the colliding accounts. And
            // user_name was always in the payload, so the same save could rename a login.
            // Both were demonstrated: a trainee rewrote employee 11058's user_name and
            // email_id to attacker-chosen values.
            $actingAsAdmin = $this->actingUserCanManageMembers();

            $credentialAttributes = [
                'first_name' => $request->first_name,
                'last_name'  => $request->last_name,
                'email_id'   => $request->personalemail,
                'mobile_no'  => $request->mnumber,
            ];

            if ($actingAsAdmin) {
                // Only an administrator may set the login name or (re)assert the category.
                $credentialAttributes['user_name']     = $request->userid;
                $credentialAttributes['user_category'] = 'E';

                $userCredential = UserCredential::where('user_id', $request->emp_id)
                    ->orderBy('pk')
                    ->first();
            } else {
                // Self-service: the actor's own row, by primary key. authorizeMemberWrite()
                // has already established that this actor is an 'E' login whose user_id is
                // this employee, so there is no lookup to get wrong.
                $userCredential = UserCredential::find(Auth::id());
            }

            if ($userCredential) {
                $userCredential->update($credentialAttributes);
            } elseif ($actingAsAdmin) {
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

                $saveWarnings[] = $this->syncSpatieRolesFromWizardSelection($userCredential->pk, $roles, $previouslySelectedRoleIds);
            }
            });
        } catch (\Throwable $e) {
            $this->deleteUploadedMemberFiles($profile_picture, $additional_doc_upload);
            throw $e;
        }

        MemberDataTable::bumpListingCacheEpoch();

        // PR #319 review round 2 (F-002 residual) and round 3 (R-001/R-003): when part of
        // the save is refused — RBAC for an employee with more than one login, or Step 6
        // on an environment missing its schema — say so. Reporting plain success while
        // silently doing nothing is what made those defects invisible to administrators.
        // The wizard's success handler renders this (edit.blade.php); a warning that only
        // exists in the response body is not a fix.
        $saveWarnings = array_values(array_filter($saveWarnings));

        if ($saveWarnings !== []) {
            return response()->json([
                'message'  => 'Member successfully updated',
                'warning'  => implode(' ', $saveWarnings),
                'warnings' => $saveWarnings,
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
        // Same key as the write path (F-037). Reading by the raw pk showed a blank grade
        // for every existing employee, because no live payroll row is keyed that way.
        $payrollSalary = PayrollSalaryMaster::where('employee_master_pk', $this->payrollEmployeeKey((int) $id))->first();
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
