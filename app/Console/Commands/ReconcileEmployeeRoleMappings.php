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
 * Notification routing (PR #354 review, F-006): NotificationReceiverService picks
 * recipients by option name from employee_role_mapping (Admin, HAC Person,
 * Training-Induction, ...). Every row this command adds or removes therefore adds or
 * removes that login as a recipient of those notifications. The summary prints the rows
 * to add and remove per option, by name, so the routing change can be reviewed before
 * --apply. Run with -v to list each login; keep that listing as the rollback record.
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
        $offered = UserRoleMaster::getUserRoleList()->keys()->map(fn ($pk) => (int) $pk)->all();

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

                $roleNames = $user->roles->pluck('name')->all();
                $wanted = UserRoleMaster::offeredOptionPksForRoles($roleNames);
                $held = collect($mapped->get($user->pk, []))
                    ->pluck('user_role_master_pk')
                    ->map(fn ($pk) => (int) $pk)
                    ->unique()
                    ->all();

                $toAdd = array_diff($wanted, $held);
                $toRemove = array_diff($held, $wanted);

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
                        'user_credentials.pk %d: add [%s] remove [%s]',
                        $user->pk,
                        implode(',', $toAdd),
                        implode(',', $toRemove)
                    ));
                }

                if ($apply) {
                    DB::transaction(fn () => EmployeeRoleMapping::mirrorOfferedOptions((int) $user->pk, $roleNames));
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
     * Rows to add and remove per option, by name. Each row is one login gained or lost
     * as a recipient of the mapping-driven notifications for that option (F-006).
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

        $this->line('By option (each row adds or removes the login as a recipient of notifications looked up by that option):');
        $this->table(['Option pk', 'Option', 'Logins gaining it', 'Logins losing it'], array_map(fn ($pk) => [
            $pk,
            (string) ($names[$pk] ?? '?'),
            $addedByOption[$pk] ?? 0,
            $removedByOption[$pk] ?? 0,
        ], $pks));
    }
}
