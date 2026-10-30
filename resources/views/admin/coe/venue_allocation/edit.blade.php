@extends('admin.layouts.master')

@section('title', 'Edit Seating Plan')

@push('styles')
<link rel="stylesheet"
      href="{{ asset('css/coe-admin.css') }}?v={{ @filemtime(public_path('css/coe-admin.css')) ?: time() }}">
<style>
/* Edit Seating Plan (design node 1514-58696). The large hall (.vsp-*) and seat map
   (.esp-*) are in coe-admin.css, shared with View Seating Plan. */
.esp-head        { display:flex;flex-wrap:wrap;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:8px; }
.esp-tools       { display:flex;flex-wrap:wrap;align-items:center;gap:12px; }
.esp-legend      { display:flex;flex-wrap:wrap;align-items:center;gap:14px;font-size:12px;color:#667085; }
.esp-swap-toggle { display:inline-flex;align-items:center;gap:6px;border:none;border-radius:6px;padding:7px 14px;
                   font-size:13px;font-weight:600;cursor:pointer;background:#fef3c7;color:#b54708; }
.esp-swap-toggle.is-off { background:#f2f4f7;color:#475467; }
.esp-verify-btn  { display:inline-flex;align-items:center;gap:6px;background:#004384;color:#fff;border:none;border-radius:6px;
                   padding:7px 16px;font-size:13px;font-weight:600;cursor:pointer; }
.esp-verify-btn:hover { background:#00356a; }
.esp-wrap        { display:flex;gap:24px;align-items:flex-start; }
.esp-map-side    { flex:1;min-width:0; }
.esp-panel-side  { width:580px;flex-shrink:0; }
@media (max-width:1400px) { .esp-wrap { flex-direction:column; } .esp-panel-side { width:100%; } }
.esp-under-legend{ display:flex;flex-wrap:wrap;gap:14px;justify-content:center;font-size:11px;color:#667085;margin-top:12px; }
/* Swap Operation */
.esp-swap-card   { border:1.5px solid #f5c26b;border-radius:10px;padding:12px 14px;margin-bottom:14px;background:#fffaeb; }
.esp-swap-title  { font-size:14px;font-weight:700;color:#dc6803;margin-bottom:10px; }
.esp-swap-row    { display:flex;flex-wrap:wrap;align-items:center;gap:10px; }
.esp-swap-slot   { width:136px;border-radius:6px;padding:8px 10px;text-align:center; }
.esp-swap-slot.is-from { background:#004384;color:#fff; }
.esp-swap-slot.is-to   { border:1.5px solid #f5c26b;color:#dc6803;background:#fff; }
.esp-swap-slot .seat-id   { font-size:13px;font-weight:500; }
.esp-swap-slot .seat-roll { font-size:11px;opacity:.75; }
.esp-swap-arrow  { display:flex;flex-direction:column;align-items:center;color:#dc6803;font-size:11px; }
.esp-swap-arrow i{ font-size:20px;line-height:1; }
.esp-confirm-btn { background:#f5a623;color:#fff;border:none;border-radius:6px;padding:8px 16px;font-size:13px;font-weight:600;cursor:pointer; }
.esp-confirm-btn:disabled { opacity:.5;cursor:not-allowed; }
.esp-cancel-btn  { background:#f2f4f7;color:#475467;border:none;border-radius:6px;padding:8px 16px;font-size:13px;font-weight:500;cursor:pointer; }
/* Assignment table */
.esp-assign-wrap { border:1px solid var(--ds-line);border-radius:8px;max-height:420px;overflow-y:auto; }
.esp-assign-table{ width:100%;border-collapse:collapse;font-size:13px; }
.esp-assign-table th{ position:sticky;top:0;padding:10px 12px;background:#f9fafb;font-weight:600;color:#101828;border-bottom:1px solid var(--ds-line);text-align:left; }
.esp-assign-table td{ padding:10px 12px;border-bottom:1px solid var(--ds-line);vertical-align:middle;color:#344054; }
.esp-assign-table tr:last-child td{ border-bottom:none; }
.esp-assign-table tr.is-sel  td{ background:#eef4ff;color:#004384;font-weight:500; }
.esp-assign-table tr.is-swap td{ background:#fffaeb;color:#dc6803;font-weight:500; }
.esp-badge       { display:inline-block;border-radius:4px;padding:2px 8px;font-size:11px;font-weight:500; }
.esp-badge--sel  { background:#004384;color:#fff; }
.esp-badge--swap { background:#f5a623;color:#fff; }
.esp-stats       { display:flex;flex-wrap:wrap;gap:16px;font-size:12px;color:#667085;margin-top:10px; }
@media print { .esp-tools, .esp-swap-card, .vsp-actions, .modern-breadcrumb-back { display:none !important; } }
</style>
@endpush

@section('setup_content')
@php
    $assigned = $allocation['seats']->count();
    $blockNames = ['A' => 'Block A', 'B' => 'Centre', 'C' => 'Block C'];
@endphp
<div class="container-fluid coe-page">
    <x-breadcrum title="Edit Seating Plan" :showBack="true"
                 :items="['Setup', 'COE', 'Examination Management', 'Edit Seating Plan']" />

    <div class="d-flex flex-wrap justify-content-end gap-2 mb-3 coe-secondary-actions vsp-actions">
        <button type="button" class="btn programme-dt-btn-columns" id="espDownloadBtn" title="Download as CSV">
            <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
        </button>
        <button type="button" class="btn programme-dt-btn-columns" id="espPrintBtn" title="Print">
            <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
        </button>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            {{-- Title · legend · Swap Mode · Verify & Save --}}
            <div class="esp-head">
                <div>
                    <div class="esp-hall-label" style="font-size:16px">
                        {{ $allocation['room'] }} — Seating Plan {{ $allocation['frozen'] ? '(Frozen)' : 'Editor' }}
                    </div>
                    <div class="esp-hall-sub" style="font-size:13px">
                        {{ $allocation['course'] }} ({{ $allocation['batch'] }}) ·
                        {{ $allocation['subject'] }} / {{ $allocation['component'] }} ·
                        {{ $allocation['category'] }}@if ($allocation['exam_date']) · {{ $allocation['exam_date'] }}@endif ·
                        {{ $allocation['source'] }}
                    </div>
                </div>
                <div class="esp-tools">
                    <div class="esp-legend">
                        <span><span class="vsp-dot" style="background:#dce8fb"></span>Available</span>
                        <span><span class="vsp-dot" style="background:#004384"></span>Selected</span>
                        <span><span class="vsp-dot" style="background:#fef3c7"></span>Swap Target</span>
                        <span><span class="vsp-dot" style="background:#eaecf0"></span>Empty</span>
                    </div>
                    @unless ($allocation['frozen'])
                        <button type="button" id="espSwapToggle" class="esp-swap-toggle is-off" aria-pressed="false">
                            <i class="bi bi-arrow-left-right" aria-hidden="true"></i><span>Swap Mode: OFF</span>
                        </button>
                        <button type="button" id="espVerifyBtn" class="esp-verify-btn">
                            <i class="bi bi-check2-circle" aria-hidden="true"></i>Verify &amp; Save
                        </button>
                    @endunless
                </div>
            </div>

            <div class="esp-wrap">
                {{-- Left: the hall --}}
                <div class="esp-map-side">
                    <div class="vsp-hall">
                        <div class="vsp-hall-inner">
                            <div class="vsp-entrance">
                                <i class="bi bi-arrow-down" aria-hidden="true"></i>ENTRANCE<i class="bi bi-arrow-down" aria-hidden="true"></i>
                            </div>
                            <div class="esp-blocks-row vsp-map" id="espSeatMap"></div>
                            <div class="vsp-podium"><i class="bi bi-volume-up-fill" aria-hidden="true"></i>SPEAKER / PODIUM</div>
                        </div>
                    </div>
                    <div class="esp-under-legend">
                        <span><span class="vsp-dot" style="background:#eaecf0;box-shadow:inset 0 -3px 0 #17a2b8"></span>Laptop desk</span>
                        <span><span class="vsp-dot" style="background:#eaecf0;box-shadow:inset 0 -3px 0 #8e44ad"></span>Scriber desk</span>
                    </div>
                </div>

                {{-- Right: swap operation · assignments · totals --}}
                <div class="esp-panel-side">
                    <div class="esp-swap-card" id="espSwapCard" style="display:none">
                        <div class="esp-swap-title">Swap Operation</div>
                        <div class="esp-swap-row">
                            <div class="esp-swap-slot is-from">
                                <div class="seat-id" id="espSlotFromId">Select a seat</div>
                                <div class="seat-roll" id="espSlotFromRoll">Click an assigned seat</div>
                            </div>
                            <div class="esp-swap-arrow"><i class="bi bi-arrow-left-right" aria-hidden="true"></i>Swap</div>
                            <div class="esp-swap-slot is-to">
                                <div class="seat-id" id="espSlotToId">Swap with</div>
                                <div class="seat-roll" id="espSlotToRoll">Then the seat to swap with</div>
                            </div>
                            <button type="button" class="esp-confirm-btn" id="espConfirmSwap" disabled>Confirm Swap</button>
                            <button type="button" class="esp-cancel-btn" id="espCancelSwap">Cancel</button>
                        </div>
                    </div>

                    <div class="esp-assign-wrap">
                        <table class="esp-assign-table">
                            <thead>
                                <tr><th>Roll No.</th><th>Name</th><th>Seat</th><th>Block</th><th>Status</th></tr>
                            </thead>
                            <tbody id="espAssignBody">
                                @forelse ($allocation['seats'] as $a)
                                    @php $parts = explode('-', $a['label'], 2); @endphp
                                    <tr data-seat="{{ $a['seat'] }}">
                                        <td>{{ $a['roll'] }}</td>
                                        <td>{{ $a['name'] }}</td>
                                        <td>{{ $parts[1] ?? $a['label'] }}</td>
                                        <td>{{ $blockNames[$parts[0]] ?? $parts[0] }}</td>
                                        <td class="esp-assign-status"
                                            data-status="{{ collect([$a['laptop'] ? 'Laptop' : null, $a['scriber'] ? 'Scriber' : null, $a['manual'] ? 'Moved' : null])->filter()->implode(' · ') ?: 'Seated' }}">
                                            {{ collect([$a['laptop'] ? 'Laptop' : null, $a['scriber'] ? 'Scriber' : null, $a['manual'] ? 'Moved' : null])->filter()->implode(' · ') ?: 'Seated' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted">Nobody is seated in this room.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="esp-stats">
                        <span><span class="vsp-dot" style="background:#004384"></span>Total Seats: <strong>{{ $allocation['capacity'] }}</strong></span>
                        <span><span class="vsp-dot" style="background:#dce8fb"></span>Assigned: <strong>{{ $assigned }}</strong></span>
                        <span><span class="vsp-dot" style="background:#eaecf0"></span>Empty: <strong>{{ max(0, $allocation['capacity'] - $assigned) }}</strong></span>
                        <span><span class="vsp-dot" style="background:#fff;box-shadow:inset 0 0 0 1.5px #f5a623"></span>Pending Swaps: <strong id="espStatSwaps">0</strong></span>
                    </div>
                </div>
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
    var SWAP_URL = @json(route('admin.coe.venue-allocation.swap', $allocation['id']));
    var CSRF = $('meta[name="csrf-token"]').attr('content');

    var laptop = {};
    ROOM.laptop_seats.forEach(function (n) { laptop[n] = true; });

    /* ── Seat map: same numbering as the allocator (public/js/coe-seat-map.js) ── */
    var $map = $('#espSeatMap');
    var bySeat = CoeSeatMap.render($map, ROOM);

    // "B-A08" → "Seat A08" — the block is its own column.
    function seatName(n) { var l = CoeSeatMap.label(n); return 'Seat ' + (l.split('-')[1] || l); }

    if (ROOM.frozen) { return; }

    /* ── Swap: click an assigned seat, then any other seat (empty = move) ── */
    var swapMode = false, from = null, to = null;

    function paintSwap() {
        $map.find('.esp-seat').removeClass('is-picked is-swap-target');
        $('#espAssignBody tr').removeClass('is-sel is-swap').find('.esp-assign-status').each(function () {
            $(this).text($(this).attr('data-status'));
        });
        if (from) {
            $map.find('[data-seat="' + from + '"]').addClass('is-picked');
            $('#espAssignBody tr[data-seat="' + from + '"]').addClass('is-sel')
                .find('.esp-assign-status').html('<span class="esp-badge esp-badge--sel">Selected</span>');
        }
        if (to) {
            $map.find('[data-seat="' + to + '"]').addClass('is-swap-target');
            $('#espAssignBody tr[data-seat="' + to + '"]').addClass('is-swap')
                .find('.esp-assign-status').html('<span class="esp-badge esp-badge--swap">Swap Target</span>');
        }
        var $sel = $('#espAssignBody tr.is-sel');
        if ($sel.length) { $sel[0].scrollIntoView({ block: 'nearest' }); }

        $('#espSlotFromId').text(from ? seatName(from) : 'Select a seat');
        $('#espSlotFromRoll').text(from ? bySeat[from].roll : 'Click an assigned seat');
        $('#espSlotToId').text(to ? seatName(to) : 'Swap with');
        $('#espSlotToRoll').text(to ? (bySeat[to] ? bySeat[to].roll : 'Empty — OT moves here') : 'Then the seat to swap with');
        $('#espConfirmSwap').prop('disabled', !(from && to));
        $('#espStatSwaps').text(from && to ? 1 : 0);
    }

    $('#espSwapToggle').on('click', function () {
        swapMode = !swapMode;
        $(this).toggleClass('is-off', !swapMode).attr('aria-pressed', swapMode)
            .find('span').text('Swap Mode: ' + (swapMode ? 'ON' : 'OFF'));
        $('#espSwapCard').toggle(swapMode);
        $map.find('.esp-seat[data-seat]').css('cursor', swapMode ? 'pointer' : '');
        from = to = null;
        paintSwap();
    });

    $map.on('click', '.esp-seat[data-seat]', function () {
        if (!swapMode) { return; }
        var n = parseInt($(this).attr('data-seat'), 10);
        if (!from) {
            if (!bySeat[n]) { return; }
            from = n;
        } else if (n === from) {
            from = to = null;
        } else {
            to = n;
        }
        paintSwap();
    });

    // A row in the table picks its seat too, as on the map.
    $('#espAssignBody').on('click', 'tr[data-seat]', function () {
        if (!swapMode) { return; }
        $map.find('[data-seat="' + $(this).attr('data-seat') + '"]').trigger('click');
    });

    $('#espCancelSwap').on('click', function () { from = to = null; paintSwap(); });

    $('#espConfirmSwap').on('click', function () {
        var $btn = $(this).prop('disabled', true);
        $.ajax({ url: SWAP_URL, method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF }, data: { from_seat: from, to_seat: to } })
            .done(function (r) {
                Swal.fire({ icon: 'success', title: 'Swapped', text: r.message, timer: 1500, showConfirmButton: false })
                    .then(function () { location.reload(); });
            })
            .fail(function (xhr) {
                var res = xhr.responseJSON || {};
                var msg = res.errors ? Object.keys(res.errors).map(function (k) { return res.errors[k][0]; }).join('\n') : (res.message || 'The swap could not be saved.');
                Swal.fire({ icon: 'error', title: 'Not swapped', text: msg });
                $btn.prop('disabled', false);
            });
    });

    /* ── Verify: every swap is saved as it is confirmed; this checks the room as saved ── */
    $('#espVerifyBtn').on('click', function () {
        var problems = [];
        if (ROOM.laptop_seats.length) {
            ROOM.seats.forEach(function (s) {
                if (s.laptop && !laptop[s.seat]) { problems.push(s.roll + ' needs a laptop but sits at ' + s.label); }
            });
        }
        if (from && to) { problems.push('A swap is selected but not confirmed.'); }
        Swal.fire(problems.length
            ? { icon: 'warning', title: 'Check the seating', text: problems.join('\n') }
            : { icon: 'success', title: 'Verified', text: ROOM.seats.length + ' OT(s) seated. All swaps are saved.' });
    });
});
</script>
<script>
$(function () {
    /* Download: the assignment list as CSV. */
    $('#espDownloadBtn').on('click', function () {
        var rows = [['Roll No.', 'Name', 'Seat', 'Block', 'Status']];
        $('#espAssignBody tr[data-seat]').each(function () {
            var cells = $(this).children('td').map(function () { return $(this).text().trim(); }).get();
            cells[4] = $(this).find('.esp-assign-status').attr('data-status');
            rows.push(cells);
        });
        var csv = rows.map(function (r) { return r.map(function (v) { return '"' + String(v).replace(/"/g, '""') + '"'; }).join(','); }).join('\r\n');
        var a = document.createElement('a');
        a.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8' }));
        a.download = 'seating-plan.csv';
        document.body.appendChild(a); a.click();
        setTimeout(function () { URL.revokeObjectURL(a.href); a.remove(); }, 0);
    });
    $('#espPrintBtn').on('click', function () { window.print(); });
});
</script>
@endpush
