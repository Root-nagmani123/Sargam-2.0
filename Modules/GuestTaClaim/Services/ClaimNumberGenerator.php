<?php

namespace Modules\GuestTaClaim\Services;

use Illuminate\Support\Facades\DB;

class ClaimNumberGenerator
{
    /** Must be called inside a transaction. Produces GTA/2026/0001 style numbers. */
    public function next(?int $year = null): string
    {
        $year ??= (int) now()->format('Y');

        DB::table('gtc_claim_sequences')->insertOrIgnore(['year' => $year, 'last_number' => 0]);
        $row = DB::table('gtc_claim_sequences')->where('year', $year)->lockForUpdate()->first();
        $next = (int) $row->last_number + 1;
        DB::table('gtc_claim_sequences')->where('year', $year)->update(['last_number' => $next]);

        return sprintf('%s/%d/%04d', config('guest-ta-claim.claim_number_prefix', 'GTA'), $year, $next);
    }
}
