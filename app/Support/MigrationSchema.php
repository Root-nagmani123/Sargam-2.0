<?php

namespace App\Support;

use Closure;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Idempotent migration helpers: create tables only when missing so
 * `php artisan migrate` can run safely alongside pre-existing legacy tables.
 */
final class MigrationSchema
{
    /**
     * @param  Closure(Blueprint):void  $callback
     */
    public static function createIfMissing(string $table, Closure $callback): void
    {
        if (Schema::hasTable($table)) {
            return;
        }

        Schema::create($table, $callback);
    }
}
