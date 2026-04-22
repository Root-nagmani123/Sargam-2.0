<?php

namespace App\Http\Controllers\FC;

use App\Http\Controllers\Controller;
use App\Models\FC\{
    StudentMasterFirst, StudentMasterSecond, StudentMaster,
    CategoryMaster, ReligionMaster, StateMaster, CountryMaster, FatherProfession
};
use App\Services\FC\RegistrationService;
use App\Services\FC\DynamicFormService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RegistrationStep2Controller extends Controller
{
    public function __construct(
        private RegistrationService $regService,
        private DynamicFormService $formService,
    ) {}

    // ── SHOW Step 2 ──────────────────────────────────────────────────
    public function showStep2()
    {
        $username = Auth::user()->username;

        // Guard: Step 1 must be done
        $step1 = StudentMasterFirst::where('username', $username)
            ->where('step1_completed', 1)->first();
        if (! $step1) {
            return redirect()->route('fc-reg.registration.step1')
                ->with('error', 'Please complete Step 1 first.');
        }

        $step   = $this->formService->getStep('step2');
        $fields = $this->formService->getStepFields('step2');

        // Fallback to original view if no dynamic fields configured
        if ($fields->isEmpty()) {
            $step2             = StudentMasterSecond::where('username', $username)->first();
            $categories        = CategoryMaster::all();
            $religions         = ReligionMaster::all();
            $states            = StateMaster::orderBy('state_name')->get();
            $countries         = CountryMaster::orderBy('country_name')->get();
            $fatherProfessions = FatherProfession::all();
            return view('fc.registration.step2', compact(
                'step1', 'step2', 'categories', 'religions', 'states', 'countries', 'fatherProfessions'
            ));
        }

        $lookups      = $this->formService->getLookupData($fields);
        $existingData = $this->formService->getExistingData('step2', $username);

        return view('fc.registration.dynamic-step', [
            'step'         => $step,
            'fields'       => $fields,
            'lookups'      => $lookups,
            'existingData' => $existingData,
            'saveUrl'      => route('fc-reg.registration.step2.save'),
            'prevUrl'      => route('fc-reg.registration.step1'),
        ]);
    }

    // ── SAVE Step 2 ──────────────────────────────────────────────────
    public function saveStep2(Request $request)
    {
        $username = Auth::user()->username;
        $fields   = $this->formService->getStepFields('step2');

        if ($fields->isEmpty()) {
            return $this->saveStep2Legacy($request, $username);
        }

        $rules     = $this->formService->buildValidationRules($fields);
        $validated = $request->validate($rules);

        // If "same as permanent" ticked, copy perm → pres
        if ($request->boolean('same_as_permanent')) {
            $validated['pres_address_line1'] = $validated['perm_address_line1'] ?? null;
            $validated['pres_address_line2'] = $validated['perm_address_line2'] ?? null;
            $validated['pres_city']          = $validated['perm_city'] ?? null;
            $validated['pres_state_id']      = $validated['perm_state_id'] ?? null;
            $validated['pres_pincode']       = $validated['perm_pincode'] ?? null;
            $validated['pres_country_id']    = $validated['perm_country_id'] ?? null;
        }

        $this->formService->saveStepData('step2', $username, $validated, $request);

        return redirect()->route('fc-reg.registration.step3')
            ->with('success', 'Step 2 saved. Please complete Step 3.');
    }

    private function saveStep2Legacy(Request $request, string $username)
    {
        $validated = $request->validate([
            'category_id'                => 'required|exists:caste_category_master,pk',
            'religion_id'                => 'required|exists:religion_master,pk',
            'domicile_state'             => 'nullable|string|max:100',
            'marital_status'             => 'required|in:Single,Married,Divorced,Widowed',
            'blood_group'                => 'required|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'height_cm'                  => 'nullable|numeric|min:50|max:300',
            'weight_kg'                  => 'nullable|numeric|min:20|max:300',
            'identification_mark1'       => 'nullable|string|max:300',
            'identification_mark2'       => 'nullable|string|max:300',
            'perm_address_line1'         => 'required|string|max:300',
            'perm_address_line2'         => 'nullable|string|max:300',
            'perm_city'                  => 'required|string|max:100',
            'perm_state_id'              => 'required|exists:state_masters,id',
            'perm_pincode'               => 'required|digits:6',
            'perm_country_id'            => 'required|exists:country_master,pk',
            'same_as_permanent'          => 'nullable|boolean',
            'pres_address_line1'         => 'required_without:same_as_permanent|nullable|string|max:300',
            'pres_address_line2'         => 'nullable|string|max:300',
            'pres_city'                  => 'required_without:same_as_permanent|nullable|string|max:100',
            'pres_state_id'              => 'required_without:same_as_permanent|nullable|exists:state_masters,id',
            'pres_pincode'               => 'required_without:same_as_permanent|nullable|digits:6',
            'pres_country_id'            => 'required_without:same_as_permanent|nullable|exists:country_master,pk',
            'emergency_contact_name'     => 'required|string|max:200',
            'emergency_contact_relation' => 'required|string|max:100',
            'emergency_contact_mobile'   => 'required|digits:10',
            'father_profession_id'       => 'nullable|exists:father_professions,id',
            'father_occupation_details'  => 'nullable|string|max:300',
        ]);

        if ($request->boolean('same_as_permanent')) {
            $validated['pres_address_line1'] = $validated['perm_address_line1'];
            $validated['pres_address_line2'] = $validated['perm_address_line2'] ?? null;
            $validated['pres_city']          = $validated['perm_city'];
            $validated['pres_state_id']      = $validated['perm_state_id'];
            $validated['pres_pincode']       = $validated['perm_pincode'];
            $validated['pres_country_id']    = $validated['perm_country_id'];
        }

        $validated['username']        = $username;
        $validated['step2_completed'] = 1;

        StudentMasterSecond::updateOrCreate(['username' => $username], $validated);
        StudentMaster::where('username', $username)->update(['step2_done' => 1]);

        return redirect()->route('fc-reg.registration.step3')
            ->with('success', 'Step 2 saved. Please complete Step 3.');
    }
}
