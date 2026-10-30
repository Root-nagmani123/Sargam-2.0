<?php

namespace App\Imports\COE;

use App\Models\COE\LaptopResponse;
use App\Services\COE\LaptopDeclarationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;

/**
 * Bulk import of OT laptop status from the downloaded template.
 *
 * ── Matching ──
 * Rows are matched on Participant ID, never on OT code: generated_OT_code is not unique
 * (2,712 participants, 780 distinct codes, and repeats even inside one course+batch), so
 * matching on it would attach answers to the wrong people. The template ships pre-filled
 * with the id, so an administrator never types it.
 *
 * ── What a row can do ──
 *   blank Laptop Required   skipped, silently — the template lists every participant and
 *                           an administrator is expected to fill only some of them
 *   Yes / No                the answer is created, or the existing one corrected
 *   Yes with no Reason      the row fails: the reason is what explains the request, and
 *                           the single-record dialog demands it too
 *
 * Every failure is collected rather than thrown, so one bad row does not discard the
 * ninety-nine good ones — the controller reports both counts.
 *
 * The write itself goes through LaptopDeclarationService, the same path the dialog and the
 * trainee's own form use, so an imported answer is stamped exactly like a typed one.
 */
class LaptopStatusImport implements ToCollection, WithHeadingRow, WithStartRow
{
    /** @var array<int, string> */
    public array $failures = [];

    public int $importedCount = 0;

    public int $updatedCount = 0;

    public int $skippedCount = 0;

    /**
     * How the spreadsheet's free text maps onto the stored 1 / 0.
     *
     * Wider than "Yes"/"No" on purpose: the column comes back from Excel as Y, TRUE or 1
     * depending on how it was filled and what autocorrect did to it, and failing a whole
     * upload over "y" would be a poor trade.
     */
    private const YES = ['yes', 'y', '1', 'true', 'required', 'haan'];
    private const NO = ['no', 'n', '0', 'false', 'not required', 'nahi'];

    public function __construct(private LaptopDeclarationService $declarations)
    {
    }

    public function headingRow(): int
    {
        return 1;
    }

    public function startRow(): int
    {
        return 2;
    }

    public function collection(Collection $rows): void
    {
        if ($rows->isEmpty()) {
            $this->failures[] = 'The file has no data rows.';

            return;
        }

        [$participants, $existing] = $this->prefetch($rows);

        // Participants answered earlier in THIS file. The unique key catches a clash
        // against existing data; this catches two rows of the same upload answering for
        // the same person, where the second would otherwise silently win.
        $seenInFile = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + $this->startRow();
            $data = $this->normalise($row->toArray());

            $answer = $this->parseAnswer((string) ($data['laptop_required'] ?? ''));
            if ($answer === null) {
                // Blank is "leave this participant alone"; anything else is a typo the
                // administrator needs to see rather than have guessed at.
                if (trim((string) ($data['laptop_required'] ?? '')) === '') {
                    $this->skippedCount++;
                } else {
                    $this->failures[] = "Row {$rowNumber}: Laptop Required must be Yes or No.";
                }
                continue;
            }

            $participantId = (int) ($data['participant_id'] ?? 0);
            if ($participantId <= 0) {
                $this->failures[] = "Row {$rowNumber}: Participant ID is missing. Use the downloaded template — the import matches on that column.";
                continue;
            }

            $participant = $participants[$participantId] ?? null;
            if ($participant === null) {
                $this->failures[] = "Row {$rowNumber}: no participant found for ID {$participantId}.";
                continue;
            }

            if (isset($seenInFile[$participantId])) {
                $this->failures[] = "Row {$rowNumber}: {$participant->display_name} already has an answer in this file (row {$seenInFile[$participantId]}).";
                continue;
            }

            $reason = trim((string) ($data['reason'] ?? ''));

            // Mirrors LaptopDeclarationService::rules() — checked here as well so the
            // failure names the row and the person, which a validator exception cannot.
            if ($answer === LaptopResponse::LAPTOP_REQUIRED_YES && $reason === '') {
                $this->failures[] = "Row {$rowNumber}: {$participant->display_name} needs a Reason, because Laptop Required is Yes.";
                continue;
            }

            if (mb_strlen($reason) > 500) {
                $this->failures[] = "Row {$rowNumber}: the reason is longer than 500 characters.";
                continue;
            }

            try {
                DB::transaction(function () use ($participantId, $answer, $reason) {
                    $this->declarations->record($participantId, [
                        'laptop_required' => (string) $answer,
                        'reason' => $reason,
                    ], LaptopResponse::SOURCE_ADMIN);
                });
            } catch (\Throwable $e) {
                $this->failures[] = "Row {$rowNumber}: could not save {$participant->display_name}. Please try again.";
                continue;
            }

            $seenInFile[$participantId] = $rowNumber;
            isset($existing[$participantId]) ? $this->updatedCount++ : $this->importedCount++;
        }
    }

    /**
     * Load every participant the file refers to, and which of them already have an
     * answer, in two queries.
     *
     * Looking each one up inside the loop would be the N+1 the guidelines call out (G5) —
     * on a 2,700-row upload that is 5,400 queries.
     *
     * @param  Collection<int, object>  $rows
     * @return array{0: array<int, object>, 1: array<int, true>}
     */
    private function prefetch(Collection $rows): array
    {
        $ids = [];

        foreach ($rows as $row) {
            $data = $this->normalise($row->toArray());
            $id = (int) ($data['participant_id'] ?? 0);
            if ($id > 0) {
                $ids[$id] = true;
            }
        }

        if ($ids === []) {
            return [[], []];
        }

        $participants = DB::table('student_master')
            ->select('pk', 'display_name', 'course_master_pk', 'batch')
            ->whereIn('pk', array_keys($ids))
            ->get()
            ->keyBy(fn ($p) => (int) $p->pk)
            ->all();

        $existing = DB::table('coe_laptop_responses')
            ->whereIn('student_master_pk', array_keys($ids))
            ->pluck('student_master_pk')
            ->mapWithKeys(fn ($pk) => [(int) $pk => true])
            ->all();

        return [$participants, $existing];
    }

    /**
     * "Yes" / "No" as the stored 1 / 0, or null when the cell says neither.
     *
     * Null covers both the blank cell (skip) and the unrecognised one (fail); the caller
     * tells them apart, because only it knows which of the two deserves a message.
     */
    private function parseAnswer(string $value): ?int
    {
        $clean = strtolower(trim($value));

        if ($clean === '') {
            return null;
        }
        if (in_array($clean, self::YES, true)) {
            return LaptopResponse::LAPTOP_REQUIRED_YES;
        }
        if (in_array($clean, self::NO, true)) {
            return LaptopResponse::LAPTOP_REQUIRED_NO;
        }

        return null;
    }

    /**
     * Accept the heading however Excel mangled it.
     *
     * WithHeadingRow already lower-cases and underscores, but a file that has been through
     * a few hands picks up trailing spaces and stray capitals, and failing an entire
     * upload over "Participant ID " would be a poor trade.
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function normalise(array $row): array
    {
        $out = [];
        foreach ($row as $key => $value) {
            $clean = preg_replace('/[^a-z0-9]+/', '_', strtolower(trim((string) $key)));
            $out[trim($clean, '_')] = is_string($value) ? trim($value) : $value;
        }

        return $out;
    }
}
