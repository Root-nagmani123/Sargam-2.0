<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStateMasterTable extends Migration
{
    public function up(): void
    {
        Schema::create('state_master', function (Blueprint $table) {
            $table->bigIncrements('Pk');
            $table->string('state_name', 50)->nullable();
            $table->unsignedInteger('country_master_pk');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->dateTime('created_date');
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->timestamp('modified_date')->useCurrent()->useCurrentOnUpdate();

            $table->index('country_master_pk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('state_master');
    }
}
