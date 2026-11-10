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
 *   3. COE Verify & Publish it — only while Signature Master holds exactly one
 *      Active UDC, AAO and COE signature. Publishing freezes the marksheet in
 *      `snapshot`; a published marksheet is never recomputed.
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
            return array_map(fn ($row) => $row + ['number' => '-', 'generated_on' => '-', 'published' => false], $candidates);
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

    /** A marksheet row with its record's number, date and status. */
    private function stamp(array $row, Marksheet $record): array
    {
        return array_replace($row, [
            'number' => $record->marksheet_number,
            'generated_on' => optional($record->generated_at)->format('d M Y') ?? '-',
            'published' => $record->isPublished(),
        ]);
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
     * Why a marksheet cannot be published yet, or null: Signature Master must hold
     * exactly one Active UDC, AAO and COE signature, each with its image.
     */
    public function signatureProblem(): ?string
    {
        $active = $this->activeSignatures()->groupBy('designation_name');
        $disk = Storage::disk(SignatureMaster::DISK);

        $missing = [];
        $twice = [];
        foreach (array_keys(self::SIGNATORIES) as $designation) {
            $rows = $active->get($designation, collect());
            if ($rows->count() > 1) {
                $twice[] = $designation;
            } elseif ($rows->isEmpty() || ! $disk->exists((string) $rows->first()->signature_path)) {
                $missing[] = $designation;
            }
        }

        if ($missing) {
            return 'Upload the '.implode(', ', $missing).' signature'.(count($missing) > 1 ? 's' : '')
                .' in Signature Master and keep '.(count($missing) > 1 ? 'them' : 'it').' Active before publishing. All three — UDC, AAO and COE — are needed.';
        }
        if ($twice) {
            return 'More than one Active '.implode(', ', $twice).' signature in Signature Master. Keep only one Active per designation before publishing.';
        }

        return null;
    }

    /**
     * The signatures a marksheet prints, keyed by designation: those it was published
     * with (while they still exist), else the current Active ones.
     */
    public function signaturesFor(array $m): Collection
    {
        $current = $this->activeSignatures()->keyBy('designation_name');
        if (! $m['published'] || empty($m['signatures'])) {
            return $current;
        }

        $kept = SignatureMaster::whereIn('pk', array_values($m['signatures']))->get()->keyBy('pk');
        $out = collect();
        foreach (array_keys(self::SIGNATORIES) as $designation) {
            $sig = $kept->get($m['signatures'][$designation] ?? 0) ?? $current->get($designation);
            if ($sig) {
                $out->put($designation, $sig);
            }
        }

        return $out;
    }

    /* ───────────── Verify & Publish ───────────── */

    /**
     * Publish the course's Generated marksheets of these OTs (student pks). All or
     * nothing; an already published one is skipped.
     *
     * @param  string[]  $codes
     * @return array{published:int, error:?string}
     */
    public function publish(User $user, string $courseKey, array $codes): array
    {
        if (! self::enabled()) {
            return ['published' => 0, 'error' => 'Marksheets cannot be published yet: the coe_marksheets table has not been created.'];
        }
        if ($problem = $this->signatureProblem()) {
            return ['published' => 0, 'error' => $problem];
        }

        $rows = collect($this->courseRows($courseKey))->keyBy('code');
        foreach ($codes as $code) {
            if (! $rows->has($code)) {
                return ['published' => 0, 'error' => 'Some of these marksheets are no longer listed. Reload the page.'];
            }
        }

        $signatures = $this->activeSignatures()->mapWithKeys(fn (SignatureMaster $s) => [$s->designation_name => (int) $s->pk])->all();
        $now = now();

        $published = DB::transaction(function () use ($rows, $codes, $courseKey, $signatures, $user, $now) {
            $n = 0;
            foreach ($codes as $code) {
                $row = $rows[$code];
                if ($row['published']) {
                    continue;
                }
                $snapshot = array_diff_key($row, array_flip(['number', 'generated_on', 'published'])) + ['signatures' => $signatures];
                $n += Marksheet::where('course_master_pk', (int) $courseKey)
                    ->where('student_master_pk', (int) $code)
                    ->where('status', Marksheet::STATUS_GENERATED)
                    ->update([
                        'status' => Marksheet::STATUS_PUBLISHED,
                        'published_by' => $user->user_id,
                        'published_at' => $now,
                        'snapshot' => json_encode($snapshot),
                        'modified_by' => $user->user_id,
                        'modified_date' => $now,
                    ]);
            }

            return $n;
        });
        $this->rowCache = [];

        return ['published' => $published, 'error' => null];
    }
}
