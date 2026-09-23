<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('protocol_guest_houses', function (Blueprint $table) {
            $table->id();
            $table->string('guest_name'); // "Self" or the visiting guest's name
            $table->string('guest_house_name');
            $table->unsignedInteger('guest_house_master_id')->nullable(); // FK to a masters table if you maintain one
            $table->date('check_in_date');
            $table->date('check_out_date');
            $table->unsignedInteger('no_of_guests')->default(1);
            $table->string('contact_number')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('protocol_guest_houses');
    }
};
