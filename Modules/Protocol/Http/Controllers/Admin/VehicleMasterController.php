<?php

namespace Modules\Protocol\Http\Controllers\Admin;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Protocol\Entities\ProtocolRequest;
use Modules\Protocol\Entities\ProtocolDriverMaster;
use Modules\Protocol\Entities\ProtocolVehicleMaster;
use Modules\Protocol\Http\Requests\ReviewProtocolRequest;
use Modules\Protocol\Services\VehicleMasterService;
use App\Models\HostelBuildingMaster;
use App\Models\State;

class VehicleMasterController extends Controller
{
    protected $service;

    public function __construct(VehicleMasterService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->service->getDatatable($request);
        }

        $pageData = $this->service->pageData();
        return view('protocol::admin.vehicles.master.index', compact('pageData'));
    }

    public function create()
    {
        $drivers = ProtocolDriverMaster::select('id', 'name', 'driving_licence_no')->orderBy('name')->get();
        $states  = State::select('pk', 'state_name')->orderBy('state_name')->get();

        return view('protocol::admin.vehicles.master.create', [
            'drivers' => $drivers,
            'states'  => $states,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_name'            => 'required|string|max:255',
            'vehicle_number'          => 'required|string|max:255|unique:protocol_vehicle_masters,vehicle_number',
            'manufacturer'            => 'required|string|max:255',
            'engine_number'           => 'required|string|max:255|unique:protocol_vehicle_masters,engine_number',
            'chassis_number'          => 'required|string|max:255|unique:protocol_vehicle_masters,chassis_number',
            'model'                   => 'required|string|max:255',
            'average_committed'       => 'nullable|numeric|min:0',
            'current_reading'         => 'required|numeric|min:0',
            'fuel_type'               => 'required|string|max:255',
            'vehicle_type'            => 'required|string|max:255',
            'capacity'                => 'required|integer|min:1',
            'status'                  => 'required|boolean',
            'manufacture_year'        => 'nullable|digits:4',
            'maintained_by'           => 'required|string|max:255',
            'registration_number'     => 'required|string|max:255',
            'country_id'              => 'required|integer',
            'state_id'                => 'required|integer',
            'city_id'                 => 'required|integer',
            'driver_id'               => 'required|string|max:255',
            'registration_authority'  => 'required|string|max:255',
            'vehicle_part'            => 'nullable|string|max:255',
            'vehicle_notes'           => 'nullable|string|max:5000',
            'validity_km'             => 'nullable|numeric|min:0',
            'validity_years'          => 'required|integer|min:0',
            'owned_by'                => 'nullable|string|max:255',
            'financed_by'             => 'nullable|string|max:255',
            'total_vehicle_cost'      => 'nullable|numeric|min:0',
            'actual_vehicle_cost'     => 'nullable|numeric|min:0',
            'purchase_type'           => 'required|in:purchase,rent',
            'purchase_date'           => 'nullable|date',
            'purchase_cost'           => 'nullable|numeric|min:0',
            'margin_money'            => 'nullable|numeric|min:0',
            'loan_amount'             => 'nullable|numeric|min:0',
            'emi'                     => 'nullable|numeric|min:0',
            'rate_of_interest'        => 'nullable|numeric|min:0|max:100',
            'first_installment_date'  => 'nullable|date',
            'last_installment_date'   => 'nullable|date',
            'vendor_name'             => 'nullable|string|max:255',
            'purchase_notes'          => 'nullable|string|max:5000',
            'has_insurance'           => 'nullable|boolean',
            'insurer_name'            => 'required_if:has_insurance,1|nullable|string|max:255',
            'insurance_company_name'  => 'required_if:has_insurance,1|nullable|string|max:255',
            'insurance_date'          => 'required_if:has_insurance,1|nullable|date',
            'insurance_expiry_date'   => 'required_if:has_insurance,1|nullable|date',
            'premium_amount'          => 'required_if:has_insurance,1|nullable|numeric|min:0',
            'insurance_notes'         => 'nullable|string|max:5000',
        ]);

        $validated['status']        = $request->boolean('status');
        $validated['has_insurance'] = $request->boolean('has_insurance');

        ProtocolVehicleMaster::create($validated);

        return redirect()
            ->route('protocol.vehicle-master.index')
            ->with('success', 'Vehicle added successfully.');
    }

    public function edit($id)
    {
        $vehicle = ProtocolVehicleMaster::findOrFail($id);
        $drivers = ProtocolDriverMaster::select('id', 'name', 'driving_licence_no')->orderBy('name')->get();

        return view('protocol::admin.vehicles.master.edit', compact('vehicle', 'drivers'));
    }

    public function update(Request $request, $id)
    {
        $vehicle = ProtocolVehicleMaster::findOrFail($id);

        $validated = $request->validate([
            'vehicle_name'            => 'required|string|max:255',
            'vehicle_number'          => ['required', 'string', 'max:255', Rule::unique('protocol_vehicle_masters', 'vehicle_number')->ignore($vehicle->id)],
            'manufacturer'            => 'required|string|max:255',
            'engine_number'           => ['required', 'string', 'max:255', Rule::unique('protocol_vehicle_masters', 'engine_number')->ignore($vehicle->id)],
            'chassis_number'          => ['required', 'string', 'max:255', Rule::unique('protocol_vehicle_masters', 'chassis_number')->ignore($vehicle->id)],
            'model'                   => 'required|string|max:255',
            'average_committed'       => 'nullable|numeric|min:0',
            'current_reading'         => 'required|numeric|min:0',
            'fuel_type'               => 'required|string|max:255',
            'vehicle_type'            => 'required|string|max:255',
            'capacity'                => 'required|integer|min:1',
            'status'                  => 'required|boolean',
            'manufacture_year'        => 'nullable|digits:4',
            'maintained_by'           => 'required|string|max:255',
            'registration_number'     => 'required|string|max:255',
            'country_id'              => 'required|integer',
            'state_id'                => 'required|integer',
            'city_id'                 => 'required|integer',
            'driver_id'               => 'required|string|max:255',
            'registration_authority'  => 'required|string|max:255',
            'vehicle_part'            => 'nullable|string|max:255',
            'vehicle_notes'           => 'nullable|string|max:5000',
            'validity_km'             => 'nullable|numeric|min:0',
            'validity_years'          => 'required|integer|min:0',
            'owned_by'                => 'nullable|string|max:255',
            'financed_by'             => 'nullable|string|max:255',
            'total_vehicle_cost'      => 'nullable|numeric|min:0',
            'actual_vehicle_cost'     => 'nullable|numeric|min:0',
            'purchase_type'           => 'required|in:purchase,rent',
            'purchase_date'           => 'nullable|date',
            'purchase_cost'           => 'nullable|numeric|min:0',
            'margin_money'            => 'nullable|numeric|min:0',
            'loan_amount'             => 'nullable|numeric|min:0',
            'emi'                     => 'nullable|numeric|min:0',
            'rate_of_interest'        => 'nullable|numeric|min:0|max:100',
            'first_installment_date'  => 'nullable|date',
            'last_installment_date'   => 'nullable|date',
            'vendor_name'             => 'nullable|string|max:255',
            'purchase_notes'          => 'nullable|string|max:5000',
            'has_insurance'           => 'nullable|boolean',
            'insurer_name'            => 'required_if:has_insurance,1|nullable|string|max:255',
            'insurance_company_name'  => 'required_if:has_insurance,1|nullable|string|max:255',
            'insurance_date'          => 'required_if:has_insurance,1|nullable|date',
            'insurance_expiry_date'   => 'required_if:has_insurance,1|nullable|date',
            'premium_amount'          => 'required_if:has_insurance,1|nullable|numeric|min:0',
            'insurance_notes'         => 'nullable|string|max:5000',
        ]);

        $validated['status']        = $request->boolean('status');
        $validated['has_insurance'] = $request->boolean('has_insurance');

        $vehicle->update($validated);

        return redirect()
            ->route('protocol.vehicle-master.index')
            ->with('success', 'Vehicle updated successfully.');
    }

    public function destroy($id)
    {
        $vehicle = ProtocolVehicleMaster::findOrFail($id);
        $vehicle->delete();

        if (request()->ajax()) {
            return response()->json(['success' => true, 'message' => 'Vehicle deleted successfully.']);
        }

        return redirect()
            ->route('protocol.vehicle-master.index')
            ->with('success', 'Vehicle deleted successfully.');
    }
}