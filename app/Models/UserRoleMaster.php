<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserRoleMaster extends Model
{
    protected $table = 'user_role_master';
    protected $primaryKey = 'PK';
    public $timestamps = false;

    public static function getUserRoleList()
    {
        return self::select('PK', 'USER_ROLE_DISPLAY_NAME')->get()->pluck('USER_ROLE_DISPLAY_NAME', 'PK');
    }
}
