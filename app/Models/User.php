<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
     * Whether $plainPassword matches this user's own stored jbp_password.
     * Supports bcrypt (current format, set via Hash::make() on password
     * change) and legacy 32-character MD5 hashes from older imports; neither
     * format is accepted as a literal match against any other value, so
     * this never grants access on a value that is not that user's own.
     */
    public function checkStoredPassword(string $plainPassword): bool
    {
        $hash = (string) ($this->jbp_password ?? '');
        if ($hash === '') {
            return false;
        }

        if (\Illuminate\Support\Facades\Hash::check($plainPassword, $hash)) {
            return true;
        }

        return strlen($hash) === 32 && hash_equals($hash, md5($plainPassword));
    }

    /**
     * Employees and faculty for complaint section (complainant / assignment dropdowns).
     * user_credentials.user_id maps to employee_master.pk and faculty_master.employee_master_pk.
     * Only logins with a category other than 'S' count: students are excluded, and
     * so are NULL-category logins unless their user_id is the employee's own
     * pk_old. A login reached through pk_old counts only when that key names
     * no other employee (see complaintLoginRows()).
     *
     * Each row carries `can_login`: whether at least one of the employee's
     * logins gets past LoginController's inactive-account check — see
     * LOGIN_ALLOWED_SQL. Assigning a complaint to an employee none of whose
     * logins can sign in sends it to somebody who can never open the app, so
     * it surfaces for nobody.
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
        $query = self::complaintLoginRows()
            ->leftJoin('designation_master as d', 'e.designation_master_pk', '=', 'd.pk')
            ->select(
                'e.pk as employee_pk',
                DB::raw("TRIM(CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.middle_name, ''), ' ', COALESCE(e.last_name, ''))) as employee_name"),
                DB::raw("COALESCE(e.mobile, '') as mobile"),
                'd.designation_name',
                DB::raw('MAX(' . self::LOGIN_ALLOWED_SQL . ') as can_login')
            )
            ->orderByDesc('can_login')
            ->orderBy('e.first_name')
            ->groupBy('e.pk', 'e.first_name', 'e.middle_name', 'e.last_name', 'e.mobile', 'd.designation_name');

        if ($departmentId) {
            $query->where('e.department_master_pk', $departmentId);
        }

        return $query->get();
    }

    /**
     * Whether the employee named by $employeeKey has a non-student login that
     * LoginController would let in. Same rule as the `can_login` column above,
     * so the dropdown and the server agree.
     *
     * The key is resolved to one employee first (see resolveEmployeePk()).
     * Matching `e.pk = key OR e.pk_old = key` directly would let one employee's
     * logins answer for another whose pk_old happens to equal the key.
     */
    public static function employeeCanSignIn($employeeKey): bool
    {
        $employeePk = self::resolveEmployeePk($employeeKey);
        if ($employeePk === null) {
            return false;
        }

        return self::complaintLoginRows()
            ->where('e.pk', $employeePk)
            ->whereRaw(self::LOGIN_ALLOWED_SQL)
            ->exists();
    }

    /**
     * The employee_master.pk an employee key (pk or pk_old) names: the
     * employee whose pk it is, otherwise the one employee whose pk_old it is.
     * Null when it names nobody, or when several employees share it as pk_old
     * and nothing says which one is meant.
     */
    public static function resolveEmployeePk($employeeKey): ?string
    {
        if ($employeeKey === null || $employeeKey === '') {
            return null;
        }

        $pk = DB::table('employee_master')->where('pk', $employeeKey)->value('pk');
        if ($pk !== null) {
            return (string) $pk;
        }
        if (! Schema::hasColumn('employee_master', 'pk_old')) {
            return null;
        }

        $byPkOld = DB::table('employee_master')->where('pk_old', $employeeKey)->limit(2)->pluck('pk');

        return $byPkOld->count() === 1 ? (string) $byPkOld->first() : null;
    }

    /**
     * True for a user_credentials row (alias uc) that passes LoginController's
     * inactive-account check, which refuses a login only when user_category is
     * 'E' AND employee_master looked up by pk = user_id (never pk_old) has
     * status 2. A missing employee row, a NULL or any other status, and every
     * other category sign in. `lu` is that pk-only lookup. COALESCE keeps the
     * result 0/1, because a NULL here would read as "cannot sign in".
     */
    private const LOGIN_ALLOWED_SQL =
        "NOT (COALESCE(uc.user_category = 'E', 0) AND COALESCE(lu.status = 2, 0))";

    /**
     * Non-student logins joined to the employee they belong to (by pk or
     * pk_old), plus `lu`: the employee LoginController itself would look up.
     *
     * A NULL user_category counts only when user_id is the employee's pk_old
     * (Engineering Lead decision, PR337-14). The join is only an identity when
     * user_id carries an employee key. For NULL-category logins joined on
     * employee_master.pk it usually does not: their user_id values fall in the
     * same numeric range as employee_master.pk and mostly name somebody else,
     * so counting them would mark an inactive employee as able to sign in on
     * the strength of another person's account. Those stay excluded.
     *
     * A login reached through pk_old counts only when that key is
     * unambiguous: no employee has it as pk (`lu` is empty) and no other
     * employee has it as pk_old. Once signed in, getEmployeeIdsForUser() maps
     * the login to ONE employee with that pk or pk_old (->first(), no order).
     * When the key is shared, that may be someone else, whose ids do not
     * include this employee's pk, so a complaint stored under this employee
     * would reach a login that cannot act on it. A login joined on the
     * employee's own pk has no such problem: whichever employee it resolves
     * to, the ids include user_id, which is that pk.
     *
     * These exclusions apply only here, to "can this employee sign in". They
     * are not how a signed-in login is identified elsewhere: LoginController
     * admits these logins, and getEmployeeIdsForUser() then maps their user_id
     * to whichever employee has that pk or pk_old, so Issue Management access
     * checks (e.g. status_update()) treat the login as that employee.
     */
    private static function complaintLoginRows()
    {
        $hasPkOld = Schema::hasColumn('employee_master', 'pk_old');

        return DB::table('user_credentials as uc')
            ->join('employee_master as e', function ($join) use ($hasPkOld) {
                $join->on('uc.user_id', '=', 'e.pk');
                if ($hasPkOld) {
                    $join->orOn('uc.user_id', '=', 'e.pk_old');
                }
            })
            ->leftJoin('employee_master as lu', 'lu.pk', '=', 'uc.user_id')
            ->where(function ($q) use ($hasPkOld) {
                // On the employee's own pk: any category but 'S' and NULL.
                $q->where(function ($c) {
                    $c->whereColumn('uc.user_id', 'e.pk')
                        ->whereNotNull('uc.user_category')
                        ->where('uc.user_category', '!=', 'S');
                });
                if ($hasPkOld) {
                    // On the employee's pk_old: any category but 'S' (NULL
                    // included), and only when the key names this employee alone.
                    $q->orWhere(function ($c) {
                        $c->whereColumn('uc.user_id', 'e.pk_old')
                            ->where(function ($cat) {
                                $cat->whereNull('uc.user_category')
                                    ->orWhere('uc.user_category', '!=', 'S');
                            })
                            ->whereNull('lu.pk')
                            ->whereNotExists(function ($other) {
                                $other->select(DB::raw(1))
                                    ->from('employee_master as other')
                                    ->whereColumn('other.pk_old', 'uc.user_id')
                                    ->whereColumn('other.pk', '!=', 'e.pk');
                            });
                    });
                }
            });
    }
}
