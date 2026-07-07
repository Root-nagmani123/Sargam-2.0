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

    public function __construct(
        protected FcProgrammeContextService $programmeContext
    ) {}

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
     * True when this request carries a non-empty ?form= or ?formid= programme token.
     */
    public function requestHasFormToken(Request $request): bool
    {
        if ($request->query->has('form')) {
            $token = $request->query('form');

            return is_string($token) && $token !== '';
        }

        if ($request->query->has('formid')) {
            $token = $request->query('formid');

            return is_string($token) && $token !== '';
        }

        return false;
    }

    /**
     * Best-effort encrypted ?form= query for the shared FC header login/logout links,
     * so the programme token survives navigation across the public funnel and after login.
     *
     * Resolution order (most authoritative first):
     *   1. Logged-in dynamic form pages (/fc-reg/forms/{form}) already carry the FcForm.
     *   2. Session intent captured earlier in the funnel.
     *   3. The encrypted token already present in the current URL.
     *
     * @return array{form?: string}
     */
    public function formQueryForHeaderLinks(Request $request): array
    {
        $routeForm = $request->route('form');
        if ($routeForm instanceof FcForm && $routeForm->getKey()) {
            return ['form' => FcEncryptedFormId::encode((int) $routeForm->getKey())];
        }

        $id = $request->session()->get(self::SESSION_FORM_ID);
        if (is_numeric($id) && (int) $id > 0) {
            return ['form' => FcEncryptedFormId::encode((int) $id)];
        }

        foreach (['form', 'formid'] as $key) {
            $token = $request->query($key);
            if (is_string($token) && $token !== '') {
                return ['form' => $token];
            }
        }

        return [];
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

        $this->programmeContext->rememberCourseForForm($form);

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
