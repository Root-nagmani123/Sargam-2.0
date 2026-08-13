<?php

namespace App\Services\FC;

use App\Models\FC\FcForm;
use Illuminate\Database\Query\Builder;

/**
 * Special Assistant report — physical impairment information and the reasonable adjustments a
 * trainee has asked for (FC-101 step 6).
 *
 * Unlike the Vision Statement, this step stores into its own table, so one extra join is needed.
 * That join is safe: student_iosr_reasonable_adjust_masters.user_id carries a UNIQUE index, so
 * at most one row can match and the driving row cannot be multiplied — the failure mode that
 * would silently show a trainee twice in the table and in every export.
 */
class FcSpecialAssistantReport extends FcStepReport
{
    private const TABLE = 'student_iosr_reasonable_adjust_masters';

    public function key(): string
    {
        return 'special-assistant';
    }

    public function title(): string
    {
        return 'Special Assistant';
    }

    public function subtitle(): string
    {
        return 'Physical impairment information and reasonable adjustments — course wise, with Excel and PDF export.';
    }

    public function statusLabels(): array
    {
        return ['submitted' => 'Recorded only', 'pending' => 'Not recorded only'];
    }

    public function reportColumns(): array
    {
        return [
            'physical_impairment_info' => ['label' => 'Physical Impairment Information', 'orderable' => false, 'long' => true],
            'adjustment_required' => ['label' => 'Reasonable Adjustments', 'orderable' => false, 'long' => true],
            'adjustment_type' => ['label' => 'Document Title', 'orderable' => true],
            'doc_path' => ['label' => 'Document', 'orderable' => false, 'file' => true],
        ];
    }

    protected function reportExpressions(FcForm $form): array
    {
        // NULL rather than a column reference when a deployment lacks the table, so the report
        // degrades to empty cells instead of failing with an unknown-column error.
        if (! $this->tableAvailable()) {
            return [
                'physical_impairment_info' => 'NULL',
                'adjustment_required' => 'NULL',
                'adjustment_type' => 'NULL',
                'doc_path' => 'NULL',
            ];
        }

        return [
            'physical_impairment_info' => '`sa`.`physical_impairment_info`',
            'adjustment_required' => '`sa`.`adjustment_required`',
            'adjustment_type' => "NULLIF(TRIM(`sa`.`adjustment_type`), '')",
            'doc_path' => "NULLIF(TRIM(`sa`.`doc_path`), '')",
        ];
    }

    protected function applyJoins(Builder $query, FcForm $form): void
    {
        if (! $this->tableAvailable()) {
            return;
        }

        // Joined through s1, not the tracker: s1 has already been resolved against every id
        // shape by scopedBase(), so hanging off it keeps this report's row set identical to the
        // others' instead of re-deriving the resolution and disagreeing on edge cases.
        $s1Col = fc_user_col('student_master_firsts');
        $saCol = fc_user_col(self::TABLE);
        $query->leftJoin(self::TABLE.' as sa', "sa.{$saCol}", '=', "s1.{$s1Col}");
    }

    /**
     * "Recorded" means the trainee entered something in either free-text field. A document title
     * or an upload alone is not treated as a completed step — those are attachments to an
     * answer, not the answer.
     */
    public function statusColumns(): array
    {
        return $this->tableAvailable()
            ? ['`sa`.`physical_impairment_info`', '`sa`.`adjustment_required`']
            : [];
    }

    protected function reportOrderSql(string $key, FcForm $form): ?string
    {
        return ($key === 'adjustment_type' && $this->tableAvailable()) ? 'sa.adjustment_type' : null;
    }

    protected function extraSearchColumns(): array
    {
        if (! $this->tableAvailable()) {
            return [];
        }

        return ['sa.physical_impairment_info', 'sa.adjustment_required', 'sa.adjustment_type'];
    }

    protected function probeField(): array
    {
        return ['table' => self::TABLE, 'column' => 'physical_impairment_info'];
    }

    private function tableAvailable(): bool
    {
        return fc_schema_has_table(self::TABLE)
            && fc_schema_has_column(self::TABLE, 'physical_impairment_info');
    }
}
