<?php

use App\Support\MigrationSchema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        MigrationSchema::createIfMissing('jbp_users', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100)->unique();
            $table->string('password', 255);
            $table->string('email', 150)->nullable();
            $table->string('role', 50)->default('FC');         // FC, ADMIN, REPORT
            $table->tinyInteger('enabled')->default(1);
            $table->integer('login_attempts')->default(0);
            $table->timestamp('last_login')->nullable();
            $table->string('session_id', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jbp_users');
    }
};
