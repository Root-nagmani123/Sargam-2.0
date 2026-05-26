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
        'consolidation_table', 'user_identifier', 'is_active', 'course_master_pk',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

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
     * Active Foundation Course registration form used by the dynamic trainee UI (/fc-reg/forms/…).
     */
    public static function activeRegistrationDynamicForm(): ?self
    {
        return static::query()
            ->where('form_slug', 'fc-registration')
            ->where('is_active', true)
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

        return static::activeRegistrationDynamicForm();
    }
}

