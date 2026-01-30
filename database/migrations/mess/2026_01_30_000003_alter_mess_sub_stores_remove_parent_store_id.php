<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $table = 'mess_sub_stores';
        if (!Schema::hasColumn($table, 'parent_store_id')) {
            return;
        }
        Schema::table($table, function (Blueprint $t) {
            $t->dropForeign(['parent_store_id']);
        });
        Schema::table($table, function (Blueprint $t) {
            $t->dropColumn('parent_store_id');
        });
    }

    public function down(): void
    {
        Schema::table('mess_sub_stores', function (Blueprint $t) {
            $t->unsignedBigInteger('parent_store_id')->nullable()->after('id');
        });
        Schema::table('mess_sub_stores', function (Blueprint $t) {
            $t->foreign('parent_store_id')->references('id')->on('mess_stores')->onDelete('cascade');
        });
    }
};
