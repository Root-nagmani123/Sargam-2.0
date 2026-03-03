<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('mess_material_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number')->unique();
            $table->date('request_date');
            $table->unsignedBigInteger('store_id')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'completed'])->default('pending');
            $table->text('purpose')->nullable();
            $table->unsignedInteger('requested_by');
            $table->unsignedInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            
            $table->foreign('requested_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('mess_material_requests');
    }
};
