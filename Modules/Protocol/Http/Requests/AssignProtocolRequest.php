<?php

namespace Modules\Protocol\Http\Requests;

use App\Models\HostelBuildingMaster;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Protocol\Entities\ProtocolRequest as ProtocolRequestEntity;

class AssignProtocolRequest extends FormRequest
{
    public function authorize(): bool
    {
        $protocolRequest = $this->route('protocolRequest');

        return $this->user()?->hasRole(config('protocol.roles.protocol_staff')) === true
            && $protocolRequest instanceof ProtocolRequestEntity
            && $protocolRequest->status === ProtocolRequestEntity::STATUS_APPROVED
            && in_array($protocolRequest->request_type, ['vehicle', 'guesthouse'], true);
    }

    public function rules(): array
    {
        $protocolRequest = $this->route('protocolRequest');

        if ($protocolRequest->request_type === 'vehicle') {
            return [
                'vehicle_no' => ['required', 'string', 'max:100'],
                'vehicle_name' => ['required', 'string', 'max:150'],
                'driver_name' => ['required', 'string', 'max:150'],
                'driver_number' => ['required', 'string', 'max:30'],
            ];
        }

        $building = new HostelBuildingMaster();

        return [
            'assigned_guest_house' => [
                'required',
                'integer',
                Rule::exists($building->getTable(), $building->getKeyName()),
            ],
        ];
    }
}
