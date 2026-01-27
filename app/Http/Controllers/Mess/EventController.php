<?php
namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use App\Models\Mess\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index() {
        $events = Event::all();
        return view('mess.events.index', compact('events'));
    }
    public function create() {
        return view('mess.events.create');
    }
    public function store(Request $request) {
        $data = $request->validate([
            'name' => 'required',
            'description' => 'nullable',
            'event_date' => 'required|date',
        ]);
        Event::create($data);
        return redirect()->route('admin.mess.events.index')->with('success', 'Event created successfully.');
    }
}
