<?php

namespace App\Models\FC;

use App\Support\FcEncryptedFormId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FcForm extends Model
{
    protected $fillable = [
        'form_name', 'form_slug', 'description', 'icon',
        'consolidation_table', 'user_identifier', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

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
}

