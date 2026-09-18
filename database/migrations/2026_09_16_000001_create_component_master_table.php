<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('component_master')) {
            return;
        }

        Schema::create('component_master', function (Blueprint $table) {
            $table->bigIncrements('pk');
            $table->string('component_name', 100);
            $table->tinyInteger('active_inactive')->default(1);
            $table->timestamp('created_date')->useCurrent();
            $table->timestamp('modified_date')->useCurrent()->useCurrentOnUpdate();

            $table->unique('component_name');
            // Listing page filters/sorts by status.
            $table->index('active_inactive');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('component_master');
    }
};
