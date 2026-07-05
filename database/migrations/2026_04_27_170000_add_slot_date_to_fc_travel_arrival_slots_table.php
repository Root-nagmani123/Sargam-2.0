<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fc_travel_arrival_slots', function (Blueprint $table) {
            if (! Schema::hasColumn('fc_travel_arrival_slots', 'slot_date')) {
                $table->date('slot_date')->nullable()->after('slot_label')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('fc_travel_arrival_slots', function (Blueprint $table) {
            if (Schema::hasColumn('fc_travel_arrival_slots', 'slot_date')) {
                $table->dropIndex(['slot_date']);
                $table->dropColumn('slot_date');
            }
        });
    }
};

