<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Officer Bearers — LBSNAA</title>
    @include('admin.issue_management.partials.export_print_styles')
    <style>
        .col-sno     { width: 7%;  text-align: center; }
        .col-course  { width: 16%; }
        .col-society { width: 24%; }
        .col-role    { width: 18%; }
        .col-name    { width: 24%; }
        .col-ot      { width: 11%; text-align: center; }
    </style>
</head>
<body onload="window.print();">

    @include('admin.issue_management.partials.export_print_header', [
        'title'      => 'Officer Bearers',
        'exportDate' => $exportDate,
        'filterLine' => $filterLine,
        'total'      => $rows->count(),
    ])

    {{-- Same rows as OfficeBearerExport; both read OfficeBearerController::rows(). --}}
    <table class="ic-print-table">
        <thead>
            <tr>
                <th class="col-sno">S No.</th>
                <th class="col-course">Course Name</th>
                <th class="col-society">Club/Society/Association</th>
                <th class="col-role">Role</th>
                <th class="col-name">Officer Bearer Name</th>
                <th class="col-ot">OT Code</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $index => $row)
                <tr>
                    <td class="col-sno">{{ $index + 1 }}</td>
                    <td class="col-course">{{ $row->course_name }}</td>
                    <td class="col-society">{{ $row->club_society_name }}</td>
                    <td class="col-role">{{ $row->role_name }}</td>
                    <td class="col-name">{{ $row->officer_bearer_name }}</td>
                    <td class="col-ot">{{ $row->ot_code }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="ic-print-empty">No officer bearers to print</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="ic-print-foot">Sargam 2.0 · Communications · Lal Bahadur Shastri National Academy of Administration</div>
</body>
</html>
