<?php

namespace App\Http\Controllers\FC;

use App\Http\Controllers\Controller;
use App\Models\FC\{
    StudentRegisterMaster,
    FcJoiningAttendanceGangaMaster,
    FcJoiningAttendanceKaveriMaster,
    FcJoiningAttendanceNarmadaMaster,
    FcJoiningAttendanceMahanadiMaster,
    FcJoiningAttendanceHappyValleyMaster,
    FcJoiningAttendanceSilverwoodMaster,
    FcJoiningMedicalDetailsMaster,
    StudentMasterFirst,
    SessionMaster
};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Matches: FcJoiningAttendance templates + FcJoiningAttendance*MasterRepository pattern
 * Admin/hostel-warden marks attendance per hostel block.
 */
class FcJoiningAttendanceController extends Controller
{
    // Hostel → Model mapping (matches the original hostel-specific table pattern)
    private array $hostelModels = [
        'Ganga'       => FcJoiningAttendanceGangaMaster::class,
        'Kaveri'      => FcJoiningAttendanceKaveriMaster::class,
        'Narmada'     => FcJoiningAttendanceNarmadaMaster::class,
        'Mahanadi'    => FcJoiningAttendanceMahanadiMaster::class,
        'HappyValley' => FcJoiningAttendanceHappyValleyMaster::class,
        'Silverwood'  => FcJoiningAttendanceSilverwoodMaster::class,
    ];

    // ── List all students for a given hostel ─────────────────────────
    public function showHostelList(string $hostel)
    {
        abort_unless(array_key_exists($hostel, $this->hostelModels), 404);

        $session   = SessionMaster::where('is_active', 1)->first();
        $students  = StudentRegisterMaster::where('allotted_hostel', $hostel)
            ->with(['session'])
            ->get();

        $modelClass   = $this->hostelModels[$hostel];
        $attendanceMap = $modelClass::whereIn('username', $students->pluck('username'))
            ->get()->keyBy('username');

        return view('fc.joining.attendance-list', compact(
            'hostel','students','attendanceMap','session'
        ));
    }

    // ── Mark / update attendance for one student ─────────────────────
    public function markAttendance(Request $request, string $hostel)
    {
        abort_unless(array_key_exists($hostel, $this->hostelModels), 404);

        $validated = $request->validate([
            'username'      => 'required|string|max:100',
            'room_no'       => 'nullable|string|max:20',
            'joining_date'  => 'required|date',
            'joining_time'  => 'nullable|date_format:H:i',
            'transport_mode'=> 'nullable|string|max:100',
            'attended'      => 'required|boolean',
            'remarks'       => 'nullable|string|max:500',
        ]);

        $modelClass = $this->hostelModels[$hostel];
        $modelClass::updateOrCreate(
            ['username' => $validated['username']],
            $validated
        );

        return back()->with('success', "Attendance marked for {$validated['username']}.");
    }

    // ── Bulk mark attendance from list ───────────────────────────────
    public function bulkMark(Request $request, string $hostel)
    {
        abort_unless(array_key_exists($hostel, $this->hostelModels), 404);

        $validated = $request->validate([
            'attendances'                   => 'required|array',
            'attendances.*.username'        => 'required|string|max:100',
            'attendances.*.attended'        => 'required|boolean',
            'attendances.*.joining_date'    => 'nullable|date',
            'attendances.*.room_no'         => 'nullable|string|max:20',
            'attendances.*.remarks'         => 'nullable|string|max:500',
        ]);

        $modelClass = $this->hostelModels[$hostel];
        foreach ($validated['attendances'] as $a) {
            $modelClass::updateOrCreate(['username' => $a['username']], $a);
        }

        return back()->with('success', 'Attendance updated for all students.');
    }

    // ── Medical Details (FcJoiningMedicalDetails) ────────────────────
    public function showMedicalForm(string $username)
    {
        $medical = FcJoiningMedicalDetailsMaster::where('username', $username)->first();
        $student = StudentMasterFirst::where('username', $username)->first();
        return view('fc.joining.medical', compact('medical','student','username'));
    }

    public function saveMedicalDetails(Request $request, string $username)
    {
        $validated = $request->validate([
            'height_cm'       => 'nullable|numeric',
            'weight_kg'       => 'nullable|numeric',
            'blood_pressure'  => 'nullable|string|max:20',
            'blood_group'     => 'nullable|string|max:10',
            'is_fit'          => 'required|boolean',
            'medical_remarks' => 'nullable|string|max:1000',
            'examined_by'     => 'nullable|string|max:200',
            'examined_date'   => 'nullable|date',
        ]);
        $validated['username'] = $username;
        FcJoiningMedicalDetailsMaster::updateOrCreate(['username' => $username], $validated);
        return back()->with('success', 'Medical details saved.');
    }
}
