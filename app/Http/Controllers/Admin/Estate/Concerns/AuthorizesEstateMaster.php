<?php

namespace App\Http\Controllers\Admin\Estate\Concerns;

/**
 * Server-side gate for the Estate Master CRUD controllers.
 *
 * The estate route group (routes/web.php) carries only the 'auth' middleware, and these
 * controllers had no check of their own — the screens were protected purely by the sidebar
 * hiding their links, which does nothing for a direct request. Any authenticated user could
 * therefore POST/PUT/DELETE estate master rows.
 *
 * Registering the check as constructor middleware (rather than a call at the top of each
 * action) means a newly added action is covered automatically and cannot be forgotten.
 *
 * @see isEstateMasterAuthority() in app/helpers.php for which roles are allowed and why.
 */
trait AuthorizesEstateMaster
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless(
                isEstateMasterAuthority(),
                403,
                'You do not have permission to access this estate section.'
            );

            return $next($request);
        });
    }
}
