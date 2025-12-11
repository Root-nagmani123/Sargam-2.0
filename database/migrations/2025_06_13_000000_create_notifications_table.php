<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->bigIncrements('pk');
            $table->bigInteger('sender_user_id')->unsigned()->nullable()->comment('Admin who triggered notification');
            $table->bigInteger('receiver_user_id')->unsigned()->comment('Faculty / Student user_id');
            $table->string('type', 50)->comment('mdo / memo / course / notice etc.');
            $table->string('module_name', 100)->nullable()->comment('Duty / Memo / Course / Notice etc.');
            $table->bigInteger('reference_pk')->unsigned()->nullable()->comment('duty_pk / memo_pk / course_pk etc.');
            $table->string('title', 255)->nullable();
            $table->text('message')->nullable();
            $table->tinyInteger('is_read')->nullable()->default(0)->comment('0 = unread, 1 = read');
            $table->dateTime('created_at')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
            
            // Indexes for better query performance
            $table->index('receiver_user_id');
            $table->index('is_read');
            $table->index('created_at');
            $table->index(['receiver_user_id', 'is_read']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};

