<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reconcile FC trainee data that was written under the staged-login roster id
 * (fc_registration_master.pk) before the trainee was migrated into
 * user_credentials.
 *
 * Background: FC trainees can authenticate via /fc/login (Auth id = negative
 * roster pk). If they save registration data BEFORE being migrated into
 * user_credentials, fc_user_val() stores their rows under the roster pk. After
 * migration the app uses the user_credentials.pk, so those earlier rows are
 * orphaned — reports (keyed by user_credentials.pk) no longer find them
 * (e.g. uploaded joining documents showing as "Not uploaded").
 *
 * This command re-keys the integer user_id of orphaned rows from the roster pk
 * to the matching user_credentials.pk across the FC registration tables. It only
 * moves a row when the destination id has no existing row in that table (a
 * conflict is left untouched and reported for manual review). Re-runnable and
 * idempotent. File paths are left as-is — they remain valid (the stored path
 * already points at the real file), and the reports key off the column value,
 * not the file location.
 */
class FcReconcileRosterIds extends Command
{
    protected $signature = 'fc:reconcile-roster-ids
                            {--dry-run : Report what would change without modifying anything}
                            {--user= : Limit to a single roster username (fc_registration_master.user_id)}';

    protected $description = 'Re-key orphaned FC registration rows from the staged roster id to the migrated user_credentials id';

    /**
     * FC tables keyed by an integer user id, derived from the FC form config
     * (fc_form_steps / fc_form_fields / fc_form_field_groups target tables) plus
     * the student_masters tracker. Username-keyed tables are intentionally
     * excluded — the username string is identical for both identities, so their
     * data is never split.
     */
    private array $tables = [
        'student_masters',
        'student_master_firsts',
        'student_master_seconds',
        'new_registration_bank_details_masters',
        'fc_joining_documents_user_uploads',
        'fc_joining_related_documents_details_masters',
        'student_iosr_reasonable_adjust_masters',
        'student_knowledge_hindi_masters',
        'fc_pre_history',
        'student_cloth_size_master_details',
        'student_master_academic_distinctions',
        'student_master_employment_details',
        'student_master_higher_educational_details',
        'student_master_hobbies_details',
        'student_master_language_knowns',
        'student_master_module_masters',
        'student_master_qualification_details',
        'student_master_spouse_masters',
        'student_sports_fitness_teach_masters',
        'student_confirm_masters',
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $this->info($dryRun ? '[DRY RUN] No changes will be written.' : 'Applying reconciliation…');

        // roster pk -> credentials pk, for trainees whose username exists in
        // user_credentials and whose roster pk differs from the credentials pk.
        $pairs = DB::table('fc_registration_master as r')
            ->join('user_credentials as uc', 'uc.user_name', '=', 'r.user_id')
            ->whereColumn('r.pk', '<>', 'uc.pk')
            ->when($this->option('user'), fn ($q) => $q->where('r.user_id', $this->option('user')))
            ->select('r.pk as roster_pk', 'uc.pk as cred_pk', 'r.user_id as username')
            ->get();

        if ($pairs->isEmpty()) {
            $this->info('No roster/credentials id pairs to reconcile.');
            return self::SUCCESS;
        }

        $this->line('Trainees to consider: ' . $pairs->count());

        $totalMoved = 0;
        $totalConflicts = 0;
        $rows = [];

        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            $col = Schema::hasColumn($table, 'user_id') ? 'user_id'
                : (Schema::hasColumn($table, 'userid') ? 'userid' : null);
            if ($col === null) {
                continue; // username-keyed or unrelated; nothing to re-key
            }

            $moved = 0;
            $conflicts = 0;

            foreach ($pairs as $p) {
                $rosterCount = DB::table($table)->where($col, $p->roster_pk)->count();
                if ($rosterCount === 0) {
                    continue;
                }

                $credExists = DB::table($table)->where($col, $p->cred_pk)->exists();
                if ($credExists) {
                    $conflicts += $rosterCount;
                    $this->warn("  CONFLICT {$table}: {$p->username} has rows under both roster {$p->roster_pk} and cred {$p->cred_pk} — left untouched.");
                    continue;
                }

                if (! $dryRun) {
                    DB::table($table)->where($col, $p->roster_pk)->update([$col => $p->cred_pk]);
                }
                $moved += $rosterCount;
            }

            if ($moved > 0 || $conflicts > 0) {
                $rows[] = [$table, $col, $moved, $conflicts];
            }
            $totalMoved += $moved;
            $totalConflicts += $conflicts;
        }

        $this->newLine();
        $this->table(['Table', 'Key column', 'Rows ' . ($dryRun ? 'to move' : 'moved'), 'Conflicts (skipped)'], $rows);
        $this->info(($dryRun ? 'Would re-key' : 'Re-keyed') . " {$totalMoved} row(s); {$totalConflicts} conflict row(s) skipped.");

        if ($dryRun) {
            $this->comment('Re-run without --dry-run to apply.');
        }

        return self::SUCCESS;
    }
}
