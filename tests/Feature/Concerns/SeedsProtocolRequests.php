<?php

namespace Tests\Feature\Concerns;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Modules\Protocol\Entities\ProtocolRequest;
use Modules\Protocol\Entities\ProtocolRequestBatch;
use Modules\Protocol\Entities\TicketJourney;
use Modules\Protocol\Entities\TicketRequest;
use Modules\Protocol\Services\ProtocolDirectory;

/**
 * Builds the Protocol rows a test needs instead of relying on rows that happen to
 * exist (PR #348 F-022). Logins and employees are master data and are picked from
 * the database; everything under protocol_* is created by the test.
 */
trait SeedsProtocolRequests
{
    /** Skips, rather than errors, when the database is unreachable or has no Protocol tables */
    protected function skipUnlessProtocolDatabase(): void
    {
        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $this->markTestSkipped('database unreachable: ' . $e->getMessage());
        }

        if (! Schema::hasTable('protocol_requests')) {
            $this->markTestSkipped('protocol tables are not in this database');
        }
    }

    /**
     * A non-student login that resolves to exactly one employee. $byPkOld picks one whose
     * user_id is an employee's pk_old rather than a pk.
     */
    protected function employeeLogin(array $exceptPks = [], bool $byPkOld = false, ?callable $where = null): User
    {
        $query = User::where('user_category', '!=', 'S')->whereNotIn('pk', $exceptPks);

        if ($byPkOld) {
            if (! Schema::hasColumn('employee_master', 'pk_old')) {
                $this->markTestSkipped('employee_master has no pk_old column');
            }
            $query->whereNotIn('user_id', DB::table('employee_master')->select('pk'))
                ->whereIn('user_id', DB::table('employee_master')->whereNotNull('pk_old')->select('pk_old'));
        } else {
            $query->whereIn('user_id', DB::table('employee_master')->select('pk'));
        }

        $directory = app(ProtocolDirectory::class);
        $login = $query->orderBy('pk')->limit(50)->get()
            ->first(fn (User $user) => $directory->employeePkFor($user) !== null && (! $where || $where($user)));

        if (! $login) {
            $this->markTestSkipped('no suitable employee login in this database');
        }

        return $login;
    }

    /** Lets this user through a can: route gate for the test only, without writing a permission row */
    protected function allow(User $user, string $ability): User
    {
        Gate::before(fn ($actor, $asked) => $asked === $ability && (int) $actor->getKey() === (int) $user->getKey() ? true : null);

        return $user;
    }

    /** A ticket request raised by $owner, in its own batch unless one is given */
    protected function seedRequest(User $owner, string $status = ProtocolRequest::STATUS_PENDING, ?ProtocolRequestBatch $batch = null, array $attributes = []): ProtocolRequest
    {
        $employeePk = app(ProtocolDirectory::class)->employeePkFor($owner);

        if (! $batch) {
            $batch = ProtocolRequestBatch::create([
                'user_id' => $owner->getKey(),
                'employee_id' => $employeePk,
                'purpose' => 'Official',
                'escort_required' => false,
                'is_faculty' => false,
            ]);
            $batch->guests()->create([
                'guest_name' => 'Seeded Guest',
                'sex' => 'Male',
                'mobile_no' => '9876543210',
                'is_main_guest' => true,
                'sort_order' => 0,
            ]);
        }

        $ticket = TicketRequest::create([]);
        TicketJourney::create([
            'ticket_id' => $ticket->id,
            'origin' => 'Delhi',
            'destination' => 'Dehradun',
            'ticket_type' => 'Rail',
            'date_of_journey' => now()->addDay()->toDateString(),
            'train_flight_bus_name' => 'Shatabdi',
            'train_flight_bus_no' => '12017',
            'quota' => 'General',
            'book_if_waiting' => false,
            'payment_will_be_done_by' => 'Myself',
            'sort_order' => 0,
        ]);

        $request = ProtocolRequest::create($attributes + [
            'request_number' => ProtocolRequest::generateRequestNumber(),
            'batch_id' => $batch->id,
            'request_type' => 'ticket',
            'requestable_id' => $ticket->id,
            'requestable_type' => TicketRequest::class,
            'user_id' => $owner->getKey(),
            'employee_id' => $employeePk,
            'status' => $status,
            'current_stage' => 'Protocol Staff Review',
        ]);
        $request->logRaised();

        return $request;
    }

    protected function lastLog(ProtocolRequest $request)
    {
        return $request->logs()->reorder()->orderByDesc('id')->first();
    }
}
