<?php

namespace App\Services\Timetable;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Builds the Time x Group x Day grid for the weekly timetable PDF.
 *
 * Nothing about the shape of the grid is fixed. Time rows are derived from the
 * distinct start/end instants the week's own events imply, day columns from the
 * days that actually carry events, and the group axis from the group names those
 * events are mapped to. A week of 3 days with one group and a week of 6 days
 * with four groups both fall out of the same code.
 *
 * Breaks are not special-cased: a break is an event whose interval happens to be
 * shared by every day/group, and the "band" it renders as is just what any event
 * with that coverage would render as.
 *
 * Geometry note: the grid is flattened to physical rows indexed
 * (timeIndex * groupCount + groupIndex). An event merges into a single cell only
 * when the physical rows it covers are contiguous — true when it spans every
 * group, or when it occupies a single time row. A subset of groups across several
 * time rows is a comb, not a rectangle, and is emitted as one cell per contiguous
 * run rather than being silently dropped or stretched over groups it doesn't own.
 */
class WeeklyTimetableBuilder
{
    /** Placeholder used when a week's events carry no group mapping at all. */
    private const NO_GROUP = '__ungrouped__';

    /** faculty_master.faculty_type: 1 = Internal, 2 = Guest, 3 = Research. */
    private const FACULTY_TYPE_INTERNAL = 1;

    /**
     * @param  iterable  $events  timetable rows joined to venue_master
     * @return array{
     *     days: array, groups: array, rows: array, hasGroupAxis: bool,
     *     weekNumber: int, rangeLabel: string, isEmpty: bool
     * }
     */
    public function build(iterable $events, Carbon $weekStart, ?object $course = null): array
    {
        $weekStart = $weekStart->copy()->startOfWeek(Carbon::MONDAY);
        $events    = collect($events);

        $groupNames = $this->resolveGroupNames($events);
        $items      = $this->normaliseEvents($events, $weekStart, $groupNames);

        $days     = $this->resolveDays($items, $weekStart);
        $groups   = $this->resolveGroups($items);
        $segments = $this->resolveSegments($items);

        $rows = $this->layout($items, $days, $groups, $segments);

        return [
            'days'         => $days,
            'groups'       => $groups,
            'rows'         => $rows,
            'hasGroupAxis' => count($groups) > 1,
            'weekNumber'   => $this->resolveWeekNumber($weekStart, $course),
            'rangeLabel'   => $this->resolveRangeLabel($weekStart, $days),
            'isEmpty'      => $items->isEmpty(),
        ];
    }

    /**
     * Map every group PK referenced by the week onto its group_name in one query.
     * timetable.group_name holds a JSON array of group_type_master_course_master_map
     * PKs; the names behind them are free text and are printed verbatim.
     *
     * @return array<int, string>
     */
    private function resolveGroupNames(iterable $events): array
    {
        $pks = [];
        foreach ($events as $event) {
            foreach ($this->decodeIdList($event->group_name ?? null) as $pk) {
                $pks[$pk] = true;
            }
        }
        if (!$pks) {
            return [];
        }

        return DB::table('group_type_master_course_master_map')
            ->whereIn('pk', array_keys($pks))
            ->pluck('group_name', 'pk')
            ->map(fn ($name) => trim((string) $name))
            ->filter(fn ($name) => $name !== '')
            ->all();
    }

    /**
     * Resolve every faculty PK referenced by the week in one query, so cells can
     * be built without a lookup each.
     *
     * The printed sheet abbreviates in-house faculty ("MK", "GSM") but spells
     * guests out in full ("Narendra Bhooshan"), so each name resolves to its
     * abbreviation only when one is on record and the faculty is internal
     * (faculty_type 1). Everyone else keeps their full name.
     *
     * @return array<int, string>
     */
    private function resolveFacultyNames(iterable $events): array
    {
        $pks = [];
        foreach ($events as $event) {
            foreach ($this->decodeIdList($event->faculty_master ?? null) as $pk) {
                $pks[$pk] = true;
            }
            foreach ($this->decodeIdList($event->internal_faculty ?? null) as $pk) {
                $pks[$pk] = true;
            }
        }
        if (!$pks) {
            return [];
        }

        return DB::table('faculty_master')
            ->whereIn('pk', array_keys($pks))
            ->get(['pk', 'full_name', 'abbreviation', 'faculty_type'])
            ->mapWithKeys(function ($row) {
                $abbreviation = trim((string) ($row->abbreviation ?? ''));
                $isInternal   = (int) $row->faculty_type === self::FACULTY_TYPE_INTERNAL;

                $label = ($isInternal && $abbreviation !== '')
                    ? $abbreviation
                    : preg_replace('/\s+/', ' ', trim((string) $row->full_name));

                return [(int) $row->pk => $label];
            })
            ->filter(fn ($name) => $name !== '')
            ->all();
    }

    /**
     * Turn raw timetable rows into flat, fully-resolved grid items. A row that
     * carries break fields yields a second item for the break interval, so from
     * here on breaks travel through the same code path as everything else.
     */
    private function normaliseEvents(iterable $events, Carbon $weekStart, array $groupNames)
    {
        $weekEnd      = $weekStart->copy()->addDays(6)->endOfDay();
        $facultyNames = $this->resolveFacultyNames($events);
        $items        = collect();

        foreach ($events as $event) {
            $date = $this->parseDate($event->START_DATE ?? null);
            if (!$date || !$date->between($weekStart, $weekEnd)) {
                continue;
            }

            $groups  = $this->groupsFor($event, $groupNames);
            $faculty = $this->facultyFor($event, $facultyNames);

            [$start, $end] = $this->parseInterval((string) ($event->class_session ?? ''));
            if ($start !== null && $end !== null) {
                $items->push([
                    'day'      => $date->dayOfWeekIso,
                    'groups'   => $groups,
                    'start'    => $start,
                    'end'      => $end,
                    'title'    => trim((string) ($event->subject_topic ?? '')) ?: 'Session',
                    'faculty'  => $faculty,
                    'venue'    => trim((string) ($event->venue_name ?? '')),
                    'remarks'  => trim((string) ($event->remarks ?? '')),
                    'isBreak'  => false,
                ]);
            }

            $break = $this->breakItemFor($event, $date, $groups);
            if ($break) {
                $items->push($break);
            }
        }

        return $items;
    }

    /**
     * A break carried on an event row becomes an ordinary item whose title is the
     * break's own label. Its type string is printed as given rather than mapped
     * through a fixed tea/lunch/snacks list, so a new break type needs no code.
     */
    private function breakItemFor(object $event, Carbon $date, array $groups): ?array
    {
        $type = trim((string) ($event->break_type ?? ''));
        $isBreak = !empty($event->is_break) || $type !== '';
        if (!$isBreak) {
            return null;
        }

        $start = $this->parseTime($event->break_start_time ?? null);
        $end   = $this->parseTime($event->break_end_time ?? null);
        if ($start === null || $end === null || $end <= $start) {
            return null;
        }

        return [
            'day'     => $date->dayOfWeekIso,
            'groups'  => $groups,
            'start'   => $start,
            'end'     => $end,
            'title'   => $this->breakLabel($type),
            'faculty' => '',
            'venue'   => '',
            'remarks' => '',
            'isBreak' => true,
        ];
    }

    /** Title-case the stored break_type into a printable label ("tea" -> "Tea Break"). */
    private function breakLabel(string $type): string
    {
        $type = trim($type);
        if ($type === '') {
            return 'Break';
        }
        $label = ucwords(str_replace(['_', '-'], ' ', strtolower($type)));

        return str_ends_with(strtolower($label), 'break') ? $label : $label . ' Break';
    }

    /** @return string[] group labels this event belongs to, or [NO_GROUP] */
    private function groupsFor(object $event, array $groupNames): array
    {
        $labels = [];
        foreach ($this->decodeIdList($event->group_name ?? null) as $pk) {
            if (isset($groupNames[$pk])) {
                $labels[$groupNames[$pk]] = true;
            }
        }

        return $labels ? array_keys($labels) : [self::NO_GROUP];
    }

    private function facultyFor(object $event, array $facultyNames): string
    {
        $names = [];
        foreach ($this->decodeIdList($event->faculty_master ?? null) as $pk) {
            if (isset($facultyNames[$pk])) {
                $names[$facultyNames[$pk]] = true;
            }
        }
        foreach ($this->decodeIdList($event->internal_faculty ?? null) as $pk) {
            if (isset($facultyNames[$pk])) {
                $names[$facultyNames[$pk]] = true;
            }
        }

        return implode(', ', array_keys($names));
    }

    /**
     * Day columns are the days that carry events. A week with no Saturday session
     * prints no Saturday column; a week with one prints six columns.
     */
    private function resolveDays($items, Carbon $weekStart): array
    {
        $present = $items->pluck('day')->unique()->sort()->values();
        if ($present->isEmpty()) {
            $present = collect(range(1, 5));
        }

        return $present->map(function (int $iso) use ($weekStart) {
            $date = $weekStart->copy()->addDays($iso - 1);

            return [
                'key'   => $iso,
                'name'  => $date->format('l'),
                'label' => $date->format('d.m.Y'),
            ];
        })->all();
    }

    /**
     * The group axis is whatever group names the week's events carry, ordered so
     * that short plain labels (A, B) sort naturally and anything else falls in
     * behind them alphabetically.
     */
    private function resolveGroups($items): array
    {
        $names = $items->flatMap(fn ($i) => $i['groups'])->unique()->values()->all();
        if (!$names || $names === [self::NO_GROUP]) {
            return [self::NO_GROUP];
        }

        $names = array_values(array_filter($names, fn ($n) => $n !== self::NO_GROUP));
        natcasesort($names);

        return array_values($names);
    }

    /**
     * The week's distinct start/end instants cut the day into segments; each
     * segment that at least one item actually spans becomes a time row.
     *
     * Segments nothing spans are discarded rather than printed empty: the gap
     * between a session ending at 10:30 and the next starting at 10:40 is the
     * boundary between two rows, not a row of its own. Keeping them would print
     * a dead band at every changeover in the week.
     *
     * @return array<int, array{from: int, to: int}>
     */
    private function resolveSegments($items): array
    {
        $bounds = [];
        foreach ($items as $item) {
            $bounds[$item['start']] = true;
            $bounds[$item['end']]   = true;
        }
        $bounds = array_keys($bounds);
        sort($bounds, SORT_NUMERIC);

        $starts = $items->pluck('start')->unique()->flip();
        $ends   = $items->pluck('end')->unique()->flip();

        $segments = [];
        for ($i = 0; $i < count($bounds) - 1; $i++) {
            [$from, $to] = [$bounds[$i], $bounds[$i + 1]];

            // A segment earns a row only if something actually begins or finishes
            // on it. A segment that merely sits inside a longer session — the
            // 10:30-10:40 changeover under a 09:30-11:40 lecture — is absorbed by
            // that session's rowspan instead of printing as a dead band.
            if ($starts->has($from) || $ends->has($to)) {
                $segments[] = ['from' => $from, 'to' => $to];
            }
        }

        return $segments;
    }

    /**
     * Place every item onto the flattened (time x group) row space and emit the
     * physical rows the Blade iterates. Each emitted cell knows its own rowspan;
     * rows covered by a cell above are omitted entirely rather than rendered empty.
     */
    private function layout($items, array $days, array $groups, array $segments): array
    {
        $timeCount  = count($segments);
        $groupCount = count($groups);
        if ($timeCount === 0 || $groupCount === 0) {
            return [];
        }

        $groupIndex = array_flip($groups);
        $physical   = $timeCount * $groupCount;

        // occupancy[dayKey][physicalRow] = [
        //     'cell'      => array|null,  the cell anchored here, if any
        //     'covered'   => bool,        spanned by a cell anchored above
        //     'coveredBy' => ?int,        which physical row that cell is anchored at
        // ]
        $occupancy = [];
        foreach ($days as $day) {
            $occupancy[$day['key']] = array_fill(0, $physical, [
                'cell' => null, 'covered' => false, 'coveredBy' => null,
            ]);
        }

        foreach ($items as $item) {
            if (!isset($occupancy[$item['day']])) {
                continue;
            }

            // Every segment the item covers. Segments are ordered and disjoint, so
            // the indices this yields are contiguous.
            $covers = [];
            foreach ($segments as $t => $segment) {
                if ($segment['from'] >= $item['start'] && $segment['to'] <= $item['end']) {
                    $covers[] = $t;
                }
            }
            if (!$covers) {
                continue;
            }

            $targets = $this->groupIndicesFor($item['groups'], $groupIndex, $groupCount);
            if (!$targets) {
                continue;
            }

            $rows = [];
            foreach ($covers as $t) {
                foreach ($targets as $g) {
                    $rows[] = $t * $groupCount + $g;
                }
            }
            sort($rows, SORT_NUMERIC);

            foreach ($this->contiguousRuns($rows) as $run) {
                $top  = $run[0];
                $span = count($run);

                if ($occupancy[$item['day']][$top]['cell'] === null) {
                    $occupancy[$item['day']][$top]['cell'] = [
                        'rowspan' => $span,
                        'isBreak' => $item['isBreak'],
                        'events'  => [],
                    ];
                } else {
                    // Two items share this slot (parallel sessions for the same
                    // group). Keep the taller span so neither gets clipped.
                    $occupancy[$item['day']][$top]['cell']['rowspan'] =
                        max($occupancy[$item['day']][$top]['cell']['rowspan'], $span);
                    $occupancy[$item['day']][$top]['cell']['isBreak'] =
                        $occupancy[$item['day']][$top]['cell']['isBreak'] && $item['isBreak'];
                }

                $occupancy[$item['day']][$top]['cell']['events'][] = [
                    'title'   => $item['title'],
                    'faculty' => $item['faculty'],
                    'venue'   => $item['venue'],
                    'remarks' => $item['remarks'],
                    'isBreak' => $item['isBreak'],
                ];

                foreach (array_slice($run, 1) as $covered) {
                    $occupancy[$item['day']][$covered]['covered']   = true;
                    $occupancy[$item['day']][$covered]['coveredBy'] = $top;
                }
            }
        }

        return $this->emitRows($occupancy, $days, $groups, $segments, $timeCount, $groupCount);
    }

    /**
     * Emit the physical rows the Blade iterates.
     *
     * A time row whose every occupied cell — across all days and all groups —
     * holds the same single break collapses to one full-width band row. That is a
     * consequence of coverage rather than a rule about breaks: any event with the
     * same reach would band identically. A break that only some groups take is not
     * cohort-wide and stays an ordinary cell.
     */
    private function emitRows(array $occupancy, array $days, array $groups, array $segments, int $timeCount, int $groupCount): array
    {
        $rows = [];

        for ($t = 0; $t < $timeCount; $t++) {
            $band = $this->bandFor($occupancy, $days, $t, $groupCount);

            if ($band !== null) {
                $rows[] = [
                    'timeIndex'   => $t,
                    'groupIndex'  => 0,
                    'showTime'    => true,
                    'timeRowspan' => 1,
                    'from'        => $this->formatMinutes($segments[$t]['from']),
                    'to'          => $this->formatMinutes($segments[$t]['to']),
                    'group'       => null,
                    'band'        => $band,
                    'cells'       => [],
                ];
                continue;
            }

            for ($g = 0; $g < $groupCount; $g++) {
                $physicalRow = $t * $groupCount + $g;

                $cells = [];
                foreach ($days as $day) {
                    $slot = $occupancy[$day['key']][$physicalRow];
                    $cells[$day['key']] = $slot['covered'] ? null : ($slot['cell'] ?? [
                        'rowspan' => 1,
                        'isBreak' => false,
                        'events'  => [],
                    ]);
                }

                $rows[] = [
                    'timeIndex'   => $t,
                    'groupIndex'  => $g,
                    // The time cell is emitted once per time row and spans its groups.
                    'showTime'    => $g === 0,
                    'timeRowspan' => $groupCount,
                    'from'        => $this->formatMinutes($segments[$t]['from']),
                    'to'          => $this->formatMinutes($segments[$t]['to']),
                    'group'       => $groups[$g] === self::NO_GROUP ? null : $groups[$g],
                    'band'        => null,
                    'cells'       => $cells,
                ];
            }
        }

        return $rows;
    }

    /**
     * The band label for time row $t, or null if it is not a band.
     *
     * Cells left empty by a day that simply has nothing scheduled do not veto the
     * band — a tea break is still cohort-wide when one group's afternoon is free.
     * A single non-break event anywhere in the row does veto it.
     *
     * A break reaching outside this time row also vetoes it. Banding row t means
     * emitting one <tr> in place of the row's real cells, so a cell that expected
     * to span into t+1 would lose the rows its rowspan was counting on and drag
     * the rest of the grid up with it.
     */
    private function bandFor(array $occupancy, array $days, int $t, int $groupCount): ?string
    {
        $lo    = $t * $groupCount;
        $hi    = $lo + $groupCount;      // exclusive
        $label = null;
        $seen  = false;

        for ($row = $lo; $row < $hi; $row++) {
            foreach ($days as $day) {
                $slot = $occupancy[$day['key']][$row];

                if ($slot['covered']) {
                    // Only tolerable when the covering cell also lives in this row.
                    if ($slot['coveredBy'] === null || $slot['coveredBy'] < $lo) {
                        return null;
                    }
                    continue;
                }
                if ($slot['cell'] === null) {
                    continue;
                }
                if ($row + $slot['cell']['rowspan'] > $hi) {
                    return null;
                }

                foreach ($slot['cell']['events'] as $event) {
                    if (!$event['isBreak']) {
                        return null;
                    }
                    if ($label !== null && $event['title'] !== $label) {
                        return null;
                    }
                    $label = $event['title'];
                    $seen  = true;
                }
            }
        }

        return $seen ? $label : null;
    }

    /**
     * Which rows of the group axis an item occupies.
     *
     * An item carrying no group mapping applies to the whole cohort, so in a week
     * that does have a group axis it spans every group rather than landing against
     * whichever one happens to sort first.
     *
     * @param  string[]  $names
     * @return int[]
     */
    private function groupIndicesFor(array $names, array $groupIndex, int $groupCount): array
    {
        if (in_array(self::NO_GROUP, $names, true)) {
            return range(0, $groupCount - 1);
        }

        $indices = [];
        foreach ($names as $name) {
            if (isset($groupIndex[$name])) {
                $indices[] = $groupIndex[$name];
            }
        }

        return array_values(array_unique($indices));
    }

    /** @return int[][] maximal runs of consecutive integers */
    private function contiguousRuns(array $sorted): array
    {
        $runs    = [];
        $current = [];
        foreach ($sorted as $n) {
            if (!$current || $n === end($current) + 1) {
                $current[] = $n;
                continue;
            }
            $runs[]  = $current;
            $current = [$n];
        }
        if ($current) {
            $runs[] = $current;
        }

        return $runs;
    }

    private function resolveWeekNumber(Carbon $weekStart, ?object $course): int
    {
        if ($course && !empty($course->start_year)) {
            $courseMonday = Carbon::parse($course->start_year)->startOfWeek(Carbon::MONDAY);
            $relative     = intdiv((int) $courseMonday->diffInDays($weekStart, false), 7) + 1;
            if ($relative >= 1) {
                return $relative;
            }
        }

        return $weekStart->isoWeek;
    }

    private function resolveRangeLabel(Carbon $weekStart, array $days): string
    {
        if (!$days) {
            return '';
        }
        $first = $weekStart->copy()->addDays(reset($days)['key'] - 1);
        $last  = $weekStart->copy()->addDays(end($days)['key'] - 1);

        return $first->format('d.m.Y') . ' to ' . $last->format('d.m.Y');
    }

    /**
     * class_session is free text in two shapes: "09:00 to 10:25" (shift-derived)
     * and "02:00 PM - 05:00 PM" (custom). Both are accepted; anything else yields
     * nulls and the event is dropped from the timed grid rather than mis-placed.
     *
     * @return array{0: ?int, 1: ?int} minutes-of-day
     */
    private function parseInterval(string $slot): array
    {
        $parts = preg_split('/\s+to\s+|\s*[-–—]\s*/iu', trim($slot), 2);
        if (count($parts) < 2) {
            return [null, null];
        }

        $start = $this->parseTime($parts[0]);
        $end   = $this->parseTime($parts[1]);
        if ($start === null || $end === null || $end <= $start) {
            return [null, null];
        }

        return [$start, $end];
    }

    /** Parse "09:30", "9:30 AM", "02:00 PM" into minutes-of-day. */
    private function parseTime(?string $time): ?int
    {
        $time = trim((string) $time);
        if ($time === '') {
            return null;
        }
        if (!preg_match('/^(\d{1,2}):(\d{2})(?:\s*([AaPp])\.?[Mm]\.?)?$/', $time, $m)) {
            return null;
        }

        $hour   = (int) $m[1];
        $minute = (int) $m[2];
        if ($hour > 23 || $minute > 59) {
            return null;
        }

        if (isset($m[3])) {
            $meridiem = strtolower($m[3]);
            if ($meridiem === 'p' && $hour !== 12) {
                $hour += 12;
            }
            if ($meridiem === 'a' && $hour === 12) {
                $hour = 0;
            }
        }

        return $hour * 60 + $minute;
    }

    private function formatMinutes(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    private function parseDate($value): ?Carbon
    {
        if (empty($value)) {
            return null;
        }
        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * timetable stores id lists as a JSON array, a comma string, or a bare scalar
     * depending on when the row was written. Accept all three.
     *
     * @return int[]
     */
    private function decodeIdList($raw): array
    {
        if (empty($raw)) {
            return [];
        }

        $decoded = json_decode((string) $raw, true);
        if (!is_array($decoded)) {
            $decoded = explode(',', (string) $raw);
        }

        return array_values(array_filter(array_map('intval', $decoded)));
    }
}
