<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE leave_application MODIFY from_date DATETIME NOT NULL');
        DB::statement('ALTER TABLE leave_application MODIFY to_date DATETIME NOT NULL');
    }

    public function down(): void
    {
        // Deliberately one-way. Narrowing DATETIME back to DATE would silently drop
        // the time on every leave row (stationed leave depends on it). If this change
        // must be reverted, restore leave_application from a backup instead.
    }
};
