<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * COE → Room Master: laptop and scriber seats of a room. The DBA's DDL, as given:
 *
 *     alter table building_floor_room_mapping
 *     add column laptop_count int default null after capacity,
 *     add column scriber_count int default null after laptop_count;
 *
 * Idempotent: staging already has both columns, so a column already there is left as it is.
 */
return new class extends Migration
{
    private const TABLE = 'building_floor_room_mapping';

    public function up(): void
    {
        Schema::table(self::TABLE, function (Blueprint $table) {
            if (! Schema::hasColumn(self::TABLE, 'laptop_count')) {
                $table->integer('laptop_count')->nullable()->default(null)->after('capacity');
            }
            if (! Schema::hasColumn(self::TABLE, 'scriber_count')) {
                $table->integer('scriber_count')->nullable()->default(null)->after('laptop_count');
            }
        });
    }

    public function down(): void
    {
        Schema::table(self::TABLE, function (Blueprint $table) {
            foreach (['scriber_count', 'laptop_count'] as $column) {
                if (Schema::hasColumn(self::TABLE, $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
