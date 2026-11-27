<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_deletion_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_credentials_pk')->index()->comment('user_credentials.pk');
            // Snapshot of the account as it was when the request was made.
            $table->string('user_name', 100);
            $table->string('full_name', 200)->nullable();
            $table->string('email', 191);
            $table->string('mobile', 30)->nullable();
            $table->string('reason', 120);
            $table->string('comments', 500)->nullable();
            // pending -> (reviewer) verified -> (admin) accepted; either stage may reject.
            $table->string('status', 20)->default('pending')->index();
            $table->string('review_remarks', 500)->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable()->comment('user_credentials.pk');
            $table->timestamp('reviewed_at')->nullable();
            $table->string('admin_remarks', 500)->nullable();
            $table->unsignedBigInteger('decided_by')->nullable()->comment('user_credentials.pk');
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_deletion_requests');
    }
};
