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
        Schema::create('issue_priority_master', function (Blueprint $table) {
            $table->id('pk');
            $table->string('priority', 100)->nullable(false)->comment('e.g., High, Medium, Low, Critical');
            $table->text('description')->nullable();
            $table->integer('priority_order')->default(0)->comment('Display order, lower = higher priority');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_date')->useCurrent();
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->timestamp('modified_date')->nullable();
            $table->tinyInteger('status')->default(1)->comment('1=Active, 0=Inactive');
            
            // Indexes
            $table->index('created_by');
            $table->index('priority_order');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('issue_priority_master');
    }
};
