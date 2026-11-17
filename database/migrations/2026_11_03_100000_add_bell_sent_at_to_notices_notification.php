<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * When a notice's bell notifications went out (NoticeBellNotifier).
 *
 * A notice with a future display date is not announced when it is saved; the
 * notices:send-due-bells command announces it on that date, and this column is
 * how it tells a notice still waiting from one already sent.
 *
 * Existing notices are marked as sent, except those whose display date is still
 * ahead: without that, the first run of the command would ring every bell for
 * every live notice ever written.
 */
return new class extends Migration
{
    private const TABLE = 'notices_notification';

    public function up(): void
    {
        if (! Schema::hasTable(self::TABLE) || Schema::hasColumn(self::TABLE, 'bell_sent_at')) {
            return;
        }

        Schema::table(self::TABLE, function (Blueprint $table) {
            $table->dateTime('bell_sent_at')->nullable()->after('active_inactive');
        });

        DB::table(self::TABLE)
            ->where(function ($q) {
                $q->whereNull('display_date')->orWhere('display_date', '<=', now()->toDateString());
            })
            ->update(['bell_sent_at' => now()]);
    }

    public function down(): void
    {
        if (Schema::hasTable(self::TABLE) && Schema::hasColumn(self::TABLE, 'bell_sent_at')) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->dropColumn('bell_sent_at');
            });
        }
    }
};
