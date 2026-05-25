<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_carousel_images', function (Blueprint $table) {
            $table->id();
            $table->string('image_path');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('active_inactive')->default(true);
            $table->unsignedBigInteger('created_by_pk')->nullable();
            $table->unsignedBigInteger('updated_by_pk')->nullable();
            $table->timestamps();

            $table->index(['active_inactive', 'sort_order', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_carousel_images');
    }
};
