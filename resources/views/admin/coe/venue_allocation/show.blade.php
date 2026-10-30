@extends('admin.layouts.master')

@section('title', 'View Seating Plan')

@push('styles')
<link rel="stylesheet"
      href="{{ asset('css/coe-admin.css') }}?v={{ @filemtime(public_path('css/coe-admin.css')) ?: time() }}">
<style>
/* View Seating Plan — read-only (design node 2166-58975). The large hall (.vsp-*) is in
   coe-admin.css, shared with Edit Seating Plan; drawn by public/js/coe-seat-map.js. */
.vsp-legend           { display:flex;flex-wrap:wrap;gap:14px;justify-content:center;font-size:12px;color:#667085;margin-top:18px; }
.vsp-picked           { min-height:22px;text-align:center;font-size:13px;color:#344054;margin-top:12px; }
@media print {
    .vsp-actions, .modern-breadcrumb-back { display:none !important; }
    .vsp-hall { overflow:visible; }
}
</style>
@endpush

@section('setup_content')
<div class="container-fluid coe-page">
    <x-breadcrum title="View Seating Plan" :showBack="true"
                 :items="['Setup', 'COE', 'Examination Management', 'View Seating Plan']" />

    <div class="d-flex flex-wrap justify-content-end gap-2 mb-3 coe-secondary-actions vsp-actions">
        <button type="button" id="vspDownloadBtn" class="btn programme-dt-btn-columns" title="Download as CSV">
            <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
        </button>
        <button type="button" id="vspPrintBtn" class="btn programme-dt-btn-columns" title="Print">
            <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
        </button>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">
            <div class="esp-hall-label" style="font-size:14px">{{ $allocation['room'] }} — Seating Plan</div>
            <div class="esp-hall-sub">
                {{ $allocation['course'] }} ({{ $allocation['batch'] }}) ·
                {{ $allocation['subject'] }} / {{ $allocation['component'] }} ·
                {{ $allocation['category'] }}@if ($allocation['exam_date']) · {{ $allocation['exam_date'] }}@endif ·
                {{ collect([$allocation['building'], $allocation['floor']])->filter()->implode(', ') }} ·
                {{ $allocation['source'] }}@if ($allocation['frozen']) · Frozen @endif
            </div>

            <div class="vsp-hall">
                <div class="vsp-hall-inner">
                    <div class="vsp-entrance">
                        <i class="bi bi-arrow-down" aria-hidden="true"></i>ENTRANCE<i class="bi bi-arrow-down" aria-hidden="true"></i>
                    </div>
                    <div class="esp-blocks-row vsp-map" id="vspSeatMap"></div>
                    <div class="vsp-podium"><i class="bi bi-volume-up-fill" aria-hidden="true"></i>SPEAKER / PODIUM</div>
                </div>
            </div>

            {{-- Click a seat to see who sits there. --}}
            <div class="vsp-picked" id="vspPicked" aria-live="polite"></div>

            <div class="vsp-legend">
                <span><span class="vsp-dot" style="background:#dce8fb"></span>Assigned ({{ $allocation['seats']->count() }})</span>
                <span><span class="vsp-dot" style="background:#eaecf0"></span>Empty ({{ max(0, $allocation['capacity'] - $allocation['seats']->count()) }})</span>
                <span><span class="vsp-dot" style="background:#eaecf0;box-shadow:inset 0 -3px 0 #17a2b8"></span>Laptop desk</span>
                <span><span class="vsp-dot" style="background:#eaecf0;box-shadow:inset 0 -3px 0 #8e44ad"></span>Scriber desk</span>
                <span>Total Seats: <strong>{{ $allocation['capacity'] }}</strong></span>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/coe-seat-map.js') }}?v={{ @filemtime(public_path('js/coe-seat-map.js')) ?: time() }}"></script>
<script>
$(function () {
    'use strict';

    var ROOM = @json($allocation);
    var $map = $('#vspSeatMap');
    var bySeat = CoeSeatMap.render($map, ROOM);

    // Read-only: a click only says who sits at the desk.
    $map.on('click', '.esp-seat.is-assigned', function () {
        var s = bySeat[$(this).attr('data-seat')];
        $map.find('.is-picked').removeClass('is-picked');
        $(this).addClass('is-picked');
        var extra = [s.laptop ? 'Laptop' : null, s.scriber ? 'Scriber' : null].filter(Boolean).join(' · ');
        $('#vspPicked').text('Seat ' + s.label + ' — ' + s.roll + ' · ' + s.name + (extra ? ' · ' + extra : ''));
    });

    /* Download: who sits where, as CSV. */
    $('#vspDownloadBtn').on('click', function () {
        var rows = [['Roll No.', 'Name', 'Seat', 'Block', 'Laptop', 'Scriber']];
        ROOM.seats.forEach(function (s) {
            rows.push([s.roll, s.name, s.label, s.label.charAt(0), s.laptop ? 'Yes' : 'No', s.scriber ? 'Yes' : 'No']);
        });
        var csv = rows.map(function (r) { return r.map(function (v) { return '"' + String(v).replace(/"/g, '""') + '"'; }).join(','); }).join('\r\n');
        var a = document.createElement('a');
        a.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8' }));
        a.download = (ROOM.room + ' seating plan').replace(/[^\w-]+/g, '-').toLowerCase() + '.csv';
        document.body.appendChild(a); a.click();
        setTimeout(function () { URL.revokeObjectURL(a.href); a.remove(); }, 0);
    });
    $('#vspPrintBtn').on('click', function () { window.print(); });
});
</script>
@endpush
