<?php

namespace Modules\Protocol\Authorization;
use App\Models\User;

final class ProtocolGate
{
    public function viewRequests(User $user): bool
    {
        return $user->can('protocol.requests.view_all')
            || $user->can('protocol.guest_house_booking')
            || $user->can('protocol.vehicle_booking')
            || $user->can('protocol.ticket_booking');
    }
}