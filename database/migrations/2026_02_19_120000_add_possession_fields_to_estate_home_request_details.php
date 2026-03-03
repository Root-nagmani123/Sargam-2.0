<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estate_home_request_details', function (Blueprint $table) {
            if (! Schema::hasColumn('estate_home_request_details', 'pos_from')) {
                $table->date('pos_from')->nullable()->after('testing');
            }
            if (! Schema::hasColumn('estate_home_request_details', 'pos_to')) {
                $table->date('pos_to')->nullable()->after('pos_from');
            }
            if (! Schema::hasColumn('estate_home_request_details', 'extension')) {
                $table->string('extension', 255)->nullable()->after('pos_to');
            }
        });
    }

    public function down(): void
    {
        Schema::table('estate_home_request_details', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('estate_home_request_details', 'pos_from')) {
                $cols[] = 'pos_from';
            }
            if (Schema::hasColumn('estate_home_request_details', 'pos_to')) {
                $cols[] = 'pos_to';
            }
            if (Schema::hasColumn('estate_home_request_details', 'extension')) {
                $cols[] = 'extension';
            }
            if (! empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
