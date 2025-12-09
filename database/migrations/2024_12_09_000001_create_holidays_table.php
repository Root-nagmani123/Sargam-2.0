<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->string('holiday_name');
            $table->date('holiday_date');
            $table->enum('holiday_type', ['gazetted', 'restricted', 'optional'])->default('gazetted');
            $table->text('description')->nullable();
            $table->year('year');
            $table->boolean('active_inactive')->default(1);
            $table->timestamps();
            
            $table->index('holiday_date');
            $table->index(['year', 'holiday_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
