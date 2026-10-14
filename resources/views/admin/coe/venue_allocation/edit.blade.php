@extends('admin.layouts.master')

@section('title', 'Edit Seating Plan')

@push('styles')
<link rel="stylesheet"
      href="{{ asset('css/coe-admin.css') }}?v={{ @filemtime(public_path('css/coe-admin.css')) ?: time() }}">
<style>
/* ── Page layout ─────────────────────────────────────────────────────── */
.esp-wrap        { display:flex;gap:20px;align-items:flex-start; }
.esp-map-side    { flex:1;min-width:0; }
.esp-panel-side  { width:340px;flex-shrink:0; }
@media(max-width:992px){
    .esp-wrap    { flex-direction:column; }
    .esp-panel-side{ width:100%; }
}
/* ── Legend / toolbar ─────────────────────────────────────────────────── */
.esp-legend      { display:flex;flex-wrap:wrap;align-items:center;gap:14px;font-size:12px;margin-bottom:10px; }
.esp-legend-dot  { width:14px;height:14px;border-radius:3px;display:inline-block;vertical-align:middle;margin-right:4px; }
.esp-leg-avail   { background:#c5dcf5; }
.esp-leg-sel     { background:#004384;color:#fff; }
.esp-leg-swap    { background:#f5a623; }
.esp-leg-empty   { background:#e2e8f0;border:1px solid #cbd5e1; }
.esp-swap-toggle { background:#f5a623;color:#fff;border:none;border-radius:6px;padding:5px 14px;font-size:12px;font-weight:700;cursor:pointer; }
.esp-swap-toggle.is-off{ background:#e2e8f0;color:#475569; }
.esp-verify-btn  { background:#004384;color:#fff;border:none;border-radius:6px;padding:6px 16px;font-size:13px;font-weight:600;cursor:pointer; }
.esp-verify-btn:hover{ background:#003070; }
/* ── Seating map ──────────────────────────────────────────────────────── */
.esp-hall-label  { font-size:13px;font-weight:700;color:var(--ds-ink);margin-bottom:2px; }
.esp-hall-sub    { font-size:11px;color:var(--ds-ink-muted);margin-bottom:12px; }
.esp-entrance    { text-align:center;font-size:11px;font-weight:600;color:var(--ds-ink-muted);letter-spacing:.5px;margin-bottom:6px; }
.esp-entrance i  { display:block;font-size:18px; }
.esp-podium      { text-align:center;font-size:11px;font-weight:600;color:var(--ds-ink-muted);letter-spacing:.5px;margin-top:6px;padding:6px;border:1px dashed var(--ds-line);border-radius:6px; }
.esp-blocks-row  { display:flex;gap:6px;align-items:flex-start; }
.esp-block       { display:flex;flex-direction:column;gap:3px; }
.esp-row         { display:flex;gap:3px; }
.esp-row-label   { width:14px;font-size:10px;font-weight:700;color:var(--ds-ink-muted);line-height:24px;flex-shrink:0;text-align:center; }
.esp-block-label { font-size:10px;font-weight:700;color:var(--ds-ink-muted);text-align:center;margin-bottom:2px;letter-spacing:.5px; }
.esp-sep         { width:8px;flex-shrink:0; }
/* Seats */
.esp-seat        { width:24px;height:24px;border-radius:4px;background:#c5dcf5;border:none;
                   font-size:9px;font-weight:600;color:#1e3a5f;cursor:pointer;display:flex;
                   align-items:center;justify-content:center;line-height:1;padding:0; }
.esp-seat.is-assigned{ background:#004384;color:#fff; }
.esp-seat.is-swap-target{ background:#f5a623;color:#fff; }
.esp-seat.is-empty   { background:#e2e8f0;color:#94a3b8;cursor:default;pointer-events:none; }
.esp-seat:hover:not(.is-empty){ opacity:.8; }
/* ── Swap Operation card ─────────────────────────────────────────────── */
.esp-swap-card   { border:1px solid var(--ds-line);border-radius:10px;padding:12px;margin-bottom:14px;background:var(--ds-surface-2); }
.esp-swap-card-title{ font-size:12px;font-weight:700;color:var(--ds-ink-muted);margin-bottom:10px;text-transform:uppercase;letter-spacing:.5px; }
.esp-swap-slots  { display:flex;align-items:center;gap:8px; }
.esp-swap-slot   { flex:1;border:2px solid var(--ds-brand);border-radius:8px;padding:8px 10px;text-align:center; }
.esp-swap-slot .seat-id  { font-size:16px;font-weight:700;color:var(--ds-brand); }
.esp-swap-slot .seat-roll{ font-size:10px;color:var(--ds-ink-muted); }
.esp-swap-slot-lbl{ font-size:10px;font-weight:600;color:var(--ds-ink-muted);margin-bottom:4px; }
.esp-swap-arrow  { font-size:20px;color:var(--ds-ink-muted); }
.esp-swap-actions{ display:flex;gap:8px;margin-top:10px; }
.esp-confirm-btn { background:#198754;color:#fff;border:none;border-radius:6px;padding:5px 12px;font-size:12px;font-weight:600;cursor:pointer;flex:1; }
.esp-cancel-btn  { background:#e2e8f0;color:#475569;border:none;border-radius:6px;padding:5px 12px;font-size:12px;font-weight:600;cursor:pointer;flex:1; }
/* ── Assignment table ─────────────────────────────────────────────────── */
.esp-assign-table{ width:100%;border-collapse:collapse;font-size:12px; }
.esp-assign-table th{ padding:6px 8px;background:var(--ds-surface-2);font-weight:700;color:var(--ds-ink-muted);border-bottom:1px solid var(--ds-line); }
.esp-assign-table td{ padding:6px 8px;border-bottom:1px solid var(--ds-line);vertical-align:middle; }
.esp-assign-table tr:last-child td{ border-bottom:none; }
.esp-assign-sel  { background:#eff6ff; }
.esp-assign-swap { background:#fff7ed; }
.esp-status-sel  { background:#dbeafe;color:#1e40af;border-radius:12px;padding:1px 8px;font-size:11px;font-weight:600; }
.esp-status-swap { background:#fef3c7;color:#92400e;border-radius:12px;padding:1px 8px;font-size:11px;font-weight:600; }
/* ── Footer stats ─────────────────────────────────────────────────────── */
.esp-stats       { display:flex;flex-wrap:wrap;gap:14px;font-size:12px;color:var(--ds-ink-muted);margin-top:10px; }
.esp-stat-dot    { width:10px;height:10px;border-radius:2px;display:inline-block;margin-right:4px;vertical-align:middle; }
</style>
@endpush

@section('setup_content')
<div class="container-fluid coe-page">

    {{-- Breadcrumb --}}
    <div class="d-flex align-items-center gap-3 mb-1" style="font-size:12px;color:var(--ds-ink-muted)">
        <a href="{{ route('admin.coe.venue-allocation.index') }}"
           class="d-inline-flex align-items-center gap-1 text-decoration-none" style="color:inherit">
            <i class="bi bi-arrow-left" style="font-size:18px;color:var(--ds-ink)"></i>
        </a>
        <span>Setup &rsaquo; COE &rsaquo; Examination Management &rsaquo; Edit Seating Plan</span>
    </div>
    <h4 class="fw-bold mb-3" style="font-size:22px">Edit Seating Plan</h4>

    {{-- Scope tabs + Download/Print --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <nav class="coe-tabs" aria-label="Scope">
            <div class="coe-tab is-active">Active</div>
            <div class="coe-tab">Archived</div>
        </nav>
        <div class="d-flex gap-2 coe-secondary-actions">
            <button type="button" class="btn programme-dt-btn-columns" id="espDownloadBtn">
                <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
            </button>
            <button type="button" class="btn programme-dt-btn-columns" id="espPrintBtn">
                <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
            </button>
        </div>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            {{-- Hall title --}}
            <div class="esp-hall-label">Tagore Hall — Seating Plan Editor</div>
            <div class="esp-hall-sub">{{ $allocation['course'] }} – {{ $allocation['component'] }} (Special/Re-Exam) · System Generated</div>

            {{-- Legend + Swap Mode toggle + Verify --}}
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                <div class="esp-legend">
                    <span><span class="esp-legend-dot esp-leg-avail"></span>Available</span>
                    <span><span class="esp-legend-dot esp-leg-sel"></span>Selected</span>
                    <span><span class="esp-legend-dot esp-leg-swap"></span>Swap Target</span>
                    <span><span class="esp-legend-dot esp-leg-empty"></span>Empty</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" id="espSwapToggle" class="esp-swap-toggle" aria-pressed="true">
                        <i class="bi bi-arrow-left-right me-1" aria-hidden="true"></i>Swap Mode: On
                    </button>
                    <button type="button" id="espVerifyBtn" class="esp-verify-btn">
                        <i class="bi bi-check2-circle me-1" aria-hidden="true"></i>Verify &amp; Save
                    </button>
                </div>
            </div>

            <div class="esp-wrap">
                {{-- Left: seat map --}}
                <div class="esp-map-side">
                    <div class="esp-entrance">
                        <i class="bi bi-arrow-down-circle" aria-hidden="true"></i>ENTRANCE
                    </div>

                    <div class="esp-blocks-row" id="espSeatMap"></div>

                    <div class="esp-podium">🎤 SPEAKER / PODIUM</div>

                    {{-- Stats footer --}}
                    <div class="esp-stats mt-3">
                        <span><span class="esp-stat-dot" style="background:#004384"></span>Total Seats: <strong id="espStatTotal">{{ $allocation['total_seats'] }}</strong></span>
                        <span><span class="esp-stat-dot" style="background:#c5dcf5"></span>Assigned: <strong id="espStatAssigned">{{ $allocation['assigned_seats'] }}</strong></span>
                        <span><span class="esp-stat-dot" style="background:#e2e8f0;border:1px solid #cbd5e1"></span>Empty: <strong id="espStatEmpty">{{ $allocation['empty_seats'] }}</strong></span>
                        <span><span class="esp-stat-dot" style="background:#f5a623"></span>Pending Swaps: <strong id="espStatSwaps">{{ $allocation['pending_swaps'] }}</strong></span>
                    </div>
                </div>

                {{-- Right: swap panel + assignment table --}}
                <div class="esp-panel-side">
                    {{-- Swap Operation card --}}
                    <div class="esp-swap-card" id="espSwapCard">
                        <div class="esp-swap-card-title">Swap Operation</div>
                        <div class="esp-swap-slots">
                            <div>
                                <div class="esp-swap-slot-lbl">Seat A08</div>
                                <div class="esp-swap-slot" id="espSlotFrom">
                                    <div class="seat-id" id="espSlotFromId">Seat A08</div>
                                    <div class="seat-roll" id="espSlotFromRoll">FC101-047</div>
                                </div>
                                <div class="mt-1 text-center" style="font-size:10px;color:#17a2b8;font-weight:600;">Swap</div>
                            </div>
                            <div class="esp-swap-arrow">⇄</div>
                            <div>
                                <div class="esp-swap-slot-lbl">Seat A06</div>
                                <div class="esp-swap-slot" id="espSlotTo" style="border-color:#f5a623">
                                    <div class="seat-id" id="espSlotToId" style="color:#f5a623">Seat A06</div>
                                    <div class="seat-roll" id="espSlotToRoll">FC101-052</div>
                                </div>
                            </div>
                        </div>
                        <div class="esp-swap-actions">
                            <button type="button" class="esp-confirm-btn" id="espConfirmSwap">Confirm Swap</button>
                            <button type="button" class="esp-cancel-btn" id="espCancelSwap">Cancel</button>
                        </div>
                    </div>

                    {{-- Assignment table --}}
                    <table class="esp-assign-table">
                        <thead>
                            <tr>
                                <th>Roll No.</th>
                                <th>Name</th>
                                <th>Seat</th>
                                <th>Block</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="espAssignBody">
                            @foreach ($allocation['seat_assignments'] as $a)
                            @php
                                $isSwap = $a['status'] === 'Swap Target';
                            @endphp
                            <tr class="{{ $isSwap ? 'esp-assign-swap' : 'esp-assign-sel' }}">
                                <td>{{ $a['roll'] }}</td>
                                <td>{{ $a['name'] }}</td>
                                <td>{{ $a['seat'] }}</td>
                                <td>{{ $a['block'] }}</td>
                                <td>
                                    <span class="{{ $isSwap ? 'esp-status-swap' : 'esp-status-sel' }}">
                                        {{ $a['status'] }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    'use strict';

    /* ── Seat map layout ──────────────────────────────────────────────── *
     * Block A: 6 rows (F-A top-to-bottom) × 4 cols
     * Block B (CENTRE): 6 rows × 13 cols
     * Block C: 6 rows × 6 cols
     * Assigned seats hard-coded from sample data; swap seats pre-marked.
     */
    var ROWS   = ['F','E','D','C','B','A'];
    var BLOCKS = [
        { label:'BLOCK A',   cols: 4  },
        { label:'BLOCK B (CENTRE)', cols: 13 },
        { label:'BLOCK C',   cols: 6  },
    ];

    /* Seats that are "assigned" (selected = navy) */
    var assignedSeats = new Set();
    for (var i = 1; i <= 98; i++) { assignedSeats.add(i); } // first 98 seats assigned

    /* Swap pair */
    var swapFrom = 'A08';
    var swapTo   = 'A06';
    var swapMode = true;

    var $map = $('#espSeatMap');
    var seatIndex = 0;

    BLOCKS.forEach(function (blk, bi) {
        var $block = $('<div class="esp-block"></div>');
        $('<div class="esp-block-label"></div>').text(blk.label).appendTo($block);

        ROWS.forEach(function (rowLetter) {
            var $row = $('<div class="esp-row"></div>');
            $('<span class="esp-row-label"></span>').text(rowLetter).appendTo($row);
            for (var c = 1; c <= blk.cols; c++) {
                seatIndex++;
                var seatId = rowLetter + String(c).padStart(2, '0');
                var $seat  = $('<button type="button" class="esp-seat"></button>').text(seatId).attr('data-sid', seatId);

                if (seatId === swapFrom) {
                    $seat.addClass('is-assigned').attr('title', 'FC101-047 – Rajesh Kumar Sharma');
                } else if (seatId === swapTo) {
                    $seat.addClass('is-swap-target').attr('title', 'FC101-052 – Priya Rani Menon');
                } else if (seatIndex <= 98) {
                    $seat.addClass('is-assigned');
                } else if (seatIndex > 105) {
                    $seat.addClass('is-empty');
                }

                $seat.appendTo($row);
            }
            $row.appendTo($block);
        });

        $block.appendTo($map);
        if (bi < BLOCKS.length - 1) {
            $('<div class="esp-sep"></div>').appendTo($map);
        }
    });

    /* ── Swap Mode toggle ─────────────────────────────────────────────── */
    $('#espSwapToggle').on('click', function () {
        swapMode = !swapMode;
        $(this).text(swapMode ? '⇄ Swap Mode: On' : '⇄ Swap Mode: Off')
               .toggleClass('is-off', !swapMode);
        $('#espSwapCard').toggle(swapMode);
    });

    /* ── Seat click (in swap mode) ────────────────────────────────────── */
    $map.on('click', '.esp-seat:not(.is-empty)', function () {
        if (!swapMode) { return; }
        var sid = $(this).attr('data-sid');
        Swal.fire({ icon: 'info', title: 'Seat ' + sid, text: 'Click Confirm Swap to swap with the current target.', timer: 1500, showConfirmButton: false });
    });

    /* ── Confirm Swap ─────────────────────────────────────────────────── */
    $('#espConfirmSwap').on('click', function () {
        // Swap visual classes
        var $from = $map.find('.esp-seat.is-assigned[data-sid="' + swapFrom + '"]');
        var $to   = $map.find('.esp-seat.is-swap-target[data-sid="' + swapTo + '"]');
        $from.removeClass('is-assigned').addClass('is-swap-target');
        $to.removeClass('is-swap-target').addClass('is-assigned');

        // Swap in panel
        var tmp = swapFrom; swapFrom = swapTo; swapTo = tmp;
        $('#espSlotFromId').text('Seat ' + swapFrom);
        $('#espSlotToId').text('Seat ' + swapTo);

        $('#espStatSwaps').text(0);
        Swal.fire({ icon: 'success', title: 'Swapped', text: 'Seat swap confirmed.', timer: 1500, showConfirmButton: false });
    });

    $('#espCancelSwap').on('click', function () {
        $('#espSwapCard').hide();
        swapMode = false;
        $('#espSwapToggle').text('⇄ Swap Mode: Off').addClass('is-off');
    });

    /* ── Verify & Save ────────────────────────────────────────────────── */
    $('#espVerifyBtn').on('click', function () {
        Swal.fire({ icon: 'success', title: 'Saved', text: 'Seating plan verified and saved.' });
    });

    /* ── Download/Print shortcuts ─────────────────────────────────────── */
    $('#espDownloadBtn').on('click', function () {
        Swal.fire({ icon: 'info', title: 'Download', text: 'Downloading seating plan…', timer: 1200, showConfirmButton: false });
    });
    $('#espPrintBtn').on('click', function () { window.print(); });
});
</script>
@endpush
