<?php

namespace App\Imports\COE;

use App\Models\COE\RollNumber;
use App\Services\COE\RollNumberWriter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;

/**
 * Bulk import of roll numbers from the downloaded template.
 *
 * ── Matching ──
 * Rows are matched on Participant ID, never on OT code: generated_OT_code is not unique
 * (2,712 participants, 780 distinct codes, and repeats even inside one course+batch), so
 * matching on it would attach roll numbers to the wrong people. The template ships
 * pre-filled with the id, so an administrator never types it.
 *
 * ── What a row can do ──
 *   blank Roll Number   skipped, silently — the template lists every participant and an
 *                       administrator is expected to fill only some of them
 *   participant is new  a roll number is created
 *   already numbered    the existing one is UPDATED, unless it is frozen
 *   frozen              the row fails with a message naming the participant
 *
 * Every failure is collected rather than thrown, so one bad row does not discard the
 * ninety-nine good ones — the controller reports both counts.
 */
class RollNumberImport implements ToCollection, WithHeadingRow, WithStartRow
{
    /** @var array<int, string> */
    public array $failures = [];

    public int $importedCount = 0;

    public int $updatedCount = 0;

    public int $skippedCount = 0;

    public function __construct(private RollNumberWriter $writer)
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

        [$participants, $rollNumberOwners] = $this->prefetch($rows);

        // Roll numbers claimed earlier in THIS file. The database's unique key catches a
        // clash against existing data; this catches two rows of the same upload claiming
        // the same number, which would otherwise fail confusingly on the second one.
        $claimedInFile = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + $this->startRow();
            $data = $this->normalise($row->toArray());

            $rollNumber = trim((string) ($data['roll_number'] ?? ''));
            if ($rollNumber === '') {
                $this->skippedCount++;
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

            if (mb_strlen($rollNumber) > 50) {
                $this->failures[] = "Row {$rowNumber}: roll number is longer than 50 characters.";
                continue;
            }

            if (isset($claimedInFile[$rollNumber])) {
                $this->failures[] = "Row {$rowNumber}: roll number {$rollNumber} is used more than once in this file (also row {$claimedInFile[$rollNumber]}).";
                continue;
            }

            $owner = $rollNumberOwners[$rollNumber] ?? null;
            if ($owner !== null && (int) $owner !== $participantId) {
                $this->failures[] = "Row {$rowNumber}: roll number {$rollNumber} already belongs to someone else.";
                continue;
            }

            $existing = RollNumber::where('student_master_pk', $participantId)->first();
            if ($existing !== null && $existing->isFrozen()) {
                $this->failures[] = "Row {$rowNumber}: {$participant->display_name} is frozen at {$existing->roll_number} and cannot be changed.";
                continue;
            }

            try {
                DB::transaction(function () use ($participant, $rollNumber, $existing) {
                    // deferCount: the allocation is recounted once after the file, not
                    // once per row.
                    $this->writer->assign($participant, $rollNumber, $existing, true);
                });
            } catch (\Throwable $e) {
                $this->failures[] = "Row {$rowNumber}: could not save {$rollNumber}. " . $this->hint($e);
                continue;
            }

            $claimedInFile[$rollNumber] = $rowNumber;
            $existing === null ? $this->importedCount++ : $this->updatedCount++;
        }

        $this->writer->flushCounts();
    }

    /**
     * Load every participant and every taken roll number the file refers to, in two
     * queries.
     *
     * Looking each one up inside the loop would be the N+1 the guidelines call out (G5) —
     * on a 2,700-row upload that is 5,400 queries.
     *
     * @param  Collection<int, object>  $rows
     * @return array{0: array<int, object>, 1: array<string, int>}
     */
    private function prefetch(Collection $rows): array
    {
        $ids = [];
        $rollNumbers = [];

        foreach ($rows as $row) {
            $data = $this->normalise($row->toArray());
            $id = (int) ($data['participant_id'] ?? 0);
            if ($id > 0) {
                $ids[$id] = true;
            }
            $value = trim((string) ($data['roll_number'] ?? ''));
            if ($value !== '') {
                $rollNumbers[$value] = true;
            }
        }

        $participants = array_keys($ids) === []
            ? collect()
            : DB::table('student_master')
                ->select('pk', 'display_name', 'course_master_pk', 'batch')
                ->whereIn('pk', array_keys($ids))
                ->get();

        $owners = array_keys($rollNumbers) === []
            ? collect()
            : RollNumber::whereIn('roll_number', array_keys($rollNumbers))
                ->get(['roll_number', 'student_master_pk']);

        return [
            $participants->keyBy(fn ($p) => (int) $p->pk)->all(),
            $owners->mapWithKeys(fn ($r) => [$r->roll_number => (int) $r->student_master_pk])->all(),
        ];
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

    private function hint(\Throwable $e): string
    {
        return str_contains($e->getMessage(), 'coe_rn_roll_number_unique')
            ? 'That roll number is already in use.'
            : 'Please try again.';
    }
}
