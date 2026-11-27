{{-- params: claims, actions? (view name rendered per row with $claim), empty? --}}
@php($fmt = \Modules\GuestTaClaim\Support\Format::class)
@if ($claims->isEmpty())
    <div class="gtc-empty">{{ $empty ?? 'No claims here yet.' }}</div>
@else
    <div class="gtc-table-wrap">
        <table class="table gtc-table table-hover">
            <thead><tr>
                <th>Claim no.</th><th>Guest faculty</th><th>Course</th><th>Period</th>
                <th class="text-end">Sessions</th><th class="text-end">Amount</th><th>Status</th><th>Last update</th>
                @isset($actions)<th class="no-print"></th>@endisset
            </tr></thead>
            <tbody>
            @foreach ($claims as $claim)
                <tr class="gtc-click" data-href="{{ route('guest-ta-claim.claims.show', $claim) }}">
                    <td><a href="{{ route('guest-ta-claim.claims.show', $claim) }}" class="fw-semibold text-decoration-none">{{ $claim->claim_no }}</a></td>
                    <td>{{ $claim->faculty_name }}</td>
                    <td>{{ $claim->course_name }}</td>
                    <td class="text-nowrap">{{ $fmt::date($claim->period_from) }} to {{ $fmt::date($claim->period_to) }}</td>
                    <td class="text-end">{{ $claim->sessions_taken }}</td>
                    <td class="text-end">{{ $fmt::money($claim->amountPaidOrClaimed()) }}</td>
                    <td>@include('guesttaclaim::partials.status', ['status' => $claim->status])</td>
                    <td class="text-nowrap">{{ $fmt::dateTime($claim->updated_at) }}</td>
                    @isset($actions)
                        <td class="no-print text-end text-nowrap">@include($actions, ['claim' => $claim])</td>
                    @endisset
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @if (method_exists($claims, 'links'))
        <div class="mt-3 no-print">{{ $claims->links('pagination::bootstrap-5') }}</div>
    @endif
@endif
