<?php

namespace App\Services\Timetable;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Builds the info sheet printed on the back of the weekly timetable — the
 * Cadre Counsellors, abbreviation legends, language venues, outdoor activities,
 * guest speakers and signatory block.
 *
 * Sections split by where their data lives:
 *  - Derived from masters: venue abbreviations (venue_master), cadre counsellors
 *    (Counsellor Groups), faculty abbreviations and guest speakers (faculty_master).
 *  - Held per course + week on course_week_notes, because no master models them:
 *    each counsellor's role label and room, the session moderator against a guest,
 *    language-class venues, the outdoor block and the signatory.
 *
 * Anything with no data simply yields an empty section, and the view omits it —
 * a week that has no guests prints no Guest Speakers box.
 */
class WeeklyInfoSheetBuilder
{
    /** faculty_master.faculty_type: 1 = Internal, 2 = Guest, 3 = Research. */
    private const FACULTY_TYPE_INTERNAL = 1;
    private const FACULTY_TYPE_GUEST    = 2;

    /** course_group_type_master.pk for the Counsellor Group. */
    private const GROUP_TYPE_COUNSELLOR = 8;

    /**
     * @param  iterable  $weekEvents  the same timetable rows the grid was built from
     */
    public function build(iterable $weekEvents, ?object $course, ?object $notes): array
    {
        $meta        = $this->decodeMap($notes->counsellor_meta ?? null);
        $counsellors = $this->counsellors($course, $meta);

        $weekFaculty = $this->facultyFor($this->facultyPksIn($weekEvents));

        // The legend has to cover every abbreviation the sheet prints, which is
        // the week's faculty plus the cadre counsellors listed above it —
        // otherwise the counsellor column shows "KP" with nothing explaining it.
        $legendFaculty = $this->facultyFor(array_unique(array_merge(
            $this->facultyPksIn($weekEvents),
            $this->counsellorFacultyPks($course),
        )));

        return [
            'counsellors'         => $counsellors,
            'facultyLegend'       => $this->facultyLegend($legendFaculty),
            'venueLegend'         => $this->venueLegend($weekEvents),
            'guestSpeakers'       => $this->guestSpeakers($weekFaculty, $this->decodeMap($notes->guest_moderators ?? null)),
            'languageVenues'      => $this->languageVenues($notes),
            'outdoorActivities'   => trim((string) ($notes->outdoor_activities ?? '')),
            'signatoryName'       => trim((string) ($notes->signatory_name ?? '')),
            'signatoryDesignation'=> trim((string) ($notes->signatory_designation ?? '')),
            'signatoryDate'       => !empty($notes->signatory_date)
                ? Carbon::parse($notes->signatory_date)->format('jS F, Y')
                : '',
        ];
    }

    /** True when the sheet would print nothing at all. */
    public function isEmpty(array $sheet): bool
    {
        foreach (['counsellors', 'facultyLegend', 'venueLegend', 'guestSpeakers', 'languageVenues'] as $section) {
            if (!empty($sheet[$section])) {
                return false;
            }
        }

        return $sheet['outdoorActivities'] === '' && $sheet['signatoryName'] === '';
    }

    /** @return int[] */
    private function facultyPksIn(iterable $events): array
    {
        $pks = [];
        foreach ($events as $event) {
            foreach (['faculty_master', 'internal_faculty'] as $field) {
                foreach ($this->decodeIdList($event->{$field} ?? null) as $pk) {
                    $pks[$pk] = true;
                }
            }
        }

        return array_keys($pks);
    }

    /** @return int[] faculty who counsel a cadre on this course */
    private function counsellorFacultyPks(?object $course): array
    {
        if (!$course || empty($course->pk)) {
            return [];
        }

        return DB::table('group_type_master_course_master_map')
            ->where('type_name', self::GROUP_TYPE_COUNSELLOR)
            ->where('course_name', $course->pk)
            ->where('active_inactive', 1)
            ->whereNotNull('facility_id')
            ->pluck('facility_id')
            ->map(fn ($pk) => (int) $pk)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function facultyFor(array $pks)
    {
        if (!$pks) {
            return collect();
        }

        return DB::table('faculty_master')
            ->whereIn('pk', $pks)
            ->orderBy('full_name')
            ->get(['pk', 'full_name', 'faculty_code', 'abbreviation', 'faculty_type',
                   'current_designation', 'current_department']);
    }

    /**
     * Counsellor Groups already map a faculty member to the cadres they counsel.
     * Several cadres share one counsellor on the printed sheet, so rows are keyed
     * by faculty and their cadres joined — matching "AGMUT/Assam Meghalaya/Bhutan".
     */
    private function counsellors(?object $course, array $meta): array
    {
        if (!$course || empty($course->pk)) {
            return [];
        }

        $rows = DB::table('group_type_master_course_master_map as g')
            ->join('faculty_master as f', 'g.facility_id', '=', 'f.pk')
            ->where('g.type_name', self::GROUP_TYPE_COUNSELLOR)
            ->where('g.course_name', $course->pk)
            ->where('g.active_inactive', 1)
            ->orderBy('g.group_name')
            ->get(['g.group_name', 'f.pk as faculty_pk', 'f.full_name', 'f.abbreviation']);

        $byFaculty = [];
        foreach ($rows as $row) {
            $pk = (int) $row->faculty_pk;
            $byFaculty[$pk]['cadres'][]   = trim((string) $row->group_name);
            $byFaculty[$pk]['fullName']   = preg_replace('/\s+/', ' ', trim((string) $row->full_name));
            $byFaculty[$pk]['abbreviation'] = trim((string) ($row->abbreviation ?? ''));
        }

        $out = [];
        foreach ($byFaculty as $pk => $row) {
            $stored = $meta[$pk] ?? $meta[(string) $pk] ?? [];

            $out[] = [
                // "JD(SW)" has no master behind it; fall back to the abbreviation,
                // then the full name, so the row is never blank.
                'label'  => trim((string) ($stored['label'] ?? ''))
                    ?: ($row['abbreviation'] ?: $row['fullName']),
                'cadres' => implode('/', $row['cadres']),
                'venue'  => trim((string) ($stored['venue'] ?? '')),
            ];
        }

        return $out;
    }

    /** "AK : Aakanksha Kulshrestha (INT-00005)" for in-house faculty with a code. */
    private function facultyLegend($faculty): array
    {
        return $faculty
            ->filter(fn ($f) => (int) $f->faculty_type === self::FACULTY_TYPE_INTERNAL)
            ->filter(fn ($f) => trim((string) ($f->abbreviation ?? '')) !== '')
            ->map(fn ($f) => [
                'abbreviation' => trim((string) $f->abbreviation),
                'name'         => preg_replace('/\s+/', ' ', trim((string) $f->full_name)),
                'code'         => trim((string) ($f->faculty_code ?? '')),
            ])
            ->sortBy('abbreviation')
            ->values()
            ->all();
    }

    /**
     * "VH: Vivekananda Hall" — venue_master already carries both halves.
     *
     * Only the venues this week actually uses are listed. venue_master holds
     * every space the academy has ever booked (hotels, DRDO, a school garden),
     * so an unfiltered legend runs to three pages of rooms nobody on this sheet
     * will visit.
     */
    private function venueLegend(iterable $weekEvents): array
    {
        $used = [];
        foreach ($weekEvents as $event) {
            $short = trim((string) ($event->venue_name ?? ''));
            if ($short !== '') {
                $used[$short] = true;
            }
        }
        if (!$used) {
            return [];
        }

        return DB::table('venue_master')
            ->whereIn('venue_short_name', array_keys($used))
            ->orderBy('venue_short_name')
            ->get(['venue_short_name', 'venue_name'])
            ->map(fn ($v) => [
                'abbreviation' => trim((string) $v->venue_short_name),
                'name'         => trim((string) $v->venue_name),
            ])
            ->unique('abbreviation')
            ->values()
            ->all();
    }

    /**
     * Guests teaching this week, with their designation sentence and the session
     * moderator recorded against them. Guests with no designation on file still
     * list — a missing sentence is better than a missing speaker.
     */
    private function guestSpeakers($faculty, array $moderators): array
    {
        return $faculty
            ->filter(fn ($f) => (int) $f->faculty_type === self::FACULTY_TYPE_GUEST)
            ->map(function ($f) use ($moderators) {
                $pk    = (int) $f->pk;
                $parts = array_filter([
                    trim((string) ($f->current_designation ?? '')),
                    trim((string) ($f->current_department ?? '')),
                ]);

                return [
                    'name'        => preg_replace('/\s+/', ' ', trim((string) $f->full_name)),
                    'code'        => trim((string) ($f->faculty_code ?? '')),
                    'designation' => implode(', ', $parts),
                    'moderator'   => trim((string) ($moderators[$pk] ?? $moderators[(string) $pk] ?? '')),
                ];
            })
            ->values()
            ->all();
    }

    /** @return array<int, array{language: string, venue: string}> */
    private function languageVenues(?object $notes): array
    {
        $rows = json_decode((string) ($notes->language_venues ?? ''), true);
        if (!is_array($rows)) {
            return [];
        }

        $out = [];
        foreach ($rows as $row) {
            $language = trim((string) ($row['language'] ?? ''));
            $venue    = trim((string) ($row['venue'] ?? ''));
            if ($language !== '') {
                $out[] = ['language' => $language, 'venue' => $venue];
            }
        }

        return $out;
    }

    private function decodeMap($raw): array
    {
        $decoded = json_decode((string) $raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    /** @return int[] */
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
