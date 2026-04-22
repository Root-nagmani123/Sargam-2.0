<?php

namespace App\Models\FC;

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
}
