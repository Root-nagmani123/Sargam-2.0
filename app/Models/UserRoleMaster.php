<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserRoleMaster extends Model
{
    protected $table = 'user_role_master';
    protected $primaryKey = 'pk';
    public $timestamps = false;

    protected $fillable = [
        'user_role_name',
        'user_role_display_name',
        'active_inactive',
    ];

    public static function getUserRoleList()
    {
        return self::select('pk', 'user_role_display_name')->get()->pluck('user_role_display_name', 'pk');
    }
}
