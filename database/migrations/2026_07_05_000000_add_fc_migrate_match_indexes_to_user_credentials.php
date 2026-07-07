<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Functional indexes for the FC "Migrate Students" eligibility/migrated grids.
 *
 * The eligibility check matches roster rows against user_credentials on
 * TRIM(CAST(user_name AS CHAR)), TRIM(CAST(mobile_no AS CHAR)) and
 * LOWER(TRIM(email_id)). Those exact expressions are indexed here so the
 * per-identifier EXISTS subqueries in FcMigrateStudentsExportService can seek
 * instead of full-scanning user_credentials as the table grows.
 *
 * Functional/expression indexes require MySQL 8.0.13+. On any other engine
 * (MySQL < 8.0.13, MariaDB, etc.) this migration is a safe no-op — the query
 * still returns correct results, just without the index speed-up. So a prod
 * `php artisan migrate` never fails here regardless of the DB engine.
 */
return new class extends Migration
{
    private array $indexes = [
        'idx_uc_mig_username' => '((TRIM(CAST(user_name AS CHAR))))',
        'idx_uc_mig_mobile'   => '((TRIM(CAST(mobile_no AS CHAR))))',
        'idx_uc_mig_email'    => '((LOWER(TRIM(email_id))))',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('user_credentials')) {
            return;
        }

        if (! $this->supportsFunctionalIndexes()) {
            Log::warning('Skipping FC migrate functional indexes: DB engine does not support them (needs MySQL 8.0.13+).');

            return;
        }

        foreach ($this->indexes as $name => $expr) {
            if ($this->indexExists($name)) {
                continue;
            }
            // ALGORITHM=INPLACE, LOCK=NONE keeps user_credentials fully readable and
            // writable while the index builds (online DDL) — safe on a live prod table.
            try {
                DB::statement("ALTER TABLE user_credentials ADD INDEX {$name} {$expr}, ALGORITHM=INPLACE, LOCK=NONE");
            } catch (\Throwable $e) {
                // Fall back to a plain online add if this server rejects the explicit clause.
                DB::statement("ALTER TABLE user_credentials ADD INDEX {$name} {$expr}");
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('user_credentials')) {
            return;
        }

        foreach (array_keys($this->indexes) as $name) {
            if ($this->indexExists($name)) {
                DB::statement("ALTER TABLE user_credentials DROP INDEX {$name}");
            }
        }
    }

    private function indexExists(string $name): bool
    {
        return ! empty(DB::select(
            'SHOW INDEX FROM user_credentials WHERE Key_name = ?',
            [$name]
        ));
    }

    /**
     * Functional indexes need MySQL (not MariaDB) at version 8.0.13 or newer.
     */
    private function supportsFunctionalIndexes(): bool
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return false;
        }

        $version = (string) DB::selectOne('SELECT VERSION() AS v')->v;

        // MariaDB reports e.g. "10.5.x-MariaDB" and does not support this syntax.
        if (stripos($version, 'mariadb') !== false) {
            return false;
        }

        return version_compare($version, '8.0.13', '>=');
    }
};
