<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $drive->drive_name }} — Nominees — LBSNAA</title>
    @include('admin.issue_management.partials.export_print_styles')
    <style>
        .col-sno    { width: 6%;  text-align: center; }
        .col-society{ width: 20%; }
        .col-post   { width: 13%; }
        .col-name   { width: 21%; }
        .col-ot     { width: 10%; text-align: center; }
        .col-num    { width: 10%; text-align: center; }
        .col-status { width: 10%; text-align: center; }
    </style>
</head>
<body onload="window.print();">

    @include('admin.issue_management.partials.export_print_header', [
        'title'      => 'Nominations — ' . $drive->drive_name,
        'exportDate' => $exportDate,
        'filterLine' => null,
        'total'      => $rows->count(),
    ])

    {{-- Same columns as NominationDriveNomineeExport and the View screen. --}}
    <table class="ic-print-table">
        <thead>
            <tr>
                <th class="col-sno">S. No.</th>
                <th class="col-society">Society Name</th>
                <th class="col-post">Post</th>
                <th class="col-name">Nominee Name</th>
                <th class="col-ot">OT Code</th>
                <th class="col-num">No of Nominated By</th>
                <th class="col-num">Required Nomination</th>
                <th class="col-status">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $index => $row)
                <tr>
                    <td class="col-sno">{{ $index + 1 }}</td>
                    <td class="col-society">{{ $row->club_society_name }}</td>
                    <td class="col-post">{{ $row->post_name }}</td>
                    <td class="col-name">{{ $row->nominee_name }}</td>
                    <td class="col-ot">{{ $row->ot_code }}</td>
                    <td class="col-num">{{ $row->nominated_by_count }}</td>
                    <td class="col-num">{{ $row->required_nomination }}</td>
                    <td class="col-status">{{ ucfirst((string) $row->status) }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="ic-print-empty">No nominations received yet</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="ic-print-foot">Sargam 2.0 · Communications · Lal Bahadur Shastri National Academy of Administration</div>
</body>
</html>
