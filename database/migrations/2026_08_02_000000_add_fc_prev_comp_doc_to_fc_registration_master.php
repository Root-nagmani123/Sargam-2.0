<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fc_registration_master', function (Blueprint $table) {
            if (! Schema::hasColumn('fc_registration_master', 'fc_Prev_comp_doc')) {
                // Certificate of the previously completed Foundation Course, uploaded with a
                // "Previously Completed Foundation Course" exemption application. Stores the
                // path on the public disk, exactly like medical_exemption_doc beside it.
                $table->string('fc_Prev_comp_doc', 255)->nullable()->after('medical_exemption_doc');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fc_registration_master', function (Blueprint $table) {
            if (Schema::hasColumn('fc_registration_master', 'fc_Prev_comp_doc')) {
                $table->dropColumn('fc_Prev_comp_doc');
            }
        });
    }
};
