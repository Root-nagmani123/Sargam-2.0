<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\Protocol\Entities\ProtocolRequest;
use Modules\Protocol\Entities\VehiclePassRequest;
use Modules\Protocol\Services\ApprovalService;
use Modules\Protocol\Services\ProtocolDirectory;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\Feature\Concerns\SeedsProtocolRequests;
use Tests\TestCase;

/**
 * Protocol request paths from the PR #348 review.
 *
 * F-003: show/edit/update were reachable by any employee holding protocol.my_requests,
 * for any request id. F-006/F-021: every actor on the approval trail is recorded as the
 * employee their login resolves to (pk, else a unique pk_old). F-005: a request that has
 * been decided cannot be decided again. F-007: the permission screen only changes the
 * users it lists, and logs each change. F-016: the requester is resolved by pk or pk_old.
 * F-017: editing a pending request must not change the guests of a sibling already
 * decided. F-018: a request can only be recommended to someone on the approver list.
 *
 * Every protocol_* row is created inside the test's transaction (F-022).
 */
class ProtocolRequestWorkflowTest extends TestCase
{
    use DatabaseTransactions {
        beginDatabaseTransaction as startDatabaseTransaction;
    }
    use SeedsProtocolRequests;

    public function beginDatabaseTransaction()
    {
        $this->skipUnlessProtocolDatabase();
        $this->startDatabaseTransaction();
    }

    /** An employee who may use My Requests but is not Protocol Staff */
    private function plainEmployee(array $exceptPks = []): User
    {
        $employee = $this->employeeLogin($exceptPks, false, fn (User $user) => ! $user->can('protocol.protocol_requests'));

        return $this->allow($employee, 'protocol.my_requests');
    }

    public function test_another_employee_cannot_open_or_change_someone_elses_request(): void
    {
        $owner = $this->plainEmployee();
        $other = $this->plainEmployee([$owner->pk]);
        $request = $this->seedRequest($owner);

        $this->actingAs($other)->get(route('protocol.requests.show', $request))->assertForbidden();
        $this->actingAs($other)->get(route('protocol.requests.edit', $request))->assertForbidden();
        $this->actingAs($other)->put(route('protocol.requests.update', $request), [])->assertForbidden();
    }

    public function test_the_owner_can_open_their_request(): void
    {
        $owner = $this->plainEmployee();
        $request = $this->seedRequest($owner);

        $this->actingAs($owner)->get(route('protocol.requests.show', $request))->assertOk();
    }

    public function test_a_manager_decision_is_logged_as_the_managers_employee_and_cannot_be_repeated(): void
    {
        $owner = $this->employeeLogin();
        $manager = $this->employeeLogin([$owner->pk]);
        $request = $this->seedRequest($owner, ProtocolRequest::STATUS_RECOMMENDED, null, ['recommended_to_id' => $manager->pk]);
        $managerEmployeePk = app(ProtocolDirectory::class)->employeePkFor($manager);

        $this->actingAs($manager)
            ->post(route('protocol.manager.decide', $request), ['decision' => 'approve', 'remarks' => 'test'])
            ->assertRedirect(route('protocol.requests.all'));

        $request->refresh();
        $this->assertSame(ProtocolRequest::STATUS_APPROVED, $request->status);
        $this->assertSame($managerEmployeePk, (int) $request->decided_by_id);
        $this->assertSame($managerEmployeePk, (int) $this->lastLog($request)->action_by_id);

        $this->actingAs($manager)
            ->post(route('protocol.manager.decide', $request), ['decision' => 'reject', 'remarks' => 'too late'])
            ->assertForbidden();
        // PR #355 F-002: the 403 answers before the remarks rule, so a remarks-less repeat is refused, not validated
        $this->actingAs($manager)
            ->post(route('protocol.manager.decide', $request), ['decision' => 'reject'])
            ->assertForbidden();
        $this->assertSame(1, $request->logs()->where('action', '!=', 'raised')->count());
    }

    public function test_a_manager_cannot_reject_without_remarks(): void
    {
        $owner = $this->employeeLogin();
        $manager = $this->employeeLogin([$owner->pk]);
        $request = $this->seedRequest($owner, ProtocolRequest::STATUS_RECOMMENDED, null, ['recommended_to_id' => $manager->pk]);

        foreach ([[], ['remarks' => ''], ['remarks' => '   ']] as $remarks) {
            $this->actingAs($manager)
                ->post(route('protocol.manager.decide', $request), ['decision' => 'reject'] + $remarks)
                ->assertSessionHasErrors('remarks');
        }
        $this->assertSame(ProtocolRequest::STATUS_RECOMMENDED, $request->refresh()->status);

        $this->actingAs($manager)
            ->post(route('protocol.manager.decide', $request), ['decision' => 'reject', 'remarks' => 'Dates clash with the convocation'])
            ->assertRedirect(route('protocol.requests.all'));
        $request->refresh();
        $this->assertSame(ProtocolRequest::STATUS_REJECTED, $request->status);
        $this->assertSame('Dates clash with the convocation', $this->lastLog($request)->remarks);
    }

    public function test_protocol_staff_can_reject_a_pending_request_with_remarks(): void
    {
        $owner = $this->employeeLogin();
        $staff = $this->allow($this->employeeLogin([$owner->pk]), 'protocol.protocol_requests');
        $request = $this->seedRequest($owner);
        $staffEmployeePk = app(ProtocolDirectory::class)->employeePkFor($staff);

        $this->actingAs($staff)
            ->get(route('protocol.approval.review', $request))
            ->assertOk()
            ->assertSee('Approve')
            ->assertSee('Recommend')
            ->assertSee('Reject')
            ->assertSee('required when rejecting');

        $this->actingAs($staff)
            ->post(route('protocol.approval.decide', $request), ['decision' => 'reject'])
            ->assertSessionHasErrors('remarks');
        $this->assertSame(ProtocolRequest::STATUS_PENDING, $request->refresh()->status);

        $this->actingAs($staff)
            ->post(route('protocol.approval.decide', $request), [
                'decision' => 'reject',
                'remarks' => 'Insufficient information',
            ])
            ->assertRedirect(route('protocol.requests.all'))
            ->assertSessionHas('success', "Request {$request->request_number} rejected.");

        $request->refresh();
        $this->assertSame(ProtocolRequest::STATUS_REJECTED, $request->status);
        $this->assertSame($staffEmployeePk, (int) $request->decided_by_id);
        $this->assertSame('rejected', $this->lastLog($request)->action);
        $this->assertSame('Protocol Staff', $this->lastLog($request)->action_by_role);
        $this->assertSame('Insufficient information', $this->lastLog($request)->remarks);
    }

    public function test_review_actions_are_limited_to_pending_or_current_recommended_user(): void
    {
        $owner = $this->employeeLogin();
        $staff = $this->allow($this->employeeLogin([$owner->pk]), 'protocol.protocol_requests');
        $manager = $this->employeeLogin([$owner->pk, $staff->pk]);
        $nextManager = $this->employeeLogin([$owner->pk, $staff->pk, $manager->pk]);
        $approverRole = Role::firstOrCreate(['name' => ProtocolDirectory::APPROVER_ROLE, 'guard_name' => 'web']);
        $manager->assignRole($approverRole);
        $nextManager->assignRole($approverRole);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $recommended = $this->seedRequest($owner, ProtocolRequest::STATUS_RECOMMENDED, null, [
            'recommended_to_id' => $manager->pk,
        ]);

        $this->actingAs($manager)
            ->get(route('protocol.approval.review', $recommended))
            ->assertOk()
            ->assertSee('Approve')
            ->assertSee('Reject')
            ->assertSee('Recommend')
            ->assertSee(route('protocol.requests.all'), false)
            ->assertSee(route('protocol.manager.decide', $recommended), false);

        $this->actingAs($manager)
            ->post(route('protocol.approval.decide', $recommended), [
                'decision' => 'recommend',
                'recommended_to_id' => $nextManager->pk,
                'remarks' => 'Please review this request.',
            ])
            ->assertRedirect(route('protocol.requests.all'));

        $recommended->refresh();
        $this->assertSame(ProtocolRequest::STATUS_RECOMMENDED, $recommended->status);
        $this->assertSame($nextManager->pk, (int) $recommended->recommended_to_id);
        $this->assertSame('Manager', $this->lastLog($recommended)->action_by_role);

        $this->actingAs($staff)
            ->get(route('protocol.approval.review', $recommended))
            ->assertOk()
            ->assertDontSee('id="decisionForm"', false)
            ->assertDontSee('id="recommendModal"', false);

        foreach ([ProtocolRequest::STATUS_APPROVED, ProtocolRequest::STATUS_REJECTED] as $status) {
            $decided = $this->seedRequest($owner, $status);
            $this->actingAs($staff)
                ->get(route('protocol.approval.review', $decided))
                ->assertOk()
                ->assertDontSee('id="decisionForm"', false)
                ->assertDontSee('id="recommendModal"', false);
        }
    }

    public function test_all_requests_includes_log_recommendations_once(): void
    {
        $owner = $this->employeeLogin();
        $recommendedUser = $this->employeeLogin([$owner->pk]);
        $otherUser = $this->employeeLogin([$owner->pk, $recommendedUser->pk]);
        $request = $this->seedRequest($owner, ProtocolRequest::STATUS_APPROVED, null, [
            'recommended_to_id' => $otherUser->pk,
        ]);
        $actorEmployeeId = app(ProtocolDirectory::class)->employeePkFor($owner);

        foreach (['First recommendation', 'Second recommendation'] as $remarks) {
            $request->logs()->create([
                'action' => 'recommended',
                'action_by_id' => $actorEmployeeId,
                'action_by_role' => 'Protocol Staff',
                'recommended_to_id' => $recommendedUser->pk,
                'remarks' => $remarks,
                'action_at' => now(),
            ]);
        }

        $this->actingAs($recommendedUser);
        $listedRequests = app(ApprovalService::class)
            ->scopedQuery()
            ->whereKey($request->getKey())
            ->get();

        $this->assertCount(1, $listedRequests);
        $this->assertSame($request->getKey(), $listedRequests->first()->getKey());
    }

    /** PR #355 F-002: a manager the request was not recommended to gets 403, with or without remarks */
    public function test_a_manager_it_was_not_recommended_to_cannot_decide_it(): void
    {
        $owner = $this->employeeLogin();
        $manager = $this->employeeLogin([$owner->pk]);
        $otherManager = $this->employeeLogin([$owner->pk, $manager->pk]);
        $request = $this->seedRequest($owner, ProtocolRequest::STATUS_RECOMMENDED, null, ['recommended_to_id' => $manager->pk]);

        foreach ([['decision' => 'reject'], ['decision' => 'reject', 'remarks' => 'Not mine'], ['decision' => 'approve']] as $payload) {
            $this->actingAs($otherManager)
                ->post(route('protocol.manager.decide', $request), $payload)
                ->assertForbidden();
        }
        $this->assertSame(ProtocolRequest::STATUS_RECOMMENDED, $request->refresh()->status);
    }

    /** F-021: a login keyed on an employee's pk_old is listed, recommended to, and recorded as that employee */
    public function test_a_pk_old_keyed_approver_is_listed_and_recorded_as_their_own_employee(): void
    {
        $approver = $this->employeeLogin([], true);
        $owner = $this->employeeLogin([$approver->pk]);
        $staff = $this->allow($this->employeeLogin([$approver->pk, $owner->pk]), 'protocol.protocol_requests');
        $directory = app(ProtocolDirectory::class);
        $approverEmployeePk = $directory->employeePkFor($approver);

        $approver->assignRole(Role::firstOrCreate(['name' => ProtocolDirectory::APPROVER_ROLE, 'guard_name' => 'web']));
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $listed = $directory->approvers()->firstWhere('user_credentials_pk', $approver->pk);
        $this->assertNotNull($listed, 'the pk_old-keyed approver is missing from the Recommend to list');
        $this->assertSame($approverEmployeePk, (int) $listed->employee_pk);

        $request = $this->seedRequest($owner);
        $this->actingAs($staff)
            ->post(route('protocol.approval.decide', $request), ['decision' => 'recommend', 'recommended_to_id' => $approver->pk])
            ->assertSessionHasNoErrors();
        $this->assertSame($directory->employeePkFor($staff), (int) $this->lastLog($request)->action_by_id);

        $this->actingAs($approver)
            ->post(route('protocol.manager.decide', $request), ['decision' => 'approve'])
            ->assertRedirect(route('protocol.requests.all'));

        $request->refresh();
        $this->assertSame($approverEmployeePk, (int) $request->decided_by_id);
        $this->assertSame($approverEmployeePk, (int) $this->lastLog($request)->action_by_id);
        $this->assertSame($approverEmployeePk, (int) $this->lastLog($request)->actionBy->pk);
    }

    public function test_editing_a_pending_request_leaves_a_decided_siblings_guests_alone_and_is_logged(): void
    {
        if (! Schema::hasColumn('protocol_request_batches', 'other_purpose')) {
            $this->markTestSkipped('protocol_request_batches has no other_purpose column, which every edit writes (schema of record: F-001)');
        }

        $owner = $this->plainEmployee();
        $decided = $this->seedRequest($owner, ProtocolRequest::STATUS_APPROVED);
        $sharedBatch = $decided->batch;
        $request = $this->seedRequest($owner, ProtocolRequest::STATUS_PENDING, $sharedBatch);
        $guestsBefore = $sharedBatch->guests()->pluck('guest_name', 'id')->all();
        $journey = $request->requestable->journeys->first();

        $this->actingAs($owner)
            ->put(route('protocol.requests.update', $request), [
                'types' => ['ticket'],
                'purpose' => 'Official',
                'escort_required' => 'no',
                'guests' => [['guest_name' => 'Edited Guest', 'sex' => 'Male', 'mobile_no' => '9876543210']],
                'ticket' => ['journeys' => [[
                    'id' => $journey->id,
                    'origin' => 'Delhi',
                    'destination' => 'Dehradun',
                    'ticket_type' => 'Rail',
                    'date_of_journey' => now()->addDay()->toDateString(),
                    'train_flight_bus_name' => 'Shatabdi',
                    'train_flight_bus_no' => '12017',
                ]]],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('protocol.requests.my'));

        $this->assertSame($guestsBefore, $sharedBatch->guests()->pluck('guest_name', 'id')->all(), 'the decided sibling\'s guests changed');

        $request->refresh();
        $this->assertNotSame((int) $sharedBatch->id, (int) $request->batch_id);
        $this->assertSame(['Edited Guest'], $request->batch->guests()->pluck('guest_name')->all());
        $this->assertSame('updated', $this->lastLog($request)->action);
    }

    public function test_a_request_cannot_be_recommended_to_someone_off_the_approver_list(): void
    {
        $owner = $this->employeeLogin();
        $staff = $this->allow($this->employeeLogin([$owner->pk]), 'protocol.protocol_requests');
        $request = $this->seedRequest($owner);

        $this->actingAs($staff)
            ->from(route('protocol.approval.review', $request))
            ->post(route('protocol.approval.decide', $request), ['decision' => 'recommend', 'recommended_to_id' => 987654321])
            ->assertSessionHasErrors('recommended_to_id');

        $this->assertSame(ProtocolRequest::STATUS_PENDING, $request->fresh()->status);
    }

    public function test_a_login_keyed_on_an_employees_pk_old_raises_requests_as_that_employee(): void
    {
        if (! Schema::hasColumn('employee_master', 'pk_old')) {
            $this->markTestSkipped('employee_master has no pk_old column');
        }

        // A pk_old that names exactly one employee and is nobody's pk
        $employee = DB::table('employee_master as e')
            ->whereNotNull('e.pk_old')
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('employee_master as p')->whereColumn('p.pk', 'e.pk_old'))
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('employee_master as o')->whereColumn('o.pk_old', 'e.pk_old')->whereColumn('o.pk', '!=', 'e.pk'))
            ->first(['e.pk', 'e.pk_old']);
        if (! $employee) {
            $this->markTestSkipped('no unambiguous pk_old in this database');
        }

        $login = new User();
        $login->user_id = $employee->pk_old;

        $this->assertSame((string) $employee->pk, (string) app(ProtocolDirectory::class)->employeeFor($login)?->pk);
    }

    /** F-024: the vehicle dropdown offers active, undeleted vehicles; edit keeps the saved one */
    public function test_the_vehicle_dropdown_offers_only_active_vehicles_and_keeps_the_saved_one_on_edit(): void
    {
        $owner = $this->plainEmployee();
        $active = $this->seedVehicle('Active Test Car', 1);
        $inactive = $this->seedVehicle('Inactive Test Car', 0);
        $deleted = $this->seedVehicle('Deleted Test Car', 0, now());

        $offered = $this->actingAs($owner)->get(route('protocol.requests.create'))->assertOk()->viewData('vehicles');
        $this->assertArrayHasKey($active, $offered->all());
        $this->assertArrayNotHasKey($inactive, $offered->all());
        $this->assertArrayNotHasKey($deleted, $offered->all());

        // A pending vehicle request whose vehicle has since been made inactive
        $pass = VehiclePassRequest::create(['vehicle_type' => (string) $inactive, 'one_way_booking' => false, 'payment_will_be_done_by' => 'Myself']);
        $request = $this->seedRequest($owner, ProtocolRequest::STATUS_PENDING, null, [
            'request_type' => 'vehicle', 'requestable_id' => $pass->id, 'requestable_type' => VehiclePassRequest::class,
        ]);

        $onEdit = $this->actingAs($owner)->get(route('protocol.requests.edit', $request))->assertOk()->viewData('vehicles');
        $this->assertArrayHasKey($inactive, $onEdit->all());
        $this->assertArrayHasKey($active, $onEdit->all());
        $this->assertArrayNotHasKey($deleted, $onEdit->all());
    }

    /**
     * F-028: when the next PR-<id + 1> is already taken (PR-1001 again once the highest id is
     * 1000), a submission through the form still gets a new, unique number. The collision is set
     * up by renumbering a row rather than inserting id 1000, which would move the table's
     * AUTO_INCREMENT for good on a non-disposable database.
     */
    public function test_a_submission_gets_a_new_number_when_the_next_one_is_taken(): void
    {
        $owner = $this->plainEmployee();
        $last = $this->seedRequest($owner);
        $taken = 'PR-' . ($last->id + 1);
        $last->update(['request_number' => $taken]);

        $this->submitTicket($owner)->assertSessionHasNoErrors()->assertSessionMissing('error');

        $created = ProtocolRequest::where('id', '>', $last->id)->sole();
        $this->assertNotSame($taken, $created->request_number);
        $this->assertSame(1, ProtocolRequest::withTrashed()->where('request_number', $created->request_number)->count());
    }

    /** PR #355 F-003: a new request is numbered one after the highest number issued, not from its id */
    public function test_a_submission_is_numbered_after_the_highest_number_issued(): void
    {
        $owner = $this->plainEmployee();
        $last = $this->seedRequest($owner);
        $highest = $this->highestIssuedNumber() + 50;
        $last->update(['request_number' => 'PR-' . $highest]);

        $this->submitTicket($owner)->assertSessionHasNoErrors()->assertSessionMissing('error');

        $this->assertSame('PR-' . ($highest + 1), ProtocolRequest::where('id', '>', $last->id)->sole()->request_number);
    }

    /** PR #355 F-003: a soft-deleted request still holds its number, so the next one continues after it */
    public function test_a_soft_deleted_highest_number_is_not_reissued(): void
    {
        $owner = $this->plainEmployee();
        $last = $this->seedRequest($owner);
        $highest = $this->highestIssuedNumber() + 50;
        $last->update(['request_number' => 'PR-' . $highest]);
        $last->delete();

        $this->submitTicket($owner)->assertSessionHasNoErrors()->assertSessionMissing('error');

        $this->assertSame('PR-' . ($highest + 1), ProtocolRequest::where('id', '>', $last->id)->sole()->request_number);
    }

    /** PR #355 F-004: Course Team To Notify must be a course_master pk, which is what course() joins on */
    public function test_course_team_to_notify_must_be_an_existing_course(): void
    {
        $owner = $this->plainEmployee();
        $missingPk = (int) DB::table('course_master')->max('pk') + 1000;

        foreach (['12abc', 'Foundation', $missingPk] as $value) {
            $this->submitTicket($owner, ['course_team_to_notify' => $value])->assertSessionHasErrors('course_team_to_notify');
        }

        $course = DB::table('course_master')->value('pk');
        if ($course !== null) {
            $this->submitTicket($owner, ['course_team_to_notify' => $course])->assertSessionHasNoErrors();
        }
    }

    private function highestIssuedNumber(): int
    {
        return (int) ProtocolRequest::withTrashed()->max(DB::raw('CAST(SUBSTRING(request_number, 4) AS UNSIGNED)'));
    }

    /** A one-journey ticket submission through the store route, as the create form posts it */
    private function submitTicket(User $owner, array $overrides = [])
    {
        return $this->actingAs($owner)->post(route('protocol.requests.store'), $overrides + [
            'types' => ['ticket'],
            'purpose' => 'Official',
            'escort_required' => 'no',
            'guests' => [['guest_name' => 'Numbering Guest', 'sex' => 'Male', 'mobile_no' => '9876543210']],
            'ticket' => ['journeys' => [[
                'origin' => 'Delhi', 'destination' => 'Dehradun', 'ticket_type' => 'Rail',
                'date_of_journey' => now()->addDay()->toDateString(),
                'train_flight_bus_name' => 'Shatabdi', 'train_flight_bus_no' => '12017',
            ]]],
        ]);
    }

    private function seedVehicle(string $name, int $status, $deletedAt = null): int
    {
        $unique = uniqid('T', true);

        return (int) DB::table('protocol_vehicle_masters')->insertGetId([
            'vehicle_name' => $name, 'vehicle_number' => $unique, 'manufacturer' => 'Test', 'engine_number' => $unique,
            'chassis_number' => $unique, 'model' => 'Test', 'current_reading' => 0, 'fuel_type' => 'Petrol',
            'vehicle_type' => 'Car', 'capacity' => 4, 'status' => $status, 'maintained_by' => 'Test',
            'registration_number' => $unique, 'country_id' => 1, 'state_id' => 1, 'city_id' => 1, 'driver_id' => '1',
            'registration_authority' => 'Test', 'validity_years' => 1, 'purchase_type' => 'purchase', 'has_insurance' => 0,
            'deleted_at' => $deletedAt, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /** F-007: only users the screen lists can be changed */
    public function test_the_permission_screen_refuses_a_user_it_does_not_list(): void
    {
        $listed = $this->protocolDepartmentLogins();
        $admin = $this->allow($this->employeeLogin(), 'protocol.permissions');
        $outsider = $this->employeeLogin([], false, fn (User $user) => ! $listed->contains($user->pk));

        $this->actingAs($admin)->getJson('/protocol/permissions/employee/' . $outsider->pk)->assertForbidden();
        $this->actingAs($admin)->postJson('/protocol/permissions/employee/' . $outsider->pk, ['permissions' => []])->assertForbidden();
    }

    /** F-007: each change to a listed user is logged with who made it */
    public function test_a_permission_change_is_logged(): void
    {
        $target = (int) $this->protocolDepartmentLogins()->first();
        $admin = $this->allow($this->employeeLogin([$target]), 'protocol.permissions');
        Log::spy();

        $this->actingAs($admin)->postJson('/protocol/permissions/employee/' . $target, ['permissions' => []])->assertOk();

        Log::shouldHaveReceived('info')->with('Protocol permissions updated', \Mockery::on(fn ($context) => $context['by_user_credentials_pk'] === $admin->pk
            && $context['target_user_credentials_pk'] === $target))->once();
    }

    /** user_credentials pks the permission screen lists (the Protocol department) */
    private function protocolDepartmentLogins()
    {
        $department = DB::table('department_master')->where('department_name', 'Protocol')->value('pk');
        $logins = $department ? app(ProtocolDirectory::class)->employees($department)->pluck('user_credentials_pk') : collect();
        if ($logins->isEmpty()) {
            $this->markTestSkipped('no Protocol department employees in this database');
        }

        return $logins->map(fn ($pk) => (int) $pk);
    }
}
