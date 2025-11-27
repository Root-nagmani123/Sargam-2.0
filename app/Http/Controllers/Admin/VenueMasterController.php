<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{VenueMaster};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

class VenueMasterController extends Controller
{
    public function index() {
        $venues = VenueMaster::paginate(10);
        return view('admin.venueMaster.index', compact('venues'));
    }

    public function create() {
        return view('admin.venueMaster.create'); 
    }

    public function store(Request $request) {
        $request->validate([
            'venue_name' => 'required|string|max:255',
            'venue_short_name' => 'required|string|max:100',
            'description' => 'nullable|string',
        ]);
        VenueMaster::create([
            'venue_name' => $request->venue_name,
            'description' => $request->description,
            'venue_short_name' => $request->venue_short_name,
            'created_date' => now(),
        ]);
        return redirect()->route('Venue-Master.index')->with('success', 'Venue Added Successfully');
    }

    public function edit($id) {
        $venue = VenueMaster::findOrFail($id);
        return view('admin.venueMaster.edit', compact('venue'));
    }

    public function update(Request $request, $id) {
        $request->validate([
            'venue_name' => 'required|string|max:255',
            'venue_short_name' => 'required|string|max:100',
            'description' => 'nullable|string',
        ]);
        VenueMaster::where('venue_id', $id)->update([
            'venue_name' => $request->venue_name,
            'description' => $request->description,
            'venue_short_name' => $request->venue_short_name,
            'modified_date' => now(),
        ]);
        return redirect()->route('Venue-Master.index')->with('success', 'Venue Updated Successfully');
    }

    public function destroy($id) {
        VenueMaster::destroy($id);
        return redirect()->route('Venue-Master.index')->with('success', 'Venue Deleted Successfully');
    }
}
