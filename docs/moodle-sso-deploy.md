# Moodle SSO — deploy note

Read this before deploying the branch that closes the Moodle login-as-anyone paths
(PR #324). **If you skip it, every Moodle sign-in can start returning 403 at once,
and nothing in the error says why.**

## What changed for operators

| Before | After |
| --- | --- |
| `?username=<name>` on `/feedback/student-feedback-url` signed you in as that user | Refused with 403. Moodle must send `?token=` |
| The key and IV had literal fallbacks in source, so SSO worked with nothing configured | **No fallback.** With the variables unset, every token is refused |
| The `auth` middleware accepted `?token=` on every signed-in route | Only on the paths in `MOODLE_TOKEN_PATHS` (default: the timetable, `/calendar`) |
| Any `?token=` on a link signed the current user out, even an empty one | Only a token that resolves to a *different* account replaces the session |
| A Moodle sign-in always got the session role `Student-OT` | Session roles follow the account, the same as a password login |

## Environment variables

Set these on every environment where Moodle hands users to Sargam, **before** the
code goes live:

```dotenv
# Must be byte-for-byte the values the Moodle side encrypts with.
# AES-128-CBC: the key is 16 bytes and the IV is 16 bytes.
MOODLE_SHARED_KEY=
MOODLE_SHARED_IV=

# Optional. Comma-separated Laravel path patterns on which the auth middleware
# accepts ?token=. Leave unset for the timetable only.
# Example: MOODLE_TOKEN_PATHS=calendar,calendar/*
MOODLE_TOKEN_PATHS=
```

Run `php artisan config:clear` after setting them if the config is cached.

The two feedback entry points, `/student-faculty-feedback` and
`/feedback/student-feedback-url`, always accept a token. `MOODLE_TOKEN_PATHS` only
governs the `auth` middleware.

## Before you deploy

1. **Confirm both variables are set** in the target environment's `.env`. If they
   are not, this deploy is a total Moodle SSO outage, by design.
2. **If either variable was ever set to the literal that used to be in source**
   (`1234567890abcdef` / `abcdef1234567890`), rotate it on both ends. That value
   was readable by anyone with the repository and could mint a login for any
   account.
3. **List every Sargam URL Moodle links to with `?token=`**. Anything other than
   the timetable and the two feedback routes must go into `MOODLE_TOKEN_PATHS`,
   or those links will send users to the login page.

## After you deploy

- Follow a real Moodle hand-off to the timetable and to the student feedback page.
  Both should land signed in, with no `token=` left in the address bar.
- `GET /feedback/student-feedback-url?username=anyone` should return 403.

## Known residual

The token itself is still the user_name encrypted under a shared static key and
IV. It has no expiry, no nonce and no signature, so a token that appears in an
access log or a `Referer` header can be replayed until the key is rotated.
Limiting it to the landing paths narrows where a leaked token works. It does not
make the token safe. Replacing it with a signed, expiring, single-use credential
needs a matching change on the Moodle side and is tracked separately (PR #324
review, F-001).
