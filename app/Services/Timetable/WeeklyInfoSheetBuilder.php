<?php

namespace App\Services\Timetable;

use App\Models\FacultyMaster;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Builds the info sheet printed on the back of the weekly timetable (the
 * "P.T.O." page) - Cadre Counsellors, abbreviation legends, language venues,
 * outdoor activities, guest speakers and the signatory block.
 *
 * Sections split by where their data lives:
 *  - Derived from masters: venue abbreviations (venue_master), cadre counsellors
 *    (Counsellor Groups), faculty abbreviations and guest speakers (faculty_master).
 *  - Held per course + week on course_week_notes, because no master models them:
 *    each counsellor's printed label, room and cadre wording, the session
 *    moderator against a speaker, language-class venues, the outdoor block and
 *    the signatory.
 *
 * A section with no data yields an empty list and the view omits it.
 */
class WeeklyInfoSheetBuilder
{
    /** faculty_master.faculty_type: 1 = Internal, 2 = Guest, 3 = Research. */
    private const FACULTY_TYPE_INTERNAL = 1;
    private const FACULTY_TYPE_GUEST    = 2;

    /**
     * @param  iterable  $weekEvents  the same timetable rows the grid was built from
     */
    public function build(iterable $weekEvents, ?object $course, ?object $notes): array
    {
        $meta        = $this->decodeMap($notes->counsellor_meta ?? null);
        $moderators  = $this->decodeMap($notes->guest_moderators ?? null);
        $weekPks     = $this->facultyPksIn($weekEvents);
        $weekFaculty = $this->facultyFor($weekPks);

        return [
            'counsellors'          => $this->counsellors($course, $meta),
            'facultyLegend'        => $this->facultyLegend($this->decodeList($notes->faculty_legend_order ?? null)),
            'venueLegend'          => $this->storedVenueLegend($notes) ?: $this->venueLegend($weekEvents),
            'guestSpeakers'        => $this->guestSpeakers($weekPks, $weekFaculty, $moderators),
            'languageVenues'       => $this->languageVenues($notes),
            'outdoorActivities'    => trim((string) ($notes->outdoor_activities ?? '')),
            'signatoryName'        => trim((string) ($notes->signatory_name ?? '')),
            'signatoryDesignation' => trim((string) ($notes->signatory_designation ?? '')),
            'signatoryDate'        => !empty($notes->signatory_date)
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

    /**
     * Faculty named by the week's sessions, in the order they first teach - the
     * order the circulated sheet numbers its guest speakers in.
     *
     * @return int[]
     */
    public function facultyPksIn(iterable $events): array
    {
        $rows = collect($events)->sortBy(fn ($e) => sprintf(
            '%s %05d',
            (string) ($e->START_DATE ?? ''),
            $this->startMinutes((string) ($e->class_session ?? ''))
        ));

        $pks = [];
        foreach ($rows as $event) {
            foreach (['faculty_master', 'internal_faculty'] as $field) {
                foreach ($this->decodeIdList($event->{$field} ?? null) as $pk) {
                    $pks[$pk] = true;
                }
            }
        }

        return array_keys($pks);
    }

    private function startMinutes(string $slot): int
    {
        if (preg_match('/(\d{1,2})[:.](\d{2})\s*(AM|PM)?/i', $slot, $m)) {
            $h = (int) $m[1];
            if (!empty($m[3])) {
                $pm = strtoupper($m[3]) === 'PM';
                $h  = $h % 12 + ($pm ? 12 : 0);
            }
            return $h * 60 + (int) $m[2];
        }

        return 99999;
    }

    private function facultyFor(array $pks)
    {
        if (!$pks) {
            return collect();
        }

        return DB::table('faculty_master')
            ->whereIn('pk', $pks)
            ->orderBy('full_name')
            ->get(['pk', 'full_name', 'faculty_code', FacultyMaster::abbreviationSelect(), 'faculty_type',
                   'current_designation', 'current_department'])
            ->keyBy('pk');
    }

    /**
     * Counsellor Groups map a faculty member to the cadres they counsel. Several
     * cadres share one counsellor on the printed sheet, so rows are keyed by
     * faculty and their cadres joined - "AGMUT/Assam Meghalaya/Bhutan". The week
     * may override the printed label, room and cadre wording.
     */
    private function counsellors(?object $course, array $meta): array
    {
        if (!$course || empty($course->pk)) {
            return [];
        }

        $rows = DB::table('group_type_master_course_master_map as g')
            ->join('faculty_master as f', 'g.facility_id', '=', 'f.pk')
            // course_group_type_master.pk of the Counsellor Group.
            ->where('g.type_name', (int) config('timetable.counsellor_group_type', 8))
            ->where('g.course_name', $course->pk)
            ->where('g.active_inactive', 1)
            ->orderBy('g.group_name')
            ->get(['g.group_name', 'f.pk as faculty_pk', 'f.full_name', FacultyMaster::abbreviationSelect('f')]);

        $byFaculty = [];
        foreach ($rows as $row) {
            $pk = (int) $row->faculty_pk;
            $byFaculty[$pk]['cadres'][]     = $this->squish($row->group_name);
            $byFaculty[$pk]['fullName']     = $this->squish($row->full_name);
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
                'cadres' => trim((string) ($stored['cadres'] ?? '')) ?: implode('/', $row['cadres']),
                'venue'  => trim((string) ($stored['venue'] ?? '')),
                'order'  => (int) ($stored['order'] ?? 0),
            ];
        }

        return $this->inPrintedOrder($out);
    }

    /**
     * The sheet lists counsellors by seniority, which no master records, so a
     * week may number them; numbered rows come first, the rest keep cadre order.
     */
    private function inPrintedOrder(array $rows): array
    {
        $keyed = [];
        foreach ($rows as $i => $row) {
            $keyed[] = [$row['order'] > 0 ? $row['order'] : PHP_INT_MAX, $i, $row];
        }
        usort($keyed, fn ($a, $b) => [$a[0], $a[1]] <=> [$b[0], $b[1]]);

        return array_map(function ($k) {
            unset($k[2]['order']);
            return $k[2];
        }, $keyed);
    }

    /**
     * "AK : Aakanksha Kulshrestha (INT-00005)" for every in-house faculty member
     * with a curated code. The issued sheet prints the academy's whole legend,
     * not only the codes this week happens to use - the codes are academy-wide.
     * It lists them by seniority, which no master records, so the week may give
     * the printed order; codes it leaves out follow alphabetically.
     */
    private function facultyLegend(array $order = []): array
    {
        if (!FacultyMaster::hasAbbreviationColumn()) {
            return [];
        }
        $rank = array_flip(array_map('strtoupper', $order));

        return DB::table('faculty_master')
            ->where('faculty_type', self::FACULTY_TYPE_INTERNAL)
            ->where('active_inactive', 1)
            ->whereNotNull('abbreviation')
            ->where('abbreviation', '<>', '')
            ->get(['full_name', 'faculty_code', 'abbreviation'])
            ->map(fn ($f) => [
                'abbreviation' => trim((string) $f->abbreviation),
                'name'         => $this->squish($f->full_name),
                'code'         => trim((string) ($f->faculty_code ?? '')),
            ])
            ->sortBy(fn ($f) => [$rank[strtoupper($f['abbreviation'])] ?? PHP_INT_MAX, $f['abbreviation']])
            ->values()
            ->all();
    }

    /**
     * "VH: Vivekananda Hall" - venue_master carries both halves.
     *
     * Only the venues this week uses are listed: venue_master holds every space
     * ever booked, so an unfiltered legend runs to pages.
     */
    private function venueLegend(iterable $weekEvents): array
    {
        $used = [];
        foreach ($weekEvents as $event) {
            $short = trim((string) ($event->venue_short_name ?? ''));
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
     * Guests teaching this week - plus any in-house speaker the week records a
     * session moderator for, as the sheet lists those too - in teaching order,
     * with their designation sentence and moderator. A guest with no
     * designation on file still lists: a missing sentence beats a missing speaker.
     */
    private function guestSpeakers(array $order, $faculty, array $moderators): array
    {
        // The issued sheet's box is the list of moderated sessions: once a week
        // records moderators, a speaker without one - a panellist, say - is not
        // listed. A week with none recorded lists every guest.
        $moderatedOnly = (bool) array_filter($moderators, fn ($m) => trim((string) $m) !== '');

        $out = [];
        foreach ($order as $pk) {
            $f = $faculty->get($pk);
            if (!$f) {
                continue;
            }
            $moderator = trim((string) ($moderators[$pk] ?? $moderators[(string) $pk] ?? ''));
            if ($moderator === '' && ($moderatedOnly || (int) $f->faculty_type !== self::FACULTY_TYPE_GUEST)) {
                continue;
            }

            $parts = array_filter([
                trim((string) ($f->current_designation ?? ''), " \t\n\r\0\x0B,"),
                trim((string) ($f->current_department ?? ''), " \t\n\r\0\x0B,"),
            ]);

            $out[] = [
                'name'        => $this->squish($f->full_name),
                'code'        => trim((string) ($f->faculty_code ?? '')),
                'designation' => implode(', ', $parts),
                'moderator'   => $moderator,
            ];
        }

        return $out;
    }

    /**
     * The "Venues Abbreviation" box as stored for the week, when the sheet prints
     * the academy's standard list rather than only the venues the week uses.
     *
     * @return array<int, array{abbreviation: string, name: string}>
     */
    private function storedVenueLegend(?object $notes): array
    {
        $rows = json_decode((string) ($notes->venue_legend ?? ''), true);
        if (!is_array($rows)) {
            return [];
        }

        $out = [];
        foreach ($rows as $row) {
            $abbr = trim((string) ($row['abbreviation'] ?? ''));
            if ($abbr !== '') {
                $out[] = ['abbreviation' => $abbr, 'name' => trim((string) ($row['name'] ?? ''))];
            }
        }

        return $out;
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

    /** Collapse runs of whitespace, including the non-breaking spaces in master names. */
    private function squish($value): string
    {
        return trim(preg_replace('/[\s\x{00A0}]+/u', ' ', (string) $value));
    }

    /** @return string[] a stored JSON list, or a comma-separated one */
    private function decodeList($raw): array
    {
        $decoded = json_decode((string) $raw, true);
        if (!is_array($decoded)) {
            $decoded = explode(',', (string) $raw);
        }

        return array_values(array_filter(array_map(fn ($v) => trim((string) $v), $decoded), fn ($v) => $v !== ''));
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
