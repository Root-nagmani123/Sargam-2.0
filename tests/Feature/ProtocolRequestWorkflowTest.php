<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Modules\Protocol\Entities\ProtocolRequest;
use Modules\Protocol\Services\ProtocolDirectory;
use Tests\TestCase;

/**
 * Protocol request paths from the PR #348 review.
 *
 * F-003: show/edit/update were reachable by any employee holding protocol.my_requests,
 * for any request id. F-006: a manager's decision was logged with their user_credentials pk
 * while every other actor is an employee_master pk, so the timeline named the wrong person.
 * F-005: a request that has been decided cannot be decided again.
 * F-016: the requester's employee is resolved by pk or pk_old. F-017: editing a pending
 * request must not change the guests of a sibling already decided. F-018: a request can
 * only be recommended to someone on the approver list.
 *
 * Runs against existing rows and skips when the database has none to use.
 */
class ProtocolRequestWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('protocol_requests')) {
            $this->markTestSkipped('protocol tables are not in this database');
        }
    }

    /** A request with an owner, plus a different employee who may use My Requests but is not Protocol Staff */
    private function requestAndOtherEmployee(): array
    {
        $request = ProtocolRequest::whereNotNull('user_id')->where('status', ProtocolRequest::STATUS_PENDING)->first();
        if (! $request) {
            $this->markTestSkipped('no pending protocol request in this database');
        }

        $other = User::where('pk', '!=', $request->user_id)
            ->where('pk', '!=', (int) $request->recommended_to_id)
            ->whereNotNull('user_id')
            ->limit(50)
            ->get()
            ->first(fn (User $user) => ! $user->can('protocol.protocol_requests'));
        if (! $other) {
            $this->markTestSkipped('no second employee account in this database');
        }

        return [$request, $this->withMyRequests($other)];
    }

    /** Lets this user through the protocol.my_requests route gate for the test only, without writing a permission row */
    private function withMyRequests(User $user): User
    {
        Gate::before(fn ($actor, $ability) => $ability === 'protocol.my_requests' && (int) $actor->pk === (int) $user->pk ? true : null);

        return $user;
    }

    public function test_another_employee_cannot_open_or_change_someone_elses_request(): void
    {
        [$request, $other] = $this->requestAndOtherEmployee();

        $this->actingAs($other)->get(route('protocol.requests.show', $request))->assertForbidden();
        $this->actingAs($other)->get(route('protocol.requests.edit', $request))->assertForbidden();
        $this->actingAs($other)->put(route('protocol.requests.update', $request), [])->assertForbidden();
    }

    public function test_the_owner_can_open_their_request(): void
    {
        [$request] = $this->requestAndOtherEmployee();
        $owner = User::find($request->user_id);
        if (! $owner) {
            $this->markTestSkipped('request owner account is not in this database');
        }

        $this->actingAs($this->withMyRequests($owner))->get(route('protocol.requests.show', $request))->assertOk();
    }

    public function test_a_manager_decision_is_logged_as_the_managers_employee_and_cannot_be_repeated(): void
    {
        $request = ProtocolRequest::where('status', ProtocolRequest::STATUS_RECOMMENDED)->whereNotNull('recommended_to_id')->first();
        $manager = $request ? User::find($request->recommended_to_id) : null;
        if (! $manager || ! $manager->user_id) {
            $this->markTestSkipped('no recommended request with a resolvable approver in this database');
        }

        $this->actingAs($manager)
            ->post(route('protocol.manager.decide', $request), ['decision' => 'approve', 'remarks' => 'test'])
            ->assertRedirect(route('protocol.manager.queue'));

        $request->refresh();
        $this->assertSame(ProtocolRequest::STATUS_APPROVED, $request->status);
        $this->assertSame((int) $manager->user_id, (int) $request->decided_by_id);
        $this->assertSame((int) $manager->user_id, (int) $request->logs()->get()->last()->action_by_id);

        $this->actingAs($manager)
            ->post(route('protocol.manager.decide', $request), ['decision' => 'reject'])
            ->assertForbidden();
    }

    public function test_editing_a_pending_request_leaves_a_decided_siblings_guests_alone_and_is_logged(): void
    {
        if (! Schema::hasColumn('protocol_request_batches', 'other_purpose')) {
            $this->markTestSkipped('protocol_request_batches has no other_purpose column, which every edit writes (schema of record: F-001)');
        }

        $request = ProtocolRequest::where('status', ProtocolRequest::STATUS_PENDING)
            ->where('request_type', 'ticket')
            ->whereNotNull('user_id')
            ->get()
            ->first(fn ($r) => ProtocolRequest::where('batch_id', $r->batch_id)
                ->whereKeyNot($r->getKey())
                ->where('status', '!=', ProtocolRequest::STATUS_PENDING)
                ->exists());
        $owner = $request ? User::find($request->user_id) : null;
        if (! $owner) {
            $this->markTestSkipped('no pending ticket request with a decided sibling in this database');
        }

        $sharedBatch = $request->batch;
        $guestsBefore = $sharedBatch->guests()->pluck('guest_name', 'id')->all();
        $journeys = $request->requestable->journeys;

        $this->actingAs($this->withMyRequests($owner))
            ->put(route('protocol.requests.update', $request), [
                'types' => ['ticket'],
                'purpose' => $sharedBatch->purpose ?: 'Official',
                'escort_required' => $sharedBatch->escort_required ? 'yes' : 'no',
                'guests' => [['guest_name' => 'Edited Guest', 'sex' => 'Male', 'mobile_no' => '9876543210']],
                'ticket' => ['journeys' => $journeys->map(fn ($j) => [
                    'id' => $j->id,
                    'origin' => $j->origin ?: 'Delhi',
                    'destination' => $j->destination ?: 'Dehradun',
                    'ticket_type' => in_array($j->ticket_type, ['Air', 'Rail', 'Bus'], true) ? $j->ticket_type : 'Rail',
                    'date_of_journey' => now()->addDay()->toDateString(),
                    'train_flight_bus_name' => $j->train_flight_bus_name ?: 'Shatabdi',
                    'train_flight_bus_no' => $j->train_flight_bus_no ?: '12017',
                ])->all() ?: [[
                    'origin' => 'Delhi', 'destination' => 'Dehradun', 'ticket_type' => 'Rail',
                    'date_of_journey' => now()->addDay()->toDateString(),
                    'train_flight_bus_name' => 'Shatabdi', 'train_flight_bus_no' => '12017',
                ]]],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('protocol.requests.my'));

        $this->assertSame($guestsBefore, $sharedBatch->guests()->pluck('guest_name', 'id')->all(), 'the decided sibling\'s guests changed');

        $request->refresh();
        $this->assertNotSame((int) $sharedBatch->id, (int) $request->batch_id);
        $this->assertSame(['Edited Guest'], $request->batch->guests()->pluck('guest_name')->all());
        $this->assertSame('updated', $request->logs()->get()->last()->action);
    }

    public function test_a_request_cannot_be_recommended_to_someone_off_the_approver_list(): void
    {
        $request = ProtocolRequest::where('status', ProtocolRequest::STATUS_PENDING)->first();
        $staff = User::permission('protocol.protocol_requests')->first();
        if (! $request || ! $staff) {
            $this->markTestSkipped('no pending request or Protocol Staff account in this database');
        }

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
}
