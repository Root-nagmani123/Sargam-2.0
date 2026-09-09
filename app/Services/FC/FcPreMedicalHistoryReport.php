<?php

namespace App\Services\FC;

use App\Models\FC\FcForm;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Pre-Medical History report — the declarations a trainee makes in the Pre-Medical History
 * section of FC-101 step 2, plus the supporting document.
 *
 * The join here is the awkward one. Unlike every other step table, fc_pre_history.user_id is
 * NOT unique — it carries a plain MUL index, and the write path
 * (DynamicFormService::saveFcPreHistoryGroup) does updateOrCreate([user_id, course_master_pk]),
 * so a trainee who has been on two courses legitimately has two rows. A plain
 * `LEFT JOIN ... ON ph.user_id = s1.user_id` would return both and show that trainee twice in
 * the table and in every export — silently, and only for the people who have been on more than
 * one course, which is exactly the kind of bug that reaches production.
 *
 * So the join picks exactly ONE row per trainee, by primary key, via a correlated subquery:
 * the row for THIS course if there is one, otherwise a legacy row saved before course scoping
 * existed (course_master_pk IS NULL — 6 such rows at the time of writing), newest first as the
 * tie-break. One row in, one row out, whatever the data does later.
 */
class FcPreMedicalHistoryReport extends FcStepReport
{
    private const TABLE = 'fc_pre_history';

    /**
     * The five free-text declarations. Named once because both the status filter and the search
     * predicate need exactly this list — the supporting document is not a declaration.
     */
    private const DECLARATION_COLUMNS = [
        'allergy_illness',
        'prolonged_medication',
        'hospital_history',
        'altitude_illness',
        'additional_info',
    ];

    public function key(): string
    {
        return 'pre-medical-history';
    }

    public function title(): string
    {
        return 'Pre-Medical History';
    }

    public function subtitle(): string
    {
        return 'Declarations for post-arrival medical processing — course wise, with Excel, PDF and document ZIP export.';
    }

    public function statusLabels(): array
    {
        return ['submitted' => 'Declared only', 'pending' => 'Not declared only'];
    }

    public function reportColumns(): array
    {
        return [
            'allergy_illness' => ['label' => 'Allergy / Illness / Injury / Disability', 'orderable' => false, 'long' => true],
            'prolonged_medication' => ['label' => 'Prolonged Medication', 'orderable' => false, 'long' => true],
            'hospital_history' => ['label' => 'Hospitalisation / Surgery', 'orderable' => false, 'long' => true],
            'altitude_illness' => ['label' => 'Altitude Illness / Motion Sickness', 'orderable' => false, 'long' => true],
            'additional_info' => ['label' => 'Other Relevant Medical Information', 'orderable' => false, 'long' => true],
            'doc_path' => ['label' => 'Supporting Document', 'orderable' => false, 'file' => true],
        ];
    }

    protected function reportExpressions(FcForm $form): array
    {
        if (! $this->tableAvailable()) {
            return array_map(fn () => 'NULL', $this->reportColumns());
        }

        $out = [];
        foreach (array_keys($this->reportColumns()) as $key) {
            $out[$key] = fc_schema_has_column(self::TABLE, $key)
                ? ($key === 'doc_path' ? "NULLIF(TRIM(`ph`.`doc_path`), '')" : "`ph`.`{$key}`")
                : 'NULL';
        }

        return $out;
    }

    protected function applyJoins(Builder $query, FcForm $form): void
    {
        if (! $this->tableAvailable()) {
            return;
        }

        $s1Col = fc_user_col('student_master_firsts');
        $phCol = fc_user_col(self::TABLE);
        $coursePk = $form->course_master_pk;

        // Join on the PRIMARY KEY of a single chosen row. See the class docblock for why this is
        // not a plain user_id join. The subquery is an indexed lookup on user_id, so the cost is
        // one seek per driving row rather than a scan.
        if ($coursePk !== null && fc_schema_has_column(self::TABLE, 'course_master_pk')) {
            $query->leftJoin(self::TABLE.' as ph', function ($join) use ($s1Col, $phCol, $coursePk) {
                $join->on('ph.id', '=', DB::raw(
                    '(select `ph2`.`id` from `'.self::TABLE.'` as `ph2` '
                    .'where `ph2`.`'.$phCol.'` = `s1`.`'.$s1Col.'` '
                    .'and (`ph2`.`course_master_pk` = ? or `ph2`.`course_master_pk` is null) '
                    // course match first, then legacy rows, newest id as the tie-break
                    .'order by (`ph2`.`course_master_pk` = ?) desc, `ph2`.`id` desc limit 1)'
                ))->addBinding([$coursePk, $coursePk], 'join');
            });

            return;
        }

        // Form has no course: take the trainee's newest row rather than risk two.
        $query->leftJoin(self::TABLE.' as ph', function ($join) use ($s1Col, $phCol) {
            $join->on('ph.id', '=', DB::raw(
                '(select max(`ph2`.`id`) from `'.self::TABLE.'` as `ph2` '
                .'where `ph2`.`'.$phCol.'` = `s1`.`'.$s1Col.'`)'
            ));
        });
    }

    /**
     * "Declared" means the trainee wrote something in any of the five declaration fields. A
     * supporting document on its own is not a declaration — it supports one.
     */
    public function statusColumns(): array
    {
        if (! $this->tableAvailable()) {
            return [];
        }

        return array_values(array_map(
            fn ($c) => "`ph`.`{$c}`",
            array_filter(self::DECLARATION_COLUMNS, fn ($c) => fc_schema_has_column(self::TABLE, $c))
        ));
    }

    protected function extraSearchColumns(): array
    {
        if (! $this->tableAvailable()) {
            return [];
        }

        return array_map(
            fn ($c) => "ph.{$c}",
            array_filter(self::DECLARATION_COLUMNS, fn ($c) => fc_schema_has_column(self::TABLE, $c))
        );
    }

    protected function probeField(): array
    {
        return ['table' => self::TABLE, 'column' => 'allergy_illness'];
    }

    private function tableAvailable(): bool
    {
        return fc_schema_has_table(self::TABLE) && fc_schema_has_column(self::TABLE, 'allergy_illness');
    }
}
