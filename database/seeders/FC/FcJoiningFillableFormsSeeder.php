<?php

namespace Database\Seeders\FC;

use App\Models\FC\FcFormField;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

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
        // Both writes are one statement each and must land together, so an aborted
        // seed cannot leave some documents fillable and others not (G7).
        [$total, $cleared] = DB::transaction(fn () => [
            $this->applyTemplates(),
            $this->clearUploadOnly(),
        ]);

        $this->command?->info("FcJoiningFillableFormsSeeder: set form_template on {$total} field rows, cleared {$cleared} upload-only rows.");
    }

    /**
     * Set every fillable document's template in a single UPDATE.
     *
     * Each field maps to a different template key, so this uses a CASE expression
     * rather than one UPDATE per field (G5: no queries inside loops). The SQL
     * structure is built only from self::MAP — a private constant — and every
     * field name and template key is passed as a bound parameter.
     */
    private function applyTemplates(): int
    {
        $cases = '';
        $bindings = [];

        foreach (self::MAP as $fieldName => $templateKey) {
            $cases .= ' WHEN ? THEN ?';
            $bindings[] = $fieldName;
            $bindings[] = $templateKey;
        }

        $names = array_keys(self::MAP);
        $placeholders = implode(', ', array_fill(0, count($names), '?'));

        // updated_at is set explicitly: raw SQL bypasses Eloquent's timestamp
        // handling, and the per-field loop this replaces did bump it.
        return DB::update(
            "UPDATE fc_form_fields
                SET form_template = CASE field_name{$cases} END,
                    updated_at = ?
              WHERE field_type = 'file'
                AND field_name IN ({$placeholders})",
            array_merge($bindings, [now()], $names)
        );
    }

    /**
     * Actively clear the upload-only documents rather than just skipping them, so
     * re-running this seeder corrects an environment where they were set.
     */
    private function clearUploadOnly(): int
    {
        return FcFormField::whereIn('field_name', array_keys(self::UPLOAD_ONLY))
            ->where('field_type', 'file')
            ->whereNotNull('form_template')
            ->update(['form_template' => null]);
    }
}
