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
.esp-verify-btn.is-pending { box-shadow:0 0 0 3px #fdb022; }
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
.esp-pick        { width:36px; }
.esp-move-btn    { display:inline-flex;align-items:center;gap:6px;background:#fff;color:#004384;border:1.5px solid #004384;border-radius:6px;
                   padding:6px 14px;font-size:13px;font-weight:600;cursor:pointer; }
.esp-move-btn:disabled { opacity:.5;cursor:not-allowed; }
@media print { .esp-tools, .esp-swap-card, .vsp-actions, .modern-breadcrumb-back, .esp-pick { display:none !important; } }
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
        <div class="dropdown">
            <button type="button" class="btn programme-dt-btn-columns dropdown-toggle" id="espDownloadBtn" data-bs-toggle="dropdown" aria-expanded="false" title="Download">
                <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
            </button>
            {{-- Server export (VenueAllocationController::seatsExport); `download` keeps them out of Edit's leave-page guard. --}}
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="espDownloadBtn">
                <li><a class="dropdown-item" download href="{{ route('admin.coe.venue-allocation.export', ['id' => $allocation['id'], 'format' => 'excel']) }}">
                    <i class="bi bi-file-earmark-excel me-1" aria-hidden="true"></i> Excel (.xlsx)</a></li>
                <li><a class="dropdown-item" download href="{{ route('admin.coe.venue-allocation.export', ['id' => $allocation['id'], 'format' => 'pdf']) }}">
                    <i class="bi bi-file-earmark-pdf me-1" aria-hidden="true"></i> PDF</a></li>
                <li><a class="dropdown-item" download href="{{ route('admin.coe.venue-allocation.export', ['id' => $allocation['id'], 'format' => 'csv']) }}">
                    <i class="bi bi-filetype-csv me-1" aria-hidden="true"></i> CSV</a></li>
            </ul>
        </div>
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
                        <button type="button" id="espMoveBtn" class="esp-move-btn"
                                title="Tick the OTs in the list, then move them to another room">
                            <i class="bi bi-door-open" aria-hidden="true"></i>Change Room
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
                                <tr>
                                    @unless ($allocation['frozen'])
                                        <th class="esp-pick"><input type="checkbox" class="form-check-input" id="espPickAll" aria-label="Select every OT"></th>
                                    @endunless
                                    <th>Roll No.</th><th>Name</th><th>Seat</th><th>Block</th><th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="espAssignBody">
                                @forelse ($allocation['seats'] as $a)
                                    @php $parts = explode('-', $a['label'], 2); @endphp
                                    <tr data-seat="{{ $a['seat'] }}">
                                        @unless ($allocation['frozen'])
                                            <td class="esp-pick"><input type="checkbox" class="form-check-input esp-pick-one" value="{{ $a['seat'] }}"
                                                                        aria-label="Select {{ $a['roll'] }}"></td>
                                        @endunless
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
                                    <tr><td colspan="{{ $allocation['frozen'] ? 5 : 6 }}" class="text-center text-muted">Nobody is seated in this room.</td></tr>
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
@unless ($allocation['frozen'])
{{-- Change Room: move the ticked OTs to another room of this paper, or a free room. --}}
<div class="modal fade" id="espMoveModal" tabindex="-1" aria-labelledby="espMoveModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content coe-modal border-0 shadow">
            <div class="modal-header coe-modal-header coe-modal-header--rule">
                <h5 class="modal-title" id="espMoveModalLabel">Change Room</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body coe-modal-body">
                <p class="mb-2">Move <strong id="espMoveCount">0</strong> OT(s): <span id="espMoveRolls" class="text-muted"></span></p>
                <label class="coe-form-label" for="espMoveRoom">Move to Room<span class="coe-req">*</span></label>
                <select id="espMoveRoom" class="form-select coe-control">
                    <option value="">Select Room</option>
                    @foreach (collect($moveRooms)->groupBy('group') as $group => $rooms)
                        <optgroup label="{{ $group }}">
                            @foreach ($rooms as $r)
                                <option value="{{ $r['id'] }}">{{ $r['name'] }} ({{ $r['free'] }} free desk(s), {{ $r['laptop'] }} laptop(s))</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                <div class="invalid-feedback" id="espMoveError"></div>
                <div class="form-text">Each OT gets a free desk there (a laptop desk if they need one). A free room is added to this paper.</div>
                @if (empty($moveRooms))
                    <div class="text-danger small mt-2">No other examination room has a free desk on this paper's exam date.</div>
                @endif
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-danger rounded-3 px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary rounded-3 px-4" id="espMoveConfirm">Move</button>
            </div>
        </div>
    </div>
</div>
@endunless
@endsection

@push('scripts')
<script src="{{ asset('js/coe-seat-map.js') }}?v={{ @filemtime(public_path('js/coe-seat-map.js')) ?: time() }}"></script>
<script>
$(function () {
    'use strict';

    var ROOM = @json($allocation);
    var SWAP_URL = @json(route('admin.coe.venue-allocation.swap', $allocation['id']));
    var MOVE_URL = @json(route('admin.coe.venue-allocation.move', $allocation['id']));
    var CSRF = $('meta[name="csrf-token"]').attr('content');

    var laptop = {};
    ROOM.laptop_seats.forEach(function (n) { laptop[n] = true; });

    /* ── Seat map: same numbering as the allocator (public/js/coe-seat-map.js) ── */
    var $map = $('#espSeatMap');
    var bySeat = CoeSeatMap.render($map, ROOM);

    // "B-A08" → "Seat A08" — the block is its own column.
    function seatName(n) { var l = CoeSeatMap.label(n); return 'Seat ' + (l.split('-')[1] || l); }

    if (ROOM.frozen) { return; }

    /* ── Unverified changes: a swap / room change is saved at once and the page reloads,
       so the "not verified yet" flag is kept in sessionStorage (this tab only) until
       Verify & Save passes. Until then, leaving the page is stopped with a reminder. ── */
    var UNVERIFIED_KEY = 'coeSeatPlanEdit:unverified:' + ROOM.id;
    var reloading = false;
    function storage(fn) { try { return fn(window.sessionStorage); } catch (e) { return null; } }
    var unverified = storage(function (s) { return s.getItem(UNVERIFIED_KEY) === '1'; }) === true;
    function setUnverified(on) {
        unverified = on;
        storage(function (s) { if (on) { s.setItem(UNVERIFIED_KEY, '1'); } else { s.removeItem(UNVERIFIED_KEY); } });
    }
    // Our own reload after a saved change is not "leaving".
    function reloadAfterChange() { setUnverified(true); reloading = true; location.reload(); }

    function remindToVerify() {
        Swal.fire({
            icon: 'warning',
            title: 'Please Verify & Save',
            text: 'The seating of this room was changed. Verify & Save it before leaving this page.',
            showCancelButton: true,
            confirmButtonText: 'Verify & Save',
            cancelButtonText: 'Stay on this page'
        }).then(function (r) { if (r.isConfirmed) { $('#espVerifyBtn').trigger('click'); } });
    }

    // In-page ways out: any link to another page, and the breadcrumb's back arrow
    // (an inline history.back()). Caught in the capture phase, before they act.
    document.addEventListener('click', function (e) {
        if (!unverified) { return; }
        var back = e.target.closest('.modern-breadcrumb-back');
        var link = e.target.closest('a[href]');
        if (link) {
            var href = link.getAttribute('href') || '';
            if (href === '' || href.charAt(0) === '#' || /^javascript:/i.test(href) || link.target === '_blank'
                || link.hasAttribute('download') || link.getAttribute('data-bs-toggle')) { link = null; }
        }
        if (!back && !link) { return; }
        e.preventDefault();
        e.stopImmediatePropagation();
        remindToVerify();
    }, true);

    // Reload, close, typed URL, browser Back: the browser shows its own "Leave site?" prompt
    // (it does not allow custom text).
    window.addEventListener('beforeunload', function (e) {
        if (!unverified || reloading) { return; }
        e.preventDefault();
        e.returnValue = '';
    });

    if (unverified) {
        $('#espVerifyBtn').addClass('is-pending').attr('title', 'Seating changed: verify and save it before leaving');
    }

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
                setUnverified(true);
                Swal.fire({ icon: 'success', title: 'Swapped', text: r.message + ' Verify & Save before leaving.', timer: 1800, showConfirmButton: false })
                    .then(reloadAfterChange);
            })
            .fail(function (xhr) {
                var res = xhr.responseJSON || {};
                var msg = res.errors ? Object.keys(res.errors).map(function (k) { return res.errors[k][0]; }).join('\n') : (res.message || 'The swap could not be saved.');
                Swal.fire({ icon: 'error', title: 'Not swapped', text: msg });
                $btn.prop('disabled', false);
            });
    });

    /* ── Change Room: tick OTs in the list, pick a room ── */
    function picked() { return $('.esp-pick-one:checked').map(function () { return parseInt(this.value, 10); }).get(); }
    function paintPicked() {
        var n = picked().length, all = $('.esp-pick-one').length;
        $('#espPickAll').prop('checked', n > 0 && n === all).prop('indeterminate', n > 0 && n < all);
    }
    // A tick is not a swap pick.
    $('#espAssignBody').on('click', '.esp-pick', function (e) { e.stopPropagation(); });
    $('#espAssignBody').on('change', '.esp-pick-one', paintPicked);
    $('#espPickAll').on('change', function () { $('.esp-pick-one').prop('checked', this.checked); paintPicked(); });

    $('#espMoveBtn').on('click', function () {
        var seats = picked();
        // Always clickable (a disabled button read as "no Change Room"); with nothing ticked it says what to do.
        if (!seats.length) {
            Swal.fire({ icon: 'info', title: 'Select OTs first',
                text: 'Tick the OTs to move in the list on the right (Roll No. column), then click Change Room.' });
            return;
        }
        $('#espMoveCount').text(seats.length);
        $('#espMoveRolls').text(seats.map(function (n) { return bySeat[n] ? bySeat[n].roll : n; }).join(', '));
        $('#espMoveRoom').val('').removeClass('is-invalid');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('espMoveModal')).show();
    });

    $('#espMoveConfirm').on('click', function () {
        var $room = $('#espMoveRoom'), venue = $room.val();
        $room.removeClass('is-invalid');
        if (!venue) { $('#espMoveError').text('Select the room to move them to.'); $room.addClass('is-invalid'); return; }
        var $btn = $(this).prop('disabled', true);
        $.ajax({ url: MOVE_URL, method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF }, data: { seats: picked(), venue_id: venue } })
            .done(function (r) {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('espMoveModal')).hide();
                setUnverified(true);
                Swal.fire({ icon: 'success', title: 'Room Changed', text: r.message + ' Verify & Save before leaving.', timer: 1800, showConfirmButton: false })
                    .then(reloadAfterChange);
            })
            .fail(function (xhr) {
                var res = xhr.responseJSON || {};
                var msg = res.errors ? Object.keys(res.errors).map(function (k) { return res.errors[k][0]; }).join('\n') : (res.message || 'The room could not be changed.');
                $('#espMoveError').text(msg); $room.addClass('is-invalid');
            })
            .always(function () { $btn.prop('disabled', false); });
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
        if (problems.length) {
            Swal.fire({ icon: 'warning', title: 'Check the seating', text: problems.join('\n') });
            return;
        }
        // Verified: leaving the page is free again.
        setUnverified(false);
        $('#espVerifyBtn').removeClass('is-pending').removeAttr('title');
        Swal.fire({ icon: 'success', title: 'Verified', text: ROOM.seats.length + ' OT(s) seated. All swaps are saved.' });
    });
});
</script>
<script>
$(function () {
    // Print = the branded Seating Plan sheet (venue_allocation/print), not this screen.
    $('#espPrintBtn').on('click', function () {
        window.open(@json(route('admin.coe.venue-allocation.print', $allocation['id'])), '_blank');
    });
});
</script>
@endpush
