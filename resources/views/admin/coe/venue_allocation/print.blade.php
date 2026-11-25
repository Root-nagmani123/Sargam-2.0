{{-- Venue Allocation · printable Seating Plan of one room (VenueAllocationController::printSheet).
     A standalone page, no app layout: LBSNAA header (the masters' print chrome), exam details,
     the hall with each desk's roll number, the seat list, invigilators and signatures.
     It prints itself once the logos have loaded. Seat numbering = seatLabel() /
     public/js/coe-seat-map.js: blocks A | B (centre) | C, row A by the podium. --}}
@php
    // Some course names are stored HTML-encoded ("&#039;"); show them as text.
    $text = fn ($v) => html_entity_decode((string) $v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $seats = $allocation['seats']->keyBy('seat');
    $laptopDesks = array_flip($allocation['laptop_seats']);
    $scriberDesks = array_flip($allocation['scriber_seats']);
    $capacity = (int) $allocation['capacity'];
    $perRow = array_sum($blocks);
    $rowCount = (int) ceil($capacity / max(1, $perRow));
    $rowLetter = function (int $i): string {
        $s = '';
        for ($i++; $i > 0; $i = intdiv($i - 1, 26)) {
            $s = chr(65 + ($i - 1) % 26) . $s;
        }

        return $s;
    };
    $blockNames = ['A' => 'Block A', 'B' => 'Block B (Centre)', 'C' => 'Block C'];
    $seated = $allocation['seats']->count();
    $laptopOts = $allocation['seats']->where('laptop', true)->count();
    $scriberOts = $allocation['seats']->where('scriber', true)->count();
    $title = 'Seating Plan — ' . $allocation['room'];
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    @include('admin.master.partials.export_print_styles', ['orientation' => 'landscape'])
    <style>
        body { background: #fff; }
        .sp-toolbar { display: flex; justify-content: flex-end; gap: 8px; margin-bottom: 12px; }
        .sp-toolbar button { font: inherit; font-size: 12px; padding: 6px 14px; border-radius: 6px; cursor: pointer;
                             border: 1px solid #003366; background: #003366; color: #fff; }
        .sp-toolbar button.sp-ghost { background: #fff; color: #003366; }

        .sp-status { display: inline-block; margin-left: 6px; padding: 1px 8px; border-radius: 10px; font-size: 9px;
                     vertical-align: middle; border: 1px solid currentColor; }
        .sp-status.is-frozen { color: #067647; }
        .sp-status.is-draft { color: #b54708; }

        /* ── Exam details ── */
        table.sp-info { width: 100%; border-collapse: collapse; margin: 4px 0 8px; font-size: 10px; table-layout: fixed; }
        table.sp-info td { border: 1px solid #dee2e6; padding: 4px 7px; vertical-align: top; }
        table.sp-info .k { width: 11%; background: #eef2f8; color: #003366; font-weight: bold;
                           -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        table.sp-info .v { width: 22.3%; overflow-wrap: anywhere; }

        .sp-stats { display: flex; gap: 8px; margin-bottom: 4px; }
        .sp-stat { flex: 1; border: 1px solid #dee2e6; border-radius: 4px; padding: 3px 8px; text-align: center; }
        .sp-stat b { display: block; font-size: 14px; color: #003366; }
        .sp-stat span { font-size: 9px; color: #6b7280; text-transform: uppercase; letter-spacing: .03em; }

        .sp-h { font-size: 11px; font-weight: bold; color: #003366; text-transform: uppercase; letter-spacing: .04em;
                border-left: 3px solid #003366; padding-left: 6px; margin: 8px 0 5px; }

        /* ── Hall ── */
        .sp-hall { border: 1px solid #c7d2e0; border-radius: 6px; padding: 8px 8px 6px; break-inside: avoid; page-break-inside: avoid; }
        .sp-marker { text-align: center; font-size: 9px; font-weight: bold; letter-spacing: .2em; color: #475467;
                     background: #f2f4f7; border-radius: 3px; padding: 2px 0; margin: 0 auto 4px; width: 40%;
                     -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .sp-marker.is-podium { margin: 4px auto 0; }
        .sp-blocks { display: flex; justify-content: center; gap: 10px; }
        .sp-block-label { text-align: center; font-size: 8px; font-weight: bold; color: #003366; margin-bottom: 3px; }
        table.sp-grid { border-collapse: separate; border-spacing: 2px 1px; }
        table.sp-grid th { font-size: 8px; color: #6b7280; font-weight: bold; padding: 0 2px; }
        .sp-desk { width: 38px; height: 22px; border: 1px solid #98a2b3; border-radius: 3px; text-align: center;
                   vertical-align: middle; padding: 0; position: relative; line-height: 1.1;
                   -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .sp-desk .n { display: block; font-size: 6.5px; color: #6b7280; }
        .sp-desk .r { display: block; font-size: 7.5px; font-weight: bold; color: #101828; white-space: nowrap; overflow: hidden; }
        .sp-desk.is-taken { border-color: #003366; background: #eef4ff; }
        .sp-desk.is-empty { border-style: dashed; border-color: #d0d5dd; background: #fff; }
        .sp-desk.is-empty .n { color: #b0b7c3; }
        .sp-desk.is-laptop { box-shadow: inset 0 -3px 0 #0e9384; }
        .sp-desk.is-scriber { box-shadow: inset 0 -3px 0 #7a5af8; }
        .sp-desk.is-none { border: none; background: none; }
        .sp-legend { display: flex; justify-content: center; gap: 14px; font-size: 8px; color: #475467; margin-top: 6px; }
        .sp-legend i { display: inline-block; width: 14px; height: 9px; border-radius: 2px; vertical-align: middle; margin-right: 3px;
                       border: 1px solid #98a2b3; -webkit-print-color-adjust: exact; print-color-adjust: exact; }

        /* ── Seat list + invigilators ── */
        table.mst-print-table td.c, table.mst-print-table th.c { text-align: center; }
        .sp-tag { display: inline-block; font-size: 8px; padding: 0 5px; border-radius: 8px; margin-right: 2px; border: 1px solid; }
        .sp-tag.is-laptop { color: #0e9384; }
        .sp-tag.is-scriber { color: #7a5af8; }
        .sp-tag.is-moved { color: #b54708; }
        .sp-cols { display: flex; gap: 14px; align-items: flex-start; }
        .sp-cols > div { flex: 1; min-width: 0; }

        /* Signatures and the footer travel together: no page with the footer alone. */
        .sp-end { break-inside: avoid; page-break-inside: avoid; }
        .sp-end .mst-print-foot { margin-top: 8px; }
        .sp-sign { display: flex; justify-content: space-between; gap: 30px; margin-top: 22px; }
        .sp-sign div { flex: 1; border-top: 1px solid #344054; padding-top: 4px; text-align: center; font-size: 9px; color: #344054; }

        @media print {
            .sp-toolbar { display: none; }
        }
    </style>
</head>
<body>
    <div class="sp-toolbar">
        <button type="button" class="sp-ghost" onclick="window.close()">Close</button>
        <button type="button" onclick="window.print()">Print</button>
    </div>

    @include('admin.master.partials.export_print_header', ['title' => 'Examination Seating Plan', 'exportDate' => $printedAt])

    <table class="sp-info">
        <tr>
            <td class="k">Examination Drive</td><td class="v">{{ $text($drive) ?: '—' }}</td>
            <td class="k">Course</td><td class="v">{{ $text($allocation['course']) }} ({{ $allocation['batch'] }})</td>
            <td class="k">Exam Date</td><td class="v">{{ $allocation['exam_date'] ?: '—' }}</td>
        </tr>
        <tr>
            <td class="k">Subject</td><td class="v">{{ $text($allocation['subject']) }}</td>
            <td class="k">Component</td><td class="v">{{ $text($allocation['component']) ?: '—' }}</td>
            <td class="k">Candidates</td><td class="v">{{ $allocation['category'] }}</td>
        </tr>
        <tr>
            <td class="k">Room</td>
            <td class="v"><strong>{{ $allocation['room'] }}</strong></td>
            <td class="k">Building / Floor</td>
            <td class="v">{{ collect([$allocation['building'], $allocation['floor']])->filter()->implode(' / ') ?: '—' }}</td>
            <td class="k">Status</td>
            <td class="v">{{ $allocation['source'] }}
                <span class="sp-status {{ $allocation['frozen'] ? 'is-frozen' : 'is-draft' }}">{{ $allocation['frozen'] ? 'Frozen' : 'Not frozen' }}</span>
            </td>
        </tr>
    </table>

    <div class="sp-stats">
        <div class="sp-stat"><b>{{ $capacity }}</b><span>Room Capacity</span></div>
        <div class="sp-stat"><b>{{ $seated }}</b><span>OTs Seated</span></div>
        <div class="sp-stat"><b>{{ max(0, $capacity - $seated) }}</b><span>Empty Desks</span></div>
        <div class="sp-stat"><b>{{ $laptopOts }}</b><span>Laptop OTs</span></div>
        <div class="sp-stat"><b>{{ $scriberOts }}</b><span>Scriber OTs</span></div>
    </div>

    <div class="sp-h">Seating Layout</div>
    <div class="sp-hall">
        <div class="sp-marker">&darr; ENTRANCE &darr;</div>
        <div class="sp-blocks">
            @php $offset = 0; @endphp
            @foreach ($blocks as $letter => $cols)
                <div>
                    <div class="sp-block-label">{{ strtoupper($blockNames[$letter] ?? 'Block ' . $letter) }}</div>
                    <table class="sp-grid">
                        {{-- Back row on top, row A by the podium at the bottom, as on screen. --}}
                        @for ($r = $rowCount - 1; $r >= 0; $r--)
                            @php $row = $rowLetter($r); @endphp
                            <tr>
                                <th>{{ $row }}</th>
                                @for ($c = 1; $c <= $cols; $c++)
                                    @php
                                        $n = $r * $perRow + $offset + $c;
                                        $who = $n <= $capacity ? $seats->get($n) : null;
                                    @endphp
                                    @if ($n > $capacity)
                                        <td class="sp-desk is-none"></td>
                                    @else
                                        <td class="sp-desk {{ $who ? 'is-taken' : 'is-empty' }} {{ isset($laptopDesks[$n]) ? 'is-laptop' : '' }} {{ isset($scriberDesks[$n]) ? 'is-scriber' : '' }}">
                                            <span class="n">{{ $row }}{{ str_pad((string) $c, 2, '0', STR_PAD_LEFT) }}</span>
                                            <span class="r">{{ $who['roll'] ?? '' }}</span>
                                        </td>
                                    @endif
                                @endfor
                            </tr>
                        @endfor
                    </table>
                </div>
                @php $offset += $cols; @endphp
            @endforeach
        </div>
        <div class="sp-marker is-podium">SPEAKER / PODIUM</div>
        <div class="sp-legend">
            <span><i style="background:#eef4ff;border-color:#003366"></i>Occupied (roll no.)</span>
            <span><i style="border-style:dashed;border-color:#d0d5dd"></i>Empty</span>
            <span><i style="box-shadow:inset 0 -3px 0 #0e9384"></i>Laptop desk</span>
            <span><i style="box-shadow:inset 0 -3px 0 #7a5af8"></i>Scriber desk</span>
        </div>
    </div>

    <div class="sp-cols">
        <div>
            <div class="sp-h">Seat List</div>
            <table class="mst-print-table">
                <thead>
                    <tr>
                        <th class="c" style="width:7%">S.No</th>
                        <th style="width:16%">Roll No.</th>
                        <th>Name</th>
                        <th class="c" style="width:10%">Seat</th>
                        <th style="width:17%">Block</th>
                        <th style="width:18%">Seat Type</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($allocation['seats'] as $i => $s)
                        @php $parts = explode('-', $s['label'], 2); @endphp
                        <tr>
                            <td class="c">{{ $i + 1 }}</td>
                            <td><strong>{{ $s['roll'] }}</strong></td>
                            <td>{{ $s['name'] }}</td>
                            <td class="c">{{ $parts[1] ?? $s['label'] }}</td>
                            <td>{{ $blockNames[$parts[0]] ?? $parts[0] }}</td>
                            <td>
                                @if ($s['laptop'])<span class="sp-tag is-laptop">Laptop</span>@endif
                                @if ($s['scriber'])<span class="sp-tag is-scriber">Scriber</span>@endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="mst-print-empty">Nobody is seated in this room.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($allocation['takes_invigilators'])
            <div style="flex:0 0 34%">
                <div class="sp-h">Invigilators</div>
                <table class="mst-print-table">
                    <thead>
                        <tr><th style="width:36%">Role</th><th>Name</th><th style="width:26%">Signature</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($allocation['invigilators'] as $inv)
                            <tr>
                                <td>{{ $inv['role'] }}</td>
                                <td>{{ $inv['name'] ?? '—' }}</td>
                                <td></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="sp-end">
        <div class="sp-sign">
            <div>Prepared By</div>
            <div>Chief Invigilator</div>
            <div>Controller of Examinations</div>
        </div>
        <div class="mst-print-foot">Sargam 2.0 · COE · Lal Bahadur Shastri National Academy of Administration · Printed {{ $printedAt }}</div>
    </div>

    <script>
        // Print once the logos are in, so they are not missing on paper.
        window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 150); });
    </script>
</body>
</html>
