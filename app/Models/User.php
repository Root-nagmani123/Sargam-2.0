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
        'name',
        'email',
        'password',
        'last_login',
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

//   public function roles()
// {
//     return $this->belongsToMany(
//         UserRoleMaster::class,
//         'employee_role_mapping',          // pivot table
//         'user_credentials_pk',            // foreign key of user
//         'user_role_master_pk'             // foreign key of role
//     );
// }

/**
     * Employees and faculty for complaint section (complainant / assignment dropdowns).
     * user_credentials.user_id maps to employee_master.pk and faculty_master.employee_master_pk.
     * Excludes students: user_credentials.user_category != 'S'.
     *
     * Each row carries `can_login`: LoginController turns away any user_category
     * 'E' whose employee_master.status is 2 ("Your account is inactive"), and
     * two thirds of the rows this join reaches are status 2 — dormant accounts,
     * some unused since 2016. Assigning a complaint to one of those sent it to
     * somebody who could never open the app, so it surfaced for nobody.
     *
     * The flag rather than a filter, because the two callers want different
     * things: the assignment dropdown must not let a nodal officer hand work to
     * an account that cannot sign in, while the complainant dropdown may still
     * name anyone a complaint is logged on behalf of. Loginable names sort
     * first so the usable half of the list is what the officer sees.
     *
     * @param int|null $departmentId Optional: filter by employee department_master_pk
     * @return \Illuminate\Support\Collection { employee_pk, employee_name, mobile, designation_name?, can_login }
     */
    public static function getEmployeesAndFacultyForComplaint($departmentId = null)
    {
        $query = DB::table('user_credentials as uc')
            ->join('employee_master as e', function ($join) {
                $join->on('uc.user_id', '=', 'e.pk');
                if (\Illuminate\Support\Facades\Schema::hasColumn('employee_master', 'pk_old')) {
                    $join->orOn('uc.user_id', '=', 'e.pk_old');
                }
            })
            ->leftJoin('designation_master as d', 'e.designation_master_pk', '=', 'd.pk')
            ->where('uc.user_category', '!=', 'S')
            ->select(
                'e.pk as employee_pk',
                DB::raw("TRIM(CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.middle_name, ''), ' ', COALESCE(e.last_name, ''))) as employee_name"),
                DB::raw("COALESCE(e.mobile, '') as mobile"),
                'd.designation_name',
                DB::raw('(e.status = 1) as can_login')
            )
            ->orderByDesc(DB::raw('e.status = 1'))
            ->orderBy('e.first_name')
            ->groupBy('e.pk', 'e.first_name', 'e.middle_name', 'e.last_name', 'e.mobile', 'd.designation_name', 'e.status');

        if ($departmentId) {
            $query->where('e.department_master_pk', $departmentId);
        }

        return $query->get();
    }
}
