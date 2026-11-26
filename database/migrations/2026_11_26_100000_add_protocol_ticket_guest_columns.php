<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('protocol_ticket_journeys')
            && ! Schema::hasColumn('protocol_ticket_journeys', 'meal_type')) {
            Schema::table('protocol_ticket_journeys', function (Blueprint $table) {
                $table->string('meal_type', 155)->nullable()->after('book_if_waiting');
            });
        }

        if (Schema::hasTable('protocol_request_guests')
            && ! Schema::hasColumn('protocol_request_guests', 'salutation')) {
            Schema::table('protocol_request_guests', function (Blueprint $table) {
                $table->string('salutation', 20)->nullable()->after('batch_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('protocol_ticket_journeys')
            && Schema::hasColumn('protocol_ticket_journeys', 'meal_type')) {
            Schema::table('protocol_ticket_journeys', function (Blueprint $table) {
                $table->dropColumn('meal_type');
            });
        }

        if (Schema::hasTable('protocol_request_guests')
            && Schema::hasColumn('protocol_request_guests', 'salutation')) {
            Schema::table('protocol_request_guests', function (Blueprint $table) {
                $table->dropColumn('salutation');
            });
        }
    }
};
