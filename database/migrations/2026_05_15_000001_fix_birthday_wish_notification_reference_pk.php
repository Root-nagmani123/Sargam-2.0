<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Old birthday wishes used the receiver's pk as reference_pk for every sender,
     * which could collapse multiple wishes into one row. Point reference_pk at the sender instead.
     */
    public function up(): void
    {
        DB::statement("
            UPDATE notifications
            SET reference_pk = sender_user_id
            WHERE type = 'birthday'
              AND (module_name = 'BirthdayWish' OR module_name IS NULL)
              AND sender_user_id IS NOT NULL
              AND reference_pk = receiver_user_id
        ");

        DB::table('notifications')
            ->where('type', 'birthday')
            ->where(function ($query) {
                $query->where('module_name', 'BirthdayWish')
                    ->orWhereNull('module_name');
            })
            ->whereNull('module_name')
            ->update(['module_name' => 'BirthdayWish']);
    }

    public function down(): void
    {
        // Cannot safely restore previous reference_pk values.
    }
};
