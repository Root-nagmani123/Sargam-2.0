<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Course Participants</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { margin: 0; padding: 12px; color: #222; }
        h2 { text-align: center; margin: 0 0 4px; color: #004a93; font-size: 16px; }
        .meta { text-align: center; font-size: 11px; color: #555; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #444; padding: 5px 6px; font-size: 10px; text-align: left; }
        thead th { background-color: #004a93; color: #fff; text-align: center; }
        tbody td:first-child { text-align: center; }
        tbody tr:nth-child(even) { background-color: #f2f5fa; }
    </style>
</head>
<body>
    <h2>My Course Participant</h2>
    <div class="meta">Total Records: {{ $totalCount }}</div>

    @php $serial = 0; $chunks = $participants->chunk(35); $lastChunk = $chunks->count() - 1; @endphp

    @forelse($chunks as $ci => $chunk)
        <table @if($ci < $lastChunk) style="page-break-after: always;" @endif>
            <thead>
                <tr>
                    <th style="width:6%;">S.No</th>
                    <th>user_name</th>
                    <th>Name</th>
                    <th>ot code</th>
                    <th>email_id</th>
                    <th>mobile no</th>
                    <th>cadre</th>
                </tr>
            </thead>
            <tbody>
                @foreach($chunk as $row)
                    @php $student = $row->studentMaster ?? null; $serial++; @endphp
                    <tr>
                        <td>{{ $serial }}</td>
                        <td>{{ $student->user_id ?? 'N/A' }}</td>
                        <td>{{ $student->display_name ?? 'N/A' }}</td>
                        <td>{{ $student->generated_OT_code ?? 'N/A' }}</td>
                        <td>{{ $student->email ?? 'N/A' }}</td>
                        <td>{{ $student->contact_no ?? 'N/A' }}</td>
                        <td>{{ ($student && $student->cadre) ? $student->cadre->cadre_name : 'N/A' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @empty
        <table>
            <thead>
                <tr>
                    <th style="width:6%;">S.No</th>
                    <th>user_name</th>
                    <th>Name</th>
                    <th>ot code</th>
                    <th>email_id</th>
                    <th>mobile no</th>
                    <th>cadre</th>
                </tr>
            </thead>
            <tbody>
                <tr><td colspan="7" style="text-align:center;">No records found</td></tr>
            </tbody>
        </table>
    @endforelse
</body>
</html>
