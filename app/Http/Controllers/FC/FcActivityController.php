<?php

namespace App\Http\Controllers\FC;

use App\Http\Controllers\Controller;
use App\Services\FC\FcActivityService;
use App\Services\FC\FcPostArrivalAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FcActivityController extends Controller
{
    public function __construct(
        private FcActivityService $svc,
        private FcPostArrivalAccessService $access
    ) {
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'otcode' => 'required|string',
            'ccode' => 'required|string',
            'uactivity' => 'required|string',
            'actvalue' => 'required|string|max:500',
            'course_master_pk' => 'nullable|integer|min:0',
        ]);

        $result = $this->svc->store(
            $request->only(['otcode', 'ccode', 'uactivity', 'actvalue', 'course_master_pk']),
            Auth::user()->user_name ?? ''
        );

        return response()->json(['status' => $result]);
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
}
