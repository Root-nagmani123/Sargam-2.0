@extends('admin.layouts.master')

@section('title', 'My Exam Seat')

@push('styles')
<link rel="stylesheet"
      href="{{ asset('css/coe-admin.css') }}?v={{ @filemtime(public_path('css/coe-admin.css')) ?: time() }}">
<style>
/* My Exam Seat (VenueAllocationController::mySeats): an OT's seats on frozen seating plans.
   The hall map is drawn by public/js/coe-seat-map.js with only this OT's desk placed. */
.mes-who           { display:flex;flex-wrap:wrap;gap:6px 24px;font-size:14px;color:#475467;margin-bottom:14px; }
.mes-who strong    { color:#101828; }
.mes-tabs          { display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px; }
.mes-tab           { border:1px solid #d0d5dd;background:#fff;color:#344054;border-radius:18px;padding:5px 14px;font-size:13px;font-weight:500;cursor:pointer; }
.mes-tab.is-active { background:#004384;border-color:#004384;color:#fff; }
.mes-list          { display:grid;grid-template-columns:repeat(auto-fill, minmax(min(100%, 460px), 1fr));gap:14px; }
.mes-card          { border:1px solid var(--ds-line, #eaecf0);border-radius:12px;background:#fff;padding:16px 18px;display:flex;flex-direction:column;gap:12px; }
.mes-card.is-today { border-color:#fdb022;box-shadow:0 0 0 2px #fef0c7; }
.mes-card-head     { display:flex;align-items:flex-start;justify-content:space-between;gap:10px; }
.mes-date          { font-size:15px;font-weight:600;color:#101828; }
.mes-paper         { font-size:17px;font-weight:600;color:#004384;line-height:1.3;margin-top:2px; }
.mes-sub           { font-size:13px;color:#667085;margin-top:2px; }
.mes-when          { border-radius:10px;padding:2px 10px;font-size:12px;font-weight:500;white-space:nowrap; }
.mes-when.is-today     { background:#fef3c7;color:#b54708; }
.mes-when.is-upcoming  { background:#eef4ff;color:#004384; }
.mes-when.is-completed { background:#f2f4f7;color:#667085; }
.mes-body          { display:grid;grid-template-columns:1fr auto;gap:12px;align-items:center; }
.mes-place dt      { font-size:12px;color:#667085;font-weight:500;margin:0; }
.mes-place dd      { font-size:14px;color:#101828;font-weight:500;margin:1px 0 8px; }
.mes-place dd:last-child { margin-bottom:0; }
.mes-seat          { text-align:center;background:#004384;color:#fff;border-radius:10px;padding:12px 18px;min-width:120px; }
.mes-seat small    { display:block;font-size:11px;letter-spacing:.06em;text-transform:uppercase;opacity:.8; }
.mes-seat b        { display:block;font-size:30px;line-height:1.1;margin:2px 0; }
.mes-seat span     { font-size:12px;opacity:.9; }
.mes-tags          { display:flex;flex-wrap:wrap;gap:6px; }
.mes-tag           { border-radius:4px;padding:1px 8px;font-size:12px;font-weight:500; }
.mes-tag.is-laptop { background:#e6f6f8;color:#0e7490; }
.mes-tag.is-scriber{ background:#f4ebfa;color:#7e3fa8; }
.mes-tag.is-mode   { background:#f2f4f7;color:#475467; }
.mes-map-btn       { align-self:flex-start;background:none;border:none;padding:0;color:#004384;font-size:13px;font-weight:500;cursor:pointer; }
.mes-map           { border-top:1px solid var(--ds-line, #eaecf0);padding-top:10px; }
.mes-map .vsp-hall { padding:8px 0 4px; }
.mes-map .vsp-map .esp-seat { cursor:default; }
.mes-map-note      { text-align:center;font-size:12px;color:#667085;margin-top:6px; }
.mes-empty         { text-align:center;padding:40px 16px;color:#667085; }
.mes-empty i       { font-size:32px;color:#98a2b3; }
</style>
@endpush

@section('setup_content')
@php
    $whenLabel = ['today' => 'Today', 'upcoming' => 'Upcoming', 'completed' => 'Completed'];
    $upcoming = $seats->whereIn('when', ['today', 'upcoming'])->count();
@endphp
<div class="container-fluid coe-page">
    <x-breadcrum title="My Exam Seat" :showBack="false"
                 :items="['General', 'Examination Centre', 'My Exam Seat']" />

    <x-session_message />

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">
            <div class="mes-who">
                <span>Name: <strong>{{ $name }}</strong></span>
                @if ($student->generated_OT_code)<span>OT Code: <strong>{{ $student->generated_OT_code }}</strong></span>@endif
                <span>Roll No.: <strong>{{ $student->roll_number ?: 'Not allotted yet' }}</strong></span>
            </div>

            @if ($seats->isEmpty())
                <div class="mes-empty">
                    <i class="bi bi-calendar2-x d-block mb-2" aria-hidden="true"></i>
                    <h6 class="fw-semibold mb-1">No seat allotted yet</h6>
                    <p class="mb-0 small">Your examination seat appears here, and you get a notification, once the Controller of Examinations freezes the seating plan.</p>
                </div>
            @else
                <div class="mes-tabs" role="group" aria-label="Show">
                    <button type="button" class="mes-tab {{ $upcoming ? 'is-active' : '' }}" data-when="upcoming">Upcoming ({{ $upcoming }})</button>
                    <button type="button" class="mes-tab" data-when="completed">Completed ({{ $seats->count() - $upcoming }})</button>
                    <button type="button" class="mes-tab {{ $upcoming ? '' : 'is-active' }}" data-when="all">All ({{ $seats->count() }})</button>
                </div>

                <div class="mes-list" id="mesList">
                    @foreach ($seats as $s)
                        <article class="mes-card {{ $s['when'] === 'today' ? 'is-today' : '' }}"
                                 data-when="{{ $s['when'] === 'completed' ? 'completed' : 'upcoming' }}" data-key="{{ $s['key'] }}">
                            <div class="mes-card-head">
                                <div>
                                    <div class="mes-date"><i class="bi bi-calendar-event me-1" aria-hidden="true"></i>{{ $s['date'] }}</div>
                                    <div class="mes-paper">{{ $s['subject'] }} — {{ $s['component'] }}</div>
                                    <div class="mes-sub">{{ $s['drive'] }} · {{ $s['course'] }}</div>
                                </div>
                                <span class="mes-when is-{{ $s['when'] }}">{{ $whenLabel[$s['when']] }}</span>
                            </div>

                            <div class="mes-body">
                                <dl class="mes-place mb-0">
                                    <dt>Room</dt><dd>{{ $s['room'] }}</dd>
                                    <dt>Building / Floor</dt><dd>{{ $s['building'] }} / {{ $s['floor'] }}</dd>
                                </dl>
                                <div class="mes-seat" aria-label="Your seat">
                                    <small>Your Seat</small>
                                    <b>{{ $s['desk'] }}</b>
                                    <span>{{ $s['block'] }}</span>
                                </div>
                            </div>

                            <div class="mes-tags">
                                @if ($s['mode'] !== '—')<span class="mes-tag is-mode">{{ $s['mode'] }}</span>@endif
                                @if ($s['laptop'])<span class="mes-tag is-laptop"><i class="bi bi-laptop me-1" aria-hidden="true"></i>Laptop desk</span>@endif
                                @if ($s['scriber'])<span class="mes-tag is-scriber">Scriber provided</span>@endif
                            </div>

                            <button type="button" class="mes-map-btn" aria-expanded="false">
                                <i class="bi bi-map me-1" aria-hidden="true"></i><span>Show on hall map</span>
                            </button>
                            <div class="mes-map d-none">
                                <div class="vsp-hall">
                                    <div class="vsp-hall-inner">
                                        <div class="vsp-entrance"><i class="bi bi-arrow-down" aria-hidden="true"></i>ENTRANCE<i class="bi bi-arrow-down" aria-hidden="true"></i></div>
                                        <div class="esp-blocks-row vsp-map"></div>
                                        <div class="vsp-podium"><i class="bi bi-volume-up-fill" aria-hidden="true"></i>SPEAKER / PODIUM</div>
                                    </div>
                                </div>
                                <div class="mes-map-note">Your desk is highlighted. Row A is nearest the podium.</div>
                            </div>
                        </article>
                    @endforeach
                </div>
                <div class="mes-empty d-none" id="mesNone"><p class="mb-0 small">Nothing to show here.</p></div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/coe-seat-map.js') }}?v={{ @filemtime(public_path('js/coe-seat-map.js')) ?: time() }}"></script>
<script>
$(function () {
    'use strict';
    var SEATS = {};
    @json($seats).forEach(function (s) { SEATS[s.key] = s; });

    function show(when) {
        $('.mes-tab').removeClass('is-active').filter('[data-when="' + when + '"]').addClass('is-active');
        var shown = 0;
        $('#mesList .mes-card').each(function () {
            var on = when === 'all' || $(this).attr('data-when') === when;
            $(this).toggleClass('d-none', !on);
            if (on) { shown++; }
        });
        $('#mesNone').toggleClass('d-none', shown > 0);
    }
    $('.mes-tab').on('click', function () { show($(this).attr('data-when')); });
    show($('.mes-tab.is-active').attr('data-when') || 'all');

    // The room's shape and marked desks; only this OT's own desk is placed and highlighted.
    $('#mesList').on('click', '.mes-map-btn', function () {
        var $card = $(this).closest('.mes-card'), $map = $card.find('.mes-map'), open = $map.hasClass('d-none');
        if (open && !$map.data('drawn')) {
            var s = SEATS[$card.attr('data-key')];
            CoeSeatMap.render($map.find('.vsp-map'), {
                capacity: s.capacity, laptop_seats: s.laptop_seats, scriber_seats: s.scriber_seats,
                seats: [{ seat: s.seat, roll: 'You', name: '' }]
            });
            $map.find('[data-seat="' + s.seat + '"]').addClass('is-picked').attr('title', 'Your seat — ' + s.desk);
            $map.data('drawn', true);
        }
        $map.toggleClass('d-none', !open);
        $(this).attr('aria-expanded', open).find('span').text(open ? 'Hide hall map' : 'Show on hall map');
        if (open) {
            var mine = $map.find('.is-picked')[0];
            if (mine) { mine.scrollIntoView({ block: 'nearest', inline: 'center' }); }
        }
    });
});
</script>
@endpush
