<?php

namespace App\Services\Attendance;

use App\Models\ClassSessionMaster;
use App\Models\MDOEscotDutyMap;
use App\Models\StudentMedicalExemption;
use App\Models\Timetable;

/**
 * Resolves whether an Officer Trainee is on duty or exempt for one timetabled
 * session, and which duty/exemption it is.
 *
 * An OT carrying any of the four is never marked absent: the mark-attendance
 * screen defaults them to Present and locks the status, and
 * AttendanceController::save enforces the same rule server-side. The page's JS
 * only mirrors that — it is not what makes the rule hold.
 *
 * One instance per (course, timetable). The timetable row, the duty-type PKs
 * and every per-student verdict are resolved once and cached: the callers run
 * these checks per student per rendered row, and getMdoDutyTypes() alone costs
 * three queries each time it is asked.
 */
class OtExemptionResolver
{
    public const MDO = 'MDO Duty';
    public const ESCORT = 'Escort/Moderator Duty';
    public const MEDICAL = 'Medical Exemption';
    public const OTHER = 'Other Duty';

    /** Checked in this order, so reasonFor() names the most specific duty first. */
    private const DUTY_LABELS = [
        'mdo' => self::MDO,
        'escort' => self::ESCORT,
        'other' => self::OTHER,
    ];

    private bool $timetableLoaded = false;
    private ?Timetable $timetable = null;
    private ?array $dutyTypes = null;

    /** [studentId][cacheKey] => bool|string|null */
    private array $cache = [];

    public function __construct(
        private int $coursePk,
        private int $timetablePk
    ) {}

    /** MDO duty overlapping this session. The OT is on duty but still attending. */
    public function hasMdo(int $studentId): bool
    {
        return $this->hasDuty($studentId, 'mdo');
    }

    /** Escort/Moderator duty overlapping this session — also counted as attending. */
    public function hasEscort(int $studentId): bool
    {
        return $this->hasDuty($studentId, 'escort');
    }

    public function hasOther(int $studentId): bool
    {
        return $this->hasDuty($studentId, 'other');
    }

    public function hasMedical(int $studentId): bool
    {
        return $this->remember($studentId, 'medical', fn () => $this->resolveMedical($studentId));
    }

    /**
     * The duty/exemption that locks this OT's status, or null if there is none.
     * Used both for the lock itself and for the message shown to the marker.
     */
    public function reasonFor(int $studentId): ?string
    {
        return $this->remember($studentId, 'reason', function () use ($studentId) {
            foreach (array_keys(self::DUTY_LABELS) as $key) {
                if ($this->hasDuty($studentId, $key)) {
                    return self::DUTY_LABELS[$key];
                }
            }

            return $this->hasMedical($studentId) ? self::MEDICAL : null;
        });
    }

    public function isExempt(int $studentId): bool
    {
        return $this->reasonFor($studentId) !== null;
    }

    /**
     * Batch counterpart to {@see isExempt()}: which of the given saved sessions this
     * OT is on duty or exempt for.
     *
     * One instance answers one session for ~5 queries. A caller re-checking every
     * saved row an OT has (the profile attendance summary, where a session covered
     * by a duty must count as Present even though the row still holds the Late or
     * Absent it was marked with) would pay that per row. This resolves the whole
     * set with a fixed number of queries and the same rules.
     *
     * @param  array<int, array{course: int, timetable: int}> $sessions
     * @return array<string, true> "course|timetable" keys, covered sessions only
     */
    public static function coveredSessionsFor(int $studentId, array $sessions): array
    {
        if (empty($sessions)) {
            return [];
        }

        $timetablePks = array_values(array_unique(array_column($sessions, 'timetable')));
        $coursePks = array_values(array_unique(array_column($sessions, 'course')));

        $timetables = Timetable::select('pk', 'START_DATE', 'class_session')
            ->whereIn('pk', $timetablePks)
            ->get()
            ->keyBy('pk');

        $dates = $timetables->pluck('START_DATE')->filter()
            ->map(fn ($d) => substr((string) $d, 0, 10))
            ->unique()->values()->all();

        if (empty($dates)) {
            return [];
        }

        $dutyTypes = array_filter(MDOEscotDutyMap::getMdoDutyTypes());

        // Grouped by type|course|date so the per-session lookup below is a hash hit.
        $duties = empty($dutyTypes) ? collect() : MDOEscotDutyMap::where('selected_student_list', $studentId)
            ->whereIn('course_master_pk', $coursePks)
            ->whereIn('mdo_duty_type_master_pk', array_values($dutyTypes))
            ->where(function ($q) use ($dates) {
                // Full-day ranges, so the index on mdo_date is not defeated by a cast.
                foreach ($dates as $d) {
                    $q->orWhereBetween('mdo_date', [$d . ' 00:00:00', $d . ' 23:59:59']);
                }
            })
            ->get()
            ->groupBy(fn ($r) => $r->mdo_duty_type_master_pk . '|' . $r->course_master_pk . '|' . substr((string) $r->mdo_date, 0, 10));

        $medicals = StudentMedicalExemption::where('student_master_pk', $studentId)
            ->whereIn('course_master_pk', $coursePks)
            ->where('active_inactive', 1)
            ->get();

        $covered = [];

        foreach ($sessions as $session) {
            $key = $session['course'] . '|' . $session['timetable'];
            if (isset($covered[$key])) {
                continue;
            }

            $timetable = $timetables->get($session['timetable']);
            if (!$timetable || empty($timetable->START_DATE)) {
                continue;
            }

            $date = substr((string) $timetable->START_DATE, 0, 10);
            $window = self::windowFor($timetable->START_DATE, $timetable->class_session);

            foreach ($dutyTypes as $typePk) {
                $group = $duties->get($typePk . '|' . $session['course'] . '|' . $date);
                $duty = $group ? $group->first() : null;

                if ($duty && self::overlapsSession($timetable->class_session, $duty->Time_from, $duty->Time_to)) {
                    $covered[$key] = true;
                    continue 2;
                }
            }

            foreach ($medicals as $exemption) {
                $inRange = (string) $exemption->course_master_pk === (string) $session['course']
                    && substr((string) $exemption->from_date, 0, 10) <= $date
                    && (empty($exemption->to_date) || substr((string) $exemption->to_date, 0, 10) >= $date);

                if ($inRange && self::medicalCovers($exemption, $window)) {
                    $covered[$key] = true;
                    continue 2;
                }
            }
        }

        return $covered;
    }

    private function hasDuty(int $studentId, string $key): bool
    {
        return $this->remember($studentId, 'duty:' . $key, function () use ($studentId, $key) {
            $timetable = $this->timetable();
            $typePk = $this->dutyTypes()[$key] ?? null;

            if (!$timetable || empty($typePk)) {
                return false;
            }

            $duty = MDOEscotDutyMap::where([
                ['course_master_pk', '=', $this->coursePk],
                ['mdo_duty_type_master_pk', '=', $typePk],
                ['selected_student_list', '=', $studentId],
            ])->whereDate('mdo_date', '=', $timetable->START_DATE)->first();

            return $duty && self::overlapsSession($timetable->class_session, $duty->Time_from, $duty->Time_to);
        });
    }

    private function resolveMedical(int $studentId): bool
    {
        $timetable = $this->timetable();
        if (!$timetable) {
            return false;
        }

        $date = $timetable->START_DATE;

        $exemption = StudentMedicalExemption::where([
            ['course_master_pk', '=', $this->coursePk],
            ['student_master_pk', '=', $studentId],
            ['active_inactive', '=', 1],
        ])
            ->whereDate('from_date', '<=', $date)
            ->where(function ($q) use ($date) {
                // An open-ended exemption (no to_date) has not expired.
                $q->whereNull('to_date')->orWhereDate('to_date', '>=', $date);
            })
            ->first();

        return $exemption ? self::medicalCovers($exemption, $this->sessionWindow()) : false;
    }

    /**
     * Whether a medical exemption already known to span this session's DATE also
     * covers the session itself. Shared with {@see coveredSessionsFor()} so the
     * batch path and the per-session path cannot drift.
     *
     * @param array{start: int, end: int}|null $session
     */
    private static function medicalCovers(object $exemption, ?array $session): bool
    {
        // No end date → still running, and the caller's date filter already put this
        // session inside it.
        if (empty($exemption->to_date)) {
            return true;
        }

        $from = strtotime((string) $exemption->from_date);
        $to = strtotime((string) $exemption->to_date);

        if ($from === false || $to === false) {
            return true;
        }

        /* from_date/to_date are datetimes, but the form behind them collects two
           different things: a precise window ("09 Dec 11:45" → "24 Dec 15:50"), or
           plain dates, which store midnight ("15 Dec 00:00" → "16 Dec 00:00").

           Both endpoints at midnight means dates were entered, so the exemption
           covers those days whole — and the caller's date filter has already
           established this session falls inside them. Reading 00:00→00:00 as a pair
           of clock times instead (as this did before) makes a zero-length window
           that overlaps no session, so half of the exemptions on record — every
           date-only one — silently failed to register. */
        if (date('H:i:s', $from) === '00:00:00' && date('H:i:s', $to) === '00:00:00') {
            return true;
        }

        // A timed exemption only covers the sessions it actually overlaps. Compared
        // as absolute datetimes so a multi-day window still spans the days between.
        return $session === null
            ? true // Session times unreadable — honour the exemption rather than drop it.
            : $session['start'] <= $to && $session['end'] >= $from;
    }

    /**
     * This session as absolute timestamps on its own date.
     *
     * @return array{start: int, end: int}|null
     */
    private function sessionWindow(): ?array
    {
        $timetable = $this->timetable();

        return $timetable ? self::windowFor($timetable->START_DATE, $timetable->class_session) : null;
    }

    /**
     * A session as absolute timestamps on its own date, built from the two raw
     * columns so the batch path can make one without a resolver instance.
     *
     * @return array{start: int, end: int}|null
     */
    private static function windowFor(?string $startDate, ?string $classSession): ?array
    {
        $parsed = self::parseClassSession($classSession);
        if (!$parsed || empty($startDate)) {
            return null;
        }

        $day = date('Y-m-d', strtotime((string) $startDate));
        $start = strtotime($day . ' ' . $parsed['start']);
        $end = strtotime($day . ' ' . $parsed['end']);

        return ($start !== false && $end !== false) ? ['start' => $start, 'end' => $end] : null;
    }

    private function remember(int $studentId, string $key, callable $resolve)
    {
        if (!isset($this->cache[$studentId]) || !array_key_exists($key, $this->cache[$studentId])) {
            $this->cache[$studentId][$key] = $resolve();
        }

        return $this->cache[$studentId][$key];
    }

    private function timetable(): ?Timetable
    {
        if (!$this->timetableLoaded) {
            $this->timetable = Timetable::select('START_DATE', 'class_session')
                ->where('pk', $this->timetablePk)
                ->first();
            $this->timetableLoaded = true;
        }

        return $this->timetable;
    }

    private function dutyTypes(): array
    {
        if ($this->dutyTypes === null) {
            $this->dutyTypes = MDOEscotDutyMap::getMdoDutyTypes();
        }

        return $this->dutyTypes;
    }

    /**
     * True when the session's time range overlaps the duty's. A session at
     * 10:35–11:30 does not overlap a duty at 14:05–15:07, so that duty must not
     * lock it; a session at 14:10–14:50 does.
     */
    private static function overlapsSession(?string $classSession, ?string $dutyFrom, ?string $dutyTo): bool
    {
        if (empty($classSession) || empty($dutyFrom) || empty($dutyTo)) {
            return false;
        }

        $session = self::parseClassSession($classSession);
        if (!$session) {
            return false;
        }

        $sessionStart = self::toSeconds($session['start']);
        $sessionEnd = self::toSeconds($session['end']);
        $dutyStart = self::toSeconds($dutyFrom);
        $dutyEnd = self::toSeconds($dutyTo);

        if ($sessionStart === false || $sessionEnd === false || $dutyStart === false || $dutyEnd === false) {
            return false;
        }

        return $sessionStart <= $dutyEnd && $sessionEnd >= $dutyStart;
    }

    /**
     * class_session is either a ClassSessionMaster PK or free text the Add Event
     * form wrote — "10:35 AM - 11:30 AM", "10:35 to 11:30", "06:00 to 07:00".
     *
     * @return array{start: string, end: string}|null
     */
    private static function parseClassSession(?string $classSession): ?array
    {
        if (empty($classSession)) {
            return null;
        }

        if (is_numeric($classSession)) {
            // The same shift PK repeats on nearly every row the batch path walks;
            // one lookup per distinct PK per request is plenty.
            static $masters = [];
            if (!array_key_exists($classSession, $masters)) {
                $masters[$classSession] = ClassSessionMaster::find($classSession);
            }
            $master = $masters[$classSession];

            return ($master && $master->start_time && $master->end_time)
                ? ['start' => date('H:i', strtotime($master->start_time)), 'end' => date('H:i', strtotime($master->end_time))]
                : null;
        }

        foreach ([' - ', ' to ', '-'] as $separator) {
            if (!str_contains($classSession, $separator)) {
                continue;
            }

            $parts = explode($separator, $classSession);
            if (count($parts) !== 2) {
                continue;
            }

            $start = strtotime(trim($parts[0]));
            $end = strtotime(trim($parts[1]));

            return ($start !== false && $end !== false)
                ? ['start' => date('H:i', $start), 'end' => date('H:i', $end)]
                : null;
        }

        return null;
    }

    /** Seconds since midnight, or false when the value cannot be read as a time. */
    private static function toSeconds(?string $time): int|false
    {
        if (empty($time)) {
            return false;
        }

        // "14:05" / "14:05:00" first — strtotime() would also accept these, but it
        // resolves them against today, which is needless work for a plain clock time.
        if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', trim($time), $m)) {
            [$h, $i, $s] = [(int) $m[1], (int) $m[2], (int) ($m[3] ?? 0)];

            if ($h <= 23 && $i <= 59 && $s <= 59) {
                return ($h * 3600) + ($i * 60) + $s;
            }
        }

        $timestamp = strtotime($time);
        if ($timestamp === false) {
            return false;
        }

        return ((int) date('H', $timestamp) * 3600) + ((int) date('i', $timestamp) * 60) + (int) date('s', $timestamp);
    }
}
