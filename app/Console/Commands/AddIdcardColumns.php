<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddIdcardColumns extends Command
{
    protected $signature = 'idcard:add-columns';
    protected $description = 'Add joining_letter and duplication_reason columns to employee_idcard_requests if missing';

    public function handle()
    {
        $tables = ['employee_i_d_card_requests', 'employee_idcard_requests'];

        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            $added = 0;
            if (!Schema::hasColumn($table, 'joining_letter')) {
                DB::statement("ALTER TABLE `{$table}` ADD COLUMN joining_letter VARCHAR(255) NULL AFTER photo");
                $this->info("Added joining_letter to {$table}");
                $added++;
            }
            if (!Schema::hasColumn($table, 'duplication_reason')) {
                DB::statement("ALTER TABLE `{$table}` ADD COLUMN duplication_reason VARCHAR(255) NULL AFTER request_for");
                $this->info("Added duplication_reason to {$table}");
                $added++;
            }

            if ($added > 0) {
                $this->info("Done. Added {$added} column(s) to {$table}");
                return 0;
            }
        }

        $this->info('All columns already exist in both tables.');
        return 0;
    }
}
