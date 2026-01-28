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
        Schema::create('security_employee_type', function (Blueprint $table) {
            $table->id('pk');
            $table->string('employee_type_name', 150);
            $table->tinyInteger('active_inactive')->default(1);
            $table->timestamp('created_date')->nullable();
            $table->timestamp('modified_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_employee_type');
    }
};
