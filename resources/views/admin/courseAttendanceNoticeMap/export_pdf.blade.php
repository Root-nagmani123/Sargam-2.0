<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Notice/Memo Management Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 10px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #004a93;
            padding-bottom: 10px;
        }

        .header h1 {
            margin: 0;
            color: #004a93;
            font-size: 18px;
        }

        .header h2 {
            margin: 5px 0;
            color: #666;
            font-size: 14px;
            font-weight: normal;
        }

        .filters {
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f8f9fa;
            border-left: 4px solid #004a93;
        }

        .filters p {
            margin: 3px 0;
            font-size: 9px;
        }

        .filters strong {
            color: #004a93;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 8px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 5px;
            text-align: left;
        }

        th {
            background-color: #004a93;
            color: white;
            font-weight: bold;
            font-size: 9px;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .badge {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
        }

        .badge-notice {
            background-color: #cfe2ff;
            color: #084298;
        }

        .badge-memo {
            background-color: #e2e3e5;
            color: #41464b;
        }

        .badge-open {
            background-color: #d1e7dd;
            color: #0f5132;
        }

        .badge-close {
            background-color: #f8d7da;
            color: #842029;
        }

        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 8px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }

        .no-data {
            text-align: center;
            padding: 20px;
            color: #999;
            font-style: italic;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Notice / Memo Management Report</h1>
        <h2>Lal Bahadur Shastri National Academy of Administration</h2>
        <p style="margin: 5px 0; color: #666; font-size: 9px;">Generated on: {{ Carbon\Carbon::now()->format('d-m-Y h:i A') }}</p>
    </div>

    <div class="filters">
        <p><strong>Applied Filters:</strong></p>
        @if($fromDateFilter || $toDateFilter)
            <p><strong>Date Range:</strong> 
                {{ $fromDateFilter ? Carbon\Carbon::parse($fromDateFilter)->format('d-m-Y') : 'N/A' }} 
                to 
                {{ $toDateFilter ? Carbon\Carbon::parse($toDateFilter)->format('d-m-Y') : 'N/A' }}
            </p>
        @endif
        @if($programNameFilter && $selectedCourse)
            <p><strong>Program:</strong> {{ $selectedCourse->course_name }}</p>
        @endif
        @if($typeFilter !== null && $typeFilter !== '')
            <p><strong>Type:</strong> {{ $typeFilter == '1' ? 'Notice' : ($typeFilter == '0' ? 'Memo' : 'All') }}</p>
        @endif
        @if($statusFilter !== null && $statusFilter !== '')
            <p><strong>Status:</strong> {{ $statusFilter == '1' ? 'Open' : 'Close' }}</p>
        @endif
        @if($searchFilter)
            <p><strong>Search:</strong> {{ $searchFilter }}</p>
        @endif
        <p><strong>Total Records:</strong> {{ $memos->count() }}</p>
    </div>

    @if($memos->isEmpty())
        <div class="no-data">
            <p>No records found based on the applied filters.</p>
        </div>
    @else
        <table>
            <thead>
                <tr>
                    <th style="width: 3%;">S.No.</th>
                    <th style="width: 12%;">Program Name</th>
                    <th style="width: 12%;">Participant Name</th>
                    <th style="width: 6%;">Type</th>
                    <th style="width: 8%;">Session Date</th>
                    <th style="width: 12%;">Topic</th>
                    <th style="width: 8%;">Status</th>
                    <th style="width: 10%;">Conclusion Type</th>
                    <th style="width: 12%;">Discussion Name</th>
                    <th style="width: 17%;">Conclusion Remark</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($memos as $index => $memo)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $memo->course_name ?? 'N/A' }}</td>
                        <td>{{ $memo->student_name ?? 'N/A' }}</td>
                        <td>
                            @if ($memo->notice_memo == '1')
                                <span class="badge badge-notice">Notice</span>
                            @elseif ($memo->notice_memo == '2')
                                <span class="badge badge-memo">Memo</span>
                            @else
                                <span>Other</span>
                            @endif
                        </td>
                        <td>{{ $memo->date_ ? Carbon\Carbon::parse($memo->date_)->format('d-m-Y') : 'N/A' }}</td>
                        <td>{{ $memo->topic_name ?? 'N/A' }}</td>
                        <td>
                            @if ($memo->status == 1)
                                <span class="badge badge-open">Open</span>
                            @else
                                <span class="badge badge-close">Close</span>
                            @endif
                        </td>
                        <td>
                            @if($memo->type_notice_memo == 'Memo')
                                Memo Generated
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            @if($memo->type_notice_memo == 'Memo' && isset($memo->communication_status) && $memo->communication_status == 2)
                                {{ $memo->discussion_name ?? '-' }}
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            @if($memo->type_notice_memo == 'Memo' && isset($memo->communication_status) && $memo->communication_status == 2)
                                {{ $memo->conclusion_remark ?? '-' }}
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        <p>This is a system generated report. For any queries, please contact the administration.</p>
        <p>Page 1 of 1</p>
    </div>
</body>

</html>

