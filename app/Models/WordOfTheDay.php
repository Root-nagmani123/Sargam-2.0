<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WordOfTheDay extends Model
{
    protected $table = 'word_of_the_days';

    protected $fillable = [
        'hindi_text',
        'english_text',
        'sort_order',
        'active_inactive',
    ];

    protected $casts = [
        'active_inactive' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('active_inactive', true);
    }

    /**
     * Pick one row per calendar day (app timezone): cycles through active rows in sort_order / id order.
     */
    public static function wordForToday(): ?self
    {
        $words = static::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($words->isEmpty()) {
            return null;
        }

        $dayIndex = (int) floor(now()->copy()->startOfDay()->timestamp / 86400);

        return $words[$dayIndex % $words->count()];
    }

    public function displayLine(): string
    {
        return trim($this->hindi_text).' - '.trim($this->english_text);
    }
}
