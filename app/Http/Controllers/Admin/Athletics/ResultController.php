<?php

namespace App\Http\Controllers\Admin\Athletics;

use App\Models\Athletics\AthleticsDrive;
use App\Models\Athletics\AthleticsHouse;
use App\Models\Athletics\AthleticsHouseMember;
use App\Models\Athletics\AthleticsResult;
use App\Models\CourseMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Course list → a course's drives → the result sheet of a drive (one block per
 * event). A drive is Complete once every one of its events has a result row;
 * nothing is stored for that, so it can't drift from the sheet.
 */
class ResultController extends AthleticsController
{
    public function index(Request $request)
    {
        $tab = $request->query('tab') === 'archived' ? 'archived' : 'active';
        $courses = $this->courses($tab);

        return view('admin.athletics.results.index', compact('courses', 'tab'));
    }

    public function course(CourseMaster $course)
    {
        $drives = $this->drives()->where('course_master_pk', $course->pk)->get();
        abort_if($drives->isEmpty(), 404);

        $drives->load(['events', 'results']);
        $houses = AthleticsHouse::active()->orderBy('name')->get(['id', 'name']);
        $houseMap = AthleticsHouseMember::houseMapForCourse((int) $course->pk);

        return view('admin.athletics.results.course', [
            'course' => $course,
            'drives' => $drives,
            'houses' => $houses,
            'athletes' => $drives->mapWithKeys(fn ($d) => [$d->id => $this->athletes($d, $houseMap)]),
        ]);
    }

    /** Replaces the drive's whole result sheet with what was submitted. */
    public function save(Request $request, AthleticsDrive $drive)
    {
        $request->validate([
            'results' => 'nullable|array',
            'results.*' => 'array',
            'results.*.*.position' => 'nullable|integer|min:1|max:999', // required below, once a row is not blank
            'results.*.*.student_master_pk' => 'nullable|integer',
            'results.*.*.house_id' => 'nullable|integer',
            'results.*.*.performance' => 'nullable|string|max:30',
            'results.*.*.points' => 'nullable|numeric|min:0|max:9999',
            'results.*.*.remarks' => 'nullable|string|max:255',
        ]);

        $eventIds = $drive->events()->pluck('athletics_events.id')->map(fn ($id) => (int) $id)->flip();
        $athletes = $this->athletes($drive, AthleticsHouseMember::houseMapForCourse((int) $drive->course_master_pk));
        $houseIds = AthleticsHouse::pluck('id')->map(fn ($id) => (int) $id)->flip();
        $rows = [];

        foreach ($request->input('results', []) as $eventId => $list) {
            $eventId = (int) $eventId;
            if (! $eventIds->has($eventId)) {
                continue;
            }

            foreach ($list as $r) {
                $student = (int) ($r['student_master_pk'] ?? 0) ?: null;
                $house = (int) ($r['house_id'] ?? 0) ?: null;

                if (! $student && ! $house) {
                    continue; // an untouched blank row
                }
                if (empty($r['position'])) {
                    return back()->with('error', 'Every result line needs a position.');
                }

                if ($student) {
                    $athlete = $athletes->get($eventId, collect())->firstWhere('pk', $student);
                    if (! $athlete) {
                        return back()->with('error', 'An athlete on the sheet has no approved nomination for that event.');
                    }
                    // The athlete's own house wins over whatever the row's House said.
                    $house = $athlete['house_id'] ?? $house;
                }

                $rows[] = [
                    'drive_id' => $drive->id,
                    'event_id' => $eventId,
                    'position' => (int) $r['position'],
                    'student_master_pk' => $student,
                    'house_id' => $house && $houseIds->has($house) ? $house : null,
                    'performance' => $drive->result_recorded_as === 'rank' ? null : (filled($r['performance'] ?? null) ? trim($r['performance']) : null),
                    'points' => (float) ($r['points'] ?? 0),
                    'remarks' => filled($r['remarks'] ?? null) ? trim($r['remarks']) : null,
                    'created_by' => Auth::id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        DB::transaction(function () use ($drive, $rows) {
            $drive->results()->delete();
            AthleticsResult::insert($rows);
        });

        return redirect()
            ->route('admin.athletics.results.course', ['course' => $drive->course_master_pk, 'open' => $drive->id])
            ->with('success', 'Results saved for '.$drive->form_title.'.');
    }

    public function export(Request $request, string $format = 'csv')
    {
        $tab = $request->query('tab') === 'archived' ? 'archived' : 'active';

        return $this->exportGrid($request, $format, $this->courses($tab), [
            'sno' => $this->snoColumn(),
            'course' => ['heading' => 'Course', 'width' => '40%', 'value' => fn ($c) => static::courseLabel($c)],
            'drives' => ['heading' => 'Drives', 'width' => '20%', 'align' => 'center', 'value' => fn ($c) => $c->drive_count],
            'status' => ['heading' => 'Result Entry Status', 'width' => '32%', 'align' => 'center', 'value' => fn ($c) => $c->complete ? 'Complete' : 'Pending'],
        ], 'Result Entry', 'AthleticsResultEntry');
    }

    /** Active drives with the counts that decide Complete / Pending. */
    private function drives()
    {
        return AthleticsDrive::where('status', 'active')
            ->withCount('events')
            ->withCount(['results as resulted_events' => fn ($q) => $q->select(DB::raw('count(distinct event_id)'))])
            ->orderBy('start_date');
    }

    /** Courses (of the tab's lifecycle) that have an active drive, each with drive_count and complete. */
    private function courses(string $tab): Collection
    {
        $byCourse = $this->drives()->get()->groupBy('course_master_pk');

        return CourseMaster::query()
            ->when($tab === 'archived', fn ($q) => $q->archived(), fn ($q) => $q->activeRunning())
            ->whereIn('pk', $byCourse->keys())
            ->orderBy('course_name')
            ->get(['pk', 'course_name', 'couse_short_name'])
            ->each(function ($course) use ($byCourse) {
                $drives = $byCourse->get($course->pk, collect());
                $course->drive_count = $drives->count();
                $course->complete = $drives->every(fn ($d) => static::isComplete($d));
            });
    }

    public static function isComplete(AthleticsDrive $drive): bool
    {
        return $drive->events_count > 0 && $drive->resulted_events >= $drive->events_count;
    }

    /**
     * event_id => approved nominees for it: [pk, label, house_id].
     *
     * @param  Collection  $houseMap  student_master_pk => house
     */
    private function athletes(AthleticsDrive $drive, Collection $houseMap): Collection
    {
        $byEvent = collect();

        $drive->nominations()->where('status', 'approved')
            ->with(['events:athletics_events.id', 'student:pk,display_name,generated_OT_code'])
            ->get()
            ->each(function ($n) use ($byEvent, $houseMap) {
                foreach ($n->events as $event) {
                    $byEvent->put($event->id, $byEvent->get($event->id, collect())->push([
                        'pk' => (int) $n->student_master_pk,
                        'label' => static::otLabel($n->student),
                        'house_id' => $houseMap->get((int) $n->student_master_pk)?->id,
                    ]));
                }
            });

        return $byEvent;
    }
}
