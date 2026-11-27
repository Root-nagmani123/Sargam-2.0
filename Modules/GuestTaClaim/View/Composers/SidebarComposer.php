<?php

namespace Modules\GuestTaClaim\View\Composers;

use Illuminate\View\View;
use Modules\GuestTaClaim\Enums\ClaimStatus;
use Modules\GuestTaClaim\Enums\Permission as P;
use Modules\GuestTaClaim\Models\Claim;
use Modules\GuestTaClaim\Services\PermissionService;

class SidebarComposer
{
    public function __construct(protected PermissionService $perms) {}

    public function compose(View $view): void
    {
        $u = auth()->user();
        $can = fn (array $p) => $this->perms->hasAny($u, $p);

        $items = [
            ['route' => 'guest-ta-claim.dashboard', 'match' => 'guest-ta-claim.dashboard', 'label' => 'Dashboard', 'icon' => 'bi-grid', 'show' => true],
            ['route' => 'guest-ta-claim.claims.create', 'match' => 'guest-ta-claim.claims.create|guest-ta-claim.claims.form', 'label' => 'New claim', 'icon' => 'bi-file-earmark-plus', 'show' => $can([P::CreateClaim])],
            ['route' => 'guest-ta-claim.claims.index', 'match' => 'guest-ta-claim.claims.index|guest-ta-claim.claims.show|guest-ta-claim.claims.edit', 'label' => 'Claim requests', 'icon' => 'bi-list-ul', 'show' => $can([P::CreateClaim, P::Forward, P::GenerateReport])],
            ['route' => 'guest-ta-claim.approvals.index', 'match' => 'guest-ta-claim.approvals.*', 'label' => 'Approvals', 'icon' => 'bi-check2-circle', 'show' => $can([P::Approve]),
                'count' => $can([P::Approve]) ? Claim::where('status', ClaimStatus::PendingCc)->forCoordinator($u)->count() : 0],
            ['route' => 'guest-ta-claim.payments.index', 'match' => 'guest-ta-claim.payments.*', 'label' => 'Payments', 'icon' => 'bi-wallet2', 'show' => $can([P::ViewPayments, P::MarkPaid]),
                'count' => $can([P::MarkPaid]) ? Claim::where('status', ClaimStatus::PendingAccounts)->count() : 0],
            ['route' => 'guest-ta-claim.reports.index', 'match' => 'guest-ta-claim.reports.*', 'label' => 'Reports', 'icon' => 'bi-bar-chart', 'show' => $can([P::GenerateReport, P::ViewPayments, P::MarkPaid])],
            ['route' => 'guest-ta-claim.permissions.index', 'match' => 'guest-ta-claim.permissions.*', 'label' => 'Assign permission', 'icon' => 'bi-key', 'show' => $can([P::ManagePermissions])],
        ];

        $view->with('gtcMenu', array_values(array_filter($items, fn ($i) => $i['show'])));
    }
}
