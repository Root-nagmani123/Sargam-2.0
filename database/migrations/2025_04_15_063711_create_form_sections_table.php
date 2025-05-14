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
        Schema::create('form_sections', function (Blueprint $table) {
            $table->bigIncrements('id'); // Primary key, AUTO_INCREMENT
            $table->unsignedBigInteger('formid')->nullable();
            $table->string('section_title', 255)->nullable()->collation('utf8mb4_0900_ai_ci');
            $table->bigInteger('sort_order')->default(0)->nullable();
            
            // Optional: if you want created_at and updated_at timestamps
            // $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('form_sections');
    }
};
