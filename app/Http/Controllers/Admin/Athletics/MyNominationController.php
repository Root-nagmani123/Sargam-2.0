<?php

namespace App\Http\Controllers\Admin\Athletics;

use App\Models\Athletics\AthleticsDrive;
use App\Models\Athletics\AthleticsHouseMember;
use App\Models\Athletics\AthleticsNomination;
use App\Models\StudentMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** The OT's own side: the drives open to them and their nomination form. */
class MyNominationController extends AthleticsController
{
    public function index()
    {
        $student = $this->student();
        $gender = AthleticsDrive::normaliseGender($student->gender);

        $drives = AthleticsDrive::with('course:pk,course_name,couse_short_name')
            ->where('status', 'active')
            ->whereIn('course_master_pk', $this->enrolledCourses($student->pk))
            ->orderByDesc('start_date')
            ->get()
            ->filter(fn (AthleticsDrive $d) => $d->isVisibleTo($gender))
            ->values();

        $mine = AthleticsNomination::where('student_master_pk', $student->pk)
            ->whereIn('drive_id', $drives->pluck('id'))->get()->keyBy('drive_id');

        return view('admin.athletics.my_nominations.index', compact('drives', 'mine'));
    }

    public function show(AthleticsDrive $drive)
    {
        [$student, $house] = $this->guard($drive);
        $nomination = AthleticsNomination::with('events:id')->where('drive_id', $drive->id)
            ->where('student_master_pk', $student->pk)->first();

        return view('admin.athletics.my_nominations.form', [
            'drive' => $drive->load(['course', 'events']),
            'student' => $student,
            'house' => $house,
            'nomination' => $nomination,
            'picked' => $nomination ? $nomination->events->pluck('id') : collect(),
            'preview' => false,
        ]);
    }

    public function store(Request $request, AthleticsDrive $drive)
    {
        [$student] = $this->guard($drive);

        if (! $drive->isOpen()) {
            return back()->with('error', 'Nominations for this drive are closed.');
        }

        $existing = AthleticsNomination::where('drive_id', $drive->id)->where('student_master_pk', $student->pk)->first();
        if ($existing && $existing->status !== 'pending') {
            return back()->with('error', 'Your nomination has already been '.$existing->status.' and can no longer be changed.');
        }

        $data = $request->validate([
            'event_ids' => 'required|array|min:1',
            'event_ids.*' => 'integer',
            'declaration' => 'accepted',
        ], [
            'event_ids.required' => 'Select at least one event.',
            'declaration.accepted' => 'Confirm the declaration to submit.',
        ]);

        $events = $drive->events()->whereIn('athletics_events.id', $data['event_ids'])->get();
        if ($events->count() !== count(array_unique($data['event_ids']))) {
            return back()->withInput()->with('error', 'One of the picked events is not part of this drive.');
        }
        if ($error = $drive->limitError($events)) {
            return back()->withInput()->with('error', $error);
        }

        DB::transaction(function () use ($drive, $student, $events) {
            $nomination = AthleticsNomination::updateOrCreate(
                ['drive_id' => $drive->id, 'student_master_pk' => $student->pk],
                ['status' => 'pending', 'created_by' => auth()->id()]
            );
            $nomination->events()->sync($events->pluck('id'));
        });

        return redirect()->route('athletics.my-nominations.show', $drive)
            ->with('success', 'Your nomination has been submitted for approval.');
    }

    private function student(): StudentMaster
    {
        $pk = auth_student_master_pk();
        abort_unless($pk, 403, 'Only officer trainees can nominate for the athletics meet.');

        return StudentMaster::findOrFail($pk);
    }

    private function enrolledCourses($studentPk)
    {
        return DB::table('student_master_course__map')
            ->where('student_master_pk', $studentPk)->where('active_inactive', 1)
            ->pluck('course_master_pk');
    }

    /** The drive must be Active, for a course the OT is in, and open to their gender. */
    private function guard(AthleticsDrive $drive): array
    {
        $student = $this->student();

        abort_unless(
            $drive->status === 'active'
                && $this->enrolledCourses($student->pk)->contains($drive->course_master_pk)
                && $drive->isVisibleTo(AthleticsDrive::normaliseGender($student->gender)),
            404
        );

        $house = AthleticsHouseMember::with('house')
            ->where('course_master_pk', $drive->course_master_pk)
            ->where('student_master_pk', $student->pk)
            ->first()?->house;

        return [$student, $house];
    }
}
