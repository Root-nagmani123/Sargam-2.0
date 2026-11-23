<?php

namespace App\Services\COE;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Marks read from scanned OMR answer sheets (coe_exam_omr, a copy of the OMR app's `sheets`).
 *
 * A component whose Evaluation Through is OMR takes its marks from the sheets whose
 * exam_id is that component's examination_drive_component_maps.id. A sheet belongs to the
 * OT whose roll number (coe_roll_numbers) equals the sheet's roll_number. Only sheets a
 * person has checked (status `verified`) count; when an OT has several, the latest wins.
 * A negative score (negative marking) counts as 0.
 *
 * Read-only: nothing here writes to coe_exam_omr.
 */
class OmrMarks
{
    /** The Evaluation Through value of an OMR-evaluated component. */
    public const SOURCE = 'OMR';

    public const TABLE = 'coe_exam_omr';

    /** Only sheets a person has checked carry final marks. */
    public const FINAL_STATUS = 'verified';

    /** Off (no OMR marks) until the coe_exam_omr migration has run. */
    public static function enabled(): bool
    {
        static $enabled;

        return $enabled ??= Schema::hasTable(self::TABLE);
    }

    /**
     * OMR scores for the OMR columns of a marks sheet.
     *
     * @param  list<array<string, mixed>>  $columns  ResultMarksService columns (with map_id and source)
     * @param  array<int, array<string, mixed>>  $rows  participants keyed by student pk (with roll)
     * @return array<string, array<int, float>> column key => student pk => score
     */
    public function scores(array $columns, array $rows): array
    {
        $keyOfMap = [];
        foreach ($columns as $col) {
            if (($col['source'] ?? null) === self::SOURCE && isset($col['map_id'])) {
                $keyOfMap[(int) $col['map_id']] = $col['key'];
            }
        }

        $studentOfRoll = [];
        foreach ($rows as $pk => $row) {
            $roll = trim((string) ($row['roll'] ?? ''));
            if ($roll !== '') {
                $studentOfRoll[mb_strtolower($roll)] = (int) $pk;
            }
        }

        if ($keyOfMap === [] || $studentOfRoll === [] || ! self::enabled()) {
            return [];
        }

        $out = [];
        DB::table(self::TABLE)
            ->whereIn('exam_id', array_keys($keyOfMap))
            ->where('status', self::FINAL_STATUS)
            ->whereIn('roll_number', array_keys($studentOfRoll))
            ->orderByDesc('id')
            ->get(['exam_id', 'roll_number', 'score'])
            ->each(function ($sheet) use (&$out, $keyOfMap, $studentOfRoll) {
                $key = $keyOfMap[(int) $sheet->exam_id] ?? null;
                $student = $studentOfRoll[mb_strtolower(trim((string) $sheet->roll_number))] ?? null;
                // Latest sheet first: an OT's older sheets are skipped.
                if ($key === null || $student === null || isset($out[$key][$student])) {
                    return;
                }
                $out[$key][$student] = max(0.0, round((float) $sheet->score, 2));
            });

        return $out;
    }
}
