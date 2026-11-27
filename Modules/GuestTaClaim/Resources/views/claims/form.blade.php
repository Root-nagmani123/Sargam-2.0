@extends('admin.layouts.master')
@section('title', $claim ? 'Edit claim' : 'Claim form')
@section('page_title', $claim ? 'Edit claim' : 'Claim form')
@section('page_subtitle', $claim ? 'Edit claim' : 'Claim form')
@include('GuestTaClaim::partials.style')
@section('content')
@php
    $fmt = \Modules\GuestTaClaim\Support\Format::class;
    $rates = config('guest-ta-claim.honorarium_rates');
    $v = fn ($k) => old($k, $values[$k] ?? '');
    $rows = old('sessions', $sessions);
    $travelRows = old('travelling_allowances', $travelAllowances ?? []);
    $travelRows = array_filter(is_array($travelRows) ? $travelRows : [], 'is_array');
    $nextTravelIndex = $travelRows ? max(array_map('intval', array_keys($travelRows))) + 1 : 1;
    $hasTravelData = collect($travelRows)->contains(fn ($row) => collect($row)->contains(fn ($value) => $value !== null && $value !== '' && $value !== 0 && $value !== '0'));
    $legacyFare = $claim && $claim->travellingAllowances->isEmpty() ? (float) $claim->travel_fare : 0;
    $legacyAccommodation = $claim && $claim->travellingAllowances->isEmpty() ? (float) $claim->local_conveyance : 0;
    $travelFare = $hasTravelData ? (float) collect($travelRows)->sum(fn ($row) => (float) ($row['fare_admissible'] ?? 0)) : $legacyFare;
    $accommodation = $hasTravelData ? (float) collect($travelRows)->sum(fn ($row) => (float) ($row['accommodation'] ?? 0)) : $legacyAccommodation;
    $daDays = (int) $v('da_journey_days') + (int) $v('da_stay_days');
    $taken = collect($rows)->where('status', 'taken')->count();
    $calc = [
        'sessions_taken' => $taken, 'honorarium_rate' => $v('honorarium_rate'), 'honorarium_amount' => $taken * (float) $v('honorarium_rate'),
        'travel_fare' => $travelFare, 'local_conveyance' => $accommodation, 'da_days' => $daDays, 'da_rate' => $v('da_rate'),
        'da_percentage' => $v('da_percentage') ?: 100,
        'da_amount' => $daDays * (float) $v('da_rate') * ((int) ($v('da_percentage') ?: 100) / 100),
    ];
    $calc['total_amount'] = $calc['honorarium_amount'] + $travelFare + $accommodation + $calc['da_amount'];
    $back = $claim ? route('guest-ta-claim.claims.show', $claim) : route('guest-ta-claim.claims.create', ['course_id' => $course['id'], 'from' => $prepare['period_from'], 'to' => $prepare['period_to']]);
@endphp
<div class="container-fluid profile-page">
    @include('guesttaclaim::partials.page-head', ['title' => $claim ? 'Edit claim '.$claim->claim_no : 'Claim form', 'crumb' => 'Claim form', 'back' => $back])
@include('guesttaclaim::partials.flash')

    <form method="POST" id="gtcClaimForm" enctype="multipart/form-data"
        action="{{ $claim ? route('guest-ta-claim.claims.update', $claim) : route('guest-ta-claim.claims.store') }}" class="gtc-card">
        @csrf
        @if ($claim) @method('PUT') @endif
        <input type="hidden" name="sessions_confirmed" value="{{ $sessionsConfirmed ? '1' : '0' }}">
        @unless ($claim)
            <input type="hidden" name="course_id" value="{{ $course['id'] }}">
            <input type="hidden" name="faculty_id" value="{{ $faculty['id'] }}">
            <input type="hidden" name="period_from" value="{{ $prepare['period_from'] }}">
            <input type="hidden" name="period_to" value="{{ $prepare['period_to'] }}">
        @endunless

        <div class="d-flex align-items-center gap-3">
            <span class="gtc-avatar">{{ $fmt::initials($faculty['name']) }}</span>
            <div><h2 class="mb-0">{{ $faculty['name'] }}</h2><p class="gtc-sub">{{ collect([$faculty['designation'] ?? null, $faculty['organisation'] ?? null])->filter()->join(', ') }}</p></div>
        </div>
        <div class="gtc-kv gtc-section">
            <div><span>Course</span><b>{{ $course['name'] }}</b></div>
            <div><span>Period</span><b>{{ $fmt::date($prepare['period_from']) }} to {{ $fmt::date($prepare['period_to']) }}</b></div>
            <div><span>Home station</span><b>{{ $faculty['city'] ?? '' }}</b></div>
        </div>

        <div class="gtc-section">
            <h2>Sessions</h2>
            <p class="gtc-sub">Cancelled sessions are not counted for honorarium.</p>
            @include('guesttaclaim::partials.sessions-table', ['sessions' => $rows, 'editable' => Gate::allows('gtc.claim.edit'), 'prefix' => 'sessions'])
        </div>

        <div class="gtc-section">
            <h2>Guest speaker details and allowances</h2>
            <div class="row g-3 mt-1">
                <div class="col-md-6"><span class="form-label d-block">1. Name of the Guest Speaker</span><strong>{{ $faculty['name'] }}</strong></div>
                <div class="col-md-6"><span class="form-label d-block">2. Designation</span><strong>{{ $faculty['designation'] ?? '' }}</strong></div>
                <div class="col-md-6">
                    <label class="form-label" for="faculty_official_address">3. Official Address</label>
                    <textarea class="form-control" id="faculty_official_address" name="faculty_official_address" rows="2" maxlength="2000">{{ $v('faculty_official_address') }}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="basic_pay">4. Basic Pay (if in government service) (₹)</label>
                    <input type="number" min="0" step="0.01" class="form-control" id="basic_pay" name="basic_pay" value="{{ $v('basic_pay') }}">
                </div>
                <div class="col-md-6"><span class="form-label d-block">5. Course/Workshop/Seminar for which invited</span><strong>{{ $course['name'] }}</strong></div>
            </div>
            <div class="row g-3 mt-1">
                <div class="col-md-3 col-sm-6">
                    <label class="form-label req" for="faculty_category">Faculty category</label>
                    <select class="form-select" id="faculty_category" name="faculty_category" required>
                        @foreach ($rates as $cat => $rate)
                            <option value="{{ $cat }}" data-rate="{{ $rate }}" {{ ($v('faculty_category') === $cat) ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 col-sm-6"><label class="form-label req" for="honorarium_rate">Honorarium per session (₹)</label>
                    <input type="number" min="0" step="0.01" class="form-control" id="honorarium_rate" name="honorarium_rate" value="{{ $v('honorarium_rate') }}" required></div>
            </div>
            <div class="gtc-section">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div><h3 class="h5 mb-1">A. Travelling Allowance</h3><p class="gtc-sub mb-0">Enter each journey in its own section below.</p></div>
                    <button type="button" class="btn btn-outline-primary btn-sm" data-gtc-add-travel-row>Add journey</button>
                </div>
                <div id="gtcTravelRows" class="gtc-travel-list mt-3" data-next-index="{{ $nextTravelIndex }}">
                    @forelse ($travelRows as $i => $travelRow)
                        @include('guesttaclaim::claims._travelling-allowance-row', ['index' => $i, 'row' => $travelRow])
                    @empty
                        @include('guesttaclaim::claims._travelling-allowance-row', ['index' => 0, 'row' => []])
                    @endforelse
                </div>
                <template id="gtcTravelRowTemplate">
                    @include('guesttaclaim::claims._travelling-allowance-row', ['index' => '__INDEX__', 'row' => []])
                </template>
            </div>
            <div class="row g-3 mt-1">
                <div class="col-12"><h3 class="h5 mb-0">Journey declarations</h3></div>
                <div class="col-md-4"><label class="form-label" for="admissible_journey_numbers">Journey serial numbers performed by admissible mode/class</label>
                    <input class="form-control" id="admissible_journey_numbers" name="admissible_journey_numbers" maxlength="500" value="{{ $v('admissible_journey_numbers') }}"></div>
                <div class="col-md-4"><label class="form-label" for="higher_class_journey_numbers">Journey serial numbers performed by a higher mode/class</label>
                    <input class="form-control" id="higher_class_journey_numbers" name="higher_class_journey_numbers" maxlength="500" value="{{ $v('higher_class_journey_numbers') }}"></div>
                <div class="col-md-4"><label class="form-label" for="higher_class_reimbursement_basis">Reimbursement for higher-class journeys</label>
                    <select class="form-select" id="higher_class_reimbursement_basis" name="higher_class_reimbursement_basis">
                        <option value="">Select</option>
                        <option value="admissible_rate" {{ $v('higher_class_reimbursement_basis') === 'admissible_rate' ? 'selected' : '' }}>Pay at admissible rate</option>
                        <option value="actual_fare_public_interest" {{ $v('higher_class_reimbursement_basis') === 'actual_fare_public_interest' ? 'selected' : '' }}>Reimburse actual fare (essential/public interest)</option>
                    </select></div>
            </div>
            <div class="row g-3 mt-1">
                <div class="col-12"><h3 class="h5 mb-0">B. Dearness Allowance</h3></div>
                <div class="col-md-3 col-sm-6"><label class="form-label" for="da_journey_days">DA for journey period (days)</label>
                    <input type="number" min="0" max="{{ config('guest-ta-claim.max_da_days') }}" class="form-control" id="da_journey_days" name="da_journey_days" value="{{ $v('da_journey_days') }}"></div>
                <div class="col-md-3 col-sm-6"><label class="form-label" for="da_stay_days">DA for stay in Academy (days)</label>
                    <input type="number" min="0" max="{{ config('guest-ta-claim.max_da_days') }}" class="form-control" id="da_stay_days" name="da_stay_days" value="{{ $v('da_stay_days') }}"></div>
                <div class="col-md-3 col-sm-6"><label class="form-label" for="da_rate">DA rate per day (₹)</label>
                    <input type="number" min="0" step="0.01" class="form-control" id="da_rate" name="da_rate" value="{{ $v('da_rate') }}"></div>
                <div class="col-md-3 col-sm-6"><label class="form-label" for="da_rate_basis">DA rate basis</label>
                    <select class="form-select" id="da_rate_basis" name="da_rate_basis">
                        <option value="">Select</option>
                        <option value="old" {{ $v('da_rate_basis') === 'old' ? 'selected' : '' }}>Old rate</option>
                        <option value="revised" {{ $v('da_rate_basis') === 'revised' ? 'selected' : '' }}>Revised rate</option>
                    </select></div>
                <div class="col-md-3 col-sm-6"><label class="form-label" for="da_percentage">DA rate percentage</label>
                    <select class="form-select" id="da_percentage" name="da_percentage">
                        <option value="100" {{ (int) $v('da_percentage') === 100 ? 'selected' : '' }}>Full (100%)</option>
                        <option value="70" {{ (int) $v('da_percentage') === 70 ? 'selected' : '' }}>70%</option>
                    </select></div>
            </div>
        </div>

        <div class="gtc-section d-flex gap-4 flex-wrap align-items-start">
            <div class="flex-grow-1" style="min-width:260px">
                <label class="form-label" for="remarks">Remarks for the course coordinator</label>
                <textarea class="form-control" id="remarks" name="remarks" rows="3" maxlength="2000" placeholder="Anything the approver should know">{{ $v('remarks') }}</textarea>
                <label class="form-label mt-3" for="attachments">Other supporting documents (tickets, receipts)</label>
                <input type="file" class="form-control" id="attachments" name="attachments[]" multiple accept=".{{ implode(',.', config('guest-ta-claim.attachments.mimes')) }}">
                <div class="form-text">PDF, JPG or PNG, up to {{ (int) (config('guest-ta-claim.attachments.max_kb') / 1024) }} MB each.</div>
                @if ($claim && $claim->attachments->isNotEmpty())
                    <div class="mt-2 gtc-sub">Already attached:
                        @foreach ($claim->attachments as $a)<a href="{{ route('guest-ta-claim.attachments.download', $a) }}">{{ $a->original_name }}</a>@if (! $loop->last), @endif @endforeach
                    </div>
                @endif
            </div>
            @include('guesttaclaim::partials.calc', ['c' => $calc])
        </div>

        <div class="gtc-actions">
            <a href="{{ $back }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" name="action" value="draft" class="btn btn-outline-primary"><i class="bi bi-save"></i> Save draft</button>
            @can('gtc.claim.forward')
                <button type="submit" name="action" value="forward" class="btn btn-primary"><i class="bi bi-send"></i> Forward to course coordinator</button>
            @else
                <span class="gtc-sub align-self-center">You can save this draft. Someone with forward permission sends it to the course coordinator.</span>
            @endcan
        </div>
    </form>
</div>
@include('guesttaclaim::partials.session-modal')
@include('guesttaclaim::partials.session-confirmation-modal')
@endsection

@push('scripts')
    <script>{!! \Modules\GuestTaClaim\Support\Format::js() !!}</script>
@endpush
