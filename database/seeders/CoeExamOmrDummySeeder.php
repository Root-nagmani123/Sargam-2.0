<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Dummy rows for coe_exam_omr (dev / testing only, never run on production).
 *
 * Roll numbers, OT codes and names are real Sargam OTs from roll number allocation 1
 * (course TEST-PHASE-III-MEMO/NOTICE/DISCIPLINE, batch 2026), so the rows match
 * coe_roll_numbers.roll_number and student_master.generated_OT_code.
 * exam_id is the examination_drive_component_maps.id of the paper: 159 = drive 415
 * "test1", subject Climate, component Written (max 100) on the dev database (no marks saved yet).
 * Its scores show on Result Entry once that row's Evaluation Through is OMR.
 * Safe to run again: one dummy sheet per roll number, updated in place.
 *
 *   php artisan db:seed --class=CoeExamOmrDummySeeder
 */
class CoeExamOmrDummySeeder extends Seeder
{
    private const EXAM_ID = 159;

    private const QUESTIONS = 50;

    /** roll number, OT code, name, correct, wrong, blank */
    private const SHEETS = [
        ['t2-001', 'K09', 'B SIVACHANDRAN',  42, 6, 2],
        ['t2-002', 'O13', 'PRANAY MITTAL',   38, 9, 3],
        ['t2-003', 'I03', 'RIA KAUR SETHI',  45, 4, 1],
        ['t2-004', 'K18', 'VIVEK SHINDE',    31, 15, 4],
        ['t2-005', 'B40', 'ADITYA ACHARYA',  36, 10, 4],
    ];

    public function run(): void
    {
        $now = now();

        foreach (self::SHEETS as [$roll, $ot, $name, $correct, $wrong, $blank]) {
            // 1 mark per correct answer, no negative marking.
            $score = (float) $correct;

            DB::table('coe_exam_omr')->updateOrInsert(
                ['roll_number' => $roll],
                [
                    'exam_id'        => self::EXAM_ID,
                    'upload_name'    => "omr_{$roll}.jpg",
                    'path'           => 'omr-sheets/exam-' . self::EXAM_ID . "/omr_{$roll}.jpg",
                    'file_hash'      => sha1($roll),
                    'page'           => 1,
                    'candidate_name' => $name,
                    'name_source'    => 'ocr',
                    'roll_detail'    => null,
                    'ot_code'        => $ot,
                    'ot_detail'      => null,
                    'series'         => 'A',
                    'upload_series'  => 'A',
                    'answers'        => null,
                    'fills'          => null,
                    'review'         => json_encode([]),
                    'status'         => 'verified',
                    'error'          => null,
                    'correct'        => $correct,
                    'wrong'          => $wrong,
                    'multiple'       => 0,
                    'blank'          => $blank,
                    'max_questions'  => self::QUESTIONS,
                    'score'          => $score,
                    'percentage'     => round($score / self::QUESTIONS * 100, 2),
                    'reviewed_by'    => null,
                    'reviewed_at'    => $now,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ]
            );
        }
    }
}
