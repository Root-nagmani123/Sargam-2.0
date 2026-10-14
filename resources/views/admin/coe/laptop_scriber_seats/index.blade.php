@extends('admin.layouts.master')

@section('title', 'Laptop & Scriber Seats Master')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}">
<link rel="stylesheet"
      href="{{ asset('css/coe-admin.css') }}?v={{ @filemtime(public_path('css/coe-admin.css')) ?: time() }}">
<style>
/* ── Seat map ──────────────────────────────────────────────────────────── */
.lss-map-wrap        { overflow-x: auto; }
.lss-map             { display: inline-block; min-width: 340px; font-size: 11px; user-select: none; }
.lss-map-header      { display: flex; align-items: center; justify-content: center; gap: .5rem;
                        font-size: 11px; font-weight: 600; color: var(--ds-ink-muted); margin-bottom: 4px; }
.lss-blocks          { display: flex; gap: 6px; }
.lss-block           { display: flex; flex-direction: column; gap: 2px; }
.lss-block-label     { text-align: center; font-weight: 600; font-size: 10px;
                        color: var(--ds-ink-muted); margin-bottom: 2px; }
.lss-row             { display: flex; gap: 2px; align-items: center; }
.lss-row-label       { width: 10px; font-weight: 600; font-size: 10px;
                        color: var(--ds-ink-muted); text-align: right; margin-right: 2px; flex-shrink: 0; }
.lss-seat            { width: 20px; height: 20px; border-radius: 3px; border: 1px solid #c5d6ee;
                        background: #c5dcf5; cursor: pointer; font-size: 9px; display: flex;
                        align-items: center; justify-content: center; color: #1a4270;
                        transition: background .12s, border-color .12s; flex-shrink: 0; }
.lss-seat:hover      { background: #a9cbee; }
.lss-seat.is-selected{ background: #f5a623; border-color: #c8841a; color: #5a3800; }
.lss-seat.is-disabled{ background: #e9ecef; border-color: #ced4da; color: #adb5bd; cursor: default; pointer-events: none; }
.lss-podium          { text-align: center; font-size: 10px; color: var(--ds-ink-muted);
                        margin-top: 4px; border-top: 1px solid #d0d9e8; padding-top: 3px; }
.lss-sep             { width: 6px; }
.lss-count-badge     { display: inline-flex; align-items: center; gap: 4px; font-size: 12px;
                        background: var(--ds-surface-2); border: 1px solid var(--ds-line);
                        border-radius: 4px; padding: 2px 8px; margin-top: 6px; }
.lss-count-badge b   { color: var(--ds-primary); }
/* index table */
.lss-dash            { color: var(--ds-ink-muted); }
</style>
@endpush

@section('setup_content')
<div class="container-fluid coe-page">
    <x-breadcrum title="Laptop &amp; Scriber Seats Master" :showBack="false"
                 :items="['Setup', 'COE', 'Master Data', 'Laptop &amp; Scriber Seats Master']">
        <button type="button" class="btn coe-btn-header" id="lssAddBtn">
            <i class="bi bi-plus-lg" aria-hidden="true"></i><span>Add Laptop &amp; Scriber Seats</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    <div class="d-flex flex-wrap justify-content-end gap-2 mb-3 coe-secondary-actions">
        <button type="button" id="lssDownloadBtn" class="btn programme-dt-btn-columns" title="Download as Excel">
            <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
        </button>
        <button type="button" id="lssPrintBtn" class="btn programme-dt-btn-columns" title="Print">
            <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
        </button>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            {{-- Toolbar --}}
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar coe-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Filter</span>

                    <div class="programme-dt-filter-select" style="min-width:160px">
                        <select id="lssFilterBuilding" class="form-select" data-placeholder="Building Name" aria-label="Building Name">
                            <option value="">Building Name</option>
                            @foreach ($buildingFilters as $b)
                                <option value="{{ $b }}">{{ $b }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="programme-dt-filter-select">
                        <select id="lssFilterFloor" class="form-select" data-placeholder="Floor" aria-label="Floor">
                            <option value="">Floor</option>
                            @foreach ($floorFilters as $f)
                                <option value="{{ $f }}">{{ $f }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="button" id="lssResetFilters" class="btn programme-dt-btn-reset">Remove Filter</button>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns"
                            data-bs-toggle="modal" data-bs-target="#lssColumnVisibilityModal"
                            title="Show / hide columns">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="lssDtSearch" class="programme-dt-search d-none" data-dt-search-for="lssTable"></div>
                    <button type="button" id="lssSearchToggle" class="coe-search-toggle"
                            aria-controls="lssDtSearch" aria-expanded="false" title="Search">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <span class="visually-hidden">Search</span>
                    </button>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table id="lssTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th scope="col">Room Name</th>
                                <th scope="col">Building</th>
                                <th scope="col">Floor</th>
                                <th scope="col">Room Type</th>
                                <th scope="col" class="coe-col-center">Total Capacity</th>
                                <th scope="col" class="coe-col-center">Laptop Seats</th>
                                <th scope="col" class="coe-col-center">Scriber Seats</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr data-coe-id="{{ $row['id'] }}"
                                    data-lss-building="{{ $row['building'] }}"
                                    data-lss-floor="{{ $row['floor'] }}"
                                    data-lss-room="{{ $row['room'] }}"
                                    data-lss-type="{{ $row['type'] }}"
                                    data-lss-capacity="{{ $row['capacity'] }}"
                                    data-lss-laptop="{{ $row['laptop_seats'] }}"
                                    data-lss-scriber="{{ $row['scriber_seats'] }}">
                                    <td class="lss-cell-room coe-strong">{{ $row['room'] }}</td>
                                    <td class="lss-cell-building">{{ $row['building'] }}</td>
                                    <td class="lss-cell-floor">{{ $row['floor'] }}</td>
                                    <td class="lss-cell-type">{{ $row['type'] }}</td>
                                    <td class="coe-col-center lss-cell-capacity" data-order="{{ $row['capacity'] }}">{{ $row['capacity'] }}</td>
                                    <td class="coe-col-center lss-cell-laptop" data-order="{{ $row['laptop_seats'] }}">
                                        @if ($row['laptop_seats'] > 0)
                                            {{ $row['laptop_seats'] }}
                                        @else
                                            <span class="lss-dash">&mdash;</span>
                                        @endif
                                    </td>
                                    <td class="coe-col-center lss-cell-scriber" data-order="{{ $row['scriber_seats'] }}">
                                        @if ($row['scriber_seats'] > 0)
                                            {{ $row['scriber_seats'] }}
                                        @else
                                            <span class="lss-dash">&mdash;</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="coe-act-group" role="group" aria-label="Row actions">
                                            <button type="button" class="coe-act coe-act--edit">
                                                <span class="coe-act__icon"><i class="bi bi-pencil" aria-hidden="true"></i></span>
                                                <span class="coe-act__label">Edit</span>
                                            </button>
                                            <button type="button" class="coe-act coe-act--del">
                                                <span class="coe-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>
                                                <span class="coe-act__label">Delete</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3"
                     data-dt-footer-for="lssTable"></div>
            </div>
        </div>
    </div>
</div>

{{-- ── Add / Edit modal ──────────────────────────────────────────────────── --}}
<div class="modal fade" id="lssFormModal" tabindex="-1" aria-labelledby="lssFormModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content coe-modal border-0 shadow">
            <form id="lssForm" novalidate>
                <div class="modal-header coe-modal-header coe-modal-header--rule">
                    <h5 class="modal-title" id="lssFormModalLabel">Add Laptop &amp; Scriber Seats</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body coe-modal-body">
                    {{-- Row 1: Building + Floor --}}
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="coe-form-label" for="lssBuilding">Building Name<span class="coe-req">*</span></label>
                            <select id="lssBuilding" name="building_name" class="form-select coe-control" required>
                                <option value="">Select Building Name</option>
                                @foreach ($buildings as $b)
                                    <option value="{{ $b }}">{{ $b }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="coe-form-label" for="lssFloor">Floor Name<span class="coe-req">*</span></label>
                            <select id="lssFloor" name="floor_name" class="form-select coe-control" required>
                                <option value="">Select Floor</option>
                            </select>
                        </div>
                    </div>

                    {{-- Row 2: Room --}}
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <label class="coe-form-label" for="lssRoom">Room Name<span class="coe-req">*</span></label>
                            <select id="lssRoom" name="room_name" class="form-select coe-control" required>
                                <option value="">Select Room</option>
                            </select>
                        </div>
                    </div>

                    {{-- Row 3: Seat maps side by side --}}
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <label class="coe-form-label mb-2">Laptop Seats<span class="coe-req">*</span></label>
                            <div id="lssLaptopMap" class="lss-map-wrap"></div>
                            <div class="lss-count-badge mt-2">
                                Selected: <b id="lssLaptopCount">0</b> laptop seat(s)
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <label class="coe-form-label mb-2">Scriber Seats<span class="coe-req">*</span></label>
                            <div id="lssScriberMap" class="lss-map-wrap"></div>
                            <div class="lss-count-badge mt-2">
                                Selected: <b id="lssScriberCount">0</b> scriber seat(s)
                            </div>
                        </div>
                    </div>
                    <div id="lssSeatsError" class="coe-field-error d-none mt-2">Select at least one laptop seat.</div>
                </div>
                <div class="modal-footer coe-modal-footer">
                    <button type="button" class="btn coe-btn coe-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn coe-btn coe-btn-primary" id="lssSubmit">Add Laptop &amp; Scriber Seats</button>
                </div>
            </form>
        </div>
    </div>
</div>

@include('admin.coe.partials.confirm_delete_modal', ['id' => 'lssDeleteModal', 'confirmId' => 'lssDeleteConfirm'])

{{-- Column Visibility --}}
<div class="modal fade" id="lssColumnVisibilityModal" tabindex="-1" aria-labelledby="lssColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="lssColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="lssColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/coe-grid.js') }}?v={{ @filemtime(public_path('js/coe-grid.js')) ?: time() }}"></script>
<script>
$(function () {
    'use strict';

    /* ── Data from server ─────────────────────────────────────────────── */
    var FLOORS_BY_BUILDING = @json($floorsByBuilding);
    var ROOMS_BY_BUILDING_FLOOR = @json($roomsByBuildingFloor);

    /* ── Column map ───────────────────────────────────────────────────── */
    var COL = { room: 0, building: 1, floor: 2, type: 3, capacity: 4, laptop: 5, scriber: 6, action: 7 };

    var $table = $('#lssTable');

    /* ── DataTable ────────────────────────────────────────────────────── */
    var dt = CoeGrid.init({
        table: '#lssTable',
        title: 'Laptop & Scriber Seats Master',
        snoCol: false,
        order: [[COL.building, 'asc'], [COL.room, 'asc']],
        noSort: [COL.action],
        exclude: [COL.action],
        filters: { '#lssFilterBuilding': COL.building, '#lssFilterFloor': COL.floor },
        reset: '#lssResetFilters',
        download: '#lssDownloadBtn',
        print: '#lssPrintBtn',
        searchToggle: '#lssSearchToggle',
        searchSlot: '#lssDtSearch',
        colvisGrid: '#lssColumnToggleGrid',
        colvisKey: 'coeLaptopScriberSeats:hiddenColumns:v1',
        empty: { title: 'No Seat Allocations', zeroTitle: 'No Records Found',
                 text: 'Add laptop and scriber seats to get started.' }
    });

    /* ── Delete ────────────────────────────────────────────────────────── */
    CoeGrid.confirmDelete({
        table: '#lssTable',
        modal: '#lssDeleteModal',
        confirm: '#lssDeleteConfirm',
        onConfirm: function ($row) {
            dt.row($row).remove().draw(false);
            Swal.fire({ icon: 'success', title: 'Deleted', text: 'Record deleted.' });
        }
    });

    /* ── Seat map ─────────────────────────────────────────────────────── */
    /*
     * Seat layout matches the screenshot:
     *   Block A  (cols 1-4, rows A-F)
     *   Block B CENTRE  (cols 1-13, rows A-F)  — widest block
     *   Block C  (cols 1-6, rows A-F)
     * Seats are numbered within each block, prefixed by block letter.
     * The row label (A-F) goes front-to-back (A = front row near entrance).
     */
    var ROWS = ['F', 'E', 'D', 'C', 'B', 'A'];  // top to bottom in visual
    var BLOCKS = [
        { label: 'BLOCK A',          cols: 4  },
        { label: 'BLOCK B (CENTRE)', cols: 13 },
        { label: 'BLOCK C',          cols: 6  }
    ];

    function buildSeatId(blockIdx, row, col) {
        return 'B' + blockIdx + '_' + row + col;
    }

    /**
     * Render a seat map into `container`.
     * Returns { getSelected, setSelected, reset, setCapacity }
     */
    function buildSeatMap(container, onCountChange) {
        var $wrap = $(container);
        var selected = {};

        var $map = $('<div class="lss-map"></div>');

        // Entrance arrow header
        $map.append(
            '<div class="lss-map-header">' +
            '<i class="bi bi-arrow-down-short" aria-hidden="true"></i> ENTRANCE ' +
            '<i class="bi bi-arrow-down-short" aria-hidden="true"></i></div>'
        );

        var $blocks = $('<div class="lss-blocks"></div>').appendTo($map);

        BLOCKS.forEach(function (block, bi) {
            var $block = $('<div class="lss-block"></div>').appendTo($blocks);
            $('<div class="lss-block-label"></div>').text(block.label).appendTo($block);

            // Separator between blocks
            if (bi < BLOCKS.length - 1) {
                $blocks.append('<div class="lss-sep"></div>');
            }

            ROWS.forEach(function (rowLetter) {
                var $row = $('<div class="lss-row"></div>');
                $('<div class="lss-row-label"></div>').text(rowLetter).appendTo($row);

                for (var c = block.cols; c >= 1; c--) {
                    var seatNum = String(c).padStart(2, '0');
                    // Build label: block-letter + rowLetter + col e.g. "A G21"
                    var id = buildSeatId(bi, rowLetter, seatNum);
                    var $seat = $('<button type="button" class="lss-seat"></button>')
                        .attr({ 'data-sid': id, 'title': rowLetter + seatNum })
                        .text(rowLetter + seatNum);
                    $row.append($seat);
                }
                $block.append($row);
            });
        });

        $map.append('<div class="lss-podium"><i class="bi bi-speaker" aria-hidden="true"></i> SPEAKER / PODIUM</div>');
        $wrap.append($map);

        // Toggle on click
        $map.on('click', '.lss-seat:not(.is-disabled)', function () {
            var id = $(this).attr('data-sid');
            if (selected[id]) {
                delete selected[id];
                $(this).removeClass('is-selected');
            } else {
                selected[id] = true;
                $(this).addClass('is-selected');
            }
            onCountChange(Object.keys(selected).length);
        });

        return {
            getCount: function () { return Object.keys(selected).length; },
            reset: function () {
                selected = {};
                $map.find('.lss-seat').removeClass('is-selected is-disabled');
                onCountChange(0);
            },
            /** Disable seats beyond `n` total capacity — those that don't exist. */
            setCapacity: function (n) {
                // Count all seats and disable those above capacity
                var count = 0;
                $map.find('.lss-seat').each(function () {
                    count++;
                    $(this).toggleClass('is-disabled', count > n);
                });
            },
            /** Pre-select the first `n` seats (for edit pre-fill). */
            selectFirst: function (n) {
                selected = {};
                var done = 0;
                $map.find('.lss-seat:not(.is-disabled)').each(function () {
                    if (done < n) {
                        selected[$(this).attr('data-sid')] = true;
                        $(this).addClass('is-selected');
                        done++;
                    }
                });
                onCountChange(done);
            }
        };
    }

    /* Build both maps once */
    var laptopMap  = buildSeatMap('#lssLaptopMap',  function (n) { $('#lssLaptopCount').text(n); });
    var scriberMap = buildSeatMap('#lssScriberMap', function (n) { $('#lssScriberCount').text(n); });

    /* ── Dependent selects: Building → Floor → Room ───────────────────── */
    var $building = $('#lssBuilding');
    var $floor    = $('#lssFloor');
    var $room     = $('#lssRoom');
    var $modal    = $('#lssFormModal');

    [$building, $floor, $room].forEach(function ($s) {
        $s.select2({ width: '100%', dropdownParent: $modal });
    });

    function fillFloors() {
        var b = $building.val();
        var floors = b ? (FLOORS_BY_BUILDING[b] || []) : [];
        $floor.empty().append(new Option('Select Floor', '', true, true));
        floors.forEach(function (f) { $floor.append(new Option(f, f)); });
        $floor.trigger('change.select2');
        fillRooms();
    }

    function fillRooms() {
        var b = $building.val(), f = $floor.val();
        var rooms = (b && f) ? ((ROOMS_BY_BUILDING_FLOOR[b] || {})[f] || []) : [];
        $room.empty().append(new Option('Select Room', '', true, true));
        rooms.forEach(function (r) { $room.append(new Option(r[0], r[0])); });
        $room.trigger('change.select2');
        resetMaps();
    }

    function resetMaps() {
        laptopMap.reset();
        scriberMap.reset();
        // Apply capacity from selected room
        var b = $building.val(), f = $floor.val(), rm = $room.val();
        var rooms = (b && f) ? ((ROOMS_BY_BUILDING_FLOOR[b] || {})[f] || []) : [];
        var found = rooms.find(function (r) { return r[0] === rm; });
        var capacity = found ? found[2] : 999;
        laptopMap.setCapacity(capacity);
        scriberMap.setCapacity(capacity);
    }

    $building.on('change', function () { fieldError($building, ''); fillFloors(); });
    $floor.on('change', function () { fieldError($floor, ''); fillRooms(); });
    $room.on('change', function () { fieldError($room, ''); resetMaps(); });

    /* ── Form validation helpers ─────────────────────────────────────── */
    function fieldError($el, msg) {
        var $wrap = $el.closest('[class*="col-"]');
        $wrap.find('.coe-field-error').remove();
        $el.toggleClass('is-invalid', !!msg);
        $wrap.find('.select2-selection').toggleClass('is-invalid', !!msg);
        if (msg) { $('<div class="coe-field-error"></div>').text(msg).appendTo($wrap); }
        return !msg;
    }

    /* ── Form open (add / edit) ──────────────────────────────────────── */
    var $editingRow = null;

    function openForm($row) {
        $editingRow = $row || null;
        var isEdit = !!$row;
        $('#lssFormModalLabel').text(isEdit ? 'Edit Laptop & Scriber Seats' : 'Add Laptop & Scriber Seats');
        $('#lssSubmit').text(isEdit ? 'Update' : 'Add Laptop & Scriber Seats');

        if (isEdit) {
            var b  = $row.attr('data-lss-building');
            var fl = $row.attr('data-lss-floor');
            var rm = $row.attr('data-lss-room');
            var laptop  = parseInt($row.attr('data-lss-laptop'), 10)  || 0;
            var scriber = parseInt($row.attr('data-lss-scriber'), 10) || 0;
            var capacity = parseInt($row.attr('data-lss-capacity'), 10) || 999;

            $building.val(b).trigger('change.select2');
            fillFloors();
            $floor.val(fl).trigger('change.select2');
            fillRooms();
            $room.val(rm).trigger('change.select2');
            laptopMap.setCapacity(capacity);
            scriberMap.setCapacity(capacity);
            laptopMap.selectFirst(laptop);
            scriberMap.selectFirst(scriber);
        } else {
            document.getElementById('lssForm').reset();
            $building.val('').trigger('change.select2');
            fillFloors();
        }
        $('#lssSeatsError').addClass('d-none');
        bootstrap.Modal.getOrCreateInstance($modal[0]).show();
    }

    $('#lssAddBtn').on('click', function () { openForm(null); });
    $table.on('click', '.coe-act--edit', function () { openForm($(this).closest('tr')); });

    $modal.on('hidden.bs.modal', function () {
        $editingRow = null;
        laptopMap.reset();
        scriberMap.reset();
        $modal.find('.coe-field-error').remove();
        $modal.find('.is-invalid').removeClass('is-invalid');
        $('#lssSeatsError').addClass('d-none');
    });

    /* ── Submit ─────────────────────────────────────────────────────────── */
    $('#lssForm').on('submit', function (e) {
        e.preventDefault();
        var ok = true;
        ok = fieldError($building, $building.val() ? '' : 'Select a building.') && ok;
        ok = fieldError($floor, $floor.val() ? '' : 'Select a floor.') && ok;
        ok = fieldError($room, $room.val() ? '' : 'Select a room.') && ok;

        var laptopCount  = laptopMap.getCount();
        var scriberCount = scriberMap.getCount();
        if (laptopCount === 0) {
            $('#lssSeatsError').text('Select at least one laptop seat.').removeClass('d-none');
            ok = false;
        } else {
            $('#lssSeatsError').addClass('d-none');
        }
        if (!ok) { return; }

        var b  = $building.val();
        var fl = $floor.val();
        var rm = $room.val();
        var rooms = ((ROOMS_BY_BUILDING_FLOOR[b] || {})[fl] || []);
        var found = rooms.find(function (r) { return r[0] === rm; });
        var type     = found ? found[1] : '';
        var capacity = found ? found[2] : '';
        var esc = function (t) { return $('<div>').text(t).html(); };

        if ($editingRow) {
            /* Update existing row in-place. */
            $editingRow.attr({
                'data-lss-building': b, 'data-lss-floor': fl,
                'data-lss-room': rm,    'data-lss-type': type,
                'data-lss-capacity': capacity,
                'data-lss-laptop': laptopCount, 'data-lss-scriber': scriberCount
            });
            $editingRow.find('.lss-cell-room').text(rm);
            $editingRow.find('.lss-cell-building').text(b);
            $editingRow.find('.lss-cell-floor').text(fl);
            $editingRow.find('.lss-cell-type').text(type);
            $editingRow.find('.lss-cell-capacity').text(capacity).attr('data-order', capacity);
            $editingRow.find('.lss-cell-laptop').html(laptopCount > 0 ? laptopCount : '<span class="lss-dash">&mdash;</span>').attr('data-order', laptopCount);
            $editingRow.find('.lss-cell-scriber').html(scriberCount > 0 ? scriberCount : '<span class="lss-dash">&mdash;</span>').attr('data-order', scriberCount);
            dt.row($editingRow).invalidate('dom').draw(false);
            bootstrap.Modal.getOrCreateInstance($modal[0]).hide();
            Swal.fire({ icon: 'success', title: 'Updated', text: 'Seat allocation updated.' });
        } else {
            /* Add new row. */
            var newId = 1 + Math.max(0, Math.max.apply(null,
                dt.rows().nodes().to$().map(function () {
                    return parseInt(this.getAttribute('data-coe-id'), 10) || 0;
                }).get()
            ));
            var $newRow = $(dt.row.add([
                esc(rm),
                esc(b),
                esc(fl),
                esc(type),
                capacity,
                laptopCount > 0 ? laptopCount : '<span class="lss-dash">&mdash;</span>',
                scriberCount > 0 ? scriberCount : '<span class="lss-dash">&mdash;</span>',
                '<div class="coe-act-group" role="group" aria-label="Row actions">' +
                '<button type="button" class="coe-act coe-act--edit">' +
                '<span class="coe-act__icon"><i class="bi bi-pencil" aria-hidden="true"></i></span>' +
                '<span class="coe-act__label">Edit</span></button>' +
                '<button type="button" class="coe-act coe-act--del">' +
                '<span class="coe-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>' +
                '<span class="coe-act__label">Delete</span></button></div>'
            ]).draw(false).node());

            $newRow.attr({
                'data-coe-id': newId,
                'data-lss-building': b, 'data-lss-floor': fl,
                'data-lss-room': rm,    'data-lss-type': type,
                'data-lss-capacity': capacity,
                'data-lss-laptop': laptopCount, 'data-lss-scriber': scriberCount
            });
            var $cells = $newRow.children('td');
            $cells.eq(COL.room).addClass('lss-cell-room coe-strong');
            $cells.eq(COL.building).addClass('lss-cell-building');
            $cells.eq(COL.floor).addClass('lss-cell-floor');
            $cells.eq(COL.type).addClass('lss-cell-type');
            $cells.eq(COL.capacity).addClass('coe-col-center lss-cell-capacity').attr('data-order', capacity);
            $cells.eq(COL.laptop).addClass('coe-col-center lss-cell-laptop').attr('data-order', laptopCount);
            $cells.eq(COL.scriber).addClass('coe-col-center lss-cell-scriber').attr('data-order', scriberCount);

            bootstrap.Modal.getOrCreateInstance($modal[0]).hide();
            Swal.fire({ icon: 'success', title: 'Added', text: 'Laptop & scriber seats added.' });
        }
    });
});
</script>
@endpush
