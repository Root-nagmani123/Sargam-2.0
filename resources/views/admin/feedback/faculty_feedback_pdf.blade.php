<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Faculty Feedback Report</title>
    <style>
        /* Match the web view styling */
        :root {
            --primary: #af2910;
            --secondary: #f4f6f9;
            --border: #d0d7de;
            --text-dark: #1f2937;
        }

        body {
            font-family: Arial, sans-serif;
            background: white;
            color: var(--text-dark);
            font-size: 11px;
            line-height: 1.4;
            margin: 0;
            padding: 15px;
        }

        .header {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--primary);
        }

        .header h1 {
            margin: 0;
            color: var(--primary);
            font-size: 18px;
            font-weight: 600;
        }

        .header h2 {
            margin: 5px 0;
            font-size: 14px;
            color: #555;
        }

        .filters-section {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 25px;
            border: 1px solid var(--border);
        }

        .filters-section h3 {
            margin: 0 0 12px 0;
            color: var(--primary);
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 25px;
            margin-bottom: 8px;
        }

        .filter-item {
            flex: 1;
            min-width: 180px;
        }

        .filter-label {
            font-weight: 600;
            color: #555;
            display: inline-block;
            width: 100px;
        }

        .filter-value {
            color: #333;
            font-weight: normal;
        }

        /* Feedback section styling */
        .feedback-section {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }

        .meta-info {
            text-align: center;
            margin-bottom: 20px;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 5px;
            border: 1px solid var(--border);
        }

        .meta-info p {
            margin: 4px 0;
            font-size: 11px;
        }

        .faculty-type-badge {
            font-size: 9px;
            padding: 2px 6px;
            border-radius: 10px;
            background: #e9ecef;
            color: #495057;
            font-weight: 600;
            margin-left: 5px;
        }

        /* Table styling to match web view */
        .feedback-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            border: 1px solid var(--border);
        }

        .feedback-table th {
            background-color: #eef4fb;
            color: var(--text-dark);
            padding: 8px;
            text-align: left;
            border: 1px solid var(--border);
            font-size: 10px;
            font-weight: 600;
        }

        .feedback-table td {
            padding: 6px;
            border: 1px solid var(--border);
            font-size: 10px;
            vertical-align: middle;
        }

        .rating-header {
            color: var(--primary) !important;
            font-weight: 600;
        }

        .percentage-cell {
            font-weight: 600;
            color: var(--primary);
        }

        /* Remarks styling */
        .remarks-title {
            background: var(--primary);
            color: #fff;
            padding: 6px 10px;
            font-weight: 600;
            border-radius: 4px 4px 0 0;
            font-size: 11px;
        }

        .remarks-list {
            border: 1px solid var(--border);
            border-top: 0;
            border-radius: 0 0 4px 4px;
            padding: 10px 10px 10px 25px;
            margin-bottom: 15px;
            background: white;
        }

        .remarks-list li {
            margin-bottom: 4px;
            font-size: 10px;
        }

        hr {
            border: none;
            border-top: 1px dashed #ddd;
            margin: 20px 0;
        }

        /* Footer */
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 9px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }

        .page-break {
            page-break-after: always;
        }

        .total-info {
            font-size: 10px;
            color: #666;
            text-align: center;
            margin-top: 5px;
            font-style: italic;
        }

        /* Print-specific styles */
        @media print {
            body {
                font-size: 10px;
                padding: 10px;
            }
            
            .feedback-section {
                page-break-inside: avoid;
            }
            
            .header h1 {
                font-size: 16px;
            }
            
            .feedback-table {
                font-size: 9px;
            }
            
            .feedback-table th,
            .feedback-table td {
                padding: 4px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Faculty Feedback with Comments (Admin View)</h1>
        <h2>Sargam | Lal Bahadur Shastri Institute of Management</h2>
        <p>Report Generated: {{ $export_date }}</p>
    </div>

    <div class="filters-section">
        <h3>Applied Filters</h3>
        <div class="filter-row">
            <div class="filter-item">
                <span class="filter-label">Course Status:</span>
                <span class="filter-value">{{ $filters['course_type'] }}</span>
            </div>
            <div class="filter-item">
                <span class="filter-label">Program:</span>
                <span class="filter-value">{{ $filters['program'] }}</span>
            </div>
            <div class="filter-item">
                <span class="filter-label">Faculty Name:</span>
                <span class="filter-value">{{ $filters['faculty_name'] }}</span>
            </div>
        </div>
        <div class="filter-row">
            <div class="filter-item">
                <span class="filter-label">Faculty Type:</span>
                <span class="filter-value">{{ $filters['faculty_type'] }}</span>
            </div>
            <div class="filter-item">
                <span class="filter-label">Date Range:</span>
                <span class="filter-value">{{ $filters['date_range'] }}</span>
            </div>
            <div class="filter-item">
                <span class="filter-label">Total Records:</span>
                <span class="filter-value">{{ count($feedbackData) }}</span> {{-- FIXED: Use count() function --}}
            </div>
        </div>
    </div>

    @if(count($feedbackData) > 0) {{-- FIXED: Use count() function --}}
        @foreach($feedbackData as $index => $data)
            <div class="feedback-section">
                <!-- META INFO -->
                <div class="meta-info">
                    <p class="mb-1"><strong>Course:</strong> {{ $data['Program Name'] ?? '' }}
                        @if(isset($data['Course Status']))
                            <span class="faculty-type-badge">{{ $data['Course Status'] }}</span>
                        @endif
                    </p>
                    <p class="mb-1">
                        <strong>Faculty:</strong> {{ $data['Faculty Name'] ?? '' }}
                        <span class="faculty-type-badge">{{ $data['Faculty Type'] ?? '' }}</span>
                    </p>
                    <p class="mb-1"><strong>Topic:</strong> {{ $data['Topic'] ?? '' }}</p>
                    @if (!empty($data['Lecture Date']))
                        <p class="mb-0">
                            <strong>Lecture Date:</strong> {{ $data['Lecture Date'] }}
                            @if (!empty($data['Time']))
                                {{ $data['Time'] }}
                            @endif
                        </p>
                    @endif
                </div>

                <!-- FEEDBACK TABLE -->
                <table class="feedback-table">
                    <thead>
                        <tr>
                            <th scope="col">Rating</th>
                            <th scope="col">Content <span style="color: #1f2937">*</span></th>
                            <th scope="col">Presentation <span style="color: #1f2937">*</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Excellent -->
                        <tr>
                            <th class="rating-header">Excellent</th>
                            <td>{{ $data['Content - Excellent'] ?? 0 }}</td>
                            <td>{{ $data['Presentation - Excellent'] ?? 0 }}</td>
                        </tr>
                        <!-- Very Good -->
                        <tr>
                            <th class="rating-header">Very Good</th>
                            <td>{{ $data['Content - Very Good'] ?? 0 }}</td>
                            <td>{{ $data['Presentation - Very Good'] ?? 0 }}</td>
                        </tr>
                        <!-- Good -->
                        <tr>
                            <th class="rating-header">Good</th>
                            <td>{{ $data['Content - Good'] ?? 0 }}</td>
                            <td>{{ $data['Presentation - Good'] ?? 0 }}</td>
                        </tr>
                        <!-- Average -->
                        <tr>
                            <th class="rating-header">Average</th>
                            <td>{{ $data['Content - Average'] ?? 0 }}</td>
                            <td>{{ $data['Presentation - Average'] ?? 0 }}</td>
                        </tr>
                        <!-- Below Average -->
                        <tr>
                            <th class="rating-header">Below Average</th>
                            <td>{{ $data['Content - Below Average'] ?? 0 }}</td>
                            <td>{{ $data['Presentation - Below Average'] ?? 0 }}</td>
                        </tr>
                        <!-- Percentage -->
                        <tr style="font-weight: 600;">
                            <th class="rating-header">Percentage</th>
                            <td class="percentage-cell">{{ $data['Content Percentage'] ?? '0.00%' }}</td>
                            <td class="percentage-cell">{{ $data['Presentation Percentage'] ?? '0.00%' }}</td>
                        </tr>
                    </tbody>
                </table>
                {{-- <div class="total-info">* is defined as Total Student Count: {{ $data['Total Participants'] ?? 0 }}</div> --}}

                <!-- REMARKS -->
                @if (!empty($data['Remarks']))
                    <div style="margin-top: 15px;">
                        <div class="remarks-title">Remarks ({{ count(explode("\n", $data['Remarks'])) }})</div>
                        <ol class="remarks-list">
                            @php
                                $remarks = explode("\n", $data['Remarks']);
                                $filteredRemarks = array_filter($remarks, function($remark) {
                                    return !empty(trim($remark));
                                });
                            @endphp
                            @foreach($filteredRemarks as $remark)
                                <li>{{ trim($remark) }}</li>
                            @endforeach
                        </ol>
                    </div>
                @endif

                @if(!$loop->last)
                    <hr>
                @endif
            </div>
        @endforeach
    @else
        <div style="text-align: center; padding: 40px; color: #666; background: #f8f9fa; border-radius: 5px; border: 1px solid var(--border);">
            <h3 style="margin: 0; color: var(--primary);">No feedback data found for the selected filters.</h3>
        </div>
    @endif

    <div class="footer">
        <p>Confidential - For Internal Use Only | Page 1 of 1</p>
        <p>Generated by Sargam Faculty Feedback System</p>
    </div>
</body>
</html>