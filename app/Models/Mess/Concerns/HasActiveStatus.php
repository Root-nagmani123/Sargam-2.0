<?php

namespace App\Models\Mess\Concerns;

/**
 * Shared active/inactive status scope + display accessors for Mess master-data models.
 * Consuming model must define STATUS_ACTIVE (and typically STATUS_INACTIVE) constants.
 */
trait HasActiveStatus
{
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status ?: self::STATUS_ACTIVE;
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return $this->status === self::STATUS_ACTIVE ? 'success' : 'danger';
    }
}
