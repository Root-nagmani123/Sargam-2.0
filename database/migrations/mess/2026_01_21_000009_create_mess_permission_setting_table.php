<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('mess_permission_settings', function (Blueprint $table) {
            $table->id();
            $table->string('role');
            $table->string('permission');
            $table->timestamps();
        });
    }
    public function down() {
        Schema::dropIfExists('mess_permission_settings');
    }
};
