<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Time Table</title>
    <style>
        * { font-family: 'DejaVu Sans', sans-serif; }
        body { margin: 0; padding: 18px 22px; color: #1f2937; font-size: 11px; }
        .header { text-align: center; border-bottom: 2px solid #004a93; padding-bottom: 8px; margin-bottom: 12px; }
        .header h1 { margin: 0 0 2px; font-size: 18px; color: #004a93; }
        .header .sub { font-size: 10px; color: #6b7280; }
        .meta { width: 100%; margin-bottom: 10px; font-size: 10px; }
        .meta td { padding: 2px 4px; }
        .meta .label { color: #6b7280; font-weight: bold; width: 110px; }
        table.grid { width: 100%; border-collapse: collapse; }
        table.grid th {
            background: #004a93; color: #fff; text-align: left;
            padding: 6px 8px; font-size: 10px; border: 1px solid #004a93;
        }
        table.grid td { padding: 5px 8px; border: 1px solid #e5e7eb; vertical-align: top; }
        table.grid tr:nth-child(even) td { background: #f8fbff; }
        .empty { text-align: center; padding: 24px; color: #6b7280; }
        .footer { margin-top: 10px; font-size: 9px; color: #9ca3af; text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Academic Time Table</h1>
        <div class="sub">Lal Bahadur Shastri National Academy of Administration</div>
    </div>

    <table class="meta">
        <tr>
            @if($course)
                <td class="label">Course Name</td>
                <td>{{ $course->course_name }}</td>
                <td class="label">Course Code</td>
                <td>{{ $course->couse_short_name }}</td>
                <td class="label">Year</td>
                <td>{{ $course->course_year }}</td>
            @else
                <td class="label">Period</td>
                <td colspan="5">{{ $rangeStart }} &ndash; {{ $rangeEnd }}</td>
            @endif
        </tr>
        @if($course)
        <tr>
            <td class="label">Period</td>
            <td colspan="5">{{ $rangeStart }} &ndash; {{ $rangeEnd }}</td>
        </tr>
        @endif
    </table>

    <table class="grid">
        <thead>
            <tr>
                <th style="width: 80px;">Date</th>
                <th style="width: 70px;">Day</th>
                <th style="width: 110px;">Session</th>
                <th>Topic</th>
                <th style="width: 150px;">Faculty</th>
                <th style="width: 110px;">Venue</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row->date }}</td>
                    <td>{{ $row->day }}</td>
                    <td>{{ $row->session }}</td>
                    <td>{{ $row->topic }}</td>
                    <td>{{ $row->faculty_name }}</td>
                    <td>{{ $row->venue_name }}</td>
                </tr>
            @empty
                <tr><td class="empty" colspan="6">No sessions scheduled for this period.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Generated on {{ now()->format('d M Y, h:i A') }}@if($studentName) &middot; {{ $studentName }}@endif
    </div>
</body>
</html>
