<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class LoginCarouselImage extends Model
{
    protected $fillable = [
        'image_path',
        'sort_order',
        'active_inactive',
        'created_by_pk',
        'updated_by_pk',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'active_inactive' => 'boolean',
        'created_by_pk' => 'integer',
        'updated_by_pk' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('active_inactive', true);
    }

    public static function activeForLogin()
    {
        if (! Schema::hasTable((new static)->getTable())) {
            return collect();
        }

        return static::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }
}
