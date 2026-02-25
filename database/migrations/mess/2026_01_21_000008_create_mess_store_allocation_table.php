<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('mess_store_allocations', function (Blueprint $table) {
            $table->id();
            $table->string('store_name');
            $table->string('allocated_to');
            $table->integer('quantity');
            $table->date('allocation_date');
            $table->timestamps();
        });
    }
    public function down() {
        Schema::dropIfExists('mess_store_allocations');
    }
};
