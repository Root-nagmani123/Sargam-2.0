<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * One PDF in the Timetable Repository: a document filed against a course and a
 * week of that course. `week_start` holds the Monday of the week (same
 * convention as course_week_notes), so "Week N" is derived from the course
 * start date rather than frozen at upload time.
 */
class TimetableRepositoryDocument extends Model
{
    protected $table = 'timetable_repository_documents';

    protected $primaryKey = 'pk';

    protected $fillable = [
        'document_name',
        'course_master_pk',
        'week_start',
        'file_name',
        'file_path',
        'file_size',
        'created_by',
        'modified_by',
    ];

    protected $casts = [
        'week_start' => 'date',
        'file_size'  => 'integer',
    ];

    public function course()
    {
        return $this->belongsTo(CourseMaster::class, 'course_master_pk', 'pk');
    }

    /**
     * Week number of this document within its course — 1-based, counted from the
     * week that contains the course start date. Null when the course row is gone.
     */
    public function getWeekNumberAttribute(): ?int
    {
        $anchor = self::courseWeekAnchor($this->course);

        if (! $anchor || ! $this->week_start) {
            return null;
        }

        $week = $this->week_start->copy()->startOfWeek(Carbon::MONDAY);

        return (int) $anchor->diffInWeeks($week, false) + 1;
    }

    /**
     * Monday of the course's first week — the anchor every "Week N" is counted from.
     * Falls back to the course year when start_year is not filled in, so courses
     * without a start date still get a usable week list.
     */
    public static function courseWeekAnchor(?CourseMaster $course): ?Carbon
    {
        if (! $course) {
            return null;
        }

        $start = $course->start_year
            ? Carbon::parse($course->start_year)
            : ($course->course_year ? Carbon::create((int) $course->course_year, 1, 1) : null);

        return $start?->startOfWeek(Carbon::MONDAY);
    }

    /** Human readable size for the listing, e.g. "1.4 MB". */
    public function getFileSizeForHumansAttribute(): ?string
    {
        if (! $this->file_size) {
            return null;
        }

        return $this->file_size >= 1048576
            ? round($this->file_size / 1048576, 1) . ' MB'
            : max(1, (int) round($this->file_size / 1024)) . ' KB';
    }

    public function fileExists(): bool
    {
        return filled($this->file_path) && Storage::disk('public')->exists($this->file_path);
    }
}
