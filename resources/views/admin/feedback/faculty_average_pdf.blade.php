<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Feedback Average Report</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #0b4f8a;
        }
        .header h1 {
            color: #0b4f8a;
            margin: 0 0 5px 0;
            font-size: 24px;
        }
        .header h3 {
            color: #0b4f8a;
            margin: 0 0 5px 0;
            font-size: 18px;
        }
        .header p {
            margin: 5px 0;
            color: #666;
            font-size: 12px;
        }
        .filters-section {
            background-color: #f8f9fa;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 5px;
            border-left: 5px solid #0b4f8a;
            page-break-inside: avoid;
        }
        .filters-section h4 {
            margin: 0 0 10px 0;
            color: #0b4f8a;
            font-size: 14px;
        }
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }
        .filter-item {
            font-size: 11px;
        }
        .filter-label {
            font-weight: bold;
            color: #555;
        }
        .program-title {
            text-align: center;
            margin: 15px 0;
            font-size: 16px;
            font-weight: bold;
            color: #0b4f8a;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            page-break-inside: auto;
        }
        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }
        thead {
            display: table-header-group;
        }
        tfoot {
            display: table-footer-group;
        }
        th {
            background-color: #0b4f8a;
            color: white;
            padding: 10px 8px;
            text-align: left;
            font-size: 11px;
            font-weight: bold;
            white-space: nowrap;
        }
        td {
            padding: 8px;
            border-bottom: 1px solid #dee2e6;
        }
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .percentage-good {
            color: #198754;
            font-weight: bold;
        }
        .percentage-average {
            color: #b45309;
            font-weight: bold;
        }
        .percentage-low {
            color: #dc3545;
            font-weight: bold;
        }
        .summary-section {
            margin-top: 25px;
            padding: 15px;
            background-color: #eef4fb;
            border-radius: 5px;
            page-break-inside: avoid;
        }
        .summary-section h4 {
            margin: 0 0 15px 0;
            color: #0b4f8a;
            font-size: 14px;
            border-bottom: 1px solid #0b4f8a;
            padding-bottom: 5px;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
        }
        .summary-item {
            text-align: center;
        }
        .summary-value {
            font-size: 18px;
            font-weight: bold;
            color: #0b4f8a;
            margin: 5px 0;
        }
        .summary-label {
            font-size: 11px;
            color: #666;
        }
        .footer {
            margin-top: 30px;
            text-align: right;
            font-size: 9px;
            color: #666;
            border-top: 1px solid #dee2e6;
            padding-top: 10px;
            position: fixed;
            bottom: 0;
            width: 100%;
        }
        .page-number:before {
            content: "Page " counter(page);
        }
        .watermark {
            position: fixed;
            bottom: 10px;
            left: 10px;
            font-size: 8px;
            color: #999;
        }
        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
            font-size: 14px;
            background: #f8f9fa;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>SARGAM | Lal Bahadur Shastri Institute of Management</h1>
        <h3>Faculty Feedback Average Report</h3>
        <p>Generated on: {{ now()->format('d-M-Y H:i:s') }}</p>
    </div>

    <div class="filters-section">
        <h4>📊 Applied Filters</h4>
        <div class="filter-grid">
            <div class="filter-item">
                <span class="filter-label">Course Type:</span> 
                {{ ucfirst($courseType ?? 'current') }}
            </div>
            @if(!empty($currentProgramName))
            <div class="filter-item">
                <span class="filter-label">Program:</span> 
                {{ $currentProgramName }}
            </div>
            @endif
            @if(!empty($currentFaculty) && isset($faculties[$currentFaculty]))
            <div class="filter-item">
                <span class="filter-label">Faculty:</span> 
                {{ $faculties[$currentFaculty] }}
            </div>
            @endif
            @if(!empty($fromDate))
            <div class="filter-item">
                <span class="filter-label">From Date:</span> 
                {{ \Carbon\Carbon::parse($fromDate)->format('d M Y') }}
            </div>
            @endif
            @if(!empty($toDate))
            <div class="filter-item">
                <span class="filter-label">To Date:</span> 
                {{ \Carbon\Carbon::parse($toDate)->format('d M Y') }}
            </div>
            @endif
        </div>
    </div>

    @if(!empty($currentProgramName))
    <div class="program-title">
        {{ $currentProgramName }}
    </div>
    @endif

    @if($feedbackData->isEmpty())
        <div class="no-data">
            <p>No feedback data found for the selected filters.</p>
        </div>
    @else
        <table>
            <thead>
                <tr>
                    <th>Sl No.</th>
                    <th>Faculty Name</th>
                    <th>Topic</th>
                    <th>Program</th>
                    <th>Content (%)</th>
                    <th>Presentation (%)</th>
                    <th>Participants</th>
                    <th>Session Date</th>
                    <th>Session Time</th>
                </tr>
            </thead>
            <tbody>
                @foreach($feedbackData as $index => $data)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $data['faculty_name'] }}</td>
                    <td>{{ $data['topic_name'] }}</td>
                    <td>{{ $data['program_name'] }}</td>
                    <td class="
                        {{ $data['content_percentage'] >= 80 ? 'percentage-good' : 
                           ($data['content_percentage'] >= 60 ? 'percentage-average' : 'percentage-low') }}">
                        {{ number_format($data['content_percentage'], 2) }}%
                    </td>
                    <td class="
                        {{ $data['presentation_percentage'] >= 80 ? 'percentage-good' : 
                           ($data['presentation_percentage'] >= 60 ? 'percentage-average' : 'percentage-low') }}">
                        {{ number_format($data['presentation_percentage'], 2) }}%
                    </td>
                    <td style="text-align: center;">{{ $data['participants'] }}</td>
                    <td>{{ \Carbon\Carbon::parse($data['session_date'])->format('d M Y') }}</td>
                    <td>{{ $data['class_session'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Summary Statistics -->
        <div class="summary-section">
            <h4>📈 Summary Statistics</h4>
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="summary-value">{{ $feedbackData->count() }}</div>
                    <div class="summary-label">Total Sessions</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value">{{ number_format($feedbackData->avg('content_percentage'), 2) }}%</div>
                    <div class="summary-label">Average Content</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value">{{ number_format($feedbackData->avg('presentation_percentage'), 2) }}%</div>
                    <div class="summary-label">Average Presentation</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value">{{ $feedbackData->sum('participants') }}</div>
                    <div class="summary-label">Total Participants</div>
                </div>
            </div>
        </div>

        <!-- Rating Distribution Summary -->
        <div class="summary-section" style="margin-top: 15px;">
            <h4>📊 Rating Distribution</h4>
            @php
                $totalExcellent = $feedbackData->where('content_percentage', '>=', 90)->count() + 
                                 $feedbackData->where('presentation_percentage', '>=', 90)->count();
                $totalGood = $feedbackData->whereBetween('content_percentage', [70, 89.99])->count() + 
                            $feedbackData->whereBetween('presentation_percentage', [70, 89.99])->count();
                $totalAverage = $feedbackData->where('content_percentage', '<', 70)->count() + 
                               $feedbackData->where('presentation_percentage', '<', 70)->count();
            @endphp
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="summary-value percentage-good">{{ $totalExcellent }}</div>
                    <div class="summary-label">Excellent (≥90%)</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value percentage-average">{{ $totalGood }}</div>
                    <div class="summary-label">Good (70-89%)</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value percentage-low">{{ $totalAverage }}</div>
                    <div class="summary-label">Needs Improvement (&lt;70%)</div>
                </div>
            </div>
        </div>
    @endif

    <div class="footer">
        <div class="page-number"></div>
        <div>Generated by Sargam | Lal Bahadur Shastri Institute of Management</div>
    </div>
    
    <div class="watermark">
        Confidential Document
    </div>
</body>
</html>