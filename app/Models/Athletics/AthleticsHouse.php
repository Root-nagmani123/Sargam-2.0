<?php

namespace App\Models\Athletics;

use Illuminate\Database\Eloquent\Model;

class AthleticsHouse extends Model
{
    protected $table = 'athletics_houses';

    protected $fillable = ['name', 'status', 'created_by'];

    public function members()
    {
        return $this->hasMany(AthleticsHouseMember::class, 'house_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
