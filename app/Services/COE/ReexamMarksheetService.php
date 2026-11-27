<?php

namespace App\Services\COE;

use App\Models\COE\Reexam;
use App\Models\COE\ReexamMarksheet;
use App\Models\COE\SignatureMaster;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * COE Re-exam, step 7: the re-exam marksheet. **Pass / Fail only, no marks.**
 *
 * The flow, as the main marksheet (MarksheetService) but on its own table
 * (coe_reexam_marksheets; coe_marksheets is not touched):
 *   1. Once every re-exam subject of an OT has approved marks, ReexamService::result()
 *      gives them Pass / Fail (no moderation); then their re-exam marksheet is Generated,
 *      numbered RMS/<course short name>/<year>/<serial>, serial per course.
 *   2. Verify puts the signer's signature on it in the fixed order UDC → AAO → COE
 *      (Signature Master, the same rules and helpers as MarksheetService). The COE's
 *      signature publishes it and freezes it in `snapshot`.
 *
 * Rows keep the keys the views read; `reexam_key` is the coe_reexams id and `code` the
 * OT's student_master pk, which the routes carry.
 */
class ReexamMarksheetService
{
    /** @var list<array<string, mixed>>|null */
    private ?array $rowCache = null;

    public function __construct(private ReexamService $reexams, private MarksheetService $marksheets)
    {
    }

    /** The table is created by migration; until then re-exam marksheets are listed but neither numbered nor signed. */
    public static function enabled(): bool
    {
        static $enabled;

        return $enabled ??= ReexamService::enabled() && Schema::hasTable('coe_reexam_marksheets');
    }

    /* ───────────── Listing ───────────── */

    /** @return list<array<string, mixed>> every re-exam marksheet: Generated as the result is now, Published as published */
    public function rows(): array
    {
        return $this->rowCache ??= $this->build();
    }

    /**
     * The re-exam marksheets of one tab of Marksheet Generation / Verified Marksheet, where they
     * also list with the main ones: Archived when the re-exam drive is Closed, else Active.
     *
     * @return list<array<string, mixed>>
     */
    public function scopeRows(string $scope): array
    {
        return array_values(array_filter($this->rows(), fn ($m) => ($m['scope'] ?? 'active') === $scope));
    }

    /** @return list<array<string, mixed>> */
    public function reexamRows(string $reexamKey): array
    {
        return array_values(array_filter($this->rows(), fn ($m) => $m['reexam_key'] === $reexamKey));
    }

    public function find(string $reexamKey, string $code): ?array
    {
        foreach ($this->reexamRows($reexamKey) as $m) {
            if ($m['code'] === $code) {
                return $m;
            }
        }

        return null;
    }

    /** @return list<array<string, mixed>> */
    private function build(): array
    {
        if (! ReexamService::enabled()) {
            return [];
        }

        $candidates = [];
        $links = Reexam::with(['reexamDrive.course', 'reexamDrive.term', 'reexamDrive.examinationType', 'reexamDrive.componentMaps.subject', 'mainDrive'])
            ->orderBy('id')->get();
        foreach ($links as $link) {
            if (! $link->reexamDrive) {
                continue;
            }
            $result = $this->reexams->result($link->reexamDrive);
            foreach ($result['ots'] as $ot) {
                if (in_array($ot['result'], [ReexamService::RESULT_PASS, ReexamService::RESULT_FAIL], true)) {
                    $candidates[] = $this->row($link, $ot);
                }
            }
        }

        // Tab of the main lists: the re-exam drive's status now (a published snapshot carries none).
        $scopeOf = $links->mapWithKeys(fn (Reexam $l) => [(string) $l->id => $l->reexamDrive?->status === \App\Models\ExaminationDrive::STATUS_CLOSED ? 'archived' : 'active'])->all();
        $scoped = fn (array $rows) => array_map(fn ($row) => ['scope' => $scopeOf[$row['reexam_key']] ?? 'active'] + $row, $rows);

        if (! self::enabled()) {
            return $scoped(array_map(fn ($row) => $row + ['number' => '-', 'generated_on' => '-', 'published' => false, 'signatures' => []] + self::status(false, []), $candidates));
        }

        $this->generate($candidates);

        $records = ReexamMarksheet::query()->get()->keyBy(fn (ReexamMarksheet $m) => $m->reexam_id.'|'.$m->student_master_pk);
        $rows = [];
        foreach ($records as $record) {
            if ($record->isPublished() && is_array($record->snapshot)) {
                $rows[] = $this->stamp($record->snapshot, $record);
            }
        }
        foreach ($candidates as $row) {
            $record = $records[$row['reexam_key'].'|'.$row['code']] ?? null;
            if ($record && ! $record->isPublished()) {
                $rows[] = $this->stamp($row, $record);
            }
        }

        return $scoped($rows);
    }

    /** One OT's re-exam marksheet as the views read it. */
    private function row(Reexam $link, array $ot): array
    {
        $drive = $link->reexamDrive;
        $session = trim((string) $drive->academic_session);

        return [
            'code' => (string) $ot['student_pk'],
            'reexam_key' => (string) $link->id,
            'reexam_no' => (int) $link->reexam_no,
            // Its main drive's marksheet sheet (MarksheetService::sheet_key): listed right after it.
            'sheet_key' => (string) $link->main_drive_id,
            'course_key' => (string) $drive->course_master_pk,
            'course' => trim((string) ($drive->course->course_name ?? '')),
            'course_short' => trim((string) ($drive->course->couse_short_name ?? '')) ?: trim((string) ($drive->course->course_name ?? '')),
            'name' => $ot['name'],
            'roll' => $ot['roll'] ?: ($ot['ot_code'] ?: '-'),
            'service' => $ot['service'] ?: '-',
            'result' => $ot['result'],
            // Subject => Pass / Fail. No marks on a re-exam marksheet.
            'subjects' => array_map(fn ($s) => ['name' => $s['name'], 'result' => $s['result']], $ot['subjects']),
            'examination' => ReexamService::driveName($drive),
            'main_examination' => $link->mainDrive ? ReexamService::driveName($link->mainDrive) : '-',
            'batch' => $session === '' ? '-' : $session.' Batch',
            'exam_term' => trim((string) ($drive->term->term_name ?? '')) ?: '-',
            'exam_type' => trim((string) ($drive->examinationType->exam_type_name ?? '')) ?: '-',
            'year' => preg_match('/^\d{4}$/', $session) ? (int) $session : (int) now()->year,
        ];
    }

    /** A row with its record's number, date, status and the signatures put on it so far. */
    private function stamp(array $row, ReexamMarksheet $record): array
    {
        $signed = $record->isPublished() ? [] : ($record->signed ?? []);

        return array_replace($row, [
            'number' => $record->marksheet_number,
            'generated_on' => optional($record->generated_at)->format('d M Y') ?? '-',
            'published' => $record->isPublished(),
            ...self::status($record->isPublished(), array_keys($signed)),
        ], $record->isPublished() ? [] : [
            'signatures' => array_map(fn ($s) => (int) $s['signature'], $signed),
        ]);
    }

    /** Same statuses as the main marksheet (MarksheetService::STATUSES). */
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

    /* ───────────── Generation ───────────── */

    /**
     * Number the re-exam marksheets not generated yet, course by course, continuing the
     * course's re-exam serial. A second request at the same moment is turned away by the
     * unique keys and the page shows the first one's numbers.
     */
    private function generate(array $candidates): void
    {
        $byCourse = [];
        foreach ($candidates as $row) {
            $byCourse[$row['course_key']][] = $row;
        }

        foreach ($byCourse as $courseKey => $rows) {
            $coursePk = (int) $courseKey;
            $have = ReexamMarksheet::where('course_master_pk', $coursePk)->get(['reexam_id', 'student_master_pk'])
                ->map(fn ($m) => $m->reexam_id.'|'.$m->student_master_pk)->all();
            $new = array_values(array_filter($rows, fn ($r) => ! in_array($r['reexam_key'].'|'.$r['code'], $have, true)));
            if ($new === []) {
                continue;
            }

            try {
                DB::transaction(function () use ($coursePk, $new) {
                    $serial = (int) ReexamMarksheet::where('course_master_pk', $coursePk)->lockForUpdate()->max('serial');
                    $now = now();
                    $userId = auth()->user()?->user_id;
                    foreach ($new as $row) {
                        $serial++;
                        ReexamMarksheet::create([
                            'reexam_id' => (int) $row['reexam_key'],
                            'course_master_pk' => $coursePk,
                            'student_master_pk' => (int) $row['code'],
                            'serial' => $serial,
                            'marksheet_number' => self::number($row, $serial),
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

    /** RMS/FC101/2026/0001: course short name (letters and digits), batch year, serial. */
    private static function number(array $row, int $serial): string
    {
        $course = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $row['course_short']) ?: 'C'.$row['course_key'], 0, 20));

        return sprintf('RMS/%s/%d/%04d', $course, $row['year'], $serial);
    }

    /* ───────────── Verify & Publish ───────────── */

    /**
     * Verify these OTs' marksheets of one re-exam as $user (MarksheetService::verify's
     * rules): the UDC first, then the AAO, then the COE, whose signature publishes it and
     * needs all three Active in Signature Master. All or nothing; a published marksheet,
     * or one this designation already signed, is skipped.
     *
     * @param  string[]  $codes
     * @return array{as:?string, signed:int, published:int, error:?string}
     */
    public function verify(User $user, string $reexamKey, array $codes): array
    {
        $fail = fn (string $error, ?string $as = null) => ['as' => $as, 'signed' => 0, 'published' => 0, 'error' => $error];

        $as = MarksheetService::signerRole($user);
        if ($as === null) {
            return $fail('Only the UDC, AAO or COE can verify marksheets.');
        }
        if (! self::enabled()) {
            return $fail('Re-exam marksheets cannot be signed yet: run the coe_reexam_marksheets migration.', $as);
        }

        $rows = collect($this->reexamRows($reexamKey))->keyBy('code');
        foreach ($codes as $code) {
            if (! $rows->has($code)) {
                return $fail('Some of these marksheets are no longer listed. Reload the page.', $as);
            }
        }

        $before = MarksheetService::signsBefore($as);
        $records = ReexamMarksheet::where('reexam_id', (int) $reexamKey)->whereIn('student_master_pk', array_map('intval', $codes))
            ->get()->keyBy(fn (ReexamMarksheet $m) => (string) $m->student_master_pk);

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
        if ($toSign && ($problem = $this->marksheets->signatureProblem($as === 'COE' ? null : [$as]))) {
            return $fail($problem, $as);
        }
        $signatureOf = $this->marksheets->activeSignatures()
            ->mapWithKeys(fn (SignatureMaster $s) => [MarksheetService::signatoryOf($s->designation_name) => (int) $s->pk])->all();
        $now = now();

        [$signed, $published] = DB::transaction(function () use ($rows, $codes, $reexamKey, $before, $as, $signatureOf, $user, $now) {
            $signed = 0;
            $published = 0;
            foreach ($codes as $code) {
                $record = ReexamMarksheet::where('reexam_id', (int) $reexamKey)->where('student_master_pk', (int) $code)
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
                    $record->status = ReexamMarksheet::STATUS_PUBLISHED;
                    $record->published_by = $user->user_id;
                    $record->published_at = $now;
                    $record->snapshot = array_replace(
                        array_diff_key($rows[$code], array_flip(['number', 'generated_on', 'published', 'status', 'state'])),
                        ['signatures' => $signatures]
                    );
                    $published++;
                }
                $record->save();
                $signed++;
            }

            return [$signed, $published];
        });
        $this->rowCache = null;

        return ['as' => $as, 'signed' => $signed, 'published' => $published, 'error' => null];
    }
}
