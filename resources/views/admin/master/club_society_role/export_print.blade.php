<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Define Club/ Society Role — LBSNAA</title>
    @include('admin.issue_management.partials.export_print_styles')
    <style>
        /* Column widths are the only per-report part of the table. */
        .col-sno    { width: 12%; text-align: center; }
        .col-name   { width: 68%; }
        .col-status { width: 20%; text-align: center; }
    </style>
</head>
<body onload="window.print();">

    @include('admin.issue_management.partials.export_print_header', [
        'title'      => 'Define Club/ Society Role',
        'exportDate' => $exportDate,
        'filterLine' => null,
        'total'      => $rows->count(),
    ])

    {{-- Same columns as ClubSocietyRoleMasterExport so the sheet and the
         printout can't drift apart. --}}
    <table class="ic-print-table">
        <thead>
            <tr>
                <th class="col-sno">S. No.</th>
                <th class="col-name">Club/Society/Association Role</th>
                <th class="col-status">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $index => $row)
                <tr>
                    <td class="col-sno">{{ $index + 1 }}</td>
                    <td class="col-name">{{ $row->club_society_role_name }}</td>
                    <td class="col-status">{{ (int) $row->active_inactive === 1 ? 'Active' : 'Inactive' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="ic-print-empty">No club / society roles to print</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="ic-print-foot">Sargam 2.0 · Communications · Lal Bahadur Shastri National Academy of Administration</div>
</body>
</html>
