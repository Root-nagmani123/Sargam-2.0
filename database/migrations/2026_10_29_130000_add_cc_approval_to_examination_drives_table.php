<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Course Coordinator (CC) approval of an examination drive.
 *
 * A mapped faculty saving their Map Faculty setup sends the drive to the CC of
 * its course (cc_status = pending). The CC approves with the proposed exam
 * dates (Exam Drive Date Confirmation), which also become the drive's
 * start_date/end_date, or rejects with a remark.
 */
return new class extends Migration
{
    private const TABLE = 'examination_drives';

    private const COLUMNS = ['cc_status', 'proposed_start_date', 'proposed_end_date', 'cc_remarks', 'cc_action_by', 'cc_action_at'];

    public function up(): void
    {
        // Each column is guarded so the migration is safe on a shared/developer
        // database that already has some of them (partial run, manual change).
        $has = fn (string $column) => Schema::hasColumn(self::TABLE, $column);

        Schema::table(self::TABLE, function (Blueprint $table) use ($has) {
            if (! $has('cc_status')) {
                // null = not sent yet, pending, approved, rejected
                $table->string('cc_status', 20)->nullable()->after('status')->index();
            }
            if (! $has('proposed_start_date')) {
                $table->date('proposed_start_date')->nullable()->after('end_date');
            }
            if (! $has('proposed_end_date')) {
                $table->date('proposed_end_date')->nullable()->after('proposed_start_date');
            }
            if (! $has('cc_remarks')) {
                $table->string('cc_remarks', 500)->nullable()->after('proposed_end_date');
            }
            if (! $has('cc_action_by')) {
                // user_credentials.user_id of the CC who approved / rejected
                $table->unsignedBigInteger('cc_action_by')->nullable()->after('cc_remarks');
            }
            if (! $has('cc_action_at')) {
                $table->timestamp('cc_action_at')->nullable()->after('cc_action_by');
            }
        });
    }

    public function down(): void
    {
        $existing = array_values(array_filter(self::COLUMNS, fn ($column) => Schema::hasColumn(self::TABLE, $column)));
        if ($existing) {
            Schema::table(self::TABLE, function (Blueprint $table) use ($existing) {
                if (in_array('cc_status', $existing, true)) {
                    $table->dropIndex(['cc_status']);
                }
                $table->dropColumn($existing);
            });
        }
    }
};
