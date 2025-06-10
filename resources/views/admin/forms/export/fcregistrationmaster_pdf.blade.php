<!DOCTYPE html>
<html>
<head>
    <title>Registration PDF Export</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 6px; text-align: left; }
    </style>
</head>
<body>
    <h2>FC Registration Report</h2>
    <table>
        <thead>
            <tr>
                <th>S No</th>
                <th>First Name</th>
                <th>Middle Name</th>
                <th>Last Name</th>
                <th>Email</th>
                <th>Contact No</th>
                <th>Rank</th>
                <th>Web Auth</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($registrations as $reg)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $reg->first_name }}</td>
                    <td>{{ $reg->middle_name }}</td>
                    <td>{{ $reg->last_name }}</td>
                    <td>{{ $reg->email }}</td>
                    <td>{{ $reg->contact_no }}</td>
                    <td>{{ $reg->rank }}</td>
                    <td>{{ $reg->web_auth }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
