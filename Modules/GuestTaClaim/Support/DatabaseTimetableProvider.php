<?php

namespace Modules\GuestTaClaim\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\GuestTaClaim\Contracts\TimetableProvider;

/**
 * Reads courses, guest faculty and sessions from the existing Sargam tables.
 * Table and column names come from config('guest-ta-claim.timetable').
 */
class DatabaseTimetableProvider implements TimetableProvider
{
    protected function map(string $key): array
    {
        return config("guest-ta-claim.timetable.$key");
    }

    public function courses(): Collection
    {
        $c = $this->map('courses');
        $q = DB::table($c['table'])->select([
            "{$c['id']} as id", "{$c['name']} as name", "{$c['coordinator_user_id']} as coordinator_user_id",
        ]);
        foreach ((array) ($c['active_scope'] ?? []) as $col => $val) {
            $q->where($col, $val);
        }

        return $q->orderBy($c['name'])->get()->map(fn ($r) => (array) $r);
    }

    public function course(string|int $courseId): ?array
    {
        $c = $this->map('courses');
        $row = DB::table($c['table'])->where($c['id'], $courseId)->first([
            "{$c['id']} as id", "{$c['name']} as name", "{$c['coordinator_user_id']} as coordinator_user_id",
        ]);

        return $row ? (array) $row : null;
    }

    public function guestSessions(string|int $courseId, CarbonInterface $from, CarbonInterface $to): Collection
    {
        $s = $this->map('sessions');
        $f = $this->map('faculty');

        return DB::table($s['table'].' as s')
            ->join($f['table'].' as f', "f.{$f['id']}", '=', "s.{$s['faculty_id']}")
            ->where("s.{$s['course_id']}", $courseId)
            ->whereBetween("s.{$s['date']}", [$from->format('Y-m-d'), $to->format('Y-m-d')])
            ->where("f.{$f['type_column']}", $f['guest_value'])
            ->orderBy("s.{$s['date']}")->orderBy("s.{$s['start']}")
            ->get([
                "s.{$s['id']} as id", "s.{$s['faculty_id']} as faculty_id", "s.{$s['date']} as date",
                "s.{$s['start']} as start", "s.{$s['end']} as end", "s.{$s['topic']} as topic", "s.{$s['venue']} as venue",
            ])
            ->map(fn ($r) => [
                'id' => (string) $r->id,
                'faculty_id' => $r->faculty_id,
                'date' => substr((string) $r->date, 0, 10),
                'start' => substr((string) $r->start, 0, 5),
                'end' => substr((string) $r->end, 0, 5),
                'topic' => (string) $r->topic,
                'venue' => (string) $r->venue,
            ]);
    }

    public function faculty(string|int $facultyId): ?array
    {
        return $this->facultyMany([$facultyId])->first();
    }

    public function facultyMany(array $ids): Collection
    {
        $f = $this->map('faculty');

        return DB::table($f['table'])->whereIn($f['id'], $ids)->get([
            "{$f['id']} as id", "{$f['name']} as name", "{$f['designation']} as designation",
            "{$f['organisation']} as organisation", "{$f['category']} as category", "{$f['city']} as city",
        ])->mapWithKeys(function ($r) {
            $faculty = (array) $r;
            $faculty['official_address'] = collect([$faculty['organisation'], $faculty['city']])->filter()->join(', ');

            return [$r->id => $faculty];
        });
    }
}
