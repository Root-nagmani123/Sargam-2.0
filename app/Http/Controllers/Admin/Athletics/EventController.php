<?php

namespace App\Http\Controllers\Admin\Athletics;

use App\Models\Athletics\AthleticsEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EventController extends AthleticsController
{
    public function index()
    {
        return view('admin.athletics.events.index', [
            'events' => AthleticsEvent::orderByDesc('id')->get(),
            // A drive references the event by foreign key, so Delete is refused for these.
            'inUse' => DB::table('athletics_drive_events')->distinct()->pluck('event_id')->flip(),
        ]);
    }

    public function store(Request $request)
    {
        AthleticsEvent::create($this->validated($request) + ['created_by' => Auth::id()]);

        return back()->with('success', 'Event created successfully.');
    }

    public function update(Request $request, AthleticsEvent $event)
    {
        $event->update($this->validated($request));

        return back()->with('success', 'Event updated successfully.');
    }

    public function destroy(AthleticsEvent $event)
    {
        if (DB::table('athletics_drive_events')->where('event_id', $event->id)->exists()) {
            return back()->with('error', 'This event is used in an athletics drive and cannot be deleted. Deactivate it instead.');
        }

        $event->delete();

        return back()->with('success', 'Event deleted successfully.');
    }

    public function export(Request $request, string $format = 'csv')
    {
        return $this->exportGrid($request, $format, AthleticsEvent::orderByDesc('id')->get(), [
            'sno' => $this->snoColumn(),
            'name' => ['heading' => 'Event Name', 'width' => '28%', 'value' => fn ($r) => $r->name],
            'category' => ['heading' => 'Category', 'width' => '18%', 'value' => fn ($r) => AthleticsEvent::CATEGORIES[$r->category] ?? $r->category],
            'gender' => ['heading' => 'Gender', 'width' => '14%', 'value' => fn ($r) => AthleticsEvent::GENDERS[$r->gender] ?? $r->gender],
            'participation' => ['heading' => 'Participation', 'width' => '20%', 'value' => fn ($r) => AthleticsEvent::PARTICIPATION[$r->participation] ?? $r->participation],
            'status' => $this->statusColumn(),
        ], 'Event Master', 'AthleticsEventMaster');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:150',
            'category' => ['required', Rule::in(array_keys(AthleticsEvent::CATEGORIES))],
            'gender' => ['required', Rule::in(array_keys(AthleticsEvent::GENDERS))],
            'participation' => ['required', Rule::in(array_keys(AthleticsEvent::PARTICIPATION))],
            'status' => 'required|in:0,1',
        ]);
    }
}
