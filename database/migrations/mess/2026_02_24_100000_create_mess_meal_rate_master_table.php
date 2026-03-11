<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mess_meal_rate_master', function (Blueprint $table) {
            $table->id();
            $table->string('meal_type', 20); // breakfast, lunch, dinner
            $table->string('category_type', 30); // govrt, ot, faculty, alumni
            $table->decimal('rate', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['meal_type', 'category_type']);
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mess_meal_rate_master');
    }
};
