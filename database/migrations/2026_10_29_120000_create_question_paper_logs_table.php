<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * COE › Question Paper Management Logs: who did what to which question paper,
 * when and from where (App\Services\COE\QuestionPaperLogger). Append-only.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('question_paper_logs')) {
            return;
        }

        Schema::create('question_paper_logs', function (Blueprint $table) {
            $table->bigIncrements('id');

            // question_papers.id; no foreign key: the log outlives a deleted paper.
            $table->unsignedBigInteger('question_paper_id')->nullable()->index();

            // user_credentials.pk, with the name and screen role as they were then.
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('user_name', 150);
            $table->string('role', 30);       // Faculty / Rajbhasha / COE
            $table->string('module', 30);     // Exam Paper / Translation
            $table->string('action', 40);     // Viewed, Created, Updated, Frozen, ...
            $table->string('detail', 100)->nullable();   // e.g. "V2"

            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();

            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_paper_logs');
    }
};
