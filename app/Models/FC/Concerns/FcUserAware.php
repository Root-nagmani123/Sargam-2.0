<?php

namespace App\Models\FC\Concerns;

/**
 * Provides a migration-safe way to resolve the user-identifier column name
 * for FC Eloquent models.  Before the username→user_id migration the column
 * may be `username` or `userid`; after it is always `user_id`.
 *
 * Usage:
 *   StudentMaster::where(StudentMaster::userIdCol(), $userId)->...
 */
trait FcUserAware
{
    /**
     * Return the user-identifier column name for this model's table.
     */
    public static function userIdCol(): string
    {
        return fc_user_col((new static())->getTable());
    }

    /**
     * Scope a query to a specific user, resolved via the migration-safe column
     * and the correct value type (integer post-migration, username string pre-migration).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    public function scopeForUser($query, int $userId)
    {
        $table = $this->getTable();
        return $query->where(fc_user_col($table), fc_user_val($table, $userId));
    }
}
