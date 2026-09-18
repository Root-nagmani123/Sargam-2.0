<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Examination Drive Configuration — LBSNAA</title>
    <style>
        * { font-family: Arial, Helvetica, sans-serif; box-sizing: border-box; }
        body { margin: 0; padding: 16px; color: #1f2937; }

        .print-hdr { text-align: center; border-bottom: 2px solid #003366; padding-bottom: 8px; margin-bottom: 12px; }
        .print-hdr .inst { font-size: 16px; font-weight: bold; color: #003366; }
        .print-hdr .sub { font-size: 11px; color: #4b5563; margin-top: 2px; }

        .report-title { text-align: center; font-size: 14px; font-weight: bold; color: #003366; margin: 6px 0 4px; }
        .meta { text-align: center; font-size: 10px; color: #6b7280; margin-bottom: 6px; }
        .total { text-align: center; font-size: 11px; font-weight: bold; color: #003366;
                 background: #eef2f8; padding: 4px 0; margin-bottom: 10px; }

        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #cccccc; padding: 6px 8px; text-align: left; font-size: 11px; }
        thead th { background: #003366; color: #ffffff; }
        tbody tr:nth-child(even) { background: #f4f7fb; }
        .text-center { text-align: center; }
        .empty { text-align: center; padding: 20px; color: #6b7280; }

        .foot { margin-top: 10px; text-align: center; font-size: 9px; color: #6b7280; }

        @media print {
            body { padding: 0; }
        }
    </style>
</head>
<body onload="window.print();">
    <div class="print-hdr">
        <div class="inst">LAL BAHADUR SHASTRI NATIONAL ACADEMY OF ADMINISTRATION</div>
        <div class="sub">Mussoorie, Uttarakhand &nbsp;|&nbsp; Sargam 2.0</div>
    </div>

    <div class="report-title">EXAMINATION DRIVE CONFIGURATION</div>
    <div class="meta">Generated: {{ $exportDate }}</div>
    <div class="total">Total Records: {{ number_format($rows->count()) }}</div>

    <table>
        <thead>
            <tr>
                <th class="text-center">S. No.</th>
                <th>Examination Type</th>
                <th>Term</th>
                <th>Course</th>
                <th class="text-center">Phase</th>
                <th class="text-center">Academic Session</th>
                <th class="text-center">Start Date</th>
                <th class="text-center">End Date</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $index => $row)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $row->examinationType->exam_type_name ?? '-' }}</td>
                    <td>{{ $row->term->term_name ?? '-' }}</td>
                    <td>{{ $row->course->couse_short_name ?? $row->course->course_name ?? '-' }}</td>
                    <td class="text-center">{{ $row->phase }}</td>
                    <td class="text-center">{{ $row->academic_session }}</td>
                    <td class="text-center">{{ $row->start_date ? \Carbon\Carbon::parse($row->start_date)->format('d-m-Y') : '' }}</td>
                    <td class="text-center">{{ $row->end_date ? \Carbon\Carbon::parse($row->end_date)->format('d-m-Y') : '' }}</td>
                    <td class="text-center">{{ \App\Models\ExaminationDrive::STATUS_LABELS[(int) $row->status] ?? 'Draft' }}</td>
                </tr>
            @empty
                <tr><td colspan="9" class="empty">No examination drives to print</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="foot">Sargam 2.0 · Lal Bahadur Shastri National Academy of Administration</div>
</body>
</html>
