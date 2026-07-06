<?php

namespace App\Models\FC;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FcFormGroupField extends Model
{
    protected $fillable = [
        'group_id', 'field_name', 'label', 'field_type', 'target_column',
        'validation_rules', 'is_required', 'display_order', 'placeholder',
        'options_json', 'lookup_table', 'lookup_value_column', 'lookup_label_column',
        'css_class', 'is_active',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_active'   => 'boolean',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(FcFormFieldGroup::class, 'group_id');
    }

    public function getDecodedOptionsAttribute(): array
    {
        if (! $this->options_json) {
            return [];
        }
        return json_decode($this->options_json, true) ?? [];
    }
}
