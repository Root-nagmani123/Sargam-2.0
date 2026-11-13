<?php

namespace App\Http\Controllers\Concerns;

use App\Support\LogSafe;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * One audit line per privileged Club / Society action (publish, toggle, withdraw,
 * save, delete), so an election outcome or a deletion can be attributed to the
 * account that made it (PR #340 F-002).
 *
 * Written to the application log rather than a table: the new tables carry no
 * created_by / updated_by, and schema changes are the DBA's call.
 */
trait AuditsClubSocietyActions
{
    /**
     * @param  string  $action   e.g. "election_drive.publish"
     * @param  array<string, mixed>  $context  target pk, old/new values; names and other
     *                                         request text are neutralised by LogSafe
     */
    protected function auditClubSociety(string $action, array $context = []): void
    {
        Log::info('club_society.' . $action, LogSafe::context(array_merge([
            'actor' => Auth::id(),
            'ip'    => request()->ip(),
        ], $context)));
    }
}
