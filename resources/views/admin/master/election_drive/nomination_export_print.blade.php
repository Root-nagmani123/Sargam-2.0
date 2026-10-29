<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $drive->election_drive_name }} — Nominations — LBSNAA</title>
    @include('admin.issue_management.partials.export_print_styles')
    <style>
        .col-sno     { width: 7%;  text-align: center; }
        .col-society { width: 26%; }
        .col-name    { width: 27%; }
        .col-ot      { width: 14%; text-align: center; }
        .col-num     { width: 14%; text-align: center; }
        .col-status  { width: 12%; text-align: center; }

        /* Post band above each group, mirroring the screen. */
        .ed-post-band {
            margin: 14px 0 4px;
            padding: 5px 8px;
            background: #eef2f7;
            border: 1px solid #d6dee8;
            font-weight: bold;
            font-size: 11px;
        }
    </style>
</head>
<body onload="window.print();">

    @include('admin.issue_management.partials.export_print_header', [
        'title'      => 'Nominations — ' . $drive->election_drive_name,
        'exportDate' => $exportDate,
        'filterLine' => null,
        'total'      => $groups->flatten()->count(),
    ])

    {{-- Grouped by post, the same shape the View Nominations screen renders. --}}
    @forelse ($groups as $postName => $rows)
        <div class="ed-post-band">Post : {{ $postName }}</div>
        <table class="ic-print-table">
            <thead>
                <tr>
                    <th class="col-sno">S No.</th>
                    <th class="col-society">Club/Society/Association</th>
                    <th class="col-name">Nominee</th>
                    <th class="col-ot">OT Code</th>
                    <th class="col-num">Nominated By</th>
                    <th class="col-status">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $i => $row)
                    <tr>
                        <td class="col-sno">{{ $i + 1 }}</td>
                        <td class="col-society">{{ $row->club_society_name }}</td>
                        <td class="col-name">{{ $row->nominee_name }}</td>
                        <td class="col-ot">{{ $row->ot_code }}</td>
                        <td class="col-num">{{ $row->nominated_by_count }}</td>
                        <td class="col-status">{{ ucfirst((string) $row->status) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @empty
        <table class="ic-print-table">
            <tbody><tr><td class="ic-print-empty">No nominations to print</td></tr></tbody>
        </table>
    @endforelse

    <div class="ic-print-foot">Sargam 2.0 · Communications · Lal Bahadur Shastri National Academy of Administration</div>
</body>
</html>
