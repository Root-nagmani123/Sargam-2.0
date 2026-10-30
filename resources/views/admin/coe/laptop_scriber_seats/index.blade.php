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
.lss-seat--void      { visibility: hidden; pointer-events: none; }
.lss-map-empty       { font-size: 12px; color: var(--ds-ink-muted); padding: 1rem 0; }
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
                                <th scope="col" class="coe-col-center">Total Capacity</th>
                                <th scope="col" class="coe-col-center">Laptop Seats</th>
                                <th scope="col" class="coe-col-center">Scriber Seats</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr data-coe-id="{{ $row['venue_id'] }}"
                                    data-lss-capacity="{{ $row['capacity'] }}"
                                    data-lss-laptop-seats="{{ json_encode($row['laptop']) }}"
                                    data-lss-scriber-seats="{{ json_encode($row['scriber']) }}">
                                    <td class="lss-cell-room coe-strong">{{ $row['room'] }}</td>
                                    <td class="lss-cell-building">{{ $row['building'] !== '' ? $row['building'] : '—' }}</td>
                                    <td class="lss-cell-floor">{{ $row['floor'] !== '' ? $row['floor'] : '—' }}</td>
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
                    @if ($rooms->isEmpty())
                        {{-- No examination rooms yet: rooms come from Venue Master (building, floor, capacity). --}}
                        <div id="lssNoRooms" class="alert alert-warning d-flex align-items-start gap-2 mb-0" role="alert">
                            <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
                            <div>
                                <strong>No examination rooms found.</strong>
                                Laptop and scriber seats are marked on an examination room. Add rooms in
                                <a href="{{ route('Venue-Master.index') }}">Venue Master</a>
                                with a building, a floor and a capacity first, then come back here.
                            </div>
                        </div>
                    @else
                    {{-- Row 1: Building + Floor --}}
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="coe-form-label" for="lssBuilding">Building Name<span class="coe-req">*</span></label>
                            <select id="lssBuilding" name="building" class="form-select coe-control" required>
                                <option value="">Select Building Name</option>
                                @foreach ($rooms->unique('building_pk') as $r)
                                    <option value="{{ $r['building_pk'] }}">{{ $r['building'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="coe-form-label" for="lssFloor">Floor Name<span class="coe-req">*</span></label>
                            <select id="lssFloor" name="floor" class="form-select coe-control" required>
                                <option value="">Select Floor</option>
                            </select>
                        </div>
                    </div>

                    {{-- Row 2: Room --}}
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <label class="coe-form-label" for="lssRoom">Room Name<span class="coe-req">*</span></label>
                            <select id="lssRoom" name="venue_id" class="form-select coe-control" required>
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
                            <label class="coe-form-label mb-2">Scriber Seats</label>
                            <div id="lssScriberMap" class="lss-map-wrap"></div>
                            <div class="lss-count-badge mt-2">
                                Selected: <b id="lssScriberCount">0</b> scriber seat(s)
                            </div>
                        </div>
                    </div>
                    <div id="lssSeatsError" class="coe-field-error d-none mt-2">Select at least one laptop seat.</div>
                    @endif
                </div>
                <div class="modal-footer coe-modal-footer">
                    <button type="button" class="btn coe-btn coe-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    @if ($rooms->isNotEmpty())
                        <button type="submit" class="btn coe-btn coe-btn-primary" id="lssSubmit">Add Laptop &amp; Scriber Seats</button>
                    @endif
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
<script src="{{ asset('js/coe-seat-map.js') }}?v={{ @filemtime(public_path('js/coe-seat-map.js')) ?: time() }}"></script>
<script>
$(function () {
    'use strict';

    /* ── Data from server ─────────────────────────────────────────────── */
    /* Examination rooms: [{id, name, building_pk, building, floor_pk, floor, floor_order, capacity}] */
    var ROOMS = @json($rooms);
    var ENDPOINTS = {
        store:   @json(route('admin.coe.laptop-scriber-seats.store')),
        destroy: @json(route('admin.coe.laptop-scriber-seats.destroy', ['venue' => '__ID__']))
    };
    var CSRF = $('meta[name="csrf-token"]').attr('content') || '';

    function roomById(id) {
        id = parseInt(id, 10);
        for (var i = 0; i < ROOMS.length; i++) { if (ROOMS[i].id === id) { return ROOMS[i]; } }
        return null;
    }

    function ajax(opts) {
        return $.ajax($.extend({ headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } }, opts));
    }

    function seatList(attr) {
        try {
            var v = JSON.parse(attr || '[]');
            return Array.isArray(v) ? v.map(function (n) { return parseInt(n, 10); }).filter(function (n) { return n > 0; }) : [];
        } catch (e) { return []; }
    }

    /* ── Column map ───────────────────────────────────────────────────── */
    var COL = { room: 0, building: 1, floor: 2, capacity: 3, laptop: 4, scriber: 5, action: 6 };

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
        // v2: the Room Type column was removed, so v1's hidden indexes no longer line up.
        colvisKey: 'coeLaptopScriberSeats:hiddenColumns:v2',
        empty: { title: 'No Seat Allocations', zeroTitle: 'No Records Found',
                 text: 'Add laptop and scriber seats to get started.' }
    });

    /* ── Delete ────────────────────────────────────────────────────────── */
    CoeGrid.confirmDelete({
        table: '#lssTable',
        modal: '#lssDeleteModal',
        confirm: '#lssDeleteConfirm',
        onConfirm: function ($row) {
            ajax({
                url: ENDPOINTS.destroy.replace('__ID__', encodeURIComponent($row.attr('data-coe-id'))),
                method: 'DELETE'
            }).done(function (res) {
                dt.row($row).remove().draw(false);
                Swal.fire({ icon: 'success', title: 'Deleted', text: (res && res.message) || 'Record deleted.' });
            }).fail(function (xhr) {
                Swal.fire({ icon: 'error', title: 'Error',
                    text: (xhr.responseJSON && xhr.responseJSON.message) || 'Something went wrong.' });
            });
        }
    });

    /* ── Seat map ─────────────────────────────────────────────────────── */
    /*
     * Drawn from CoeSeatMap.layout(capacity) (public/js/coe-seat-map.js) — the
     * numbering the seat allocator and Venue Allocation use, so data-seat is the
     * real seat number. Row A is the front row by the podium, so rows are drawn
     * back-to-front (podium at the bottom).
     */
    function buildSeatMap(container, onChange) {
        var $wrap = $(container);
        var selected = {};
        var blocked = {};
        var $map = null;

        function count() { return Object.keys(selected).length; }

        function render(capacity) {
            selected = {};
            blocked = {};
            $wrap.empty();
            capacity = parseInt(capacity, 10) || 0;
            if (capacity < 1) {
                $map = null;
                $wrap.append('<div class="lss-map-empty">Select a room to see its seats.</div>');
                onChange();
                return;
            }

            var layout = CoeSeatMap.layout(capacity);
            var rows = layout.rows.slice().reverse();

            $map = $('<div class="lss-map"></div>');
            $map.append(
                '<div class="lss-map-header">' +
                '<i class="bi bi-arrow-down-short" aria-hidden="true"></i> ENTRANCE ' +
                '<i class="bi bi-arrow-down-short" aria-hidden="true"></i></div>'
            );

            var $blocks = $('<div class="lss-blocks"></div>').appendTo($map);

            layout.blocks.forEach(function (block, bi) {
                var $block = $('<div class="lss-block"></div>').appendTo($blocks);
                $('<div class="lss-block-label"></div>').text(block.label).appendTo($block);

                if (bi < layout.blocks.length - 1) {
                    $blocks.append('<div class="lss-sep"></div>');
                }

                rows.forEach(function (rowLetter) {
                    var $row = $('<div class="lss-row"></div>');
                    $('<div class="lss-row-label"></div>').text(rowLetter).appendTo($row);

                    block.seats[rowLetter].forEach(function (seat) {
                        if (!seat) {
                            // Past capacity: keep the grid aligned, no desk.
                            $row.append('<span class="lss-seat lss-seat--void" aria-hidden="true"></span>');
                            return;
                        }
                        var text = seat.row + String(seat.col).padStart(2, '0');
                        $('<button type="button" class="lss-seat"></button>')
                            .attr({ 'data-seat': seat.number, 'title': seat.label + ' (seat ' + seat.number + ')' })
                            .text(text)
                            .appendTo($row);
                    });
                    $block.append($row);
                });
            });

            $map.append('<div class="lss-podium"><i class="bi bi-speaker" aria-hidden="true"></i> SPEAKER / PODIUM</div>');
            $wrap.append($map);
            onChange();
        }

        $wrap.on('click', '.lss-seat[data-seat]:not(.is-disabled)', function () {
            var n = parseInt($(this).attr('data-seat'), 10);
            if (selected[n]) {
                delete selected[n];
                $(this).removeClass('is-selected');
            } else {
                selected[n] = true;
                $(this).addClass('is-selected');
            }
            onChange();
        });

        render(0);

        return {
            render: render,
            getCount: count,
            /** Sorted seat numbers. */
            getSelected: function () {
                return Object.keys(selected).map(Number).sort(function (a, b) { return a - b; });
            },
            /** Select exactly these seat numbers (seats not on the map are ignored). */
            setSelected: function (numbers) {
                selected = {};
                if ($map) {
                    $map.find('.lss-seat[data-seat]').removeClass('is-selected');
                    (numbers || []).forEach(function (n) {
                        var $s = $map.find('.lss-seat[data-seat="' + parseInt(n, 10) + '"]');
                        if ($s.length && !blocked[n]) {
                            selected[n] = true;
                            $s.addClass('is-selected');
                        }
                    });
                }
                onChange();
            },
            /** Seats taken by the other map: shown disabled, not selectable. */
            setBlocked: function (numbers) {
                blocked = {};
                (numbers || []).forEach(function (n) { blocked[n] = true; });
                if ($map) {
                    $map.find('.lss-seat[data-seat]').each(function () {
                        $(this).toggleClass('is-disabled', !!blocked[$(this).attr('data-seat')]);
                    });
                }
            }
        };
    }

    if (!ROOMS.length) {
        /* Empty state is rendered server-side in the modal; only the open button is wired. */
        $('#lssAddBtn').on('click', function () {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('lssFormModal')).show();
        });
        return;
    }

    /* A seat chosen on one map is disabled on the other. */
    var laptopMap, scriberMap;
    laptopMap = buildSeatMap('#lssLaptopMap', function () {
        $('#lssLaptopCount').text(laptopMap ? laptopMap.getCount() : 0);
        if (scriberMap && laptopMap) { scriberMap.setBlocked(laptopMap.getSelected()); }
    });
    scriberMap = buildSeatMap('#lssScriberMap', function () {
        $('#lssScriberCount').text(scriberMap ? scriberMap.getCount() : 0);
        if (laptopMap && scriberMap) { laptopMap.setBlocked(scriberMap.getSelected()); }
    });

    /* Saved layouts by venue id, so picking a room that already has seats shows them. */
    var SAVED = {};
    $table.find('tbody tr[data-coe-id]').each(function () {
        SAVED[$(this).attr('data-coe-id')] = {
            laptop: seatList($(this).attr('data-lss-laptop-seats')),
            scriber: seatList($(this).attr('data-lss-scriber-seats'))
        };
    });

    /* ── Dependent selects: Building → Floor → Room ───────────────────── */
    var $building = $('#lssBuilding');
    var $floor    = $('#lssFloor');
    var $room     = $('#lssRoom');
    var $modal    = $('#lssFormModal');

    [$building, $floor, $room].forEach(function ($s) {
        $s.select2({ width: '100%', dropdownParent: $modal });
    });

    /* Floors are not tied to buildings in the schema: a building's floors are the floors that have an exam room in it. */
    function fillFloors() {
        var b = parseInt($building.val(), 10);
        var seen = {};
        var floors = [];
        ROOMS.forEach(function (r) {
            if (r.building_pk === b && !seen[r.floor_pk]) {
                seen[r.floor_pk] = true;
                floors.push(r);
            }
        });
        floors.sort(function (x, y) { return (x.floor_order - y.floor_order) || x.floor.localeCompare(y.floor); });
        $floor.empty().append(new Option('Select Floor', '', true, true));
        floors.forEach(function (r) { $floor.append(new Option(r.floor, r.floor_pk)); });
        $floor.trigger('change.select2');
        fillRooms();
    }

    function fillRooms() {
        var b = parseInt($building.val(), 10), f = parseInt($floor.val(), 10);
        $room.empty().append(new Option('Select Room', '', true, true));
        ROOMS.forEach(function (r) {
            if (r.building_pk === b && r.floor_pk === f) {
                var text = r.name + ' (capacity ' + r.capacity + ')' + (SAVED[r.id] ? ' — seats already set' : '');
                $room.append(new Option(text, r.id));
            }
        });
        $room.trigger('change.select2');
        showRoom();
    }

    /* Draw both maps for the chosen room and select its saved seats, if any. */
    function showRoom() {
        var room = roomById($room.val());
        laptopMap.render(room ? room.capacity : 0);
        scriberMap.render(room ? room.capacity : 0);
        var saved = room ? SAVED[room.id] : null;
        if (saved) {
            laptopMap.setSelected(saved.laptop);
            scriberMap.setSelected(saved.scriber);
        }
        $('#lssSeatsError').addClass('d-none');
    }

    $building.on('change', function () { fieldError($building, ''); fillFloors(); });
    $floor.on('change', function () { fieldError($floor, ''); fillRooms(); });
    $room.on('change', function () { fieldError($room, ''); showRoom(); });

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
    var isEdit = false;

    function openForm($row) {
        isEdit = !!$row;
        $('#lssFormModalLabel').text(isEdit ? 'Edit Laptop & Scriber Seats' : 'Add Laptop & Scriber Seats');
        $('#lssSubmit').text(isEdit ? 'Update' : 'Add Laptop & Scriber Seats');

        var room = isEdit ? roomById($row.attr('data-coe-id')) : null;
        if (isEdit && !room) {
            Swal.fire({ icon: 'error', title: 'Error', text: 'This room is no longer an examination room in Venue Master.' });
            return;
        }

        if (room) {
            $building.val(String(room.building_pk)).trigger('change.select2');
            fillFloors();
            $floor.val(String(room.floor_pk)).trigger('change.select2');
            fillRooms();
            $room.val(String(room.id)).trigger('change.select2');
            showRoom();
            // Use the row's own seat lists — they are what is saved for this room.
            laptopMap.setSelected(seatList($row.attr('data-lss-laptop-seats')));
            scriberMap.setSelected(seatList($row.attr('data-lss-scriber-seats')));
        } else {
            document.getElementById('lssForm').reset();
            $building.val('').trigger('change.select2');
            fillFloors();
        }
        // Editing is for one room; pick a different room with Add.
        [$building, $floor, $room].forEach(function ($s) { $s.prop('disabled', isEdit); });
        $('#lssSeatsError').addClass('d-none');
        bootstrap.Modal.getOrCreateInstance($modal[0]).show();
    }

    $('#lssAddBtn').on('click', function () { openForm(null); });
    $table.on('click', '.coe-act--edit', function () { openForm($(this).closest('tr')); });

    $modal.on('hidden.bs.modal', function () {
        isEdit = false;
        [$building, $floor, $room].forEach(function ($s) { $s.prop('disabled', false); });
        laptopMap.render(0);
        scriberMap.render(0);
        $modal.find('.coe-field-error').not('#lssSeatsError').remove();
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

        var laptop  = laptopMap.getSelected();
        var scriber = scriberMap.getSelected();
        if (laptop.length === 0) {
            $('#lssSeatsError').text('Select at least one laptop seat.').removeClass('d-none');
            ok = false;
        } else {
            $('#lssSeatsError').addClass('d-none');
        }
        if (!ok) { return; }

        var $submit = $('#lssSubmit').prop('disabled', true);
        ajax({
            url: ENDPOINTS.store,
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ venue_id: parseInt($room.val(), 10), laptop_seats: laptop, scriber_seats: scriber })
        }).done(function (res) {
            bootstrap.Modal.getOrCreateInstance($modal[0]).hide();
            Swal.fire({ icon: 'success', title: isEdit ? 'Updated' : 'Saved',
                        text: (res && res.message) || 'Laptop & scriber seats saved.' })
                .then(function () { window.location.reload(); });
        }).fail(function (xhr) {
            if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                var errors = xhr.responseJSON.errors;
                var seatMsgs = [];
                Object.keys(errors).forEach(function (name) {
                    if (name === 'venue_id') {
                        fieldError($room, errors[name][0]);
                    } else if (name.indexOf('laptop_seats') === 0 || name.indexOf('scriber_seats') === 0) {
                        seatMsgs.push(errors[name][0]);
                    }
                });
                if (seatMsgs.length) { $('#lssSeatsError').text(seatMsgs.join(' ')).removeClass('d-none'); }
                if (!errors.venue_id && !seatMsgs.length) {
                    Swal.fire({ icon: 'error', title: 'Error', text: xhr.responseJSON.message || 'Please check the form.' });
                }
                return;
            }
            Swal.fire({ icon: 'error', title: 'Error',
                text: (xhr.responseJSON && xhr.responseJSON.message) || 'Something went wrong.' });
        }).always(function () { $submit.prop('disabled', false); });
    });
});
</script>
@endpush
