<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Master table behind Communications → Club/ Society → Define Club/ Society.
 *
 * Column/type conventions follow the sibling masters (building_master et al.):
 * `pk` primary key, `active_inactive` soft status flag, and DB-managed
 * created_date / updated_date timestamps rather than Eloquent timestamps.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('club_society_master')) {
            return;
        }

        Schema::create('club_society_master', function (Blueprint $table) {
            $table->bigIncrements('pk');
            $table->string('club_society_name', 255)->nullable();
            $table->integer('active_inactive')->default(1);
            $table->timestamp('created_date')->useCurrent();
            $table->timestamp('updated_date')->useCurrent()->useCurrentOnUpdate();

            $table->index('club_society_name', 'club_society_master_name_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('club_society_master');
    }
};
