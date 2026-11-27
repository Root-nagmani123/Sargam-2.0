<?php

namespace Modules\GuestTaClaim\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;
use Modules\GuestTaClaim\Enums\Permission;
use Modules\GuestTaClaim\Models\Claim;
use Modules\GuestTaClaim\Services\PermissionService;

abstract class Controller extends BaseController
{
    protected function perms(): PermissionService
    {
        return app(PermissionService::class);
    }

    protected function can(Permission ...$p): bool
    {
        return $this->perms()->hasAny(auth()->user(), $p);
    }

    protected function authorizeView(Claim $claim): void
    {
        abort_unless(Claim::visibleTo(auth()->user())->whereKey($claim->getKey())->exists(), 403);
    }
}
