<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $table = 'user_credentials';
    protected $primaryKey = 'pk';


    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_name',
        'first_name',
        'last_name',
        'email_id',
        'mobile_no',
        'jbp_password',
        'password',
        'last_login',
        'Active_inactive',
        'user_category',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Accessor for name to combine first_name and last_name
     */
    public function getNameAttribute()
    {
        $firstName = $this->attributes['first_name'] ?? '';
        $lastName = $this->attributes['last_name'] ?? '';
        return trim($firstName . ' ' . $lastName) ?: ($this->attributes['user_name'] ?? 'N/A');
    }

    /**
     * Accessor for email to map to email_id column
     */
    public function getEmailAttribute()
    {
        return $this->attributes['email_id'] ?? '';
    }

    /**
     * Mutator for email to map to email_id column
     */
    public function setEmailAttribute($value)
    {
        $this->attributes['email_id'] = $value;
    }

    /**
     * Accessor for is_active to map to Active_inactive column
     */
    public function getIsActiveAttribute()
    {
        return $this->attributes['Active_inactive'] ?? 0;
    }

    /**
     * Scope for active users
     */
    public function scopeActive($query)
    {
        return $query->where('Active_inactive', 1);
    }

    public function getAuthIdentifierName()
    {
        return 'pk';
    }

    public static function getpermissionGroups()
    {
        return DB::table('permissions')
            ->select('group_name as name')
            ->groupBy('group_name')
            ->get();
    }

    public static function getpermissionsByGroupName(string $group_name)
    {
        return DB::table('permissions')
            ->select('name', 'id')
            ->where('group_name', $group_name)
            ->get();
    }

    public static function roleHasPermissions(Role $role, $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (! $role->hasPermissionTo($permission->name)) {
                return false;
            }
        }

        return true; // ensure returning true if all permissions are granted
    }

    public function actionLogs()
    {
        return $this->hasMany(ActionLog::class, 'action_by');
    }

  public function roles()
{
    return $this->belongsToMany(
        UserRoleMaster::class,
        'employee_role_mapping',          // pivot table
        'user_credentials_pk',            // foreign key of user
        'user_role_master_pk'             // foreign key of role
    );
}

}
