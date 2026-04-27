<?php

namespace App\Http\Controllers\FC;

use App\Http\Controllers\Controller;
use App\Models\FC\FcTravelArrivalSlot;
use Illuminate\Http\Request;

class FcTravelArrivalSlotController extends Controller
{
    public function index()
    {
        $slots = FcTravelArrivalSlot::query()->orderBy('sort_order')->orderBy('id')->get();

        return view('admin.travel.slots.index', compact('slots'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'slot_label'    => 'required|string|max:100',
            'time_start'    => 'nullable|date_format:H:i',
            'time_end'      => 'nullable|date_format:H:i',
            'max_capacity'  => 'nullable|integer|min:0',
            'is_active'     => 'nullable|boolean',
            'sort_order'    => 'nullable|integer|min:0',
        ]);
        if ($request->filled('time_start') && $request->filled('time_end')
            && strtotime($request->time_start) >= strtotime($request->time_end)) {
            return back()->with('error', 'End time must be after start time.')->withInput();
        }

        $data['is_active'] = $request->boolean('is_active', true);
        $data['sort_order'] = $data['sort_order'] ?? 0;
        FcTravelArrivalSlot::create($data);

        return redirect()->route('admin.travel.slots.index')
            ->with('success', 'Slot created.');
    }

    public function update(Request $request, FcTravelArrivalSlot $slot)
    {
        $data = $request->validate([
            'slot_label'    => 'required|string|max:100',
            'time_start'    => 'nullable|date_format:H:i',
            'time_end'      => 'nullable|date_format:H:i',
            'max_capacity'  => 'nullable|integer|min:0',
            'is_active'     => 'nullable|boolean',
            'sort_order'    => 'nullable|integer|min:0',
        ]);

        if ($request->filled('time_start') && $request->filled('time_end')
            && strtotime($request->time_start) >= strtotime($request->time_end)) {
            return back()->with('error', 'End time must be after start time.')->withInput();
        }

        $data['is_active'] = $request->boolean('is_active', true);
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $slot->update($data);

        return redirect()->route('admin.travel.slots.index')
            ->with('success', 'Slot updated.');
    }

    public function destroy(FcTravelArrivalSlot $slot)
    {
        if ($slot->travelPlans()->exists()) {
            return redirect()->route('admin.travel.slots.index')
                ->with('error', 'This slot is assigned to one or more travel plans. Deactivate it instead of deleting.');
        }
        $slot->delete();

        return redirect()->route('admin.travel.slots.index')
            ->with('success', 'Slot deleted.');
    }
}
