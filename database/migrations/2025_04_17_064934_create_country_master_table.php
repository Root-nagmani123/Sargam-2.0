<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('country_master', function (Blueprint $table) {
            $table->increments('pk');
            $table->string('country_name', 50)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->dateTime('created_date');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('updated_date')->useCurrent()->useCurrentOnUpdate();

            // Agar aapko INDEX ya FOREIGN KEY lagani ho to yahan add kar sakte hain.
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('country_master');
    }
};

