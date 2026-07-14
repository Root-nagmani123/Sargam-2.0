<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add a length rule to the dynamic Passport No fields (currently only max:20).
     * Passport numbers are 8–9 characters, so require min 8 / max 9 when provided.
     */
    public function up(): void
    {
        DB::table('fc_form_fields')
            ->where('field_name', 'passport_no')
            ->where('validation_rules', 'nullable|string|max:20')
            ->update(['validation_rules' => 'nullable|string|min:8|max:9']);
    }

    public function down(): void
    {
        DB::table('fc_form_fields')
            ->where('field_name', 'passport_no')
            ->where('validation_rules', 'nullable|string|min:8|max:9')
            ->update(['validation_rules' => 'nullable|string|max:20']);
    }
};
