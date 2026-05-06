<?php

namespace App\Services\FC;

use App\Models\FC\FcForm;
use App\Support\FcEncryptedFormId;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Persists which FC dynamic form (fc_forms) a user should land on after the public
 * foundation → credentials → web login funnel. Intent is set from ?form= / ?formid=
 * on the foundation landing page and consumed after successful login.
 */
class FcRegistrationIntentService
{
    public const SESSION_FORM_ID = 'fc_reg_intended_fc_form_id';

    public const SESSION_FORM_SET_AT = 'fc_reg_intended_form_set_at';

    /** Max age of intent in seconds (from first capture) before login ignores it. */
    public const TTL_SECONDS = 604800;

    /**
     * Read optional encrypted form token from query string and store validated form id in session.
     *
     * - If `form` / `formid` is absent: leave existing session intent unchanged.
     * - If present but empty: clear intent (explicit reset).
     * - If present and invalid: clear intent and flash a safe message.
     */
    public function ingestFormQuery(Request $request): void
    {
        $token = null;
        if ($request->query->has('form')) {
            $token = $request->query('form');
        } elseif ($request->query->has('formid')) {
            $token = $request->query('formid');
        } else {
            return;
        }

        if ($token === null || $token === '') {
            $this->forgetIntent();
            return;
        }

        if (! is_string($token)) {
            $this->forgetIntent();
            $request->session()->flash('warning', 'This registration link is invalid. Please use the link provided by the academy.');

            return;
        }

        try {
            $id = FcEncryptedFormId::decode($token);
        } catch (\InvalidArgumentException) {
            $this->forgetIntent();
            $request->session()->flash('warning', 'This registration link is invalid or has expired. Please use the link provided by the academy.');

            return;
        }

        $form = FcForm::query()->whereKey($id)->where('is_active', true)->first();
        if (! $form) {
            $this->forgetIntent();
            $request->session()->flash('warning', 'Registration for this programme is not available. Please contact the academy office.');

            return;
        }

        $request->session()->put([
            self::SESSION_FORM_ID => (int) $form->id,
            self::SESSION_FORM_SET_AT => now()->getTimestamp(),
        ]);
    }

    public function forgetIntent(): void
    {
        session()->forget([self::SESSION_FORM_ID, self::SESSION_FORM_SET_AT]);
    }

    /**
     * After successful Auth::login from FC web credentials form.
     * Callers should read session into local variables before login if session may migrate.
     *
     * @param  int|null  $formId  Resolved from session before login
     * @param  int|null  $setAt  Unix timestamp from session before login
     */
    public function redirectAfterFcWebLogin(?int $formId, ?int $setAt): RedirectResponse
    {
        if ($formId === null || $formId < 1) {
            return $this->redirectTraineeHome()->with('success', 'Login successful!');
        }

        if ($setAt !== null && (now()->getTimestamp() - $setAt) > self::TTL_SECONDS) {
            return $this->redirectTraineeHome()
                ->with('warning', 'Your programme link has expired. You can continue from your dashboard or open a fresh link from the academy.');
        }

        $form = FcForm::query()->whereKey($formId)->where('is_active', true)->first();
        if (! $form) {
            return $this->redirectTraineeHome()
                ->with('warning', 'The selected programme is no longer available. You can continue from your dashboard.');
        }

        return redirect()->route('fc-reg.forms.dashboard', $form)
            ->with('success', 'Login successful!');
    }

    /**
     * Prefer the dynamic FC Registration dashboard when configured; otherwise legacy trainee dashboard.
     */
    public function redirectTraineeHome(): RedirectResponse
    {
        $registrationForm = FcForm::activeRegistrationDynamicForm();
        if ($registrationForm) {
            return redirect()->route('fc-reg.forms.dashboard', $registrationForm);
        }

        return redirect()->route('fc-reg.dashboard');
    }
}
