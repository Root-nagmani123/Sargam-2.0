<?php

namespace App\Models\FC;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FcFormField extends Model
{
    protected $fillable = [
        'step_id', 'field_name', 'label', 'field_type', 'target_table', 'target_column',
        'validation_rules', 'is_required', 'display_order', 'placeholder', 'help_text',
        'default_value', 'options_json', 'lookup_table', 'lookup_value_column',
        'lookup_label_column', 'lookup_order_column', 'section_heading', 'css_class',
        'file_max_kb', 'file_extensions', 'is_active',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_active'   => 'boolean',
    ];

    public function step(): BelongsTo
    {
        return $this->belongsTo(FcFormStep::class, 'step_id');
    }

    public function getDecodedOptionsAttribute(): array
    {
        if (! $this->options_json) {
            return [];
        }
        return json_decode($this->options_json, true) ?? [];
    }
}
