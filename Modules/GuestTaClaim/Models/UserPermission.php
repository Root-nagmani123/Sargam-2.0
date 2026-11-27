<?php

namespace Modules\GuestTaClaim\Models;

use Illuminate\Database\Eloquent\Model;

class UserPermission extends Model
{
    protected $table = 'gtc_user_permissions';

    protected $guarded = ['id'];
}
