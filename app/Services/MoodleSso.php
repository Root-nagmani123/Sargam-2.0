<?php

namespace App\Services;

use App\Models\User;

/**
 * Moodle single sign-on: the one place a Moodle token is turned into a Sargam
 * account. The two feedback entry points in CalendarController and the `auth`
 * middleware all go through here, so one token resolves to the same account
 * whichever of them it lands on.
 *
 * WHAT THE TOKEN IS. The user_name, AES-128-CBC encrypted under a key and IV
 * shared with Moodle, base64-encoded. No expiry, no nonce, no MAC, so a token
 * never changes and never expires, and whoever reads one (an access log, browser
 * history, a Referer header) can replay it until the shared key is rotated. It
 * is encryption, not authentication. Replacing it with a signed, expiring,
 * single-use credential needs a matching change on the Moodle side and is
 * tracked as its own piece of work (PR #324 review F-001). Until then the
 * middleware only accepts it on the landing paths in tokenPaths(), which keeps
 * a leaked token from working on every route behind `auth`.
 */
class MoodleSso
{
    /**
     * The user_name a token carries, or null if it cannot be trusted.
     *
     * Fails closed when MOODLE_SHARED_KEY / MOODLE_SHARED_IV are unset: with a
     * null key and IV openssl_decrypt() silently uses all-zero bytes, so anyone
     * could encrypt any user_name themselves and be logged in as that user.
     *
     * @param  mixed  $base64Token  already URL-decoded; anything but a string is refused
     */
    public function usernameFromToken($base64Token): ?string
    {
        $key = (string) config('services.moodle.key');
        $iv = (string) config('services.moodle.iv');

        if ($key === '' || $iv === '' || ! is_string($base64Token) || $base64Token === '') {
            return null;
        }

        $raw = base64_decode($base64Token);
        if ($raw === false) {
            return null;
        }

        $username = openssl_decrypt($raw, 'AES-128-CBC', $key, 0, $iv);
        $username = is_string($username) ? trim($username) : '';

        return $username === '' ? null : $username;
    }

    /**
     * The account a Moodle user_name refers to, or null.
     *
     * An exact match wins (indexed, and the only behaviour before). Failing that,
     * the stored name is compared trimmed, because 78 user_credentials rows carry
     * stray whitespace and could never sign in through Moodle - but only a single
     * trimmed match is accepted. Where two accounts differ only by whitespace we
     * cannot tell which one Moodle means, and guessing would log in a stranger.
     */
    public function userFor(string $username): ?User
    {
        $user = User::where('user_name', $username)->first();
        if ($user) {
            return $user;
        }

        $candidates = User::whereRaw('TRIM(user_name) = ?', [$username])->limit(2)->get();

        return $candidates->count() === 1 ? $candidates->first() : null;
    }

    /** The account a token names, or null. */
    public function userFromToken($base64Token): ?User
    {
        $username = $this->usernameFromToken($base64Token);

        return $username === null ? null : $this->userFor($username);
    }

    /**
     * The session roles for an account signed in through Moodle - the same rule
     * LoginController applies to a password login. It used to be ['Student-OT']
     * whoever the token named, and hasRole() reads the session before the role
     * tables, so a staff account arriving from Moodle was treated as a trainee.
     *
     * @return list<string>
     */
    public function sessionRolesFor(User $user): array
    {
        return $user->user_category === 'S'
            ? ['Student-OT']
            : $user->roles()->pluck('name')->all();
    }

    /**
     * Paths (Request::is() patterns) on which the `auth` middleware accepts a
     * Moodle token. Everywhere else ?token= is ignored. The default is the
     * timetable, the page the middleware's token login was first written for
     * (7a9becd86 moved it there from CalendarController::index). Any other Moodle
     * landing page must be added through MOODLE_TOKEN_PATHS.
     *
     * @return list<string>
     */
    public function tokenPaths(): array
    {
        $paths = config('services.moodle.token_paths');

        return is_array($paths) && $paths !== [] ? array_values($paths) : ['calendar'];
    }
}
