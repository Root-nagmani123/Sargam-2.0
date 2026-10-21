<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Election Drive — LBSNAA</title>
    @include('admin.issue_management.partials.export_print_styles')
    <style>
        .col-sno    { width: 6%;  text-align: center; }
        .col-drive  { width: 24%; }
        .col-nom    { width: 26%; }
        .col-course { width: 16%; }
        .col-status { width: 14%; text-align: center; }
    </style>
</head>
<body onload="window.print();">

    @include('admin.issue_management.partials.export_print_header', [
        'title'      => 'Election Drive',
        'exportDate' => $exportDate,
        'filterLine' => $filterLine,
        'total'      => $rows->count(),
    ])

    <table class="ic-print-table">
        <thead>
            <tr>
                <th class="col-sno">S. No.</th>
                <th class="col-drive">Election Drive</th>
                <th class="col-nom">Nomination Drive</th>
                <th class="col-course">Course Name</th>
                <th class="col-status">Election Publish</th>
                <th class="col-status">Result Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $index => $row)
                <tr>
                    <td class="col-sno">{{ $index + 1 }}</td>
                    <td class="col-drive">{{ $row->election_drive_name }}</td>
                    <td class="col-nom">{{ $row->nomination_drive_name }}</td>
                    <td class="col-course">{{ $row->course_name }}</td>
                    <td class="col-status">{{ (int) $row->election_publish_status === 1 ? 'Live' : 'Pending' }}</td>
                    <td class="col-status">{{ (int) $row->result_status === 1 ? 'Published' : 'Pending' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="ic-print-empty">No election drives to print</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="ic-print-foot">Sargam 2.0 · Communications · Lal Bahadur Shastri National Academy of Administration</div>
</body>
</html>
