<?php

namespace Modules\Protocol\Http\Requests; // adjust namespace to match StoreCombinedRequest

/**
 * Same validation as the store form, plus the row `id`s that the edit form
 * sends. Without these rules, $request->validated() would drop the ids and
 * update() could not tell existing rows from new ones.
 */
class UpdateCombinedRequest extends StoreCombinedRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'guests.*.id'             => ['nullable', 'integer'],
            'vehicle.legs.*.id'       => ['nullable', 'integer'],
            'ticket.journeys.*.id'    => ['nullable', 'integer'],
        ]);
    }
}