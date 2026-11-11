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
 *   1. Merit & Ranking ranks an OT (every mark approved) with a Pass / Fail
 *      (ResultMarksService::meritSheets()). An OT whose service has no passing
 *      marks is not decided yet, so gets no marksheet.
 *   2. Their marksheet is Generated: a coe_marksheets row with its number,
 *      MS/<course short name>/<year>/<serial>, the first time it is listed.
 *   3. It is signed one by one with Verify (verify()): the UDC puts the UDC
 *      signature; the AAO the AAO one, and the UDC one if still blank; the COE
 *      the COE one and any still blank, which publishes it. Each signature is
 *      the designation's Active image in Signature Master. Publishing freezes the
 *      marksheet in `snapshot`; a published marksheet is never recomputed.
 *   4. Verified Marksheet lists the published ones.
 *
 * A course is Active while it has an Active drive, else Archived, so each
 * marksheet sits on one tab. Rows keep the keys the marksheet views read; `code`
 * is the OT's student_master pk, which is what the routes carry.
 */
class MarksheetService
{
    /** Signature Master designation => the caption it signs under on the marksheet. */
    public const SIGNATORIES = [
        'UDC' => 'Prepared By',
        'AAO' => 'Compared By',
        'COE' => 'Controller of Examination',
    ];

    /** Marksheet group of the Director Assessment drives (merit's B). */
    private const DIRECTOR_GROUP = 'Director Assessment';

    /** @var array<string, list<array<string, mixed>>> */
    private array $rowCache = [];

    /** @var array<int, string> */
    private array $scopeCache = [];

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

    /** @return list<array<string, mixed>> */
    public function courseRows(string $courseKey): array
    {
        if (! ctype_digit($courseKey)) {
            return [];
        }

        return array_values(array_filter(
            $this->rows($this->scopeOf((int) $courseKey)),
            fn ($m) => $m['course_key'] === $courseKey
        ));
    }

    /** One marksheet by course pk and student pk, or null. */
    public function find(string $courseKey, string $code): ?array
    {
        foreach ($this->courseRows($courseKey) as $m) {
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
        $live = array_filter($this->results->meritSheets($scope), fn ($sheet) => $this->scopeOf($sheet['course_pk']) === $scope);

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
                $q->whereIn('course_master_pk', array_unique(array_map(fn ($r) => (int) $r['course_key'], $candidates)) ?: [0])
                    ->orWhere('status', Marksheet::STATUS_PUBLISHED);
            })
            ->get()
            ->keyBy(fn (Marksheet $m) => $m->course_master_pk.'|'.$m->student_master_pk);

        $rows = [];
        foreach ($records as $record) {
            if ($record->isPublished() && $this->scopeOf($record->course_master_pk) === $scope && is_array($record->snapshot)) {
                $rows[] = $this->stamp($record->snapshot, $record);
            }
        }
        foreach ($candidates as $row) {
            $record = $records[$row['course_key'].'|'.$row['code']] ?? null;
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

    /** Active while the course has an Active drive, else Archived. */
    private function scopeOf(int $coursePk): string
    {
        return $this->scopeCache[$coursePk] ??= ExaminationDrive::where('course_master_pk', $coursePk)
            ->where('status', ExaminationDrive::STATUS_ACTIVE)
            ->whereHas('componentMaps')
            ->exists() ? 'active' : 'archived';
    }

    /* ───────────── Generation ───────────── */

    /**
     * Number the marksheets not generated yet, course by course in merit order,
     * continuing each course's serial. Two people opening the page at once may
     * both try: the unique keys turn the second away and the page shows the first.
     *
     * @param  list<array<string, mixed>>  $candidates
     */
    private function generate(array $candidates): void
    {
        $byCourse = [];
        foreach ($candidates as $row) {
            $byCourse[$row['course_key']][] = $row;
        }

        foreach ($byCourse as $courseKey => $rows) {
            $coursePk = (int) $courseKey;
            $have = Marksheet::where('course_master_pk', $coursePk)->pluck('student_master_pk')->map(fn ($pk) => (int) $pk)->all();
            $new = array_values(array_filter($rows, fn ($r) => ! in_array((int) $r['code'], $have, true)));
            if ($new === []) {
                continue;
            }

            try {
                DB::transaction(function () use ($coursePk, $new) {
                    $serial = (int) Marksheet::where('course_master_pk', $coursePk)->lockForUpdate()->max('serial');
                    $now = now();
                    $userId = auth()->user()?->user_id;
                    foreach ($new as $row) {
                        $serial++;
                        Marksheet::create([
                            'course_master_pk' => $coursePk,
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

    /** Active Signature Master rows of the three signatories, keyed by designation. */
    public function activeSignatures(): Collection
    {
        return SignatureMaster::query()
            ->where('active_inactive', 1)
            ->whereIn('designation_name', array_keys(self::SIGNATORIES))
            ->orderBy('pk')
            ->get();
    }

    /**
     * Why these designations cannot sign yet, or null: Signature Master must hold
     * exactly one Active signature of each, with its image. Default: all three.
     *
     * @param  string[]|null  $designations
     */
    public function signatureProblem(?array $designations = null): ?string
    {
        $active = $this->activeSignatures()->groupBy('designation_name');
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
        $current = $m['published'] ? $this->activeSignatures()->keyBy('designation_name') : collect();

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

    /* ───────────── Verify & Publish ───────────── */

    /**
     * Verify the course's marksheets of these OTs (student pks) as $user: put their
     * designation's signature on each, and every lower one still blank (the AAO the
     * UDC's, the COE the UDC's and AAO's). The COE's signature publishes the
     * marksheet. All or nothing; a published marksheet is skipped.
     *
     * @param  string[]  $codes
     * @return array{as:?string, signed:int, published:int, error:?string}
     */
    public function verify(User $user, string $courseKey, array $codes): array
    {
        $fail = fn (string $error, ?string $as = null) => ['as' => $as, 'signed' => 0, 'published' => 0, 'error' => $error];

        $as = self::signerRole($user);
        if ($as === null) {
            return $fail('Only the UDC, AAO or COE can verify marksheets.');
        }
        if (! self::signingEnabled()) {
            return $fail('Marksheets cannot be signed yet: run the coe_marksheets migrations.', $as);
        }

        $rows = collect($this->courseRows($courseKey))->keyBy('code');
        foreach ($codes as $code) {
            if (! $rows->has($code)) {
                return $fail('Some of these marksheets are no longer listed. Reload the page.', $as);
            }
        }

        // UDC → [UDC]; AAO → [UDC, AAO]; COE → [UDC, AAO, COE]: each put only where still blank.
        $order = array_keys(self::SIGNATORIES);
        $mine = array_slice($order, 0, array_search($as, $order, true) + 1);
        $records = Marksheet::where('course_master_pk', (int) $courseKey)->whereIn('student_master_pk', array_map('intval', $codes))
            ->get()->keyBy(fn (Marksheet $m) => (string) $m->student_master_pk);

        $needed = [];
        foreach ($codes as $code) {
            if (! $rows[$code]['published'] && isset($records[$code])) {
                $needed = array_merge($needed, array_diff($mine, array_keys($records[$code]->signed ?? [])));
            }
        }
        $needed = array_values(array_unique($needed));
        if ($needed && ($problem = $this->signatureProblem($needed))) {
            return $fail($problem, $as);
        }
        $signatureOf = $this->activeSignatures()->mapWithKeys(fn (SignatureMaster $s) => [$s->designation_name => (int) $s->pk])->all();
        $now = now();

        [$signed, $published] = DB::transaction(function () use ($rows, $codes, $courseKey, $mine, $as, $signatureOf, $user, $now) {
            $signed = 0;
            $published = 0;
            foreach ($codes as $code) {
                $record = Marksheet::where('course_master_pk', (int) $courseKey)->where('student_master_pk', (int) $code)
                    ->lockForUpdate()->first();
                if (! $record || $record->isPublished()) {
                    continue;
                }

                $put = $record->signed ?? [];
                $added = false;
                foreach ($mine as $designation) {
                    if (! isset($put[$designation])) {
                        $put[$designation] = ['signature' => $signatureOf[$designation], 'by' => $user->user_id, 'at' => $now->format('Y-m-d H:i:s')];
                        $added = true;
                    }
                }
                if (! $added) {
                    continue;
                }

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
