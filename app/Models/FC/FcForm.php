<?php

namespace App\Models\FC;

use App\Models\CourseMaster;
use App\Support\FcEncryptedFormId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class FcForm extends Model
{
    protected $fillable = [
        'form_name', 'form_slug', 'description', 'icon',
        'consolidation_table', 'user_identifier', 'registration_requires_all_steps',
        'is_active', 'course_master_pk',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'registration_requires_all_steps' => 'boolean',
    ];

    /**
     * Whether fc_registration_master.is_registered for this form means "every applicable
     * step done" rather than the default "first two steps done". Opt-in per form so the
     * rule can change for an upcoming intake without touching existing cohorts.
     *
     * Reads defensively: the column is absent until the migration runs, and a form loaded
     * from a cache written before it would not carry the attribute.
     */
    public function registrationRequiresAllSteps(): bool
    {
        return (bool) ($this->registration_requires_all_steps ?? false);
    }

    public function courseMaster(): BelongsTo
    {
        return $this->belongsTo(CourseMaster::class, 'course_master_pk', 'pk');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(FcFormStep::class, 'form_id')->orderBy('step_number');
    }

    public function activeSteps(): HasMany
    {
        return $this->hasMany(FcFormStep::class, 'form_id')
            ->where('is_active', 1)
            ->orderBy('step_number');
    }

    /**
     * Normalise user_identifier: treat legacy 'username' value as 'user_id' so
     * code that reads $form->user_identifier always gets the correct column name
     * even before the DB migration has been run.
     */
    public function getUserIdentifierAttribute(?string $value): string
    {
        $v = $value ?? '';
        return ($v === '' || $v === 'username') ? 'user_id' : $v;
    }

    /**
     * Table where step tracker flags (e.g. step1_done) are written and read for dynamic forms.
     * When no consolidation table is configured, the app uses student_masters (same as DynamicFormService).
     */
    public function trackerStorageTable(): string
    {
        return filled($this->consolidation_table) ? $this->consolidation_table : 'student_masters';
    }

    /**
     * Public + admin URLs use encrypted id instead of raw integer.
     *
     * {@inheritdoc}
     */
    public function getRouteKey(): mixed
    {
        if ($this->getKey() === null) {
            return parent::getRouteKey();
        }

        return FcEncryptedFormId::encode((int) $this->getKey());
    }

    /**
     * Resolve implicit route binding from encrypted URL token only.
     *
     * {@inheritdoc}
     */
    public function resolveRouteBinding($value, $field = null)
    {
        try {
            $id = FcEncryptedFormId::decode((string) $value);
        } catch (\InvalidArgumentException) {
            abort(404);
        }

        return $this->where('id', $id)->firstOrFail();
    }

    /**
     * The form with slug fc-registration, if active. Null means "no fc-registration
     * intake is currently open" — callers that gate a legacy/fallback UI on that
     * exact meaning (e.g. RegistrationStep1Controller::dashboard()) must use this,
     * not activeRegistrationDynamicForm().
     */
    public static function strictActiveRegistrationForm(): ?self
    {
        return static::query()
            ->where('form_slug', 'fc-registration')
            ->where('is_active', true)
            ->first();
    }

    /**
     * Active Foundation Course registration form used by the dynamic trainee UI (/fc-reg/forms/…).
     * Falls back to the newest other active intake when slug fc-registration is not
     * present (e.g. fc-102) — only safe for callers that just need *some* current
     * intake (portal links, bulk-notification scoping), not per-user form attribution.
     */
    public static function activeRegistrationDynamicForm(): ?self
    {
        $form = static::strictActiveRegistrationForm();

        if ($form) {
            return $form;
        }

        // Fallback: current intake when slug fc-registration is not present (e.g. fc-102).
        return static::query()
            ->where('is_active', true)
            ->where('form_slug', '!=', 'fc_template')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * FC form used for reports/PDF for a trainee — resolved via user_id.
     */
    public static function resolveForUserId(int $userId): ?self
    {
        if ($userId > 0 && Schema::hasTable('student_masters') && Schema::hasColumn('student_masters', 'form_id')) {
            $formId = (int) (StudentMaster::where(fc_user_col('student_masters'), fc_user_val('student_masters', $userId))->value('form_id') ?? 0);
            if ($formId > 0) {
                $form = static::query()->whereKey($formId)->where('is_active', true)->first();
                if ($form) {
                    return $form;
                }
            }
        }

        $ninetyNinth = static::query()
            ->where('form_slug', 'fc-registration-99th')
            ->where('is_active', true)
            ->first();
        if ($ninetyNinth) {
            return $ninetyNinth;
        }

        // Strict match only: this resolves which specific form a user belongs to
        // (reports/PDFs/registration sync) — an arbitrary "some other active form"
        // would misattribute the user, so the broader fallback does not apply here.
        return static::strictActiveRegistrationForm();
    }

    /**
     * Form URL for Users (/fc-reg/forms/{token}), as shown in Form Management.
     */
    public function formUrlForUsers(): string
    {
        return route('fc-reg.forms.dashboard', $this);
    }

    /**
     * Public landing page URL for this form (?form= encrypted token), as shown in Form Management.
     */
    public function landingPageUrl(): string
    {
        return route('frontpage.index', ['form' => $this->getRouteKey()]);
    }

    /**
     * Login page URL — same query-string shape as landingPageUrl() (BSNL CTA
     * whitelisting constraint, see FcNotifyService::portal()) but points trainees
     * who are already registered straight to login instead of the registration
     * landing page.
     */
    public function loginUrl(): string
    {
        return route('fc.login', ['form' => $this->getRouteKey()]);
    }
}

