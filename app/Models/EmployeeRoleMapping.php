<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeRoleMapping extends Model
{
    protected $table = 'employee_role_mapping';

    protected $primaryKey = 'pk';
    protected $guarded = [];
    public $timestamps = false;
    public function employee()
    {
        return $this->belongsTo(EmployeeMaster::class, 'user_credentials_pk', 'pk');
    }

    public function role()
    {
        return $this->belongsTo(UserRoleMaster::class, 'user_role_master_pk', 'pk');
    }

    /**
     * Make a login's offered Role Assignment options (the Member wizard's Step 3
     * checkboxes) match the `roles` it actually holds.
     *
     * Role & Permission > Users assigns roles with syncRoles() alone, so before this an
     * employee's Step 3, show and print pages kept the roles from the last wizard save
     * while the Users list showed the new ones. Options Step 3 does not offer (HR-only
     * tags such as "Staff", inactive options) are left as they are, as update() does.
     * Only rows that change are deleted or inserted.
     *
     * @param  iterable<string>  $roleNames  canonical `roles` names the login now holds
     */
    public static function mirrorOfferedOptions(int $userCredentialPk, iterable $roleNames): void
    {
        $offered = UserRoleMaster::getUserRoleList()->keys()->map(fn ($pk) => (int) $pk)->all();

        if ($offered === []) {
            return;
        }

        $wanted = UserRoleMaster::offeredOptionPksForRoles($roleNames);

        $held = self::where('user_credentials_pk', $userCredentialPk)
            ->whereIn('user_role_master_pk', $offered)
            ->pluck('user_role_master_pk')
            ->map(fn ($pk) => (int) $pk)
            ->unique()
            ->all();

        $remove = array_values(array_diff($held, $wanted));
        $add = array_values(array_diff($wanted, $held));

        if ($remove !== []) {
            self::where('user_credentials_pk', $userCredentialPk)
                ->whereIn('user_role_master_pk', $remove)
                ->delete();
        }

        if ($add !== []) {
            self::insert(array_map(fn ($pk) => [
                'user_credentials_pk' => $userCredentialPk,
                'user_role_master_pk' => $pk,
            ], $add));
        }
    }
}
