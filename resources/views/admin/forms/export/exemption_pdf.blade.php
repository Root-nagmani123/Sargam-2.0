<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Exemption Data PDF</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>

    <h2>Exemption Submissions</h2>

    <table>
        <thead>
            <tr>
                <th>S.No</th>
                <th>User Name</th>
                <th>Contact No</th>
                <th>Web Code</th>
                <th>Exemption Category</th>
                <th>Medical Document</th>
                <th>Submitted On</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($submissions as $index => $data)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $data->user_name ?? 'N/A' }}</td>
                    <td>{{ $data->contact_no ?? 'N/A' }}</td>
                    <td>{{ $data->web_auth ?? 'N/A' }}</td>
                    <td>{{ $data->Exemption_short_name ?? 'N/A' }}</td>
                    <td>
                        {{ $data->medical_exemption_doc ? 'Available' : 'N/A' }}
                    </td>
                    <td>{{ \Carbon\Carbon::parse($data->created_date)->format('d-m-Y H:i') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>
