<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Converts original user_login table to Laravel users table.
 *
 * Original: username (PK), name, department, password (plain text — SECURITY RISK)
 * Laravel:  id (PK), username (unique), name, department, password (bcrypt hashed)
 *
 * Original departments: Medical, Administration, Security, IT, Trg, Mess
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username', 50)->unique();
            $table->string('name', 150);
            $table->string('department', 30)->nullable(); // Medical | Administration | Security | IT | Trg | Mess
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
