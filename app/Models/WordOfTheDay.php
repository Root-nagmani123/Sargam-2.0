<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class WordOfTheDay extends Model
{
    use SoftDeletes;

    protected $table = 'word_of_the_days';

    protected $fillable = [
        'hindi_text',
        'english_text',
        'sort_order',
        'active_inactive',
        'scheduled_date',
        'created_by_pk',
        'updated_by_pk',
    ];

    protected $casts = [
        'active_inactive' => 'boolean',
        'sort_order' => 'integer',
        'scheduled_date' => 'date',
        'created_by_pk' => 'integer',
        'updated_by_pk' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saved(static fn () => static::forgetTodayCache());
        static::deleted(static fn () => static::forgetTodayCache());
        static::restored(static fn () => static::forgetTodayCache());
    }

    public function scopeActive($query)
    {
        return $query->where('active_inactive', true);
    }

    public static function cacheKeyForDate(string $dateYmd): string
    {
        return config('word_of_the_day.cache_key_prefix', 'word_of_the_day:').$dateYmd;
    }

    public static function forgetTodayCache(): void
    {
        $tz = config('app.timezone');
        Cache::forget(static::cacheKeyForDate(now($tz)->toDateString()));
    }

    /**
     * Cached resolver for “today” (app timezone).
     */
    public static function wordForToday(): ?self
    {
        $tz = config('app.timezone');
        $dateKey = now($tz)->toDateString();
        $ttl = now($tz)->endOfDay()->addSecond();

        return Cache::remember(static::cacheKeyForDate($dateKey), $ttl, function () use ($tz) {
            return static::resolveForDate(now($tz));
        });
    }

    /**
     * Uncached: scheduled override for $on, else cyclic rotation from anchor date.
     */
    public static function resolveForDate(CarbonInterface $on): ?self
    {
        $day = $on->copy()->startOfDay();

        $scheduled = static::query()
            ->active()
            ->whereDate('scheduled_date', $day)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();

        if ($scheduled) {
            return $scheduled;
        }

        $words = static::query()
            ->active()
            ->whereNull('scheduled_date')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($words->isEmpty()) {
            return null;
        }

        $anchor = \Carbon\Carbon::parse(
            config('word_of_the_day.rotation_anchor_date', '1970-01-01'),
            config('app.timezone')
        )->startOfDay();

        $dayIndex = static::rotationIndexFromAnchor($day, $anchor);

        return $words[$dayIndex % $words->count()];
    }

    /**
     * Build a preview of which word shows on each date (useful for admin UI).
     *
     * @return array<int, array{date:string, line:?string, id:?int, mode:string}>
     */
    public static function previewRotation(int $days = 7, ?CarbonInterface $start = null): array
    {
        $tz = config('app.timezone');
        $cursor = ($start ?? now($tz))->copy()->startOfDay();
        $out = [];

        for ($i = 0; $i < $days; $i++) {
            $d = $cursor->copy()->addDays($i);
            $w = static::resolveForDate($d);
            $out[] = [
                'date' => $d->toDateString(),
                'line' => $w ? $w->displayLine() : null,
                'id' => $w?->id,
                'mode' => static::resolveModeForDate($d),
            ];
        }

        return $out;
    }

    protected static function resolveModeForDate(CarbonInterface $on): string
    {
        $day = $on->copy()->startOfDay();
        $scheduled = static::query()
            ->active()
            ->whereDate('scheduled_date', $day)
            ->exists();

        return $scheduled ? 'scheduled' : 'rotation';
    }

    public function displayLine(): string
    {
        return trim($this->hindi_text).' - '.trim($this->english_text);
    }

    /**
     * Whole days from anchor (midnight) to day (midnight); non-negative.
     */
    public static function rotationIndexFromAnchor(CarbonInterface $day, CarbonInterface $anchor): int
    {
        $dayStart = $day->copy()->startOfDay();
        $anchorStart = $anchor->copy()->startOfDay();
        $dayIndex = (int) $anchorStart->diffInDays($dayStart, false);

        return $dayIndex < 0 ? 0 : $dayIndex;
    }
}
