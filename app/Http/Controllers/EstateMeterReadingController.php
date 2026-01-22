<?php

namespace App\Http\Controllers;

use App\Models\EstateMeterReading;
use App\Models\EstatePossession;
use App\Models\EstateElectricSlab;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class EstateMeterReadingController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'estate_possession_pk' => 'required|exists:estate_possession,pk',
            'reading_date' => 'required|date',
            'meter_reading_one' => 'nullable|numeric|min:0',
            'meter_reading_two' => 'nullable|numeric|min:0',
            'remarks' => 'nullable|string',
        ]);

        $validated['created_by'] = Auth::id();
        $validated['created_date'] = now();

        // Calculate units consumed
        $possession = EstatePossession::findOrFail($validated['estate_possession_pk']);
        $lastReading = EstateMeterReading::where('estate_possession_pk', $validated['estate_possession_pk'])
            ->orderBy('reading_date', 'desc')
            ->first();

        if ($lastReading) {
            $validated['units_consumed_one'] = max(0, ($validated['meter_reading_one'] ?? 0) - $lastReading->meter_reading_one);
            $validated['units_consumed_two'] = max(0, ($validated['meter_reading_two'] ?? 0) - $lastReading->meter_reading_two);
        } else {
            $validated['units_consumed_one'] = max(0, ($validated['meter_reading_one'] ?? 0) - ($possession->initial_reading_one ?? 0));
            $validated['units_consumed_two'] = max(0, ($validated['meter_reading_two'] ?? 0) - ($possession->initial_reading_two ?? 0));
        }

        // Calculate electric charges using slab
        $totalUnits = $validated['units_consumed_one'] + $validated['units_consumed_two'];
        $validated['electric_charge'] = $this->calculateElectricCharge($totalUnits);

        EstateMeterReading::create($validated);

        return back()->with('success', 'Meter reading recorded successfully.');
    }

    private function calculateElectricCharge($units)
    {
        $slabs = EstateElectricSlab::where('effective_from', '<=', now())
            ->where(function($query) {
                $query->whereNull('effective_to')
                      ->orWhere('effective_to', '>=', now());
            })
            ->orderBy('units_from')
            ->get();

        $totalCharge = 0;
        $remainingUnits = $units;

        foreach ($slabs as $slab) {
            if ($remainingUnits <= 0) break;

            $slabUnits = min($remainingUnits, $slab->units_to - $slab->units_from + 1);
            $totalCharge += $slabUnits * $slab->rate_per_unit;
            $remainingUnits -= $slabUnits;

            if ($slab->fixed_charge > 0 && $units > $slab->units_from) {
                $totalCharge += $slab->fixed_charge;
            }
        }

        return $totalCharge;
    }

    public function destroy(string $id)
    {
        try {
            $reading = EstateMeterReading::findOrFail($id);
            $reading->delete();
            
            return response()->json(['success' => true, 'message' => 'Meter reading deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error deleting reading: ' . $e->getMessage()], 500);
        }
    }
}
