<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('mess_meal_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('meal_name');
            $table->string('day_of_week');
            $table->string('items'); // Comma separated or use a relation in future
            $table->timestamps();
        });
    }
    public function down() {
        Schema::dropIfExists('mess_meal_mappings');
    }
};
