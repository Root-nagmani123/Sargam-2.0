<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE fc_activity_master MODIFY COLUMN entry_policy ENUM('unique','upsert','repeat') NOT NULL DEFAULT 'unique'");
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::table('fc_activity_master')->where('entry_policy', 'repeat')->update(['entry_policy' => 'upsert']);

        DB::statement("ALTER TABLE fc_activity_master MODIFY COLUMN entry_policy ENUM('unique','upsert') NOT NULL DEFAULT 'unique'");
    }
};
