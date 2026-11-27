<?php

namespace App\Console\Commands;

use App\Models\EmployeeRoleMapping;
use App\Models\User;
use App\Models\UserRoleMaster;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-off reconciliation of employee_role_mapping with the roles employee logins hold.
 *
 * The Member wizard's Step 3 now ticks the roles a login holds (Spatie). The employee
 * show and print pages, and the mapping-driven notification lookups in
 * NotificationReceiverService, still read employee_role_mapping. Before this PR the Users
 * screen's role assignment never wrote that table, so existing employees whose roles were
 * changed there show different roles on Step 3 and on the show/print pages until they are
 * re-saved (PR #354 review, F-003).
 *
 * This mirrors each employee ('E') login's offered Step 3 options onto the roles it
 * holds, using EmployeeRoleMapping::mirrorOfferedOptions(), the same method the Users
 * screen now calls. It changes only employee_role_mapping. Spatie roles and permissions
 * are read, never written. Options Step 3 does not offer (HR-only tags, inactive
 * options) are kept.
 *
 * Skipped: logins whose employee has more than one user_credentials row. Step 3 reads
 * the mapping for those, and the wizard refuses to change their RBAC, so the mapping is
 * the only record of what was chosen for them. The Users screen skips them by the same
 * rule (EmployeeRoleMapping::belongsToSharedEmployee(), PR #354 review, F-011); this
 * command computes it in one query instead of once per login.
 *
 * Notification routing does not change (PR #354 review, F-006). Options whose mapping
 * rows a NotificationReceiverService lookup reads (EmployeeRoleMapping::routingOptionNames():
 * HAC Person, Admin, Super Admin, Training Induction Admin, Training-Induction, Estate
 * Admin) are never added or removed by this command, nor by the Users screen. Their rows
 * stay as the Member wizard last wrote them, so for those options Step 3 (Spatie) and the
 * show/print pages (mapping) can still differ until an administrator saves the member in
 * the wizard. Every other offered option is reconciled.
 *
 * Concurrency (F-017): with --apply each login is mirrored in its own transaction that
 * locks the login row first and reads its roles after the lock, so a Users-screen save
 * made during the run is never undone.
 *
 * Run with -v to list each login. With --apply the listing shows the rows actually
 * written; keep it as the rollback record.
 *
 * Dry run by default. Pass --apply to write. Re-runnable: a second run finds nothing.
 * Deploy steps: docs/deploy-notes-employee-role-sync.md.
 */
class ReconcileEmployeeRoleMappings extends Command
{
    protected $signature = 'members:reconcile-role-mappings
                            {--apply : Write the changes (default is a dry run that only reports)}
                            {--user= : Limit to one user_credentials.pk}';

    protected $description = 'Mirror employee logins\' offered Role Assignment options (employee_role_mapping) onto the Spatie roles they hold';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        // Offered options minus the notification-routing ones, which this command never
        // writes (F-006). Same set mirrorOfferedOptions() uses, so the dry run's counts are
        // what --apply writes.
        $offered = EmployeeRoleMapping::mirroredOptionPks();

        if ($offered === []) {
            $this->warn('No offered Role Assignment options; nothing to reconcile.');

            return self::SUCCESS;
        }

        $sharedEmployeePks = DB::table('user_credentials')
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('user_id')
            ->flip();

        $query = User::query()
            ->whereRaw("UPPER(TRIM(COALESCE(user_category, ''))) = 'E'")
            ->orderBy('pk');

        if ($this->option('user') !== null) {
            $query->where('pk', (int) $this->option('user'));
        }

        $checked = $changed = $added = $removed = $skipped = 0;
        $addedByOption = $removedByOption = [];

        $query->chunkById(200, function ($users) use (
            $apply, $offered, $sharedEmployeePks, &$checked, &$changed, &$added, &$removed, &$skipped,
            &$addedByOption, &$removedByOption
        ) {
            $users->load('roles');

            $mapped = EmployeeRoleMapping::whereIn('user_credentials_pk', $users->pluck('pk'))
                ->whereIn('user_role_master_pk', $offered)
                ->get(['user_credentials_pk', 'user_role_master_pk'])
                ->groupBy('user_credentials_pk');

            foreach ($users as $user) {
                $checked++;

                if ($user->user_id !== null && isset($sharedEmployeePks[$user->user_id])) {
                    $skipped++;

                    continue;
                }

                if ($apply) {
                    // F-017: the roles mirrored are read AFTER the lock, inside the same
                    // transaction, not taken from the chunk loaded earlier. A Users-screen
                    // save of this login that committed while the chunk was waiting is
                    // therefore seen, and one that starts now waits for this commit. The
                    // counts and the -v line below report what was actually written, so the
                    // -v listing is a true rollback record.
                    $result = DB::transaction(function () use ($user) {
                        $locked = User::whereKey($user->pk)->lockForUpdate()->first();

                        if (! $locked) {
                            return ['added' => [], 'removed' => []];
                        }

                        return EmployeeRoleMapping::mirrorOfferedOptions(
                            (int) $locked->pk,
                            $locked->getRoleNames()->all()
                        );
                    });

                    $toAdd = $result['added'];
                    $toRemove = $result['removed'];
                } else {
                    $roleNames = $user->roles->pluck('name')->all();
                    $wanted = array_values(array_intersect(UserRoleMaster::offeredOptionPksForRoles($roleNames), $offered));
                    $held = collect($mapped->get($user->pk, []))
                        ->pluck('user_role_master_pk')
                        ->map(fn ($pk) => (int) $pk)
                        ->unique()
                        ->all();

                    $toAdd = array_values(array_diff($wanted, $held));
                    $toRemove = array_values(array_diff($held, $wanted));
                }

                if ($toAdd === [] && $toRemove === []) {
                    continue;
                }

                $changed++;
                $added += count($toAdd);
                $removed += count($toRemove);

                foreach ($toAdd as $pk) {
                    $addedByOption[$pk] = ($addedByOption[$pk] ?? 0) + 1;
                }

                foreach ($toRemove as $pk) {
                    $removedByOption[$pk] = ($removedByOption[$pk] ?? 0) + 1;
                }

                if ($this->output->isVerbose()) {
                    $this->line(sprintf(
                        'user_credentials.pk %d: %s [%s] %s [%s]',
                        $user->pk,
                        $apply ? 'added' : 'add',
                        implode(',', $toAdd),
                        $apply ? 'removed' : 'remove',
                        implode(',', $toRemove)
                    ));
                }
            }
        }, 'pk');

        $this->table(['Employee logins checked', 'Skipped (shared employee)', 'Logins to change', 'Rows to add', 'Rows to remove'], [
            [$checked, $skipped, $changed, $added, $removed],
        ]);

        $this->reportByOption($addedByOption, $removedByOption);

        if ($apply) {
            if ($changed > 0) {
                \App\DataTables\MemberDataTable::bumpListingCacheEpoch();
            }

            $this->info('Applied.');
        } else {
            $this->comment('Dry run: nothing was written. Re-run with --apply to write.');
        }

        return self::SUCCESS;
    }

    /**
     * Options read by a lookup that uses employee_role_mapping alone
     * (NotificationReceiverService::getEstateHacPersonUserIds(), getAdminUserId()).
     * Keep in step with that service.
     */
    public const MAPPING_ONLY_LOOKUP_OPTIONS = EmployeeRoleMapping::MAPPING_ONLY_LOOKUP_OPTIONS;

    /**
     * Options read by a lookup that takes the union of the mapping and the holders of the
     * Spatie role with the same name. Defined once on EmployeeRoleMapping.
     */
    public const UNION_LOOKUP_OPTIONS = EmployeeRoleMapping::UNION_LOOKUP_OPTIONS;

    /**
     * How the notification lookups read an option, by its user_role_name (the field they
     * match on). See the class docblock for what an added or removed row does in each case.
     */
    public static function notificationLookupFor(string $userRoleName): string
    {
        if (in_array($userRoleName, self::MAPPING_ONLY_LOOKUP_OPTIONS, true)) {
            return 'mapping only';
        }

        if (in_array($userRoleName, self::UNION_LOOKUP_OPTIONS, true)) {
            return 'mapping + Spatie role';
        }

        return 'no lookup';
    }

    /**
     * Rows to add and remove per option, by name, with how the notification lookups read
     * that option (F-006, F-015).
     *
     * @param  array<int, int>  $addedByOption    option pk => logins gaining it
     * @param  array<int, int>  $removedByOption  option pk => logins losing it
     */
    private function reportByOption(array $addedByOption, array $removedByOption): void
    {
        $pks = array_unique(array_merge(array_keys($addedByOption), array_keys($removedByOption)));

        if ($pks === []) {
            return;
        }

        sort($pks);
        $names = UserRoleMaster::whereIn('pk', $pks)->pluck('user_role_name', 'pk');

        $this->line('By option. Notification-routing options are never changed by this command (F-006), so every row below has no notification effect.');
        $this->table(['Option pk', 'Option', 'Logins gaining it', 'Logins losing it', 'Read by'], array_map(fn ($pk) => [
            $pk,
            (string) ($names[$pk] ?? '?'),
            $addedByOption[$pk] ?? 0,
            $removedByOption[$pk] ?? 0,
            self::notificationLookupFor((string) ($names[$pk] ?? '')),
        ], $pks));
    }
}
