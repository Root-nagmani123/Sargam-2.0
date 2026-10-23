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
            // When the faculty froze the paper with the OTP (Draft -> Pending at Rajbhasha);
            // frozen_by is user_credentials.pk.
            if (! $has('frozen_at')) {
                $table->timestamp('frozen_at')->nullable()->after('comments');
            }
            if (! $has('frozen_by')) {
                $table->unsignedBigInteger('frozen_by')->nullable()->after('frozen_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('question_papers', function (Blueprint $table) {
            $table->dropColumn(['frozen_at', 'frozen_by']);
        });
    }
};
