<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\Protocol\Entities\ProtocolRequest;
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
            ->assertRedirect(route('protocol.manager.queue'));

        $request->refresh();
        $this->assertSame(ProtocolRequest::STATUS_APPROVED, $request->status);
        $this->assertSame($managerEmployeePk, (int) $request->decided_by_id);
        $this->assertSame($managerEmployeePk, (int) $this->lastLog($request)->action_by_id);

        $this->actingAs($manager)
            ->post(route('protocol.manager.decide', $request), ['decision' => 'reject'])
            ->assertForbidden();
        $this->assertSame(1, $request->logs()->where('action', '!=', 'raised')->count());
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
            ->assertRedirect(route('protocol.manager.queue'));

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
