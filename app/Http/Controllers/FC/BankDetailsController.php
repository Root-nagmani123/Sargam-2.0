<?php

namespace App\Http\Controllers\FC;

use App\Http\Controllers\Controller;
use App\Models\FC\{NewRegistrationBankDetailsMaster, StudentMaster};
use App\Services\FC\DynamicFormService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BankDetailsController extends Controller
{
    public function __construct(private DynamicFormService $formService) {}

    public function show()
    {
        $username = Auth::user()->username;

        // Guard: Step 3 must be done
        $step3Done = StudentMaster::where('username', $username)->value('step3_done');
        if (! $step3Done) {
            return redirect()->route('fc-reg.registration.step3')
                ->with('error', 'Please complete Step 3 before filling bank details.');
        }

        $step   = $this->formService->getStep('bank');
        $fields = $this->formService->getStepFields('bank');

        // Fallback to original view if no dynamic fields configured
        if ($fields->isEmpty()) {
            $bank = NewRegistrationBankDetailsMaster::where('username', $username)->first();
            return view('fc.registration.bank', compact('bank'));
        }

        $lookups      = $this->formService->getLookupData($fields);
        $existingData = $this->formService->getExistingData('bank', $username);

        return view('fc.registration.dynamic-step', [
            'step'         => $step,
            'fields'       => $fields,
            'lookups'      => $lookups,
            'existingData' => $existingData,
            'saveUrl'      => route('fc-reg.registration.bank.save'),
            'prevUrl'      => route('fc-reg.registration.step3'),
        ]);
    }

    public function save(Request $request)
    {
        $username = Auth::user()->username;
        $fields   = $this->formService->getStepFields('bank');

        if ($fields->isEmpty()) {
            return $this->saveLegacy($request, $username);
        }

        $rules     = $this->formService->buildValidationRules($fields);
        $validated = $request->validate($rules);

        $this->formService->saveStepData('bank', $username, $validated, $request);

        // Mark bank_done on tracker
        StudentMaster::where('username', $username)->update(['bank_done' => 1]);

        return redirect()->route('fc-reg.registration.travel')
            ->with('success', 'Bank details saved. Please complete your travel plan next.');
    }

    private function saveLegacy(Request $request, string $username)
    {
        $validated = $request->validate([
            'bank_name'           => 'required|string|max:200',
            'branch_name'         => 'required|string|max:200',
            'ifsc_code'           => ['required', 'string', 'max:20', 'regex:/^[A-Z]{4}0[A-Z0-9]{6}$/'],
            'account_no'          => 'required|string|max:50',
            'account_no_confirm'  => 'required|same:account_no',
            'account_holder_name' => 'required|string|max:200',
            'account_type'        => 'required|in:Savings,Current',
            'bank_passbook'       => 'nullable|file|mimes:jpeg,jpg,png,pdf|max:2048',
        ]);

        if ($request->hasFile('bank_passbook')) {
            $file = $request->file('bank_passbook');
            $validated['bank_passbook_path'] = $file->storeAs(
                "uploads/{$username}/bank",
                'passbook_' . time() . '.' . $file->extension(),
                'public'
            );
        }

        unset($validated['account_no_confirm'], $validated['bank_passbook']);
        $validated['username'] = $username;

        NewRegistrationBankDetailsMaster::updateOrCreate(['username' => $username], $validated);
        StudentMaster::where('username', $username)->update(['bank_done' => 1]);

        return redirect()->route('fc-reg.registration.travel')
            ->with('success', 'Bank details saved. Please complete your travel plan next.');
    }
}
