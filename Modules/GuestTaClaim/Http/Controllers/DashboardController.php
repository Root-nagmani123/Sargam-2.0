<?php

namespace Modules\GuestTaClaim\Http\Controllers;

use Modules\GuestTaClaim\Enums\ClaimStatus;
use Modules\GuestTaClaim\Models\Claim;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $user = auth()->user();
        $base = Claim::visibleTo($user);

        $counts = (clone $base)->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');
        $paidTotal = (float) (clone $base)->where('status', ClaimStatus::Paid)->sum('paid_amount');
        $recent = (clone $base)->latest('updated_at')->limit(8)->get();

        return view('guesttaclaim::dashboard', compact('counts', 'paidTotal', 'recent'));
    }
}
