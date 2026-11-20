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
/* Invigilators of this room */
.vsp-inv              { margin-top:22px;border-top:1px solid var(--ds-line);padding-top:16px; }
.vsp-inv__title       { font-weight:600;font-size:15px;color:#004384;margin-bottom:10px; }
.vsp-inv__grid        { display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:12px; }
.vsp-inv__item        { border:1px solid var(--ds-line);border-radius:8px;padding:10px 12px; }
.vsp-inv__role        { font-size:12px;color:#667085; }
.vsp-inv__name        { font-size:14px;color:#101828;font-weight:500;margin-top:2px;overflow-wrap:anywhere; }
.vsp-inv__name.is-empty { color:#98a2b3;font-weight:400; }
/* Plan details */
.vsp-details          { display:grid;grid-template-columns:repeat(auto-fit, minmax(190px, 1fr));gap:10px 16px;
                        border:1px solid var(--ds-line);border-radius:8px;padding:12px 14px;margin-bottom:12px;background:#f9fafb; }
.vsp-details dt       { font-size:12px;color:#667085;font-weight:500;margin:0; }
.vsp-details dd       { font-size:14px;color:#101828;font-weight:500;margin:2px 0 0;overflow-wrap:anywhere; }
.vsp-chip             { display:inline-block;border-radius:10px;padding:1px 9px;font-size:12px;font-weight:500; }
.vsp-chip.is-frozen   { background:#ecfdf3;color:#067647; }
.vsp-chip.is-draft    { background:#fffaeb;color:#b54708; }
.vsp-counts           { display:grid;grid-template-columns:repeat(auto-fit, minmax(120px, 1fr));gap:10px;margin-bottom:6px; }
.vsp-count            { border:1px solid var(--ds-line);border-radius:8px;padding:8px 12px; }
.vsp-count b          { display:block;font-size:20px;color:#004384;line-height:1.2; }
.vsp-count span       { font-size:12px;color:#667085; }
/* Seat details */
.vsp-seats            { margin-top:22px;border-top:1px solid var(--ds-line);padding-top:16px; }
.vsp-seats__head      { display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:10px;margin-bottom:10px; }
.vsp-seats__search    { max-width:280px; }
.vsp-seats__wrap      { border:1px solid var(--ds-line);border-radius:8px;max-height:460px;overflow:auto; }
.vsp-seats table      { width:100%;border-collapse:collapse;font-size:13px; }
.vsp-seats th         { position:sticky;top:0;z-index:1;padding:10px 12px;background:#f9fafb;font-weight:600;color:#101828;
                        border-bottom:1px solid var(--ds-line);text-align:left;white-space:nowrap; }
.vsp-seats td         { padding:9px 12px;border-bottom:1px solid var(--ds-line);color:#344054;vertical-align:middle; }
.vsp-seats tbody tr   { cursor:pointer; }
.vsp-seats tbody tr:hover td { background:#f5f8ff; }
.vsp-seats tbody tr.is-picked td { background:#eef4ff;color:#004384;font-weight:500; }
.vsp-tag              { display:inline-block;border-radius:4px;padding:1px 7px;font-size:11px;font-weight:500;margin-right:3px; }
.vsp-tag.is-laptop    { background:#e6f6f8;color:#0e7490; }
.vsp-tag.is-scriber   { background:#f4ebfa;color:#7e3fa8; }
.vsp-tag.is-moved     { background:#fffaeb;color:#b54708; }
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
        <div class="dropdown">
            <button type="button" class="btn programme-dt-btn-columns dropdown-toggle" id="vspDownloadBtn" data-bs-toggle="dropdown" aria-expanded="false" title="Download">
                <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
            </button>
            {{-- Server export (VenueAllocationController::seatsExport); `download` keeps them out of Edit's leave-page guard. --}}
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="vspDownloadBtn">
                <li><a class="dropdown-item" download href="{{ route('admin.coe.venue-allocation.export', ['id' => $allocation['id'], 'format' => 'excel']) }}">
                    <i class="bi bi-file-earmark-excel me-1" aria-hidden="true"></i> Excel (.xlsx)</a></li>
                <li><a class="dropdown-item" download href="{{ route('admin.coe.venue-allocation.export', ['id' => $allocation['id'], 'format' => 'pdf']) }}">
                    <i class="bi bi-file-earmark-pdf me-1" aria-hidden="true"></i> PDF</a></li>
                <li><a class="dropdown-item" download href="{{ route('admin.coe.venue-allocation.export', ['id' => $allocation['id'], 'format' => 'csv']) }}">
                    <i class="bi bi-filetype-csv me-1" aria-hidden="true"></i> CSV</a></li>
            </ul>
        </div>
        <button type="button" id="vspPrintBtn" class="btn programme-dt-btn-columns" title="Print">
            <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
        </button>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">
@php
    $seated = $allocation['seats']->count();
    $blockNames = ['A' => 'Block A', 'B' => 'Centre', 'C' => 'Block C'];
@endphp
            <div class="esp-hall-label mb-2" style="font-size:16px">{{ $allocation['room'] }} — Seating Plan</div>

            <dl class="vsp-details">
                <div><dt>Examination Drive</dt><dd>{{ $allocation['drive'] ?: '—' }}</dd></div>
                <div><dt>Course</dt><dd>{{ $allocation['course'] }} ({{ $allocation['batch'] }})</dd></div>
                <div><dt>Subject / Component</dt><dd>{{ $allocation['subject'] }} / {{ $allocation['component'] ?: '—' }}</dd></div>
                <div><dt>Exam Date</dt><dd>{{ $allocation['exam_date'] ?: '—' }}</dd></div>
                <div><dt>Room</dt><dd>{{ $allocation['room'] }}</dd></div>
                <div><dt>Building / Floor</dt><dd>{{ collect([$allocation['building'], $allocation['floor']])->filter()->implode(' / ') ?: '—' }}</dd></div>
                <div><dt>Candidates</dt><dd>{{ $allocation['category'] }}</dd></div>
                <div><dt>Status</dt><dd>{{ $allocation['source'] }}
                    <span class="vsp-chip {{ $allocation['frozen'] ? 'is-frozen' : 'is-draft' }}">{{ $allocation['frozen'] ? 'Frozen' : 'Not frozen' }}</span></dd></div>
            </dl>

            <div class="vsp-counts">
                <div class="vsp-count"><b>{{ $allocation['capacity'] }}</b><span>Total Seats</span></div>
                <div class="vsp-count"><b>{{ $seated }}</b><span>OTs Seated</span></div>
                <div class="vsp-count"><b>{{ max(0, $allocation['capacity'] - $seated) }}</b><span>Empty Seats</span></div>
                <div class="vsp-count"><b>{{ $allocation['seats']->where('laptop', true)->count() }}</b><span>Laptop OTs</span></div>
                <div class="vsp-count"><b>{{ $allocation['seats']->where('scriber', true)->count() }}</b><span>Scriber OTs</span></div>
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

            {{-- Click a seat (or a row below) to see who sits there. --}}
            <div class="vsp-picked" id="vspPicked" aria-live="polite">Click a seat, or a row in Seat Details, to find an OT on the map.</div>

            <div class="vsp-legend">
                <span><span class="vsp-dot" style="background:#dce8fb"></span>Assigned ({{ $allocation['seats']->count() }})</span>
                <span><span class="vsp-dot" style="background:#eaecf0"></span>Empty ({{ max(0, $allocation['capacity'] - $allocation['seats']->count()) }})</span>
                <span><span class="vsp-dot" style="background:#eaecf0;box-shadow:inset 0 -3px 0 #17a2b8"></span>Laptop desk</span>
                <span><span class="vsp-dot" style="background:#eaecf0;box-shadow:inset 0 -3px 0 #8e44ad"></span>Scriber desk</span>
                <span>Total Seats: <strong>{{ $allocation['capacity'] }}</strong></span>
            </div>

            {{-- Every OT seated in this room; a row and its desk on the map highlight together. --}}
            <section class="vsp-seats" aria-labelledby="vspSeatsTitle">
                <div class="vsp-seats__head">
                    <div class="vsp-inv__title mb-0" id="vspSeatsTitle">Seat Details <span class="text-muted fw-normal small">({{ $seated }} OT{{ $seated === 1 ? '' : 's' }})</span></div>
                    <input type="search" id="vspSeatSearch" class="form-control form-control-sm vsp-seats__search"
                           placeholder="Search roll no., name or seat" aria-label="Search seat details">
                </div>
                <div class="vsp-seats__wrap">
                    <table>
                        <thead>
                            <tr><th>S.No</th><th>Roll No.</th><th>Name</th><th>Seat</th><th>Block</th><th>Remarks</th></tr>
                        </thead>
                        <tbody id="vspSeatBody">
                            @forelse ($allocation['seats'] as $i => $a)
                                @php $parts = explode('-', $a['label'], 2); @endphp
                                <tr data-seat="{{ $a['seat'] }}">
                                    <td>{{ $i + 1 }}</td>
                                    <td class="fw-semibold">{{ $a['roll'] }}</td>
                                    <td>{{ $a['name'] }}</td>
                                    <td>{{ $parts[1] ?? $a['label'] }}</td>
                                    <td>{{ $blockNames[$parts[0]] ?? $parts[0] }}</td>
                                    <td>
                                        @if ($a['laptop'])<span class="vsp-tag is-laptop">Laptop</span>@endif
                                        @if ($a['scriber'])<span class="vsp-tag is-scriber">Scriber</span>@endif
                                        @if ($a['manual'])<span class="vsp-tag is-moved">Moved</span>@endif
                                        @unless ($a['laptop'] || $a['scriber'] || $a['manual'])<span class="text-muted">—</span>@endunless
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted">Nobody is seated in this room.</td></tr>
                            @endforelse
                            <tr id="vspSeatNone" class="d-none"><td colspan="6" class="text-center text-muted">No OT matches the search.</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            {{-- Who invigilates this room (Add / Edit Invigilators on the listing). --}}
            <section class="vsp-inv" aria-labelledby="vspInvTitle">
                <div class="vsp-inv__title" id="vspInvTitle">Invigilators</div>
                @if (! $allocation['takes_invigilators'])
                    <div class="small text-muted">An Offline Objective paper takes no invigilators.</div>
                @elseif (collect($allocation['invigilators'])->pluck('name')->filter()->isEmpty())
                    <div class="small text-muted">No invigilators added yet — use Add Invigilators on the Venue Allocation list.</div>
                @else
                    <div class="vsp-inv__grid">
                        @foreach ($allocation['invigilators'] as $inv)
                            <div class="vsp-inv__item">
                                <div class="vsp-inv__role">{{ $inv['role'] }}</div>
                                <div class="vsp-inv__name {{ $inv['name'] ? '' : 'is-empty' }}">{{ $inv['name'] ?? 'Not assigned' }}</div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
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

    // Read-only: a desk and its row in Seat Details highlight together.
    function pick(n, from) {
        var s = bySeat[n];
        if (!s) { return; }
        $map.find('.is-picked').removeClass('is-picked');
        var $seat = $map.find('[data-seat="' + n + '"]').addClass('is-picked');
        $('#vspSeatBody tr.is-picked').removeClass('is-picked');
        var $row = $('#vspSeatBody tr[data-seat="' + n + '"]').addClass('is-picked');
        var extra = [s.laptop ? 'Laptop' : null, s.scriber ? 'Scriber' : null].filter(Boolean).join(' · ');
        $('#vspPicked').text('Seat ' + s.label + ' — ' + s.roll + ' · ' + s.name + (extra ? ' · ' + extra : ''));
        // Bring the other half into view.
        if (from === 'map' && $row.length) { $row[0].scrollIntoView({ block: 'nearest' }); }
        if (from === 'table' && $seat.length) { $seat[0].scrollIntoView({ block: 'nearest', inline: 'center' }); }
    }
    $map.on('click', '.esp-seat.is-assigned', function () { pick($(this).attr('data-seat'), 'map'); });
    $('#vspSeatBody').on('click', 'tr[data-seat]', function () { pick($(this).attr('data-seat'), 'table'); });

    $('#vspSeatSearch').on('input', function () {
        var q = this.value.trim().toLowerCase(), shown = 0;
        $('#vspSeatBody tr[data-seat]').each(function () {
            var hit = !q || $(this).text().toLowerCase().indexOf(q) !== -1;
            $(this).toggle(hit);
            if (hit) { shown++; }
        });
        $('#vspSeatNone').toggleClass('d-none', shown > 0 || !$('#vspSeatBody tr[data-seat]').length);
    });

    // Print = the branded Seating Plan sheet (venue_allocation/print), not this screen.
    $('#vspPrintBtn').on('click', function () {
        window.open(@json(route('admin.coe.venue-allocation.print', $allocation['id'])), '_blank');
    });
});
</script>
@endpush
