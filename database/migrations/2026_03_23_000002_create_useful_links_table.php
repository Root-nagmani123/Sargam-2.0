<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('useful_links', function (Blueprint $table) {
            $table->id();
            $table->string('label', 255);
            $table->string('url', 2048)->nullable();
            $table->string('file_path', 1024)->nullable();
            $table->boolean('target_blank')->default(true);
            $table->integer('position')->default(0);
            $table->boolean('active_inactive')->default(1);
            $table->timestamps();

            $table->index(['active_inactive', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('useful_links');
    }
};

