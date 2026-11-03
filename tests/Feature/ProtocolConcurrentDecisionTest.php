<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Modules\Protocol\Entities\ProtocolRequest;
use Modules\Protocol\Entities\TicketJourney;
use Modules\Protocol\Entities\TicketRequest;
use Modules\Protocol\Services\ProtocolDirectory;
use Tests\Feature\Concerns\SeedsProtocolRequests;
use Tests\TestCase;

/**
 * PR #348 F-005: two Protocol Staff deciding the same request at once. Needs two real
 * database sessions, so the rows are committed (a second session cannot see another
 * session's open transaction) and removed again in tearDown.
 */
class ProtocolConcurrentDecisionTest extends TestCase
{
    use SeedsProtocolRequests;

    private ?ProtocolRequest $request = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->skipUnlessProtocolDatabase();

        config(['database.connections.protocol_second_session' => config('database.connections.' . config('database.default'))]);
    }

    protected function tearDown(): void
    {
        while (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        DB::disconnect('protocol_second_session');

        if ($this->request) {
            $request = $this->request;
            $request->logs()->delete();
            TicketJourney::where('ticket_id', $request->requestable_id)->delete();
            TicketRequest::whereKey($request->requestable_id)->delete();
            $batch = $request->batch;
            ProtocolRequest::withTrashed()->whereKey($request->getKey())->forceDelete();
            $batch?->guests()->delete();
            $batch?->delete();
        }

        parent::tearDown();
    }

    public function test_a_second_decision_waits_for_the_first_then_is_refused(): void
    {
        $owner = $this->employeeLogin();
        $staffA = $this->allow($this->employeeLogin([$owner->pk]), 'protocol.protocol_requests');
        $staffB = $this->allow($this->employeeLogin([$owner->pk, $staffA->pk]), 'protocol.protocol_requests');
        $this->request = $this->seedRequest($owner);
        $id = $this->request->getKey();

        // Session B has opened the review and seen the request as pending
        $sessionB = DB::connection('protocol_second_session');
        $sessionB->statement('SET SESSION innodb_lock_wait_timeout = 1');
        $sessionB->beginTransaction();
        $statusB = fn (bool $locking) => $sessionB->table('protocol_requests')->where('id', $id)
            ->when($locking, fn ($q) => $q->lockForUpdate())->value('status');
        $this->assertSame(ProtocolRequest::STATUS_PENDING, $statusB(false));

        // Session A approves and has not committed yet
        DB::beginTransaction();
        $this->request->approveDirectly(app(ProtocolDirectory::class)->employeePkFor($staffA), 'A');

        // B's locking read (what lockStatus() runs) waits for A instead of proceeding
        try {
            $statusB(true);
            $this->fail('session B read the row while session A held the decision lock');
        } catch (QueryException $e) {
            $this->assertSame(1205, (int) ($e->errorInfo[1] ?? 0), $e->getMessage());
        }

        DB::commit();

        // A plain read in B's transaction still says pending; only the locking read sees A's decision,
        // which is why lockStatus() must lock rather than trust the status loaded earlier
        $this->assertSame(ProtocolRequest::STATUS_PENDING, $statusB(false));
        $this->assertSame(ProtocolRequest::STATUS_APPROVED, $statusB(true));
        $sessionB->rollBack();

        // Once A has committed, B's decision is refused and A's stands
        $this->actingAs($staffB)
            ->post(route('protocol.approval.decide', $id), ['decision' => 'approve', 'remarks' => 'B'])
            ->assertForbidden();

        $this->assertSame(ProtocolRequest::STATUS_APPROVED, $this->request->fresh()->status);
        $this->assertSame(['A'], $this->request->logs()->where('action', 'approved')->pluck('remarks')->all());
    }
}
