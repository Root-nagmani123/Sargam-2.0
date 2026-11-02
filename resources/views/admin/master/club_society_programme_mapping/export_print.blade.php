<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Club/ Society Programme Mapping — LBSNAA</title>
    @include('admin.issue_management.partials.export_print_styles')
    <style>
        /* Column widths are the only per-report part of the table. */
        .col-sno    { width: 8%;  text-align: center; }
        .col-course { width: 27%; }
        .col-clubs  { width: 65%; }

        /* The club list is long — let it wrap rather than blow the page width. */
        .col-clubs { word-break: break-word; }
    </style>
</head>
<body onload="window.print();">

    @include('admin.issue_management.partials.export_print_header', [
        'title'      => 'Club/ Society Programme Mapping',
        'exportDate' => $exportDate,
        'filterLine' => $filterLine,
        'total'      => $rows->count(),
    ])

    {{-- Same columns as ClubSocietyProgrammeMappingExport, and both draw their
         rows from ClubSocietyProgrammeMappingController::mappingRows(). --}}
    <table class="ic-print-table">
        <thead>
            <tr>
                <th class="col-sno">S. No.</th>
                <th class="col-course">Course Name</th>
                <th class="col-clubs">Club/Society/Association</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $index => $row)
                <tr>
                    <td class="col-sno">{{ $index + 1 }}</td>
                    <td class="col-course">{{ $row->course_name }}</td>
                    <td class="col-clubs">{{ $row->club_names }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="ic-print-empty">No mappings to print</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="ic-print-foot">Sargam 2.0 · Communications · Lal Bahadur Shastri National Academy of Administration</div>
</body>
</html>
