<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * COE Examination - the audit trail for question papers.
 *
 * Records every action taken on a paper, including the ones that leave no other
 * trace: who downloaded a confidential file, and when. The requirement is that
 * this is not editable by ordinary users, so nothing in the application updates
 * or deletes a row here - it is append-only by design.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('question_paper_audit_logs')) {
            return;
        }

        Schema::create('question_paper_audit_logs', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('question_paper_id')->nullable();

            // App\Services\COE\QuestionPaperAuditService action constants.
            $table->string('action', 50);

            $table->unsignedBigInteger('user_id')->nullable();

            // Copied rather than joined: the log has to keep reading correctly
            // even if the user record is later renamed or removed.
            $table->string('user_name', 150)->nullable();
            $table->string('user_role', 100)->nullable();

            // Free-form detail of what happened - the file touched, the status
            // moved from and to, the reason given.
            $table->json('details')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();

            $table->timestamp('created_date')->useCurrent();

            // No foreign key: a paper that is ever deleted must not take its
            // audit trail with it.
            $table->index('question_paper_id');
            $table->index('action');
            $table->index('user_id');
            $table->index('created_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_paper_audit_logs');
    }
};
