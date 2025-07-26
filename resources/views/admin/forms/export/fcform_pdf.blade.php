<!DOCTYPE html>
<html>
<head>
    <title>Form Data</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        th, td {
            border: 1px solid #333;
            padding: 5px;
            text-align: left;
        }
    </style>
</head>
<body>
    <h3>{{ $formName ?? 'Form Submission List' }}</h3>

    <table>
        <thead>
            <tr>
                @foreach($fields as $field)
                    <th>{{ ucfirst($field) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($records as $record)
                <tr>
                    @foreach($fields as $field)
                        <td>{{ $record[$field] ?? '' }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
