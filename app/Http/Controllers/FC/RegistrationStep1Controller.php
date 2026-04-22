<?php

namespace App\Http\Controllers\FC;

use App\Http\Controllers\Controller;
use App\Models\FC\{StudentMasterFirst, StudentMaster, SessionMaster, ServiceMaster, StateMaster};
use App\Services\FC\RegistrationService;
use App\Services\FC\DynamicFormService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RegistrationStep1Controller extends Controller
{
    public function __construct(
        private RegistrationService $regService,
        private DynamicFormService $formService,
    ) {}

    // ── Dashboard (home after login) ─────────────────────────────────
    public function dashboard()
    {
        $username = Auth::user()->username;
        $progress = $this->regService->getProgress($username);
        $step1    = StudentMasterFirst::where('username', $username)->first();

        return view('fc.registration.dashboard', compact('progress', 'step1'));
    }

    // ── SHOW Step 1 form ─────────────────────────────────────────────
    public function showStep1()
    {
        $username     = Auth::user()->username;
        $step         = $this->formService->getStep('step1');
        $fields       = $this->formService->getStepFields('step1');
        $lookups      = $this->formService->getLookupData($fields);
        $existingData = $this->formService->getExistingData('step1', $username);

        // Fallback: if no dynamic fields configured, use original view
        if ($fields->isEmpty()) {
            $step1    = StudentMasterFirst::where('username', $username)->first();
            $sessions = SessionMaster::where('is_active', 1)->get();
            $services = ServiceMaster::all();
            $states   = StateMaster::orderBy('state_name')->get();
            return view('fc.registration.step1', compact('step1', 'sessions', 'services', 'states'));
        }

        return view('fc.registration.dynamic-step', [
            'step'         => $step,
            'fields'       => $fields,
            'lookups'      => $lookups,
            'existingData' => $existingData,
            'saveUrl'      => route('fc-reg.registration.step1.save'),
            'prevUrl'      => null,
        ]);
    }

    // ── SAVE Step 1 ──────────────────────────────────────────────────
    public function saveStep1(Request $request)
    {
        $username = Auth::user()->username;
        $fields   = $this->formService->getStepFields('step1');

        if ($fields->isEmpty()) {
            // Fallback to original hardcoded save
            return $this->saveStep1Legacy($request, $username);
        }

        $rules     = $this->formService->buildValidationRules($fields);
        $validated = $request->validate($rules);

        $this->formService->saveStepData('step1', $username, $validated, $request);

        return redirect()->route('fc-reg.registration.step2')
            ->with('success', 'Step 1 saved successfully. Please complete Step 2.');
    }

    private function saveStep1Legacy(Request $request, string $username)
    {
        $validated = $request->validate([
            'full_name'         => 'required|string|max:200',
            'fathers_name'      => 'required|string|max:200',
            'mothers_name'      => 'required|string|max:200',
            'date_of_birth'     => 'required|date|before:today',
            'gender'            => 'required|in:Male,Female,Other',
            'service_id'        => 'required|exists:service_master,pk',
            'cadre'             => 'required|string|max:100',
            'allotted_state_id' => 'required|exists:state_master,pk',
            'mobile_no'         => 'required|digits:10',
            'email'             => 'required|email|max:150',
            'session_id'        => 'required|exists:session_masters,id',
            'photo'             => 'nullable|image|mimes:jpeg,jpg,png|max:500',
            'signature'         => 'nullable|image|mimes:jpeg,jpg,png|max:200',
        ]);

        $data = $validated;
        $data['username'] = $username;
        $data['step1_completed'] = 1;

        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            $data['photo_path'] = $photo->storeAs("uploads/{$username}", 'photo_' . time() . '.' . $photo->extension(), 'public');
        }
        if ($request->hasFile('signature')) {
            $sig = $request->file('signature');
            $data['signature_path'] = $sig->storeAs("uploads/{$username}", 'signature_' . time() . '.' . $sig->extension(), 'public');
        }

        StudentMasterFirst::updateOrCreate(['username' => $username], $data);

        StudentMaster::updateOrCreate(['username' => $username], [
            'session_id'   => $validated['session_id'],
            'full_name'    => $validated['full_name'],
            'service_code' => ServiceMaster::find($validated['service_id'])?->service_code,
            'cadre'        => $validated['cadre'],
            'step1_done'   => 1,
        ]);

        return redirect()->route('fc-reg.registration.step2')
            ->with('success', 'Step 1 saved successfully. Please complete Step 2.');
    }
}
