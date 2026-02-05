<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

$table = 'employee_idcard_requests';

if (!Schema::hasColumn($table, 'joining_letter')) {
    DB::statement("ALTER TABLE {$table} ADD COLUMN joining_letter VARCHAR(255) NULL AFTER photo");
    echo "Added joining_letter\n";
} else {
    echo "joining_letter already exists\n";
}

if (!Schema::hasColumn($table, 'duplication_reason')) {
    DB::statement("ALTER TABLE {$table} ADD COLUMN duplication_reason VARCHAR(255) NULL AFTER request_for");
    echo "Added duplication_reason\n";
} else {
    echo "duplication_reason already exists\n";
}

echo "Done.\n";
