<?php

namespace App\Http\Controllers\Mess\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/**
 * The `?status=` query the Mess status pills push into the URL.
 *
 * Every mess master stores `status` as the literal strings 'active' / 'inactive'
 * (see the STATUS_* constants on Store, SubStore, ClientType, ItemCategory and
 * ItemSubcategory), so one implementation serves all of them.
 *
 * @see resources/views/mess/partials/status-pills.blade.php
 */
trait FiltersByStatus
{
    /**
     * Status values the pill row may ask for; anything else means "all".
     */
    protected function resolveStatusFilter(Request $request): ?string
    {
        $status = strtolower(trim((string) $request->query('status', '')));

        return in_array($status, ['active', 'inactive'], true) ? $status : null;
    }

    /**
     * Narrow a query to the pill's status, when one is set and the table has the
     * column. Tables that grew a `status` column late are guarded — the mess
     * schema is not uniform (see ItemCategoryController's Schema::hasColumn use).
     */
    protected function applyStatusFilter(Builder $query, Request $request, string $table): Builder
    {
        $status = $this->resolveStatusFilter($request);

        if ($status !== null && Schema::hasColumn($table, 'status')) {
            $query->where('status', $status);
        }

        return $query;
    }
}
