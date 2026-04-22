<?php

namespace App\Models\FC;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FcFormFieldGroup extends Model
{
    protected $fillable = [
        'step_id', 'group_name', 'group_label', 'target_table',
        'save_mode', 'min_rows', 'max_rows', 'display_order', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function step(): BelongsTo
    {
        return $this->belongsTo(FcFormStep::class, 'step_id');
    }

    public function groupFields(): HasMany
    {
        return $this->hasMany(FcFormGroupField::class, 'group_id')->orderBy('display_order');
    }

    public function activeGroupFields(): HasMany
    {
        return $this->hasMany(FcFormGroupField::class, 'group_id')->where('is_active', 1)->orderBy('display_order');
    }
}
