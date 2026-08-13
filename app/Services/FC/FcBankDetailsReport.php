<?php

namespace App\Services\FC;

use App\Models\FC\FcForm;
use Illuminate\Database\Query\Builder;

/**
 * Bank Details report — account details and the three supporting documents a trainee uploads
 * (FC-101 step 4).
 *
 * Stores into its own table, so one extra join is needed. That join is safe:
 * new_registration_bank_details_masters.user_id carries a UNIQUE index, so at most one row can
 * match and the driving row cannot be multiplied — the failure mode that would silently show a
 * trainee twice in the table and in every export.
 *
 * Only the columns the form actually maps are reported. The table also carries branch_name,
 * account_type and a legacy bank_passbook_path, none of which FC-101 step 4 collects; showing
 * them would mean columns that are empty by construction.
 */
class FcBankDetailsReport extends FcStepReport
{
    private const TABLE = 'new_registration_bank_details_masters';

    public function key(): string
    {
        return 'bank-report';
    }

    public function title(): string
    {
        // Distinct from the older ReportController::bankDetails() screen at
        // /admin/reports/bank-details, which is also titled "Bank Details" and is still live.
        // Both were kept deliberately; identical titles made their downloads indistinguishable.
        return 'Bank Details & Documents';
    }

    public function subtitle(): string
    {
        return 'Bank account details and supporting documents — course wise, with Excel, PDF and document ZIP export.';
    }

    public function statusLabels(): array
    {
        return ['submitted' => 'Details entered only', 'pending' => 'Not entered only'];
    }

    public function reportColumns(): array
    {
        return [
            'account_holder_name' => ['label' => 'Account Holder Name', 'orderable' => true],
            'account_no' => ['label' => 'Account Number', 'orderable' => true],
            'ifsc_code' => ['label' => 'Bank IFSC Code', 'orderable' => true],
            'bank_name' => ['label' => 'Bank Name', 'orderable' => true],
            'doc_aadhar_path' => ['label' => 'Aadhar Card', 'orderable' => false, 'file' => true],
            'doc_pan_path' => ['label' => 'PAN Card', 'orderable' => false, 'file' => true],
            'doc_cancel_cheque_path' => ['label' => 'Cancel Cheque', 'orderable' => false, 'file' => true],
        ];
    }

    protected function reportExpressions(FcForm $form): array
    {
        // NULL rather than a column reference when a deployment lacks the table, so the report
        // degrades to empty cells instead of failing with an unknown-column error.
        if (! $this->tableAvailable()) {
            return array_map(fn () => 'NULL', $this->reportColumns());
        }

        $out = [];
        foreach (array_keys($this->reportColumns()) as $key) {
            $out[$key] = fc_schema_has_column(self::TABLE, $key)
                ? "NULLIF(TRIM(`bd`.`{$key}`), '')"
                : 'NULL';
        }

        return $out;
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
        $bdCol = fc_user_col(self::TABLE);
        $query->leftJoin(self::TABLE.' as bd', "bd.{$bdCol}", '=', "s1.{$s1Col}");
    }

    /**
     * "Entered" means the account itself is on record. A document uploaded without an account
     * number is not a completed step — the documents support the details, they are not the
     * details.
     */
    public function statusColumns(): array
    {
        return $this->tableAvailable()
            ? ['`bd`.`account_no`', '`bd`.`account_holder_name`']
            : [];
    }

    protected function reportOrderSql(string $key, FcForm $form): ?string
    {
        if (! $this->tableAvailable()) {
            return null;
        }

        return in_array($key, ['account_holder_name', 'account_no', 'ifsc_code', 'bank_name'], true)
            ? "bd.{$key}"
            : null;
    }

    protected function extraSearchColumns(): array
    {
        if (! $this->tableAvailable()) {
            return [];
        }

        return ['bd.account_holder_name', 'bd.account_no', 'bd.ifsc_code', 'bd.bank_name'];
    }

    protected function probeField(): array
    {
        return ['table' => self::TABLE, 'column' => 'account_no'];
    }

    private function tableAvailable(): bool
    {
        return fc_schema_has_table(self::TABLE) && fc_schema_has_column(self::TABLE, 'account_no');
    }
}
