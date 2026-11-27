# Dynamic user variables in sidebar menu Urls

A sidebar menu Url can now carry `{username}`, `{email}` or `{phone}`. The menu
row stores the Url as typed (the template); the signed-in user's own value is
put in each time the link is built, so every user gets their own Url. This is
for the old SARGAM links, which identify the user in the query string:

```
stored:   https://sargam.lbsnaa.gov.in/LBSNA/gyanListAllTourReport?uname={username}
user A:   https://sargam.lbsnaa.gov.in/LBSNA/gyanListAllTourReport?uname=b.rajender
```

No migration, no schema change, no new package, no frontend build. The release
is PHP and Blade only.

## Variables

| Token | Form label | Column (`user_credentials`) |
| --- | --- | --- |
| `{username}` | Username | `user_name` |
| `{email}` | Email | `email_id` |
| `{phone}` | Phone Number | `mobile_no` |

`user_credentials` is the table behind `App\Models\User`. Note the email column
is `email_id`; the model's `$fillable` lists an `email` attribute that has no
column, so do not map to that.

The Add / Edit Menu form (one modal, `/sidebar/menus`) has an **Insert
variable** picker under the Url field. It always shows the variable currently in
the Url (`None` when there is none), so Edit opens with the saved variable
selected. Choosing a variable inserts it at the cursor; choosing a different one
replaces the existing one in place; choosing `None` removes it. Tokens can also
be typed, and the picker follows what is typed.

### Supported formats

A Url using variables is accepted only when all of these hold. They are checked
on save (`MenuRequest`) and again whenever the link is built, so a row written
some other way (a migration, a seeder, SQL) cannot bypass them.

- **Exact lowercase tokens.** `{Username}`, `{ username }`, `{password}` and any
  other `{word}` are refused on save with "Unknown variable …".
- **Query string only.** Tokens must come after `?` and before any `#`.
  `https://sargam.lbsnaa.gov.in/{username}/x` is refused. A token in the path
  would break the sidebar's active-menu matching, which compares paths.
- **`https://` on an allowlisted host.** Exact host match, no wildcards, no
  `user:pass@` in the Url. `http://`, relative paths (`admin/x?u={username}`)
  and other hosts are refused.
- **One variable per Url, used once.** `?u={username}&e={email}` and
  `?u={username}&v={username}` are refused on save with "Use only one variable
  per Url …", and a row written outside the form with more than one goes to
  `reason=invalid_route` instead of being resolved. Other, static query
  parameters are fine: `https://sargam.lbsnaa.gov.in/x?a=1&e={email}&b=2`.

Each value is URL-encoded with `rawurlencode` (`@` → `%40`, `+` → `%2B`,
space → `%20`).

### When a value is missing

If the user has no value for a token the Url uses (null, empty or whitespace), or
nobody is signed in, the link goes to the navigation-error page with
`reason=missing_profile`, which says "Missing profile details." (HTTP 422). A Url
is never built with an empty value. In breadcrumbs the menu shows as plain text
instead of a link.

On the local copy of the data (15,296 users) every user has a `user_name`;
1,396 have no `email_id` and 1,100 have no `mobile_no`. Those users will see the
error page on an `{email}` / `{phone}` menu until their profile is filled in.

A Url with a token that fails the host, position or one-variable rules at click
time (only possible for a row written outside the form) goes to
`reason=invalid_route` instead.

### Static Urls

A Url with none of the three tokens takes exactly the code path it took before.
On the local data, no pre-existing menu row carried a token, and none would be
newly refused when edited. Re-check on staging and production with the query
under "Before deploying".

## Allowlist configuration

`config/menu_url_variables.php`, read from the environment:

```
MENU_URL_VARIABLE_HOSTS=sargam.lbsnaa.gov.in
```

- Comma separated, case-insensitive, exact host match:
  `MENU_URL_VARIABLE_HOSTS=sargam.lbsnaa.gov.in,other.lbsnaa.gov.in`
- **Unset:** defaults to `sargam.lbsnaa.gov.in`. Nothing has to be added to
  `.env` for the initial rollout.
- **Set to empty** (`MENU_URL_VARIABLE_HOSTS=`): no host is approved. Variables
  cannot be saved, existing variable menus go to `invalid_route`, and the form's
  help text says so.
- Removing a host later immediately stops user values going to it; menus on
  that host go to `invalid_route` until they are edited.

Adding a host is a decision to send users' usernames, emails and phone numbers
to it. Record who approved it.

## Before deploying

Confirm no existing row already contains a token (expected: no rows). A row
listed here would start resolving per user after the release:

```sql
SELECT id, name, route
FROM menus
WHERE deleted_at IS NULL
  AND (route LIKE '%{username}%' OR route LIKE '%{email}%' OR route LIKE '%{phone}%');
```

## Artisan commands

Run on each environment after the code is deployed:

```sh
php artisan config:cache   # or config:clear where config is not cached
php artisan view:clear
```

`config:cache` / `config:clear` is **required** where the config is cached:
otherwise `config/menu_url_variables.php` is not read, the allowlist is empty,
and every variable menu goes to the error page and cannot be saved.

No `migrate` and no queue restart are needed. The sidebar structure cache
(`fc_sidebar_structure`, 600 s) needs no clearing: no existing row changes, and
saving a menu in the form already clears it, so a new variable menu shows at once.

## Staging smoke test

Record the date, the environment and who ran each step.

1. **Form.** Open `/sidebar/menus` as Super Admin, click Add Menu. The Url field
   has an "Insert variable" picker with Username, Email and Phone Number, and the
   help text names `sargam.lbsnaa.gov.in`.
2. **Add.** Type
   `https://sargam.lbsnaa.gov.in/LBSNA/gyanListAllTourReport?uname=` and choose
   Username in the picker. The Url ends `?uname={username}` and the picker keeps
   showing `Username — {username}`. Now choose Email: the Url ends
   `?uname={email}` (replaced, not appended) and the picker shows Email. Choose
   Username again and save. The grid shows the Url with `{username}` (the
   template, not a value).
3. **Edit pre-selection.** Open Edit on that menu: the picker already shows
   `Username — {username}`. Open Edit on any static menu: it shows `None`.
4. **Per user.** Sign in as two different users who can see that menu. Hover
   or click it: each gets `?uname=` followed by their own `user_name`.
5. **Encoding.** Edit the menu, choose Email (the Url becomes `?uname={email}`),
   and save. Reopen Edit: the picker shows Email. Open the menu as a user with an
   email: the `@` appears as `%40`.
6. **Missing value.** Edit the menu to Phone Number and save. Sign in as a user
   with no `mobile_no` and open the menu: the page says "Missing profile
   details." and the address bar shows `reason=missing_profile`, not a Url with
   an empty `uname=`.
7. **Refused on save.** Type each of these into the Url field (on Add and on
   Edit); each is refused with a message under the field and nothing is saved:
   - `https://sargam.lbsnaa.gov.in/x?u={username}&e={email}` (more than one variable)
   - `https://sargam.lbsnaa.gov.in/x?u={password}` (unknown variable)
   - `https://example.com/x?u={username}` (host not approved)
   - `http://sargam.lbsnaa.gov.in/x?u={username}` (not https)
   - `https://sargam.lbsnaa.gov.in/{username}` (not in the query string)
8. **Static menus.** Click a few existing menus (an internal path, an external
   link, an attachment-only menu, a parent with sub-menus). They behave as before.
9. **Container.** Tick "This menu only holds sub-menus": the Url field and the
   variable picker are both disabled, and the picker shows `None`.
10. **Clean up.** Delete the test menu.

## Rollback

1. **Before reverting the code**, find every menu that uses a token (the query
   under "Before deploying") and edit each to a static Url or deactivate it.
   After a revert, the old code treats `{username}` as ordinary text: the link
   would go to the external host with the literal `{username}` in it. No user
   data is sent, but the link is broken.
2. Revert the release commit and deploy.
3. Run `php artisan config:cache` (or `config:clear`) and `php artisan view:clear`.

There is no data or schema to roll back. Menu rows keep whatever Url was
last saved.

## Security considerations

- **Personal data leaves the application.** A variable menu sends the user's
  username, email or phone number to another server in a Url. Query strings are
  kept in that server's access logs, the user's browser history, and any proxy
  in between. That is why the allowlist exists and is exact-match only; keep it
  to hosts the Academy operates.
- **Allowlist enforced twice.** On save and on every link build, so a row
  written outside the form cannot send data to an unapproved host.
- **https only**, so the values are not sent in clear text; Urls carrying
  `user:pass@` are refused.
- **Who can create them.** Menu create / update are already Super Admin only
  (`EnsureRoleAdmin` on the `sidebar.menus` write routes); this release does not
  change that.
- **Encoding.** Values are `rawurlencode`d, so a value cannot add query
  parameters or change the path, and links are HTML-escaped where they are
  output.
- **No resolved Url is stored or shared.** The database row holds the template.
  The shared caches (`fc_sidebar_structure`, `fc_sidebar_map:*`,
  `sidebar_nav_route_index`) hold raw menu rows and the parsed template; the
  per-user Url is built on every request. `MenuUrlVariablesTest` checks the
  structure cache holds the template and no user's value.
- **The value is not verified by the receiving site.** Anyone can type
  `?uname=someone.else` into the old SARGAM Url by hand. This feature only
  pre-fills the link; whether the old SARGAM page trusts `uname` without its own
  login check is that system's concern, and its owner should confirm it.

## Files

- `app/Services/SidebarMenu/MenuUrlVariables.php` (new): tokens, column map,
  validation, resolution
- `config/menu_url_variables.php` (new): allowlist
- `app/Services/SidebarMenu/MenuRouteMatcher.php`, `SidebarNavResolver.php`,
  `MenuService.php`: the three places a menu Url becomes a link
- `app/Http/Requests/SidebarMenu/MenuRequest.php`: save-time validation
- `app/Http/Controllers/Admin/NavigationErrorController.php`,
  `resources/views/errors/configured-path.blade.php`: `missing_profile` reason
- `resources/views/SidebarMenu/menus/index.blade.php`: Insert variable picker
- `tests/Feature/MenuUrlVariablesTest.php` (new): 16 tests
