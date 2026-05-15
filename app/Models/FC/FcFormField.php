<?php

namespace App\Models\FC;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FcFormField extends Model
{
    /** Bootstrap column classes for field width on the registration form row. */
    public const COLUMN_LAYOUTS = [
        'col-md-3'  => 'Small — ¼ of the row (3 columns)',
        'col-md-6'  => 'Medium — half row (6 columns)',
        'col-md-9'  => 'Large — ¾ of the row (9 columns)',
        'col-md-12' => 'Full width — entire row (12 columns)',
    ];

    public static function columnLayoutOptions(): array
    {
        return self::COLUMN_LAYOUTS;
    }

    public static function normalizeColumnLayout(?string $cssClass): string
    {
        $value = trim((string) $cssClass);
        if (array_key_exists($value, self::COLUMN_LAYOUTS)) {
            return $value;
        }

        return match ($value) {
            'col-3', 'col-md-4' => 'col-md-3',
            'col-6' => 'col-md-6',
            'col-9' => 'col-md-9',
            'col-12', 'col-md-12' => 'col-md-12',
            default => 'col-md-6',
        };
    }

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

    /** Comma-separated labels for admin UI (from stored options_json). */
    public static function optionsJsonToCommaList(?string $json): string
    {
        if ($json === null || trim($json) === '') {
            return '';
        }

        $decoded = json_decode($json, true);
        if (! is_array($decoded)) {
            return trim($json);
        }

        $labels = [];
        foreach ($decoded as $item) {
            if (is_array($item)) {
                $labels[] = trim((string) ($item['label'] ?? $item['value'] ?? ''));
            } elseif (is_string($item) || is_numeric($item)) {
                $labels[] = trim((string) $item);
            }
        }

        return implode(', ', array_filter($labels, fn ($l) => $l !== ''));
    }

    /** Build options_json from comma-separated admin input (value and label are the same). */
    public static function commaListToOptionsJson(?string $commaList): ?string
    {
        if ($commaList === null || trim($commaList) === '') {
            return null;
        }

        $parts = preg_split('/\s*,\s*/', trim($commaList), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if ($parts === []) {
            return null;
        }

        $options = array_map(
            fn (string $part) => ['value' => $part, 'label' => $part],
            $parts
        );

        return json_encode($options, JSON_UNESCAPED_UNICODE);
    }
}
