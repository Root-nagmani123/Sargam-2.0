<?php

namespace App\Models\FC;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FcFormStep extends Model
{
    protected $fillable = [
        'form_id', 'step_name', 'step_slug', 'step_number', 'target_table',
        'completion_column', 'tracker_column', 'is_active', 'description', 'icon',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(FcForm::class, 'form_id');
    }

    public function fields(): HasMany
    {
        return $this->hasMany(FcFormField::class, 'step_id')->orderBy('display_order');
    }

    public function activeFields(): HasMany
    {
        return $this->hasMany(FcFormField::class, 'step_id')->where('is_active', 1)->orderBy('display_order');
    }

    public function fieldGroups(): HasMany
    {
        return $this->hasMany(FcFormFieldGroup::class, 'step_id')->orderBy('display_order');
    }

    public function activeFieldGroups(): HasMany
    {
        return $this->hasMany(FcFormFieldGroup::class, 'step_id')->where('is_active', 1)->orderBy('display_order');
    }
}
