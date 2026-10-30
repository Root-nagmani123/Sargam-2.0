<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Club/ Society Role Programme Mapping — LBSNAA</title>
    @include('admin.issue_management.partials.export_print_styles')
    <style>
        /* Column widths are the only per-report part of the table. */
        .col-sno    { width: 7%;  text-align: center; }
        .col-course { width: 22%; }
        .col-club   { width: 26%; }
        .col-roles  { width: 45%; word-break: break-word; }
    </style>
</head>
<body onload="window.print();">

    @include('admin.issue_management.partials.export_print_header', [
        'title'      => 'Club/ Society Role Programme Mapping',
        'exportDate' => $exportDate,
        'filterLine' => $filterLine,
        'total'      => $rows->count(),
    ])

    {{-- Same columns as ClubSocietyRoleProgrammeMappingExport, and both draw
         their rows from the controller's mappingRows(). --}}
    <table class="ic-print-table">
        <thead>
            <tr>
                <th class="col-sno">S. No.</th>
                <th class="col-course">Course Name</th>
                <th class="col-club">Club/Society/Association</th>
                <th class="col-roles">Role</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $index => $row)
                <tr>
                    <td class="col-sno">{{ $index + 1 }}</td>
                    <td class="col-course">{{ $row->course_name }}</td>
                    <td class="col-club">{{ $row->club_society_name }}</td>
                    <td class="col-roles">{{ $row->role_names }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="ic-print-empty">No role mappings to print</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="ic-print-foot">Sargam 2.0 · Communications · Lal Bahadur Shastri National Academy of Administration</div>
</body>
</html>
