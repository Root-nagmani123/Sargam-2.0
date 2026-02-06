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
        Schema::create('mess_permission_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('mess_permission_id');
            $table->unsignedBigInteger('user_id')->comment('References user_credentials.pk');
            $table->timestamps();

            // Indexes
            $table->index('mess_permission_id');
            $table->index('user_id');
            $table->unique(['mess_permission_id', 'user_id'], 'unique_permission_user');

            // Foreign key
            $table->foreign('mess_permission_id')
                  ->references('id')
                  ->on('mess_permissions')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mess_permission_users');
    }
};
