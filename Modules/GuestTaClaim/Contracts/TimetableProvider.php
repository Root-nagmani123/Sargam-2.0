<?php

namespace Modules\GuestTaClaim\Contracts;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Source of courses, guest faculty and timetable sessions.
 *
 * Course:  ['id', 'name', 'coordinator_user_id']
 * Faculty: ['id', 'name', 'designation', 'organisation', 'category', 'city']
 * Session: ['id', 'faculty_id', 'date' (Y-m-d), 'start' (H:i), 'end' (H:i), 'topic', 'venue']
 */
interface TimetableProvider
{
    public function courses(): Collection;

    public function course(string|int $courseId): ?array;

    /** Guest faculty sessions of a course between two dates (inclusive). */
    public function guestSessions(string|int $courseId, CarbonInterface $from, CarbonInterface $to): Collection;

    public function faculty(string|int $facultyId): ?array;

    /** @return Collection<string|int, array> keyed by faculty id */
    public function facultyMany(array $ids): Collection;
}
