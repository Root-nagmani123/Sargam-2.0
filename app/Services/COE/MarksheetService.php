<?php

namespace App\Services\COE;

use App\Models\COE\Marksheet;
use App\Models\COE\SignatureMaster;
use App\Models\ExaminationDrive;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * COE → Document Management: Marksheet Generation and Verified Marksheet.
 *
 * The flow:
 *   1. Merit & Ranking ranks an OT on an examination drive (every mark of the drive
 *      approved and moderated) with a Pass / Fail (ResultMarksService::meritSheets(),
 *      drive-wise since 2026-11-27), decided by the fixed passing rule
 *      (ResultMarksService::passingPercent). A row with no result is skipped.
 *   2. Their marksheet for that drive is Generated: a coe_marksheets row (drive + OT)
 *      with its number, MS/<course short name>/<year>/<serial>, the serial running on
 *      per course across its drives, the first time it is listed.
 *   3. It is signed one by one with Verify (verify()), in order: the UDC first,
 *      then the AAO once the UDC has, then the COE once the AAO has, which
 *      publishes it. Each puts only their own signature, the designation's Active
 *      image in Signature Master. Publishing freezes the marksheet in `snapshot`;
 *      a published marksheet is never recomputed.
 *   4. Verified Marksheet lists the published ones.
 *
 * A drive marksheet sits on the tab of its drive (Active / Closed = Archived); a legacy
 * course-wise one (published before marksheets were per drive, no drive) on its course's.
 * Rows keep the keys the marksheet views read; `code` is the OT's student_master pk and
 * `sheet_key` the marksheet's sheet: the drive id, or "c<course pk>" for a legacy one.
 * The routes carry both.
 */
class MarksheetService
{
    /** Signature Master designation => the caption it signs under on the marksheet. */
    public const SIGNATORIES = [
        'UDC' => 'Prepared By',
        'AAO' => 'Compared By',
        'COE' => 'Controller of Examination',
    ];

    /**
     * Other Signature Master designation names that sign as one of SIGNATORIES
     * (upper case): the COE's signature is saved under "COE-Incharge", and rows
     * picked from the HR Designation Master carry the full names.
     */
    public const SIGNATORY_ALIASES = [
        'UPPER DIVISION CLERK' => 'UDC',
        'ASSISTANT ADMINISTRATIVE OFFICER' => 'AAO',
        'COE-INCHARGE' => 'COE',
    ];

    /** Marksheet group of the Director Assessment drives (merit's B). */
    private const DIRECTOR_GROUP = 'Director Assessment';

    /** @var array<string, list<array<string, mixed>>> */
    private array $rowCache = [];

    /** @var array<int, string> course pk => tab, for legacy course-wise marksheets */
    private array $scopeCache = [];

    /** @var array<int, string> drive id => tab */
    private array $driveScopeCache = [];

    public function __construct(private ResultMarksService $results)
    {
    }

    /** coe_marksheets is created by migration; until then marksheets are shown but neither numbered nor published. */
    public static function enabled(): bool
    {
        static $enabled;

        return $enabled ??= Schema::hasTable('coe_marksheets');
    }

    /** coe_marksheets.signed is added by a later migration; until then marksheets cannot be signed. */
    public static function signingEnabled(): bool
    {
        static $enabled;

        return $enabled ??= self::enabled() && Schema::hasColumn('coe_marksheets', 'signed');
    }

    /* ───────────── Listing ───────────── */

    /**
     * Every marksheet of the courses in $scope: Generated ones as Merit & Ranking
     * has them now, Published ones as they were published.
     *
     * @return list<array<string, mixed>>
     */
    public function rows(string $scope): array
    {
        return $this->rowCache[$scope] ??= $this->build($scope);
    }

    /**
     * The marksheets of one sheet: a drive (its id) or a legacy course-wise one ("c<course pk>").
     *
     * @return list<array<string, mixed>>
     */
    public function sheetRows(string $sheetKey): array
    {
        if (! preg_match('/^c?\d+$/', $sheetKey)) {
            return [];
        }

        return array_values(array_filter(
            array_merge($this->rows('active'), $this->rows('archived')),
            fn ($m) => $m['sheet_key'] === $sheetKey
        ));
    }

    /** One marksheet by sheet key and student pk, or null. */
    public function find(string $sheetKey, string $code): ?array
    {
        foreach ($this->sheetRows($sheetKey) as $m) {
            if ($m['code'] === $code) {
                return $m;
            }
        }

        return null;
    }

    /**
     * The Course filter of a listing: course key => name / short name.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, array{name:string, short:string}>
     */
    public static function courses(array $rows): array
    {
        $courses = [];
        foreach ($rows as $row) {
            $courses[$row['course_key']] ??= ['name' => $row['course'], 'short' => $row['course_short']];
        }
        uasort($courses, fn ($a, $b) => strnatcasecmp($a['name'], $b['name']));

        return $courses;
    }

    /** @return list<array<string, mixed>> */
    private function build(string $scope): array
    {
        // Drive-wise: meritSheets() of $scope are the drives on this tab.
        $live = $this->results->meritSheets($scope);

        $candidates = [];
        foreach ($live as $sheet) {
            $layout = $this->layout($sheet);
            $info = $this->driveInfo($sheet['drives']);
            foreach ($sheet['rows'] as $r) {
                if ($r['result'] === null) {
                    continue;
                }
                $candidates[] = $this->row($sheet, $info, $layout, $r);
            }
        }

        if (! self::enabled()) {
            return array_map(fn ($row) => $row + ['number' => '-', 'generated_on' => '-', 'published' => false] + self::status(false, []), $candidates);
        }

        $this->generate($candidates);

        $records = Marksheet::query()
            ->where(function ($q) use ($candidates) {
                $q->whereIn('examination_drive_id', array_unique(array_map(fn ($r) => (int) $r['sheet_key'], $candidates)) ?: [0])
                    ->orWhere('status', Marksheet::STATUS_PUBLISHED);
            })
            ->get()
            ->keyBy(fn (Marksheet $m) => self::sheetKeyOf($m).'|'.$m->student_master_pk);

        $rows = [];
        foreach ($records as $record) {
            if ($record->isPublished() && $this->recordScope($record) === $scope && is_array($record->snapshot)) {
                $rows[] = $this->stamp($record->snapshot + ['sheet_key' => self::sheetKeyOf($record)], $record);
            }
        }
        foreach ($candidates as $row) {
            $record = $records[$row['sheet_key'].'|'.$row['code']] ?? null;
            if ($record && ! $record->isPublished()) {
                $rows[] = $this->stamp($row, $record);
            }
        }

        return $rows;
    }

    /**
     * A marksheet row with its record's number, date and status. `signatures` is
     * designation => Signature Master pk: those put on it so far, or as published.
     */
    /** A record's sheet: its drive id, or "c<course pk>" for a legacy course-wise marksheet. */
    public static function sheetKeyOf(Marksheet $record): string
    {
        return $record->examination_drive_id ? (string) $record->examination_drive_id : 'c'.$record->course_master_pk;
    }

    /** The tab a record is listed on: its drive's (Closed = Archived), else its course's. */
    private function recordScope(Marksheet $record): string
    {
        if (! $record->examination_drive_id) {
            return $this->scopeOf($record->course_master_pk);
        }

        return $this->driveScopeCache[$record->examination_drive_id] ??=
            ExaminationDrive::whereKey($record->examination_drive_id)->value('status') === ExaminationDrive::STATUS_CLOSED ? 'archived' : 'active';
    }

    private function stamp(array $row, Marksheet $record): array
    {
        $signed = $record->isPublished() || ! self::signingEnabled() ? [] : ($record->signed ?? []);

        return array_replace($row, [
            'number' => $record->marksheet_number,
            'generated_on' => optional($record->generated_at)->format('d M Y') ?? '-',
            'published' => $record->isPublished(),
            ...self::status($record->isPublished(), array_keys($signed)),
        ], $record->isPublished() ? [] : [
            'signatures' => array_map(fn ($s) => (int) $s['signature'], $signed),
        ]);
    }

    /** Statuses of the Marksheet Status column and filter, in signing order. */
    public const STATUSES = ['Generated', 'Verified by UDC', 'Verified by AAO', 'Published'];

    /**
     * The Status column: Generated, then Verified by the highest signatory so far
     * (UDC, AAO), then Published once the COE signs. `state` is its badge style.
     *
     * @param  string[]  $signed  designations signed so far
     * @return array{status:string, state:string}
     */
    private static function status(bool $published, array $signed): array
    {
        if ($published) {
            return ['status' => 'Published', 'state' => 'published'];
        }
        foreach (['AAO', 'UDC'] as $designation) {
            if (in_array($designation, $signed, true)) {
                return ['status' => 'Verified by '.$designation, 'state' => 'pending'];
            }
        }

        return ['status' => 'Generated', 'state' => 'draft'];
    }

    /**
     * The marksheet's table as the view reads it: group (subject, or Director
     * Assessment) => components [label => max], and the column key behind each
     * label. Qualifying papers (ICT / Hindi) are not added to the Grand Total in
     * Merit & Ranking, so they are not on the marksheet either.
     *
     * @return array<string, array{components:array<string, float>, keys:array<string, string>}>
     */
    private function layout(array $sheet): array
    {
        $groups = [];
        $add = function (string $name, array $columns) use (&$groups) {
            if ($columns === []) {
                return;
            }
            $counts = array_count_values(array_column($columns, 'component'));
            foreach ($columns as $key => $col) {
                $label = $counts[$col['component']] > 1 ? $col['component'].' · '.$col['drive'] : $col['component'];
                $groups[$name]['components'][$label] = $col['max'];
                $groups[$name]['keys'][$label] = $key;
            }
        };
        foreach ($sheet['subjects'] as $s) {
            $add($s['name'], $s['columns']);
        }
        $add(self::DIRECTOR_GROUP, $sheet['partB']);

        return $groups;
    }

    /** Batch, term, type and name of the course's held drives, for the listing columns. */
    private function driveInfo(Collection $drives): array
    {
        $list = fn (callable $value) => $drives->map($value)->map(fn ($v) => trim((string) $v))->filter()->unique()->implode(', ');
        $sessions = $drives->pluck('academic_session')->map(fn ($v) => trim((string) $v))->filter()->unique()->values();

        return [
            'batch' => $sessions->isEmpty() ? '-' : $sessions->implode(', ').' Batch',
            'exam_term' => $list(fn ($d) => $d->term->term_name ?? '') ?: '-',
            'exam_type' => $list(fn ($d) => $d->examinationType->exam_type_name ?? '') ?: '-',
            'examination' => $list(fn ($d) => $d->drive_name) ?: '-',
            'year' => preg_match('/^\d{4}$/', (string) $sessions->first()) ? (int) $sessions->first() : (int) now()->year,
        ];
    }

    private function row(array $sheet, array $info, array $layout, array $r): array
    {
        $marks = [];
        foreach ($layout as $name => $group) {
            foreach ($group['keys'] as $label => $key) {
                $marks[$name][$label] = (float) ($r['marks'][$key] ?? 0);
            }
        }

        return [
            'code' => (string) $r['student'],
            'sheet_key' => (string) $sheet['drive_id'],
            'course_key' => (string) $sheet['course_pk'],
            'course' => $sheet['course'],
            'course_short' => $sheet['course_short'],
            'name' => $r['name'],
            'roll' => $r['roll'] ?: ($r['code'] ?: '-'),
            'service' => $r['service'] ?: '-',
            'rank' => $r['rank'],
            'grand' => $r['grand'],
            'result' => $r['result'],
            'marks' => $marks,
            'subjects' => array_map(fn ($g) => ['components' => $g['components']], $layout),
            'year' => $info['year'],
        ] + array_intersect_key($info, array_flip(['batch', 'exam_term', 'exam_type', 'examination']));
    }

    /** Legacy course-wise marksheets: Active while the course has an Active drive, else Archived. */
    private function scopeOf(int $coursePk): string
    {
        // Main drives only, as ResultMarksService::scopeDrives() (re-exams left out).
        return $this->scopeCache[$coursePk] ??= ReexamService::excludeFrom(ExaminationDrive::where('course_master_pk', $coursePk)
            ->where('status', ExaminationDrive::STATUS_ACTIVE)
            ->whereHas('componentMaps'))
            ->exists() ? 'active' : 'archived';
    }

    /* ───────────── Generation ───────────── */

    /**
     * Number the marksheets not generated yet, drive by drive in merit order. The
     * serial runs on per course, across its drives (agreed 2026-11-27), so numbers stay
     * unique. Two people opening the page at once may both try: the unique keys turn the
     * second away and the page shows the first.
     *
     * @param  list<array<string, mixed>>  $candidates
     */
    private function generate(array $candidates): void
    {
        $byDrive = [];
        foreach ($candidates as $row) {
            $byDrive[$row['sheet_key']][] = $row;
        }

        foreach ($byDrive as $driveKey => $rows) {
            $driveId = (int) $driveKey;
            $coursePk = (int) $rows[0]['course_key'];
            $have = Marksheet::where('examination_drive_id', $driveId)->pluck('student_master_pk')->map(fn ($pk) => (int) $pk)->all();
            $new = array_values(array_filter($rows, fn ($r) => ! in_array((int) $r['code'], $have, true)));
            if ($new === []) {
                continue;
            }

            try {
                DB::transaction(function () use ($coursePk, $driveId, $new) {
                    $serial = (int) Marksheet::where('course_master_pk', $coursePk)->lockForUpdate()->max('serial');
                    $now = now();
                    $userId = auth()->user()?->user_id;
                    foreach ($new as $row) {
                        $serial++;
                        Marksheet::create([
                            'course_master_pk' => $coursePk,
                            'examination_drive_id' => $driveId,
                            'student_master_pk' => (int) $row['code'],
                            'serial' => $serial,
                            'marksheet_number' => $this->number($row, $serial),
                            'generated_at' => $now,
                            'created_by' => $userId,
                            'created_date' => $now,
                        ]);
                    }
                });
            } catch (QueryException $e) {
                if (($e->errorInfo[1] ?? null) !== 1062) {
                    throw $e;
                }
            }
        }
    }

    /** MS/FC101/2026/0001: course short name (letters and digits), batch year, serial. */
    private function number(array $row, int $serial): string
    {
        $course = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $row['course_short']) ?: 'C'.$row['course_key'], 0, 20));

        return sprintf('MS/%s/%d/%04d', $course, $row['year'], $serial);
    }

    /* ───────────── Signatures ───────────── */

    /**
     * Active Signature Master rows of the three signatories (their own name or an
     * alias, e.g. "COE-Incharge"). Group them with signatoryOf(), not designation_name.
     */
    public function activeSignatures(): Collection
    {
        return SignatureMaster::query()
            ->where('active_inactive', 1)
            ->whereIn('designation_name', array_merge(array_keys(self::SIGNATORIES), array_keys(self::SIGNATORY_ALIASES)))
            ->orderBy('pk')
            ->get();
    }

    /** The signatory (UDC / AAO / COE) a Signature Master designation signs as, or null. */
    public static function signatoryOf(?string $designation): ?string
    {
        $name = strtoupper(trim((string) $designation));
        if (isset(self::SIGNATORIES[$name])) {
            return $name;
        }

        return self::SIGNATORY_ALIASES[$name] ?? null;
    }

    /** Every Signature Master designation name that signs as $signatory (its own and its aliases). */
    public static function designationNamesOf(string $signatory): array
    {
        return array_merge([$signatory], array_keys(array_filter(self::SIGNATORY_ALIASES, fn ($as) => $as === $signatory)));
    }

    /**
     * Why these designations cannot sign yet, or null: Signature Master must hold
     * exactly one Active signature of each, with its image. Default: all three.
     *
     * @param  string[]|null  $designations
     */
    public function signatureProblem(?array $designations = null): ?string
    {
        $active = $this->activeSignatures()->groupBy(fn (SignatureMaster $s) => self::signatoryOf($s->designation_name));
        $disk = Storage::disk(SignatureMaster::DISK);

        $missing = [];
        $twice = [];
        foreach ($designations ?? array_keys(self::SIGNATORIES) as $designation) {
            $rows = $active->get($designation, collect());
            if ($rows->count() > 1) {
                $twice[] = $designation;
            } elseif ($rows->isEmpty() || ! $disk->exists((string) $rows->first()->signature_path)) {
                $missing[] = $designation;
            }
        }

        if ($missing) {
            return 'Upload the '.implode(', ', $missing).' signature'.(count($missing) > 1 ? 's' : '')
                .' in Signature Master and keep '.(count($missing) > 1 ? 'them' : 'it').' Active before verifying.';
        }
        if ($twice) {
            return 'More than one Active '.implode(', ', $twice).' signature in Signature Master. Keep only one Active per designation before verifying.';
        }

        return null;
    }

    /**
     * The signatures a marksheet prints, keyed by designation: the ones put on it
     * (still existing; on a published marksheet a deleted one falls back to the
     * current Active signature of its designation). A place not signed yet is blank.
     */
    public function signaturesFor(array $m): Collection
    {
        $put = $m['signatures'] ?? [];
        $kept = SignatureMaster::whereIn('pk', array_values($put) ?: [0])->get()->keyBy('pk');
        $current = $m['published'] ? $this->activeSignatures()->keyBy(fn (SignatureMaster $s) => self::signatoryOf($s->designation_name)) : collect();

        $out = collect();
        foreach (array_keys(self::SIGNATORIES) as $designation) {
            $sig = isset($put[$designation]) ? ($kept->get($put[$designation]) ?? $current->get($designation)) : null;
            if ($sig) {
                $out->put($designation, $sig);
            }
        }

        return $out;
    }

    /**
     * The designation $user signs as: their highest of COE > AAO > UDC; Super Admin
     * signs as the COE. Null when they are none of these.
     */
    public static function signerRole(User $user): ?string
    {
        if ($user->hasRole('Super Admin')) {
            return 'COE';
        }
        foreach (array_reverse(array_keys(self::SIGNATORIES)) as $designation) {
            if ($user->hasRole($designation)) {
                return $designation;
            }
        }

        return null;
    }

    /** The designation that signs before $designation (the AAO after the UDC, the COE after the AAO), or null for the UDC. */
    public static function signsBefore(string $designation): ?string
    {
        $order = array_keys(self::SIGNATORIES);

        return $order[array_search($designation, $order, true) - 1] ?? null;
    }

    /* ───────────── Verify & Publish ───────────── */

    /**
     * Verify the sheet's (drive's) marksheets of these OTs (student pks) as $user: put their
     * designation's signature on each. The order is fixed: the AAO only after the
     * UDC, the COE only after the AAO; the COE's signature publishes the marksheet
     * and needs all three Active in Signature Master. All or nothing; a published
     * marksheet, or one already signed by this designation, is skipped.
     *
     * @param  string[]  $codes
     * @return array{as:?string, signed:int, published:int, error:?string}
     */
    public function verify(User $user, string $sheetKey, array $codes): array
    {
        $fail = fn (string $error, ?string $as = null) => ['as' => $as, 'signed' => 0, 'published' => 0, 'error' => $error];

        $as = self::signerRole($user);
        if ($as === null) {
            return $fail('Only the UDC, AAO or COE can verify marksheets.');
        }
        if (! self::signingEnabled()) {
            return $fail('Marksheets cannot be signed yet: run the coe_marksheets migrations.', $as);
        }

        $rows = collect($this->sheetRows($sheetKey))->keyBy('code');
        // A legacy course-wise sheet is published already: nothing left to sign there.
        $driveId = ctype_digit($sheetKey) ? (int) $sheetKey : 0;
        foreach ($codes as $code) {
            if (! $rows->has($code)) {
                return $fail('Some of these marksheets are no longer listed. Reload the page.', $as);
            }
        }

        // UDC → AAO → COE: the one before must have signed first.
        $before = self::signsBefore($as);
        $records = Marksheet::where('examination_drive_id', $driveId)->whereIn('student_master_pk', array_map('intval', $codes))
            ->get()->keyBy(fn (Marksheet $m) => (string) $m->student_master_pk);

        $toSign = 0;
        $waiting = 0;
        foreach ($codes as $code) {
            if ($rows[$code]['published'] || ! isset($records[$code]) || isset($records[$code]->signed[$as])) {
                continue;
            }
            if ($before !== null && ! isset($records[$code]->signed[$before])) {
                $waiting++;
            } else {
                $toSign++;
            }
        }
        if ($waiting) {
            return $fail(($waiting === 1 ? '1 marksheet is' : "{$waiting} marksheets are")." not verified by the {$before} yet. The {$before} verifies first, then the {$as}.", $as);
        }
        if ($toSign && ($problem = $this->signatureProblem($as === 'COE' ? null : [$as]))) {
            return $fail($problem, $as);
        }
        $signatureOf = $this->activeSignatures()->mapWithKeys(fn (SignatureMaster $s) => [self::signatoryOf($s->designation_name) => (int) $s->pk])->all();
        $now = now();

        [$signed, $published] = DB::transaction(function () use ($rows, $codes, $driveId, $before, $as, $signatureOf, $user, $now) {
            $signed = 0;
            $published = 0;
            foreach ($codes as $code) {
                $record = Marksheet::where('examination_drive_id', $driveId)->where('student_master_pk', (int) $code)
                    ->lockForUpdate()->first();
                if (! $record || $record->isPublished()) {
                    continue;
                }

                $put = $record->signed ?? [];
                if (isset($put[$as]) || ($before !== null && ! isset($put[$before]))) {
                    continue;
                }
                $put[$as] = ['signature' => $signatureOf[$as], 'by' => $user->user_id, 'at' => $now->format('Y-m-d H:i:s')];

                $record->signed = $put;
                $record->modified_by = $user->user_id;
                $record->modified_date = $now;
                if ($as === 'COE') {
                    $signatures = array_map(fn ($s) => (int) $s['signature'], $put);
                    $snapshot = array_replace(array_diff_key($rows[$code], array_flip(['number', 'generated_on', 'published', 'status', 'state'])), ['signatures' => $signatures]);
                    $record->status = Marksheet::STATUS_PUBLISHED;
                    $record->published_by = $user->user_id;
                    $record->published_at = $now;
                    $record->snapshot = $snapshot;
                    $published++;
                }
                $record->save();
                $signed++;
            }

            return [$signed, $published];
        });
        $this->rowCache = [];

        return ['as' => $as, 'signed' => $signed, 'published' => $published, 'error' => null];
    }
}
