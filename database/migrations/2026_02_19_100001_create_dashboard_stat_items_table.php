<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_stat_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dashboard_stat_snapshot_id')->constrained('dashboard_stat_snapshots')->cascadeOnDelete();
            $table->string('chart_type', 50); // social_groups, gender, age, stream, cadre, domicile
            $table->string('label', 255);
            $table->unsignedInteger('female_count')->nullable();
            $table->unsignedInteger('male_count')->nullable();
            $table->decimal('value', 10, 2)->nullable(); // for single-value charts (gender %, stream count, domicile count)
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_stat_items');
    }
};
