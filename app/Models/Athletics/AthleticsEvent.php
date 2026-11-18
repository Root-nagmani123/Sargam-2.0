<?php

namespace App\Models\Athletics;

use Illuminate\Database\Eloquent\Model;

class AthleticsEvent extends Model
{
    protected $table = 'athletics_events';

    protected $fillable = ['name', 'category', 'gender', 'participation', 'status', 'created_by'];

    public const CATEGORIES = ['track' => 'Track Events', 'field' => 'Field Events', 'group' => 'Group Events'];

    public const GENDERS = ['male' => 'Male', 'female' => 'Female'];

    public const PARTICIPATION = ['individual' => 'Individual', 'team' => 'Team (house-wise)'];

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
