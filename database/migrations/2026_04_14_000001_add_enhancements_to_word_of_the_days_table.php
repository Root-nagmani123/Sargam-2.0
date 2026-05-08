<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('word_of_the_days', function (Blueprint $table) {
            $table->date('scheduled_date')->nullable()->after('active_inactive');
            $table->softDeletes()->after('updated_at');
            $table->unsignedBigInteger('created_by_pk')->nullable()->after('deleted_at');
            $table->unsignedBigInteger('updated_by_pk')->nullable()->after('created_by_pk');

            $table->index(['active_inactive', 'scheduled_date']);
        });
    }

    public function down(): void
    {
        Schema::table('word_of_the_days', function (Blueprint $table) {
            $table->dropIndex(['active_inactive', 'scheduled_date']);
            $table->dropColumn(['scheduled_date', 'created_by_pk', 'updated_by_pk']);
            $table->dropSoftDeletes();
        });
    }
};
