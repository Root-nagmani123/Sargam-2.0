<?php

namespace Modules\GuestTaClaim\Support;

use App\Models\CourseMaster;
use App\Models\FacultyMaster;
use App\Models\Timetable;
use Carbon\CarbonInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\GuestTaClaim\Contracts\TimetableProvider;

/**
 * Reads the real Sargam timetable.
 *
 *  course_master   pk, course_name, couse_short_name, active_inactive
 *  faculty_master  pk, faculty_code, faculty_type, full_name, current_designation, current_department, city_master_pk
 *  timetable       pk, course_master_pk, subject_topic, faculty_master (JSON ids), faculty_details (JSON rows),
 *                  venue_id, class_session ("09:30 AM - 10:30 AM" or "10:35 to 11:30"), START_DATE,
 *                  is_break, active_inactive
 *
 * A session is shown for every faculty linked to it (faculty_master ids plus
 * "Teaching" rows in faculty_details) whose faculty_master.faculty_type is a
 * guest type (config guest-ta-claim.sargam.guest_faculty_types).
 */
class SargamTimetableProvider implements TimetableProvider
{
    /** @var array<string, array|null> */
    protected array $facultyCache = [];

    public function courses(): Collection
    {
        return CourseMaster::query()
            ->where('active_inactive', 1)
            ->orderByDesc('pk')
            ->toBase()
            ->get($this->courseColumns())
            ->map(fn ($c) => $this->mapCourse($c));
    }

    public function course(string|int $courseId): ?array
    {
        $c = CourseMaster::query()->where('pk', $courseId)->toBase()->first($this->courseColumns());

        return $c ? $this->mapCourse($c) : null;
    }

    public function guestSessions(string|int $courseId, CarbonInterface $from, CarbonInterface $to): Collection
    {
        $rows = Timetable::query()
            ->where('course_master_pk', $courseId)
            ->where('active_inactive', 1)
            ->where(fn ($q) => $q->whereNull('is_break')->orWhere('is_break', 0))
            ->whereBetween('START_DATE', [$from->format('Y-m-d'), $to->format('Y-m-d')])
            ->orderBy('START_DATE')
            ->toBase()
            ->get(['pk', 'subject_topic', 'faculty_master', 'faculty_details', 'venue_id', 'class_session', 'START_DATE']);

        // Collect every faculty id first, so faculty and venues load in one query each.
        $facultyIdsByRow = $rows->mapWithKeys(fn ($r) => [$r->pk => $this->facultyIdsOf($r)]);
        $guests = $this->facultyMany($facultyIdsByRow->flatten()->unique()->values()->all())
            ->filter(fn ($f) => $f['is_guest']);
        $venues = $this->venueNames($rows->pluck('venue_id')->filter()->unique()->values()->all());

        $out = collect();
        foreach ($rows as $r) {
            [$start, $end] = $this->parseTimes((string) $r->class_session);
            foreach ($facultyIdsByRow[$r->pk] as $fid) {
                if (! isset($guests[$fid])) {
                    continue;
                }
                $out->push([
                    'id' => (string) $r->pk,
                    'faculty_id' => $fid,
                    'date' => substr((string) $r->START_DATE, 0, 10),
                    'start' => $start,
                    'end' => $end,
                    'topic' => trim(strip_tags((string) $r->subject_topic)) ?: 'Session',
                    'venue' => $venues[$r->venue_id] ?? ($r->venue_id ? 'Venue '.$r->venue_id : ''),
                ]);
            }
        }

        return $out->sortBy(fn ($s) => $s['date'].' '.$s['start'])->values();
    }

    public function faculty(string|int $facultyId): ?array
    {
        return $this->facultyMany([(string) $facultyId])->get((string) $facultyId);
    }

    public function facultyMany(array $ids): Collection
    {
        $ids = array_values(array_unique(array_map('strval', $ids)));
        $missing = array_values(array_diff($ids, array_keys($this->facultyCache)));

        if ($missing) {
            $rows = FacultyMaster::query()->whereIn('pk', $missing)->toBase()
                ->get(['pk', 'faculty_code', 'faculty_type', 'full_name', 'current_designation', 'current_department', 'city_master_pk']);
            $cities = $this->cityNames($rows->pluck('city_master_pk')->filter()->unique()->values()->all());
            $guestTypes = array_map('strval', (array) config('guest-ta-claim.sargam.guest_faculty_types', [2]));

            foreach ($rows as $f) {
                $this->facultyCache[(string) $f->pk] = [
                    'id' => (string) $f->pk,
                    'code' => $f->faculty_code,
                    'name' => trim((string) $f->full_name),
                    'designation' => (string) $f->current_designation,
                    'organisation' => (string) $f->current_department,
                    'category' => null, // chosen on the claim form
                    'city' => $cities[$f->city_master_pk] ?? '',
                    'official_address' => collect([$f->current_department, $cities[$f->city_master_pk] ?? null])->filter()->join(', '),
                    'faculty_type' => (string) $f->faculty_type,
                    'is_guest' => in_array((string) $f->faculty_type, $guestTypes, true),
                ];
            }
            foreach ($missing as $id) {
                $this->facultyCache[$id] ??= null;
            }
        }

        return collect($ids)->mapWithKeys(fn ($id) => [$id => $this->facultyCache[$id]])->filter();
    }

    /* ---------------------------------------------------------------- */

    protected function courseColumns(): array
    {
        $cols = ['pk', 'course_name', 'couse_short_name'];
        if ($cc = config('guest-ta-claim.sargam.course_coordinator_column')) {
            $cols[] = $cc;
        }

        return $cols;
    }

    protected function mapCourse(object $c): array
    {
        $cc = config('guest-ta-claim.sargam.course_coordinator_column');
        $short = trim((string) ($c->couse_short_name ?? ''));

        return [
            'id' => (string) $c->pk,
            'name' => trim((string) $c->course_name).($short !== '' ? " ($short)" : ''),
            'coordinator_user_id' => $cc ? ($c->{$cc} ?: null) : null,
        ];
    }

    /** faculty_master is '["84","12"]', '12' or '[12]'; faculty_details is a JSON list of {faculty_pk, role, ...}. */
    protected function facultyIdsOf(object $r): array
    {
        $ids = [];

        $fm = $this->decodeJson($r->faculty_master);
        if (is_array($fm)) {
            $ids = array_merge($ids, $fm);
        } elseif ($r->faculty_master !== null && trim((string) $r->faculty_master) !== '') {
            $ids = array_merge($ids, preg_split('/\s*,\s*/', trim((string) $r->faculty_master)));
        }

        $roles = array_map('strtolower', (array) config('guest-ta-claim.sargam.claimable_roles', ['Teaching']));
        foreach ((array) $this->decodeJson($r->faculty_details) as $d) {
            if (is_array($d) && ! empty($d['faculty_pk']) && in_array(strtolower((string) ($d['role'] ?? 'teaching')), $roles, true)) {
                $ids[] = $d['faculty_pk'];
            }
        }

        return array_values(array_unique(array_filter(array_map(fn ($v) => trim((string) $v), $ids), fn ($v) => $v !== '' && ctype_digit($v))));
    }

    protected function decodeJson($value)
    {
        if ($value === null || $value === '') {
            return null;
        }
        $v = json_decode((string) $value, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $v = json_decode(stripslashes((string) $value), true); // values saved with escaped quotes
        }

        return json_last_error() === JSON_ERROR_NONE ? $v : null;
    }

    /** "03:30 PM - 04:30 PM" or "10:35 to 11:30" -> ['15:30', '16:30'] */
    protected function parseTimes(string $text): array
    {
        $re = '/(\d{1,2})[:.](\d{2})\s*([AaPp][Mm])?\s*(?:-|–|to)\s*(\d{1,2})[:.](\d{2})\s*([AaPp][Mm])?/u';
        if (! preg_match($re, $text, $m)) {
            return ['00:00', '23:59'];
        }
        $to24 = function ($h, $min, $ampm) {
            $h = (int) $h;
            if ($ampm) {
                $pm = strtolower($ampm) === 'pm';
                $h = $h % 12 + ($pm ? 12 : 0);
            }

            return sprintf('%02d:%02d', min($h, 23), (int) $min);
        };
        $start = $to24($m[1], $m[2], $m[3] ?? '');
        $end = $to24($m[4], $m[5], ($m[6] ?? '') ?: ($m[3] ?? ''));

        return $end > $start ? [$start, $end] : [$start, '23:59'];
    }

    protected function venueNames(array $ids): array
    {
        return $this->lookup('venue', $ids);
    }

    protected function cityNames(array $ids): array
    {
        return $this->lookup('city', $ids);
    }

    /** Optional name lookups; if the configured table/columns don't exist, fall back quietly. */
    protected function lookup(string $key, array $ids): array
    {
        $cfg = config("guest-ta-claim.sargam.$key");
        if (! $ids || empty($cfg['table'])) {
            return [];
        }
        try {
            return DB::table($cfg['table'])->whereIn($cfg['id'], $ids)->pluck($cfg['name'], $cfg['id'])->all();
        } catch (QueryException $e) {
            report($e);

            return [];
        }
    }
}