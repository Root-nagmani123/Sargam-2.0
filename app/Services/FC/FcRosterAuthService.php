<?php

namespace App\Services\FC;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * FC web login (/fc/login): credentials are read only from fc_registration_master.
 * Main /login continues to use user_credentials. Staged FC users use Auth id -roster_pk for forms.
 */
class FcRosterAuthService
{
    public const SESSION_ROSTER_PK = 'fc_roster_auth_pk';

    public static function stagedUserId(int $rosterPk): int
    {
        return -abs($rosterPk);
    }

    public static function isStagedUserId(?int $userId): bool
    {
        return $userId !== null && $userId < 0;
    }

    public static function rosterPkFromStagedUserId(int $userId): int
    {
        return abs($userId);
    }

    public function normalizeLoginUsername(mixed $login): string
    {
        return trim((string) $login);
    }

    /**
     * FC login must not proceed once the username exists in user_credentials (post-migration / main login).
     * Existence check only — password auth for /fc/login stays on fc_registration_master.
     */
    public function usernameExistsInUserCredentials(string $login): bool
    {
        $login = $this->normalizeLoginUsername($login);
        if ($login === '') {
            return false;
        }

        return DB::table('user_credentials')->where('user_name', $login)->exists();
    }

    /**
     * Find roster row with staged credentials (user_id + bcrypt password).
     */
    public function findStagedRosterByLogin(string $login): ?object
    {
        $login = $this->normalizeLoginUsername($login);
        if ($login === '') {
            return null;
        }

        $query = DB::table('fc_registration_master')
            ->where('user_id', $login)
            ->whereNotNull('password')
            ->where('password', '!=', '');

        if (\Illuminate\Support\Facades\Schema::hasColumn('fc_registration_master', 'active_inactive')) {
            $query->where(function ($q) {
                $q->where('active_inactive', 1)->orWhereNull('active_inactive');
            });
        }

        return $query->orderByDesc('pk')->first();
    }

    public function verifyStagedPassword(object $roster, string $plainPassword): bool
    {
        $hash = $roster->password ?? '';

        return is_string($hash) && $hash !== '' && Hash::check($plainPassword, $hash);
    }

    public function establishStagedSession(object $roster): User
    {
        session([self::SESSION_ROSTER_PK => (int) $roster->pk]);
        $user = $this->buildStagedUser($roster);
        Auth::setUser($user);

        return $user;
    }

    /**
     * Re-hydrate Auth user from session on each request (staged users are not in user_credentials).
     */
    public function hydrateStagedUserFromSession(): bool
    {
        $rosterPk = session(self::SESSION_ROSTER_PK);
        if (! is_numeric($rosterPk) || (int) $rosterPk < 1) {
            return false;
        }

        $roster = DB::table('fc_registration_master')
            ->where('pk', (int) $rosterPk)
            ->whereNotNull('user_id')
            ->where('user_id', '!=', '')
            ->whereNotNull('password')
            ->where('password', '!=', '')
            ->first();

        if (! $roster) {
            $this->clearStagedSession();

            return false;
        }

        Auth::setUser($this->buildStagedUser($roster));

        return true;
    }

    public function clearStagedSession(): void
    {
        session()->forget(self::SESSION_ROSTER_PK);
    }

    public function buildStagedUser(object $roster): User
    {
        $user = new User;
        $user->pk = self::stagedUserId((int) $roster->pk);
        $user->user_name = $this->normalizeLoginUsername($roster->user_id);
        $user->user_category = 'S';
        $user->Active_inactive = 1;
        $user->exists = false;

        return $user;
    }
}
