<?php

namespace Modules\GuestTaClaim\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\GuestTaClaim\Enums\Permission;
use Modules\GuestTaClaim\Services\PermissionService;

/**
 * php artisan module:seed GuestTaClaim
 * Grants every Guest TA Claim permission to the users in GTC_SEED_USER_IDS (default: 1).
 */
class GuestTaClaimDatabaseSeeder extends Seeder
{
    public function run(PermissionService $permissions): void
    {
        $ids = array_filter(array_map('intval', explode(',', (string) env('GTC_SEED_USER_IDS', '1'))));
        foreach ($ids as $id) {
            $permissions->sync($id, Permission::values(), $id);
        }
    }
}
