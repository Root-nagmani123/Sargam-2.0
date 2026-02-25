<?php

namespace App\Models\Mess;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class MessPermissionUser extends Model
{
    protected $table = 'mess_permission_users';

    protected $fillable = [
        'mess_permission_id',
        'user_id'
    ];

    /**
     * Get the permission
     */
    public function permission()
    {
        return $this->belongsTo(MessPermission::class, 'mess_permission_id');
    }

    /**
     * Get the user
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'pk');
    }
}
