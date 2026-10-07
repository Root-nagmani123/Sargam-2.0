<?php

namespace App\Services\FC;

use App\Models\FC\FcForm;

/**
 * Vision Statement report — each trainee's Statement of Vision and Aspirations (FC-101 step 7).
 *
 * A single textarea mapped to student_master_firsts.vision_statement, so there is nothing to
 * join: s1 is already in the base query. Everything else — the identity columns, filters,
 * search, column narrowing — comes from {@see FcStepReport}.
 */
class FcVisionStatementReport extends FcStepReport
{
    public function key(): string
    {
        return 'vision-statement';
    }

    public function title(): string
    {
        return 'Vision Statement';
    }

    public function subtitle(): string
    {
        return 'Statement of Vision and Aspirations — course wise, with Excel and PDF export.';
    }

    public function reportColumns(): array
    {
        return [
            'vision_statement' => ['label' => 'Vision Statement', 'orderable' => false, 'long' => true],
        ];
    }

    protected function reportExpressions(FcForm $form): array
    {
        return [
            'vision_statement' => '`s1`.`vision_statement`',
        ];
    }

    public function statusColumns(): array
    {
        return ['`s1`.`vision_statement`'];
    }

    protected function extraSearchColumns(): array
    {
        return fc_schema_has_column('student_master_firsts', 'vision_statement')
            ? ['s1.vision_statement']
            : [];
    }

    protected function probeField(): array
    {
        return ['table' => 'student_master_firsts', 'column' => 'vision_statement'];
    }
}
