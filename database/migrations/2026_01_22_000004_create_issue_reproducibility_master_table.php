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
        Schema::create('issue_reproducibility_master', function (Blueprint $table) {
            $table->id('pk');
            $table->string('reproducibility_name', 100)->nullable(false)->comment('e.g., Always, Sometimes, Rarely');
            $table->text('reproducibility_description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_date')->useCurrent();
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->timestamp('modified_date')->nullable();
            $table->tinyInteger('status')->default(1)->comment('1=Active, 0=Inactive');
            
            // Indexes
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('issue_reproducibility_master');
    }
};
