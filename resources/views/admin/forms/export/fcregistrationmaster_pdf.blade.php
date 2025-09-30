<!DOCTYPE html>
<html>

<head>
    <title>Registration PDF Export</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 6px;
            text-align: left;
        }
    </style>
</head>

<body>
    <h2>FC Registration Report</h2>
    <table>
        <thead>
            <tr>
                <th>S No</th>
                <th>Course Name</th>
                <th>Exemption Category</th>
                <th>Application Type</th>
                <th>Service</th>
                <th>Group Type</th>
                <th>Cadre</th>
                <th>Schema ID</th>
                <th>Display Name</th>
                <th>First Name</th>
                <th>Middle Name</th>
                <th>Last Name</th>
                <th>Email</th>
                <th>Contact No</th>
                <th>Rank</th>
                <th>Date of Birth</th>
                <th>Web Auth</th>
                <th>Exam Year</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($registrations as $reg)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $reg->course_name ?? '' }}</td>
                    <td>{{ $reg->exemption_name ?? '' }}</td>
                    <td>
                        @if ($reg->application_type == 1)
                            Registration
                        @elseif($reg->application_type == 2)
                            Exemption
                        @else
                            N/A
                        @endif
                    </td>
                    <td>{{ $reg->service_short_name ?? '' }}</td>
                    <td>{{ $reg->group_type ?? 'N/A' }}</td>
                    <td>{{ $reg->cadre_name ?? '' }}</td>
                    <td>{{ $reg->schema_id ?? '' }}</td>
                    <td>{{ $reg->display_name ?? '' }}</td>
                    <td>{{ $reg->first_name ?? '' }}</td>
                    <td>{{ $reg->middle_name ?? '' }}</td>
                    <td>{{ $reg->last_name ?? '' }}</td>
                    <td>{{ $reg->email ?? '' }}</td>
                    <td>{{ $reg->contact_no ?? '' }}</td>
                    <td>{{ $reg->rank ?? '' }}</td>
                    <td>{{ $reg->dob ?? '' }}</td>
                    <td>{{ $reg->web_auth ?? '' }}</td>
                    <td>{{ $reg->exam_year ?? '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
