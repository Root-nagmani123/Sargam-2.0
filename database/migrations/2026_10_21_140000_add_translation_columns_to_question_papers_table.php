<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Each column is guarded so the migration is safe on a database that already has it.
        $has = fn (string $column) => Schema::hasColumn('question_papers', $column);

        Schema::table('question_papers', function (Blueprint $table) use ($has) {
            // Download name of Rajbhasha's translation ("QP-3-025_hindi.docx").
            if (! $has('translated_file_name')) {
                $table->string('translated_file_name')->nullable()->after('translated_file_path');
            }
            // When Rajbhasha sent the translation to the faculty to verify; sent_by is user_credentials.pk.
            if (! $has('sent_for_approval_at')) {
                $table->timestamp('sent_for_approval_at')->nullable()->after('translated_at');
            }
            if (! $has('sent_for_approval_by')) {
                $table->unsignedBigInteger('sent_for_approval_by')->nullable()->after('sent_for_approval_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('question_papers', function (Blueprint $table) {
            $table->dropColumn(['translated_file_name', 'sent_for_approval_at', 'sent_for_approval_by']);
        });
    }
};
