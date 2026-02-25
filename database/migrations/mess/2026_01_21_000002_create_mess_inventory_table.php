<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('mess_inventories', function (Blueprint $table) {
            $table->id();
            $table->string('item_name');
            $table->string('category');
            $table->integer('quantity');
            $table->string('unit');
            $table->date('expiry_date')->nullable();
            $table->timestamps();
        });
    }
    public function down() {
        Schema::dropIfExists('mess_inventories');
    }
};
