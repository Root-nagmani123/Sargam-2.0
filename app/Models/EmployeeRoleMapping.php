<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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
     * Whether an employee owns more than one user_credentials row (a "shared employee").
     *
     * For such an employee the Member wizard refuses the RBAC half of a save and keeps the
     * options the administrator ticked as the only record of that choice, and
     * members:reconcile-role-mappings skips the login. Every writer of the offered rows
     * has to apply the same rule, or one of them overwrites what the others leave alone
     * (PR #354 review, F-011). Same test as the command: COUNT(*) > 1 by user_id.
     */
    public static function belongsToSharedEmployee(?int $employeeMasterPk): bool
    {
        if ($employeeMasterPk === null) {
            return false;
        }

        return DB::table('user_credentials')->where('user_id', $employeeMasterPk)->count() > 1;
    }

    /**
     * Options read by a NotificationReceiverService lookup that uses employee_role_mapping
     * alone: getEstateHacPersonUserIds() (HAC Person) and getAdminUserId() (Admin, first
     * row only). Keep in step with that service.
     */
    public const MAPPING_ONLY_LOOKUP_OPTIONS = ['HAC Person', 'Admin'];

    /**
     * Options read by a lookup that takes the union of the mapping rows and the holders of
     * the Spatie role with the same name: getDisciplineMemoAdminReceivers(),
     * getMemoNoticeManagementRoleUserIds(), getEstateRequestApproverUserIds(),
     * getIssueReportAdminReceivers(). Keep in step with that service.
     */
    public const UNION_LOOKUP_OPTIONS = [
        'Super Admin',
        'Training Induction Admin',
        'Training-Induction',
        'Estate Admin',
    ];

    /**
     * user_role_name values whose mapping rows decide who receives notifications.
     *
     * Notification routing must not change because of the role sync (PR #354 review,
     * F-006: Product owner position). Before this PR only the Member wizard and the Users
     * create/edit forms wrote these rows, by an administrator's explicit choice. The
     * automatic mirror (Users-screen role save, members:reconcile-role-mappings) therefore
     * never adds or removes a row for these options; it leaves them exactly as they are.
     *
     * @return list<string>
     */
    public static function routingOptionNames(): array
    {
        return array_values(array_unique(array_merge(self::MAPPING_ONLY_LOOKUP_OPTIONS, self::UNION_LOOKUP_OPTIONS)));
    }

    /**
     * Offered Step 3 option pks the automatic mirror may write: every offered option
     * except the notification-routing ones (routingOptionNames()).
     *
     * @return list<int>
     */
    public static function mirroredOptionPks(): array
    {
        $offered = UserRoleMaster::getUserRoleList()->keys()->map(fn ($pk) => (int) $pk)->all();

        if ($offered === []) {
            return [];
        }

        $routing = UserRoleMaster::whereIn('pk', $offered)
            ->whereIn('user_role_name', self::routingOptionNames())
            ->pluck('pk')
            ->map(fn ($pk) => (int) $pk)
            ->all();

        return array_values(array_diff($offered, $routing));
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
     * Not atomic on its own: it reads the rows, then inserts the missing ones, and the
     * table has no unique key. Call it inside a transaction whose first statement locks the
     * login's user_credentials row (lockForUpdate), so two calls for one login run one
     * after the other (PR #354 review, F-016).
     *
     * Notification-routing options (routingOptionNames()) are never touched, so a
     * Users-screen save or --apply cannot change who receives notifications (F-006).
     *
     * @param  iterable<string>  $roleNames  canonical `roles` names the login now holds
     * @return array{added: list<int>, removed: list<int>}  option pks actually written
     */
    public static function mirrorOfferedOptions(int $userCredentialPk, iterable $roleNames): array
    {
        $offered = self::mirroredOptionPks();

        if ($offered === []) {
            return ['added' => [], 'removed' => []];
        }

        $wanted = array_values(array_intersect(UserRoleMaster::offeredOptionPksForRoles($roleNames), $offered));

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

        return ['added' => $add, 'removed' => $remove];
    }
}
