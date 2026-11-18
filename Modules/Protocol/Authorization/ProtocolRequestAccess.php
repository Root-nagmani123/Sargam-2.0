<?php

namespace Modules\Protocol\Authorization;

use App\Models\User;
use Modules\Protocol\Entities\ProtocolRequest;
use Modules\Protocol\Services\ApprovalService;

/**
 * Who may open or change a single ProtocolRequest reached by route-model binding.
 * The route middleware only says the user may use the screen, not that this row is theirs.
 */
final class ProtocolRequestAccess
{
    /** Raised by this user (protocol_requests.user_id is a user_credentials pk, as forUser() uses) */
    public static function owns(User $user, ProtocolRequest $protocolRequest): bool
    {
        return (int) $protocolRequest->user_id === (int) $user->getKey();
    }

    /**
     * The shared read view: the employee who raised it, the approver it was recommended to,
     * or Protocol Staff whose All Requests scope includes it (the dashboard links them here).
     */
    public static function canView(User $user, ProtocolRequest $protocolRequest): bool
    {
        if (self::owns($user, $protocolRequest)) {
            return true;
        }

        if ($protocolRequest->recommended_to_id && (int) $protocolRequest->recommended_to_id === (int) $user->getKey()) {
            return true;
        }

        return $user->can('protocol.protocol_requests')
            && app(ApprovalService::class)->scopedQuery()->whereKey($protocolRequest->getKey())->exists();
    }

    /** Edit / update: only the employee who raised it */
    public static function canEdit(User $user, ProtocolRequest $protocolRequest): bool
    {
        return self::owns($user, $protocolRequest);
    }
}
