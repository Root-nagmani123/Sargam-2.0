<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A login's own arrangement of one module's dashboard: the card order it chose and the cards
 * or panels it hid. Only reorders / hides what the login's roles were assigned; never grants.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_dashboard_layouts')) {
            return;
        }
        Schema::create('user_dashboard_layouts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('sidebar_category_id');
            $table->json('card_order')->nullable();
            $table->json('hidden_cards')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'sidebar_category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_dashboard_layouts');
    }
};
