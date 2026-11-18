<?php

namespace App\Models\Athletics;

use App\Models\CourseMaster;
use Illuminate\Database\Eloquent\Model;

class AthleticsDrive extends Model
{
    protected $table = 'athletics_drives';

    protected $fillable = [
        'course_master_pk', 'form_title', 'result_recorded_as', 'visible_to', 'max_criteria',
        'max_nominations', 'start_date', 'end_date', 'instructions', 'status', 'created_by',
    ];

    protected $casts = ['start_date' => 'date', 'end_date' => 'date'];

    public const RESULT_TYPES = ['time' => 'Time', 'distance' => 'Distance', 'height' => 'Height', 'rank' => 'Rank Only'];

    /** Column heading for the performance value on the result sheet. */
    public const PERFORMANCE_LABELS = ['time' => 'Time (in Seconds)', 'distance' => 'Distance (in Metres)', 'height' => 'Height (in Metres)'];

    public const VISIBLE_TO = ['male' => 'Male', 'female' => 'Female', 'both' => 'Both'];

    public const CRITERIA = ['event' => 'Basis on Event', 'overall' => 'Overall'];

    public const STATUSES = ['active' => 'Active', 'draft' => 'Draft'];

    public function course()
    {
        return $this->belongsTo(CourseMaster::class, 'course_master_pk', 'pk');
    }

    public function events()
    {
        return $this->belongsToMany(AthleticsEvent::class, 'athletics_drive_events', 'drive_id', 'event_id')
            ->withPivot('venue_id');
    }

    public function nominations()
    {
        return $this->hasMany(AthleticsNomination::class, 'drive_id');
    }

    public function results()
    {
        return $this->hasMany(AthleticsResult::class, 'drive_id');
    }

    /** "Track Events, Field Events" — the categories this drive's events fall in. */
    public function categoryList(): string
    {
        return $this->events->pluck('category')->unique()
            ->map(fn ($c) => AthleticsEvent::CATEGORIES[$c] ?? $c)->implode(', ');
    }

    public function isOpen(): bool
    {
        $today = now()->startOfDay();

        return $this->status === 'active' && $today->betweenIncluded($this->start_date, $this->end_date);
    }

    /** $gender is a normalised 'male' | 'female' | null; an unknown gender only sees "Both" drives. */
    public function isVisibleTo(?string $gender): bool
    {
        return $this->visible_to === 'both' || $this->visible_to === $gender;
    }

    /**
     * Why a set of picked events breaks this drive's nomination limit, or null.
     *
     * Group events are entered house-wise (the house in-charge picks the team),
     * so they never count. "Basis on Event" caps each individual category
     * (track, field) separately; "Overall" caps them together.
     *
     * @param  iterable<AthleticsEvent|array{category: string}>  $events
     */
    public function limitError(iterable $events): ?string
    {
        $max = (int) $this->max_nominations;
        $counts = collect($events)->map(fn ($e) => data_get($e, 'category'))
            ->reject(fn ($category) => $category === 'group')
            ->countBy();

        if ($this->max_criteria === 'overall') {
            return $counts->sum() > $max ? "You can pick at most {$max} individual events in total." : null;
        }

        foreach ($counts as $category => $count) {
            if ($count > $max) {
                $label = strtolower(AthleticsEvent::CATEGORIES[$category] ?? $category);

                return "You can pick at most {$max} {$label}.";
            }
        }

        return null;
    }

    /** 'male' | 'female' | null from student_master.gender's mixed spellings. */
    public static function normaliseGender(?string $gender): ?string
    {
        return match (strtolower(trim((string) $gender))) {
            'male', 'm' => 'male',
            'female', 'f' => 'female',
            default => null,
        };
    }
}
