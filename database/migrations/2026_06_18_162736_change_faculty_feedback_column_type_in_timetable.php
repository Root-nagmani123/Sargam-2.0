<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE `timetable` MODIFY COLUMN `Faculty_feedback` JSON NULL');
    }

    public function down()
    {
        DB::statement('ALTER TABLE `timetable` MODIFY COLUMN `Faculty_feedback` TINYINT(4) NULL');
    }
};
