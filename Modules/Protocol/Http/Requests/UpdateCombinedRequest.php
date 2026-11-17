<?php

namespace Modules\Protocol\Http\Requests; // adjust namespace to match StoreCombinedRequest

use Modules\Protocol\Authorization\ProtocolRequestAccess;
use Modules\Protocol\Entities\ProtocolRequest;

/**
 * Same validation as the store form, plus the row `id`s that the edit form
 * sends. Without these rules, $request->validated() would drop the ids and
 * update() could not tell existing rows from new ones.
 */
class UpdateCombinedRequest extends StoreCombinedRequest
{
    /** Only the employee who raised the request; checked before validation so others get a 403 */
    public function authorize(): bool
    {
        $protocolRequest = $this->route('protocolRequest');

        return $protocolRequest instanceof ProtocolRequest
            && ProtocolRequestAccess::canEdit($this->user(), $protocolRequest);
    }

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'guests.*.id'             => ['nullable', 'integer'],
            'vehicle.legs.*.id'       => ['nullable', 'integer'],
            'ticket.journeys.*.id'    => ['nullable', 'integer'],
        ]);
    }
}