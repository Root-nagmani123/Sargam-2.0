<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $table = 'mess_item_subcategories';

        if (!Schema::hasColumn($table, 'item_code')) {
            Schema::table($table, function (Blueprint $t) {
                $t->string('item_code', 50)->nullable()->after('category_id');
            });
        }

        // Backfill item_code for existing rows that have null
        $rows = DB::table($table)->orderBy('id')->get();
        $used = DB::table($table)->whereNotNull('item_code')->pluck('item_code')->flip()->all();
        $next = 1;
        foreach ($rows as $row) {
            $current = $row->item_code ?? null;
            if ($current === null || $current === '') {
                do {
                    $code = 'ITM' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
                    $next++;
                } while (isset($used[$code]));
                $used[$code] = true;
                DB::table($table)->where('id', $row->id)->update(['item_code' => $code]);
            }
        }

        // Make item_code not null
        DB::statement('ALTER TABLE mess_item_subcategories MODIFY item_code VARCHAR(50) NOT NULL');
        // Add unique index only if not already present
        $indexes = collect(DB::select("SHOW INDEX FROM {$table} WHERE Column_name = 'item_code'"))->pluck('Key_name');
        if ($indexes->isEmpty() || !$indexes->contains(fn ($n) => str_contains($n, 'unique'))) {
            try {
                DB::statement('ALTER TABLE mess_item_subcategories ADD UNIQUE KEY mess_item_subcategories_item_code_unique (item_code)');
            } catch (\Throwable $e) {
                // unique may already exist with different name
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('mess_item_subcategories', 'item_code')) {
            return;
        }
        try {
            DB::statement('ALTER TABLE mess_item_subcategories DROP INDEX mess_item_subcategories_item_code_unique');
        } catch (\Throwable $e) {
            // ignore
        }
        DB::statement('ALTER TABLE mess_item_subcategories MODIFY item_code VARCHAR(50) NULL');
    }
};
