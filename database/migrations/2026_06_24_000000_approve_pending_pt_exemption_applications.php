<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('leave_application')
            ->where('leave_type', 'PT_EXEMPTION')
            ->where('status', 1)
            ->update([
                'status' => 2,
                'approved_at' => DB::raw('COALESCE(submitted_at, modified_date, created_date)'),
            ]);
    }

    public function down(): void
    {
        // Cannot reliably restore prior pending state.
    }
};
