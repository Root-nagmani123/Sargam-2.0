<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Student Enrollment Report</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #333;
            padding: 6px;
            text-align: left;
        }

        th {
            background: #f2f2f2;
        }

        h2 {
            text-align: center;
        }
    </style>
</head>

<body>
    <h2>Student Enrollment Report</h2>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Student</th>
                <th>Email</th>
                <th>Course</th>
                <th>OT Code</th>
                <th>Status</th>
                <th>Created Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($students as $enrollment)
                <tr>
                    <td>{{ trim(($enrollment->student->first_name ?? '') . ' ' . ($enrollment->student->middle_name ?? '') . ' ' . ($enrollment->student->last_name ?? '')) }}
                    </td>
                    <td>{{ $enrollment->student->email ?? '-' }}</td>
                    <td>{{ $enrollment->course->course_name ?? 'N/A' }}</td>
                    <td>{{ $student->generated_OT_code ?? '-' }}</td>
                    <td>{{ $enrollment->active_inactive == 1 ? 'Active' : 'Inactive' }}</td>
                    <td>{{ $enrollment->created_date ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
