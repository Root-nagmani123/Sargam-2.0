<?php

namespace Tests\Feature\Concerns;

/**
 * The dashboard_cards rows PR #356's cards need, and their role/tab assignments, created by
 * running the migration that ships them. The shared dev databases differ on whether it ran, so a
 * test that reads the rows runs its up() first. It is insert-only; call it inside a rolled-back test.
 */
trait SeedsModuleDashboardCards
{
    protected function seedModuleDashboardCards(): void
    {
        self::moduleDashboardCardsMigration()->up();
    }

    protected static function moduleDashboardCardsMigration(): object
    {
        return require database_path('migrations/2026_11_25_000000_add_module_dashboard_cards.php');
    }
}
