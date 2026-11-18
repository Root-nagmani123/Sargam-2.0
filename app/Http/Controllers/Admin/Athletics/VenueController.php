<?php

namespace App\Http\Controllers\Admin\Athletics;

use App\Models\Athletics\AthleticsVenue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class VenueController extends AthleticsController
{
    public function index()
    {
        return view('admin.athletics.venues.index', [
            'venues' => AthleticsVenue::orderByDesc('id')->get(),
            'inUse' => DB::table('athletics_drive_events')->distinct()->pluck('venue_id')->flip(),
        ]);
    }

    public function store(Request $request)
    {
        AthleticsVenue::create($this->validated($request) + ['created_by' => Auth::id()]);

        return back()->with('success', 'Venue created successfully.');
    }

    public function update(Request $request, AthleticsVenue $venue)
    {
        $venue->update($this->validated($request));

        return back()->with('success', 'Venue updated successfully.');
    }

    public function destroy(AthleticsVenue $venue)
    {
        if (DB::table('athletics_drive_events')->where('venue_id', $venue->id)->exists()) {
            return back()->with('error', 'This venue is used in an athletics drive and cannot be deleted. Deactivate it instead.');
        }

        $venue->delete();

        return back()->with('success', 'Venue deleted successfully.');
    }

    public function export(Request $request, string $format = 'csv')
    {
        return $this->exportGrid($request, $format, AthleticsVenue::orderByDesc('id')->get(), [
            'sno' => $this->snoColumn(),
            'name' => ['heading' => 'Venue Name', 'width' => '45%', 'value' => fn ($r) => $r->name],
            'venue_type' => ['heading' => 'Venue Type', 'width' => '30%', 'value' => fn ($r) => AthleticsVenue::TYPES[$r->venue_type] ?? $r->venue_type],
            'status' => $this->statusColumn(),
        ], 'Venue Master', 'AthleticsVenueMaster');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:150',
            'venue_type' => ['required', Rule::in(array_keys(AthleticsVenue::TYPES))],
            'status' => 'required|in:0,1',
        ]);
    }
}
