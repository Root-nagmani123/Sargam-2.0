/**
 * COE examination-room seat layout — one numbering shared by the Laptop & Scriber
 * Seats master and the Venue Allocation seating plan, so seat 37 is the same desk
 * on both screens and in coe_seat_allocations.seat_number.
 *
 * A room is drawn as three blocks side by side (4 | 13 | 6 desks per row, the
 * design's hall). There are as many rows as the room's capacity needs; row A is
 * the front row by the podium. Numbering runs row A first, left block to right,
 * desk 1 upwards within a block, so seats 1..capacity always exist and nothing
 * past capacity does.
 *
 *   CoeSeatMap.layout(capacity) → { rows: ['A','B',…], blocks: [{ letter, label, cols,
 *                                    seats: { A: [{number,label,row,col}|null, …], … } }] }
 *   CoeSeatMap.label(number)    → 'B-A07'
 *   CoeSeatMap.render($map, room) draws the room into $map (jQuery) with the .esp-*
 *                                 classes in coe-admin.css; room = { capacity, seats:
 *                                 [{seat, roll, name}], laptop_seats, scriber_seats }.
 *                                 Returns { seat number → person }.
 */
(function (window) {
    'use strict';

    var BLOCKS = [
        { letter: 'A', label: 'BLOCK A', cols: 4 },
        { letter: 'B', label: 'BLOCK B (CENTRE)', cols: 13 },
        { letter: 'C', label: 'BLOCK C', cols: 6 }
    ];
    var PER_ROW = BLOCKS.reduce(function (n, b) { return n + b.cols; }, 0);

    function rowLetter(i) {
        // A..Z, then AA, AB … — a 600-seat hall needs 27 rows.
        var s = '';
        i += 1;
        while (i > 0) {
            var m = (i - 1) % 26;
            s = String.fromCharCode(65 + m) + s;
            i = Math.floor((i - 1) / 26);
        }
        return s;
    }

    function position(number) {
        var index = number - 1;
        var row = Math.floor(index / PER_ROW);
        var within = index % PER_ROW;
        for (var b = 0; b < BLOCKS.length; b++) {
            if (within < BLOCKS[b].cols) {
                return { block: b, row: row, col: within + 1 };
            }
            within -= BLOCKS[b].cols;
        }
        return null;
    }

    function label(number) {
        var p = position(number);
        if (!p) { return String(number); }
        return BLOCKS[p.block].letter + '-' + rowLetter(p.row) + String(p.col).padStart(2, '0');
    }

    function layout(capacity) {
        capacity = Math.max(0, parseInt(capacity, 10) || 0);
        var rowCount = Math.ceil(capacity / PER_ROW);
        var rows = [];
        for (var r = 0; r < rowCount; r++) { rows.push(rowLetter(r)); }

        var blocks = BLOCKS.map(function (b) {
            return { letter: b.letter, label: b.label, cols: b.cols, seats: {} };
        });

        rows.forEach(function (letter, r) {
            var offset = r * PER_ROW;
            blocks.forEach(function (block, b) {
                var seats = [];
                for (var c = 1; c <= block.cols; c++) {
                    var number = offset + c;
                    seats.push(number <= capacity
                        ? { number: number, label: label(number), row: letter, col: c }
                        : null);
                }
                block.seats[letter] = seats;
                offset += block.cols;
            });
        });

        return { rows: rows, blocks: blocks };
    }

    function render($map, room) {
        var $ = window.jQuery;
        var bySeat = {}, laptop = {}, scriber = {};
        (room.seats || []).forEach(function (s) { bySeat[s.seat] = s; });
        (room.laptop_seats || []).forEach(function (n) { laptop[n] = true; });
        (room.scriber_seats || []).forEach(function (n) { scriber[n] = true; });

        var lay = layout(room.capacity);
        $map.empty();
        lay.blocks.forEach(function (blk, bi) {
            var $block = $('<div class="esp-block"></div>');
            $('<div class="esp-block-label"></div>').text(blk.label).appendTo($block);
            // Back row on top, row A by the podium at the bottom.
            lay.rows.slice().reverse().forEach(function (letter) {
                var $row = $('<div class="esp-row"></div>');
                $('<span class="esp-row-label"></span>').text(letter).appendTo($row);
                blk.seats[letter].forEach(function (seat) {
                    var $seat = $('<button type="button" class="esp-seat"></button>');
                    if (!seat) { $seat.addClass('is-spacer').attr('tabindex', -1); $row.append($seat); return; }
                    var who = bySeat[seat.number];
                    $seat.text(letter + String(seat.col).padStart(2, '0')).attr('data-seat', seat.number)
                        .addClass(who ? 'is-assigned' : 'is-empty')
                        .toggleClass('is-laptop', !!laptop[seat.number])
                        .toggleClass('is-scriber', !!scriber[seat.number])
                        .attr('title', seat.label + (who ? ' — ' + who.roll + ' – ' + who.name : ' — empty'));
                    $row.append($seat);
                });
                $block.append($row);
            });
            $map.append($block);
            if (bi < lay.blocks.length - 1) { $map.append('<div class="esp-sep"></div>'); }
        });

        return bySeat;
    }

    window.CoeSeatMap = { layout: layout, label: label, render: render, perRow: PER_ROW };
})(window);
