<?php

namespace Database\Seeders\FC;

use App\Models\FC\FcFormField;
use Illuminate\Database\Seeder;

/**
 * Marks the joining-document fields that should be filled online (instead of
 * uploaded) by setting their `form_template`. Idempotent — safe to re-run.
 *
 * The 4 external cards (Aadhar, PAN, Cancel Cheque, Supporting Document) are
 * intentionally left as plain file uploads.
 *
 * So are the 3 Accounts Section documents — see self::UPLOAD_ONLY.
 */
class FcJoiningFillableFormsSeeder extends Seeder
{
    /**
     * Accounts Section documents that must stay plain uploads: statutory forms the
     * officer downloads (via the "View Sample" column), fills and signs offline,
     * then uploads. Listed here rather than deleted so the decision is explicit and
     * a future re-seed cannot quietly turn them back into online forms.
     *
     * @see database/migrations/FC/2026_07_26_000001_make_accounts_documents_upload_only.php
     */
    private const UPLOAD_ONLY = [
        'doc_group_insurance'     => 'group_insurance',
        'doc_nps_subscription'    => 'nps_subscription',
        'doc_employee_info_sheet' => 'employee_info_sheet',
    ];

    /** field_name => template_key (must match App\Support\FC\DocumentFormTemplates) */
    private const MAP = [
        'doc_family_details'      => 'family_details',
        'doc_debts_liabilities'   => 'debts_liabilities',
        'doc_immovable_prop'      => 'immovable_property',
        'doc_movable_prop'        => 'movable_property',
        'doc_close_relation'      => 'close_relation',
        'doc_dowry_decl'          => 'dowry_declaration',
        'doc_home_town'           => 'home_town',
        'doc_marital_status'      => 'marital_status',
        'doc_oath_affirmation'    => 'oath_affirmation',
        'doc_surety_bond_ias'     => 'surety_bond_ias',
        'doc_surety_bond_others'  => 'surety_bond_others',
        'doc_assumption_charge'   => 'assumption_charge',
        'doc_police_verification' => 'police_verification',
    ];

    public function run(): void
    {
        $total = 0;
        foreach (self::MAP as $fieldName => $templateKey) {
            $total += FcFormField::where('field_name', $fieldName)
                ->where('field_type', 'file')
                ->update(['form_template' => $templateKey]);
        }

        // Actively clear the upload-only documents rather than just skipping them,
        // so re-running this seeder corrects an environment where they were set.
        $cleared = FcFormField::whereIn('field_name', array_keys(self::UPLOAD_ONLY))
            ->where('field_type', 'file')
            ->whereNotNull('form_template')
            ->update(['form_template' => null]);

        $this->command?->info("FcJoiningFillableFormsSeeder: set form_template on {$total} field rows, cleared {$cleared} upload-only rows.");
    }
}
