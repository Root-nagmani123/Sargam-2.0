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
        if (! Schema::hasColumn('mess_client_types', 'description')) {
            Schema::table('mess_client_types', function (Blueprint $table) {
                $table->text('description')->nullable()->after('client_name');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('mess_client_types', 'description')) {
            Schema::table('mess_client_types', function (Blueprint $table) {
                $table->dropColumn('description');
            });
        }
    }
};
