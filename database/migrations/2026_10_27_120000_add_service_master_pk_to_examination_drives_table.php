<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Create Examination Drive gained a Service field (next to Course), picked from
 * the existing service_master table. Nullable so drives created before this
 * migration stay valid; the controller requires it on create/update.
 */
return new class extends Migration
{
    private const TABLE = 'examination_drives';

    private const FK = 'examination_drives_service_master_pk_foreign';

    public function up(): void
    {
        // Guarded: shared/developer databases may already have the column.
        if (! Schema::hasColumn(self::TABLE, 'service_master_pk')) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->unsignedBigInteger('service_master_pk')->nullable()->after('course_master_pk');
            });
        }

        if (! $this->foreignKeyExists()) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->foreign('service_master_pk')->references('pk')->on('service_master')->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        if ($this->foreignKeyExists()) {
            Schema::table(self::TABLE, fn (Blueprint $table) => $table->dropForeign(self::FK));
        }

        if (Schema::hasColumn(self::TABLE, 'service_master_pk')) {
            Schema::table(self::TABLE, fn (Blueprint $table) => $table->dropColumn('service_master_pk'));
        }
    }

    private function foreignKeyExists(): bool
    {
        return DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', self::TABLE)
            ->where('CONSTRAINT_NAME', self::FK)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();
    }
};
