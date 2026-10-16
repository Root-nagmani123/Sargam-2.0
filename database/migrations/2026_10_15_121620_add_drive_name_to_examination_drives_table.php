<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Guarded: shared/developer databases may already have the column.
        if (Schema::hasColumn('examination_drives', 'drive_name')) {
            return;
        }

        Schema::table('examination_drives', function (Blueprint $table) {
            $table->string('drive_name', 150)->nullable()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('examination_drives', 'drive_name')) {
            Schema::table('examination_drives', function (Blueprint $table) {
                $table->dropColumn('drive_name');
            });
        }
    }
};
