@extends('admin.layouts.master')
@section('title', 'Claim ' . $claim->claim_no)
@section('page_title', '')
@section('page_subtitle', '')
@php
    $fmt = \Modules\GuestTaClaim\Support\Format::class;
    $lastReturn = $claim->histories->where('to_status', \Modules\GuestTaClaim\Enums\ClaimStatus::Returned->value)->last();
@endphp
@include('GuestTaClaim::partials.style')
@section('content')
    <div class="container-fluid profile-page">

    @include('guesttaclaim::partials.page-head', ['title' => 'Claim '.$claim->claim_no, 'crumb' => $claim->claim_no,
        'back' => url()->previous() !== url()->current() ? url()->previous() : route('guest-ta-claim.dashboard'),
        'printBill' => route('guest-ta-claim.claims.bill', $claim)])

    @include('guesttaclaim::partials.flash')

    <div class="gtc-card">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-3">
                <span class="gtc-avatar">{{ $fmt::initials($claim->faculty_name) }}</span>
                <div><h2 class="mb-0">{{ $claim->faculty_name }}</h2><p class="gtc-sub">{{ collect([$claim->faculty_designation, $claim->faculty_organisation])->filter()->join(', ') }}</p></div>
            </div>
            @include('guesttaclaim::partials.status', ['status' => $claim->status])
        </div>

        @include('guesttaclaim::partials.stepper')

        @if ($claim->status === \Modules\GuestTaClaim\Enums\ClaimStatus::Returned && $lastReturn)
            <div class="alert alert-warning mt-3 mb-0"><i class="bi bi-info-circle me-1"></i><b>Returned:</b> {{ $lastReturn->remark }}</div>
        @endif

        <div class="gtc-kv gtc-section">
            <div><span>Course</span><b>{{ $claim->course_name }}</b></div>
            <div><span>Period</span><b>{{ $fmt::date($claim->period_from) }} to {{ $fmt::date($claim->period_to) }}</b></div>
            <div><span>Faculty category</span><b>{{ $claim->faculty_category }}</b></div>
            <div><span>Designation</span><b>{{ $claim->faculty_designation }}</b></div>
            <div><span>Official address</span><b>{{ $claim->faculty_official_address ?: 'Not provided' }}</b></div>
            <div><span>Basic pay</span><b>{{ $claim->basic_pay !== null ? $fmt::money($claim->basic_pay) : 'Not provided' }}</b></div>
            <div><span>Documents</span><b>
                @forelse ($claim->attachments->where('attachment_type', '!=', 'submitted_bill') as $a)<a href="{{ route('guest-ta-claim.attachments.download', $a) }}">{{ $a->original_name }}</a>@if (! $loop->last), @endif
                @empty None @endforelse
            </b></div>
            @if ($claim->remarks)<div><span>Remarks</span><b>{{ $claim->remarks }}</b></div>@endif
        </div>

        <div class="gtc-section">
            <h2>Sessions</h2>
            @include('guesttaclaim::partials.sessions-table', ['sessions' => $claim->sessions->map->toFormArray()->all(), 'editable' => false, 'prefix' => null])
        </div>

        <div class="gtc-section">
            <h2>A. Travelling Allowance</h2>
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead><tr>
                        <th>Departure date &amp; time</th><th>Departure from</th><th>Arrival date &amp; time</th><th>Arrival to</th>
                        <th>Mode</th><th>Distance (km)</th><th>Fare paid</th><th>Fare admissible</th><th>Accommodation</th>
                    </tr></thead>
                    <tbody>
                    @forelse ($claim->travellingAllowances as $allowance)
                        <tr>
                            <td>{{ $fmt::dateTime($allowance->departure_at) }}</td><td>{{ $allowance->departure_from }}</td>
                            <td>{{ $fmt::dateTime($allowance->arrival_at) }}</td><td>{{ $allowance->arrival_to }}</td>
                            <td>{{ $allowance->travel_mode }}</td><td>{{ $allowance->distance_km }}</td>
                            <td>{{ $fmt::money($allowance->fare_paid) }}</td><td>{{ $fmt::money($allowance->fare_admissible) }}</td>
                            <td>{{ $fmt::money($allowance->accommodation) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted">
                            No journey rows recorded{{ (float) $claim->travel_fare > 0 ? ' (legacy admissible fare: '.$fmt::money($claim->travel_fare).')' : '.' }}
                        </td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="gtc-section">
            <h2>Journey declarations</h2>
            <div class="gtc-kv">
                <div><span>Admissible mode/class journey numbers</span><b>{{ $claim->admissible_journey_numbers ?: 'Not provided' }}</b></div>
                <div><span>Higher mode/class journey numbers</span><b>{{ $claim->higher_class_journey_numbers ?: 'Not provided' }}</b></div>
                <div><span>Higher-class reimbursement</span><b>
                    @if ($claim->higher_class_reimbursement_basis === 'admissible_rate') Pay at admissible rate
                    @elseif ($claim->higher_class_reimbursement_basis === 'actual_fare_public_interest') Actual fare (essential/public interest)
                    @else Not specified
                    @endif
                </b></div>
            </div>
        </div>

        <div class="gtc-section">
            <h2>B. Dearness Allowance</h2>
            <div class="gtc-kv">
                <div><span>DA for journey period</span><b>{{ $claim->da_journey_days }} days</b></div>
                <div><span>DA for stay in Academy</span><b>{{ $claim->da_stay_days }} days</b></div>
                <div><span>Rate basis</span><b>{{ $claim->da_rate_basis ? ucfirst($claim->da_rate_basis) : 'Not specified' }}</b></div>
                <div><span>Daily rate / percentage</span><b>{{ $fmt::money($claim->da_rate) }} / {{ $claim->da_percentage }}%</b></div>
                <div><span>Total DA</span><b>{{ $fmt::money($claim->da_amount) }}</b></div>
                <div><span>Grand total of TA &amp; DA</span><b>{{ $fmt::money((float) $claim->travel_fare + (float) $claim->local_conveyance + (float) $claim->da_amount) }}</b></div>
                <div><span>Grand total claimed</span><b>{{ $fmt::money($claim->total_amount) }}</b></div>
            </div>
        </div>

        <div class="gtc-section d-flex gap-4 flex-wrap align-items-start">
            @include('guesttaclaim::partials.calc', ['c' => $claim->toArray() + ['paid_amount' => $claim->paid_amount]])
            <div class="flex-grow-1" style="min-width:260px">
                <h2>History</h2>
                <ul class="gtc-hist">
                    @foreach ($claim->histories->reverse() as $h)
                        <li><span class="when">{{ $fmt::dateTime($h->created_at) }}</span>
                            <div><b>{{ $h->action }}</b> by {{ $h->user_name }}@if ($h->remark)<div class="gtc-sub">{{ $h->remark }}</div>@endif</div></li>
                    @endforeach
                </ul>
            </div>
        </div>

        @if ($claim->status->isEditable())
            @can('gtc.claim.forward')
                <div class="gtc-section no-print">
                    <h2>Send signed bill directly to Accounts</h2>
                    <p class="gtc-sub mb-3">Upload the signed Travelling Allowance Bill PDF to bypass course coordinator approval.</p>
                    <form method="POST" action="{{ route('guest-ta-claim.claims.submit-bill', $claim) }}" enctype="multipart/form-data" class="gtc-claim-upload row g-3 align-items-end">
                        @csrf
                        <div class="col-md-8">
                            <label class="form-label" for="bill_pdf">Signed Travelling Allowance Bill (PDF)</label>
                            <input type="file" class="form-control" id="bill_pdf" name="bill_pdf" accept="application/pdf,.pdf" required>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-success"><i class="bi bi-file-earmark-arrow-up me-1"></i>Upload &amp; send to Accounts</button>
                        </div>
                    </form>
                </div>
            @endcan
        @endif

        <div class="gtc-actions gtc-claim-actions no-print">
            @if ($claim->status === \Modules\GuestTaClaim\Enums\ClaimStatus::Draft)
                @can('gtc.claim.create')
                    <form method="POST" action="{{ route('guest-ta-claim.claims.destroy', $claim) }}" onsubmit="return confirm('Delete this draft?')" class="me-auto">
                        @csrf @method('DELETE')<button class="btn btn-outline-danger"><i class="bi bi-trash"></i> Delete draft</button>
                    </form>
                @endcan
            @endif
            @if ($claim->status->isEditable())
                @can('gtc.claim.create')
                    <a class="btn btn-outline-secondary" href="{{ route('guest-ta-claim.claims.edit', $claim) }}"><i class="bi bi-pencil"></i> Edit claim</a>
                @endcan
                @can('gtc.claim.forward')
                    <form method="POST" action="{{ route('guest-ta-claim.claims.forward', $claim) }}">@csrf
                        <button class="btn btn-primary"><i class="bi bi-send"></i> Forward to course coordinator</button></form>
                @endcan
            @endif
            @if ($canApprove)
                <button type="button" class="btn btn-outline-danger" data-gtc-action="gtcReturnModal" data-url="{{ route('guest-ta-claim.claims.return', $claim) }}" data-claim="{{ $claim->claim_no }}"><i class="bi bi-arrow-counterclockwise"></i> Return</button>
                <button type="button" class="btn btn-success" data-gtc-action="gtcApproveModal" data-url="{{ route('guest-ta-claim.claims.approve', $claim) }}" data-claim="{{ $claim->claim_no }}"><i class="bi bi-check2-circle"></i> Approve</button>
            @endif
            @if ($claim->status === \Modules\GuestTaClaim\Enums\ClaimStatus::PendingAccounts)
                @can('gtc.acc.pay')
                    @include('guesttaclaim::payments._pay-button', ['claim' => $claim, 'size' => ''])
                @endcan
            @endif
            @if ($claim->status === \Modules\GuestTaClaim\Enums\ClaimStatus::Paid && Gate::any(['gtc.claim.report', 'gtc.acc.view', 'gtc.acc.pay']))
                <a class="btn btn-primary" href="{{ route('guest-ta-claim.claims.report', $claim) }}" target="_blank" rel="noopener"><i class="bi bi-file-earmark-text"></i> Generate report</a>
            @endif
        </div>
    </div>
    @php($submittedBill = $claim->attachments->where('attachment_type', 'submitted_bill')->last())
    @if ($submittedBill)
        <div class="gtc-card mt-3">
            <h2>Signed bill submitted to Accounts</h2>
            <p class="gtc-sub">This claim is no longer editable while Accounts reviews it or after it is marked paid.</p>
            <a class="btn btn-outline-primary" href="{{ route('guest-ta-claim.attachments.download', $submittedBill) }}">
                <i class="bi bi-file-earmark-pdf me-1"></i>{{ $submittedBill->original_name }}
            </a>
        </div>
    @endif
    </div>
    @include('guesttaclaim::partials.action-modals')
@endsection

@push('scripts')
    <script>{!! \Modules\GuestTaClaim\Support\Format::js() !!}</script>
@endpush
