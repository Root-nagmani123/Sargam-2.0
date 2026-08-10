<?php

namespace App\Models\FC;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MctpTravelModeMaster extends Model {
    protected $table = 'mctp_travel_mode_masters';
    protected $fillable = ['travel_mode_name', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    /**
     * Active travel-mode names, in display order — the single source for the joining
     * "Mode of journey" dropdown (trainee form + admin edit) and for its validation rule.
     * The plan still STORES the name string in student_travel_plan_masters.mode_of_journey
     * exactly as before; only the list of choices moved out of the hardcoded array.
     *
     * Cached on the shared FC lookup key, so fc_flush_lookup_cache() publishes a master
     * edit immediately and fc.lookup_cache_ttl bounds it otherwise.
     *
     * @return list<string>
     */
    public static function activeNames(): array
    {
        $ttl = (int) config('fc.lookup_cache_ttl', 600);

        $fetch = static fn () => static::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->pluck('travel_mode_name')
            ->map(static fn ($name) => trim((string) $name))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($ttl <= 0) {
            return $fetch();
        }

        try {
            return Cache::remember(
                'fc_lookup:'.DB::getDatabaseName().':v'.fc_lookup_cache_version().':travel_modes',
                $ttl,
                $fetch
            );
        } catch (\Throwable $e) {
            // Cache store unavailable — the dropdown still has to render.
            return $fetch();
        }
    }

    /**
     * Choices for a select, with $current appended when it is a value saved before this
     * list changed (e.g. the legacy "By Air" / "By Road" / "By Train"). Without it an older
     * plan would open with nothing selected and be silently re-answered on save.
     *
     * @return list<string>
     */
    public static function choicesIncluding(?string $current): array
    {
        $names = static::activeNames();
        $current = trim((string) $current);

        if ($current !== '' && ! in_array($current, $names, true)) {
            $names[] = $current;
        }

        return $names;
    }
}
