<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('word_of_the_days', function (Blueprint $table) {
            $table->id();
            $table->string('hindi_text', 255);
            $table->string('english_text', 255);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('active_inactive')->default(true);
            $table->timestamps();

            $table->index(['active_inactive', 'sort_order', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('word_of_the_days');
    }
};
