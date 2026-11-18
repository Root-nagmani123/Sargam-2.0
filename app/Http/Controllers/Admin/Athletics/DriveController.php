<?php

namespace App\Http\Controllers\Admin\Athletics;

use App\Models\Athletics\AthleticsDrive;
use App\Models\Athletics\AthleticsEvent;
use App\Models\Athletics\AthleticsVenue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DriveController extends AthleticsController
{
    public function index()
    {
        $drives = $this->drives();

        return view('admin.athletics.drives.index', [
            'drives' => $drives,
            'courses' => $this->courseOptions(),
            'events' => AthleticsEvent::active()->orderBy('name')->get(['id', 'name', 'category', 'gender']),
            'venues' => AthleticsVenue::active()->orderBy('name')->get(['id', 'name', 'venue_type']),
        ]);
    }

    public function store(Request $request)
    {
        DB::transaction(function () use ($request) {
            $drive = AthleticsDrive::create($this->validated($request) + ['created_by' => Auth::id()]);
            $drive->events()->sync($this->eventRows($request));
        });

        return back()->with('success', 'Athletics drive created successfully.');
    }

    /** Only a Draft drive is editable — once Active, OTs may already be nominating against it. */
    public function update(Request $request, AthleticsDrive $drive)
    {
        if ($drive->status !== 'draft') {
            return back()->with('error', 'Only a Draft drive can be edited.');
        }

        DB::transaction(function () use ($request, $drive) {
            $drive->update($this->validated($request));
            $drive->events()->sync($this->eventRows($request));
        });

        return back()->with('success', 'Athletics drive updated successfully.');
    }

    public function destroy(AthleticsDrive $drive)
    {
        if ($drive->nominations()->exists() || $drive->results()->exists()) {
            return back()->with('error', 'This drive already has nominations or results and cannot be deleted.');
        }

        $drive->delete();

        return back()->with('success', 'Athletics drive deleted successfully.');
    }

    /** The nomination form exactly as an OT will see it, read-only. */
    public function preview(AthleticsDrive $drive)
    {
        $drive->load(['course', 'events']);

        return view('admin.athletics.my_nominations.form', [
            'drive' => $drive,
            'student' => null,
            'house' => null,
            'nomination' => null,
            'picked' => collect(),
            'preview' => true,
        ]);
    }

    public function export(Request $request, string $format = 'csv')
    {
        return $this->exportGrid($request, $format, $this->drives(), [
            'sno' => $this->snoColumn(),
            'course' => ['heading' => 'Course', 'width' => '11%', 'value' => fn ($d) => static::courseLabel($d->course)],
            'date' => ['heading' => 'Date', 'width' => '15%', 'value' => fn ($d) => $d->start_date->format('d/m/Y').' - '.$d->end_date->format('d/m/Y')],
            'form_title' => ['heading' => 'Form Title', 'width' => '20%', 'value' => fn ($d) => $d->form_title],
            'visible_to' => ['heading' => 'Visible To', 'width' => '9%', 'value' => fn ($d) => AthleticsDrive::VISIBLE_TO[$d->visible_to] ?? $d->visible_to],
            'meet' => ['heading' => 'Athletics Meet', 'width' => '17%', 'value' => fn ($d) => $d->categoryList()],
            'result' => ['heading' => 'Result Record As', 'width' => '11%', 'value' => fn ($d) => AthleticsDrive::RESULT_TYPES[$d->result_recorded_as] ?? $d->result_recorded_as],
            'status' => ['heading' => 'Status', 'width' => '9%', 'align' => 'center', 'value' => fn ($d) => AthleticsDrive::STATUSES[$d->status] ?? $d->status],
        ], 'Athletics Drive', 'AthleticsDrive', 'landscape');
    }

    private function drives()
    {
        return AthleticsDrive::with(['course:pk,course_name,couse_short_name', 'events'])
            ->withCount(['nominations', 'results'])
            ->orderByDesc('id')
            ->get();
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'course_master_pk' => ['required', 'integer', Rule::exists('course_master', 'pk')],
            'form_title' => 'required|string|max:200',
            'result_recorded_as' => ['required', Rule::in(array_keys(AthleticsDrive::RESULT_TYPES))],
            'visible_to' => ['required', Rule::in(array_keys(AthleticsDrive::VISIBLE_TO))],
            'max_criteria' => ['required', Rule::in(array_keys(AthleticsDrive::CRITERIA))],
            'max_nominations' => 'required|integer|min:1|max:10',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'instructions' => 'nullable|string|max:2000',
            'status' => ['required', Rule::in(array_keys(AthleticsDrive::STATUSES))],
        ]);
    }

    /**
     * The "Athletics Meet" rows (type · activities · venue) flattened to
     * event_id => ['venue_id' => …] for sync(). Each event must suit the drive's
     * audience and the venue must be of the event's type; an event picked on two
     * rows keeps the later venue.
     */
    private function eventRows(Request $request): array
    {
        $request->validate([
            'rows' => 'required|array|min:1',
            'rows.*.event_ids' => 'required|array|min:1',
            'rows.*.event_ids.*' => 'integer',
            'rows.*.venue_id' => 'required|integer',
        ], ['rows.required' => 'Add at least one athletics meet row.']);

        $visibleTo = $request->input('visible_to');
        $events = AthleticsEvent::active()->get()->keyBy('id');
        $venues = AthleticsVenue::active()->get()->keyBy('id');
        $sync = [];

        foreach ($request->input('rows') as $row) {
            $venue = $venues->get((int) $row['venue_id']);
            foreach ($row['event_ids'] as $eventId) {
                $event = $events->get((int) $eventId);
                if (! $event || ! $venue) {
                    throw ValidationException::withMessages(['rows' => 'A picked event or venue is no longer active.']);
                }
                if ($visibleTo !== 'both' && $event->gender !== $visibleTo) {
                    throw ValidationException::withMessages(['rows' => "{$event->name} is not open to the drive's audience."]);
                }
                if ($venue->venue_type !== $event->category) {
                    throw ValidationException::withMessages(['rows' => "{$venue->name} is not a venue for {$event->name}."]);
                }
                $sync[$event->id] = ['venue_id' => $venue->id];
            }
        }

        return $sync;
    }
}
