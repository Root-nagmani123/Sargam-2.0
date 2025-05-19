<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Display</title>
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
            @if($logo_path)
                {{-- <img src="{{ asset('storage/'.$logo_path) }}" alt="User Image" style="width: 130px; height: 130px; border-radius: 8px; margin-top:-80px; margin-right:20px;"> --}}
                {{-- <img src="data:image/png;base64,{{ base64_encode(file_get_contents(storage_path('app/public/'.$logo_path))) }}" alt="User Image" style="width: 130px; height: 130px; border-radius: 8px; margin-top:-80px; margin-right:20px;"> --}}
                <img src="{{ $logo_path }}" alt="User Image" style="width: 130px; height: 130px; border-radius: 8px; margin-top:-80px; margin-right:20px;">

                @endif
            <p style="text-align: right; font-weight: bold; margin-top: 10px;margin-right: 45px;">
                {{ $user_name ?? 'Name Not Available' }}
            </p>
        </div>

        @foreach($sections as $section_title => $fields)
        <div class="section-card">
            <h2>{{ $section_title }}</h2>
    
            @if(isset($fields[0]) && isset($fields[0]['type']) && $fields[0]['type'] === 'table')
                @foreach($fields as $field)
                    @if($field['type'] === 'table')
                        <table class="table-container" style="margin-bottom: 20px;">
                            <thead>
                                <tr>
                                    @foreach($field['headers'] as $header)
                                        <th>{{ $header }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($field['rows'] as $row)
                                    <tr>
                                        @foreach($field['headers'] as $header)
                                            <td>{{ $row[$header] ?? '-' }}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                @endforeach
            @else
                <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                    <tbody>
                        @php
                            $columns = 2;
                            $total_fields = count($fields);
                            $rows = ceil($total_fields / $columns);
                            $index = 0;
                        @endphp
    
                        @for($r = 0; $r < $rows; $r++)
                            <tr>
                                @for($c = 0; $c < $columns; $c++)
                                    @if($index < $total_fields)
                                        <td style="padding: 10px; text-align: left;">
                                            <strong><span>{{ $fields[$index]['label_en'] }}:</span></strong>
                                            {{ !empty($fields[$index]['fieldvalue']) ? $fields[$index]['fieldvalue'] : '-' }}
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
        </div>
    @endforeach
     
    </div>
</body>
</html>