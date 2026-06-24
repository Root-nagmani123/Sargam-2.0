<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

class DashboardCard extends Model
{
    protected $fillable = ['key', 'label', 'icon', 'color_class', 'sort_order'];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_dashboard_cards', 'dashboard_card_id', 'role_id');
    }
}
