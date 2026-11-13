<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Nomination Drive — LBSNAA</title>
    @include('admin.issue_management.partials.export_print_styles')
    <style>
        .col-sno    { width: 6%;  text-align: center; }
        .col-drive  { width: 28%; }
        .col-course { width: 16%; }
        .col-date   { width: 12%; text-align: center; }
        .col-status { width: 13%; text-align: center; }
    </style>
</head>
<body onload="window.print();">

    @include('admin.issue_management.partials.export_print_header', [
        'title'      => 'Nomination Drive',
        'exportDate' => $exportDate,
        'filterLine' => $filterLine,
        'total'      => $rows->count(),
    ])

    {{-- Same columns as NominationDriveExport; both read driveRows(). --}}
    <table class="ic-print-table">
        <thead>
            <tr>
                <th class="col-sno">S. No.</th>
                <th class="col-drive">Nomination Drive</th>
                <th class="col-course">Course Name</th>
                <th class="col-date">Start Date</th>
                <th class="col-date">End Date</th>
                <th class="col-status">Nomination Accept</th>
                <th class="col-status">Nomination Withdraw</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $index => $row)
                <tr>
                    <td class="col-sno">{{ $index + 1 }}</td>
                    <td class="col-drive">{{ $row->drive_name }}</td>
                    <td class="col-course">{{ $row->course_name }}</td>
                    <td class="col-date">{{ $row->start_date ? date('d-m-Y', strtotime($row->start_date)) : '' }}</td>
                    <td class="col-date">{{ $row->end_date ? date('d-m-Y', strtotime($row->end_date)) : '' }}</td>
                    <td class="col-status">{{ (int) $row->nomination_accept_status === 1 ? 'Enable' : 'Disable' }}</td>
                    <td class="col-status">{{ (int) $row->nomination_withdraw_status === 1 ? 'Enable' : 'Disable' }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="ic-print-empty">No nomination drives to print</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="ic-print-foot">Sargam 2.0 · Communications · Lal Bahadur Shastri National Academy of Administration</div>
</body>
</html>
