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
        DB::statement('ALTER TABLE leave_application MODIFY from_date DATE NOT NULL');
        DB::statement('ALTER TABLE leave_application MODIFY to_date DATE NOT NULL');
    }
};
