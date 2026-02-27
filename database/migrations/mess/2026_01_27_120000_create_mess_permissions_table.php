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
        Schema::create('mess_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('role_id')->comment('References user_role_master.pk');
            $table->string('action_name', 100)->comment('Permission action like purchase_order.approve');
            $table->string('display_name', 255)->comment('Human readable permission name');
            $table->string('module', 50)->default('mess')->comment('Module name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index('role_id');
            $table->index('action_name');
            $table->index('is_active');
            $table->unique(['role_id', 'action_name'], 'unique_role_action');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mess_permissions');
    }
};
