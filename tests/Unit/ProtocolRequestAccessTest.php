<?php

namespace Tests\Unit;

use App\Models\User;
use Modules\Protocol\Authorization\ProtocolRequestAccess;
use Modules\Protocol\Entities\ProtocolRequest;
use Modules\Protocol\Entities\VehiclePassRequest;
use Tests\TestCase;

/**
 * show/edit/update take a route-bound ProtocolRequest; the route middleware only
 * says the user may use the screen. Ownership is decided here (PR #348 F-003).
 * These cases need no database: the owner and approver branches return before
 * the staff-scope query.
 */
class ProtocolRequestAccessTest extends TestCase
{
    private function user(int $pk): User
    {
        $user = new User();
        $user->setAttribute($user->getKeyName(), $pk);

        return $user;
    }

    private function request(array $attributes): ProtocolRequest
    {
        return (new ProtocolRequest())->forceFill($attributes);
    }

    public function test_the_employee_who_raised_it_may_view_and_edit(): void
    {
        $request = $this->request(['user_id' => 41]);

        $this->assertTrue(ProtocolRequestAccess::canView($this->user(41), $request));
        $this->assertTrue(ProtocolRequestAccess::canEdit($this->user(41), $request));
    }

    public function test_another_employee_may_not_edit(): void
    {
        $request = $this->request(['user_id' => 41]);

        $this->assertFalse(ProtocolRequestAccess::canEdit($this->user(42), $request));
    }

    /** F-015: the form stores the chosen vehicle-master id in vehicle_type */
    public function test_a_vehicle_pass_resolves_its_vehicle_through_vehicle_type(): void
    {
        $relation = (new VehiclePassRequest())->vehicle();

        $this->assertSame('vehicle_type', $relation->getForeignKeyName());
        $this->assertSame('id', $relation->getOwnerKeyName());
    }

    public function test_the_approver_it_was_recommended_to_may_view_but_not_edit(): void
    {
        $request = $this->request(['user_id' => 41, 'recommended_to_id' => 77]);

        $this->assertTrue(ProtocolRequestAccess::canView($this->user(77), $request));
        $this->assertFalse(ProtocolRequestAccess::canEdit($this->user(77), $request));
    }
}
