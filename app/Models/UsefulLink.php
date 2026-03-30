<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsefulLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'label',
        'url',
        'file_path',
        'target_blank',
        'position',
        'active_inactive',
    ];

    protected $casts = [
        'target_blank' => 'boolean',
        'active_inactive' => 'boolean',
        'position' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('active_inactive', 1);
    }
}

