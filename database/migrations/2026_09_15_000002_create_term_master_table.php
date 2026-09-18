<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('term_master')) {
            return;
        }

        Schema::create('term_master', function (Blueprint $table) {
            $table->bigIncrements('pk');
            $table->string('term_name', 100);
            $table->tinyInteger('active_inactive')->default(1);
            $table->timestamp('created_date')->useCurrent();
            $table->timestamp('modified_date')->useCurrent()->useCurrentOnUpdate();

            $table->unique('term_name');
            // Listing page filters/sorts by status.
            $table->index('active_inactive');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('term_master');
    }
};
