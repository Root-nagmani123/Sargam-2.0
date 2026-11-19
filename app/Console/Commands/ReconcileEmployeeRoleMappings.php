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
 * show and print pages still list employee_role_mapping, which until now was written
 * only by the wizard. Existing employees whose roles were changed on Role & Permission >
 * Users therefore show different roles on Step 3 and on the show/print pages until they
 * are re-saved (PR #354 review, F-003).
 *
 * This mirrors each employee ('E') login's offered Step 3 options onto the roles it
 * holds, using EmployeeRoleMapping::mirrorOfferedOptions(), the same method the Users
 * screen now calls. It changes only employee_role_mapping. Spatie roles and permissions
 * are read, never written. Options Step 3 does not offer (HR-only tags, inactive
 * options) are kept.
 *
 * Skipped: logins whose employee has more than one user_credentials row. Step 3 reads
 * the mapping for those, and the wizard refuses to change their RBAC, so the mapping is
 * the only record of what was chosen for them.
 *
 * Dry run by default. Pass --apply to write. Re-runnable: a second run finds nothing.
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

        $query->chunkById(200, function ($users) use (
            $apply, $offered, $sharedEmployeePks, &$checked, &$changed, &$added, &$removed, &$skipped
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
}
