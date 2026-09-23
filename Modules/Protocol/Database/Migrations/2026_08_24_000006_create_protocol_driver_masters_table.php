<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('protocol_driver_masters', function (Blueprint $table) {
            $table->id();

            $table->string('name', 255);
            $table->string('type', 255);

            $table->date('dob');

            $table->string('driver_no', 255)->unique();
            $table->string('driving_licence_no', 255)->unique();
            $table->date('licence_expiry_date');
            $table->date('hiring_date');

            $table->string('helper_one', 255)->nullable();
            $table->string('helper_two', 255)->nullable();

            $table->text('note_about_driver')->nullable();

            $table->unsignedBigInteger('country_id');
            $table->unsignedBigInteger('state_id');
            $table->unsignedBigInteger('city_id');

            $table->string('postal_code', 20);

            $table->string('email', 150);

            $table->text('address');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('name');
            $table->index('type');
            $table->index('licence_expiry_date');
            $table->index('hiring_date');

            $table->index('country_id');
            $table->index('state_id');
            $table->index('city_id');

            $table->index('postal_code');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('protocol_driver_masters');
    }
};