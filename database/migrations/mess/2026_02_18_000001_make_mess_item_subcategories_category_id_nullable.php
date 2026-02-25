<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('mess_item_subcategories', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
        });
        
        DB::statement('ALTER TABLE mess_item_subcategories MODIFY category_id BIGINT UNSIGNED NULL');
        Schema::table('mess_item_subcategories', function (Blueprint $table) {
            $table->foreign('category_id')->references('id')->on('mess_item_categories')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('mess_item_subcategories', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
        });
        DB::statement('ALTER TABLE mess_item_subcategories MODIFY category_id BIGINT UNSIGNED NOT NULL');
        Schema::table('mess_item_subcategories', function (Blueprint $table) {
            $table->foreign('category_id')->references('id')->on('mess_item_categories')->onDelete('cascade');
        });
    }
};
