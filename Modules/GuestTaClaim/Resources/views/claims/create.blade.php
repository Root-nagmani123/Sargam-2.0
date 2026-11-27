@extends('admin.layouts.master')
@section('title', 'New claim')
@section('page_title', 'New Guest Faculty TA Claim')
@section('page_subtitle', 'Choose the course and the dates the guest faculty took sessions.')
@section('content')
@include('GuestTaClaim::partials.style')
@php($fmt = \Modules\GuestTaClaim\Support\Format::class)
@php($rates = config('guest-ta-claim.honorarium_rates'))
<div class="container-fluid profile-page">
  <x-breadcrum
      title="New claim"
      :items="[
          'Home',
          ['label' => 'Guest TA Claim', 'url' => route('protocol.dashboard')],
          'New Request',
        ]"
  />
<div class="gtc-card">
    <h2>Find the timetable</h2>
    <p class="gtc-sub">Choose the course and the dates the guest faculty took sessions.</p>
    <form method="GET" action="{{ route('guest-ta-claim.claims.create') }}" class="row g-3 align-items-end mt-1">
        <div class="col-lg-5 col-md-6">
            <label class="form-label req" for="course_id">Course</label>
            <select class="form-select" id="course_id" name="course_id" required>
                <option value="">Select course</option>
                @foreach ($courses as $c)
                    <option value="{{ $c['id'] }}" {{ ((string) request('course_id') === (string) $c['id']) ? 'selected' : '' }}>{{ $c['name'] }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-2 col-md-3 col-6">
            <label class="form-label req" for="from">From date</label>
            <input type="date" class="form-control" id="from" name="from" value="{{ request('from') }}" required>
        </div>
        <div class="col-lg-2 col-md-3 col-6">
            <label class="form-label req" for="to">To date</label>
            <input type="date" class="form-control" id="to" name="to" value="{{ request('to') }}" required>
        </div>
        <div class="col-lg-3"><button class="btn btn-primary"><i class="bi bi-search"></i> Find timetable</button></div>
    </form>
</div>

@if ($search)
<div class="gtc-card">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h2>Guest faculty sessions</h2>
            <p class="gtc-sub">{{ $search['course']['name'] }}, {{ $fmt::date($search['from']) }} to {{ $fmt::date($search['to']) }}.
                {{ $search['count'] }} sessions across {{ $search['groups']->count() }} guest faculty.</p>
        </div>
        <div class="d-flex gap-2 no-print">
            @include('guesttaclaim::partials.tools', ['type' => 'timetable', 'query' => ['course_id' => $search['course']['id'], 'from' => $search['from']->format('Y-m-d'), 'to' => $search['to']->format('Y-m-d')]])
        </div>
    </div>

    @forelse ($search['groups'] as $g)
        @php($f = $g['faculty'])
        <form method="POST" action="{{ route('guest-ta-claim.claims.prepare') }}" class="gtc-fac"
            data-session-confirmation="prepare" data-faculty-name="{{ $f['name'] }}" data-session-count="{{ $g['sessions']->count() }}">
            @csrf
            <input type="hidden" name="course_id" value="{{ $search['course']['id'] }}">
            <input type="hidden" name="faculty_id" value="{{ $f['id'] }}">
            <input type="hidden" name="from" value="{{ $search['from']->format('Y-m-d') }}">
            <input type="hidden" name="to" value="{{ $search['to']->format('Y-m-d') }}">
            <input type="hidden" name="sessions_confirmed" value="{{ $g['sessions_confirmed'] ? '1' : '0' }}">
            <div class="gtc-fac-head">
                <span class="gtc-avatar">{{ $fmt::initials($f['name']) }}</span>
                <div class="flex-grow-1"><b>{{ $f['name'] }}</b><div class="gtc-sub">{{ collect([$f['designation'] ?? null, $f['organisation'] ?? null])->filter()->join(', ') }}</div></div>
                @if (! empty($f['category']))<span class="gtc-chip t-blue">{{ $f['category'] }}</span>@endif
                <span class="gtc-sub">{{ $g['sessions']->count() }} sessions</span>
            </div>
            @include('guesttaclaim::partials.sessions-table', [
                'sessions' => $g['sessions']->all(),
                'editable' => Gate::allows('gtc.claim.edit'),
                'prefix' => 'sessions',
            ])
            <div class="gtc-fac-foot no-print">
                <span class="gtc-sub">Honorarium at {{ $fmt::money($rates[$f['category'] ?? ''] ?? reset($rates)) }} per session</span>
                @if ($g['existing'])
                    <a class="btn btn-outline-secondary" href="{{ route('guest-ta-claim.claims.show', $g['existing']->id) }}">Claim {{ $g['existing']->claim_no }} already exists</a>
                @else
                    <button type="button" class="btn btn-primary" data-gtc-open-session-confirmation><i class="bi bi-file-earmark-text"></i> Fill claim form</button>
                @endif
            </div>
        </form>
    @empty
        <div class="gtc-empty">No guest faculty sessions in these dates. Try a wider date range.</div>
    @endforelse
</div>
@endif
@include('guesttaclaim::partials.session-modal')
@include('guesttaclaim::partials.session-confirmation-modal')
@endsection

@push('scripts')
    <script>{!! \Modules\GuestTaClaim\Support\Format::js() !!}</script>
@endpush
