<?php

namespace App\Http\Controllers\FC;

use App\Http\Controllers\Controller;
use App\Models\FC\FcActivityMaster;
use App\Models\FC\FcOtActivity;
use App\Models\FC\FcOtDetail;
use App\Models\FC\SessionMaster;
use App\Services\FC\FcActivityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class FcActivityController extends Controller
{
    public function __construct(private FcActivityService $svc)
    {
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'otcode' => 'required|string',
            'ccode' => 'required|string',
            'uactivity' => 'required|string',
            'actvalue' => 'required|string|max:500',
        ]);

        $result = $this->svc->store($request->only(['otcode', 'ccode', 'uactivity', 'actvalue']), Auth::user()->username);
        return response()->json(['status' => $result]);
    }

    public function storeMedicalBulk(Request $request): JsonResponse
    {
        $data = $request->validate([
            'otcode' => 'required|string',
            'ccode' => 'required|string',
            'height' => 'nullable|string|max:50',
            'weight' => 'nullable|string|max:50',
            'spo2' => 'nullable|string|max:50',
            'pulse' => 'nullable|string|max:50',
            'bp' => 'nullable|string|max:50',
            'preremarks' => 'nullable|string|max:1000',
            'vialtube' => 'nullable|string|max:100',
            'bloodsample' => 'nullable|string|max:100',
        ]);

        $ot = FcOtDetail::where('otcode', $data['otcode'])->first();
        if (! $ot) {
            return response()->json(['status' => 'error', 'message' => 'Invalid OT code.'], 422);
        }

        $staffUsername = Auth::user()->username ?? '';
        $activityDate = now()->format('d-m-Y/ h:i:s');

        $map = [
            'height' => $data['height'] ?? null,
            'weight' => $data['weight'] ?? null,
            'spo2' => $data['spo2'] ?? null,
            'pulse' => $data['pulse'] ?? null,
            'bp' => $data['bp'] ?? null,
            'preremarks' => $data['preremarks'] ?? null,
            'vialtube' => $data['vialtube'] ?? null,
            'bloodsample' => $data['bloodsample'] ?? null,
        ];

        foreach ($map as $activityCode => $value) {
            $val = trim((string) ($value ?? ''));
            if ($val === '') {
                continue;
            }

            FcOtActivity::updateOrCreate(
                ['username' => $ot->username, 'activity' => $activityCode],
                [
                    'activityid' => ($data['ccode'] . 'Act' . strtoupper(\Illuminate\Support\Str::random(8))),
                    'activityval' => $val,
                    'activitydt' => $activityDate,
                    'submitedby' => $staffUsername,
                    'course' => $data['ccode'],
                    'status' => 1,
                ]
            );
        }

        return response()->json(['status' => 'ok']);
    }

    public function edit(string $activityId): View
    {
        $record = FcOtActivity::where('activityid', $activityId)->firstOrFail();
        $courses = SessionMaster::query()
            ->where('is_active', 1)
            ->selectRaw('session_name as c_code, session_name as c_name')
            ->get();
        $ots = FcOtDetail::active()->byCourse($record->course)->select('username', 'otname', 'otcode')->get();
        $house = FcOtDetail::where('username', $record->username)->value('house');
        $activities = FcActivityMaster::active()->forCourse($record->course)->get();

        return view('admin.fc-activities.home.edit', compact('record', 'courses', 'ots', 'house', 'activities'));
    }

    public function update(Request $request, string $activityId): JsonResponse
    {
        $request->validate([
            'uactivity' => 'required|string',
            'actvalue' => 'required|string|max:500',
        ]);

        $ok = $this->svc->update($activityId, $request->uactivity, $request->actvalue);
        return response()->json(['status' => $ok ? 'ok' : 'error']);
    }

    public function destroy(string $activityId): RedirectResponse
    {
        $this->svc->delete($activityId);
        return redirect()->route('fc-reg.admin.activities.index')->with('success', 'Record deleted.');
    }

    public function ajaxOtsForEdit(Request $request): JsonResponse
    {
        $course = $request->query('course', '');
        $selectedOt = $request->query('ot', '');

        $ots = FcOtDetail::active()
            ->byCourse($course)
            ->select('username', 'otname', 'otcode')
            ->orderBy('otname')
            ->get()
            ->map(fn ($o) => array_merge($o->toArray(), ['selected' => $o->username === $selectedOt]));

        return response()->json($ots);
    }
}
