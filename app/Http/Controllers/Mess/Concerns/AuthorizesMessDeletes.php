<?php

namespace App\Http\Controllers\Mess\Concerns;

/**
 * Shared server-side guard for Mess master-data/document destroy() actions.
 * The delete button in each datatable is already hidden via the same hasRole()
 * check for non-admins; this trait makes that check authoritative server-side too,
 * since routes only carry 'auth' middleware (no role/permission gate).
 */
trait AuthorizesMessDeletes
{
    /**
     * @param  string[]  $roles  Role names, any one of which grants delete access.
     */
    protected function abortUnlessCanDeleteMessRecord(array $roles, string $message): void
    {
        $allowed = function_exists('hasRole') && collect($roles)->contains(fn ($role) => hasRole($role));

        abort_if(! $allowed, 403, $message);
    }
}
