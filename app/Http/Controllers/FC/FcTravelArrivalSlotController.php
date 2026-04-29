<?php

namespace App\Http\Controllers\FC;

use App\Http\Controllers\Controller;
use App\Models\FC\FcTravelArrivalSlot;
use Illuminate\Http\Request;

class FcTravelArrivalSlotController extends Controller
{
    public function index()
    {
        $slots = FcTravelArrivalSlot::query()
            ->orderBy('slot_date')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('admin.travel.slots.index', compact('slots'));
    }

    public function store(Request $request)
    {
        // Supports both single-slot submit and multi-row bulk submit.
        if ($request->has('slots')) {
            $validated = $request->validate([
                'slots'                    => 'required|array|min:1',
                'slots.*.slot_date'        => 'required|date',
                'slots.*.slot_label'       => 'required|string|max:100',
                'slots.*.time_start'       => 'nullable|date_format:H:i',
                'slots.*.time_end'         => 'nullable|date_format:H:i',
                'slots.*.max_capacity'     => 'nullable|integer|min:0',
                'slots.*.is_active'        => 'nullable|boolean',
                'slots.*.sort_order'       => 'nullable|integer|min:0',
            ]);

            $created = 0;
            foreach ($validated['slots'] as $idx => $row) {
                if (
                    !empty($row['time_start']) && !empty($row['time_end']) &&
                    strtotime($row['time_start']) >= strtotime($row['time_end'])
                ) {
                    return back()->with('error', 'End time must be after start time in row '.($idx + 1).'.')->withInput();
                }

                FcTravelArrivalSlot::create([
                    'slot_date'    => $row['slot_date'],
                    'slot_label'   => $row['slot_label'],
                    'time_start'   => $row['time_start'] ?? null,
                    'time_end'     => $row['time_end'] ?? null,
                    'max_capacity' => $row['max_capacity'] ?? null,
                    'is_active'    => isset($row['is_active']) ? (bool) $row['is_active'] : true,
                    'sort_order'   => $row['sort_order'] ?? 0,
                ]);
                $created++;
            }

            return redirect()->route('admin.travel.slots.index')
                ->with('success', $created.' slot(s) created.');
        }

        $data = $request->validate([
            'slot_date'     => 'required|date',
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
            'slot_date'     => 'required|date',
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
