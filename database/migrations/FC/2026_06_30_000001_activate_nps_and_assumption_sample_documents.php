<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Data fix: the "NPS Subscription Registration Form" (doc_nps_subscription) and
 * "Certificate of Assumption of Charge" (doc_assumption_charge) sample documents
 * were left is_active=0 in fc_joining_sample_documents, so their "View Sample"
 * link did not render on the joining-document checklist. The PDF files already
 * exist on disk; only the active flag needs flipping.
 *
 * Idempotent: safe to run more than once.
 */
return new class extends Migration
{
    private array $fieldNames = [
        'doc_nps_subscription',
        'doc_assumption_charge',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('fc_joining_sample_documents')) {
            return;
        }

        DB::table('fc_joining_sample_documents')
            ->whereIn('field_name', $this->fieldNames)
            ->where('is_active', 0)
            ->update(['is_active' => 1, 'updated_at' => now()]);
    }

    public function down(): void
    {
        // No-op: we cannot know the prior per-row state, and reverting these to
        // inactive would re-hide working samples. Leave the active flag as-is.
    }
};
