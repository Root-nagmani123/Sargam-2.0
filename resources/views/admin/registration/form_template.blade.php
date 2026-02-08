<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Display</title>
    <style>
        body {
            font-family: Arial, sans-serif, dejavusans;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }

        .container {
            max-width: 100%;
            margin: 20px auto;
            padding: 10px;
            background: #fff;
            border-radius: 8px;
            box-sizing: border-box;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header img {
            max-width: 150px;
            margin: 10px auto;
        }

        .header h1 {
            font-size: 1.8em;
            color: #333;
            margin-bottom: 5px;
        }

        .header p {
            color: #555;
            margin: 2px;
        }

        .form-description {
            text-align: center;
            font-style: italic;
            color: #555;
            margin-bottom: 10px;
            margin-top: -10px;
        }

        .section-card {
            width: 100%;
            margin-bottom: 30px;
            padding: 2px;
            background-color: #fafafa;
            border-radius: 2px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .section-card h2 {
            font-size: 12px;
            color: #333;
            margin-bottom: 15px;
            background-color: rgb(235, 204, 198);
            text-transform: uppercase;
            border-bottom: 2px solid #004a93;
            padding: 10px;
        }

        .field-container {
            display: flex;
            margin-bottom: 15px;
            align-items: center;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }

        .field-container:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .label {
            width: 30%;
            font-weight: bold;
            color: #333;
        }

        .value {
            width: 70%;
            color: #555;
            padding-left: 10px;
        }

        .table-container {
            margin-top: 20px;
            width: 100%;
            border-collapse: collapse;
        }

        .table-container th,
        .table-container td {
            padding: 8px 12px;
            border: 1px solid #ddd;
            text-align: left;
        }

        .table-container th {
            padding: 10px;
            font-size: 12px;
        }

        .table-container td {
            background-color: #fff;
            color: #555;
        }

        .table-container tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .image-container {
            text-align: right;
            margin: 20px 0;
            margin-top: 10px;
        }

        .image-container img {
            max-width: 50px;
            height: 50px;
            border-radius: 8px;
        }

        .logo-container {
            width: 350px;
            margin: 10px auto;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .logo-container img {
            max-width: 100%;
            height: auto;
            display: block;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="logo-container">
                {{-- <img src="{{ $azadi_logo }}" alt="Logo"> --}}
            </div>
        </div>

        <div class="form-description">
            <h4 style="margin-top:-10px;">{{ $form_description }}</h4>
            <p style="text-align: center; font-size: 14px; color: #555; margin-top: -5px;">
                From {{ $form_date_range }}
            </p>
        </div>

        <div class="image-container">
            @if ($logo_path)
                <img src="{{ $logo_path }}" alt="User Image"
                    style="width: 130px; height: 130px; border-radius: 8px; margin-top:-80px; margin-right:20px;">
            @endif
            <p style="text-align: right; font-weight: bold; margin-top: 10px; margin-right: 45px;">
                {{ $user_name ?? 'Name Not Available' }}
            </p>
        </div>

        @foreach ($sections as $section_title => $fields)
            <div class="section-card">
                <h2>{{ $section_title }}</h2>

                @php
                    $tables = [];
                    $nonTables = [];

                    foreach ($fields as $field) {
                        if (isset($field['type']) && $field['type'] === 'table') {
                            $tables[] = $field;
                        } else {
                            $nonTables[] = $field;
                        }
                    }
                @endphp

                {{-- Render non-table fields --}}
                @if (count($nonTables) > 0)
                    <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                        <tbody>
                            @php
                                $columns = 2;
                                // filter out fields where value looks like a URL or contains "uploads"
                                $filteredFields = array_filter($nonTables, function ($field) {
                                    $val = $field['fieldvalue'] ?? '';
                                    return !(
                                        filter_var($val, FILTER_VALIDATE_URL) ||
                                        str_contains($val, 'uploads/') ||
                                        str_contains($val, 'http')
                                    );
                                });

                                $total_fields = count($filteredFields);
                                $rows = ceil($total_fields / $columns);
                                $index = 0;
                                $fieldsArray = array_values($filteredFields);
                            @endphp

                            @for ($r = 0; $r < $rows; $r++)
                                <tr>
                                    @for ($c = 0; $c < $columns; $c++)
                                        @if ($index < $total_fields)
                                            <td style="padding: 10px; text-align: left;">
                                                <strong><span>{{ $nonTables[$index]['label_en'] }}:</span></strong>
                                                {{ !empty($nonTables[$index]['fieldvalue']) ? $nonTables[$index]['fieldvalue'] : '-' }}
                                            </td>
                                            @php $index++; @endphp
                                        @else
                                            <td style="padding: 10px;"></td>
                                        @endif
                                    @endfor
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                @endif

                {{-- Render table fields --}}
                @if (count($tables) > 0)
                    @foreach ($tables as $field)
                        <h4 style="margin-top: 15px;">{{ $field['label_en'] }}</h4>
                        <table class="table-container"
                            style="margin-bottom: 20px; width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr>
                                    @foreach ($field['headers'] as $header)
                                        <th style="border: 1px solid #ccc; padding: 6px;">{{ $header }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($field['rows'] as $row)
                                    <tr>
                                        @foreach ($field['headers'] as $header)
                                            <td style="border: 1px solid #ccc; padding: 6px;">{{ $row[$header] ?? '-' }}
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endforeach
                @endif
            </div>
        @endforeach
    </div>
</body>

</html>

</html>
