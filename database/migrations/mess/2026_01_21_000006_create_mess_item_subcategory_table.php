<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('mess_item_subcategories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->foreign('category_id')->references('id')->on('mess_item_categories')->onDelete('cascade');
        });
    }
    public function down() {
        Schema::dropIfExists('mess_item_subcategories');
    }
};
