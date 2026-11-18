<?php

namespace App\Services\COE;

use App\Models\COE\OmrResponse;
use Illuminate\Support\Facades\DB;

/**
 * Finds the participant an OMR answer sheet belongs to: the sheet's roll number
 * (the export's ENR_NO column) against coe_roll_numbers.roll_number.
 *
 * Compared trimmed and without regard to case — the column's collation
 * (utf8mb4_general_ci) does the same, which also keeps its unique index usable.
 * roll_number is unique there, so a number matches at most one participant; a
 * blank or partly unread ('*') roll number is never looked up.
 */
class OmrParticipantMatcher
{
    public const NOT_READABLE = 'Roll No. not readable';

    public const NOT_FOUND = 'No participant with this Roll No.';

    private const LOOKUP_CHUNK = 1000;

    /**
     * @param  iterable<string|null>  $rollNumbers
     * @return array<string, array{student_master_pk:int|null, match_remarks:string|null}> keyed by key()
     */
    public function match(iterable $rollNumbers): array
    {
        $wanted = [];
        foreach ($rollNumbers as $no) {
            if ($this->readable($no)) {
                $wanted[$this->key($no)] = true;
            }
        }

        $found = [];
        foreach (array_chunk(array_keys($wanted), self::LOOKUP_CHUNK) as $chunk) {
            DB::table('coe_roll_numbers')
                ->whereIn('roll_number', array_map('strval', $chunk))
                ->get(['student_master_pk', 'roll_number'])
                ->each(function ($row) use (&$found) {
                    $found[$this->key($row->roll_number)][] = (int) $row->student_master_pk;
                });
        }

        $result = [];
        foreach (array_keys($wanted) as $key) {
            $pks = array_values(array_unique($found[$key] ?? []));
            $result[$key] = match (count($pks)) {
                0 => ['student_master_pk' => null, 'match_remarks' => self::NOT_FOUND],
                1 => ['student_master_pk' => $pks[0], 'match_remarks' => null],
                default => ['student_master_pk' => null, 'match_remarks' => 'Matches ' . count($pks) . ' participants — not linked'],
            };
        }

        return $result;
    }

    /** The link for one sheet, given the result of match(). */
    public function linkFor(?string $rollNumber, array $matches): array
    {
        if (! $this->readable($rollNumber)) {
            return ['student_master_pk' => null, 'match_remarks' => self::NOT_READABLE];
        }

        return $matches[$this->key($rollNumber)] ?? ['student_master_pk' => null, 'match_remarks' => self::NOT_FOUND];
    }

    /**
     * Re-links every sheet of one upload — for after roll numbers have been
     * generated or corrected on COE › Roll Number.
     *
     * @return array{linked:int, total:int}
     */
    public function rematchUpload(int $uploadPk): array
    {
        $linked = 0;
        $total = 0;

        OmrResponse::where('coe_omr_upload_pk', $uploadPk)
            ->select(['pk', 'roll_number', 'student_master_pk', 'match_remarks'])
            ->chunkById(self::LOOKUP_CHUNK, function ($sheets) use (&$linked, &$total) {
                $matches = $this->match($sheets->pluck('roll_number'));
                $changed = [];
                foreach ($sheets as $sheet) {
                    $link = $this->linkFor($sheet->roll_number, $matches);
                    if ($sheet->student_master_pk !== $link['student_master_pk'] || $sheet->match_remarks !== $link['match_remarks']) {
                        $changed[(int) $sheet->pk] = $link;
                    }
                    $total++;
                    $linked += $link['student_master_pk'] !== null ? 1 : 0;
                }
                $this->updateLinks($changed);
            }, 'pk');

        return ['linked' => $linked, 'total' => $total];
    }

    /**
     * One UPDATE for a chunk of changed sheets instead of one per sheet.
     *
     * @param  array<int, array{student_master_pk:int|null, match_remarks:string|null}>  $links  sheet pk => link
     */
    private function updateLinks(array $links): void
    {
        if ($links === []) {
            return;
        }

        $when = implode(' ', array_fill(0, count($links), 'WHEN ? THEN ?'));
        $in = implode(',', array_fill(0, count($links), '?'));
        $student = [];
        $remarks = [];
        foreach ($links as $pk => $link) {
            array_push($student, $pk, $link['student_master_pk']);
            array_push($remarks, $pk, $link['match_remarks']);
        }

        DB::update(
            'UPDATE coe_omr_responses SET student_master_pk = CASE pk ' . $when . ' END, match_remarks = CASE pk ' . $when . ' END WHERE pk IN (' . $in . ')',
            array_merge($student, $remarks, array_keys($links))
        );
    }

    private function key(string $no): string
    {
        return strtoupper(trim($no));
    }

    private function readable(?string $no): bool
    {
        return $no !== null && trim($no) !== '' && ! str_contains($no, '*');
    }
}
