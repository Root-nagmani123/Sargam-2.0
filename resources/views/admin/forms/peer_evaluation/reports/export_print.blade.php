<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Evaluation Reports — LBSNAA</title>
    {{-- The branded print chrome is shared, not re-implemented: these two partials
         are pure presentation (title / date / filter line / total) and already
         serve every branded LBSNAA print sheet. --}}
    @include('admin.issue_management.partials.export_print_styles')
    <style>
        /* Column widths are the only per-report part of the table. */
        .col-sno     { width: 5%;  text-align: center; }
        .col-name    { width: 14%; }
        .col-code    { width: 8%;  text-align: center; }
        .col-group   { width: 10%; }
        .col-num     { width: 8%;  text-align: center; }
        .col-remarks { width: 22%; }
        .col-course  { width: 14%; }
        
        
        .col-status  { width: 9%;  text-align: center; }
        
    </style>
</head>
<body onload="window.print();">

    @include('admin.issue_management.partials.export_print_header', [
        'title'      => 'Evaluation Reports',
        'exportDate' => $exportDate,
        'filterLine' => filled($filterText) ? e($filterText) : null,
        'total'      => $rows->count(),
    ])

    {{-- Columns come from PeerEventController::exportColumnDefs(), already filtered
         to whatever is still ticked in the grid's Columns modal, so the printout
         matches the screen and can't drift from the CSV. Cells are keyed by column
         key — never by position. --}}
    <table class="ic-print-table">
        <thead>
            <tr>
                @foreach ($columns as $col)
                    <th class="{{ $col['class'] }}">{{ $col['heading'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $index => $row)
                <tr>
                    @foreach ($columns as $col)
                        <td class="{{ $col['class'] }}">{{ $col['value']($row, $index) }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) }}" class="ic-print-empty">No reports to print</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="ic-print-foot">Sargam 2.0 · Peer Evaluation · Lal Bahadur Shastri National Academy of Administration</div>
</body>
</html>
